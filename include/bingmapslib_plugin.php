<?php
/* 
Description: Core Library Admin Page functions
 
Copyright 2020 Malcolm Shergold

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

if (!class_exists('BingMapsLibPluginClass')) 
{
	class BingMapsLibPluginClass // Define class
  	{
		var $myDBaseObj;
		var $ajaxParams = array();
		var $adminPageActive = false;
		var $shortcodeCount = 0;
		var $myJSRoot = 'BingMapsLib_JQuery';
						
		function __construct($pluginFile, $myDBaseObj)	 //constructor	
		{
			$this->myDBaseObj = $myDBaseObj;
			
			// add_action('wp_ajax_'.BINGMAPSLIB_PLUGINNAME, array(&$this, 'bingmapslib_AjaxAction'));
		}
		
		function bingmapslib_GetAjaxPageParams()
		{
			$ajaxParams = array('ajaxpage');
			return $ajaxParams;
		}
		
		function bingmapslib_AjaxCheckParams($ajaxParams)
		{
			$response['msg'] = '';
			foreach ($ajaxParams as $param)
			{
				if (!BingMapsLibUtilsClass::IsElementSet('post', $param))
				{
					$response['msg'] = "$param ".__('not specified', 'bingmaps');
					$response['status'] = 'error';
					return $response;
				}
				else
				{
					$postVal = BingMapsLibUtilsClass::GetHTTPTextElem('post', $param); 
					$response['msg'] .= "$param = ".$postVal."\n";						
				}
			}
			
			$response['status'] = 'ok';
			return $response;
		}
		
		function bingmapslib_AjaxAction()
		{
			$response = array();
			$response['status'] = 'error';
			$response['msg'] = '';
			
			if (!check_ajax_referer(BINGMAPSLIB_AJAXNONCEKEY, 'security', false))
			{
				$response['msg'] = __('NOnce Error', 'bingmaps');
				return $response;
			} 

			$pageParams = $this->bingmapslib_GetAjaxPageParams();
			$response = $this->bingmapslib_AjaxCheckParams($pageParams);
			if ($response['status'] == 'ok')
			{
				$myDBaseObj = $this->myDBaseObj;

				$id = BingMapsLibUtilsClass::GetHTTPTextElem('post', 'ajaxid');
				$value = BingMapsLibUtilsClass::GetHTTPTextElem('post', 'ajaxval');
				$_POST[$id] = $value;

				$this->env['ajax'] = true;		// Set env variable to disable HTML output
				$_GET['page'] = BingMapsLibUtilsClass::GetHTTPTextElem('post', 'ajaxpage'); 
				
				unset($_POST['ajaxid']);
				unset($_POST['ajaxval']);
				unset($_POST['ajaxpage']);

				ob_start();			
				$this->printAdminPage();				
				$ajaxOutput = ob_get_contents();
				ob_end_clean();
				
				file_put_contents(ABSPATH.'logs/wplettings-ajax.log', $ajaxOutput);
				//$response['msg'] .= "ajaxOutput = ".BingMapsLibMigratePHPClass::Safe_strlen($ajaxOutput)." bytes\n";						

				//$myDBaseObj->UpdateState($switchID, $switchState);		
				$response['status'] = 'page';
				$response['msg'] .= "AJAX Call Processed";
				
				return $response;
			}
			
			$response = $this->bingmapslib_AjaxCheckParams($this->ajaxParams);
			return $response;
		}
		
		function GetButtonTextAndTypeDef($buttonText, $buttonID, $buttonName = '', $buttonType = '', $buttonClasses = 'button-primary')
		{
			$buttonDef  = $this->GetButtonTypeDef($buttonID, $buttonName, $buttonType, $buttonClasses);
			if (BingMapsLibMigratePHPClass::Safe_strpos($buttonDef, " src="))
				$buttonDef .= ' alt="'.$buttonText.'"';
			else
				$buttonDef .= ' value="'.$buttonText.'"';

			return $buttonDef;
		}
			
		function AdminButtonHasClickHandler($buttonID)
		{
			return false;
		}
			
		function GetButtonTypeDef($buttonID, $buttonName = '', $buttonType = '', $buttonClasses = 'button-primary')
		{
			$buttonTypeDef = '';
	
			if ($buttonType == '')
			{
				$buttonType = 'submit';
			}
			
			if (!$this->adminPageActive)
			{
				$buttonImage = $this->myDBaseObj->ButtonURL($buttonID);
				if ($buttonImage == '')
				{
					// Try for a payment gateway defined button ...
					$buttonImage = $this->myDBaseObj->gatewayObj->GetButtonImage($buttonID);
				}
				if ($buttonImage != '')
				{
					$buttonType = 'image';
					$buttonTypeDef .= 'src="'.$buttonImage.'" ';
				}				
			}
			
			$buttonTypeDef .= 'type="'.$buttonType.'"';
				
			if ($buttonName == '')
			{
				$buttonName = $this->myDBaseObj->GetButtonID($buttonID);
			}

			if ($buttonType == 'image')
			{
				$buttonClasses .= ' '.'bingmaps'.'-button-image';				
			}

			if (isset($this->cssTrolleyBaseID))
			{
				$buttonClasses .= ' '.$this->cssTrolleyBaseID.'-ui';
				$buttonClasses .= ' '.$this->cssTrolleyBaseID.'-button';
			}

			$buttonTypeDef .= ' id="'.$buttonName.'" name="'.$buttonName.'"';					
			$buttonTypeDef .= ' class="'.$buttonClasses.'"';					

			$addClickHandler = true;
			if ($this->adminPageActive)
			{
				$addClickHandler = $this->AdminButtonHasClickHandler($buttonID);
			}

			if ($addClickHandler)
			{
				$onClickHandler = $this->myJSRoot.'_OnClick'.ucwords($buttonID);
				$buttonTypeDef .= ' onClick="return '.$onClickHandler.'(this, '.$this->shortcodeCount.')"';				
			}
			
			return $buttonTypeDef;
		}
				
	}
}



