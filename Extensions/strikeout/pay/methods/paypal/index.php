<?php

$path_to_root = '../..';
$path_to_fa="../../../../..";
$path_to_pp = '.';

require_once $path_to_root.'/inc/strikeout.php';
require_once $path_to_pp.'/inc/paypal.php';

include_once $path_to_root.'/inc/invoice_setup.php';

$row = get_so_method('paypal');
$paypal = array (
  'URL' => $row['so_url'],
  'CLIENT_ID' => $row['so_pub'],
  'APP_SECRET' => $row['so_pri'],
  'WEBHOOK_ID' => $row['so_custom_1'],
  'sdk_options' => $row['so_custom_2'],
);

?>
<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?=$path_to_root?>/assets/css/checkout.css">
  </head>
  <body>
    <div id="strikeout-button-container">
  <?include $path_to_pp.'/inc/ui/checkout-button.php'?>
    </div>
  </body>
</html>

