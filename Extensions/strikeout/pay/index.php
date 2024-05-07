<?php

$path_to_root = '.';
$path_to_fa = '../../..';

ini_set('error_log', $path_to_fa.'/tmp/strikeout.log');

include_once $path_to_root.'/inc/strikeout.php';
include_once $path_to_root.'/inc/invoice_setup.php';

?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?=$path_to_root?>/assets/css/checkout.css">
  </head>
  <body>
  <div id="strikeout-button-container">
  <div id="strikeout-balance">
<?php
if (isset($_GET['custId']))
  {
    $invoice_row = get_fa_invoice_by_no($_GET['custId']);
    $bal = get_fa_open_balance($invoice_row[0]['debtor_no'], null);
    echo "Customer Balance: ".number_format($bal[0]['OutStanding'],2);
  }
?>
  </div>
  <div id="strikeout-amount">
  <form action=".">
    <input type="hidden" name="co" value="<?=$_POST['co']?>">
    <input type="hidden" name="name" value="<?=$_POST['name']?>">
    <input type="hidden" name="custId" value="<?=$_POST['custId']?>">
    Amount Paying: 
    <input type="number" name="amount" value="<?=$_POST['amount']?>" min=".01" step=".01">
    <input type="submit" value="Update">
  </form>
  </div>
  <h3>Select your payment method</h3>
<?php 
$active_methods = get_active_methods();

foreach($active_methods as $active_method => $status) {
  if ($status == 'on'){
    include $path_to_root.'/methods/'.$active_method.'/inc/ui/checkout-button.php';
  }
}

?>
</div>
  </body>
</html>

