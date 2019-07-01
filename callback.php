<?php
$servername = "localhost";
$username = "aailabsf_user1";
$password = "aaiuser1";
$dbname = "aailabsf_donate";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 


$postData = file_get_contents('php://input');
    
$json=json_decode($postData, true);

$MerchantRequestID= $json['Body']['stkCallback']['MerchantRequestID'];
$CheckoutRequestID= $json['Body']['stkCallback']['CheckoutRequestID'];
$ResultCode= $json['Body']['stkCallback']['ResultCode'];
$ResultDesc= $json['Body']['stkCallback']['ResultDesc'];


$sql = "INSERT INTO `responses`(`id`, `MerchantRequestID`, `CheckoutRequestID`, `ResultCode`, `ResultDesc`, `date`) VALUES ('','".$MerchantRequestID."','".$CheckoutRequestID."','".$ResultCode."','".$ResultDesc."','')";

if ($conn->query($sql) === TRUE) {
    echo "New record created successfully";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

//perform your processing here, e.g. log to file....
$file = fopen("log.txt", "w"); //url fopen should be allowed for this to occur
if(fwrite($file, $MerchantRequestID) === FALSE)
{
    fwrite("Error: no data written");
}

fwrite("\r\n");
fclose($file);
$conn->close();
echo '{"ResultCode": 0, "ResultDesc": "The service was accepted successfully", "ThirdPartyTransID": "1234567890"}';
?>
