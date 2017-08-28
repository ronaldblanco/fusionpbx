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
    Portions created by the Initial Developer are Copyright (C) 2013-2017
    the Initial Developer. All Rights Reserved.

    Contributor(s):
    Mark J Crane <markjcrane@fusionpbx.com>
    Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes
    require_once "root.php";
    require_once "resources/require.php";
    require_once "resources/check_auth.php";

//check permissions
    if (permission_exists('destinations_ext_add') || permission_exists('destinations_ext_edit')) {
        //access granted
    }
    else {
        echo "access denied";
        exit;
    }

//add multi-lingual support
    $language = new text;
    $text = $language->get();

//action add or update
    if (isset($_REQUEST["id"])) {
        $action = "update";
        $destination_ext_uuid = check_str($_REQUEST["id"]);
    }
    else {
        $action = "add";
    }

    //get http post variables and set them to php variables
    // ToDo - define varables list to get
    if (count($_POST) > 0) {
        //set the variables
        $domain_uuid = check_str($_POST["domain_uuid"]);
        $destination_ext_uuid = check_str($_POST["destination_ext_uuid"]);
        $destination_ext_dialplan_main_uuid = check_str($_POST["destination_ext_dialplan_main_uuid"]);
        $destination_ext_dialplan_main_details = check_str($_POST["destination_ext_dialplan_main_details"]);
        $destination_ext_dialplan_extensions_uuid = check_str($_POST["destination_ext_dialplan_extensions_uuid"]);
        $destination_ext_dialplan_extensions_details = check_str($_POST["destination_ext_dialplan_extensions_details"]);
        $destination_ext_dialplan_invalid_uuid = check_str($_POST["destination_ext_dialplan_invalid_uuid"]);
        $destination_ext_dialplan_invalid_details = check_str($_POST["destination_ext_dialplan_invalid_details"]);
        $destination_ext_number = check_str($_POST["destination_ext_number"]);
        $db_destination_ext_number = check_str($_POST["db_destination_ext_number"]);
        $destination_ext_variable = check_str($_POST["destination_ext_variable"]);
        $destination_ext_enabled = check_str($_POST["destination_ext_enabled"]);
        $destination_ext_description = check_str($_POST["destination_ext_description"]);
        $destination_ext_domain = check_str($_POST["destination_ext_domain"]);
        //convert the number to a regular expression
        $destination_ext_number_regex = string_to_regex($destination_ext_number);
    }
    unset($_POST["db_destination_ext_number"]);

    //process the http post. Here we process UPDATE request and putting/updating info in database
    if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

        // TODO - check for all required data
        $msg = '';
        // if (strlen($destination_type) == 0) { $msg .= $text['message-required']." ".$text['label-destination_type']."<br>\n"; }
        // if (strlen($destination_number) == 0) { $msg .= $text['message-required']." ".$text['label-destination_number']."<br>\n"; }
        // if (strlen($destination_context) == 0) { $msg .= $text['message-required']." ".$text['label-destination_context']."<br>\n"; }
        // if (strlen($destination_enabled) == 0) { $msg .= $text['message-required']." ".$text['label-destination_enabled']."<br>\n"; }

        //check for duplicates
        if ($destination_ext_number != $db_destination_ext_number) {
            $sql = "select ";
            $sql .= "(select count(*) as num_rows from v_destinations ";
            $sql .= "where destination_number = '".$destination_ext_number."' ) + ";
            $sql .= "(select count(*) from v_destinations_ext ";
            $sql .= "where destination_ext_number = '".$destination_ext_number."') ";
            $sql .= "as num_rows";
            $prep_statement = $db->prepare($sql);
            if ($prep_statement) {
                $prep_statement->execute();
                $row = $prep_statement->fetch(PDO::FETCH_ASSOC);
                if ($row['num_rows'] > 0) {
                    $msg .= $text['message-duplicate']."<br>\n";
                }
                unset($prep_statement);
            }
        }

        //show the message
        if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
            require_once "resources/header.php";
            require_once "resources/persist_form_var.php";
            echo "<div align='center'>\n";
            echo "<table><tr><td>\n";
            echo $msg."<br />";
            echo "</td></tr></table>\n";
            persistformvar($_POST);
            echo "</div>\n";
            require_once "resources/footer.php";
            return;
        }

        //add or update the database
        if ($_POST["persistformvar"] != "true") {

            //determine whether save the main dialplan
            foreach ($_POST["destination_ext_dialplan_main_details"] as $row) {
                if (strlen($row["dialplan_detail_data"]) > 0) {
                    $add_dialplan_main = true;
                    break;
                }
            }

            //add or update the main dialplan part if the destination number is set
            if ($add_dialplan_main) {

                //get the array
                $dialplan_details = $_POST["destination_ext_dialplan_main_details"];
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
                            $dialplan_uuid = $row['dialplan_uuid'];
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
                $dialplan["dialplan_name"] = ($dialplan_name != '') ? $dialplan_name : format_phone($destination_number);
                $dialplan["dialplan_number"] = $destination_number;
                $dialplan["dialplan_context"] = "public";
                $dialplan["dialplan_continue"] = "false";
                $dialplan["dialplan_order"] = "100";
                $dialplan["dialplan_enabled"] = $destination_ext_enabled;
                $dialplan["dialplan_description"] = ($dialplan_description != '') ? $dialplan_description : $destination_ext_description + " main";
                
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

                if (strlen($destination_ext_variable) > 0 and strlen($dialplan_details[0]['dialplan_detail_data']) > 0) {
                    
                    $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
                    $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
                    $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
                    $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = $destination_ext_variable."=".$destination_ext_domain;
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
                    if (!permission_exists('destination_domain')) {
                        $sql .= "and (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
                    }
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
                $cache->delete("dialplan:".$destination_context);
                unset($orm);

            } else {
                //add or update the dialplan if the destination number is set 
                //remove empty dialplan details from POST array so doesn't attempt to insert below
                unset($_POST["destination_ext_dialplan_main_details"]);
            }

            //get the destination_uuid
            if (strlen($dialplan_response['uuid']) > 0) {
                $_POST["destination_ext_dialplan_main_uuid"] = $dialplan_response['uuid'];
            }
            unset($dialplan_response);
            unset($dialplan);
            unset($dialplan_uuid);

        // End of main part -----------------------------

           foreach ($_POST["destination_ext_dialplan_extensions_details"] as $row) {
                if (strlen($row["dialplan_detail_data"]) > 0) {
                    $add_dialplan_extensions = true;
                    break;
                }
            }

            //add or update the main dialplan part if the destination number is set
            if ($add_dialplan_main) {

                //get the array
                $dialplan_details = $_POST["destination_ext_dialplan_extensions_details"];
                $dialplan_uuid = $destination_ext_dialplan_extensions_uuid;

                //remove the array from the HTTP POST
                unset($_POST["destination_ext_dialplan_extensions_details"]);

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
                            $dialplan_uuid = $row['dialplan_uuid'];
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
                $dialplan["dialplan_name"] = ($dialplan_name != '') ? $dialplan_name : format_phone($destination_number) + " + ext";
                $dialplan["dialplan_number"] = $destination_number + "\d{1,5}";
                $dialplan["dialplan_context"] = "public";
                $dialplan["dialplan_continue"] = "false";
                $dialplan["dialplan_order"] = "100";
                $dialplan["dialplan_enabled"] = $destination_ext_enabled;
                $dialplan["dialplan_description"] = ($dialplan_description != '') ? $dialplan_description : $destination_ext_description + " extension";
                
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

                $destination_number_ext_regex = str_replace(")$", "(\d{1,5})$", $destination_number_regex);
                $destination_number_ext_regex = str_replace("^(", "^", $destination_number_ext_regex);

                $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = $destination_number_ext_regex;
                $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
                $y++;

                //increment the dialplan detail order
                $dialplan_detail_order += 10;

                //set the call accountcode
                $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
                $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
                $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
                $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "accountcode=".$destination_ext_domain;
                $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
                
                $y++;
                $dialplan_detail_order += 10;

                $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
                $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
                $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
                $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "call_direction=inbound";
                $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
                
                $y++;
                $dialplan_detail_order += 10;

                if (strlen($destination_ext_variable) > 0) {
                    
                    $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
                    $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
                    $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
                    $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = $destination_ext_variable."=$1";
                    $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
                    $dialplan_detail_order += 10;
                    $y += 1;
                }

                //increment the dialplan detail order
                $dialplan_detail_order = $dialplan_detail_order + 10;

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
                        $dialplan_detail_order = $dialplan_detail_order + 10;
                        $y++;
                    }
                }

                //delete the previous details
                if(strlen($dialplan_uuid) > 0) {
                    $sql = "delete from v_dialplan_details ";
                    $sql .= "where dialplan_uuid = '".$dialplan_uuid."' ";
                    if (!permission_exists('destination_domain')) {
                        $sql .= "and (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
                    }
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
                $cache->delete("dialplan:".$destination_context);

                unset($orm);

            } else {
                //add or update the dialplan if the destination number is set 
                //remove empty dialplan details from POST array so doesn't attempt to insert below
                unset($_POST["destination_ext_dialplan_extensions_details"]);
            }

            //get the destination_uuid
            if (strlen($dialplan_response['uuid']) > 0) {
                $_POST["destination_ext_dialplan_extensions_details"] = $dialplan_response['uuid'];
            }
            unset($dialplan_response);
            unset($dialplan);
            unset($dialplan_uuid);

        // End of extensions part -----------------------

            if (strlen($destination_ext_variable) > 0) {
                //get the array
                $dialplan_details = $_POST["destination_ext_dialplan_invalid_details"];
                $dialplan_uuid = $destination_ext_dialplan_invalid_uuid;

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
                            $dialplan_uuid = $row['dialplan_uuid'];
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
                $dialplan["dialplan_name"] = "_invalid_ext_handler";
                $dialplan["dialplan_number"] = "";
                $dialplan["dialplan_context"] = $destination_ext_domain;
                $dialplan["dialplan_continue"] = "true";
                $dialplan["dialplan_order"] = "995";
                $dialplan["dialplan_enabled"] = $destination_ext_enabled;
                $dialplan["dialplan_description"] = ($dialplan_description != '') ? $dialplan_description : "Invalid extension handler";

                $dialplan_detail_order = 10;
                $y = 0;

                $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
                $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "condition";
                $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "${user_exists}";
                $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "^false$";
                $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
                $y += 1;
                $dialplan_detail_order += 10;

                $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
                $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "condition";
                $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "${call_direction}";
                $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "^inbound$";
                $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
                $y += 1;
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
                    if (!permission_exists('destination_domain')) {
                        $sql .= "and (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
                    }
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
                $cache->delete("dialplan:".$destination_context);

                unset($orm);


            } else {
                unset($_POST["destination_ext_dialplan_invalid_details"]);
            }

            if (strlen($dialplan_response['uuid']) > 0) {
                $_POST["destination_ext_dialplan_invalid_uuid"] = $dialplan_response['uuid'];
            }
            unset($dialplan_response);
            unset($dialplan);
            unset($dialplan_uuid);

        // End of invalid handler part -----------------

        //add the dialplan permission
            $permission = "dialplan_edit";
            $p = new permissions;
            $p->add($permission, 'temp');

        //save the destination
            $orm = new orm;
            $orm->name('destinations_ext');
            if (strlen($destination_ext_uuid) > 0) {
                $orm->uuid($destination_ext_uuid);
            }
            $orm->save($_POST);
            $message = $orm->message;
            $destination_ext_response = $orm->message;

        //remove the temporary permission
            $p->delete($permission, 'temp');

        //get the destination_uuid
            if (strlen($destination_response['uuid']) > 0) {
                $destination_ext_uuid = $destination_ext_response['uuid'];
            }

        //redirect the user
            if ($action == "add") {
                $_SESSION["message"] = $text['message-add'];
            }
            if ($action == "update") {
                $_SESSION["message"] = $text['message-update'];
            }
            header("Location: destination_ext_edit.php?id=".$destination_uuid);
            return;
        } //if ($_POST["persistformvar"] != "true")
    } //(count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0)

