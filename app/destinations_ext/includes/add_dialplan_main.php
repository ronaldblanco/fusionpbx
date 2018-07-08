<?php

//get the array
$dialplan_details = $destination_ext_dialplan_main_details;
$dialplan_uuid = $destination_ext_dialplan_main_uuid;

//remove the array from the HTTP POST
unset($_POST["destination_ext_dialplan_main_details"]);

//array cleanup

if (strlen($dialplan_details[0]['dialplan_detail_data']) == 0) {
    unset($dialplan_details[0]);
}

//check to see if the dialplan exists
if (strlen($dialplan_uuid) > 0) {
    $sql = "select dialplan_uuid, dialplan_name, dialplan_description from v_dialplans ";
    $sql .= "where dialplan_uuid = '".$dialplan_uuid."' ";
    if (!permission_exists('destination_domain')) {
        $sql .= "and domain_uuid = '".$domain_uuid."' ";
    }
    $prep_statement = $db->prepare($sql);
    if ($prep_statement) {
        $prep_statement->execute();
        $row = $prep_statement->fetch(PDO::FETCH_ASSOC);
        if (strlen($row['dialplan_uuid']) > 0) {
            //$dialplan_uuid = $row['dialplan_uuid'];
            $dialplan_name = $row['dialplan_name'];
            $dialplan_description = $row['dialplan_description'];
        }
        else {
            $dialplan_uuid = "";
        }
        unset($prep_statement);
    }
    else {
        $dialplan_uuid = "";
    }
}

//build the dialplan array
$dialplan["app_uuid"] = "cd838240-a1a6-4808-81c6-74ade7cfe100";
if (strlen($dialplan_uuid) > 0) {
    $dialplan["dialplan_uuid"] = $dialplan_uuid;
}
$dialplan["domain_uuid"] = $domain_uuid;
$dialplan["dialplan_name"] = ($dialplan_name != '') ? $dialplan_name : format_phone($destination_ext_number);
$dialplan["dialplan_number"] = $destination_ext_number;
$dialplan["dialplan_context"] = "public";
$dialplan["dialplan_continue"] = "false";
$dialplan["dialplan_order"] = "100";
$dialplan["dialplan_enabled"] = $destination_ext_enabled;
$dialplan["dialplan_description"] = ($dialplan_description != '') ? $dialplan_description : $destination_ext_number . " main";

$dialplan_detail_order = 10;
$y = 0;


if ($_SESSION['destinations']['dialplan_details']['boolean'] == "false") {
    $dialplan["dialplan_xml"] = "<extension name=\"" . $dialplan["dialplan_name"] . "\" continue=\"false\" uuid=\"" . $dialplan["dialplan_uuid"] . "\">\n";
    $dialplan["dialplan_xml"] .= "	<condition field=\"".$dialplan_detail_type."\" expression=\"" . $destination_ext_number . "\">\n";
    $dialplan["dialplan_xml"] .= "		<action application=\"export\" data=\"call_direction=inbound\" inline=\"true\"/>\n";
    $dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"domain_uuid=".$_SESSION['domain_uuid']."\" inline=\"true\"/>\n";
    $dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"domain_name=".$_SESSION['domain_name']."\" inline=\"true\"/>\n";
    $dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"hangup_after_bridge=true\" inline=\"true\"/>\n";
    $dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"continue_on_fail=true\" inline=\"true\"/>\n";
    $dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"accountcode=" . $destination_ext_domain . "\"/>\n";
    if (strlen($destination_ext_variable) > 0 and isset($invalid_ext_transfer)) {
        $dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"" . $destination_ext_variable . "=" . $invalid_ext_transfer . "\"/>\n";
    }
    if (isset($dialplan_details[0])) {
        $actions = explode(":", $dialplan_details[0]["dialplan_detail_data"]);
        $dialplan_detail_type = array_shift($actions);
        $dialplan_detail_data = join(':', $actions);
        $dialplan["dialplan_xml"] .= "		<action application=\"".$dialplan_detail_type."\" data=\"".$dialplan_detail_data."\"/>\n";
    }
    $dialplan["dialplan_xml"] .= "	</condition>\n";
    $dialplan["dialplan_xml"] .= "</extension>\n";
}

