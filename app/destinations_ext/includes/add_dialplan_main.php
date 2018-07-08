<?php

//get the array
$dialplan_details = $destination_ext_dialplan_main_details;
$dialplan_uuid = $destination_ext_dialplan_main_uuid;

//remove the array from the HTTP POST
unset($_POST["destination_ext_dialplan_main_details"]);

//array cleanup
foreach ($dialplan_details as $index => $row) {
    //unset the empty row
        if (strlen($row["dialplan_detail_data"]) == 0) {
            unset($dialplan_details[$index]);
        }
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
$dialplan["app_uuid"] = "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4";
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
$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = $destination_ext_number_regex;
$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
$y =+ 1;

//increment the dialplan detail order
$dialplan_detail_order = $dialplan_detail_order + 10;

//set the call accountcode
$dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "accountcode=".$destination_ext_domain;
$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
$y += 1;

//increment the dialplan detail order
$dialplan_detail_order += 10;


// TODO - get $destination_ext_variable here as 1st occure of $dialplan_details transfer

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
foreach ($dialplan_details as $row) {
    if (strlen($row["dialplan_detail_data"]) > 1) {
        $actions = explode(":", $row["dialplan_detail_data"]);
        $dialplan_detail_type = array_shift($actions);
        $dialplan_detail_data = join(':', $actions);

        $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
        $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
        $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = $dialplan_detail_type;
        $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = $dialplan_detail_data;
        $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
        $dialplan_detail_order += 10;
        $y += 1;
    }
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

//add the dialplan permission
$p = new permissions;
$p->add("dialplan_add", 'temp');
$p->add("dialplan_detail_add", 'temp');
$p->add("dialplan_edit", 'temp');
$p->add("dialplan_detail_edit", 'temp');

//save the dialplan
$orm = new orm;
$orm->name('dialplans');
if (isset($dialplan["dialplan_uuid"])) {
    $orm->uuid($dialplan["dialplan_uuid"]);
}
$orm->save($dialplan);
$dialplan_response = $orm->message;

//remove the temporary permission
$p->delete("dialplan_add", 'temp');
$p->delete("dialplan_detail_add", 'temp');
$p->delete("dialplan_edit", 'temp');
$p->delete("dialplan_detail_edit", 'temp');

//synchronize the xml config
save_dialplan_xml();

//clear the cache
$cache = new cache;
$cache->delete("dialplan:public");
unset($cache);
unset($orm);

?>