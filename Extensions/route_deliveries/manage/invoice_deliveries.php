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
$page_security = 'SA_SALESINVOICE';
$path_to_root = "../../..";
include($path_to_root . "/includes/db_pager.inc");
include($path_to_root . "/includes/session.inc");

include($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 600);
if (user_use_date_picker())
	$js .= get_js_date_picker();

if (isset($_GET['OutstandingOnly']) && ($_GET['OutstandingOnly'] == true))
{
	$_POST['OutstandingOnly'] = true;
	page(_($help_context = "Search Not Invoiced Deliveries"), false, false, "", $js);
}
else
{
	$_POST['OutstandingOnly'] = false;
	page(_($help_context = "Search All Deliveries"), false, false, "", $js);
}

if (isset($_GET['selected_customer']))
{
	$_POST['customer_id'] = $_GET['selected_customer'];
}
elseif (isset($_POST['selected_customer']))
{
	$_POST['customer_id'] = $_POST['selected_customer'];
}

if (isset($_POST['BatchInvoice']))
{
	// checking batch integrity
    $del_count = 0;
    if (isset($_POST['Sel_'])) {
		foreach($_POST['Sel_'] as $delivery => $branch) {
			$checkbox = 'Sel_'.$delivery;
			if (check_value($checkbox))	{
				if (!$del_count) {
					$del_branch = $branch;
				}
				else {
					if ($del_branch != $branch)	{
						$del_count=0;
						break;
					}
				}
				$selected[] = $delivery;
				$del_count++;
			}
		}
	}
    if (!$del_count) {
		display_error(_('For batch invoicing you should
		    select at least one delivery. All items must be dispatched to
		    the same customer branch.'));
    } else {
		$_SESSION['DeliveryBatch'] = $selected;
		meta_forward($path_to_root . '/sales/customer_invoice.php','BatchInvoice=Yes');
    }
}

//-----------------------------------------------------------------------------------
if (get_post('_DeliveryNumber_changed')) 
{
	$disable = get_post('DeliveryNumber') !== '';

	$Ajax->addDisable(true, 'DeliveryAfterDate', $disable);
	$Ajax->addDisable(true, 'DeliveryToDate', $disable);
	$Ajax->addDisable(true, 'StockLocation', $disable);
	$Ajax->addDisable(true, '_SelectStockFromList_edit', $disable);
	$Ajax->addDisable(true, 'SelectStockFromList', $disable);
	// if search is not empty rewrite table
	if ($disable) {
		$Ajax->addFocus(true, 'DeliveryNumber');
	} else
		$Ajax->addFocus(true, 'DeliveryAfterDate');
	$Ajax->activate('deliveries_tbl');
}

//-----------------------------------------------------------------------------------

start_form(false, false, $_SERVER['PHP_SELF'] ."?OutstandingOnly=".$_POST['OutstandingOnly']);

start_table(TABLESTYLE_NOBORDER);
start_row();
ref_cells(_("#:"), 'DeliveryNumber', '',null, '', true);
date_cells(_("from:"), 'DeliveryAfterDate', '', null, -user_transaction_days());
date_cells(_("to:"), 'DeliveryToDate', '', null, 1);

locations_list_cells(_("Location:"), 'StockLocation', null, true);
end_row();

end_table();
start_table(TABLESTYLE_NOBORDER);
start_row();

stock_items_list_cells(_("Item:"), 'SelectStockFromList', null, true);

customer_list_cells(_("Select a customer: "), 'customer_id', null, true, true);

submit_cells('SearchOrders', _("Search"),'',_('Select documents'), 'default');

hidden('OutstandingOnly', $_POST['OutstandingOnly']);

end_row();

end_table(1);
//---------------------------------------------------------------------------------------------

function trans_view($trans, $trans_no)
{
	return get_customer_trans_view_str(ST_CUSTDELIVERY, $trans['trans_no']);
}

function order_view($row)
{
	return $row['order_']>0 ?
		get_customer_trans_view_str(ST_SALESORDER, $row['order_'])
		: "";
}

function fmt_status($row)
{
  return ucfirst($row['delivery_status']);
}

function batch_checkbox($row)
{
	$name = "Sel_" .$row['trans_no'];
	return $row['Done'] ? '' :
		"<input type='checkbox' name='$name' value='1' >"
// add also trans_no => branch code for checking after 'Batch' submit
	 ."<input name='Sel_[".$row['trans_no']."]' type='hidden' value='"
	 .$row['branch_code']."'>\n";
}

function edit_link($row)
{
	return $row["Outstanding"]==0 ? '' :
		trans_editor_link(ST_CUSTDELIVERY, $row['trans_no']);
}

function copy_link($row)
{
  return pager_link(_('Copy Delivery'), "/sales/sales_order_entry.php?NewDelivery=" 
      .$row['order_'], ICON_DOC);
}

function prt_link($row)
{
	return print_document_link($row['trans_no'], _("Print"), true, ST_CUSTDELIVERY, ICON_PRINT);
}

function invoice_link($row)
{
    if ($row["delivery_status"] == 'delivered') {
        return $row["Outstanding"] == 0 ? '' :
            pager_link(_('Invoice'), "/sales/customer_invoice.php?DeliveryNumber="
                . $row['trans_no'], ICON_DOC);
    }
}

function check_overdue($row)
{
   	return date1_greater_date2(Today(), sql2date($row["due_date"])) && 
			$row["Outstanding"]!=0;
}

function get_sql_for_sales_deliveries_view_rd($from, $to, $customer_id, $stock_item, $location, $delivery, $outstanding=false)
{
	$sql = "SELECT trans.trans_no,
      trans.order_,
			debtor.name,
			branch.branch_code,
			branch.br_name,
			sorder.deliver_to,
			trans.reference,
			sorder.customer_ref,
			trans.tran_date,
			trans.due_date,
      IFNULL(log.delivery_status, 'pending') AS delivery_status,
      IFNULL(log.notes, 'pending') AS delivery_notes,
			(ov_amount+ov_gst+ov_freight+ov_freight_tax) AS DeliveryValue,
			debtor.curr_code,
			Sum(line.quantity-line.qty_done) AND sorder.prep_amount=0 AS Outstanding,
			Sum(line.qty_done) AS Done
		FROM ".TB_PREF."sales_orders as sorder
    JOIN ".TB_PREF."debtor_trans AS trans ON sorder.order_no = trans.order_
    JOIN ".TB_PREF."debtor_trans_details AS line 
      ON trans.trans_no = line.debtor_trans_no 
      AND trans.type = line.debtor_trans_type
    JOIN ".TB_PREF."debtors_master AS debtor 
      ON trans.debtor_no = debtor.debtor_no
    JOIN ".TB_PREF."cust_branch AS branch 
      ON trans.branch_code = branch.branch_code 
      AND trans.debtor_no = branch.debtor_no
    LEFT JOIN ".TB_PREF."route_delivery_log AS log 
      ON trans.trans_no = log.transaction_no AND trans.type = log.type
			WHERE
			sorder.order_no = trans.order_ AND
			trans.debtor_no = debtor.debtor_no
				AND trans.type = ".ST_CUSTDELIVERY."
				AND line.debtor_trans_no = trans.trans_no
				AND line.debtor_trans_type = trans.type
				AND trans.branch_code = branch.branch_code
				AND trans.debtor_no = branch.debtor_no "; 
				//AND log.transaction_no = trans.trans_no
				//AND log.type = trans.type ";


	if ($outstanding == true) {
		 $sql .= " AND line.qty_done < line.quantity ";
	}

	//figure out the sql required from the inputs available
	if ($delivery)
	{
		$sql .= " AND trans.trans_no LIKE ".db_escape('%' . $delivery . '%');
		$sql .= " GROUP BY trans.trans_no";
	}
	else
	{
		$sql .= " AND trans.tran_date >= '".date2sql($from)."'";
		$sql .= " AND trans.tran_date <= '".date2sql($to)."'";

		if ($stock_item != ALL_TEXT)
			$sql .= " AND line.stock_id=".db_escape($stock_item)." ";

		if ($location != ALL_TEXT)
			$sql .= " AND sorder.from_stk_loc = ".db_escape($location)." ";

		if ($customer_id != ALL_TEXT)
			$sql .= " AND trans.debtor_no = ".db_escape($customer_id);

		$sql .= " GROUP BY trans.trans_no ";

	} //end no delivery number selected
	return $sql;
}

//------------------------------------------------------------------------------------------------
$sql = get_sql_for_sales_deliveries_view_rd(get_post('DeliveryAfterDate'), get_post('DeliveryToDate'), get_post('customer_id'),	
	get_post('SelectStockFromList'), get_post('StockLocation'), get_post('DeliveryNumber'), get_post('OutstandingOnly'));

$cols = array(
		_("Delivery #") => array('fun'=>'trans_view', 'align'=>'right'), 
		_("Order") => array('fun'=>'order_view', 'align'=>'right'), 
		_("Customer"), 
		'branch_code' => 'skip',
		_("Branch") => array('ord'=>''), 
		_("Contact") => 'skip',
		_("Reference"), 
		_("Cust Ref") => 'skip', 
		_("Delivery Date") => array('type'=>'date', 'ord'=>''),
		_("Due By") => 'date', 
		_("Status") => array('fun'=>'fmt_status'), 
		_("Notes"), 
		_("Delivery Total") => array('type'=>'amount', 'ord'=>''),
		_("Currency") => array('align'=>'center'),
		submit('BatchInvoice',_("Batch"), false, _("Batch Invoicing")) 
			=> array('insert'=>true, 'fun'=>'batch_checkbox', 'align'=>'center'),
		array('insert'=>true, 'fun'=>'edit_link'),
		array('insert'=>true, 'fun'=>'copy_link'),
		array('insert'=>true, 'fun'=>'invoice_link'),
		array('insert'=>true, 'fun'=>'prt_link')
);

//-----------------------------------------------------------------------------------
if (isset($_SESSION['Batch']))
{
    foreach($_SESSION['Batch'] as $trans=>$del)
    	unset($_SESSION['Batch'][$trans]);
    unset($_SESSION['Batch']);
}

$table =& new_db_pager('deliveries_tbl', $sql, $cols);
$table->set_marker('check_overdue', _("Marked items are overdue."));

//$table->width = "92%";

display_db_pager($table);

end_form();
end_page();

