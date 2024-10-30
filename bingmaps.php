<?php

/*
Plugin Name: MapView for Bing Maps
Plugin URI: https://wordpress.org/extend/plugins/bingmaps/
Description: Adds a map to a web page using the Bing Maps API
Author: Malcolm Shergold
Version: 1.4
Author URI: https://www.corondeck.co.uk
*/

if (!class_exists('BingMapsPluginClass')) 
{
	$siteurl = get_option('siteurl');
	
	define('BINGMAPS_FILE_PATH', dirname(__FILE__).'/');
	define('BINGMAPS_DIR_NAME', basename(BINGMAPS_FILE_PATH));
	define('BINGMAPS_ADMIN_PATH', BINGMAPS_FILE_PATH . '/admin/');
	define('BINGMAPS_INCLUDE_PATH', BINGMAPS_FILE_PATH . '/include/');
	define('BINGMAPS_ADMINICON_PATH', BINGMAPS_ADMIN_PATH . 'images/');
	define('BINGMAPS_TEST_PATH', BINGMAPS_FILE_PATH . '/test/');

	define('BINGMAPSLIB_INCLUDE_PATH', BINGMAPS_INCLUDE_PATH);

	define('BINGMAPS_FOLDER', dirname(plugin_basename(__FILE__)));
	define('BINGMAPS_URL', plugins_url( '', __FILE__ ).'/');
	define('BINGMAPS_IMAGES_URL', BINGMAPS_URL . 'images/');
	define('BINGMAPS_ADMIN_URL', BINGMAPS_URL . 'admin/');
	define('BINGMAPS_ADMIN_IMAGES_URL', BINGMAPS_ADMIN_URL . 'images/');
	
	define('BINGMAPS_CODE_PREFIX', BINGMAPS_DIR_NAME);
	
	define('BINGMAPSLIB_PLUGIN_ID', 'StageShow');

	define('BINGMAPS_ADMINUSER_CAPABILITY', 'manage_options');
	define('BINGMAPSLIB_CAPABILITY_SETUPUSER', BINGMAPS_ADMINUSER_CAPABILITY);
	
	define('BINGMAPS_SHORTCODE_MAP', BINGMAPS_CODE_PREFIX.'-map');
	
	define('BINGMAPS_MAPCONTROL_SCRIPTURL', 'https://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0');
	
	include BINGMAPS_INCLUDE_PATH.'bingmapslib_plugin.php';      
	include BINGMAPS_INCLUDE_PATH.'bingmapslib_dbase_api.php';  
	    
	if (!class_exists('BingMapsDBaseClass')) 
																									{
	global $wpdb;
	
	class BingMapsDBaseClass extends BingMapsLibDBaseClass
	{
		var $adminOptions;
		
		function __construct() 
		{
			// Call base constructor
			$opts = array (
				'Caller'             => __FILE__,
				'PluginFolder'       => plugin_basename(dirname(__FILE__)),
				'CfgOptionsID'       => 'bingmapssettings',
				'DbgOptionsID'       => 'bingmapsdebug',				
			);	
			
			//$this->emailObj = new BingMapsLibHTMLEMailAPIClass($this);
					
			parent::__construct($opts);
		}

	    function upgradeDB()
	    {
		}
		
	}
}

	class BingMapsPluginClass extends BingMapsLibPluginClass
	{
		var $pluginDesc = 'MapView for Bing Maps';		

				
		function __construct() 
		{
			$myDBaseObj = new BingMapsDBaseClass();
			$this->myDBaseObj = $myDBaseObj;
			$pluginFile = __FILE__;
			
			$this->myDBaseObj->GetSessionID();	// Get Session ID while we can ...
			

			
			$this->env = array(
			    'Caller' => $pluginFile,
			    'PluginObj' => $this,
			    'DBaseObj' => $this->myDBaseObj,

			);
			
			parent::__construct($pluginFile, $myDBaseObj);
				
			// Init options & tables during activation & deregister init option
			register_activation_hook( $pluginFile, array(&$this, 'activate') );
			register_deactivation_hook( $pluginFile, array(&$this, 'deactivate') );	

			add_action('admin_print_styles', array(&$this, 'load_admin_styles') );
			
			$this->adminClassFilePrefix = 'bingmaps';
			$this->adminClassPrefix = 'BingMaps';

			$this->GetBingMapsOptions();
			
			$this->adminPagePrefix = basename(dirname($pluginFile));
			
			//Actions
			add_action('admin_menu', array(&$this, 'BingMaps_ap'));
			//add_action('activate_'.plugin_basename($pluginFile),  array(&$this, 'init'));
			add_action('wp_enqueue_scripts', array(&$this, 'load_user_styles'));
			
			//Filters
			add_shortcode(BINGMAPS_SHORTCODE_MAP, array(&$this, 'OutputContent_Map'));

/* TODO - Implement checkVersion() function 	
			if ($myDBaseObj->checkVersion())
			{
				// Versions are different ... call activate() to do any updates
				$this->activate();
			}	
*/		
		}

		function load_user_styles() 
		{
			//Add Style Sheet
			wp_enqueue_style(BINGMAPS_CODE_PREFIX, BINGMAPS_URL.'css/bingmaps.css'); 	  // BingMaps core style
			
			//Add Javascript
			wp_enqueue_script(BINGMAPS_CODE_PREFIX, BINGMAPS_URL.'js/bingmaps.js'); 		// BingMaps javascript
		}
		
		//Returns an array of admin options
		function GetBingMapsOptions() 
		{
			$myDBaseObj = $this->myDBaseObj;
			
			return $myDBaseObj->adminOptions;
		}
    
		// Saves the admin options to the options data table
		function SaveBingMapsOptions() 
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$myDBaseObj->saveOptions();
		}
    
	    // ----------------------------------------------------------------------
	    // Activation / Deactivation Functions
	    // ----------------------------------------------------------------------
	    
	    function activate() 
		{
			$myDBaseObj = $this->myDBaseObj;

/* TODO - Implement Logs Folder option          
			$LogsFolder = ABSPATH . '/' . $myDBaseObj->adminOptions['LogsFolderPath'];
			if (!is_dir($LogsFolder))
				mkdir($LogsFolder, 0644, TRUE);
						
			$DLoadsFolder = ABSPATH . '/' . $myDBaseObj->adminOptions['DownloadFolderPath'];
			if (!is_dir($DLoadsFolder))
				mkdir($DLoadsFolder, 0644, TRUE);
*/
						
			$this->SaveBingMapsOptions();
      
			$myDBaseObj->upgradeDB();
		}

	    function deactivate()
	    {
	    }

		function GetMapAttributeVal($atts, $attID, $index=0)
		{
			$rtnVal = '';
			
			if (isset($atts[$attID]))
			{
				$scodeAtt = $atts[$attID];
				$matches = array();
				
				// Check if the shorcode parameter requests a URL parameter 
				if (preg_match('|^{(.*)}$|', $scodeAtt, $matches) > 0)
				{
					// Parameter is enclosed by {} - content defines URL parameter
					$paramID = $matches[1];
					if ($index > 0)
					{
						$paramID .= $index;
					}
					if (BingMapsLibUtilsClass::IsElementSet('get', $paramID))
					{
						// Get the URL parameter
						$rtnVal = BingMapsLibUtilsClass::GetHTTPTextElem($_GET, $paramID);
					}
					return $rtnVal;				
				}				
			}
			
			if ($index > 0)
			{
				$attID .= $index;
			}
				
			if (isset($atts[$attID]))
			{
				$rtnVal = $atts[$attID];				
			}
			
			return $rtnVal;
	    }

		function GetMapSize($atts, $attID)
		{
			$size = $atts[$attID];
			$lastChar = BingMapsLibMigratePHPClass::Safe_substr($size, -1);
			if (is_numeric($lastChar)) $size .= 'px';
			return $size;
		}
		
		function OutputContent_Map( $passedAtts )
		{
      		$myDBaseObj = $this->myDBaseObj;
			if (!isset($myDBaseObj->adminOptions['BingMapsKey']))
				return 'BingMaps not configured';
			
			if ($myDBaseObj->adminOptions['BingMapsKey'] === '')
				return 'BingMaps not configured';
				
			$defWidth  = ($myDBaseObj->adminOptions['BingMapDefWidth'] > 0) ? $myDBaseObj->adminOptions['BingMapDefWidth'] : 400;
			$defHeight = ($myDBaseObj->adminOptions['BingMapDefHeight'] > 0) ? $myDBaseObj->adminOptions['BingMapDefHeight'] : 400;
			$defZoom   = ($myDBaseObj->adminOptions['BingMapDefZoom'] > 0) ? $myDBaseObj->adminOptions['BingMapDefZoom'] : 14;
			
			// Merge Shorcode Attributes with defaults
			$atts = shortcode_atts(array(
				'posn'  => '',
				'w'  => $defWidth,
				'h'  => $defHeight,
				'zoom'  => $defZoom,
				'type' => 'road' 
			), $passedAtts );

      		$mapWidth  = $this->GetMapSize($atts, 'w');
      		$mapHeight = $this->GetMapSize($atts, 'h');
  
			$mapCntr = $this->GetMapAttributeVal($atts, 'posn');
			
			// Include x and y parameters for backwards compatibility
	  		if ($mapCntr === '')
			{
	  			if (isset($passedAtts['x']) && isset($passedAtts['y']))
				{
					$atts['posn'] = $passedAtts['x'].','.$passedAtts['y'];
					$mapCntr = $this->GetMapAttributeVal($atts, 'posn');
				}
			}			
	  
	  		if ($mapCntr === '')
			{
				BingMapsLibEscapingClass::Safe_EchoHTML(__("BingMaps Shortcode must specify centre Coordinates", 'bingmaps'));
				return;
			}
			
			$coords = explode(',', $mapCntr);
			if (count($coords) != 2)
			{
				BingMapsLibEscapingClass::Safe_EchoHTML(__("BingMaps Shortcode must specify BOTH centre Coordinates", 'bingmaps'));
				return;			
			}
			
			$this->mapNo = isset($this->mapNo) ? $this->mapNo+1 : 1;
			
			$ctrlId = "mapDiv".$this->mapNo;
			$zoom   = $this->GetMapAttributeVal($atts, 'zoom');
			$mapType = $this->GetMapAttributeVal($atts, 'type');
			
			$bingKey = $myDBaseObj->adminOptions['BingMapsKey'];	// Al2Lm1tFrf8cRxrIv-4vtKal2ZVlKw4Z-NyzpDG4lf0Ff877Ae4WdRBDIC1xKCVg
			$mapObjName = 'Microsoft.Maps.MapTypeId.'.$mapType;		// Microsoft.Maps.MapTypeId.road / Microsoft.Maps.MapTypeId.ordnanceSurvey
			$libOpts = '&mkt=en-GB';
			
			ob_start();		
	
			if ($this->mapNo == 1) 
			{
				$bingMapsJSURL = BINGMAPS_MAPCONTROL_SCRIPTURL.$libOpts;
				wp_enqueue_script('virtualearth', $bingMapsJSURL);
			
				// BingMapsLibEscapingClass::Safe_EchoHTML('Zoom: '."$zoom <br>");
				$imagesURL = BINGMAPS_IMAGES_URL.'bing/';
				
				BingMapsLibEscapingClass::Safe_EchoScript('
<script type="text/javascript">
function addLoadEvent(func)
{
	var oldonload = window.onload;
	if (typeof window.onload != "function") 
	{
		window.onload = func;
	} 
	else 
	{
		window.onload = function() 
		{
          oldonload();
          func();
        }
	}
}
</script>');
			}
			
			BingMapsLibEscapingClass::Safe_EchoScript('
<script type="text/javascript">
var bingMapsImagesURL = "'.BINGMAPS_IMAGES_URL.'";

function AddPin(map, lat, long, index)
{   
	poiPinImageURL = bingMapsImagesURL + "poi_red.png";
	anchorposn = new Microsoft.Maps.Point(12, 36);
	
	// Retrieve the location of the pin
	var locn = new Microsoft.Maps.Location(lat, long);
	
	// Add a pin to the map
	var pin = new Microsoft.Maps.Pushpin(locn, {
		icon: poiPinImageURL,
		anchor: anchorposn
	});
	
	map.entities.push(pin);
}
		
function GetMap'.$this->mapNo.'()
{   
	var mapdata = {
		credentials: "'.$bingKey.'",
		center: new Microsoft.Maps.Location('.$mapCntr.'),
		mapTypeId: '.$mapObjName.',
		showDashboard: true,
		useInertia: false,
		zoom: '.$zoom.'
		};
	
	var map = new Microsoft.Maps.Map(document.getElementById("'.$ctrlId.'"), mapdata);
			');
	
			if (isset($passedAtts['pincntr']))
			{
				BingMapsLibEscapingClass::Safe_EchoScript('
	AddPin(map, '.$mapCntr.', 0);
				');	
			}		
			
			$pinIndex = 0;
			while (true)
			{
				$pinIndex++;
				$pinPosn = $this->GetMapAttributeVal($passedAtts, 'pin', $pinIndex);
				if ($pinPosn == '')
					break;
				$pinParamID = 'pin'.$pinIndex;
					
				BingMapsLibEscapingClass::Safe_EchoScript('
	AddPin(map, '.$pinPosn.', '.$pinIndex.');
				');	
			}
			
			BingMapsLibEscapingClass::Safe_EchoScript('
}
	  
addLoadEvent(GetMap'.$this->mapNo.');	  
</script>
				');	
	      		
			BingMapsLibEscapingClass::Safe_EchoHTML('<div id="'.$ctrlId.'" style="position:relative; width:'.$mapWidth.'; height:'.$mapHeight.';"></div>');

			$outputContent = ob_get_contents();
			ob_end_clean();
			
			return $outputContent;
		}
     
		function adminClass($env, $classId, $fileName)
		{
			$fileName = $this->adminClassFilePrefix.'_'.$fileName.'.php';
			include 'admin/'.$fileName;
			
			$classId = $this->adminClassPrefix.$classId;
			return new $classId($env);
		}
		
		function printAdminPage() 
		{
			$myDBaseObj = $this->myDBaseObj;		
			//Prints out an admin page
      		
      		$env = $this->env;
      			
			$pagePrefix = $this->adminPagePrefix;			
			$pageSubTitle = BingMapsLibUtilsClass::GetHTTPTextElem($_GET, 'page');
      		switch ($pageSubTitle)
      		{
				case $pagePrefix.'_settings' :
					$this->adminClass($env, 'SettingsAdminClass', 'manage_settings');
					break;
          
				case $pagePrefix.'_overview':
				default :
					$this->adminClass($env, 'OverviewAdminClass', 'manage_overview');
					break;
			}
		}//End function printAdminPage()	
		
		function load_admin_styles()
		{
			// Add our own style sheet
			//wp_enqueue_style( 'bingmaps-admin-css', plugins_url( 'admin/css/bingmaps-admin.css', __FILE__ ));
			
			//do_action('AddStyleSheet');
		}

		// add anything else
		function BingMaps_ap() 
		{
			if (function_exists('add_menu_page'))
			{
				$icon_url = BINGMAPS_ADMIN_IMAGES_URL.'salesman16grey.png';
				$pagePrefix = $this->adminPagePrefix;
					
				add_menu_page($this->pluginDesc, $this->pluginDesc, BINGMAPS_ADMINUSER_CAPABILITY, $pagePrefix.'_adminmenu', array(&$this, 'printAdminPage'), $icon_url);
				add_submenu_page( $pagePrefix.'_adminmenu', __($this->pluginDesc.' - Overview', 'bingmaps'),   __('Overview', 'bingmaps'),  BINGMAPS_ADMINUSER_CAPABILITY, $pagePrefix.'_adminmenu',  array(&$this, 'printAdminPage'));				
				add_submenu_page( $pagePrefix.'_adminmenu', __($this->pluginDesc.' - Settings', 'bingmaps'),   __('Settings', 'bingmaps'),  BINGMAPS_ADMINUSER_CAPABILITY, $pagePrefix.'_settings',   array(&$this, 'printAdminPage'));
		      
			}
		}
		
	}
} //End Class BingMapsPluginClass

if (class_exists("BingMapsPluginClass")) 
{
	new BingMapsPluginClass();
}


?>