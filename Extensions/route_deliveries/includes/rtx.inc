<?php

function add_route_delivery_rtx($data) {

    $sql = "INSERT INTO ".TB_PREF."route_delivery_rtx (
        order_no, debtor_no, branch_code, type, uuid, frequency, `interval`, 
        weekdays, month_day, week_number, weekday_of_month, start_date, end_date, 
        next_run, summary, description, status
    ) VALUES (
        " . db_escape($data['order_no']) . ",
        " . db_escape($data['debtor_no']) . ",
        " . db_escape($data['branch_code']) . ",
        " . db_escape($data['type']) . ",
        " . db_escape($data['uuid']) . ",
        " . db_escape($data['frequency']) . ",
        " . db_escape($data['interval']) . ",
        " . db_escape($data['weekdays']) . ",
        " . db_escape($data['month_day']) . ",
        " . db_escape($data['week_number']) . ",
        " . db_escape($data['weekday_of_month']) . ",
        " . db_escape($data['start_date']) . ",
        " . db_escape($data['end_date']) . ",
        " . db_escape($data['next_run']) . ",
        " . db_escape($data['summary']) . ",
        " . db_escape($data['description']) . ",
        " . db_escape($data['status']) . "
    )";

    return db_query($sql, "Could not add recurrent transaction.");
}

function update_route_delivery_rtx($id, $data) {

    $sql = "UPDATE ".TB_PREF."route_delivery_rtx SET
        order_no = " . db_escape($data['order_no']) . ",
        debtor_no = " . db_escape($data['debtor_no']) . ",
        branch_code = " . db_escape($data['branch_code']) . ",
        type = " . db_escape($data['type']) . ",
        frequency = " . db_escape($data['frequency']) . ",
        `interval` = " . db_escape($data['interval']) . ",
        weekdays = " . db_escape($data['weekdays']) . ",
        month_day = " . db_escape($data['month_day']) . ",
        week_number = " . db_escape($data['week_number']) . ",
        weekday_of_month = " . db_escape($data['weekday_of_month']) . ",
        start_date = " . db_escape($data['start_date']) . ",
        end_date = " . db_escape($data['end_date']) . ",
        next_run = " . db_escape($data['next_run']) . ",
        summary = " . db_escape($data['summary']) . ",
        description = " . db_escape($data['description']) . ",
        status = " . db_escape($data['status']) . "
        WHERE id = " . db_escape($id);

    return db_query($sql, "Could not update recurrent transaction.");
}

function delete_route_delivery_rtx($id) {
    global $db;

    $sql = "DELETE FROM ".TB_PREF."route_delivery_rtx WHERE id = " . db_escape($id);
    return db_query($sql, "Could not delete recurrent transaction.");
}


function get_all_route_delivery_rtx() {

    $sql = "SELECT * FROM ".TB_PREF."route_delivery_rtx";
    $result = db_query($sql, "Could not fetch recurrent transactions.");

    return $result; // Return the array of records
}

function calculate_next_run($data) {
    $startDate = new DateTime($data['start_date']);
    $endDate = $data['end_date'] ? new DateTime($data['end_date']) : null;
    $interval = max(1, (int)$data['interval']); // Ensure interval is at least 1

    $now = new DateTime();
    if ($now < $startDate) {
        $nextRun = $startDate;
    } else {
        $nextRun = clone $startDate; // Create a copy to avoid modifying the original
        switch ($data['frequency']) {
            case 'DAILY':
                while ($nextRun <= $now) {
                    $nextRun->modify("+" . $interval . " day");
                }
                break;

            case 'WEEKLY':
                $weekdays = explode(',', strtoupper($data['weekdays']));
                if (empty($weekdays)) {
                    return null; // No weekdays selected, invalid
                }
                while ($nextRun <= $now) {
                    $nextRun->modify("+" . $interval . " week");
                    $dayOfWeek = $nextRun->format('D'); // Mon, Tue, etc.
                    if (!in_array(strtoupper(substr($dayOfWeek, 0, 2)), $weekdays)) {
                        $tempNextRun = clone $nextRun;
                        foreach ($weekdays as $weekday) {
                            $tempNextRun->modify("next " . $weekday);
                            if ($tempNextRun > $now) {
                                if($tempNextRun < $nextRun || $nextRun <= $now){
                                    $nextRun = $tempNextRun;
                                }
                            }
                        }
                    }
                }
                break;

            case 'MONTHLY':
                $dayOfMonth = (int)$data['month_day'];
                $weekNumber = (int)$data['week_number'];
                $weekdayOfMonth = strtoupper($data['weekday_of_month']);

                while ($nextRun <= $now) {
                    $nextRun->modify("+" . $interval . " month");

                    if ($dayOfMonth) {
                        $nextRun->setDate($nextRun->format('Y'), $nextRun->format('m'), $dayOfMonth);
                        if ($nextRun->format('j') != $dayOfMonth) {
                            $nextRun->modify('last day of previous month');
                        }

                    } elseif ($weekdayOfMonth) {
                        $firstOfMonth = clone $nextRun;
                        $firstOfMonth->setDate($nextRun->format('Y'), $nextRun->format('m'), 1);
                        $dayOfWeek = $firstOfMonth->format('D');
                        $daysToAdd = 0;
                        $weekdays = ['MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU'];

                        if ($weekNumber === -1) {
                            $lastOfMonth = clone $nextRun;
                            $lastOfMonth->modify('last day of this month');
                            $dayOfWeek = $lastOfMonth->format('D');
                            $daysToSubtract = array_search(strtoupper(substr($dayOfWeek, 0, 2)), $weekdays) - array_search($weekdayOfMonth, $weekdays);
                            if ($daysToSubtract > 0) {
                                $daysToSubtract -= 7;
                            }
                            $nextRun = clone $lastOfMonth;
                            $nextRun->modify($daysToSubtract . ' days');
                        }
                        else {
                            $daysToAdd = (array_search($weekdayOfMonth, $weekdays) - array_search(strtoupper(substr($dayOfWeek, 0, 2)), $weekdays));
                            if ($daysToAdd < 0) {
                                $daysToAdd += 7;
                            }
                            $nextRun = clone $firstOfMonth;
                            $nextRun->modify($daysToAdd . ' days');
                            $nextRun->modify(($weekNumber - 1) * 7 . ' days');
                        }

                    }
                }
                break;

            case 'YEARLY':
                while ($nextRun <= $now) {
                    $nextRun->modify("+" . $interval . " year");
                }
                break;
        }
    }

    if ($endDate && $nextRun > $endDate) {
        return null; // Next run is after end date
    }

    return $nextRun->format('Y-m-d');
}

