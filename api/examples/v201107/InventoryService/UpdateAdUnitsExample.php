<?php
/**
 * This example updates an ad unit by enabling AdSense on the first 500. To
 * determine which ad units exist, run GetAllAdUnitsExample.php or
 * GetInventoryTreeExample.php.
 *
 * Tags: InventoryService
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
 * @subpackage v201107
 * @category   WebServices
 * @copyright  2011, Google Inc. All Rights Reserved.
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache License,
 *             Version 2.0
 * @author     Adam Rogal <api.arogal@gmail.com>
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

  // Get the InventoryService.
  $inventoryService = $user->GetService('InventoryService', 'v201107');

  // Create a statement to get all ad units.
  $filterStatement = new Statement("LIMIT 500");

  // Get ad units by statement.
  $page = $inventoryService->getAdUnitsByStatement($filterStatement);

  if (isset($page->results)) {
    $adUnits = $page->results;

    // Update each local ad unit object by enabling AdSense.
    foreach ($adUnits as $adUnit) {
      $adUnit->inheritedAdSenseSettings->value->adSenseEnabled = TRUE;
    }

    // Update the ad units on the server.
    $adUnits = $inventoryService->updateAdUnits($adUnits);

    // Display results.
    if (isset($adUnits)) {
      foreach ($adUnits as $adUnit) {
        print 'Ad unit with ID "' . $adUnit->id . '", name "' . $adUnit->name
            . '", and AdSense enabled "'
            . ($adUnit->inheritedAdSenseSettings->value->adSenseEnabled
                ? 'TRUE' : 'FALSE')
            . "\" was updated.\n";
      }
    }
  } else {
    print "No ad units updated.\n";
  }
} catch (Exception $e) {
  print $e->getMessage() . "\n";
}
