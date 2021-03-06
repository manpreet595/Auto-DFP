<?php

/**
 * Interacts with API, so login etc. is only done from here.
 * Responsible for all administrative tasks & only class capable of accessing DFP account.
 * @package Auto DFP
 * @author Ray Viljoen
 */
class Auto_DFP_Admin
{
	/**
	 * Switche between production and sandbox account.
	 * @var BOOL
	 */
	private $production = FALSE;
	
	/**
	 * Application Name.
	 * @var string
	 */
	private $name = 'Auto-DFP';

	/**
	 * DFP API Version.
	 * @var string
	 */
	private $api = '201108';
	
	/**
	 * DFP API Version.
	 * @var string
	 */
	private $loginError = FALSE;

	/**
	 * Whether to display the 'settings saved' message on the admin page.
	 * @var bool
	 */
	private $saved = FALSE;

	/**
	 * Reference to Auto_DFP_Data instance.
	 * @var object
	 */
	private $data;

	/**
	 * DFP User Instance.
	 * @var object
	 */
	private $user;
	
	/**
	 * Adunits in live account.
	 * @var array
	 */
	private $dfpAccountAdUnits = array();
	
	/**
	 * DFP service being used.
	 * @var object
	 */
	private $service;

	/**
	 * User logged in status.
	 * @var BOOL
	 */
	private $loggedIn = FALSE;

	/**
	 * List of admin pages.
	 * @var array
	 */
	private $menu_items = array(
		'Inventory',
		'Users'
	);
	
	/**
	 * Default ad unit description.
	 * @var string
	 */
	private $adDescription = 'Auto DFP generated ad unit for '; /* Site name appended in dfpAdUnit() */
		
	private $menu_slugs = array(
		'Inventory' => '?page=dfp_options&dfp_menu=inventory',
		'Users' => '?page=dfp_options&dfp_menu=users'
	);


	//=============================================================
	//            			METHODS
	//=============================================================


	/**
	 * Called from admin hook. Processes and outputs admin menu.
	 * @param void
	 * @return void
	 */
	public function __construct()
	{
		// Get user Info
		$wpUser = wp_get_current_user();
		$wpUser = $wpUser->ID;
		
		// Check if user requested logout
		if(isset($_GET['dfp_logout'])){
			// Record Logout
			self::log('USER LOGOUT: '.'wp_user '.$wpUser );
			$this->logout();
			// set message to confirm logout - TODO --------------------

			// Try Login
		}elseif($this->login()){

			// Create Auto_DFP_Data instance
			$this->data = new Auto_DFP_Data();
			
			// Set logged in True
			$this->loggedIn = TRUE;
		}

		//=====================================
		//    		OUTPUT ADMIN PAGE
		//=====================================

		// Print Admin Stylesheet
		echo '<link rel="stylesheet" type="text/css" href="' .plugins_url('/', dirname(__FILE__)).'DFP/pages/admin.css">';
		// Check if user is logged in and display appropriate admin page.
		if( $this->loggedIn ){
						
			// Check for any slot actions.
			$this->checkActions();
			// Show selected admin page
			$this->getAdminPage();

		}else{
			$loginPage = include 'pages/login.php';
		}
	}