if ($_SESSION['destinations']['dialplan_details']['boolean'] == "true") { 

    //check the destination number
    $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
    $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "condition";
    // -- TODO - ?
    if (strlen($_SESSION['dialplan']['destination']['text']) > 0) {
        $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = $_SESSION['dialplan']['destination']['text'];
    }
    else {
        $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "destination_number";
    }
    $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = $destination_ext_number;
    $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
    $y =+ 1;

    //increment the dialplan detail order
    $dialplan_detail_order = $dialplan_detail_order + 10;

    $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
    $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
    $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
    $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "call_direction=inbound";
    $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
    $y += 1;
    $dialplan_detail_order += 10;

    $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
    $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
    $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
    $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "domain_uuid=".$_SESSION['domain_uuid'];
    $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
    $y += 1;
    $dialplan_detail_order += 10;

    $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
    $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
    $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
    $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "domain_name=".$_SESSION['domain_name'];
    $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
    $y += 1;
    $dialplan_detail_order += 10;

    $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
    $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
    $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
    $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "hangup_after_bridge=true";
    $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
    $y += 1;
    $dialplan_detail_order += 10;

    $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
    $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
    $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
    $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "continue_on_fail=true";
    $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
    $y += 1;
    $dialplan_detail_order += 10;

    //set the call accountcode
    $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
    $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
    $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
    $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "accountcode=".$destination_ext_domain;
    $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
    $y += 1;
    $dialplan_detail_order += 10;

    if (strlen($destination_ext_variable) > 0 and isset($invalid_ext_transfer)) {
        
        $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
        $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
        $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
        $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = $destination_ext_variable."=".$invalid_ext_transfer;
        $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
        $dialplan_detail_order += 10;
        $y += 1;
    }

    $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
    $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
    $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
    $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "call_direction=inbound";
    $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;

    $y++;
    $dialplan_detail_order += 10;

    //add the actions
    if (isset($dialplan_details[0])) {
        $actions = explode(":", $dialplan_details[0]["dialplan_detail_data"]);
        $dialplan_detail_type = array_shift($actions);
        $dialplan_detail_data = join(':', $actions);

        $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
        $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
        $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = $dialplan_detail_type;
        $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = $dialplan_detail_data;
        $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
        $y += 1;
        $dialplan_detail_order += 10;
    }

    //delete the previous details
    if(strlen($dialplan_uuid) > 0) {
        $sql = "delete from v_dialplan_details ";
        $sql .= "where dialplan_uuid = '".$dialplan_uuid."' ";
        $sql .= "and (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
        //echo $sql."<br><br>";
        $db->exec(check_sql($sql));
        unset($sql);
    }
}

// Prepare an array
$array['dialplans'][] = $dialplan;
unset($dialplan);

//add the dialplan permission
$p = new permissions;
$p->add("dialplan_add", 'temp');
$p->add("dialplan_detail_add", 'temp');
$p->add("dialplan_edit", 'temp');
$p->add("dialplan_detail_edit", 'temp');

//save the dialplan
$database = new database;
$database->app_name = 'destinations_ext';
$database->app_uuid = 'cd838240-a1a6-4808-81c6-74ade7cfe100';
if (isset($dialplan["dialplan_uuid"])) {
    $database->uuid($dialplan["dialplan_uuid"]);
}
$database->save($array);
$dialplan_response = $database->message;

//remove the temporary permission
$p->delete("dialplan_add", 'temp');
$p->delete("dialplan_detail_add", 'temp');
$p->delete("dialplan_edit", 'temp');
$p->delete("dialplan_detail_edit", 'temp');

//update the dialplan xml
$dialplans = new dialplan;
$dialplans->source = "details";
$dialplans->destination = "database";
$dialplans->uuid = $dialplan_uuid;
$dialplans->xml();

//synchronize the xml config
save_dialplan_xml();

//clear the cache
$cache = new cache;
$cache->delete("dialplan:public");
$cache->delete("dialplan:public:".$destination_ext_number);

?>