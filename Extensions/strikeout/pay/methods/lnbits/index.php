<?php

$path_to_fa="../../../../..";
$path_to_root="../..";
$path_to_lnbits=".";

ini_set('error_log', $path_to_fa.'/tmp/strikeout.log');

require_once $path_to_root.'/inc/strikeout.php';
require_once $path_to_lnbits.'/inc/lnbits.php';
require_once $path_to_root.'/inc/phpqrcode/qrlib.php';

include_once $path_to_root.'/inc/invoice_setup.php';

$row = get_so_method('lnbits');
$lnbits = array(
  'lnbits_url' => $row['so_url'],
  'read_key' => $row['so_pub'],
  'webhook_url' => $row['so_custom_1'], 
);


$lnbits_invoice = issue_lnbits_invoice( 
  $_POST['amount'], 
  $_POST['name'],
  $_POST['custId'] 
);

// error if strike didnt return a invoice
if ( !isset( $lnbits_invoice['payment_request'] ) ){
  echo "
    <center><h2>There was an error</h2>
    <p>Bitcoin Lightning invoice was not generated<br>
    Check config is set correctly and post data was valid<br>
    The response given was:<br><br></p></center>";

  print("<pre>".print_r($lnbits_invoice,true)."</pre>");
  exit;
}
$invoice_info = check_lnbits_invoice($lnbits_invoice['payment_hash']);
//print_r($invoice_info);exit;

$file = $path_to_fa.$strikeout['tmp_dir'].'/'.uniqid().'so.png';
QRcode::png( $lnbits_invoice['payment_request'], $file, QR_ECLEVEL_M, 3 );

$memo = json_decode($invoice_info['details']['memo'], true);
?>

<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="./assets/css/style.css">
</head>

<body>
<div class="inv-container">
  <div class="strikeInvoice">

  <img id="bitcoin_logo" src="./assets/images/btc-lightning.png">
  <h3 id="title">Lightning Invoice</h3>

  <div class="description"><?=$memo['Memo']?></div>
  <div class="amount"><?=$invoice_info['details']['amount']/1000?> Sats</div>
  <div class="qrcode">
    <img id="invoicePic" src="<?=$file?>"  />
  </div>
  <div id="result">
    <div id="expirationLine">Expires in 
    <div id="secondsLeft"><?=$invoice_info['details']['expiry']-$invoice_info['details']['time']?></div>
       seconds</div>
    <div class="copyInvoice">
      <input 
        type="text"
        value="<?=$lnbits_invoice['payment_request']?>"
        readonly>
      <button
        onclick="copyInvoice()" 
        type="button" 
        value="<?=$lnbits_invoice['payment_request']?>" 
        id="lnInvoice">Copy
      </button>
    </div>
      <button
        onclick="location.href='lightning:<?=$lnbits_invoice['payment_request']?>';"
        type="button"
        id="openWallet">Open Wallet
        </button> 
    </div>
  </div>
  <p>Pay with any lightning wallet. Made possible with 
  <a href="https://lnbits.com">LNbits</a>.</p>
  <p><strong>Powered by 
  <a href="https://github.com/trafficpest/strikeout">Strikeout</a>
  </strong></p>
</div>

<script>
  var company = "<?=$_POST['co']?>";
  var path_to_lnbits = "<?=$path_to_lnbits?>";
  var action_url = "<?=$_POST['action_url']?>";
  var invoiceId = "<?=$lnbits_invoice['payment_hash']?>";
  var seconds = <?=$invoice_info['details']['expiry']-$invoice_info['details']['time']?>;
</script>
<script src="<?=$path_to_lnbits.'/assets/js/invoice.js'?>"></script>
<script src="<?=$path_to_lnbits.'/assets/js/copyInvoice.js'?>"></script>

</body>
</html>
