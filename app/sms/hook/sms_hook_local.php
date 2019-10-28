<?php
$received_text = <<<EOD
{"text": "\"I'm a super Alejandro $% Hello!!!\"", "from": "17867689728", "token": "swaypc_auth_token", "to": "13055908269", "messageType": "SMS"}
EOD;

$json_test = json_decode($received_text, JSON_OBJECT_AS_ARRAY);

print($json_test['text']);

?>