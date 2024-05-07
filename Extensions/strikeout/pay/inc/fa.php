<?php

function get_fa_users(){
  global $db;
  $sql ='SELECT * FROM `'.$db['tbpref'].'users`';
  $sql_results = get_sql_data($sql);
  if ($sql_results[0] === 'ERROR' ||
      $sql_results === '0 results'){
    return $sql_results;
  }
   
  foreach ($sql_results as $result){
    $users[] = array(
      'id' => $result['id'],
      'user_id' => $result['user_id']
    );
  }
  return $users;
}

function get_fa_stock_master(){
  global $db;
  $sql ='SELECT * FROM `'.$db['tbpref'].'stock_master`';
  $sql_results = get_sql_data($sql);
  if ($sql_results[0] === 'ERROR' ||
      $sql_results === '0 results'){
    return $sql_results;
  }
   
  foreach ($sql_results as $result){
    $stock_master[] = array(
      'stock_id' => $result['stock_id'],
      'description' => $result['description']
    );
  }
  return $stock_master;
}

function get_fa_bank_accts(){
  global $db;
  $sql ='SELECT * FROM `'.$db['tbpref'].'bank_accounts`';
  $sql_results = get_sql_data($sql);
  if ($sql_results[0] === 'ERROR'){
    return $sql_results;
  }
  foreach ($sql_results as $result){
    $bank_accts[] = array(
      'id' => $result['id'],
      'account_code' => $result['account_code'],
      'bank_account_name' => $result['bank_account_name'],
      'bank_charge_act' => $result['bank_charge_act']
    );
  }
  return $bank_accts;
}

function get_fa_coa(){
  global $db;
  $sql ='SELECT * FROM `'.$db['tbpref'].'chart_master`';
  $sql_results = get_sql_data($sql);
  if ($sql_results[0] === 'ERROR'){
    return $sql_results;
  }
  
  foreach ($sql_results as $result){
    $coa[] = array (
    'account_code' => $result['account_code'],
    'account_name' => $result['account_name']
    );
  }
  return $coa;
}

function get_active_methods(){
  global $db;
  $sql ='SELECT * FROM `'.$db['tbpref'].'strikeout`';
  $sql_results = get_sql_data($sql);
  if ($sql_results[0] === 'ERROR'){
    return $sql_results;
  }

  $methods = array();
  foreach ($sql_results as $result){
    if ($result['so_inactive'] == 0 && $result['so_method'] != 'strikeout')
      $methods[$result['so_method']] = 'on';
  }
  return $methods;
}

function get_so_method($method){
  global $db;
  $sql ='SELECT * FROM `'.$db['tbpref'].'strikeout` '
    .'WHERE `so_method` = '.db_escape($method);
  $sql_results = get_sql_data($sql);
  return $sql_results[0];
}

function get_coy_name(){
  global $db;
  $sql ='SELECT * FROM `'.$db['tbpref'].'sys_prefs` '
    .'WHERE `name` = "coy_name"';
  $sql_results = get_sql_data($sql);
  return $sql_results[0]['value'];
}
