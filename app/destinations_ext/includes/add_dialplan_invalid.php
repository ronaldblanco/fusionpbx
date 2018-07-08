<?php
$dialplan_details = $destination_ext_dialplan_invalid_details;

//remove the array from the HTTP POST
unset($_POST["destination_ext_dialplan_invalid_details"]);

//array cleanup
foreach ($dialplan_details as $index => $row) {
    //unset the empty row
    if (strlen($row["dialplan_detail_data"]) == 0) {
        unset($dialplan_details[$index]);
    }
}

//check to see if the dialplan exists
$sql = "SELECT dialplan_uuid, dialplan_description FROM v_dialplans ";
$sql .= "WHERE dialplan_name = '_invalid_ext_handler_".$invalid_name_id."' ";
$sql .= "AND domain_uuid = '".$domain_uuid."' ";
$prep_statement = $db->prepare($sql);
if ($prep_statement) {
    $prep_statement->execute();
    $row = $prep_statement->fetch(PDO::FETCH_ASSOC);
    if (strlen($row['dialplan_uuid']) > 0) {
        $dialplan_uuid = $row['dialplan_uuid'];
        $dialplan_description = $row['dialplan_description'];
    }
    else {
        $dialplan_uuid = "";
    }
    unset($prep_statement);
} else {
    $dialplan_uuid = "";
}

//build the dialplan array
$dialplan["app_uuid"] = "cd838240-a1a6-4808-81c6-74ade7cfe100";
if (strlen($dialplan_uuid) > 0) {
    $dialplan["dialplan_uuid"] = $dialplan_uuid;
}
$dialplan["domain_uuid"] = $domain_uuid;
$dialplan["dialplan_name"] = "_invalid_ext_handler_".$invalid_name_id;
$dialplan["dialplan_number"] = '[invalid_ext]';
$dialplan["dialplan_context"] = $destination_ext_domain;
$dialplan["dialplan_continue"] = "true";
$dialplan["dialplan_order"] = "889";
$dialplan["dialplan_enabled"] = $destination_ext_enabled;
$dialplan["dialplan_description"] = ($dialplan_description != '') ? $dialplan_description : "Invalid extension handler";

$dialplan_detail_order = 10;
$y = 0;

// build XML dialplan
if ($_SESSION['destinations']['dialplan_details']['boolean'] == "false") {
    $dialplan["dialplan_xml"] = "<extension name=\"" . $dialplan["dialplan_name"] . "\" continue=\"false\" uuid=\"" . $dialplan["dialplan_uuid"] . "\">\n";
    $dialplan["dialplan_xml"] .= "	<condition field=\"\${user_exists}\" expression=\"false\"/>\n";
    $dialplan["dialplan_xml"] .= "	<condition field=\"\${call_direction}\" expression=\"inbound\"/>\n";
    $dialplan["dialplan_xml"] .= "	<condition field=\"\${invalid_ext_id}\" expression=\"^" . $invalid_name_id . "\$\">\n";
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

    $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
    $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "condition";
    $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = '${user_exists}';
    $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "^false$";
    $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
    $y += 1;
    $dialplan_detail_order += 10;

    $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
    $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "condition";
    $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = '${call_direction}';
    $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "^inbound$";
    $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
    $y += 1;
    $dialplan_detail_order += 10;

    $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
    $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "condition";
    $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = '${invalid_ext_id}';
    $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "^".$invalid_name_id."$";
    $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
    $y += 1;
    $dialplan_detail_order += 10;

    //add the actions

    if (isset($dialplan_details[0])) {
        $actions = explode(":", $row["dialplan_detail_data"]);
        $dialplan_detail_type = array_shift($actions);
        $dialplan_detail_data = join(':', $actions);

        if ($dialplan_detail_type == 'transfer') {
            $invalid_ext_transfer = explode(" ", $dialplan_detail_data)[0];
        }

        $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
        $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
        $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = $dialplan_detail_type;
        $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = $dialplan_detail_data;
        $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
        $dialplan_detail_order += 10;
        $y += 1;
    }

    //delete the previous details
    if(strlen($dialplan_uuid) > 0) {
        $sql = "DELETE FROM v_dialplan_details ";
        $sql .= "WHERE dialplan_uuid = '".$dialplan_uuid."' ";
        $sql .= "AND (domain_uuid = '".$domain_uuid."') ";
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
$cache->delete("dialplan:".$destination_ext_domain);

if (strlen($dialplan_response['uuid']) > 0) {
    $_POST["destination_ext_dialplan_invalid_uuid"] = $dialplan_response['uuid'];
    $destination_ext_dialplan_invalid_uuid = $dialplan_response['uuid'];
}
unset($dialplan_response);
unset($dialplan);
unset($dialplan_uuid);
?>