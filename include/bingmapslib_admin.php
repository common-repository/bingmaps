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

require_once "bingmapslib_utils.php";

if (!class_exists('BingMapsLibAdminBaseClass'))
{
	if (!defined('BINGMAPSLIB_AJAXNONCEKEY'))
		define('BINGMAPSLIB_AJAXNONCEKEY', 'bingmapslib-ajax-nonce-key');

	class BingMapsLibAdminBaseClass // Define class
  	{
		var $caller;				// File Path of descendent class
		var $myPluginObj;
		var $myDBaseObj;
		var $usesAjax = false;

		function __construct($env)	 //constructor
		{
			$this->caller = $env['Caller'];
			$this->myPluginObj = $env['PluginObj'];
			$this->myDBaseObj = $env['DBaseObj'];

			$this->isAJAXCall = isset($env['ajax']);
		}

		function GetWPNonceField($referer = '', $name = '_wpnonce')
		{
			return $this->myDBaseObj->WPNonceField($referer, $name, false);
		}

		function OutputWPNonceField($referer = '', $name = '_wpnonce')
		{
			return $this->myDBaseObj->WPNonceField($referer, $name, true);
		}

		function WPNonceField($referer = '', $name = '_wpnonce', $echoOut = true)
		{
			return $this->myDBaseObj->WPNonceField($referer, $name, $echoOut);
		}

		function CheckAdminReferer($referer = '')
		{
			// AJAX calls are validated by the AJAX calback function
			if ($this->isAJAXCall) return true;

			return $this->myDBaseObj->CheckAdminReferer($referer);
		}

		// Bespoke translation functions added to remove these translations from .po file
		function getTL8($text, $domain = 'default')
		{
			return __($text, $domain);
		}

		function echoTL8($text)
		{
			return esc_html_e($text, 'bingmaps');
		}

  	}
}