//initialize the destinations object
    $destination = new destinations;

//pre-populate the form. Here we add in the form data
    if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
        if (is_uuid($_GET["id"])) {
            $destination_uuid = $_GET["id"];
            $sql = "select * from v_destinations_ext ";
            $sql .= "where (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
            $sql .= "and destination_ext_uuid = '".$destination_ext_uuid."' ";
            $prep_statement = $db->prepare(check_sql($sql));
            $prep_statement->execute();
            $result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
        }
        if (is_array($result)) {
            foreach ($result as $row) {
                $domain_uuid = $row["domain_uuid"];
                $destination_ext_uuid = $row["destination_ext_uuid"];
                $destination_ext_dialplan_main_uuid = $row["destination_ext_dialplan_main_uuid"];
                $destination_ext_dialplan_extensions_uuid = $row["destination_ext_dialplan_extensions_uuid"];
                $destination_ext_dialplan_invalid_uuid = $row["destination_ext_dialplan_invalid_uuid"];
                $destination_ext_number = $row["destination_ext_number"];
                $destination_ext_variable = $row["destination_ext_variable"];
                $destination_ext_enabled = $row["destination_ext_enabled"];
                $destination_ext_description = $row["destination_ext_description"];
            }
        }
    }

    //get the main dialplan details in an array
    $sql = "select * from v_dialplan_details ";
    $sql .= "where (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
    $sql .= "and dialplan_uuid = '".$destination_ext_dialplan_main_uuid."' ";
    $sql .= "order by dialplan_detail_group asc, dialplan_detail_order asc";
    $prep_statement = $db->prepare(check_sql($sql));
    $prep_statement->execute();
    $destination_ext_dialplan_main_details = $prep_statement->fetchAll(PDO::FETCH_NAMED);
    unset ($prep_statement, $sql);

    if (count($destination_ext_dialplan_main_details) == 0) {
        $destination_ext_dialplan_main_details[0]['domain_uuid'] = $domain_uuid;
        $destination_ext_dialplan_main_details[0]['dialplan_uuid'] = $destination_ext_dialplan_main_uuid;
        $destination_ext_dialplan_main_details[0]['dialplan_detail_type'] = '';
        $destination_ext_dialplan_main_details[0]['dialplan_detail_data'] = '';
        $destination_ext_dialplan_main_details[0]['dialplan_detail_order'] = '';
    }

    // Get extensions dialplan details into array
    $sql = "select * from v_dialplan_details ";
    $sql .= "where (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
    $sql .= "and dialplan_uuid = '".$destination_ext_dialplan_extensions_uuid."' ";
    $sql .= "order by dialplan_detail_group asc, dialplan_detail_order asc";
    $prep_statement = $db->prepare(check_sql($sql));
    $prep_statement->execute();
    $destination_ext_dialplan_extensions_details = $prep_statement->fetchAll(PDO::FETCH_NAMED);
    unset ($prep_statement, $sql);

    if (count($destination_ext_dialplan_extensions_details) == 0) {
        $destination_ext_dialplan_extensions_details[0]['domain_uuid'] = $domain_uuid;
        $destination_ext_dialplan_extensions_details[0]['dialplan_uuid'] = $destination_ext_dialplan_extensions_uuid;
        $destination_ext_dialplan_extensions_details[0]['dialplan_detail_type'] = '';
        $destination_ext_dialplan_extensions_details[0]['dialplan_detail_data'] = '';
        $destination_ext_dialplan_extensions_details[0]['dialplan_detail_order'] = '';
    }

    // Get invalid dialplan details into array
    $sql = "select * from v_dialplan_details ";
    $sql .= "where (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
    $sql .= "and dialplan_uuid = '".$destination_ext_dialplan_invalid_uuid."' ";
    $sql .= "order by dialplan_detail_group asc, dialplan_detail_order asc";
    $prep_statement = $db->prepare(check_sql($sql));
    $prep_statement->execute();
    $destination_ext_dialplan_invalid_details = $prep_statement->fetchAll(PDO::FETCH_NAMED);
    unset ($prep_statement, $sql);

    if (count($destination_ext_dialplan_invalid_details) == 0) {
        $destination_ext_dialplan_invalid_details[0]['domain_uuid'] = $domain_uuid;
        $destination_ext_dialplan_invalid_details[0]['dialplan_uuid'] = $destination_ext_dialplan_invalid_uuid;
        $destination_ext_dialplan_invalid_details[0]['dialplan_detail_type'] = '';
        $destination_ext_dialplan_invalid_details[0]['dialplan_detail_data'] = '';
        $destination_ext_dialplan_invalid_details[0]['dialplan_detail_order'] = '';
    }


