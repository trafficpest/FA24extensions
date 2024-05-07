<?php 

$path_to_root = '../../../..';
$path_to_lnbits = '../..';
$path_to_fa = $path_to_root."/../../..";

ini_set('log_errors', 1);
ini_set('error_log', $path_to_fa.'/tmp/strikeout.log');

require $path_to_root.'/inc/strikeout.php';
require $path_to_lnbits.'/inc/lnbits.php';

// error if company wasn't set
if (!isset( $_GET['co'] ) ){
  error_log('Company was not set when checking lnbits pay status');
  exit;
}

$db = company_select($_GET['co']);
$row = get_so_method('lnbits');
$lnbits = array(
  'lnbits_url' => $row['so_url'],
  'read_key' => $row['so_pub'],
  //'webhook_url' => 'need to setup', 
);

header('Content-Type: text/event-stream'); 
header('Cache-Control: no-cache');

$invoice_info = check_lnbits_invoice($_GET['invoiceId']);

if ( isset($invoice_info['paid']) ){
  if ($invoice_info['paid'] == true){
    echo "data:".'PAID'."\n\n"; 
  }
}

flush();

?>

