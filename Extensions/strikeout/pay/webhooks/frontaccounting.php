<?php

// check that config setting has been set for method first
foreach ($fa as $setting){
  if ($setting == ''){
    error_log('FA Config '.$setting.' was not set for '.$method.' had to exit');
    exit;
  }
}

if (isset ($plugin_payload['Amount']) ){
  if ($fa['acct_option'] == 'tax_id'){
    fa_payment_by_taxid($plugin_payload);
  }
  if ($fa['acct_option'] == 'debtor_no'){
    fa_payment_by_debtor($plugin_payload);
  }
  if ($fa['acct_option'] == 'invoice'){
    fa_payment_by_invoice($plugin_payload);
  }
  if ($fa['acct_option'] == 'bank_deposit'){
    fa_bank_deposit($plugin_payload);
  }
}
?> 