//show the header
    require_once "resources/header.php";
    if ($action == "update") {
        $document['title'] = $text['title-destination_ext-edit'];
    }
    else if ($action == "add") {
        $document['title'] = $text['title-destination_ext-add'];
    }

    //show the content
    echo "<form method='post' name='frm' action=''>\n";
    echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
    echo "<tr>\n";
    if ($action == "add") {
        echo "<td align='left' width='30%' nowrap='nowrap' valign='top'><b>".$text['header-destination_ext-add']."</b></td>\n";
    }
    if ($action == "update") {
        echo "<td align='left' width='30%' nowrap='nowrap' valign='top'><b>".$text['header-destination_ext-edit']."</b></td>\n";
    }
    echo "<td width='70%' align='right' valign='top'>";
    echo "  <input type='button' class='btn' alt='".$text['button-back']."' onclick=\"window.location='destinations.php'\" value='".$text['button-back']."'>";
    echo "  <input type='submit' class='btn' value='".$text['button-save']."'>\n";
    echo "</td>\n";
    echo "</tr>\n";
    echo "<tr>\n";
    echo "<td align='left' colspan='2'>\n";
    echo $text['description-destinations_ext']."<br /><br />\n";
    echo "</td>\n";
    echo "</tr>\n";

// Destination number enter
    echo "<tr>\n";
    echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
    echo "  ".$text['label-destination_ext_number']."\n";
    echo "</td>\n";
    echo "<td class='vtable' align='left'>\n";
    echo "  <input class='formfld' type='text' name='destination_ext_number' maxlength='255' value=\"$destination_number\" required='required'>\n";
    echo "<br />\n";
    echo $text['description-destination_ext_number']."\n";
    echo "</td>\n";
    echo "</tr>\n";

