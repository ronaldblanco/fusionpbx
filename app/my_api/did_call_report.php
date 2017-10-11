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
    Igor Olhovskiy <igorolhovskiy@gmail.com>

*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

include "resources/classes/functions.php";

$date = check_str($_REQUEST["date"]);

if ($date == "") {
    $date = date("Y-m-d");     
}

if (!validateDate($date,"Y-m-d")) {
    send_api_answer("406", "Date format incorrect");
    exit;
}

if (!isset($_SESSION['campagin']['did'])) {
    send_api_answer("404", "Campagin DID not found");
    exit;
}

if (!isset($_SESSION['domain_uuid'])) {
    send_api_answer("503", "Domain UUID not found");
    exit;
}

$campagin_did = $_SESSION['campagin']['did'];
$domain_uuid = $_SESSION['domain_uuid'];

// Ask for cdr here
$start_date = strtotime($date);
$end_date = strtotime("+1 day", $start_date);

// Get CDR's here
$sql = "SELECT caller_id_name, caller_id_number, destination_number, start_epoch, answer_epoch, duration, json";
$sql .= " FROM v_xml_cdr WHERE (start_epoch";
$sql .= " BETWEEN ".$start_date." AND ".$end_date.")";
$sql .= " AND (domain_uuid = '".$domain_uuid."')";

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

    $did = isset($json_cdr_line['callflow'][0]['caller_profile']['rdnis'])?$json_cdr_line['callflow'][0]['caller_profile']['rdnis']:"";
    $did = preg_replace('/\D/', '', $did);
    if (in_array($did, $campagin_did)) {
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
            $cdr_data['recording'] = $filename;
        }
        $result[] = $cdr_data;
    }
}

var_dump($result);

?>
