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

if (!validateDate($date,"Y-m-d") and $date != 'all') {
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

// Get date ranges. A bit hardcode actually, but really date = 'all' is not supposed to be supported
if ($date != 'all') {
    $end_date = strtotime($date);
    $start_date = strtotime("-1 day", $end_date);
} else {
    $end_date = time();
    $start_date = strtotime('2017-10-1');
}

// Get CDR's here
$sql = "SELECT caller_id_name, caller_id_number, destination_number, start_stamp, duration, json";
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

$did_campagin_calls = array();

foreach ($db_result as $cdr_line) {
    $json_cdr_line = json_decode($cdr_line['json'], true);

    $did = isset($json_cdr_line['callflow'][0]['caller_profile']['rdnis'])?$json_cdr_line['callflow'][0]['caller_profile']['rdnis']:"";
    $did = preg_replace('/\D/', '', $did);
    if (in_array($did, $campagin_did)) {
        $cdr_data = array(
            'caller_id_name' => $cdr_line['caller_id_name'],
            'caller_id_number' => $cdr_line['caller_id_number'],
            'start_stamp' => $cdr_line['start_stamp'],
            'duration' => $cdr_line['duration'],
            'did' => $did,
        );
        $filename = isset($json_cdr_line['variables']['nolocal:api_on_answer'])?$json_cdr_line['variables']['nolocal:api_on_answer']:False;
        if ($filename and strlen($filename) > 0) {
            $filename = urldecode($filename);
            $filename = explode(' ', $filename);
            $filename = end($filename);
            if (file_exists($filename)) {
                $cdr_data['recording'] = $filename;
            }
        }
        $did_campagin_calls[] = $cdr_data;
    }
}

if (count($did_campagin_calls) > 0) {

    include_once("resources/phpmailer/class.phpmailer.php");
    include_once("resources/phpmailer/class.smtp.php");


    $mail = new PHPMailer();
    $mail -> IsSMTP();
    $mail -> Host = $_SESSION['email']['smtp_host']['var'];
    if ($_SESSION['email']['smtp_port']['var'] != '') {
        $mail -> Port = $_SESSION['email']['smtp_port']['var'];
    }
    if ($_SESSION['email']['smtp_auth']['var'] == "true") {
        $mail -> SMTPAuth = $_SESSION['email']['smtp_auth']['var'];
    }
    if ($_SESSION['email']['smtp_username']['var']) {
        $mail -> Username = $_SESSION['email']['smtp_username']['var'];
        $mail -> Password = $_SESSION['email']['smtp_password']['var'];
    }
    if ($_SESSION['email']['smtp_secure']['var'] == "none") {
        $_SESSION['email']['smtp_secure']['var'] = '';
    }
    if ($_SESSION['email']['smtp_secure']['var'] != '') {
        $mail -> SMTPSecure = $_SESSION['email']['smtp_secure']['var'];
    }
    $eml_from_address = $_SESSION['email']['smtp_from']['var'];
    $eml_from_name = "Campagin calls";

    $mail -> addAddress($_SESSION['campagin']['report_email']['text']);
    $mail -> SetFrom($eml_from_address, $eml_from_name);
    $mail -> Subject = "Campagin calls to ".implode(',',$campagin_did)." from ".date("Y-m-d", $start_date)." to ".date("Y-m-d", $end_date);
    
    $eml_body = "";
    foreach ($did_campagin_calls as $index => $did_campagin_call) {
        
        $eml_body .= ($index + 1).". ".$did_campagin_call['start_stamp']." DID:".$did_campagin_call['did']." from ".$did_campagin_call['caller_id_name']." ".$did_campagin_call['caller_id_number']."\n";
    
        if (isset($did_campagin_call['recording'])) {
            $filename_ext = explode(".",$did_campagin_call['recording']);
            $filename_ext = end($filename_ext);
            $filename = $did_campagin_call['start_stamp']."-".$did_campagin_call['did']."-".$did_campagin_call['caller_id_name']."-".$did_campagin_call['caller_id_number'];
            $filename = preg_replace("/[^A-Za-z0-9-]/", '_', $filename);
            $filename .= ".".$filename_ext;
            $mail->addAttachment($did_campagin_call['recording'], $filename);
        }
    }
    $mail -> Body = $eml_body;

    if (!$mail->send()) {
        send_api_answer("500",'Mailer Error: ' . $mail->ErrorInfo);
    } else {
        send_api_answer("200",'Message sent!');
    }

} else {
    send_api_answer("404", "No calls on $date found");
}

?>