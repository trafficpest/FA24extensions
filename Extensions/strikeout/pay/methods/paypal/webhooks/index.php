<?php

$path_to_root = '../../..';
$path_to_fa = $path_to_root.'/../../..';
$path_to_pp = '..';

ini_set('log_errors', 1);
ini_set('error_log', $path_to_fa.'/tmp/strikeout.log');

require_once $path_to_root.'/inc/strikeout.php';
require_once $path_to_pp.'/inc/paypal.php';

if (!isset($_GET['co'])){
  echo 'ERROR: Company not set';
  error_log('ERROR: Company not set for PayPal webhook');
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

$row = get_so_method('paypal');
$paypal = array (
  'URL' => $row['so_url'],
  'CLIENT_ID' => $row['so_pub'],
  'APP_SECRET' => $row['so_pri'],
  'WEBHOOK_ID' => $row['so_custom_1'],
  'sdk_options' => $row['so_custom_2'],
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

if ( check_paypal_signature() ){
  $webhook_data = json_decode(file_get_contents("php://input"), true);
  
  if ($webhook_data['event_type'] == 'PAYMENT.CAPTURE.COMPLETED'){ 
    // code ran when invoice is updates
  
      $method = 'paypal'; 
      $plugin_payload = array(
        'Date' => date('Y-m-d'),
        'Reference' => $webhook_data['resource']['custom_id'],
        'Correlation ID' => $webhook_data['resource']['id'],
        'Amount' => $webhook_data['resource']['seller_receivable_breakdown']['gross_amount']['value'],
        'Fee' => $webhook_data['resource']['seller_receivable_breakdown']['paypal_fee']['value'],
        'Net' => $webhook_data['resource']['seller_receivable_breakdown']['net_amount']['value'],
        'Currency' => $webhook_data['resource']['seller_receivable_breakdown']['gross_amount']['currency_code'],
        'State' => $webhook_data['resource']['status'],
        'Invoice ID' => $webhook_data['resource']['supplementary_data']['related_ids']['order_id'],
        'Description' => $webhook_data['summary'],
      );

      if ($row['so_hook_inactive'] == 0 )
        include $path_to_root.'/webhooks/frontaccounting.php';
  }
}else{
  error_log('The PayPal webhook signature didnt pass. If your webhook id is set'
    .' correctly then you received a false message');
}

?>
