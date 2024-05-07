<?php

function company_select($company)
{
  global $db_connections;
  
  if (isset($db_connections[$company]['dbname']))
  {
  $db = $db_connections[$company];
	return $db;
  }
  echo "Error: Trying to access db that doesn't exist : ".$company;
  error_log("Error: Trying to access db that doesn't exist : ".$company);
  exit;
}

function db_escape($value = "", $nullify = false)
{
	global $db;

	//$value = @html_entity_decode($value, ENT_QUOTES, $_SESSION['language']->encoding=='iso-8859-2' ? 'ISO-8859-1' : $_SESSION['language']->encoding);
	$value = htmlspecialchars($value, ENT_NOQUOTES, "UTF-8");
 
  	//reset default if second parameter is skipped
	$nullify = ($nullify === null) ? (false) : ($nullify);

  	//check for null/unset/empty strings
	if ((!isset($value)) || (is_null($value)) || ($value === "")) {
		$value = ($nullify) ? ("NULL") : ("''");
	} else {
    if (is_string($value)) 
    {
      // Create connection
      $conn = new mysqli( 
        $db['host'], $db['dbuser'], $db['dbpassword'], $db['dbname'],
        !empty($db["port"]) ? $db["port"] : 3306); // default port in mysql is 3306
     
      // Check connection  
      if ($conn->connect_error) {
        $error[] = 'ERROR';
        $error[] = $conn->connect_error ;
        return $error;
      }
          $value = "'" . mysqli_real_escape_string($conn, $value) . "'";
              //value is a string and should be quoted; 
      $conn->close();
		} else if (!is_numeric($value)) {
			//value is not a string nor numeric
			error_log("ERROR: incorrect data type send to sql query");
			echo 'ERROR: incorrect data type send to sql query';
			exit();
		}
	}
	return $value;
}

function get_sql_data( $sqlquery ){
  global $db;

  // Create connection
  $conn = new mysqli( 
    $db['host'], $db['dbuser'], $db['dbpassword'], $db['dbname'],
    !empty($db["port"]) ? $db["port"] : 3306); // default port in mysql is 3306
 
  // Check connection  
  if ($conn->connect_error) {
    $error[] = 'ERROR';
    $error[] = $conn->connect_error ;
    return $error;
  }

  $result = $conn->query( $sqlquery );
  
  if ($result->num_rows > 0) {

  // output data of each row
    for ($sqlreturned = array (); 
    $row = $result->fetch_assoc(); 
    $sqlreturned[] = $row);
    return $sqlreturned;
  } else {

    $sqlreturned = "0 results";
    return $sqlreturned;
  }
  
  $conn->close();
}

function post_sql_data( $sqlpost ){
  global $db;

  // Create connection
  $conn = new mysqli( 
    $db['host'], $db['dbuser'], $db['dbpassword'], $db['dbname'],
    !empty($db["port"]) ? $db["port"] : 3306); // default port in mysql is 3306
  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }
  if ( $conn->query( $sqlpost ) === TRUE) {
  } else {
    error_log("SQL Error: " . $sqlpost);
    error_log(json_encode($conn->error));
  }
  $conn->close();
}
