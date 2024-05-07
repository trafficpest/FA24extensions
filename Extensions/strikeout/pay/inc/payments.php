<?php

function get_fa_open_balance($debtorno, $to)
{
  global $db, $path_to_fa;
  
  include_once $path_to_fa.'/includes/types.inc';
  
	//if($to)
		//$to = date2sql($to);
  $sql = "SELECT SUM(IF(t.type = ".ST_SALESINVOICE." OR 
    (t.type IN (".ST_JOURNAL." , ".ST_BANKPAYMENT.") AND t.ov_amount>0),
      -abs(IF(t.prep_amount, t.prep_amount, t.ov_amount + t.ov_gst + t.ov_freight 
      + t.ov_freight_tax + t.ov_discount)), 0)) AS charges,";

  $sql .= "SUM(IF(t.type != ".ST_SALESINVOICE." AND 
    NOT(t.type IN (".ST_JOURNAL." , ".ST_BANKPAYMENT.") AND t.ov_amount>0),
      abs(t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + 
      t.ov_discount) * -1, 0)) AS credits,";		

  $sql .= "SUM(IF(t.type != ".ST_SALESINVOICE." AND 
    NOT(t.type IN (".ST_JOURNAL." , ".ST_BANKPAYMENT.")), 
      t.alloc * -1, t.alloc)) AS Allocated,";

  $sql .=	"SUM(IF(t.type = ".ST_SALESINVOICE." OR 
    (t.type IN (".ST_JOURNAL." , ".ST_BANKPAYMENT.") AND 
      t.ov_amount>0), 1, -1) *
    (IF(t.prep_amount, t.prep_amount, abs(t.ov_amount + t.ov_gst + 
      t.ov_freight + t.ov_freight_tax + t.ov_discount)) - abs(t.alloc))) 
      AS OutStanding
		FROM ".$db['tbpref']."debtor_trans t
    WHERE t.debtor_no = ".db_escape($debtorno)." AND 
      t.type <> ".ST_CUSTDELIVERY;
   // if ($to)
    //	$sql .= " AND t.tran_date < '$to'";
	$sql .= " GROUP BY debtor_no";

    $result = get_sql_data($sql);
    return $result;
}

function get_fa_fiscal_year(){
  // find the current fiscal year id
  global $db;
  $sql = "SELECT * FROM `".$db['tbpref']."fiscal_year` WHERE `begin` <= '"
    .date('Y-m-d')."' AND `end` >= '".date('Y-m-d')."'";
  $fiscalyear_row = get_sql_data( $sql );
  
  if (empty($fiscalyear_row[0]['id'])){
    //if new year hasnt been opened try to open it
    $sql = "INSERT INTO `".$db['tbpref']."fiscal_year` (`id`, `begin`, "
      ."`end`, `closed`) VALUES (NULL, '".date('Y')."-01-01', "
      ."'".date('Y')."-12-31', '0')";

    error_log($sql);
    post_sql_data($sql);

    $sql = "SELECT * FROM `".$db['tbpref']."fiscal_year` WHERE `begin` <= '"
      .date('Y-m-d')."' AND `end` >= '".date('Y-m-d')."'";
    $fiscalyear_row = get_sql_data( $sql );
    if (empty($fiscalyear_row[0]['id'])){
      error_log("Couldn't get new fiscal year:".$sql);
      exit;
    }
  }
  return $fiscalyear_row; 
}

function get_fa_debtor_by_taxid($tax_id){
  // find the client in debtor_master table
  global $db;
  $sql = "SELECT * FROM `".$db['tbpref']."debtors_master` WHERE "
    ."`tax_id` = ".db_escape($tax_id);
  $debtor_row = get_sql_data( $sql );
  return $debtor_row; 
}

function get_fa_debtor_by_debtor($debtor_no){
  // find the client in debtor_master table
  global $db;
  $sql = "SELECT * FROM `".$db['tbpref']."debtors_master` WHERE "
    ."`debtor_no` = ".db_escape($debtor_no);
  $debtor_row = get_sql_data( $sql );
  return $debtor_row; 
}

function get_fa_invoice_by_no($invoice_no){
  // invoice_no is reference in db
  global $db;
  $sql = "SELECT * FROM `".$db['tbpref']."debtor_trans` WHERE "
    ."`reference` = ".db_escape($invoice_no)." AND "
    ."`type` = 10 ORDER BY `trans_no` DESC";
  $invoice_row = get_sql_data( $sql );
  return $invoice_row; 
}


function get_fa_branch_by_debtor($debtor_row){
  // find a branch_row for the client
  global $db;
  $sql = 'SELECT * FROM `'.$db['tbpref'].'cust_branch` WHERE `debtor_no`='
    .$debtor_row[0]['debtor_no'].' ORDER BY `branch_code` ASC Limit 1';
  $branch_row = get_sql_data( $sql ); 
  return $branch_row;
}

