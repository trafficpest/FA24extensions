<?php

$path_to_root = '../..';
$path_to_fa = $path_to_root.'/../../..';
$path_to_stripe = '.';


?>

<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?=$path_to_root?>/assets/css/checkout.css">
  </head>
  <body>
    <div id="strikeout-button-container">
  <?include $path_to_stripe.'/inc/ui/checkout-button.php'?>
    </div>
  </body>
</html>