	/**
	 * Handles DFP api access auth & account login.
	 * Creates autorised service object.
	 * @param [string DFP Service]
	 * @return object
	 */
	private function login($service = FALSE)
	{
		// Get user Info
		$wpUser = wp_get_current_user();
		$wpUser = $wpUser->ID;
		
		// Get wp url
		$wpURL = get_bloginfo('url');
		
		if(isset($_POST['dfp_password']) && isset($_POST['dfp_username']) ){

			$password = $_POST['dfp_password'];
			$username = $_POST['dfp_username'];
			// Optional Network ID
			$networkid = (isset($_POST['dfp_network'])) ? $_POST['dfp_network'] : NULL;
			$authToken = NULL;

		// Check if user id already authenticated through session.
		}elseif(isset($_SESSION['DFP']['authToken']) && isset($_SESSION['DFP']['userID'])){

			// Make sure of session
			if( ($wpUser == $_SESSION['DFP']['userID']) && ($wpURL == $_SESSION['DFP']['url']) ){
				$username = $_SESSION['DFP']['username'];
				$authToken = $_SESSION['DFP']['authToken'];
				// Optional Network ID
				$networkid = isset($_SESSION['DFP']['networkID']) ? $_SESSION['DFP']['networkID'] : NULL;
				$password = NULL;
			}
		}else{		
			// Log exception
			self::log('BLANK LOGIN: wp_user '.$wpUser);
			return FALSE;
		}
				
		// Try Login
		try {

			// Create new user
			$this->user = new DfpUser( NULL, $username, $password, $this->name, $networkid, NULL, $authToken );
			
			// Get authtoken
			$authToken = $this->user->GetAuthToken();
						
			// Update session
			$this->setSession($username, $networkid, $authToken, $wpUser, $wpURL);

			// Log successful user login
			if($password != NULL){
				self::log('SUCCESSFUL LOGIN: '.'wp_user '.$wpUser );
				
				// if user logged in manually - Update DFP Property Code
				$NetworkService = $this->dfpGetService('NetworkService');
				$propCode = $NetworkService->getCurrentNetwork();
				update_option('dfp_prop_code', $propCode->propertyCode);
			}

			// Create requested service
			if($service){
				$this->dfpGetService($service);
			}

			return $authToken;

		} catch (Exception $e) {

			// Remove Session data
			self::logout();
			// Log exception
			self::log('LOGIN ERROR: '.$e->GetMessage());

			$this->loginError = TRUE;

			return FALSE;
		}
	}


	/**
	 * Accesses one of the DFP Services.
	 * @param string $serviceType options:
	 CompanyService
	 CreativeService
	 CustomTargetingService
	 ForecastService
	 InventoryService
	 LabelService
	 LineItemCreativeAssoci...
	 LineItemService
	 NetworkService
	 OrderService
	 PlacementService
	 PublisherQueryLanguage...
	 ReportService
	 UserService
	 */
	private function dfpGetService($serviceType)
	{	
		// Check if sandbox enironment is being used else set server url to production.
		$server = ( $this->production ) ? $server = 'https://www.google.com/' : NULL;
		return( $this->user->GetService($serviceType, $this->api, $server) );
	}


	/**
	 * Log user out by unsetting session.
	 * @param void
	 * @return BOOL
	 */
	private function logout()
	{
		unset($_SESSION['DFP']);
	}


	/**
	 * Updates session vars.
	 * @param string $username, string $networkid, string $authToken, number $wpUser, string $wpURL
	 * @return void
	 */
	private function setSession($username, $networkid, $authToken, $wpUser, $wpURL)
	{
		$_SESSION['DFP']['username']  = $username;
		$_SESSION['DFP']['authToken'] = $authToken;
		$_SESSION['DFP']['userID']    = $wpUser;
		$_SESSION['DFP']['networkID'] = $networkid;
		$_SESSION['DFP']['url']       = $wpURL;
	}


	/**
	 * Logs message to daily log with timestamp.
	 * @param string $message
	 * @return void
	 */
	public static function log($message = NULL)
	{
		$path = dirname(__FILE__) . '/../logs/'.date( "d-m-y" );
		
		if(is_writable($path)){
			$logFile = fopen($path, 'a');
			$message = '('.time().') '.$message."\n";
			fwrite($logFile, $message);
			fclose($logFile);
		}
	}


	/**
	 * Creates and Outputs selected admin page.
	 * @param void
	 * @return void
	 */
	private function getAdminPage()
	{
		$invettoryPath = dirname(__FILE__) . '/pages/inventory.php';
		$settingsPath = dirname(__FILE__) . '/pages/settings.php';

		// Admin Page Header
		$this->adminHeader();
		// Settings Page
		include $settingsPath;
		// Specific Admin Page Body
		include $invettoryPath;
		// Footer. Could later be moved to getFooter.
		echo '</div><div class="props" ><a href="http://catn.com">Created by the experts at CatN</a></div>';
		echo '<script type="text/javascript">jQuery("#settings").click(function(){jQuery("#dfp .settings").slideToggle(500); return false;});</script>';
	}


	/**
	 * Called from admin page. Prints page header and menu.
	 * @param void
	 * @return void
	 */
	private function adminHeader()
	{
		echo '<div id="dfp" class="wrap">';
		echo '<div class="icon32 dfp_logo"><br></div>';
		echo '<h2>'.__( 'Auto DFP', 'menu-test' ).'<span><a href="?page=dfp_options&dfp_logout">Logout</a> | <a id="settings" href="#settings">Settings</a></span></h2>';

		if($this->saved){
			echo '<div class="updated"><p><strong>';
			_e('settings saved.', 'menu-test' );
			echo '</strong></p></div>';
		};
	}