function get_last_debtor_trans(){
  // get last payment trans_no used
  global $db;
  $sql =  "SELECT * FROM `".$db['tbpref']."debtor_trans` WHERE `type` = 12 "
    ."ORDER BY `trans_no` DESC Limit 1";
  $debttran_row = get_sql_data( $sql );
  return $debttran_row;
}

function get_last_bank_trans(){
  // get last payment trans_no used
  global $db;
  $sql =  "SELECT * FROM `".$db['tbpref']."bank_trans` WHERE `type` = 2 "
    ."ORDER BY `trans_no` DESC Limit 1";
  $banktran_row = get_sql_data( $sql );
  return $banktran_row;
}

function get_last_inv_adjust(){
  // get last payment trans_no used
  global $db;
  $sql =  "SELECT * FROM `".$db['tbpref']."stock_moves` WHERE `type` = 17 "
    ."ORDER BY `trans_no` DESC Limit 1";
  $invtran_row = get_sql_data( $sql );
  return $invtran_row;
}

function insert_debtor_trans($data){
  global $db;
// insert debtor trans
  $sql = "INSERT INTO `".$db['tbpref']."debtor_trans` (`trans_no`, "
    ."`type`, `version`, `debtor_no`, `branch_code`, `tran_date`, "
    ."`due_date`, `reference`, `tpe`, `order_`, `ov_amount`, `ov_gst`, "
    ."`ov_freight`, `ov_freight_tax`, `ov_discount`, `alloc`, `prep_amount`, "
    ."`rate`, `ship_via`, `dimension_id`, `dimension2_id`, `payment_terms`, "
    ."`tax_included`) "
    ."VALUES (".db_escape($data['trans_no']).", ".db_escape($data['type']).", "
    ."'0', ".db_escape($data['debtor_no']).", "
    .db_escape($data['branch_code']).", "
    .db_escape(date('Y-m-d')).", '0000-00-00', ".db_escape($data['ref']).", "
    ."'0', '0', ".db_escape($data['amount']).", '0', '0', '0', '0', '0', "
    ."'0', '1', '0', '0', '0', NULL, '0')";

  error_log($sql);
  post_sql_data($sql);
}

function insert_trans_ref($data){
  global $db;

  $sql = "INSERT INTO `".$db['tbpref']."refs` (`id`, `type`, `reference`) "
    ."VALUES (".db_escape($data['trans_no']).", ".db_escape($data['type']).", "
    .db_escape($data['ref']).")";

  error_log($sql);
  post_sql_data($sql);
  

}
function insert_audit_trail($data){
  global $db,$fa;

  $sql = "INSERT INTO `".$db['tbpref']."audit_trail` (`id`, `type`, "
    ."`trans_no`, `user`, `stamp`, `description`, `fiscal_year`, `gl_date`, "
    ."`gl_seq`) "
    ."VALUES (NULL, ".db_escape($data['type']).", "
    .db_escape($data['trans_no']).", "
    .db_escape($fa['user_login']).", CURRENT_TIMESTAMP, NULL, "
    .db_escape($data['fiscal_year']).", "
    .db_escape(date('Y-m-d')).", '0')";

  error_log($sql);
  post_sql_data($sql);
}
function insert_bank_deposit($data, $payload){ 
 global $db, $fa; 

 if (isset($payload['Item Received'])){
   // deposit foreign currency
   $payload['Net'] = $payload['Quantity'];
 }

  $sql = "INSERT INTO `".$db['tbpref']."bank_trans` (`id`, `type`, "
    ."`trans_no`, `bank_act`, `ref`, `trans_date`, `amount`, `dimension_id`, "
    ."`dimension2_id`, `person_type_id`, `person_id`, `reconciled`) "
    ."VALUES (NULL, ".db_escape($data['type']).", "
    .db_escape($data['trans_no']).", ".db_escape($fa['bank_acct_name']).", "
    .db_escape($data['ref']).", ".db_escape(date('Y-m-d')).", "
    .db_escape($payload['Net']).", '0', '0', "
    .db_escape($data['person_type_id']).", "
    .db_escape($data['person_id']).", NULL)";

  error_log($sql);
  post_sql_data($sql);
}

