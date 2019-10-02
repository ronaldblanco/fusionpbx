<?php

include "../root.php";
require_once "resources/require.php";

$domain_name = $_SESSION['domain_name'];

$ip_addr = $_SERVER['REMOTE_ADDR'];

$acl = new check_acl($db, $domain_name);

if (!$acl->check($ip_addr)) {
    echo "access denied";
    die(0);
}

error_log($_SERVER, JSON_PRETTY_PRINT);
?>