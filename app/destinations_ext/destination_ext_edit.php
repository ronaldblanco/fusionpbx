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
        $prev_generated_destination_ext_uuid = uuid();
    }

    //get http post variables and set them to php variables


    //get total destination count from the database, check limit, if defined. Here we check against normal destinations
	if (!permission_exists('destination_domain')) {
		if ($action == 'add') {
			if ($_SESSION['limit']['destinations']['numeric'] != '') {
				$sql = "SELECT count(*) AS num_rows FROM v_destinations WHERE domain_uuid = '".$domain_uuid."' ";
				$prep_statement = $db->prepare($sql);
				if ($prep_statement) {
					$prep_statement->execute();
					$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
					$total_destinations = $row['num_rows'];
				}
				unset($prep_statement, $row);
				if ($total_destinations >= $_SESSION['limit']['destinations']['numeric']) {
					messages::add($text['message-maximum_destinations'].' '.$_SESSION['limit']['destinations']['numeric'], 'negative');
					header('Location: destinations_ext.php');
					return;
				}
			}
		}
	}

    if (count($_POST) > 0) {
        //set the variables
        $domain_uuid = escape(check_str($_POST["domain_uuid"]));
        $destination_ext_uuid = escape(check_str($_POST["destination_ext_uuid"]));
        $destination_ext_dialplan_main_uuid = escape(check_str($_POST["destination_ext_dialplan_main_uuid"]));
        $destination_ext_dialplan_main_details = $_POST["destination_ext_dialplan_main_details"];
        $destination_ext_dialplan_extensions_uuid = escape(check_str($_POST["destination_ext_dialplan_extensions_uuid"]));
        $destination_ext_dialplan_extensions_details = $_POST["destination_ext_dialplan_extensions_details"];
        $destination_ext_dialplan_invalid_details = $_POST["destination_ext_dialplan_invalid_details"];
        $destination_ext_number = escape(check_str($_POST["destination_ext_number"]));
        $db_destination_ext_number = escape(check_str($_POST["db_destination_ext_number"]));
        $destination_ext_variable = escape(check_str($_POST["destination_ext_variable"]));
        $destination_ext_enabled = escape(check_str($_POST["destination_ext_enabled"]));
        $destination_ext_description = escape(check_str($_POST["destination_ext_description"]));
        
        //convert the number to a regular expression
        $destination_ext_number_regex = string_to_regex($destination_ext_number);
    }
    unset($_POST["db_destination_ext_number"]);

    //$invalid_name_id = explode("-", $destination_ext_uuid)[0];
    $invalid_name_id = (strlen($destination_ext_uuid) == 0)?explode("-", $prev_generated_destination_ext_uuid)[0]:explode("-", $destination_ext_uuid)[0];

    //process the http post. Here we process UPDATE request and putting/updating info in database
    if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

        $destination_ext_dialplan_main_details_data = reset($destination_ext_dialplan_main_details)['dialplan_detail_data'];
        $destination_ext_dialplan_extensions_details_data = reset($destination_ext_dialplan_extensions_details)['dialplan_detail_data'];
        $destination_ext_dialplan_invalid_details_data = reset($destination_ext_dialplan_invalid_details)['dialplan_detail_data'];
        $msg = '';
        if (strlen($destination_ext_number) == 0) { $msg .= $text['message-required']." ".$text['label-destination_ext_number']."<br>\n"; }
        if (strlen($destination_ext_dialplan_main_details_data) == 0) { $msg .= $text['message-required']." ".$text['label-detail_action_main']."<br>\n"; }
        if (strlen($destination_ext_dialplan_extensions_details_data) == 0) { $msg .= $text['message-required']." ".$text['label-detail_action_ext']."<br>\n"; }
        if (strlen($destination_ext_dialplan_invalid_details_data) == 0) { $msg .= $text['message-required']." ".$text['label-detail_action_invalid']."<br>\n"; }

        //check for duplicates
        if ($destination_ext_number != $db_destination_ext_number) {
            $sql = "SELECT ";
            $sql .= "(SELECT count(*) AS num_rows FROM v_destinations ";
            $sql .= "WHERE destination_number = '".$destination_ext_number."' ) + ";
            $sql .= "(SELECT count(*) FROM v_destinations_ext ";
            $sql .= "WHERE destination_ext_number = '".$destination_ext_number."') ";
            $sql .= "AS num_rows";
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

            $destination_ext_domain = $_SESSION['domains'][$domain_uuid]['domain_name'];            

            foreach ($destination_ext_dialplan_invalid_details as $row) {
                if (strlen($row["dialplan_detail_data"]) > 0) {
                    $add_dialplan_invalid = true;
                    break;
                }
            }

            //add or update the main dialplan part if the destination number is set

            // Adding invalid handler dialplan part
            if ($add_dialplan_invalid) {

                require_once "app/destinations_ext/includes/add_dialplan_invalid.php";

            }
        // End of invalid handler part -----------------

            //determine whether save the main dialplan
            foreach ($destination_ext_dialplan_main_details as $row) {
                if (strlen($row["dialplan_detail_data"]) > 0) {
                    $add_dialplan_main = true;
                    break;
                }
            }

            //add or update the main dialplan part if the destination number is set
            if ($add_dialplan_main) {

                require_once "app/destinations_ext/includes/add_dialplan_main.php";

            } else {
                //add or update the dialplan if the destination number is set 
                //remove empty dialplan details from POST array so doesn't attempt to insert below
                unset($_POST["destination_ext_dialplan_main_details"]);
            }

            //get the destination_uuid
            if (strlen($dialplan_response['uuid']) > 0) {
                $_POST["destination_ext_dialplan_main_uuid"] = $dialplan_response['uuid'];
                $destination_ext_dialplan_main_uuid = $dialplan_response['uuid'];
            }
            unset($dialplan_response);
            unset($dialplan);
            unset($dialplan_uuid);

        // End of main part -----------------------------

            foreach ($destination_ext_dialplan_extensions_details as $row) {
                if (strlen($row["dialplan_detail_data"]) > 0) {
                    $add_dialplan_extensions = true;
                    break;
                }
            }

            //add or update the main dialplan part if the destination number is set
            if ($add_dialplan_extensions) {

                require_once "app/destinations_ext/includes/add_dialplan_extensions.php";

            } else {
                //add or update the dialplan if the destination number is set 
                //remove empty dialplan details from POST array so doesn't attempt to insert below
                unset($_POST["destination_ext_dialplan_extensions_details"]);
            }

            //get the destination_uuid
            if (strlen($dialplan_response['uuid']) > 0) {
                $_POST["destination_ext_dialplan_extensions_details"] = $dialplan_response['uuid'];
                $destination_ext_dialplan_extensions_uuid = $dialplan_response['uuid'];
            }
            unset($dialplan_response);
            unset($dialplan);
            unset($dialplan_uuid);

        // End of extensions part -----------------------

            if ($action == 'add') {
                $destination_ext_uuid = $prev_generated_destination_ext_uuid;
                $_POST['destination_ext_uuid'] = $destination_ext_uuid;
                $sql = "INSERT INTO v_destinations_ext (";
                $sql .= " domain_uuid,";
                $sql .= " destination_ext_uuid,";
                $sql .= " destination_ext_dialplan_main_uuid,";
                $sql .= " destination_ext_dialplan_extensions_uuid,";
                $sql .= " destination_ext_number,";
                $sql .= " destination_ext_variable,";
                $sql .= " destination_ext_enabled,";
                $sql .= " destination_ext_description";
                $sql .= ") VALUES (";
                $sql .= " '".$domain_uuid."',";
                $sql .= " '".$destination_ext_uuid."',";
                $sql .= " '".$destination_ext_dialplan_main_uuid."',";
                $sql .= " '".$destination_ext_dialplan_extensions_uuid."',";
                $sql .= " '".$destination_ext_number."',";
                $sql .= " '".$destination_ext_variable."',";
                $sql .= " '".$destination_ext_enabled."',";
                $sql .= " '".$destination_ext_description."')";

                //echo "<br>".$sql."<br>";
                $db->exec(check_sql($sql));
                unset($sql);
            } elseif ($action == 'update') {
                $_POST['destination_ext_uuid'] = $destination_ext_uuid;

                $sql = "UPDATE v_destinations_ext SET";
                $sql .= " destination_ext_dialplan_main_uuid = '".$destination_ext_dialplan_main_uuid."',";
                $sql .= " destination_ext_dialplan_extensions_uuid = '".$destination_ext_dialplan_extensions_uuid."',";
                $sql .= " destination_ext_number = '".$destination_ext_number."',";
                $sql .= " destination_ext_variable = '".$destination_ext_variable."',";
                $sql .= " destination_ext_enabled = '".$destination_ext_enabled."',";
                $sql .= " destination_ext_description = '".$destination_ext_description."'";
                $sql .= " WHERE destination_ext_uuid = '".$destination_ext_uuid."'";
                $sql .= " AND domain_uuid = '".$domain_uuid."'";

                $db->exec(check_sql($sql));
                unset($sql);
            } else {
                // Here should be some errors handler
                $all_vars = get_defined_vars();
                echo "Action is not add or update....<br>";
                echo "<pre>";
                var_dump($all_vars);
                echo "</pre>";
                exit(0);
            }

        //redirect the user
            if ($action == "add") {
                $_SESSION["message"] = $text['message-add'];
            }
            if ($action == "update") {
                $_SESSION["message"] = $text['message-update'];
            }
            header("Location: destination_ext_edit.php?id=".$destination_ext_uuid);
            return;
        } //if ($_POST["persistformvar"] != "true")
    } //(count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0)

