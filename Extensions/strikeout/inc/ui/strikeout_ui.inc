<?php

function strikeout_method_list($name, $selected_id=null, $submit_on_change=false, $special_option=false)
{
	$sql = "SELECT so_id, so_name FROM ".TB_PREF."strikeout";

	return combo_input($name, $selected_id, $sql, 'so_id', 'so_name',
	array(
		'spec_option' => $special_option===true ? _("Select Method") : $special_option,
		'spec_id' => null,
		'select_submit'=> $submit_on_change,
	) );
}

function strikeout_options_list($label, $name)
{
  $items = array(
    "tax_id" => "Tax ID",
    "debtor_no" => "Debtor Number",
    "invoice" => "Invoice Ref",
    "bank_deposit" => "Bank Deposit",
  );

  array_selector_row($label, $name, $selected_id=null, $items, $options=null);
}

function strikeout_method_list_cells($label, $name, $selected_id=null, $submit_on_change=false, $special_option=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo strikeout_method_list($name, $selected_id, $submit_on_change, $special_option);
	echo "</td>\n";
}

function strikeout_method_list_row($label, $name, $selected_id=null, $submit_on_change=false, $special_option=false)
{
	echo "<tr><td class='label'>$label</td>";
	strikeout_method_list_cells(null, $name, $selected_id, $submit_on_change, $special_option);
	echo "</tr>\n";
}

function so_users_list_cells($label, $name, $selected_id=null, $submit_on_change=false, $spec_opt=true)
{
    $where = false;
	$sql = " SELECT id, IF(real_name = '', user_id, real_name) FROM ".TB_PREF."users";

    if ($label != null)
        echo "<td>$label</td>\n";
    echo "<td>";

    echo combo_input($name, $selected_id, $sql, 'id', 'real_name',
        array(
            'spec_option' => $spec_opt===true ?_("All users") : $spec_opt,
            'spec_id' => '',
            'order' => 'real_name',
            'select_submit'=> $submit_on_change,
            'async' => false
        ) );
    echo "</td>";

}

function so_static_qr($qr_data){
  global $path_to_root;

  $file = $path_to_root.'/tmp/'.uniqid().'so.png';
  QRcode::png( $qr_data, $file, QR_ECLEVEL_M, 3 );

  echo '<center><img id="invoicePic" src="'.$file.'"  /></center>';
}
