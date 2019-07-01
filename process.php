<?php
//mpesa                    
function isAssoc(array $arr){
    if (array() === $arr) return false;
    return array_keys($arr) !== range(0, count($arr) - 1);
}

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
function preparePostData($Shortcode, $Password, $callback, array $transactionData){
   if(!is_array($transactionData)){
       return "Bad_transaction_data_format_array_expected";
       exit();
   }elseif(count($transactionData) > 4){
        return "Transaction_data_too_long_for_the_system";
        exit();
   }elseif(isAssoc($transactionData)==true){
        return "Transaction_data_is_associative_sequential_expected";
        exit();
   }elseif(count($transactionData) < 4){
        return "Transaction_data_too_short_for_the_system";
        exit();
   }elseif(empty($Shortcode) || empty($Password) || empty($callback) || empty($transactionData)){
        return "System_detected_empty_parameter";
        exit();
   }else{
        $customerPhone=$transactionData[0];
        $payAmt=$transactionData[1];
        $acRef=$transactionData[2];
        $transDesc=$transactionData[3];
        $Timestamp = date('Ymdhis');   //timestamp
    $curl_post_data = array(
        "BusinessShortCode" => $Shortcode,//business receiving payment, paybill number
        "Password" => $Password,    //a base 64 encode of shortcode, passkey and timestamp
        "Timestamp" => $Timestamp,     //time in Ymdhis formart
        "TransactionType" => "CustomerPayBillOnline",
        "Amount" => $payAmt,    //amount charged
        "PartyA" => $customerPhone,   //customer
        "PartyB" => $Shortcode,   //business receiving payment
        "PhoneNumber" => $customerPhone,    //customer
        "CallBackURL" => $callback,      //use https://developer.safaricom.co.ke for test
        "AccountReference" => $acRef,     //transaction ref.. can be invoice number
        "TransactionDesc" => $transDesc
    );

    return $curl_post_data;
 }
}
function InitiatePayRequest($curl_post_data, $accessToken){
    $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';//test url
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization:Bearer '.$accessToken)); //access token from previous request
    $data_string = json_encode($curl_post_data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
    $curl_response = curl_exec($curl);
    $res = json_decode($curl_response);
    return $res;
}
function RegisterHTTPUrl($shortCode, $confirmURL, $validateURL, $accessToken){  
    $url = 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/registerurl';
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization:Bearer '.$accessToken)); //setting custom header
    $curl_post_data = array(
      'ShortCode' => $shortCode,
      'ResponseType' => 'JSON',
      'ConfirmationURL' => $confirmURL,
      'ValidationURL' => $validateURL
    );
    $data_string = json_encode($curl_post_data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
    $curl_response = curl_exec($curl);
    $resp = json_decode($curl_response);
    return $resp;
}

function GetPhone($phoneNumber) // phone number is not empty
{
    if(preg_match('/^\d{9}$/',$phoneNumber)) // phone number is valid
    {
      $phoneNumber = '254' . $phoneNumber;

    }
    else // phone number is not valid
    {
      return "Invalid Phone Number";
      exit();
    }
return $phoneNumber;
}


require_once("classes/auth/class.OAuth.php");
#universal
define("CALLBACK", "http://aailabs4.com/donations/callback.php");
$CALLBACK= "http://aailabs4.com/donations/callback.php";
define("MPESA_KEY", "yj2craqjBnO5AVnQHX6MqZmSTstsXBLJ");
define("MPESA_SECRET", "2n9kpG2v5NhittOT");
$MPESA_PHONE =$_POST['mpesa_number'];
$Amt = $_POST['amt'];
$paybill = 174379;
$acc_no = 13244;
$transactionDesc = 'Test Donation';
$transactionData = array($MPESA_PHONE, $Amt, $acc_no, $transactionDesc);
$URLresponse = RegisterHTTPUrl($paybill, $CALLBACK, $CALLBACK, getAccessToken(MPESA_KEY, MPESA_SECRET));
//print_r($URLresponse);
$post_data = preparePostData($paybill, getPassword($paybill), CALLBACK, $transactionData);
$response = InitiatePayRequest($post_data, getAccessToken(MPESA_KEY, MPESA_SECRET));

//echo '<pre>';
//print_r($response);

//print $CheckoutRequestID=$response["Body"]["stkCallback"]["CheckoutRequestID"];
//echo '</pre>';
//echo GetPhone($_POST['mpesa_number'])."<br>";

echo $MPESA_PHONE."<br>";


print_r($response);

console.log($response);

$MerchantRequestID=$response->MerchantRequestID;
$CheckoutRequestID=$response->CheckoutRequestID;
$ResponseCode=$response->ResponseCode;
$ResponseDescription=$response->ResponseDescription;
$CustomerMessage=$response->CustomerMessage;

$PaymentMethod="LIPA NA MPESA";
$payStatus="777777";