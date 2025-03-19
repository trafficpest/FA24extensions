<?php
/**********************************************************************
***********************************************************************/
$page_security = 'SA_SALESTRANSVIEW'; // Define a new security access level
$path_to_root = "../../..";
include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/sales/includes/sales_ui.inc"); // You might need other includes
include_once($path_to_root . "/sales/includes/sales_db.inc"); // If needed
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
    $js .= get_js_open_window(900, 500);
if (user_use_date_picker())
    $js .= get_js_date_picker();
page(_($help_context = "Route Delivery Log Inquiry"), false, false, "", $js);

//------------------------------------------------------------------------------------------------

function delivery_status_name($row) {
    return _(ucfirst($row['delivery_status']));
}

function gps_format($row) {
    if (empty($row['gps_coordinates'])) {
        return ''; // Return empty string if coordinates are empty
    }

    $gps_coordinates = $row['gps_coordinates']; // Get coordinates from the row
    $coordinates = explode(',', $gps_coordinates);
    $formatted_coordinates = [];

    foreach ($coordinates as $coord) {
        $trimmed_coord = trim($coord); // Remove leading/trailing whitespace
        if (is_numeric($trimmed_coord)) {
            $formatted_coordinates[] = number_format((float)$trimmed_coord, 4);
        } else {
            // Handle non-numeric coordinates (e.g., invalid input)
            $formatted_coordinates[] = $trimmed_coord; // Or return an error message
        }
    }

    return implode(', ', $formatted_coordinates);
}

function payment_received_name($row) {
    return _(ucfirst($row['payment_received']));
}

function shipper_name($row) {
    return $row['shipper_name'];
}

function customer_name($row) {
    if ($row['debtor_no']) {
        $customer = get_customer($row['debtor_no']);
        return $customer['name'];
    }
    return _('N/A');
}

function branch_name($row) {
    $branch = get_branch($row['branch_code']);
    return $branch['br_name'];
}

function fmt_payment_amount($row) {
    return price_format($row['payment_amount']);
}

function delivery_row_style($row) {
    if ($row['delivery_status'] != 'delivered') {
      return true;
    }
    return false;
}

function invoice_link($row)
{
  return $row["Outstanding"]==0 ? '' :
    pager_link(_('Invoice'), "/sales/customer_invoice.php?DeliveryNumber=" 
      .$row['trans_no'], ICON_DOC);
}

//------------------------------------------------------------------------------------------------

start_form();

start_table(TABLESTYLE_NOBORDER);
start_row();

shippers_list_cells(_("Select Shipper: "), 'shipper_id', null, true, true);
customer_list_cells(_("Select Customer: "), 'debtor_no', null, true, true, false, true);
//branch_list_cells(_("Select Branch: "), 'branch_code', null, true, true, false, true);

date_cells(_("From:"), 'TransAfterDate', '', null, -user_transaction_days());
date_cells(_("To:"), 'TransToDate', '', null);

submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'), 'default');
end_row();
end_table();

if (get_post('RefreshInquiry'))
{
    $Ajax->activate('_page_body');
}
//------------------------------------------------------------------------------------------------
$sql = get_sql_for_delivery_log_inquiry(get_post('TransAfterDate'), get_post('TransToDate'),
    get_post('shipper_id'), get_post('debtor_no'));

//------------------------------------------------------------------------------------------------

$cols = array(
    _("Log ID") => array('align'=>'right'),
    _("Shipper") => array('fun'=>'shipper_name'),
    _("Transaction #") => array('align'=>'right'),
    _("Type") => array('align'=>'center'),
    _("Customer") => array('fun'=>'customer_name'),
    _("Branch") => array('fun'=>'branch_name'),
    _("GPS Coordinates") => array('fun'=>'gps_format'),
    _("Delivery Status") => array('fun'=>'delivery_status_name'),
    _("Shipper Note"),
    _("Payment Received") => array('fun'=>'payment_received_name'),
    _("Payment Amount") => array('align'=>'right', 'fun'=>'fmt_payment_amount'),
    _("Photo Proof"),
    _("Customer Acknowledged"),
    _("Timestamp") 
);

$table =& new_db_pager('delivery_log_tbl', $sql, $cols);

$table->set_marker('delivery_row_style', _("Non delivered marked."));

$table->width = "90%";

display_db_pager($table);

end_form();
end_page();

//------------------------------------------------------------------------------------------------
function get_sql_for_delivery_log_inquiry($from_date, $to_date, $shipper_id, $debtor_no) {
    $sql = "SELECT " . TB_PREF . "route_delivery_log.*, " . // Select all columns from the delivery log.
           TB_PREF . "shippers.shipper_name, " . // Add shipper_name.
           TB_PREF . "debtors_master.name AS customer_name, " . // Add customer_name.
           TB_PREF . "cust_branch.br_name " . // Add branch_name.
           "FROM " . TB_PREF . "route_delivery_log " .
           "LEFT JOIN " . TB_PREF . "shippers ON " . TB_PREF . "route_delivery_log.shipper_id = " . TB_PREF . "shippers.shipper_id " .
           "LEFT JOIN " . TB_PREF . "debtors_master ON " . TB_PREF . "route_delivery_log.debtor_no = " . TB_PREF . "debtors_master.debtor_no " .
           "LEFT JOIN " . TB_PREF . "cust_branch ON " . TB_PREF . "route_delivery_log.branch_code = " . TB_PREF . "cust_branch.branch_code " .
           "WHERE 1=1";

    if ($from_date != '') {
        $sql .= " AND " . TB_PREF . "route_delivery_log.timestamp >= '" . date2sql($from_date) . "'";
    }
    if ($to_date != '') {
        $sql .= " AND " . TB_PREF . "route_delivery_log.timestamp <= '" . date2sql($to_date) . " 23:59:59'";
    }
    if ($shipper_id != ALL_TEXT && $shipper_id != '') {
        $sql .= " AND " . TB_PREF . "route_delivery_log.shipper_id = " . db_escape($shipper_id);
    }
    if ($debtor_no != ALL_TEXT && $debtor_no != '') {
        $sql .= " AND " . TB_PREF . "route_delivery_log.debtor_no = " . db_escape($debtor_no);
    }

    $sql .= " ORDER BY " . TB_PREF . "route_delivery_log.timestamp DESC";
    return $sql;
}