// Main number actions select

    echo "<tr id='tr_actions_main'>\n";
    echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
    echo "  ".$text['label-detail_action_main']."\n";
    echo "</td>\n";
    echo "<td class='vtable' align='left'>\n";

    echo "          <table width='52%' border='0' cellpadding='2' cellspacing='0'>\n";

    $x = 0;
    $order = 10;
    $dialplan_details = $destination_ext_dialplan_main_details;

    foreach($dialplan_details as $row) {
        if ($row["dialplan_detail_tag"] != "condition") {
            if ($row["dialplan_detail_tag"] == "action" && $row["dialplan_detail_type"] == "set") {
                // Exclude all set's.
                continue;
            }
            echo "              <tr>\n";
            echo "                  <td style='padding-top: 5px; padding-right: 3px; white-space: nowrap;'>\n";
            if (strlen($row['dialplan_detail_uuid']) > 0) {
                echo "  <input name='destination_ext_dialplan_main_details[".$x."][dialplan_detail_uuid]' type='hidden' value=\"".$row['dialplan_detail_uuid']."\">\n";
            }
            echo "  <input name='destination_ext_dialplan_main_details[".$x."][dialplan_detail_type]' type='hidden' value=\"".$row['dialplan_detail_type']."\">\n";
            echo "  <input name='destination_ext_dialplan_main_details[".$x."][dialplan_detail_order]' type='hidden' value=\"".$order."\">\n";

            $data = $row['dialplan_detail_data'];
            $label = explode("XML", $data);
            $divider = ($row['dialplan_detail_type'] != '') ? ":" : null;
            $detail_action = $row['dialplan_detail_type'].$divider.$row['dialplan_detail_data'];
            echo $destination->select('dialplan', 'destination_ext_dialplan_main_details['.$x.'][dialplan_detail_data]', $detail_action);
            echo "                  </td>\n";
            echo "                  <td class='list_control_icons' style='width: 25px;'>";
            if (strlen($row['destination_ext_uuid']) > 0) {
                echo                    "<a href='destination_ext_delete.php?id=".$row['destination_uuid']."&destination_uuid=".$row['destination_uuid']."&a=delete' alt='delete' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>\n";
            }
            echo "                  </td>\n";
            echo "              </tr>\n";
        }
        $order = $order + 10;
        $x++;
    }
    echo "          </table>\n";
    echo "</td>\n";
    echo "</tr>\n";

