<?php
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/modules/route_deliveries/includes/route_delivery.inc");
$js = "";
if (user_use_date_picker())
    $js .= get_js_date_picker();

use ICal\ICal;

// Debug
//display_notification(json_encode($_POST));

page(_($help_context = "iCal importer"));

// Get the current company's directory
$companyDir = $path_to_root . "/companies/" . $_SESSION['wa_current_user']->company . "/ics_imports/";

// Ensure the company-specific upload directory exists
if (!is_dir($companyDir)) {
    display_error("Company file directory doesn't exist: " . $companyDir);
    exit;
}

// Handle File Upload
if (isset($_POST['upload_ics']) && isset($_FILES['ics_file'])) {
    $filePath = $companyDir . basename($_FILES['ics_file']['name']);

    if (move_uploaded_file($_FILES['ics_file']['tmp_name'], $filePath)) {
        $_SESSION['ics_file'] = $filePath;
        display_notification("File uploaded successfully!");
    } else {
        display_error("File upload failed.");
    }
}

// Handle Cancel Import
if (isset($_POST['cancel_import']) && isset($_SESSION['ics_file'])){
 unset($_SESSION['ics_file']);
 display_notification("File import cancelled.");
 $Ajax->activate('_page_body');
}

if (!isset($_SESSION['ics_file'])) {
  // Start Form (Upload File)
  start_outer_table(TABLESTYLE2);
  table_section(1);
  start_form(true);
  table_section_title(_("Upload an iCal File"));
  file_row("Select iCal File:", "ics_file");
  end_outer_table(1);
  submit_center('upload_ics', "Upload");
  end_form();
  end_page();
  exit;
}

// Get user-selected date range
$startDate = isset($_POST['start_date']) ? $_POST['start_date'] : date("Y-m-d");
$endDate = isset($_POST['end_date']) ? $_POST['end_date'] : date("Y-m-d", strtotime("+3 months"));

// Display Date Picker Form
start_form(true);
start_table(TABLESTYLE2);
table_section(1);
table_section_title(_("Select Date Range"));
date_row("Start Date:", 'start_date', '', false, 0, 0, 0, ['value' => $startDate]);
date_row("End Date:", 'end_date', '', false, 0, 0, 0, ['value' => $endDate]);
end_table(1);
submit_center('filter_events', "Filter Events");
end_form();

// Parse iCal File
try {
    $ical = new ICal($_SESSION['ics_file']);
    $events = $ical->eventsFromRange($startDate, $endDate);
} catch (Exception $e) {
    display_error("Error parsing iCal file: " . $e->getMessage());
    end_page();
    exit;
}

// Pagination Setup
$eventsPerPage = 100;
$totalEvents = count($events);
$totalPages = ceil($totalEvents / $eventsPerPage);
$pageNum = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$start = ($pageNum - 1) * $eventsPerPage;
$eventsToShow = array_slice($events, $start, $eventsPerPage);

// Display Table
start_form(true);
start_table(TABLESTYLE, "width=80%");
table_header(["Select", "Event Title", "Start Date", "Recurrence", "Location", "Description", "Customer", "Branch", "Order"]);

foreach ($eventsToShow as $event) {
    // Format start and end date
    $startDate = date("Y-m-d H:i:s", strtotime($event->dtstart));
    $endDate = date("Y-m-d H:i:s", strtotime($event->dtend));
    
    // Extract useful data from additionalProperties
    $recurrence = !empty($event->rrule) ? htmlspecialchars($event->rrule) : "One-time";
    $location = !empty($event->location) ? htmlspecialchars($event->location) : "N/A";
    $description = !empty($event->description) ? htmlspecialchars($event->description) : "No description";
    //$attendee = isset($event->attendee_array[1]) ? str_replace("mailto:", "", $event->attendee_array[1]) : "No attendee info";
    $status = !empty($event->status) ? htmlspecialchars($event->status) : "N/A";

    // Start the row
    start_row();
    label_cell("<input type='checkbox' name='import[]' value='" . htmlspecialchars($event->uid) . "'>");
    label_cell(htmlspecialchars($event->summary));
    label_cell($startDate);
    //label_cell($endDate);
    label_cell($recurrence);
    label_cell($location);
    label_cell($description);
if (db_has_customers()) {
    customer_list_cells (null, 'customer_id[' . htmlspecialchars($event->uid) . ']', null, _("Select Customer"), true);
  if (isset($_POST['customer_id'][$event->uid]) && $_POST['customer_id'][$event->uid] != '') {
      $selected_customer = $_POST['customer_id'][$event->uid];

      customer_branches_list_cells(
          null, 
          $selected_customer, 
          'branch_id[' . htmlspecialchars($event->uid) . ']', 
          null, 
          true, true, true, true
      );
  }else{
    label_cell('Select Customer');
  }

}
    //label_cell($attendee);
    label_cell('Placeholder');
    end_row();
}

end_table();


// Pagination Links
echo "<div style='margin: 10px 0; text-align:center;'>";
if ($pageNum > 1) {
    echo "<a href='?page=" . ($pageNum - 1) . "'>&laquo; Previous</a> ";
}
if ($pageNum < $totalPages) {
    echo "<a href='?page=" . ($pageNum + 1) . "'>Next &raquo;</a>";
}
echo "</div>";

// Import Button
if ($totalEvents > 0) {
    submit_center_first('import_selected', "Import Selected");
    submit_center_last('cancel_import', "Cancel Import");
}
end_form();

end_page();

