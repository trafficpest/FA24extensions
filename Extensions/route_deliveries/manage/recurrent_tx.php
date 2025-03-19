<?php

$page_security = 'SA_SRECURRENT';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/db_pager.inc");
include($path_to_root . "/includes/session.inc");
include($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/modules/route_deliveries/includes/route_delivery.inc");

// DEBUG
display_notification(json_encode($_POST));

$js = "";
if ($SysPrefs->use_popup_windows)
    $js .= get_js_open_window(900, 600);
if (user_use_date_picker())
    $js .= get_js_date_picker();

page(_($help_context = "Recurrent Transactions"), false, false, "", $js);

check_db_has_template_orders(_("There is no template order in the database.
    You have to create at least one sales order marked as a template to define recurrent transactions."));

simple_page_mode(true);

if ($Mode == 'ADD_ITEM' || $Mode == 'UPDATE_ITEM') {
    $input_error = 0;

    // Validate required fields
    if (!get_post('order_no')) {
        $input_error = 1;
        display_error(_("You must select a template order."));
        set_focus('order_no');
    }
    if (!get_post('debtor_no')) {
        $input_error = 1;
        display_error(_("You must select a customer."));
        set_focus('debtor_no');
    }
    if (!get_post('branch_code')) {
        $input_error = 1;
        display_error(_("You must select a branch."));
        set_focus('branch_code');
    }
    if (!is_date($_POST['start_date'])) {
        $input_error = 1;
        display_error(_("The start date is invalid."));
        set_focus('start_date');
    }
    if ($_POST['end_date'] && !is_date($_POST['end_date'])) {
        $input_error = 1;
        display_error(_("The end date is invalid."));
        set_focus('end_date');
    }

    // Validate recurrence rules
    if ($_POST['frequency'] == 'WEEKLY' && !$_POST['weekdays']) {
        $input_error = 1;
        display_error(_("You must select at least one weekday for weekly recurrence."));
        set_focus('weekdays');
    }
    if ($_POST['frequency'] == 'MONTHLY' && !$_POST['month_day'] && !$_POST['weekday_of_month']) {
        $input_error = 1;
        display_error(_("You must specify a day or weekday for monthly recurrence."));
        set_focus('month_day');
    }

    if ($input_error != 1) {
        $data = [
            'order_no' => $_POST['order_no'],
            'debtor_no' => $_POST['debtor_no'],
            'branch_code' => $_POST['branch_code'],
            'type' => $_POST['type'],
            'uuid' => uniqid(),
            'frequency' => $_POST['frequency'],
            'interval' => $_POST['interval'],
            'weekdays' => $_POST['weekdays'],
            'month_day' => $_POST['month_day'],
            'week_number' => $_POST['week_number'],
            'weekday_of_month' => $_POST['weekday_of_month'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'next_run' => calculate_next_run($_POST), // Custom function to calculate next run
            'summary' => $_POST['summary'],
            'description' => $_POST['description'],
            'status' => 'active'
        ];

        if ($selected_id != -1) {
            update_route_delivery_rtx($selected_id, $data);
            display_notification(_('Recurrent transaction updated.'));
        } else {
            add_route_delivery_rtx($data);
            display_notification(_('New recurrent transaction added.'));
        }
        $Mode = 'RESET';
    }
}

if ($Mode == 'Delete') {
    delete_route_delivery_rtx($$selected_id);
    display_notification(_('Recurrent transaction deleted.'));
    $Mode = 'RESET';
}

if ($Mode == 'RESET') {
    $selected_id = -1;
    unset($_POST);
}

//-------------------------------------------------------------------------------------------------

// List Recurrent Transactions
$result = get_all_route_delivery_rtx();

start_form();
start_table(TABLESTYLE, "width=70%");
$th = array(_("Description"), _("Order No"), _("Customer"), _("Branch"), _("Frequency"), _("Next Run"), _("Status"), "", "");
table_header($th);
$k = 0;
while ($myrow = db_fetch($result)) {
    alt_table_row_color($k);
    label_cell($myrow["description"]);
    label_cell($myrow["order_no"]);
    label_cell(get_customer_name($myrow["debtor_no"]));
    label_cell(get_branch_name($myrow["branch_code"]));
    label_cell($myrow["frequency"]);
    label_cell(sql2date($myrow["next_run"]));
    label_cell($myrow["status"]);
    edit_button_cell("Edit" . $myrow["id"], _("Edit"));
    delete_button_cell("Delete" . $myrow["id"], _("Delete"));
    end_row();
}
end_table();
end_form();

//-------------------------------------------------------------------------------------------------

// Add/Edit Form
start_form();
start_table(TABLESTYLE2);

if ($selected_id != -1 && $Mode == 'Edit') {
    $myrow = get_recurrent_transaction($selected_id);
    $_POST = $myrow; // Populate form fields
    hidden("selected_id", $selected_id);
}

text_row_ex(_("Description:"), 'description', 50);
templates_list_row(_("Template Order:"), 'order_no');
customer_list_row(_("Customer:"), 'debtor_no', null, " ", true);
customer_branches_list_row(_("Branch:"), $_POST['debtor_no'], 'branch_code', null, false);
array_selector_row(_("Type:"), 'type', null, ['invoice' => _("Invoice"), 'delivery' => _("Delivery")]);
array_selector_row(_("Frequency:"), 'frequency', null, [
    'DAILY' => _("Daily"),
    'WEEKLY' => _("Weekly"),
    'MONTHLY' => _("Monthly"),
    'YEARLY' => _("Yearly")
]);
small_amount_row(_("Interval:"), 'interval', 1);
text_row_ex(_("Weekdays (MO,TU,WE,TH,FR,SA,SU):"), 'weekdays', 20);
small_amount_row(_("Month Day (1-31):"), 'month_day', null);
small_amount_row(_("Week Number (1-4, -1 for last):"), 'week_number', null);
text_row_ex(_("Weekday of Month (MO,TU,WE,TH,FR,SA,SU):"), 'weekday_of_month', 2);
date_row(_("Start Date:"), 'start_date');
date_row(_("End Date:"), 'end_date');
textarea_row(_("Summary:"), 'summary', null, 40, 3);
textarea_row(_("Description:"), 'description', null, 40, 3);

end_table(1);
submit_add_or_update_center($selected_id == -1, '', 'both');
end_form();

end_page();
?>
