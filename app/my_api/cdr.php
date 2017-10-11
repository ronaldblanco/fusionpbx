<?php
/*
    FusionPBX
    Version: MPL 1.1

    The contents of this file are subject to the Mozilla Public License Version
    1.1 (the "License"); you may not use this file except in compliance with
    the License. You may obtain a copy of the License at
    http://www.mozilla.org/MPL/

    Software distributed under the License is distributed on an "AS IS" basis,
    WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
    for the specific language governing rights and limitations under the
    License.

    The Original Code is FusionPBX

    The Initial Developer of the Original Code is
    Mark J Crane <markjcrane@fusionpbx.com>
    Portions created by the Initial Developer are Copyright (C) 2008-2016
    the Initial Developer. All Rights Reserved.

    Contributor(s):
    Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

include "resources/classes/functions.php";

$did = check_str($_REQUEST["did"]);
$start = check_str($_REQUEST["start"]);
$end = check_str($_REQUEST["end"]);

$did = preg_replace('/\D/', '', $did);

if (!is_numeric($start) || !is_numeric($end)) {
    send_api_answer("400", "Start and end should be timestamps");
    exit;
}

if (strlen($did) == 0) {
    send_api_answer("400", "DID not found");
    exit;
}

$sql = "SELECT caller_id_name, caller_id_number, destination_number, start_epoch, answer_epoch, duration, json";
$sql .= " FROM v_xml_cdr WHERE (start_epoch";
$sql .= " BETWEEN ".$start." AND ".$end.")";
$sql .= " AND (caller_id_name LIKE '".$did."%')";

$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$db_result = $prep_statement->fetchAll();
unset ($prep_statement, $sql);

if (count($db_result) <= 0) {
    send_api_answer("404", "No records found");
    exit;
}

$result = array();

foreach ($db_result as $cdr_line) {
    $json_cdr_line = json_decode($cdr_line['json'], true);
    $cdr_data = array(
        'caller_id_name' => $cdr_line['caller_id_name'],
        'caller_id_number' => $cdr_line['caller_id_number'],
        'destination' => $cdr_line['destination_number'],
        'start' => $cdr_line['start_epoch'],
        'time_to_answer' => (string)($cdr_line['start_epoch'] - $cdr_line['answer_epoch']),
        'duration' => $cdr_line['duration'],
    );
    $filename = isset($json_cdr_line['variables']['filename'])?$json_cdr_line['variables']['filename']:False;
    if (file_exists($filename)) {
        $filename = str_replace('/usr/local/freeswitch/recordings', 'http://'.$_SESSION['domain_name'].':8080', $filename);
        $cdr_data['recording'] = $filename;
    }
    $result[] = $cdr_data;
}

send_api_answer("200", "Query successfull", $result);

?>