	/**
	 * Creates adUnit Object.
	 * @param string $name, array( $size['w'], $size['h'] ), string $description, [string $target]
	 * @return void
	 */
	private function dfpAdUnit( $name, $size, $target = 'BLANK'  )
	{
		// Get the NetworkService.
		$networkService = $this->dfpGetService('NetworkService');
		
		// Get the effective root ad unit's ID for all ad units to be created under.
		$network = $networkService->getCurrentNetwork();
		$effectiveRootAdUnitId = $network->effectiveRootAdUnitId;

		$adUnit = new AdUnit();
		$adUnit->name = $name;
		$adUnit->parentId = $effectiveRootAdUnitId;
		$adUnit->description = $this->adDescription.get_bloginfo('name');
		$adUnit->targetWindow = $target;
		// Set the size of possible creatives that can match this ad unit.
		$adUnit->sizes = array(new Size( $size[0],  $size[1]));
		
		return $adUnit;
	}


	/**
	 * Creates adUnit[s].
	 * @param array(object AdUnit)
	 * @return void
	 */
	private function dfpCreateAdUnit($adUnits)
	{
		// Get the InventoryService.
		$inventoryService = $this->dfpGetService('InventoryService');
		
		if(is_array($adUnits)){
			// Create multiple ad units on the server.
			$adUnits = $inventoryService->createAdUnits($adUnits);
		}else{
			// Create single ad unit on the server.
			$adUnits = $inventoryService->createAdUnit($adUnits);
		}
	}


	/**
	 * Get all dfp ad units.
	 * @param void
	 * @return object
	 */
	private function dfpGetAdUnits() {
		// Get the InventoryService.
		$inventoryService = $this->dfpGetService('InventoryService');

		// Create array to hold all ad units.
		$adUnits = array();

		// Set defaults for page and statement.
		$page = new AdUnitPage();
		$filterStatement = new Statement();
		$offset = 0;

		do {
			// Create a statement to get all ad units.
			$filterStatement->query = 'LIMIT 500 OFFSET ' . $offset;

			// Get creatives by statement.
			$page = $inventoryService->getAdUnitsByStatement($filterStatement);

			if (isset($page->results)) {
				$adUnits = array_merge($adUnits, $page->results);
			}

			$offset += 500;
		} while ($offset < $page->totalResultSetSize);

		return $adUnits;
	}
	
	
	/**
	 * Checks GET for any new admin actions before loading page.
	 * @param void
	 * @return void
	 */
	private function checkActions()
	{
		// Check Approve Slots
		if(isset($_POST['dfp_approve'])){
			$this->approveSlots();
		}
		if(isset($_POST['dfp_merge'])){
			$this->mergeSlots();
		}
	}
	
	
	/**
	 * Approve all new slots and create in DFP account.
	 * @param void
	 * @return object
	 */
	private function approveSlots()
	{
		// Update pending slots to active
		$this->data->approvePendingLocal();		
	}

	
	/**
	 * Add all locally live slots to dfp account and set account status to live.
	 * @param void
	 * @return object
	 */
	private function mergeSlots()
	{
		set_time_limit(0);
		
		// Get approved slots not yet on live account
		$localLiveSlots = $this->data->getPageSlots( NULL, 'active' );
		
		$adUnits = array();
		
		$i = 0;
					
		foreach( $localLiveSlots as $slot ){
		
		$i++;
		
			//if( !$slot->dfp_status ){
			
				$size = array( $slot->size_w, $slot->size_h );
				// Create ad unit object
				$adUnits[] = $this->dfpAdUnit( $slot->adunit, $size  );
				
				echo '.';
				// Set new status to avoid trying to create duplicate in future.
				
				//TEMP $result = $this->data->updateMergedLocal($slot->adunit);
				
				// Make sure slot was updated.
				//if(!$result){ throw new Exception('Error: Cannot update '.$slot->adunit.' status.'); }
			//}
		}
		echo $i;
		//$this->dfpCreateAdUnit($adUnits);
	}

}