if (!class_exists('BingMapsLibAdminClass'))
{
  class BingMapsLibAdminClass extends BingMapsLibAdminBaseClass// Define class
  {
		var $env;

		var $currentPage;
		var $adminOptions;
		var $adminListUsesSerializedPost = false;

		var $editingRecord;

		function __construct($env)	 //constructor
		{
			parent::__construct($env);

			$this->adminOptions = $this->myDBaseObj->adminOptions;

			$this->env = $env;
			$this->env['parent'] = $this;

			$myPluginObj = $this->myPluginObj;
			$myDBaseObj  = $this->myDBaseObj;

			$myDBaseObj->AllUserCapsToServervar();

			$this->editingRecord = false;
			$this->pluginName = basename(dirname($this->caller));

			if (!isset($this->pageTitle)) $this->pageTitle = "***  pageTitle Undefined ***";

			$this->adminMsg = '';

			$bulkAction = '';
			if ( BingMapsLibUtilsClass::IsElementSet('post', 'doaction_t' ) )
			{
				$bulkAction = BingMapsLibUtilsClass::GetHTTPTextElem('post', 'action_t');
			}

			if ( BingMapsLibUtilsClass::IsElementSet('post', 'doaction_b' ) )
			{
				$bulkAction = BingMapsLibUtilsClass::GetHTTPTextElem('post', 'action_b');
			}

 			if (($bulkAction !== '') && BingMapsLibUtilsClass::IsElementSet('post', 'rowSelect'))
 			{
				// Bulk Action Apply button actions
				$this->CheckAdminReferer();
				$actionError = false;

				$selectedRows = BingMapsLibUtilsClass::GetHTTPIntegerArray('post', 'rowSelect');
				foreach($selectedRows as $recordId)
				{
					$actionError |= $this->DoBulkPreAction($bulkAction, $recordId);
				}

				$actionCount = 0;
				if (!$actionError)
				{
					foreach($selectedRows as $recordId)
					{
						if ($this->DoBulkAction($bulkAction, $recordId))
						{
							$actionCount++;
						}
					}
				}

				$actionMsg = $this->GetBulkActionMsg($bulkAction, $actionCount);
				if ($actionCount > 0)
				{
					$this->myDBaseObj->PurgeDB();
					BingMapsLibEscapingClass::Safe_EchoHTML('<div id="message" class="updated"><p>'.$actionMsg.'</p></div>');
				}
				else
				{
					BingMapsLibEscapingClass::Safe_EchoHTML('<div id="message" class="error"><p>'.$actionMsg.'</p></div>');
				}

 			}

			$pluginID = 'bingmaps';

			$adminHideClass = $pluginID.'-admin-hide';
			BingMapsLibEscapingClass::Safe_EchoHTML("
<style>
.$adminHideClass
{
display: none;
}
</style>
				");

			$tableClass = $pluginID.'-admin';
			BingMapsLibEscapingClass::Safe_EchoHTML('<div class="wrap '.$tableClass.'">');

			$this->GetSearchParams();

			ob_start();
			$this->ProcessActionButtons();
			$actionPage = ob_get_contents();
			ob_end_clean();

			$this->OutputTitle();

			BingMapsLibEscapingClass::Safe_EchoHTML($actionPage);
			if (!isset($this->donePage))
			{
				$this->Output_MainPage($this->adminMsg !== '');
			}

			BingMapsLibEscapingClass::Safe_EchoHTML('</div>');
		}

		function OutputTitle()
		{
			if ($this->isAJAXCall) return;

			if ($this->pageTitle != '')
			{
				$iconID = 'icon-'.'bingmaps';
				BingMapsLibEscapingClass::Safe_EchoHTML('
					<div id="'.$iconID.'" class="icon32"></div>
					<h2>'.$this->myDBaseObj->get_pluginName().' - '.__($this->pageTitle, 'bingmaps').'</h2>'."\n");
			}

		}

		static function ValidateEmail($ourEMail)
		{
			if (BingMapsLibMigratePHPClass::Safe_strpos($ourEMail, '@') === false)
				return false;

			return true;
		}

		static function IsOptionChanged($adminOptions, $optionID)
		{
			if (BingMapsLibUtilsClass::IsElementSet('post', $optionID) && (BingMapsLibMigratePHPClass::Safe_trim(BingMapsLibUtilsClass::GetArrayElement($adminOptions, $optionID)) !== BingMapsLibUtilsClass::GetHTTPTextElem('post', $optionID)))
			{
				return true;
			}

			return false;
		}

		function UpdateHiddenRowValues($result, $index, $settings, $dbOpts)
		{
			// Save option extensions
			foreach ($settings as $setting)
			{
				$settingId = $setting[BingMapsLibTableClass::TABLEPARAM_ID];

				if ($setting[BingMapsLibTableClass::TABLEPARAM_TYPE] == BingMapsLibTableClass::TABLEENTRY_CHECKBOX)
				{
					$newVal = BingMapsLibUtilsClass::IsElementSet('post', $settingId.$index) ? 1 : 0;
					BingMapsLibUtilsClass::SetElement('post', $settingId.$index, $newVal);
				}
				else if (!BingMapsLibUtilsClass::IsElementSet('post', $settingId.$index))
					continue;
				else if ($setting[BingMapsLibTableClass::TABLEPARAM_TYPE] != BingMapsLibTableClass::TABLEENTRY_TEXTBOX)
				{
					$newVal = BingMapsLibUtilsClass::GetHTTPTextElem('post', $settingId.$index);
				}
				else if (isset($setting[BingMapsLibTableClass::TABLEPARAM_ALLOWHTML]))
				{
					$newVal = BingMapsLibUtilsClass::GetHTTPTextHttpElem('post', $settingId.$index);
				}
				else
				{
					$newVal = BingMapsLibUtilsClass::GetHTTPTextareaElem('post', $settingId.$index);
				}
				// self::TABLEENTRY_TEXTBOX

				if ($newVal != $result->$settingId)
				{
					$this->myDBaseObj->UpdateASetting($newVal, $dbOpts['Table'], $settingId, $dbOpts['Index'], $index);
				}
			}
		}

		function DoBulkPreAction($bulkAction, $recordId)
		{
			return false;
		}

		function DoBulkAction($bulkAction, $recordId)
		{
			BingMapsLibEscapingClass::Safe_EchoHTML("DoBulkAction() function not defined in ".get_class()."<br>\n");
			return false;
		}

		function GetBulkActionMsg($bulkAction, $actionCount)
		{
			BingMapsLibEscapingClass::Safe_EchoHTML("GetBulkActionMsg() function not defined in ".get_class()."<br>\n");
		}

		function CreateAdminListObj($env, $editMode = false)
		{
			$className = get_class($this);
			$classId = BingMapsLibMigratePHPClass::Safe_str_replace('AdminClass', 'AdminListClass', $className);

			$adminListObj = new $classId($env, $editMode);
			if ($adminListObj->tableUsesSerializedPost)
			{
				$this->adminListUsesSerializedPost = true;
			}

			return $adminListObj;
		}

		function OutputPostButton($buttonId, $buttonText, $buttonClass = "button-secondary", $scanClass = '')
		{
			if ($scanClass == '') $scanClass = 'bingmapslib_PostVal';

			$clickEvent = '';
			if ($this->adminListUsesSerializedPost)
			{
				$clickEvent="return bingmapslib_JSONEncodePost(this, '$scanClass')";
			}
			$this->OutputButton($buttonId, $buttonText, $buttonClass, $clickEvent);
		}

		function OuputSearchButton($label = '', $buttonId = '')
		{
			if ($label == '')
			{
				$label = __("Search Sales", 'bingmaps');
				if ($buttonId == '') $buttonId = 'searchsales';
			}
			if ($buttonId == '') $buttonId = BingMapsLibMigratePHPClass::Safe_strtolower(BingMapsLibMigratePHPClass::Safe_str_replace(" ", "", $label));
			$searchTextInput = $buttonId.'text';

			$searchText = BingMapsLibUtilsClass::GetHTTPTextElem('request', $searchTextInput);
			BingMapsLibEscapingClass::Safe_EchoHTML('<div class="'.'bingmaps'.'-'.$buttonId.'"><input type="text" maxlength="'.PAYMENT_API_SALEEMAIL_TEXTLEN.'" size="20" name="'.$searchTextInput.'" id="'.$searchTextInput.'" value="'.$searchText.'" autocomplete="off" />'."\n");
			$this->OutputButton($buttonId."button", $label);
			BingMapsLibEscapingClass::Safe_EchoHTML('</div>'."\n");
		}

		function OutputButton($buttonId, $buttonText, $buttonClass = "button-secondary", $clickEvent = '')
		{
			$buttonText = __($buttonText, 'bingmaps');

			if ($clickEvent != '')
			{
				$clickEvent = ' onclick="'.$clickEvent.'" ';
			}

			BingMapsLibEscapingClass::Safe_EchoHTML("<input class=\"$buttonClass\" type=\"submit\" $clickEvent name=\"$buttonId\" value=\"$buttonText\" />\n");
		}

		function Output_MainPage($updateFailed)
		{
			BingMapsLibEscapingClass::Safe_EchoHTML("Output_MainPage() function not defined in ".get_class()."<br>\n");
		}

		function GetSearchParams()
		{
		}

		function ProcessActionButtons()
		{
			BingMapsLibEscapingClass::Safe_EchoHTML("ProcessActionButtons() function not defined in ".get_class()."<br>\n");
		}

		function AdminUpgradeNotice()
		{
?>
	<div id="message" class="updated fade">
		<p><strong>Plugin is ready</strong></p>
	</div>
<?php
		}

 	}
}


