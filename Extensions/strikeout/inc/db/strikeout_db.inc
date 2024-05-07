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
/*
function add_strikeout_method($so_id, $so_name, $so_method, 
  $so_pub, $so_pri, $so_percent, $so_discount,  $so_custom_1, 
  $so_custom_2, $so_custom_3, $so_custom_4, $so_inactive)
{
  $sql = "INSERT INTO ".TB_PREF."strikeout (so_id, so_name, so_method, 
    so_pub, so_pri, so_percent, so_discount, so_custom_1, 
    so_custom_2, so_custom_3, so_custom_4, so_inactive) 
    VALUES ("
		.db_escape($so_id) .", "
		.db_escape($so_name) .", "
		.db_escape($so_method) .", "
		.db_escape($so_pub) .", "
		.db_escape($so_pri) . ", "
		.db_escape($so_percent) . ","
		.db_escape($so_discount) . ", " 
		.db_escape($so_custom_1) . ", "
		.db_escape($so_custom_2) . ", "
		.db_escape($so_custom_3) .", "
		.db_escape($so_custom_4).", "
		.db_escape($so_inactive).")";
	db_query($sql,"The strikeout method could not be added");
}
*/

function update_strikeout_method($so_id, $so_name, $so_method, $so_url, 
  $so_pub, $so_pri, $so_percent, $so_discount, 
  $so_custom_1, $so_custom_2, $so_custom_3, $so_custom_4,  
  $so_user, $so_is_inv, $so_bank, $so_debit, $so_credit,
  $so_fee, $so_option, $so_inactive, $so_hook_inactive)
{
	$sql = "UPDATE ".TB_PREF."strikeout SET 
		so_name=" . db_escape($so_name) . ", 
		so_method=" . db_escape($so_method) . ", 
		so_url=" . db_escape($so_url) . ", 
		so_pub=" . db_escape($so_pub) . ",
		so_pri=".db_escape($so_pri) . ", 
		so_percent=".db_escape($so_percent) . ", 
		so_discount=".db_escape($so_discount) . ", 
		so_custom_1=" .db_escape($so_custom_1) . ", 
		so_custom_2=" .db_escape($so_custom_2) . ", 
		so_custom_3 = ".db_escape($so_custom_3) . ", 
    so_custom_4=".db_escape($so_custom_4) . ", 
    so_user=".db_escape($so_user) . ", 
    so_is_inv=".db_escape($so_is_inv) . ", 
    so_bank=".db_escape($so_bank) . ", 
    so_debit=".db_escape($so_debit) . ", 
    so_credit=".db_escape($so_credit) . ", 
    so_fee=".db_escape($so_fee) . ", 
    so_option=".db_escape($so_option) . ", 
    so_inactive=".db_escape($so_inactive) .",
    so_hook_inactive=".db_escape($so_hook_inactive) ."
		WHERE so_id= ".db_escape($so_id);

	db_query($sql,"The strikeout method could not be updated");
}

function delete_strikeout_method($so_id)
{
	$sql = "DELETE FROM ".TB_PREF."strikeout WHERE so_id=".db_escape($so_id);
	db_query($sql,"cannot delete strikeout method");

}

function get_strikeout_id($so_id)
{
  $sql = "SELECT * FROM ".TB_PREF."strikeout WHERE so_id="
    .db_escape($so_id);

	$result = db_query($sql, "could not get strikeout info");

	return db_fetch($result);
}

function get_strikeout_method($so_method)
{
  $sql = "SELECT * FROM ".TB_PREF."strikeout WHERE so_method="
    .db_escape($so_method);

	$result = db_query($sql, "could not get strikeout info");

	return db_fetch($result);
}
