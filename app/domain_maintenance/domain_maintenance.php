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

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('domain_maintenance_view')) {
		//access granted
	} else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add, update or load from server


    $c = 0;
    $row_style["0"] = "row_style0";
    $row_style["1"] = "row_style1";

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		//set the variables from the http values
		$domain_maintenance_keep_days_cdr = check_str($_POST[""]);
		$domain_maintenance_keep_days_cdr_global = check_str($_POST[""]);
		$domain_maintenance_keep_days_recordings = check_str($_POST[""]);
		$domain_maintenance_keep_days_recordings_global = check_str($_POST[""]);
		$domain_maintenance_keep_days_vm_messages = check_str($_POST[""]);
		$domain_maintenance_keep_days_vm_messages_global = check_str($_POST[""]);
		$domain_maintenance_keep_days_fax = check_str($_POST[""]);
		$domain_maintenance_keep_days_fax_global = check_str($_POST[""]);

		$domain_maintenance_keep_size_recordings = check_str($_POST[""]);
		$domain_maintenance_keep_size_recordings_global = check_str($_POST[""]);
		$domain_maintenance_keep_size_vm_messages = check_str($_POST[""]);
		$domain_maintenance_keep_size_vm_messages_global = check_str($_POST[""]);

	}

    if (isset($_REQUEST["change_done"])) {
		$action = "update"; 
	} else {
        $action = "show";
    }

	/*
    if (count($_POST) > 0) {

    	//add or update the database
    	if ($_POST["persistformvar"] != "true") {
            $e911_data = array(
                    'e911_did' => $e911_did,
                    'e911_address_1' => $e911_address_1,
                    'e911_address_2' => $e911_address_2,
                    'e911_city' => $e911_city,
                    'e911_state' => $e911_state,
                    'e911_zip' => $e911_zip,
                    'e911_zip_4' => $e911_zip_4,
                    'e911_callername' => $e911_callername,
                    'e911_alert_email' => $e911_alert_email,
                    'e911_alert_email_enable' => $e911_alert_email_enable,
                );

    		if ($action == "add" && permission_exists('e911_add')) {

    			//prepare the uuids
    			$e911_uuid = uuid();
    			//add the e911 info
    			$sql = "insert into v_e911 ";
    			$sql .= "(";
    			$sql .= "domain_uuid, ";
    			$sql .= "e911_uuid, ";
    			$sql .= "e911_did, ";
    			$sql .= "e911_address_1, ";
    			$sql .= "e911_address_2, ";
    			$sql .= "e911_city, ";
    			$sql .= "e911_state, ";
    			$sql .= "e911_zip, ";
    			$sql .= "e911_zip_4, ";
    			$sql .= "e911_callername, ";
    			$sql .= "e911_alert_email_enable, ";
    			$sql .= "e911_alert_email, ";
	                $sql .= "e911_validated";
    			$sql .= ") ";
    			$sql .= "values ";
    			$sql .= "(";
    			$sql .= "'$domain_uuid', ";
    			$sql .= "'".$e911_uuid."', ";
    			$sql .= "'$e911_did', ";
    			$sql .= "'$e911_address_1', ";
    			$sql .= "'$e911_address_2', ";
    			$sql .= "'$e911_city', ";
    			$sql .= "'$e911_state', ";
    			$sql .= "'$e911_zip', ";
    			$sql .= "'$e911_zip_4', ";
    			$sql .= "'$e911_callername', ";
    			$sql .= "'$e911_alert_email_enable', ";
    			$sql .= "'$e911_alert_email', ";
        	        $sql .= "'$e911_validated'";
    			$sql .= ")";
    			$db->exec(check_sql($sql));
                $sql_add = "ADD :".$sql;
    			unset($sql);
    		} //if ($action == "add")

    		if ($action == "update" && permission_exists('e911_edit')) {

                // Make api calls here
                if (!isset($_REQUEST["update_from_server"])) {
                    $e911_update_result = update_e911($e911_data);
                    $e911_alert_email_enable = $e911_update_result['e911_alert_email_enable'];
                    $e911_validated = $e911_update_result['e911_validated'];
                } // No need to call update API if it was update from server.
    			// update e911 info
    			$sql = "update v_e911 set ";
    			$sql .= "e911_did = '$e911_did', ";
    			$sql .= "e911_address_1 = '$e911_address_1', ";
    			$sql .= "e911_address_2 = '$e911_address_2', ";
    			$sql .= "e911_city = '$e911_city', ";
    			$sql .= "e911_state = '$e911_state', ";
    			$sql .= "e911_zip = '$e911_zip', ";
    			$sql .= "e911_zip_4 = '$e911_zip_4', ";
    			$sql .= "e911_callername = '$e911_callername', ";
                $sql .= "e911_alert_email_enable = '$e911_alert_email_enable', ";
    			$sql .= "e911_validated = '$e911_validated', ";
    			$sql .= "e911_alert_email = '$e911_alert_email' ";
    			$sql .= "where domain_uuid = '$domain_uuid' ";
    			$sql .= "and e911_uuid = '$e911_uuid'";
    			$db->exec(check_sql($sql));
                $sql_update = "UPDATE :".$sql;
    			unset($sql);
    		} //if ($action == "update")

    	} //if ($_POST["persistformvar"] != "true")
    } // if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0)

	*/

