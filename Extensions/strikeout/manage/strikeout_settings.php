<?php
/**********************************************************************
  Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
  See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/

$path_to_root = "../../..";
$page_security = 'SA_CONFIG_STRIKEOUT';

include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

$js = "";
	
page(_($help_context = "StrikeOut Configuration"), 
  false, false, "", $js); 

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root 
  ."/modules/strikeout/inc/strikeout_fa.inc");

if (isset($_GET['so_id'])) 
	$_POST['so_id'] = $_GET['so_id'];

$so_id = get_post('so_id');

if (isset($_POST['so_del_hook']) && $_POST['so_del_hook'] == 1)
  delete_method_webhooks($_POST['so_id'], $_POST['so_wh_id']);

if (isset($_POST['so_cre_hook']) && $_POST['so_cre_hook'] == 1)
 create_method_webhooks($_POST['so_id'], $_POST['webhookUrl']);

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------

function method_settings($so_id) 
{
	global $page_nested;
	
	if ($so_id) 
	{
    $myrow = get_strikeout_id($so_id);
		$_POST['so_id'] = $myrow["so_id"];
		$_POST['so_name'] = $myrow["so_name"];
		$_POST['so_method'] = $myrow["so_method"];
		$_POST['so_url'] = $myrow["so_url"];
		$_POST['so_pub']  = $myrow["so_pub"];
		$_POST['so_pri']  = $myrow["so_pri"];
		$_POST['so_percent'] = $myrow["so_percent"];
		$_POST['so_discount']  = $myrow["so_discount"];
		$_POST['so_custom_1']  = $myrow["so_custom_1"];
		$_POST['so_custom_2']  = $myrow["so_custom_2"];
		$_POST['so_custom_3']  = $myrow["so_custom_3"];
		$_POST['so_custom_4']  = $myrow["so_custom_4"];
		$_POST['so_user']  = $myrow["so_user"];
		$_POST['so_is_inv']  = $myrow["so_is_inv"];
		$_POST['so_bank']  = $myrow["so_bank"];
		$_POST['so_debit']  = $myrow["so_debit"];
		$_POST['so_credit']  = $myrow["so_credit"];
		$_POST['so_fee']  = $myrow["so_fee"];
		$_POST['so_option']  = $myrow["so_option"];
		$_POST['so_inactive']  = $myrow["so_inactive"];
		$_POST['so_hook_inactive']  = $myrow["so_hook_inactive"];

    $webhook = get_method_webhooks($so_id);
    if (isset($webhook["id"]))
		  $_POST['so_wh_id'] = $webhook["id"];

    $webhook_path = 'https://'.$_SERVER['HTTP_HOST']
    .strtok($_SERVER['REQUEST_URI'], '?');
    
    $webhook_path = str_replace(
      'manage/strikeout_settings.php',
      'pay/methods/'.$_POST['so_method'].'/webhooks/?co='.user_company(),
      $webhook_path
    ); 
	}



  // Quick Debug
  //display_notification(json_encode($myrow));  
  //display_notification(json_encode($webhook));
  //display_notification(json_encode($_POST));
	start_outer_table(TABLESTYLE2);

	table_section(1);
	hidden('so_name', $_POST['so_name']);
	hidden('so_method', $_POST['so_method']);
	table_section_title(_("API Settings"));
	text_row(_("URL:"), 'so_url', $_POST['so_url'], 50, 255);
	text_row(_($myrow["so_pub_L"].":"), 'so_pub', $_POST['so_pub'], 50, 255);
	text_row(_($myrow["so_pri_L"].":"), 'so_pri', $_POST['so_pri'], 50, 255);
	table_section_title(_("Fees / Discounts"));
	percent_row(_("Percentage:"), 'so_percent', $_POST['so_percent']);
	small_amount_row(_("Fixed Amt:"), 'so_discount', $_POST['so_discount']);
  table_section_title(_("Extra"));
	text_row(_($myrow["so_custom_1L"].":"), 'so_custom_1', $_POST['so_custom_1'], 50, 255);
	text_row(_($myrow["so_custom_2L"].":"), 'so_custom_2', $_POST['so_custom_2'], 50, 255);
	text_row(_($myrow["so_custom_3L"].":"), 'so_custom_3', $_POST['so_custom_3'], 50, 255);
	text_row(_($myrow["so_custom_4L"].":"), 'so_custom_4', $_POST['so_custom_4'], 50, 255);
	table_section(2);
  table_section_title(_("Webhook Settings"));
  if ($webhook){
	check_row(_("Delete Webhook:"),'so_del_hook', null, true);
	label_row(_("ID:"),$webhook['id']);
	hidden('so_wh_id', $webhook['id']);
	label_row(_("Url:"),$webhook['webhookUrl']);
  }elseif (!isset($webhook["id"]) && $so_id != 1){ 
	  check_row(_("Create Webhook:"),'so_cre_hook', null, true);
    text_row(_("Url:"), 'webhookUrl', $webhook_path, 50, 255);
  }
  table_section_title(_("Webhook Deposit"));
  if ($so_id != 1){
    so_users_list_cells('User:', 'so_user', $_POST['so_user'], false, false);
    check_row(_("Inventory Item:"),'so_is_inv', null, true);
    if ($_POST['so_is_inv'] != 1)
      bank_accounts_list_row('Bank Acct:', 'so_bank', null, false);
    else{
      stock_items_list_cells("", 'so_bank', $selected_id=null, $all_option=false);
    }
    gl_all_accounts_list_row('Debit:', 'so_debit', $selected_id=null, 
    $skip_bank_accounts=false, $cells=false, $all_option=false, $submit_on_change=false, $all=false, $type_id=false);
    gl_all_accounts_list_row('Credit:', 'so_credit', $selected_id=null, 
    $skip_bank_accounts=false, $cells=false, $all_option=false, $submit_on_change=false, $all=false, $type_id=false);
    gl_all_accounts_list_row('Fees:', 'so_fee', $selected_id=null, 
      $skip_bank_accounts=false, $cells=false, $all_option=false, $submit_on_change=false, $all=false, $type_id=false);
    strikeout_options_list('Option:', 'so_option');
  }
  table_section_title(_("Method Status"));
  if ($so_id != 1){
    check_row(_("Method Inactive:"),'so_inactive', $_POST['so_inactive']);
    check_row(_("Webhook Inactive:"),'so_hook_inactive', $_POST['so_hook_inactive']);
  }
  end_outer_table(1);

	div_start('controls');
  submit_center_first('submit', _("Update StrikeOut Method"), 
    _('Update StrikeOut Method'), $page_nested ? true : 'default');
  //submit_center_last('delete', _("Delete StrikeOut Method"), 
  //  _('Delete StrikeOut Method'), true);
	div_end();
}


if (isset($_POST['submit']) || isset($_POST['_so_is_inv_update'])) 
{

	if ($so_id) 
  {
    if ($_POST['so_inactive'] != 1)
      $_POST['so_inactive'] = '0';

    if ($_POST['so_hook_inactive'] != 1)
      $_POST['so_hook_inactive'] = '0';
   
    if ($_POST['so_is_inv'] != 1)
      $_POST['so_is_inv'] = '0';

  //display_notification(json_encode($_POST));
    update_strikeout_method(
      $_POST['so_id'], 
      $_POST['so_name'], 
      $_POST['so_method'], 
      $_POST['so_url'], 
      $_POST['so_pub'], 
      $_POST['so_pri'], 
      $_POST['so_percent'], 
      $_POST['so_discount'], 
      $_POST['so_custom_1'], 
      $_POST['so_custom_2'], 
      $_POST['so_custom_3'], 
      $_POST['so_custom_4'], 
      $_POST['so_user'],
      $_POST['so_is_inv'],
      $_POST['so_bank'],
      $_POST['so_debit'],
      $_POST['so_credit'],
      $_POST['so_fee'],
      $_POST['so_option'],
      $_POST['so_inactive'],
      $_POST['so_hook_inactive']);
 
      $Ajax->activate('so_id'); // in case of status change
      display_notification(_("Payment method has been updated."));
  } 
}


//------------------------------------------------------------------------------
start_form();

start_table(TABLESTYLE_NOBORDER);
if (!$so_id)
{
  display_heading(_('Select a Payment Intergration'));
}
strikeout_method_list_row(_("Payment Methods: "), 'so_id', null, true, true);

end_table();
if($so_id)
  method_settings($so_id); 
$Ajax->activate('_page_body'); // in case of status change
end_form();
end_page();
