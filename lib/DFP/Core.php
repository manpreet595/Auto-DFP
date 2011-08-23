<?php

/**
 * Main class for handling services, login etc.
 * @package Auto DFP
 * @author Ray Viljoen
 */

class Auto_DFP
{
	/**
	 * Application Name.
	 * @var string
	 */
	private $name = 'Auto-DFP';

	/**
	 * DFP API Version.
	 * @var string
	 */
	private $api = 'v201107';

	/**
	 * DFP User Instance.
	 * @var object
	 */
	protected $user;
	
	/**
	 * User logged in status.
	 * @var BOOL
	 */
	protected $loggedIn = FALSE;
	


	//=============================================================
	//       					METHODS
	//=============================================================

	/**
	 * Handles DFP api access auth & account login.
	 * Creates autorised service object.
	 * @return object
	 */
	protected function login()
	{	
		
		// Get user Info
		$wpUser = wp_get_current_user();
		$wpUser = $wpUser->ID;
		
		// Check if user id already authenticated through session.
		if(isset($_SESSION['DFP']['authToken']) && isset($_SESSION['DFP']['userID'])){
			if( $wpUser == $_SESSION['DFP']['userID'] ){
				$authToken = $_SESSION['DFP']['authToken'];
				$password = NULL;
			}
		}else{ 
			$password = $_POST['dfp_password'];
			$authToken = NULL;			
		}
		
		if(isset($_POST['dfp_username'])){
		
			$username = $_POST['dfp_username'];
			update_option('dfp_user', $username);
			
		}else{	$username = get_option('dfp_user'); }
		
		$networkid = get_option('dfp_network');
			
		// Check minimum login requirements
		if( !$username || ( !$password && !$authToken ) ){
			return FALSE;
		}
		
		// Try Login
		try {
			// Create new user
			$this->user = new DfpUser( NULL, $username, $password, $this->name, $networkid, NULL, $authToken );			
			
			$authToken = $this->user->GetAuthToken();

			$_SESSION['DFP']['authToken'] = $authToken;
			$_SESSION['DFP']['userID'] = $wpUser;
			
			// Log successful user login
			if($password != NULL){
				self::log('SUCCESSFUL LOGIN: '.'wp_user '.$wpUser );
			}
			
			return $authToken;

		} catch (Exception $e) {
			// Log exception
			self::log('LOGIN ERROR: '.$e->GetMessage());
			return FALSE;
		}
		
	}
	
	
	/**
	 * Return path to plugin.
	 * @return string
	 */
	public static function pluginPath()
	{ 
		return( plugins_url('/', dirname(__DIR__)) );
	}




	private function GetAllAdUnits() {
		// Get the InventoryService.
		$inventoryService = $this->user->GetService('InventoryService', $this->api);

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
	
	protected static function logout()
	{
		return session_destroy();
	}
	
	protected static function log($message = NULL)
	{	
		$path = dirname(__FILE__) . '/../../logs/'.date( "d-m-y" );
		$logFile = fopen($path, 'a');
		$message = '('.time().') '.$message."\n";
		fwrite($logFile, $message);
		fclose($logFile);
	}

}