//pre-populate the form
	$sql = "select * from v_e911 ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and e911_uuid = '$e911_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll();
	foreach ($result as &$row) {
		//set the php variables
		$e911_did = $row["e911_did"];
		$e911_address_1 = $row["e911_address_1"];
		$e911_address_2 = $row["e911_address_2"];
		$e911_city = $row["e911_city"];
		$e911_state = $row["e911_state"];
		$e911_zip = $row["e911_zip"];
		$e911_zip_4 = $row["e911_zip_4"];
		$e911_callername = $row["e911_callername"];
		$e911_alert_email_enable = $row["e911_alert_email_enable"];
		$e911_alert_email = $row["e911_alert_email"];
		$e911_validated = $row["e911_validated"];
	}
	unset ($prep_statement);

//show the header
	require_once "resources/header.php";
	if ($action == "update") {
		$document['title'] = $text['title-e911-edit'];
	}
	if ($action == "add") {
		$document['title'] = $text['title-e911-add'];
	}

	if (if_group("superadmin")) {
		echo "<script>\n";
		echo "var Objs;\n";
		echo "\n";
		echo "function changeToInput(obj){\n";
		echo "	tb=document.createElement('INPUT');\n";
		echo "	tb.type='text';\n";
		echo "	tb.name=obj.name;\n";
		echo "	tb.setAttribute('class', 'formfld');\n";
		//echo "	tb.setAttribute('style', 'width: 380px;');\n";
		echo "	tb.value=obj.options[obj.selectedIndex].value;\n";
		echo "	tbb=document.createElement('INPUT');\n";
		echo "	tbb.setAttribute('class', 'btn');\n";
		echo "	tbb.setAttribute('style', 'margin-left: 4px;');\n";
		echo "	tbb.type='button';\n";
		echo "	tbb.value=$('<div />').html('&#9665;').text();\n";
		echo "	tbb.objs=[obj,tb,tbb];\n";
		echo "	tbb.onclick=function(){ Replace(this.objs); }\n";
		echo "	obj.parentNode.insertBefore(tb,obj);\n";
		echo "	obj.parentNode.insertBefore(tbb,obj);\n";
		echo "	obj.parentNode.removeChild(obj);\n";
		echo "}\n";
		echo "\n";
		echo "function Replace(obj){\n";
		echo "	obj[2].parentNode.insertBefore(obj[0],obj[2]);\n";
		echo "	obj[0].parentNode.removeChild(obj[1]);\n";
		echo "	obj[0].parentNode.removeChild(obj[2]);\n";
		echo "}\n";
		echo "</script>\n";
		echo "\n";
	}

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>";
	if ($action == "update") {
		echo $text['header-e911-edit'];
	}
	if ($action == "add") {
		echo $text['header-e911-add'];
	}
	echo "</b></td>\n";
	echo "<td width='70%' align='right'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='e911.php'\" value='".$text['button-back']."'>";
    //echo "  <input type='submit' class='btn' name='update_from_server' alt='".$text['button-update_911']."' onclick=\"window.location='e911_update_from_server.php'\" value='".$text['button-update_911']."'>";
    echo "  <input type='submit' name='update_from_server' class='btn' value='".$text['button-update_911']."'>\n";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";

