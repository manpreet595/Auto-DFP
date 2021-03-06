<?php
/**
 * This example approves and overbooks all draft orders. To determine which
 * orders exist, run GetAllOrdersExample.php.
 *
 * Tags: OrderService
 *
 * PHP version 5
 *
 * Copyright 2011, Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package    GoogleApiAdsDfp
 * @subpackage v201103
 * @category   WebServices
 * @copyright  2011, Google Inc. All Rights Reserved.
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @author     Eric Koleda <api.ekoleda@gmail.com>
 */

error_reporting(E_STRICT | E_ALL);

// You can set the include path to src directory or reference
// DfpUser.php directly via require_once.
// $path = '/path/to/dfp_api_php_lib/src';
$path = dirname(__FILE__) . '/../../../src';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

require_once 'Google/Api/Ads/Dfp/Lib/DfpUser.php';

try {
  // Get DfpUser from credentials in "../auth.ini"
  // relative to the DfpUser.php file's directory.
  $user = new DfpUser();

  // Log SOAP XML request and response.
  $user->LogDefaults();

  // Get the OrderService.
  $orderService = $user->GetOrderService('v201103');

  // Create statement text to get all draft or pending approval orders.
  $filterStatementText =
      "WHERE status = 'DRAFT' OR status = 'PENDING_APPROVAL'";

  $offset = 0;

  do {
    // Create statement to page through results.
    $filterStatement =
        new Statement($filterStatementText . " LIMIT 500 OFFSET " . $offset);

    // Get orders by statement.
    $page = $orderService->getOrdersByStatement($filterStatement);

    // Display results.
    $orderIds = array();
    if (isset($page->results)) {
      $i = $page->startIndex;
      foreach ($page->results as $order) {
        // Archived orders cannot be approved.
        if (!$order->isArchived) {
          print $i . ') Order with ID "' . $order->id
              . '", name "' . $order->name
              . '", and status "' . $order->status
              . "\" will be approved.\n";
          $i++;
          $orderIds[] = $order->id;
        }
      }
    }

    $offset += 500;
  } while ($offset < $page->totalResultSetSize);

  print 'Number of orders to be approved: ' . sizeof($orderIds) . "\n";

  if (sizeof($orderIds) > 0) {
    // Create action statement.
    $filterStatementText =
        sprintf('WHERE id IN (%s)', implode(',', $orderIds));
    $filterStatement = new Statement($filterStatementText);

    // Create action.
    $action = new ApproveAndOverbookOrders();

    // Perform action.
    $result = $orderService->performOrderAction($action, $filterStatement);

    // Display results.
    if (isset($result) && $result->numChanges > 0) {
      print 'Number of orders approved: ' . $result->numChanges . "\n";
    } else {
      print "No orders were approved.\n";
    }
  }
} catch (Exception $e) {
  print $e->getMessage() . "\n";
}