//initialize the destinations object
    $destination = new destinations;

//pre-populate the form. Here we add in the form data
    // Ok, we're added one more query to database....


    if (strlen($destination_ext_uuid) > 0) {
        $sql = "SELECT * FROM v_destinations_ext ";
        $sql .= "WHERE domain_uuid = '".$domain_uuid."' ";
        $sql .= "AND destination_ext_uuid = '".$destination_ext_uuid."' LIMIT 1";

        $prep_statement = $db->prepare(check_sql($sql));
        $prep_statement->execute();
        $result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
        if (isset($result[0]["domain_uuid"])) {
            $domain_uuid = $result[0]["domain_uuid"];
            $destination_ext_uuid = $result[0]["destination_ext_uuid"];
            $destination_ext_dialplan_main_uuid = $result[0]["destination_ext_dialplan_main_uuid"];
            $destination_ext_dialplan_extensions_uuid = $result[0]["destination_ext_dialplan_extensions_uuid"];
            $destination_ext_number = $result[0]["destination_ext_number"];
            $destination_ext_variable = $result[0]["destination_ext_variable"];
            $destination_ext_enabled = $result[0]["destination_ext_enabled"];
            $destination_ext_description = $result[0]["destination_ext_description"];
        }
    }


    //get the main dialplan details in an array
    if (strlen($destination_ext_dialplan_main_uuid) > 0 or $action != "update") {
        $sql = "SELECT * FROM v_dialplan_details ";
        $sql .= "WHERE (domain_uuid = '".$domain_uuid."' OR domain_uuid is null) ";
        $sql .= "AND dialplan_uuid = '".$destination_ext_dialplan_main_uuid."' ";
        $sql .= "ORDER BY dialplan_detail_group ASC, dialplan_detail_order ASC";
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
    }


    // Get extensions dialplan details into array
    if (strlen($destination_ext_dialplan_extensions_uuid) > 0 or $action != "update") {
        $sql = "SELECT * FROM v_dialplan_details ";
        $sql .= "WHERE (domain_uuid = '".$domain_uuid."' OR domain_uuid IS NULL) ";
        $sql .= "AND dialplan_uuid = '".$destination_ext_dialplan_extensions_uuid."' ";
        $sql .= "OEDER BY dialplan_detail_group ASC, dialplan_detail_order ASC";
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
    }

    // Get invalid dialplan details into array
    $sql = "SELECT * FROM v_dialplan_details ";
    $sql .= "WHERE domain_uuid = '".$domain_uuid."' ";
    $sql .= "AND dialplan_uuid = (";
    $sql .= "SELECT dialplan_uuid FROM v_dialplans ";
    $sql .= "WHERE domain_uuid = '".$domain_uuid."' ";
    $sql .= "AND dialplan_name = '_invalid_ext_handler_".$invalid_name_id."'";
    $sql .= " LIMIT 1) ";
    $sql .= "ORDER BY dialplan_detail_group ASC, dialplan_detail_order ASC";
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
    echo "  <input type='button' class='btn' alt='".$text['button-back']."' onclick=\"window.location='destinations_ext.php'\" value='".$text['button-back']."'>";
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
    echo "  <input class='formfld' type='text' name='destination_ext_number' maxlength='255' value=\"" . escape($destination_ext_number) . "\" required='required'>\n";
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

        $row = array_map('escape', $row);

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
                echo                    "<a href='destination_ext_delete.php?id=".$row['destination_ext_uuid']."&destination_ext_uuid=".$row['destination_ext_uuid']."&a=delete' alt='delete' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>\n";
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

    if (sizeof($dialplan_details) == 1) {

        $domain_uuid = $dialplan_details[0]['domain_uuid'];
        $destination_ext_domain = $_SESSION['domains'][$domain_uuid]['domain_name'];

        $dialplan_details = array();
        $dialplan_details[1] = array();
        $dialplan_details[1]['dialplan_detail_data'] = "transfer:$1 XML " . escape($destination_ext_domain);
        $dialplan_details[1]['dialplan_detail_tag'] = "action";
    }

    foreach($dialplan_details as $row) {

        $row = array_map('escape', $row);

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
                echo                    "<a href='destination_ext_delete.php?id=".$row['destination_ext_uuid']."&destination_ext_uuid=".$row['destination_ext_uuid']."&a=delete' alt='delete' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>\n";
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

    if (sizeof($dialplan_details) == 1) {

        $domain_uuid = $dialplan_details[0]['domain_uuid'];
        $destination_ext_domain = $_SESSION['domains'][$domain_uuid]['domain_name'];

        $dialplan_details = array();
        $dialplan_details[1] = array();
        $dialplan_details[1]['dialplan_detail_data'] = "hangup";
        $dialplan_details[1]['dialplan_detail_tag'] = "action";
    }

    foreach($dialplan_details as $row) {

        $row = array_map('escape', $row);

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
                echo                    "<a href='destination_ext_delete.php?id=".$row['destination_ext_uuid']."&destination_ext_uuid=".$row['destination_ext_uuid']."&a=delete' alt='delete' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>\n";
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
            $row = array_map('escape', $row);

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
        echo "<input type='hidden' name='domain_uuid' value='" . escape($domain_uuid) ."'>\n";
    }

    echo "<tr>\n";
    echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
    echo "  ".$text['label-destination_ext_variable']."\n";
    echo "</td>\n";
    echo "<td class='vtable' align='left'>\n";
    echo "  <input class='formfld' type='text' name='destination_ext_variable' id='destination_ext_variable' maxlength='255' value=\"" . escape($destination_ext_variable) . "\">\n";
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
    echo "  <input class='formfld' type='text' name='destination_ext_description' maxlength='255' value=\"" . escape($destination_ext_description) . "\">\n";
    echo "<br />\n";
    echo $text['description-destination_ext_description']."\n";
    echo "</td>\n";
    echo "</tr>\n";
    echo "  <tr>\n";
    echo "      <td colspan='2' align='right'>\n";
    if ($action == "update") {
        echo "      <input type='hidden' name='db_destination_ext_number' value='" . escape($destination_ext_number) . "'>\n";
        echo "      <input type='hidden' name='destination_ext_dialplan_main_uuid' value='" . escape($destination_ext_dialplan_main_uuid) . "'>\n";
        echo "      <input type='hidden' name='destination_ext_dialplan_extensions_uuid' value='" . escape($destination_ext_dialplan_extensions_uuid) . "'>\n";
        echo "      <input type='hidden' name='destination_ext_uuid' value='" . escape($destination_ext_uuid) . "'>\n";
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