/*
    // DID
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-e911_did']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='e911_did' maxlength='255' value=\"$e911_did\">\n";
	echo "<br />\n";
	echo $text['description-e911_did']."\n";
	echo "</td>\n";
	echo "</tr>\n";

*/

    echo "<tr>\n";
    echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
    echo "    ".$text['label-e911_did']."\n";
    echo "</td>\n";
    echo "<td class='vtable' align='left'>\n";
    if (count($destinations) > 0) {
        echo "  <select name='e911_did' id='e911_did' class='formfld'>\n";
        echo "  <option value=''></option>\n";
        foreach ($destinations as $row) {
			$row = array_map('escape', $row);

            $tmp = $row["destination_caller_id_number"];
            if(strlen($tmp) == 0){
                $tmp = $row["destination_number"];
            }
            if(strlen($tmp) > 0){
                if ($e911_did == $tmp) {
                    echo "      <option value='".$tmp."' selected='selected'>".$tmp."</option>\n";
                }
                else {
                    echo "      <option value='".$tmp."'>".$tmp."</option>\n";
                }
            }
        }
        echo "      </select>\n";
        echo "<br />\n";
        echo $text['description-e911_did']."\n";
    }
    else {
        echo "  <input type=\"button\" class=\"btn\" name=\"\" alt=\"".$text['button-add']."\" onclick=\"window.location='".PROJECT_PATH."/app/destinations/destinations.php'\" value='".$text['button-add']."'>\n";
    }
    unset ($prep_statement);
    echo "</td>\n";
    echo "</tr>\n";

    // Address 1
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-e911_address_1']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='e911_address_1' maxlength='255' value=\"" . escape($e911_address_1) . "\">\n";
	echo "<br />\n";
	echo $text['description-e911_address_1']."\n";
	echo "</td>\n";
	echo "</tr>\n";

    // Address 2
    echo "<tr>\n";
    echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
    echo "  ".$text['label-e911_address_2']."\n";
    echo "</td>\n";
    echo "<td class='vtable' align='left'>\n";
    echo "  <input class='formfld' type='text' name='e911_address_2' maxlength='255' value=\"" . escape($e911_address_2) . "\">\n";
    echo "<br />\n";
    echo $text['description-e911_address_2']."\n";
    echo "</td>\n";
    echo "</tr>\n";

    // City
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-e911_city']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='e911_city' maxlength='255' value=\"" . escape($e911_city) . "\">\n";
	echo "<br />\n";
	echo $text['description-e911_city']."\n";
	echo "</td>\n";
	echo "</tr>\n";

    // State
    echo "<tr>\n";
    echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
    echo "  ".$text['label-e911_state']."\n";
    echo "</td>\n";
    echo "<td class='vtable' align='left'>\n";
    echo "  <input class='formfld' type='text' name='e911_state' maxlength='255' value=\"" . escape($e911_state) . "\">\n";
    echo "<br />\n";
    echo $text['description-e911_state']."\n";
    echo "</td>\n";
    echo "</tr>\n";

    // Zip
    echo "<tr>\n";
    echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
    echo "  ".$text['label-e911_zip']."\n";
    echo "</td>\n";
    echo "<td class='vtable' align='left'>\n";
    echo "  <input class='formfld' type='text' name='e911_zip' maxlength='255' value=\"" . escape($e911_zip) . "\">\n";
    echo "<br />\n";
    echo $text['description-e911_zip']."\n";
    echo "</td>\n";
    echo "</tr>\n";


    // Zip+4
    echo "<tr>\n";
    echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
    echo "  ".$text['label-e911_zip_4']."\n";
    echo "</td>\n";
    echo "<td class='vtable' align='left'>\n";
    echo "  <input class='formfld' type='text' name='e911_zip_4' maxlength='255' value=\"" . escape($e911_zip_4) . "\">\n";
    echo "<br />\n";
    echo $text['description-e911_zip_4']."\n";
    echo "</td>\n";
    echo "</tr>\n";

    // Callername
    echo "<tr>\n";
    echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
    echo "  ".$text['label-e911_callername']."\n";
    echo "</td>\n";
    echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='e911_callername' maxlength='255' value=\"" . escape($e911_callername) . "\">\n";
    echo "<br />\n";
    echo $text['description-e911_callername']."\n";
    echo "</td>\n";
    echo "</tr>\n";

    // Alert email
    echo "<tr>\n";
    echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
    echo "  ".$text['label-e911_alert_email']."\n";
    echo "</td>\n";
    echo "<td class='vtable' align='left'>\n";
    echo "  <input class='formfld' type='text' name='e911_alert_email' maxlength='255' value=\"" . escape($e911_alert_email) . "\">\n";
    echo "<br />\n";
    echo $text['description-e911_alert_email']."\n";
    echo "</td>\n";
    echo "</tr>\n";

    // Alert email enable
    echo "<tr>\n";
    echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
    echo "    ".$text['label-e911_alert_email_enable']."\n";
    echo "</td>\n";
    echo "<td class='vtable' align='left'>\n";
    echo "    <select class='formfld' name='e911_alert_email_enable'>\n";
    if ($e911_alert_email_enable == "true") {
        echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
    }
    else {
        echo "    <option value='true'>".$text['label-true']."</option>\n";
    }
    if ($e911_alert_email_enable == "false") {
        echo "    <option value='false' selected >".$text['label-false']."</option>\n";
    }
    else {
        echo "    <option value='false'>".$text['label-false']."</option>\n";
    }
    echo "    </select>\n";
    echo "<br />\n";
    echo $text['description-e911_alert_email_enable']."\n";
    echo "</td>\n";
    echo "</tr>\n";

    // Validated
    echo "<tr>\n";
    echo "<td>\n";
    echo "<center><b>";
    if ($e911_validated == "") {
        echo "No information\n";
    } else {
        echo "$e911_validated\n";
    }
    echo "</b></center>";
    echo "<br />\n";
    echo "<br />\n";
//    echo $sql_update;
    echo "</td>\n";
    echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='e911_uuid' value='" . escape($e911_uuid) . "'>\n";
        echo "      <input type='hidden' name='e911_validated' value='" . escape($e911_validated) . "'>\n";
	}
	echo "			<br>";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//include the footer
	require_once "resources/footer.php";
?>
