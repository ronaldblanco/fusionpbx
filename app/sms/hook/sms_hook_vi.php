<?php

include "../root.php";
require_once "resources/require.php";

$domain_name = $_SESSION['domain_name'];

$sms_body = json_decode(file_get_contents('php://input'), JSON_OBJECT_AS_ARRAY);

$ip_addr = $_SERVER['REMOTE_ADDR'];

$acl = new check_acl($db, $domain_name);
file_put_contents("/tmp/sms_vi_debug.log", "****** SMS Received\n", FILE_APPEND);

if (!$acl->check($ip_addr)) {
    file_put_contents("/tmp/sms_vi_debug.log", "ACL not passed!\n", FILE_APPEND);
}

file_put_contents("/tmp/sms_vi_debug.log", "SERVER: " .json_encode($_SERVER, JSON_PRETTY_PRINT)."\n", FILE_APPEND);
file_put_contents("/tmp/sms_vi_debug.log", "SESSION: " .json_encode($_SESSION, JSON_PRETTY_PRINT)."\n", FILE_APPEND);
file_put_contents("/tmp/sms_vi_debug.log", "BODY: " .$sms_body."\n", FILE_APPEND);
?>