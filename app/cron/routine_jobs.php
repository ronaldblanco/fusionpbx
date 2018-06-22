<?php

require_once('/etc/fusionpbx/config.php');
require_once('routine_jobs_tasks/functions.php');

$db_freeswitch = pg_connect("host=".$db_host." dbname=freeswitch user=".$db_username." password=".$db_password)
    or die('Could not connect: ' . pg_last_error());

$db_fusion =  pg_connect("host=".$db_host." dbname=fusionpbx user=".$db_username." password=".$db_password)
    or die('Could not connect: ' . pg_last_error());

$argv[1]($db_freeswitch, $db_fusion);

pg_close($db_freeswitch);
pg_close($db_fusion);
?>