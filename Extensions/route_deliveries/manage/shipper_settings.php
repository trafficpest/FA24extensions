<?php
/**********************************************************************
shippers_settings.php
***********************************************************************/
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";

include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/modules/route_deliveries/includes/route_delivery.inc");

function shipper_emp_type_row($label, $name, $selected = 'Full-Time') {
    echo "<tr><td class='label'>$label</td><td>";
    echo "<select name='$name'>";

    // Dropdown options
    $options = [
        'Full-Time'   => _("Full-Time"),
        'Part-Time'   => _("Part-Time"),
        'Contractor'  => _("Contractor"),
        'Other'       => _("Other"),
    ];

    // Generate dropdown options
    foreach ($options as $key => $value) {
        $selected_attr = ($key == $selected) ? " selected" : "";
        echo "<option value='$key'$selected_attr>$value</option>";
    }

    echo "</select></td></tr>";
}

function shipper_days_row($label, $name, $selected_days = '') {
    echo "<tr><td class='label'>$label</td><td>";

    // Days of the week options
    $days = [
        'Mo' => _("Mo"),
        'Tu' => _("Tu"),
        'We' => _("We"),
        'Th' => _("Th"),
        'Fr' => _("Fr"),
        'Sa' => _("Sa"),
        'Su' => _("Su"),
    ];

    // Convert stored values into an array 
    $selected = explode(',', $selected_days);

    foreach ($days as $key => $value) {
        $checked = in_array($key, $selected) ? "checked" : "";
        echo "<label><input type='checkbox' name='{$name}[]' value='$key' $checked> $value</label> ";
    }

    echo "</td></tr>";
}

function shipper_time_row($label, $name, $selected_time = '') {
    echo "<tr><td class='label'>$label</td><td>";
    echo "<input type='time' name='$name' value='$selected_time'>";
    echo "</td></tr>";
}

$selected_shipper = isset($_GET['shipper_id']) ? $_GET['shipper_id'] : get_post('shipper_id', '');

//display_notification(json_encode($_POST));

// Handle form submission
if (isset($_POST['update_shipper']) && $selected_shipper) {
    $latitude = get_post('latitude');
    $longitude = get_post('longitude');
    $service_radius = get_post('service_radius');
    if (isset($_POST['availability_days']) && 
        is_array($_POST['availability_days'])) {
        $availability_days = implode(',', $_POST['availability_days']); 
    } else {
        $availability_days = ''; 
    }    
    $availability_start_time = $_POST['availability_start_time'];
    $availability_end_time = $_POST['availability_end_time'];
    $employment_type = $_POST['employment_type'];
    $tax_id = $_POST['tax_id'];
    $hourly_rate = get_post('hourly_rate');
    $production_percent = get_post('production_percent');
    $production_fixed = get_post('production_fixed');
    $certifications = $_POST['certifications'];
    $notes = $_POST['notes'];

    $result = update_route_delivery_shipper(
        $selected_shipper, $latitude, $longitude, $service_radius,
        $availability_days, $availability_start_time, $availability_end_time,
        $employment_type, $tax_id, $hourly_rate, $production_percent,
        $production_fixed, $certifications, $notes
    );

    if ($result) {
        display_notification(_("Shipper details updated successfully."));
    } else {
        display_error(_("Failed to update shipper details."));
    }
}

page(_($help_context = "Route Plugin Settings"));
start_form();

// Select shipper dropdown
if (db_has_shippers()) {
    start_table(TABLESTYLE_NOBORDER);
    start_row();
    shippers_list_cells(_("Select a Shipper: "), 'shipper_id', $selected_shipper, true);
    submit_cells('submit_shipper', 'Select');
    end_row();
    end_table();

    if ($selected_shipper) {
        display_notification(_("Editing shipper: ".$selected_shipper));

        // Load shipper data
        $shipper = get_route_delivery_shipper($selected_shipper);
        
        if (!$shipper) {
            // No record found, set default empty values
            $shipper = [
                'latitude' => '',
                'longitude' => '',
                'service_radius' => '',
                'availability_days' => '',
                'availability_start_time' => '',
                'availability_end_time' => '',
                'employment_type' => 'Full-Time',
                'tax_id' => '',
                'hourly_rate' => 0,
                'production_percent' => 0,
                'production_fixed' => 0,
                'certifications' => '',
                'notes' => '',
            ];
        }
        start_outer_table(TABLESTYLE2);
        table_section(1);

        table_section_title(_("Shipper Information"));
        text_row(_("Home Latitude:"), 'latitude', $shipper['latitude'], 30, 50);
        text_row(_("Home Longitude:"), 'longitude', $shipper['longitude'], 30, 50);
        text_row(_("Service Radius (mi/km):"), 'service_radius', $shipper['service_radius'], 10, 20);
        shipper_days_row(_("Availability Days:"), 'availability_days', $shipper['availability_days']);
        shipper_time_row(_("Start Time:"), 'availability_start_time', $shipper['availability_start_time']);
        shipper_time_row(_("End Time:"), 'availability_end_time', $shipper['availability_end_time']);
        shipper_emp_type_row(_("Employment Type:"), 'employment_type', $shipper['employment_type']);
        text_row(_("Tax ID:"), 'tax_id', $shipper['tax_id'], 20, 50);
        amount_row(_("Hourly Rate ($):"), 'hourly_rate', $shipper['hourly_rate']);
        percent_row(_("Production Percent (%):"), 'production_percent', $shipper['production_percent']);
        amount_row(_("Production Fixed ($):"), 'production_fixed', $shipper['production_fixed']);
        text_row(_("Certifications:"), 'certifications', $shipper['certifications'], 40, 100);
        textarea_row(_("Notes:"), 'notes', $shipper['notes'], 40, 5);

        end_outer_table(1);
        submit_center('update_shipper', 'Update Shipper');
    } else {
        display_notification(_("Please select a shipper."));
    }
} else {
    display_notification(_("No shippers found."));
}

end_form();
end_page();