function insert_ledger_debit($data, $amount, $account){
  global $db, $fa;

  $sql = "INSERT INTO `".$db['tbpref']."gl_trans` (`counter`, `type`, "
    ."`type_no`, `tran_date`, `account`, `memo_`, `amount`, `dimension_id`, "
    ."`dimension2_id`, `person_type_id`, `person_id`) "
    ."VALUES (NULL, ".db_escape($data['type']).", "
    .db_escape($data['trans_no']).", ".db_escape(date('Y-m-d')).", "
    .db_escape($account).", ".db_escape($data['memo_']).", "
    .db_escape($amount).", '0', '0', NULL, NULL)";

  error_log($sql);
  post_sql_data($sql);
}

function insert_ledger_credit($data){
  global $db, $fa;

  $sql = "INSERT INTO `".$db['tbpref']."gl_trans` (`counter`, `type`, "
    ."`type_no`, `tran_date`, `account`, `memo_`, `amount`, `dimension_id`, "
    ."`dimension2_id`, `person_type_id`, `person_id`) "
    ."VALUES (NULL, ".db_escape($data['type']).", "
    .db_escape($data['trans_no']).", "
    .db_escape(date('Y-m-d')).", ".db_escape($fa['credit_acct']).", "
    .db_escape($data['memo_']).", "
    .db_escape((-1*abs($data['amount']))).", '0', '0', "
    .db_escape($data['person_type_id']).", "
    .db_escape($data['debtor_no']).")";

  error_log($sql);
  post_sql_data($sql); 
}

function insert_deposit_credit($data){
  global $db, $fa;

  $sql = "INSERT INTO `".$db['tbpref']."gl_trans` (`counter`, `type`, "
    ."`type_no`, `tran_date`, `account`, `memo_`, `amount`, `dimension_id`, "
    ."`dimension2_id`, `person_type_id`, `person_id`) "
    ."VALUES (NULL, ".db_escape($data['type']).", "
    .db_escape($data['trans_no']).", "
    .db_escape(date('Y-m-d')).", ".db_escape($fa['credit_acct']).", "
    .db_escape($data['memo_']).", "
    .db_escape((-1*abs($data['amount']))).", '0', '0', NULL, NULL)";

  error_log($sql);
  post_sql_data($sql); 
}


function fa_payment_by_taxid($payload){
  global $fa, $db;

  date_default_timezone_set( $fa['timezone'] );

  // Get the data needed
  $fiscalyear_row = get_fa_fiscal_year();  
  $debtor_row = get_fa_debtor_by_taxid($payload['Reference']);
  
  // Check the data is there
  if (empty($debtor_row[0]['debtor_no'])){
    error_log("Couldn't find tax id ".$payload['Reference']." in the database");
    fa_bank_deposit($payload);
    return;
  }

  $branch_row = get_fa_branch_by_debtor($debtor_row);
  if (empty($branch_row[0]['branch_code'])){
    error_log("Couldn't find a branch for customer with tax id "
      .$payload['Reference']." in the database");
    fa_bank_deposit($payload);
    return;
  }

  $debttran_row = get_last_debtor_trans();
  if (empty($debttran_row[0]['trans_no'])){
    error_log("Warning: Couldn't find a prior customer payment in the database."
    ." will be 1");
  }
  // clean it up
  $data = array(
    'ref' => date('ymdHis'),
    'fiscal_year' => $fiscalyear_row[0]['id'],
    'debtor_no' => $debtor_row[0]['debtor_no'],
    'branch_code' => $branch_row[0]['branch_code'],
    'trans_no' => ($debttran_row[0]['trans_no']+1),
    'amount' => $payload['Amount'],
    'type' => '12',
    'person_id' => $debtor_row[0]['debtor_no'],
    'person_type_id' => '2',
    'memo_' => 'StrikeOut',
  );

  insert_debtor_trans($data);
  insert_trans_ref($data);
  insert_audit_trail($data);
  insert_bank_deposit($data, $payload);
  insert_ledger_debit($data, $payload['Net'], $fa['debit_acct']);
  insert_ledger_credit($data);
  if ($payload['Fee'] != 0){
    // Account fee if there was one
    insert_ledger_debit($data, $payload['Fee'], $fa['fee_acct']);
  }
}