// Extensions destination set

    echo "<tr id='tr_actions_ext'>\n";
    echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
    echo "  ".$text['label-detail_action_ext']."\n";
    echo "</td>\n";
    echo "<td class='vtable' align='left'>\n";

    echo "          <table width='52%' border='0' cellpadding='2' cellspacing='0'>\n";

    $x = 0;
    $order = 10;
    $dialplan_details = $destination_ext_dialplan_extensions_details;

    foreach($dialplan_details as $row) {
        if ($row["dialplan_detail_tag"] != "condition") {
            if ($row["dialplan_detail_tag"] == "action" && $row["dialplan_detail_type"] == "set") {
                // Exclude all set's.
                continue;
            }
            echo "              <tr>\n";
            echo "                  <td style='padding-top: 5px; padding-right: 3px; white-space: nowrap;'>\n";
            if (strlen($row['dialplan_detail_uuid']) > 0) {
                echo "  <input name='destination_ext_dialplan_extensions_details[".$x."][dialplan_detail_uuid]' type='hidden' value=\"".$row['dialplan_detail_uuid']."\">\n";
            }
            echo "  <input name='destination_ext_dialplan_extensions_details[".$x."][dialplan_detail_type]' type='hidden' value=\"".$row['dialplan_detail_type']."\">\n";
            echo "  <input name='destination_ext_dialplan_extensions_details[".$x."][dialplan_detail_order]' type='hidden' value=\"".$order."\">\n";

            $data = $row['dialplan_detail_data'];
            $label = explode("XML", $data);
            $divider = ($row['dialplan_detail_type'] != '') ? ":" : null;
            $detail_action = $row['dialplan_detail_type'].$divider.$row['dialplan_detail_data'];
            echo $destination->select('dialplan', 'destination_ext_dialplan_extensions_details['.$x.'][dialplan_detail_data]', $detail_action);
            echo "                  </td>\n";
            echo "                  <td class='list_control_icons' style='width: 25px;'>";
            if (strlen($row['destination_ext_uuid']) > 0) {
                echo                    "<a href='destination_ext_delete.php?id=".$row['destination_uuid']."&destination_uuid=".$row['destination_uuid']."&a=delete' alt='delete' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>\n";
            }
            echo "                  </td>\n";
            echo "              </tr>\n";
        }
        $order = $order + 10;
        $x++;
    }
    echo "          </table>\n";
    echo "</td>\n";
    echo "</tr>\n";


