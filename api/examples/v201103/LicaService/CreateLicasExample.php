<?php
/**
 * This example creates new line item creative associations (LICAs) for an
 * existing line item and a set of creative ids. For small business networks,
 * the creative ids must represent new or copied creatives as creatives cannot
 * be used for more than one line item. For premium solution networks, the
 * creative ids can represent any creatvie. To copy creatives, run
 * CopyImageCreatives.php. To determine which LICAs exist, run
 * GetAllLicasExample.php.
 *
 * Tags: LineItemService, LineItemCreativeAssociationService
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

  // Get the LineItemCreativeAssociationService.
  $licaService = $user->GetLineItemCreativeAssociationService('v201103');

  // Get the LineItemService.
  $lineItemService = $user->GetLineItemService('v201103');

  // Set the line item ID and creative ID to associate
  // them with.
  $lineItemId = (float) 'INSERT_LINE_ITEM_ID_HERE';
  $creativeId = (float) 'INSERT_CREATIVE_ID_HERE';

  // Create local LICA.
  $lica = new LineItemCreativeAssociation();
  $lica->creativeId = $creativeId;
  $lica->lineItemId = $lineItemId;

  $licas = array($lica);

  // Create the LICAs on the server.
  $licas = $licaService->createLineItemCreativeAssociations($licas);

  // Display results.
  if (isset($licas)) {
    foreach ($licas as $lica) {
      print 'A LICA with line item ID "' . $lica->lineItemId
          . '", creative ID "' . $lica->creativeId
          . '", and status "' . $lica->status
          . "\" was created.\n";
    }
  } else {
    print "No LICAs created.";
  }
} catch (Exception $e) {
  print $e->getMessage() . "\n";
}
