<?php

/**
 * Client side ad handling.
 * Called from either theme function DFP_AD() or shortcode DFP_AD.
 * Requires 'size' param.
 * @package Auto DFP
 * @author Ray Viljoen
 */

class Auto_DFP_Ads
{	
	/**
	 * Reference to Auto_DFP_Data instance.
	 * @var object
	 */
	private $data;
	
	
	/**
	 * Plugin URL links errors to help pages etc.
	 * @var string
	 */
	private static $pluginURL = 'http://wordpress.org/extend/plugins/Auto-DFP/';

	
	//=============================================================
	//       					METHODS
	//=============================================================

	
	/**
	 * adUnit constructor.
	 * @param string $size.
	 * @return string
	 */
	public function __construct()
	{
		// Reference instance of Auto_DFP_Data
		$this->data = new Auto_DFP_Data();
	}
	
	
	/**
	 * Called remotely via AJAX notification.
	 * Creates new pending ad slot.
	 * @param void.
	 * @return void
	 */
	public static function tagNewSlot()
	{
		// Data instance
		$data = new Auto_DFP_Data();
				
		$page = intval($_GET['new_dfp_tag']);
		
		$size = $_GET['dfp_tag_size'];
		
		$pageAtts = get_page( $page );
		
		$adUnit = get_bloginfo('name');
		
		$ancestors = $pageAtts->ancestors;
		
		if(count($ancestors)){
			$ancestors = array_reverse($ancestors);
			foreach($ancestors as $id){
				$adUnit .= '_'.get_page($id)->post_name;
			}
		}
		
		$adUnit .= '_'.$pageAtts->post_name;
		
		$adUnit = str_replace(' ', '_', $adUnit);
		
// --------------------------------------------------------------------
//					create size to add to slot create here    --  TODO
// --------------------------------------------------------------------				
		$data->createSlot( $adUnit, $page, array(300, 175) );
	
	}
	
	
	/**
	 * Creates array of adSlots for a specific page ID.
	 * @param number $id.
	 * @return array
	 */
	private function getSlots($id)
	{	
		// Request all existing adSlots for curretn page
		$slotsObj = $this->data->getPageSlots($id);
		
		// Create adUnit array - also used as object in js
		$adSlots = array();
		foreach( $slotsObj as $slot ){
			$adSlots[$slot->size_w.'x'.$slot->size_h] = array(
				'name'  => $slot->adunit,
				'status' => $slot->status
			);
		}		
		return $adSlots;
	}
	
	
	/**
	 * Loads in header javascript.
	 * @param void.
	 * @return string
	 */
	public function adLoaderHeader()
	{			
		global $post;
					
		echo '<pre>'; $xx = get_page( $post->ID );
		var_dump($xx);
		echo '</pre>';
					
		$adUnitName = NULL;
		$publisherID = get_option('dfp_prop_code');
		
		// HEADER JS
		
		// Get all ad slots for page
		$adSlots = $this->getSlots($post->ID);
				
		// Load dfp Scripts and include global $post variables as JS
		echo "<script type='text/javascript' src='http://partner.googleadservices.com/gampad/google_service.js'></script>";
		echo "<script type='text/javascript'>GS_googleAddAdSenseService('".$publisherID."'); GS_googleEnableAllServices();</script>";
		echo "<script type='text/javascript'>";
		// print individual slot loaders
		foreach( $adSlots as $slot ){
			// Check slot has been approved & print hedaer unit
			if( $slot['status'] == 'approved' ){
				echo "GA_googleAddSlot('".$publisherID."', '".$slot['name']."');";
			}
		}
		echo "</script>";
		echo "<script type='text/javascript'>GA_googleFetchAds();</script>";
	}
	
	
	/**
	 * Loads in footer javascript.
	 * @param void.
	 * @return string
	 */
	public function adLoaderFooter()
	{
		global $post;
		
		// Get all ad slots for page
		$adSlots = $this->getSlots($post->ID);
		
		// ready array for use in js
		$jsAdSlots = json_encode($adSlots);
		
		// Auto DFP JS path
		$jsPath = plugins_url( '/js/', __FILE__ );
				
		// Load jQuery if not already
		wp_enqueue_script( 'jquery' );
		
		// Include jsLoader & call with page id
		echo "<script type='text/javascript' src='".$jsPath."adLoader.js' ></script>";
		echo "<script type='text/javascript'>jQuery(function(){ jsLoader(".$post->ID.", ".$jsAdSlots."); });</script>";
	}

}