function fa_payment_by_debtor($payload){
  global $fa, $db;

  date_default_timezone_set( $fa['timezone'] );

  // Get the data needed
  $fiscalyear_row = get_fa_fiscal_year();
  $debtor_row = get_fa_debtor_by_debtor($payload['Reference']);
  
  // Check the data is there
  if (empty($debtor_row[0]['debtor_no'])){
    error_log("Couldn't find debtor ".$payload['Reference']." in the database");
    fa_bank_deposit($payload);
    return;
  }

  $branch_row = get_fa_branch_by_debtor($debtor_row);
  if (empty($branch_row[0]['branch_code'])){
    error_log("Couldn't find a branch for customer with debtor no "
      .$payload['Reference']." in the database");
    fa_bank_deposit($payload);
    return;
  }
    
  $debttran_row = get_last_debtor_trans();
  if (empty($debttran_row[0]['trans_no'])){
    error_log("Warning: Couldn't find a prior customer payment in the database."
    ." will be 1");
  }
  // clean it up
  $data = array(
    'ref' => date('ymdHis'),
    'fiscal_year' => $fiscalyear_row[0]['id'],
    'debtor_no' => $debtor_row[0]['debtor_no'],
    'branch_code' => $branch_row[0]['branch_code'],
    'trans_no' => ($debttran_row[0]['trans_no']+1),
    'amount' => $payload['Amount'],
    'type' => '12',
    'person_id' => $debtor_row[0]['debtor_no'],
    'person_type_id' => '2',
    'memo_' => 'StrikeOut'
  );

  insert_debtor_trans($data);
  insert_trans_ref($data);
  insert_audit_trail($data);
  insert_bank_deposit($data, $payload);
  insert_ledger_debit($data, $payload['Net'], $fa['debit_acct']);
  insert_ledger_credit($data);
  if ($payload['Fee'] != 0){
    // Account fee if there was one
    insert_ledger_debit($data, $payload['Fee'], $fa['fee_acct']);
  }
}

function fa_payment_by_invoice($payload){
  global $fa, $db;

  date_default_timezone_set( $fa['timezone'] );

  // Get the data needed and verify its there
  $fiscalyear_row = get_fa_fiscal_year();
  $invoice_row = get_fa_invoice_by_no($payload['Reference']);
  
  if (empty($invoice_row[0]['debtor_no'])){
    error_log("Couldn't find invoice ".$payload['Reference']." in the database");
    fa_bank_deposit($payload);
    return;
  }

  $debtor_row = get_fa_debtor_by_debtor($invoice_row[0]['debtor_no']);
  if (empty($debtor_row[0]['debtor_no'])){
    error_log("Couldn't find debtor ".$debtor_no." in the database");
    fa_bank_deposit($payload);
    return;
  }

  $branch_row = get_fa_branch_by_debtor($debtor_row);
  if (empty($branch_row[0]['branch_code'])){
    error_log("Couldn't find a branch for customer with debtor no "
      .$debtor_no." in the database");
    fa_bank_deposit($payload);
    return;
  }

  $debttran_row = get_last_debtor_trans();
  if (empty($debttran_row[0]['trans_no'])){
    error_log("Warning: Couldn't find a prior customer payment in the database."
    ." will be 1");
  }

  // clean it up
  $data = array(
    'ref' => date('ymdHis'),
    'fiscal_year' => $fiscalyear_row[0]['id'],
    'debtor_no' => $debtor_row[0]['debtor_no'],
    'branch_code' => $branch_row[0]['branch_code'],
    'trans_no' => ($debttran_row[0]['trans_no']+1),
    'amount' => $payload['Amount'],
    'type' => '12',
    'person_id' => $debtor_row[0]['debtor_no'],
    'person_type_id' => '2',
    'memo_' => 'StrikeOut'
  );

  insert_debtor_trans($data);
  insert_trans_ref($data);
  insert_audit_trail($data);
  insert_bank_deposit($data, $payload);
  insert_ledger_debit($data, $payload['Net'], $fa['debit_acct']);
  insert_ledger_credit($data);
  if ($payload['Fee'] != 0){
    // Account fee if there was one
    insert_ledger_debit($data, $payload['Fee'], $fa['fee_acct']);
  }
}

function fa_bank_deposit($payload){
  global $fa, $db;

  date_default_timezone_set( $fa['timezone'] );

  // Get the data needed
  $fiscalyear_row = get_fa_fiscal_year();
  $banktran_row = get_last_bank_trans();

  // check data
  if (empty($banktran_row[0]['trans_no'])){
    error_log("Warning: Couldn't find a prior deposit in the database."
    ." will be 1");
  }

  // clean it up
  $data = array(
    'ref' => date('ymdHis'),
    'fiscal_year' => $fiscalyear_row[0]['id'],
    'debtor_no' => 'NULL', 
    'trans_no' => ($banktran_row[0]['trans_no']+1),
    'amount' => $payload['Amount'],
    'type' => '2',
    'person_id' => 'StrikeOut',
    'person_type_id' => '0',
    'memo_' => 'StrikeOut'
  );

  insert_audit_trail($data);
  insert_trans_ref($data);
  insert_bank_deposit($data, $payload);
  insert_ledger_debit($data, $payload['Net'], $fa['debit_acct']);
  insert_deposit_credit($data);
  if ($payload['Fee'] != 0){
    // Account fee if there was one
    insert_ledger_debit($data, $payload['Fee'], $fa['fee_acct']);
  }
}

?>
