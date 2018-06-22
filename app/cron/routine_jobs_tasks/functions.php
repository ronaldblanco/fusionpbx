<?php

function channels_cleanup($db_freeswitch, $db_fusion = False, $time_offset = 10800) { // 3 hours offset time
    $start_time = time() - $time_offset;
    $db_query = "DELETE FROM channels WHERE created_epoch < ".$start_time;
    pg_query($db_freeswitch, $db_query);
}

function issue_cert($db_freeswitch, $db_fusion) {
    $excluded_domains = ['master.rufan.at', 'consertis.securenetvox.net'];
    $getssl_path = "/root";
    $getssl_default_domain = "consertis.securenetvox.net";
    $domains_list = array();

    $db_query = "SELECT domain_name FROM v_domains";
    $result = pg_query($db_fusion, $db_query) or die('Request error: ' . pg_last_error());

    $result = pg_fetch_all($result);
    foreach ($result as $domain_line) {
        if (!in_array($domain_line['domain_name'], $excluded_domains)) {
            $domains_list[] = $domain_line['domain_name'];
        }
    }

    $domains_list = implode(",", $domains_list);

    $getssl_file = <<<EOT
CA="https://acme-v01.api.letsencrypt.org"
SANS="$domains_list"
ACL=('/var/www/fusionpbx/.well-known/acme-challenge')
USE_SINGLE_ACL="true"
DOMAIN_KEY_LOCATION="/etc/ssl/private/securenetvox.net/server.key"
DOMAIN_CHAIN_LOCATION="/etc/ssl/private/securenetvox.net/server.crt"
RELOAD_CMD="/etc/init.d/nginx restart"
EOT;

    file_put_contents($getssl_path."/.getssl/".$getssl_default_domain."/getssl.cfg", $getssl_file);
    #exec("/bin/bash ".$getssl_path."/getssl -f ".$getssl_default_domain." &");
    printf("/bin/bash ".$getssl_path."/getssl -f ".$getssl_default_domain." &");
}

?>