// Invalid destiantions set

    echo "<tr id='tr_actions_invalid'>\n";
    echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
    echo "  ".$text['label-detail_action_invalid']."\n";
    echo "</td>\n";
    echo "<td class='vtable' align='left'>\n";

    echo "          <table width='52%' border='0' cellpadding='2' cellspacing='0'>\n";

    $x = 0;
    $order = 10;
    $dialplan_details = $destination_ext_dialplan_invalid_details;

    foreach($dialplan_details as $row) {
        if ($row["dialplan_detail_tag"] != "condition") {
            if ($row["dialplan_detail_tag"] == "action" && $row["dialplan_detail_type"] == "set") {
                // Exclude all set's.
                continue;
            }
            echo "              <tr>\n";
            echo "                  <td style='padding-top: 5px; padding-right: 3px; white-space: nowrap;'>\n";
            if (strlen($row['dialplan_detail_uuid']) > 0) {
                echo "  <input name='destination_ext_dialplan_invalid_details[".$x."][dialplan_detail_uuid]' type='hidden' value=\"".$row['dialplan_detail_uuid']."\">\n";
            }
            echo "  <input name='destination_ext_dialplan_invalid_details[".$x."][dialplan_detail_type]' type='hidden' value=\"".$row['dialplan_detail_type']."\">\n";
            echo "  <input name='destination_ext_dialplan_invalid_details[".$x."][dialplan_detail_order]' type='hidden' value=\"".$order."\">\n";

            $data = $row['dialplan_detail_data'];
            $label = explode("XML", $data);
            $divider = ($row['dialplan_detail_type'] != '') ? ":" : null;
            $detail_action = $row['dialplan_detail_type'].$divider.$row['dialplan_detail_data'];
            echo $destination->select('dialplan', 'destination_ext_dialplan_invalid_details['.$x.'][dialplan_detail_data]', $detail_action);
            echo "                  </td>\n";
            echo "                  <td class='list_control_icons' style='width: 25px;'>";
            if (strlen($row['destination_ext_uuid']) > 0) {
                echo                    "<a href='destination_ext_delete.php?id=".$row['destination_uuid']."&destination_uuid=".$row['destination_uuid']."&a=delete' alt='delete' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>\n";
            }
            echo "                  </td>\n";
            echo "              </tr>\n";
        }
        $order = $order + 10;
        $x++;
    }
    echo "          </table>\n";
    echo "</td>\n";
    echo "</tr>\n";

    if (permission_exists('destination_domain')) {
        echo "<tr>\n";
        echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
        echo "  ".$text['label-domain']."\n";
        echo "</td>\n";
        echo "<td class='vtable' align='left'>\n";
        echo "    <select class='formfld' name='domain_uuid' id='destination_domain' onchange='context_control();'>\n";
        if (strlen($domain_uuid) == 0) {
            echo "    <option value='' selected='selected'>".$text['select-global']."</option>\n";
        }
        else {
            echo "    <option value=''>".$text['select-global']."</option>\n";
        }
        foreach ($_SESSION['domains'] as $row) {
            if ($row['domain_uuid'] == $domain_uuid) {
                echo "    <option value='".$row['domain_uuid']."' selected='selected'>".$row['domain_name']."</option>\n";
            }
            else {
                echo "    <option value='".$row['domain_uuid']."'>".$row['domain_name']."</option>\n";
            }
        }
        echo "    </select>\n";
        echo "<br />\n";
        echo $text['description-domain_name']."\n";
        echo "</td>\n";
        echo "</tr>\n";
    }
    else {
        echo "<input type='hidden' name='domain_uuid' value='".$domain_uuid."'>\n";
    }

    echo "<tr>\n";
    echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
    echo "  ".$text['label-destination_ext_variable']."\n";
    echo "</td>\n";
    echo "<td class='vtable' align='left'>\n";
    echo "  <input class='formfld' type='text' name='destination_ext_variable' id='destination_ext_variable' maxlength='255' value=\"$destination_ext_variable\">\n";
    echo "<br />\n";
    echo $text['description-destination_ext_variable']."\n";
    echo "</td>\n";
    echo "</tr>\n";

    echo "<tr>\n";
    echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
    echo "  ".$text['label-destination_ext_enabled']."\n";
    echo "</td>\n";
    echo "<td class='vtable' align='left'>\n";
    echo "  <select class='formfld' name='destination_ext_enabled'>\n";
    switch ($destination_ext_enabled) {
        case "true" :   $selected[1] = "selected='selected'";   break;
        case "false" :  $selected[2] = "selected='selected'";   break;
    }
    echo "  <option value='true' ".$selected[1].">".$text['label-true']."</option>\n";
    echo "  <option value='false' ".$selected[2].">".$text['label-false']."</option>\n";
    unset($selected);
    echo "  </select>\n";
    echo "<br />\n";
    echo $text['description-destination_ext_enabled']."\n";
    echo "</td>\n";
    echo "</tr>\n";

    echo "<tr>\n";
    echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
    echo "  ".$text['label-destination_ext_description']."\n";
    echo "</td>\n";
    echo "<td class='vtable' align='left'>\n";
    echo "  <input class='formfld' type='text' name='destination_ext_description' maxlength='255' value=\"$destination_ext_description\">\n";
    echo "<br />\n";
    echo $text['description-destination_ext_description']."\n";
    echo "</td>\n";
    echo "</tr>\n";
    echo "  <tr>\n";
    echo "      <td colspan='2' align='right'>\n";
    if ($action == "update") {
        echo "      <input type='hidden' name='db_destination_number' value='$destination_ext_number'>\n";
        echo "      <input type='hidden' name='destination_ext_dialplan_main_uuid' value='$destination_ext_dialplan_main_uuid'>\n";
        echo "      <input type='hidden' name='destination_ext_dialplan_extensions_uuid' value='$destination_ext_dialplan_extensions_uuid'>\n";
        echo "      <input type='hidden' name='destination_ext_dialplan_invalid_uuid' value='$destination_ext_dialplan_invalid_uuid'>\n";
        echo "      <input type='hidden' name='destination_ext_uuid' value='$destination_ext_uuid'>\n";
    }
    echo "          <br>";
    echo "          <input type='submit' class='btn' value='".$text['button-save']."'>\n";
    echo "      </td>\n";
    echo "  </tr>";
    echo "</table>";
    echo "<br><br>";
    echo "</form>";

//include the footer
    require_once "resources/footer.php";

?>
