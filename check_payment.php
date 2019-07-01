<?php
function getAccessToken($consumer_key, $consumer_secret){
    $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';//access toekn request url
   $keys_separater=":";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    $credentials = base64_encode($consumer_key.$keys_separater.$consumer_secret);// a base64 
    //encoding of consumer secret and consumer key separated by :
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization: Basic '.$credentials)); //setting a custom header
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);					
    $curl_response = curl_exec($curl);
    $data = json_decode($curl_response, true);
    $accessToken= $data['access_token'];
    return $accessToken;
}

function getPassword($Shortcode, $Passkey='bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'){
    //be found here : https://developer.safaricom.co.ke/test_credentials
    if(empty($Shortcode)){
        return "System_detected_empty_parameter";
        exit();  
    }else{
    $Timestamp = date('Ymdhis');//timestamp 
    $Password = base64_encode($Shortcode.$Passkey.$Timestamp);
    return $Password;
    }
}

function prepareGetData($Shortcode, $Password){
        $Timestamp = date('Ymdhis');   //timestamp
    $curl_post_data = array(
        "BusinessShortCode" => $Shortcode,//business receiving payment, paybill number
        "Password" => $Password,    //a base 64 encode of shortcode, passkey and timestamp
        "Timestamp" => $Timestamp,     //time in Ymdhis formart
        "CheckoutRequestID" => 'ws_CO_DMZ_465980283_03052019143253874'
    );
    return $curl_post_data;
}

function PayRequest($curl_post_data, $accessToken){
$url = 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query';    
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization:Bearer '.$accessToken)); //setting custom header
$data_string = json_encode($curl_post_data);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

$curl_response = curl_exec($curl);
$res = json_decode($curl_response);
    
    return $res;
}

define("MPESA_KEY", "KZJuLdQ2PGj1A5rxmxTrZ5Jomr1JD00n");
define("MPESA_SECRET", "aM1ZSisZEJXBDnjt");
$paybill = 174379;

$get_data = prepareGetData($paybill, getPassword($paybill));
$response1 = PayRequest($get_data, getAccessToken(MPESA_KEY, MPESA_SECRET));

print_r($response1);
