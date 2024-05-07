<?php

$path_to_root = '../../..';
$path_to_fa = $path_to_root.'/../../..';
$path_to_strike = '..';

ini_set('log_errors', 1);
ini_set('error_log', $path_to_fa.'/tmp/strikeout.log');

require_once $path_to_root.'/inc/strikeout.php';
require_once $path_to_strike.'/inc/strike.php';

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

$row = get_so_method('strike');

$strike = array(
  'api_key' => $row['so_pub'], 
  'secret' => $row['so_pri'] 
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

//verify the message is signed with your secret
if (check_strike_signature()){

  $webhook_data = json_decode(file_get_contents("php://input"), true);
  
  if ($webhook_data['eventType'] == 'invoice.updated'){
    // code ran when invoice is updates
  
    $strike_invoice = find_strike_invoice( $webhook_data['data']['entityId'] );
    if ($strike_invoice['state'] == 'PAID'){
      // if invoice update was a payment
      $method = 'strike'; 
      $correlation = explode('|', $strike_invoice['correlationId']);
      $plugin_payload = array(
        'Date' => date('Y-m-d'),
        'Reference' => $correlation[0],
        'Correlation ID' => $correlation[1],
        'Amount' => $strike_invoice['amount']['amount'],
        'Fee' => '0.00',
        'Net' => $strike_invoice['amount']['amount'],
        'Currency' => $strike_invoice['amount']['currency'],
        'State' => $strike_invoice['state'],
        'Invoice ID' => $strike_invoice['invoiceId'],
        'Description' => $strike_invoice['description'],
      );

        if ($row['so_hook_inactive'] == 0 )
          include $path_to_root.'/webhooks/frontaccounting.php';
      }
    }

  if ($webhook_data['eventType'] == 'invoice.created'){
  // Add code here for invoice created events 

  }
}
  
?>
