<?php

function update_route_delivery_shipper(
    $shipper_id, $latitude, $longitude, $service_radius,
    $availability_days, $availability_start_time,
    $availability_end_time, $employment_type, $tax_id,
    $hourly_rate, $production_percent, $production_fixed,
    $certifications, $notes
) {

    $sql = "INSERT INTO ".TB_PREF."route_delivery_shippers (
        shipper_id, latitude, longitude, service_radius,
        availability_days, availability_start_time,
        availability_end_time, employment_type, tax_id,
        hourly_rate, production_percent, production_fixed,
        certifications, notes, created_at, updated_at
    ) VALUES (
        " . db_escape($shipper_id) . ", " . db_escape($latitude) . ",
        " . db_escape($longitude) . ", " . db_escape($service_radius) . ",
        " . db_escape($availability_days) . ",
        " . db_escape($availability_start_time) . ",
        " . db_escape($availability_end_time) . ",
        " . db_escape($employment_type) . ",
        " . db_escape($tax_id) . ", " . db_escape($hourly_rate) . ",
        " . db_escape($production_percent) . ",
        " . db_escape($production_fixed) . ",
        " . db_escape($certifications) . ", " . db_escape($notes) . ",
        CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
    ) ON DUPLICATE KEY UPDATE 
        latitude = VALUES(latitude), 
        longitude = VALUES(longitude),
        service_radius = VALUES(service_radius),
        availability_days = VALUES(availability_days),
        availability_start_time = VALUES(availability_start_time),
        availability_end_time = VALUES(availability_end_time),
        employment_type = VALUES(employment_type),
        tax_id = VALUES(tax_id),
        hourly_rate = VALUES(hourly_rate),
        production_percent = VALUES(production_percent),
        production_fixed = VALUES(production_fixed),
        certifications = VALUES(certifications),
        notes = VALUES(notes),
        updated_at = CURRENT_TIMESTAMP";
    //echo $sql;
    db_query($sql, "Could not update or insert shipper record");

    return true;
}


function get_route_delivery_shipper($shipper_id) {
  $sql = "SELECT * FROM ".TB_PREF."route_delivery_shippers 
          WHERE shipper_id = " . db_escape($shipper_id);
  return db_fetch(db_query($sql, 
      "Could not retrieve shipper options"));
}

?>
