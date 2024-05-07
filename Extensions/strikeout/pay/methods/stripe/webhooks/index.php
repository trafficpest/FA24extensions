<?php
$path_to_root = '../../..';
$path_to_fa = $path_to_root.'/../../..';
$path_to_stripe = '..';

ini_set('log_errors', 1);
ini_set('error_log', $path_to_fa.'/tmp/strikeout.log');

require_once $path_to_root.'/inc/strikeout.php';
require_once $path_to_stripe.'/inc/stripe.php';

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

$row = get_so_method('stripe');
$stripe = array (
  'URL' => $row['so_url'],
  'PUB_KEY' => $row['so_pub'],
  'PRI_KEY' => $row['so_pri'],
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

$webhook_data = json_decode(file_get_contents("php://input"), true);

// Check if payment is made
if ($webhook_data['type'] == 'charge.succeeded'){

 // check yourself if transaction is valid with stripe 
  $tx_data = get_stripe_transaction(
    $webhook_data['data']['object']['balance_transaction']);
  if ($tx_data['source'] == $webhook_data['data']['object']['id']){
    
    // Looks good proceed to record payment
    $method = 'stripe';
    if ($webhook_data['data']['object']['paid'] == 'true')
      {$pay_state = 'PAID';} else {$pay_state = 'UNPAID';}

    $plugin_payload = array(
      'Date' => date('Y-m-d'),
      'Reference' => $webhook_data['data']['object']['metadata']['custId'],
      'Correlation ID' => $webhook_data['data']['object']['id'],
      'Amount' => $tx_data['amount']/100,
      'Fee' => $tx_data['fee']/100,
      'Net' => $tx_data['net']/100,
      'Currency' => strtoupper($webhook_data['data']['object']['currency']),
      'State' => $pay_state,
      'Invoice ID' => $webhook_data['data']['object']['balance_transaction'],
      'Description' => 'Payment from '
        .$webhook_data['data']['object']['billing_details']['email'],
    );

      if ($row['so_hook_inactive'] == 0 )
        include $path_to_root.'/webhooks/frontaccounting.php';

  }else{
    error_log('The Stripe webhook verification didnt pass. You could be receiving'
      .' a false message');
  }
}

?>
