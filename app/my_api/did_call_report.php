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

$name = check_str($_REQUEST["name"]);
$night_action = check_str($_REQUEST["night_action"]);
$ring_group = check_str($_REQUEST["ring_group"]);
$transfer = check_str($_REQUEST["transfer"]);
//sterilize the user data

$name = preg_replace('/\s+/', '', $name);
$name = preg_replace('/\D/', '', $name);
$name_len = strlen($name);
if ($name_len == 10) {
    $name = "1".$name;
} else if ($name_len != 11) {
    $name = substr($name, -11);
}

$night_action = preg_replace('/\s+/', '', $night_action);
$ring_group = preg_replace('/\s+/', '', $ring_group);
$transfer = preg_replace('/\s+/', '', $transfer);

// Get dialplan uuid
$sql = "SELECT dialplan_uuid FROM v_dialplans";
$sql .= " WHERE dialplan_name = '".$name."' AND dialplan_context = 'public'";

$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll();
$result_count = count($result);
unset ($prep_statement, $sql);

if ($result_count == 1) {
    $dialplan_uuid = $result[0]['dialplan_uuid'];
    $result = True;
    $message = "";
    if (strlen($night_action) > 0) {
        $sql = "SELECT dialplan_detail_uuid FROM v_dialplan_details";
        $sql .= " WHERE dialplan_uuid = '".$dialplan_uuid."' ";
        $sql .= "AND dialplan_detail_data LIKE 'night_action=%'";
        $prep_statement = $db->prepare(check_sql($sql));
        $prep_statement->execute();
        $result = $prep_statement->fetchAll();
        $result_count = count($result);
        unset ($prep_statement, $sql);
        if ($result_count == 1) {

            $dialplan_detail_uuid = $result[0]['dialplan_detail_uuid'];

            $sql  = "UPDATE v_dialplan_details ";
            $sql .= "SET dialplan_detail_data = 'night_action=".$night_action."' ";
            $sql .= "WHERE dialplan_detail_uuid = '".$dialplan_detail_uuid."'";
            $prep_statement = $db->prepare(check_sql($sql));
            $prep_statement->execute();
            unset ($prep_statement, $sql);
            $message .= "night_action updated. ";
        } else {
            $result = False;
            $message .= "night_action not found. ";
        }

    }
	if (strlen($ring_group) > 0) {
        $sql = "SELECT dialplan_detail_uuid FROM v_dialplan_details";
        $sql .= " WHERE dialplan_uuid = '".$dialplan_uuid."' ";
        $sql .= "AND dialplan_detail_data LIKE 'ring_group=%'";
        $prep_statement = $db->prepare(check_sql($sql));
        $prep_statement->execute();
        $result = $prep_statement->fetchAll();
        $result_count = count($result);
        unset ($prep_statement, $sql);
        if ($result_count == 1) {

            $dialplan_detail_uuid = $result[0]['dialplan_detail_uuid'];

            $sql  = "UPDATE v_dialplan_details ";
            $sql .= "SET dialplan_detail_data = 'ring_group=".$ring_group."' ";
            $sql .= "WHERE dialplan_detail_uuid = '".$dialplan_detail_uuid."'";
            $prep_statement = $db->prepare(check_sql($sql));
            $prep_statement->execute();
            unset ($prep_statement, $sql);
            $message .= "ring_group updated. ";
        } else {
            $result = False;
            $message .= "ring_group not found. ";
        }

    }
    if (strlen($transfer) > 0) {
        $sql = "SELECT dialplan_detail_uuid FROM v_dialplan_details";
        $sql .= " WHERE dialplan_uuid = '".$dialplan_uuid."' ";
        $sql .= "AND dialplan_detail_type = 'transfer'";
        $prep_statement = $db->prepare(check_sql($sql));
        $prep_statement->execute();
        $result = $prep_statement->fetchAll();
        $result_count = count($result);
        unset ($prep_statement, $sql);
        if ($result_count == 1) {

            $dialplan_detail_uuid = $result[0]['dialplan_detail_uuid'];
            // Prepare transfer action
            preg_match('/(.*)(XML)(.*)/', $transfer, $transfer_parts);
            unset($transfer_parts[0]);
            $transfer = implode(' ', $transfer_parts);

            $sql  = "UPDATE v_dialplan_details ";
            $sql .= "SET dialplan_detail_data = '".$transfer."' ";
            $sql .= "WHERE dialplan_detail_uuid = '".$dialplan_detail_uuid."'";
            $prep_statement = $db->prepare(check_sql($sql));
            $prep_statement->execute();
            unset ($prep_statement, $sql);
            $message .= "transfer updated. ";
        } else {
            $result = False;
            $message .= "transfer not found. ";
        }
    }
    if ($result) {
        send_api_answer("200", $message);
    } else {
        send_api_answer("400", $message);
    }
} elseif ($result_count == 0) {
    send_api_answer("404", "No dialplan found");
} else {
    send_api_answer("409", "Too many dialplans found (".$result_count.")");
}

?>
