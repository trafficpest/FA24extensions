<?php

//  This include requires $path_to_fa to be set

//Use url variables first if available
if ( isset($_GET['co']) ){ $_POST['co'] = $_GET['co']; }
if ( isset($_GET['amount']) ){ $_POST['amount'] = $_GET['amount']; }
if ( isset($_GET['name']) ) {$_POST['name'] = $_GET['name']; }
if ( isset($_GET['custId']) ) {$_POST['custId'] = $_GET['custId']; }
if ( isset($_GET['action_url']) ) {$_POST['action_url'] = $_GET['action_url']; }

// error if company wasn't set
if (!isset( $_POST['co'] ) ){
  echo '<center><h2>Company was not set!</h2></center>';
  exit;
}

$db = company_select($_POST['co']);
$row = get_so_method('strikeout');
$strikeout = array(
  'tmp_dir' => $row['so_custom_2'], 
  'payee_name' => get_coy_name(),
  'action_url' => $row['so_custom_3'],
  'timezone' => $row['so_custom_1'],
  'password' => 'na' 
);

// Set Action Url from default config if not set
if (empty($_POST['action_url'])){$_POST['action_url']=$strikeout['action_url'];}

// error if amount wasn't set
if ( empty( $_POST['amount'] ) ){
  echo '<center><h2>No invoice amount was set!</h2></center>';
  exit;
}
$_POST['amount'] = number_format($_POST['amount'], 2, '.', ',');

// null if empty
if ( empty($_POST['name']) ){$_POST['name'] = 'Customer';}
if ( empty($_POST['custId']) ){$_POST['custId'] = 'No Reference';}

// remove old tmp image files
if ($strikeout['tmp_dir'])
  array_map('unlink', glob($path_to_fa.$strikeout['tmp_dir'].'/*so.png'));
?>
