<?php 

$path_to_root = '../../../..';
$path_to_strike = '../..';
$path_to_fa = $path_to_root."/../../..";

ini_set('log_errors', 1);
ini_set('error_log', $path_to_fa.'/tmp/strikeout.log');

require $path_to_root.'/inc/strikeout.php';
require $path_to_strike.'/inc/strike.php';

$db = company_select($_GET['co']);
$row = get_so_method('strike');
$strike = array(
  'api_key' => $row['so_pub'], 
  'secret' => $row['so_pri'] 
);

header('Content-Type: text/event-stream'); 
header('Cache-Control: no-cache');

$invoice_status = find_strike_invoice( $_GET['invoiceId'] );

echo "data:".$invoice_status['state']."\n\n"; 

flush();
?>
