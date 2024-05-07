<?php

$path_to_root = '../../..';
$path_to_fa = $path_to_root.'/../../..';
$path_to_lnbits = '..';

ini_set('log_errors', 1);
ini_set('error_log', $path_to_fa.'/tmp/strikeout.log');

require_once $path_to_root.'/inc/strikeout.php';
require_once $path_to_lnbits.'/inc/lnbits.php';

if (!isset($_GET['co'])){
  echo 'ERROR: Company not set';
  error_log('ERROR: Company not set for strike webhook');
  exit;
}

$db = company_select($_GET['co']);
$row = get_so_method('strikeout');
$strikeout = array(
  'tmp_dir' => $row['so_custom_2'], 
  'payee_name' => get_coy_name(),
  'action_url' => $row['so_custom_3'],
  'timezone' => $row['so_custom_1'],
  'password' => 'na' 
);

$row = get_so_method('lnbits');

$lnbits = array(
  'lnbits_url' => $row['so_url'],
  'read_key' => $row['so_pub'],
  'webhook_url' => $row['so_custom_1'], 
  'bits' => filter_var($row['so_custom_2'], FILTER_VALIDATE_BOOLEAN) , 
);

$fa = array(
  'user_login' => $row['so_user'],
  'bank_acct_name' => $row['so_bank'],
  'debit_acct' =>$row['so_debit'],
  'credit_acct' => $row['so_credit'],
  'fee_acct' => $row['so_fee'],
  'timezone' => $strikeout['timezone'],
  'acct_option' => $row['so_option'],  
);

date_default_timezone_set( $strikeout['timezone'] );

//no way to verify the message?, Checking the payment ourselves
$webhook_data = json_decode(file_get_contents("php://input"), true);
$invoice_info = check_lnbits_invoice($webhook_data['payment_hash']);

if ($invoice_info['paid'] == true){
  // code ran when invoice is paid 
  
  // lnbits values return in millisats hence 1000
  $denom = 1000; // this value gives sats
  if ($lnbits['bits'])
    $denom = $denom * 100; // this value gives bits

  $method = 'lnbits'; 
  $memo = json_decode($invoice_info['details']['memo'], true);
  $plugin_payload = array(
    'Date' => date('Y-m-d'),
    'Reference' => $memo['Ref'],
    'Correlation ID' => $invoice_info['details']['payment_hash'],
    'Amount' => $memo['Amount'],
    'Fee' => '0.00',
    'Net' => $memo['Amount'],
    'Currency' => 'USD',
    'State' => 'PAID',
    'Item Received' => 'BTC',
    'Quantity' => $invoice_info['details']['amount']/$denom,
    'Rate' => round($memo['Amount']/($invoice_info['details']['amount']/$denom), 10),
    'Location' => $invoice_info['details']['wallet_id'],
    'Invoice ID' => $invoice_info['details']['bolt11'],
    'Description' => $memo['Memo'],
  );

    if ($row['so_hook_inactive'] == '0' )
      include $path_to_root.'/webhooks/frontaccounting.php';
}
?>
