<?php

include "../root.php"; 
require_once "resources/require.php";

$domain_name = $_SESSION['domain_name'];

$ip_addr = $_SERVER['REMOTE_ADDR'];

//Only for TESTs!
//$myfile = fopen("log.txt", "a") or die("Unable to open file!");
//echo 'HOOK OUTBOUND!\n';
//echo date("Y.m.d G:i:s")."\n";
//var_dump($_GET);
//var_dump($_POST);

//echo $ip_addr;

//$sms_acl = new check_sms_acl($db, $domain_name);
//var_dump($sms_acl);

//Only allow to send from the server
if ($ip_addr != '127.0.0.1') {
    echo "Access denied.";
    return;
}

/*if ($_GET['messageType'] != "SMS") {
    echo "Only SMS supported.";
    return;
}*/

$from = base64_encode($_GET['from']);
$to = base64_encode($_GET['to']);
$domain = base64_encode($_GET['domain']);
$data = base64_encode($_GET['data']);

$sms_send_out_encripted = file_get_contents("https://domain.com/sendSMS.php?from=" . $from . "&to=" . $to . "&domain=" . $domain . "&data=" . $data);

echo $sms_send_out_encripted;

//Only for TESTs!
//fwrite($myfile, file_put_contents("log.txt", ob_get_flush()));
//fclose($myfile);

?>
