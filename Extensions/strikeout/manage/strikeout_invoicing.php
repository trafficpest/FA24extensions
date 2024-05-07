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
$page_security = 'SA_INVOICE_STRIKEOUT';

include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

$js = "";
	
page(_($help_context = "StrikeOut Invoicing"), 
  false, false, "", $js); 

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root 
  ."/modules/strikeout/inc/strikeout_fa.inc");
include_once($path_to_root  
  ."/modules/strikeout/pay/inc/phpqrcode/qrlib.php");

if (isset($_GET['so_id'])) 
	$_POST['so_id'] = $_GET['so_id'];

$so_id = get_post('so_id');

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
function make_inv_url(){
  global $so_id;

    $invoice_path = 'https://'.$_SERVER['HTTP_HOST']
    .strtok($_SERVER['REQUEST_URI'], '?');
    
    if ($so_id == 1)
      $replace = 'pay/'
      .'?co='.user_company()
      .'&amount='.$_POST['amount'];
    else 
      $replace = 'pay/methods/'.$_POST['so_method'].'/'
      .'?co='.user_company()
      .'&amount='.$_POST['amount'];

    $invoice_path = str_replace(
      'manage/strikeout_invoicing.php',
      $replace,
      $invoice_path
    ); 

    if (!empty($_POST['name']))
      $invoice_path = $invoice_path.'&name='.$_POST['name'];
    if (!empty($_POST['custId']))
      $invoice_path = $invoice_path.'&custId='.$_POST['custId'];
    return $invoice_path;
}
	
function method_settings($so_id) 
{
	global $page_nested, $path_to_root;
	
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

}
  $inv_url = make_inv_url();

  // Quick Debug
  //display_notification(json_encode($myrow));  
  //display_notification(json_encode($webhook));
  //display_notification(json_encode($_POST));
	start_outer_table(TABLESTYLE2);

	table_section(1);
	table_section_title(_("Static Invoice"));
	hidden('co', user_company());
	text_row(_("Amount:"), 'amount', NULL, 50, 255);
	text_row(_("Name:"), 'name', NULL, 50, 255);
	text_row(_("Reference:"), 'custId', NULL, 50, 255);
  	//table_section(2);

  end_outer_table(1);

	div_start('qr_code');
  if (isset($_POST['static_submit'])){
    array_map('unlink', glob($path_to_root.'/tmp/*so.png'));
    so_static_qr($inv_url);
    hyperlink_no_params($inv_url, 'Invoice Link', $center=true);
  }
	div_end();

	div_start('controls');
  //function submit_center_first($name, $value, $title=false, $async=false, $icon=false)
  submit_center_first('static_submit', _("Create Payment Link"), 
    _('Create Payment Link'), $page_nested ? true : 'default');
  //submit_center_last('delete', _("Delete StrikeOut Method"), 
  //  _('Delete StrikeOut Method'), true);
	div_end();
}

/*
if (isset($_POST['static_submit'])) 
{

	if ($so_id) 
  {
      //display_notification(json_encode($_POST));

 
      $Ajax->activate('so_id'); // in case of status change
      display_notification(_("Copy URL or QR Code for Static Invoice Generation"));
  } 
}
 */

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
