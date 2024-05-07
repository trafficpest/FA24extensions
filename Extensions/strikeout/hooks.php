<?php
/**********************************************************************
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.

	================================================================
  StrikeOut Pay	
	================================================================
	
***********************************************************************/

define ('SS_STRIKEOUT', 144<<8);

class hooks_strikeout extends hooks {

	function __construct() {
		$this->module_name = 'strikeout';
	}

  function install_options($app) {
		global $path_to_root;

		switch($app->id) {
			case 'system':
			$app->add_rapp_function(0, _("Payment &Integration Setup"),
				$path_to_root."/modules/".$this->module_name."/manage/strikeout_settings.php?", 'SA_CONFIG_STRIKEOUT', MENU_SETTINGS);
			break;
			case 'orders':
			$app->add_rapp_function(2, _("Payment &Integration Invoicing / Testing"),
				$path_to_root."/modules/".$this->module_name."/manage/strikeout_invoicing.php?", 'SA_INVOICE_STRIKEOUT', MENU_ENTRY);
			break;
		}
	}

	
  function install_access() {
		$security_sections[SS_STRIKEOUT] =  _("StrikeOut Payment Integrations");

		$security_areas['SA_CONFIG_STRIKEOUT'] = array(SS_STRIKEOUT|1, _("Strikeout Settings"));
		$security_areas['SA_INVOICE_STRIKEOUT'] = array(SS_STRIKEOUT|2, _("Strikeout Invoicing / Testing"));
    return array($security_areas, $security_sections);
	}

	function activate_extension($company, $check_only=true) {
		global $db_connections;
		
		$updates = array( 'update.sql' => array('frontadd'));
 
		return $this->update_databases($company, $updates, $check_only);
	}
	
	function deactivate_extension($company, $check_only=true) {
		global $db_connections;

		$updates = array('remove.sql' => array('frontadd'));

		return $this->update_databases($company, $updates, $check_only);
	}

}
