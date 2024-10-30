<?php
/* 
Description: Code for UserEventMan Overview Page
 
Copyright 2011 Malcolm Shergold

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

include BINGMAPS_INCLUDE_PATH.'bingmapslib_adminlist.php';
include BINGMAPS_INCLUDE_PATH.'bingmapslib_admin.php';

if (!class_exists('BingMapsOverviewAdminClass')) 
{
	class BingMapsOverviewAdminClass extends BingMapsLibAdminClass
	{		
		function __construct($env)
		{
			$this->pageTitle = 'Overview';
			
			// Call base constructor
			parent::__construct($env);
			
			$myDBaseObj = $this->myDBaseObj;
 		}
		
		function ProcessActionButtons()
		{
		}
		
		function Output_ShortcodeHelp()
		{
			// FUNCTIONALITY: Overview - Show Help for Shortcode(s))
?>
	<br>			
	<h2><?php _e('Shortcodes', 'bingmaps'); ?></h2>
	<?php _e('BingMaps generates output to your Wordpress pages for the following shortcodes:', 'bingmaps'); ?>
			<table class="widefat" cellspacing="0">
				<thead>
					<tr>
						<th><?php _e('Shortcode', 'bingmaps'); ?></th>
						<th><?php _e('Description', 'bingmaps'); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>[<?php BingMapsLibEscapingClass::Safe_EchoHTML(BINGMAPS_SHORTCODE_MAP); ?> posn=xx.xxxx,yy.yyyy]</td>
						<td><?php _e('Output a map', 'bingmaps'); ?></td>
					</tr>
					<tr>
						<td>Shortcode Parameters:</td>
						<td>posn (Required) = Latitude & Longditude at centre of map (i.e. posn=51.508609,-0.126436)</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td>w (Optional) = Width of the output map (defaults to pixels)</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td>h (Optional) = Height of the output map (defaults to pixels)</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td>zoom (Optional) = Map scale - (1-19)</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td>type (Optional) = Map type - (road, aerial etc.)</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td>pincntr (Optional) = Adds a pin to the centre of map</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td>pin{n} (Optional) = Adds a pin to the map (i.e. pin1=51.508609,-0.126436)</td>
					</tr>
				</tbody>
			</table>
<?php
		}

		function Output_MainPage($updateFailed = false)
		{
			$this->Output_ShortcodeHelp();
		}
	}
}

?>