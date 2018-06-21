<?php

function channels_cleanup($db_freeswitch, $db_fusion = False, $time_offset = 10800) { // 3 hours offset time
    $start_time = time() - $time_offset;
    $db_query = "DELETE FROM channels WHERE created_epoch < ".$start_time;
    pg_query($db_freeswitch, $db_query);
}

?>