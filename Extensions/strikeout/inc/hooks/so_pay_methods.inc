<?php

function get_method_webhooks($so_id){

  if ($so_id == 2 && isset($_POST['so_pub']))
    return get_strike_subscriptions();
  if ($so_id == 4 && isset($_POST['so_url']))
    return list_pp_webhooks();
  if ($so_id == 5 && isset($_POST['so_pri']))
    return list_stripe_webhooks();
}

function delete_method_webhooks($so_id, $webhook_id){

  if ($so_id == 2){
    display_notification('Strike webhook: '.$webhook_id.' removed');
    return delete_strike_subscriptions($webhook_id);
  }
  if ($so_id == 4){
    display_notification('PayPal webhook: '.$webhook_id.' removed');
    return delete_pp_webhook($webhook_id);
  }
  if ($so_id == 5){
    display_notification('Stripe webhook: '.$webhook_id.' removed');
    return delete_stripe_webhook($webhook_id);
  }
}

function create_method_webhooks($so_id, $webhook_url){

  if ($so_id == 2){
    display_notification('Strike webhook: '.$webhook_url.' created');
    return create_strike_subscription($webhook_url);
  }
  if ($so_id == 3){
    display_notification('LNbits Webhooks Need to be placed in "Webhook URL"');
    return;
  }
  if ($so_id == 4){
    display_notification('PayPal webhook: '.$webhook_url.' created');
    display_notification('Copy the "ID" to "Webhook ID" field and update');
    return create_pp_webhook($webhook_url);
  }
  if ($so_id == 5){
    display_notification('Stripe webhook: '.$webhook_url.' created');
    return create_stripe_webhook($webhook_url);
  }
}

function get_strike_subscriptions(){
  
  $url = 'https://api.strike.me/v1/subscriptions/';
  $ch = curl_init($url);

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'accept: application/json',
    'Authorization: Bearer '.$_POST['so_pub']
  ));

  $response = curl_exec($ch);
  curl_close($ch);

  $response = json_decode($response , true);
  
  if (isset($response[0]))
    return $response[0];
  else 
    return $response;

}

function generate_pp_access_token() {

  $post_data = 'grant_type=client_credentials';

  $auth = base64_encode($_POST['so_pub'].':'.$_POST['so_pri']);

  $url = $_POST['so_url'].'/v1/oauth2/token';
  $ch = curl_init($url);

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Basic '.$auth,
  ));

  $response = curl_exec($ch);
  curl_close($ch);

  $response = json_decode($response , true);

  return $response['access_token'];

}

function list_pp_webhooks(){
  $access_token = generate_pp_access_token();

  $url = $_POST['so_url'].'/v1/notifications/webhooks';

  $ch = curl_init($url);

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Authorization: Bearer '.$access_token,
  ));

  $response = curl_exec($ch);
  curl_close($ch);

  $response = json_decode($response , true);

  if (isset($response['webhooks'][0])){
    $webhook = array(
      'id' => $response['webhooks'][0]['id'],
      'webhookUrl' => $response['webhooks'][0]['url'],
    );
    return $webhook;
  }
  else 
    return null;

}


function list_stripe_webhooks(){

  $url = 'https://api.stripe.com/v1/webhook_endpoints';

  $ch = curl_init($url);

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer '.$_POST['so_pri'],
  ));

  $response = curl_exec($ch);
  curl_close($ch);

  $response = json_decode($response , true);
  if (isset($response['data'][0]['id'])){
    $webhook = array(
      'id' => $response['data'][0]['id'],
      'webhookUrl' => $response['data'][0]['url'],
    );
    return $webhook;
  }
  else 
    return null;
}

function create_pp_webhook($webhook_url){

  $post_data = array(
    'url' => $webhook_url,
    'event_types' => array(
      array(
        'name' => '*',
      ),
    ),
  );

  $json = json_encode($post_data);

  $access_token = generate_pp_access_token();

  $url = $_POST['so_url'].'/v1/notifications/webhooks';

  $ch = curl_init($url);

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Authorization: Bearer '.$access_token,
  ));

  $response = curl_exec($ch);
  curl_close($ch);

  $response = json_decode($response , true);

  return $response;
}

function create_stripe_webhook($webhook_url){

  $post_data = [
    'url' => $webhook_url,
    'enabled_events' => ['*'],
    ];
  $query = http_build_query($post_data);
  $url = 'https://api.stripe.com/v1/webhook_endpoints';

  $ch = curl_init($url);

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer '.$_POST['so_pri'],
    'Content-Type: application/x-www-form-urlencoded',
  ));

  $response = curl_exec($ch);
  curl_close($ch);

  $response = json_decode($response , true);

  return $response;
}

function delete_pp_webhook($webhook_id){

  $access_token = generate_pp_access_token();

  $url = $_POST['so_url'].'/v1/notifications/webhooks/'
    .$webhook_id;

  $ch = curl_init($url);

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Authorization: Bearer '.$access_token,
  ));

  $response = curl_exec($ch);
  curl_close($ch);

  $response = json_decode($response , true);

  return $response;
}

function delete_strike_subscriptions($subscriptionId){

  $url = 'https://api.strike.me/v1/subscriptions/'.$subscriptionId;
  $ch = curl_init($url);

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer '.$_POST['so_pub']
  ));

  $response = curl_exec($ch);
  curl_close($ch);

  $response = json_decode($response , true);

  return $response;

}

function delete_stripe_webhook($webhook_id){

  $url = 'https://api.stripe.com/v1/webhook_endpoints/'
    .$webhook_id;

  $ch = curl_init($url);

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer '.$_POST['so_pri'],
  ));

  $response = curl_exec($ch);
  curl_close($ch);

  $response = json_decode($response , true);

  return $response;
}



function create_strike_subscription( $url ){

  $post_data = array(
    'webhookUrl' => $url,
    'webhookVersion' => 'v1',
    'secret' => $_POST['so_pri'],
    'enabled' => true,
    'eventTypes' => array(
      'invoice.created',
      'invoice.updated'
    )
  );

  $json = json_encode($post_data);

  $url = 'https://api.strike.me/v1/subscriptions';
  $ch = curl_init($url);

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'accept: application/json',
    'Content-Type: application/json',
    'Authorization: Bearer '.$_POST['so_pub']
  ));

  $response = curl_exec($ch);
  curl_close($ch);

  $response = json_decode($response , true);

  return $response;
}

