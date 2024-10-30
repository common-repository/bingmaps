<?php
/*
Description: Core Library Database Access functions

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

require_once "bingmapslib_migrate.php";
require_once "bingmapslib_escaping.php";
require_once "bingmapslib_utils.php";

if (!class_exists('BingMapsLibGenericDBaseClass'))
{
	if (!defined('BINGMAPSLIB_DATETIME_ADMIN_FORMAT'))
		define('BINGMAPSLIB_DATETIME_ADMIN_FORMAT', 'Y-m-d H:i');

	if (!defined('BINGMAPSLIB_CAPABILITY_SYSADMIN'))
		define('BINGMAPSLIB_CAPABILITY_SYSADMIN', 'manage_options');

	define ('BINGMAPSLIB_SESSION_VALIDATOR', 'bingmapslib_session_valid');

	if (!defined('BINGMAPSLIB_CACHEDPAGE_TIMEDELTA'))
		define('BINGMAPSLIB_CACHEDPAGE_TIMEDELTA', 60);

	if (!defined('BINGMAPSLIB_DATABASE_LOGFILE'))
		define('BINGMAPSLIB_DATABASE_LOGFILE', 'SQL.log');

	define('BINGMAPSLIB_MIMEENCODING_PHPMAILER', 'PHPMailer');
	define('BINGMAPSLIB_MIMEENCODING_PLUGIN', 'Plugin');

	if (!defined('MYSQL_REGEX_ICU'))
	{
		define('MYSQL_REGEX_ICU', 'ICU');
		define('MYSQL_REGEX_HenrySpencer', 'HS');
	}

	class BingMapsLibGenericDBaseClass // Define class
	{
		const ADMIN_SETTING = 1;
		const DEBUG_SETTING = 2;

		var $hideSQLErrors = false;

		// This class does nothing when running under WP
		// Overload this class with DB access functions for non-WP access
		function __construct() //constructor
		{
			$this->SetMySQLGlobals();
		}

		function IsPageCached()
		{
			if (!BingMapsLibUtilsClass::IsElementSet('post', 'pageServerTime')) return false;
			if (!BingMapsLibUtilsClass::IsElementSet('post', 'pageClientTime')) return false;
			if (!BingMapsLibUtilsClass::IsElementSet('post', 'requestClientTime')) return false;

			$pageGeneratedServerTime = BingMapsLibUtilsClass::GetHTTPNumber('post', 'pageServerTime');
			$pageGeneratedClientTime = BingMapsLibUtilsClass::GetHTTPNumber('post', 'pageClientTime');

			$jQueryRequestClientTime = BingMapsLibUtilsClass::GetHTTPNumber('post', 'requestClientTime');
			$jQueryRequestServerTime = time();

			// Calculate Time Offset of Local Machine - +ve if Client is Set Slow
			$jQueryRequestTimeOffset = $jQueryRequestServerTime - $jQueryRequestClientTime;

			// Now calculate lenght of time page has been cached
			$pageGeneratedTimeOffset = $pageGeneratedServerTime - $pageGeneratedClientTime;
			$timeInCache = $jQueryRequestTimeOffset - $pageGeneratedTimeOffset;


			return (abs($timeInCache) >= BINGMAPSLIB_CACHEDPAGE_TIMEDELTA);
		}

		function SessionVarsAvailable()
		{
			$rtnVal = $this->IsSessionElemSet(BINGMAPSLIB_SESSION_VALIDATOR);
			return $rtnVal;
		}

		function SetMySQLGlobals()
		{
			$this->hideSQLErrors = true;
			$rtnVal = $this->query("SET SQL_BIG_SELECTS=1");
			$this->hideSQLErrors = false;
			if (!$rtnVal)
			{
				// Use the old version of the query if it fails
				$rtnVal = $this->query("SET OPTION SQL_BIG_SELECTS=1");
			}

			// Get the sql mode
			$globs = $this->GetMySQLGlobals();
			$hasFullGroupMode = BingMapsLibMigratePHPClass::Safe_strpos($globs->mode, 'ONLY_FULL_GROUP_BY');
			$wantFullGroupMode = defined('BINGMAPSLIB_ONLY_FULL_GROUP_BY');
			if ($hasFullGroupMode != $wantFullGroupMode)
			{
				if ($wantFullGroupMode)
				{
					// Add the ONLY_FULL_GROUP_BY mode
					$newModes = $globs->mode;
					if ($newModes != '') $newModes .= ',';
					$newModes .= 'ONLY_FULL_GROUP_BY';
				}
				else
				{
					// Remove the ONLY_FULL_GROUP_BY mode
					$modes = explode(',',$globs->mode);
					$newModes = '';
					foreach ($modes as $mode)
					{
						if ($mode == 'ONLY_FULL_GROUP_BY') continue;
						if ($newModes != '') $newModes .= ',';
						$newModes .= $mode;
					}
				}
				$sql = "SET SESSION sql_mode='$newModes';";
				$this->query($sql);
			}

			return $rtnVal;
		}

		function GetMySQLGlobals()
		{
			$sql = "SELECT VERSION() AS version, @@SESSION.sql_mode AS mode";
			$sqlFilters['noLoginID'] = true;
			$globResult = $this->get_results($sql, true, $sqlFilters);
			return $globResult[0];
		}

		function GetAndSetAutoIncrement($tableName, $addTruncate = false)
		{
			$sql = 'SELECT TABLE_NAME AS tab, AUTO_INCREMENT AS autoInc FROM INFORMATION_SCHEMA.TABLES ';
			$sql .= "WHERE TABLE_NAME = '$tableName'";
			$autoIncResult = $this->get_results($sql);
			$autoIncValue = $autoIncResult[0]->autoInc;

			$sql = '';
			if ($addTruncate)
				$sql .= "TRUNCATE TABLE $tableName ; \n";

			// Now add the SQL to set the AUTO_INCREMENT to the log
			$sql .= "ALTER TABLE $tableName AUTO_INCREMENT=$autoIncValue";
			$this->AddToLogSQL($sql);
		}

		function GetWPVersion()
		{
			global $wp_version;
			return $wp_version;
		}

		function GetMySQLVersion()
		{
			global $wpdb;
			return $wpdb->db_server_info();
		}

		function GetMySQLRegexVersion()
		{
			$mysqlVer = $this->GetMySQLVersion();
			if (version_compare($mysqlVer, '8.0.4') >= 0)
				return MYSQL_REGEX_ICU;
			else
				return MYSQL_REGEX_HenrySpencer;
		}

		function ShowDBErrors()
		{

			if ($this->hideSQLErrors)
				return;

			global $wpdb;
			if ($wpdb->last_error == '')
				return;

			BingMapsLibEscapingClass::Safe_EchoHTML('<div id="message" class="error"><p>'.$wpdb->last_error.'</p></div>');
			BingMapsLibUtilsClass::ShowCallStack();
		}

		function ClearSQLLog()
		{
			$LogsFolder = $this->adminOptions['LogsFolderPath'];
			if ($LogsFolder == '') return;

			include "bingmapslib_logfile.php";

			$logFileObj = new BingMapsLibLogFileClass($LogsFolder);
			$logFileObj->LogToFile(BINGMAPSLIB_DATABASE_LOGFILE, '', BingMapsLibDBaseClass::ForWriting);
		}

		function ArchiveSQLLog()
		{
			$LogsFolder = $this->adminOptions['LogsFolderPath'];
			if ($LogsFolder == '') return;

			$LogsFolder = ABSPATH.$LogsFolder.'/';

			$logFilePath = $LogsFolder.BINGMAPSLIB_DATABASE_LOGFILE;
			if (!file_exists($logFilePath)) return;

			$localTime = date('Y-m-d-H-i-s', current_time('timestamp'));
			$archiveFileName = BingMapsLibMigratePHPClass::Safe_str_replace('.', "-{$localTime}.", BINGMAPSLIB_DATABASE_LOGFILE);
			$archiveFilePath = $LogsFolder.$archiveFileName;

			rename($logFilePath, $archiveFilePath);
		}

		function LogSQL($sql, $queryResult)
		{
		}

		function AddToLogSQL($sql)
		{
		}

		function ShowDebugMsg($html_msg)
		{
			if (!$this->isDbgOptionSet('Dev_ShowMiscDebug'))
				return;

			$this->OutputDebugStart();
			BingMapsLibUtilsClass::DebugOut("<br>$html_msg<br>\n");
			$this->OutputDebugEnd();
		}

		function ShowSQL($sql, $values = null)
		{
			if (!$this->isDbgOptionSet('Dev_ShowSQL'))
			{
				return;
			}

			if ($this->isDbgOptionSet('Dev_ShowMemUsage'))
			{
				BingMapsLibEscapingClass::Safe_EchoHTML("Memory Usage=".memory_get_usage()." Peak=".memory_get_peak_usage()." Max=".ini_get('memory_limit')." bytes<br>\n");
			}

			if ($this->isDbgOptionSet('Dev_ShowCallStack'))
			{
				BingMapsLibUtilsClass::ShowCallStack();
			}

			$sql = BingMapsLibMigratePHPClass::Safe_htmlspecialchars($sql);
			$sql = BingMapsLibMigratePHPClass::Safe_str_replace("\n", "<br>\n", $sql);
			BingMapsLibEscapingClass::Safe_EchoHTML("<br>$sql<br>\n");
			if (isset($values))
			{
				print_r($values);
				BingMapsLibEscapingClass::Safe_EchoHTML("<br>\n");
			}
		}

		function queryWithPrepare($sql, $values)
		{
			global $wpdb;

			$sql = $wpdb->prepare($sql, $values);

			return $this->query($sql);
		}

		function query($sql)
		{
			global $wpdb;

			$this->ShowSQL($sql);

			if ($this->hideSQLErrors)
			{
				$suppress_errors = $wpdb->suppress_errors;
				$wpdb->suppress_errors = true;
			}
			$this->queryResult = $wpdb->query($sql);
			if ($this->isDbgOptionSet('Dev_ShowDBOutput'))
			{
				BingMapsLibEscapingClass::Safe_EchoHTML("Query Result: ".$this->queryResult." <br>\n");
			}
			$rtnStatus = ($this->queryResult !== false);
			if ($this->hideSQLErrors)
			{
				$wpdb->suppress_errors = $suppress_errors;
			}
			else
			{
				$this->ShowDBErrors();
			}

			$this->LogSQL($sql, $this->queryResult);

			return $rtnStatus;
		}

		function GetInsertId()
		{
			global $wpdb;

			return $wpdb->insert_id;
		}

		function esc_like($text)
		{
			global $wpdb;

			return $wpdb->esc_like($text);
		}

		function getresultsWithPrepare($sql, $values)
		{
			global $wpdb;

			$sql = $wpdb->prepare($sql, $values);

			return $this->get_results($sql);
		}

		function get_results($sql, $debugOutAllowed = true, $sqlFilters = array())
		{
			global $wpdb;

			$this->ShowSQL($sql);
			$results = $wpdb->get_results($sql);
			if ($debugOutAllowed) $this->show_results($results);

			$this->ShowDBErrors();

			return $results;
		}

		function show_results($results)
		{
			if (!$this->isDbgOptionSet('Dev_ShowDBOutput'))
			{
				if ($this->isDbgOptionSet('Dev_ShowSQL'))
				{
					$entriesCount = count($results);
					BingMapsLibEscapingClass::Safe_EchoHTML("Database Results Entries: $entriesCount<br>\n");
					return;
				}
				return;
			}

			if (function_exists('wp_get_current_user'))
			{
				if (!$this->isSysAdmin())
					return;
			}

			BingMapsLibEscapingClass::Safe_EchoHTML("<br>Database Results:<br>\n");
			for ($i = 0; $i < count($results); $i++)
				BingMapsLibEscapingClass::Safe_EchoHTML("Array[$i] = " . print_r($results[$i], true) . "<br>\n");
		}

		function ForceSQLDebug($activate=true)
		{
			if ($activate)
			{
				if (!isset($this->Last_Dev_ShowSQL))
				{
					$this->Last_Dev_ShowSQL = $this->isDbgOptionSet('Dev_ShowSQL');
					$this->Last_Dev_ShowDBOutput = $this->isDbgOptionSet('Dev_ShowDBOutput');
					$this->Last_Dev_ShowCallStack = $this->isDbgOptionSet('Dev_ShowCallStack');
				}

				$this->dbgOptions['Dev_ShowSQL'] = true;
				$this->dbgOptions['Dev_ShowDBOutput'] = true;
				$this->dbgOptions['Dev_ShowCallStack'] = true;
			}
			else
			{
				if (isset($this->Last_Dev_ShowSQL))
				{
					$this->dbgOptions['Dev_ShowSQL'] = $this->Last_Dev_ShowSQL;
					$this->dbgOptions['Dev_ShowDBOutput'] = $this->Last_Dev_ShowDBOutput;
					$this->dbgOptions['Dev_ShowCallStack'] = $this->Last_Dev_ShowCallStack;
				}
			}
		}

		function GetSQLBlockEnd($sql, $startPosn, $startChar = '(', $endChar = ')')
		{
			$posn = $startPosn;
			$len = BingMapsLibMigratePHPClass::Safe_strlen($sql);
			$matchCount = 0;

			while ($posn < $len)
			{
				$nxtChar = $sql[$posn];
				if ($nxtChar == $startChar)
				{
					$matchCount++;
				}
				else if ($nxtChar == $endChar)
				{
					$matchCount--;
					if ($matchCount == 0)
					{
						return $posn;
					}
				}
				$posn++;
			}

			return -1;
		}

		function isSysAdmin()
		{
			if (!function_exists('wp_get_current_user'))
			{
				return false;
			}

			if (current_user_can(BINGMAPSLIB_CAPABILITY_SYSADMIN))
			{
				return true;
			}

			if (defined('BINGMAPSLIB_CAPABILITY_DEVUSER') && current_user_can(BINGMAPSLIB_CAPABILITY_DEVUSER))
			{
				return true;
			}

			return false;
		}

		function getOption($optionID, $optionClass = self::ADMIN_SETTING)
		{
			switch ($optionClass)
			{
				case self::ADMIN_SETTING:
					$options = $this->adminOptions;
					break;

				case self::DEBUG_SETTING:
					$options = $this->dbgOptions;
					break;

				default:
					return;
			}

			$optionVal = '';
			if (isset($options[$optionID]))
			{
				$optionVal = $options[$optionID];
			}

			return $optionVal;
		}

		function isDbgOptionSet($optionID)
		{
			$rtnVal = false;
			return $rtnVal;
		}

		function isOptionSet($optionID, $optionClass = self::ADMIN_SETTING)
		{
			return false;
		}

		function FormatCurrencyValue($amount, $asHTML = true)
		{
			$currencyText = sprintf($this->getOption('CurrencyFormat'), $amount);
			return $currencyText;
		}

		function FormatCurrency($amount, $asHTML = true)
		{
			$currencyText = $this->FormatCurrencyValue($amount, $asHTML);
			if (!$this->getOption('UseCurrencySymbol'))
				return $currencyText;

			if ($asHTML)
			{
				$currencyText = $this->getOption('CurrencySymbol').$currencyText;
			}
			else
			{
				$currencyText = $this->getOption('CurrencyText').$currencyText;
			}

			return $currencyText;
		}

		static function FormatDateForAdminDisplay($dateInDB)
		{
			// Convert time string to UNIX timestamp
			$timestamp = BingMapsLibMigratePHPClass::Safe_strtotime( $dateInDB );

			// Get Time & Date formatted for display to user
			return date(BINGMAPSLIB_DATETIME_ADMIN_FORMAT, $timestamp);
		}

	}
}


