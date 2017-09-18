<?php

require_once('/etc/fusionpbx/config.php');
require_once('routine_jobs_tasks/channels_cleanup.php');

$db_freeswitch = pg_connect("host=".$db_host." dbname=freeswitch user=".$db_username." password=".$db_password)
    or die('Could not connect: ' . pg_last_error());

channels_cleanup($db_freeswitch);

pg_close($db_freeswitch);
?>