<style>
	#groupField{
		height: auto;
	}
</style>
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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>

	Call Block is written by Gerrit Visser <gerrit308@gmail.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

if (permission_exists('phonebook_edit') || permission_exists('phonebook_add')) {
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
	$phonebook_detail_uuid = check_str($_REQUEST["id"]);
}
elseif (isset($_REQUEST["cid"])) {
	$action = "add";
	$company_name = check_str($_REQUEST["cid"]);
	$phonebook_uuid = check_str($_REQUEST["did"]);
}
else {
	$action = "add";
}

//get http post variables and set them to php variables
if (count($_POST) > 0) {
	$company_name = check_str($_POST["company_name"]);
	$phonenumber = check_str($_POST["phonenumber"]);
	$phonebook_uuid = check_str($_POST["phonebook_uuid"]);

}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {
	$msg = '';
	//check for all required data
	if (strlen($company_name) == 0) { $msg .= $text['label-provide-name']."<br>\n"; }
	if ($action == "add") {
		if (strlen($phonenumber) == 0) { $msg .= $text['label-provide-number']."<br>\n"; }
	}
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
		
	if(!is_array($_POST['contact_group'])){
		$cg = array();
		$cg[0] = '';
		$tmp = 	$_POST['contact_group'].",";
		$tmp = ucfirst(strtolower($tmp));
		$cg[0] = ucfirst(strtolower($cg[0]));
		foreach ($_POST['null'] as $key => $value) {
			$value = strtolower($value);
			$value = ucfirst($value);
			$cg[0] .= $value.",";
		}
		$cg[0] .= $tmp;
	}
	else{
		$cg = $_POST['contact_group'];
	}
	foreach($cg as $cgroup){
		$contact_group .= $cgroup.",";
	}
	// remove trailing comma
	$contact_group = rtrim($contact_group, ",");
	// remove whitspace
	$contact_group = str_replace(' ', '', $contact_group);
	// add space after comma
	
	// check if a group is being added
	if(strlen($contact_group) == 0){
		$contact_group = "Global";
	}
	//add or update the database
	if (($_POST["persistformvar"] != "true")>0) {
		if ($action == "add") {
			if (!(strlen($_REQUEST["cid"]) > 0)){ 
				$phonebook_uuid = uuid();
	            $sql = "insert into v_phonebook ";
	            $sql .= "(";
	            $sql .= "domain_uuid, ";
	            $sql .= "phonebook_uuid, ";
	            $sql .= "company_name";
	            $sql .= ") ";
	            $sql .= "values ";
	            $sql .= "(";
	            $sql .= "'".$_SESSION['domain_uuid']."', ";
	            $sql .= "'$phonebook_uuid', ";
	            $sql .= "'$company_name'";
	            $sql .= ")";
	            $db->exec(check_sql($sql));
				unset($sql);
			}
			$sql = "insert into v_phonebook_details ";
            $sql .= "(";
            $sql .= "domain_uuid, ";
			$sql .= "phonebook_uuid, ";
            $sql .= "phonebook_detail_uuid, ";
            $sql .= "phonenumber,";
			$sql .= "contact_group";
            $sql .= ") ";
            $sql .= "values ";
            $sql .= "(";
            $sql .= "'$domain_uuid', ";
            $sql .= "'$phonebook_uuid', ";
            $sql .= "'".uuid()."', ";
            $sql .= "'$phonenumber',";
     		$sql .= "'$contact_group'";
            $sql .= ")";
			$db->exec(check_sql($sql));

			$_SESSION["message"] = $text['label-add-complete'];
			header("Location: phonebook.php");
			return;
		}

		if ($action == "update") {
            $sql = " select v_phonebook.company_name, v_phonebook.domain_name from v_phonebook ";
            $sql  .= "INNER JOIN v_phonebook ON v_phonebook_details.phonebook_uuid = v_phonebook.phonebook_uuid ";
            $sql .= "where v_phonebook..domain_uuid = '".$_SESSION['domain_uuid']."' ";
            $sql .= "and v_phonebook_details.phonebook_uuid = '$phonebook_uuid'";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll();
			$result_count = count($result);
			if ($result_count > 0) {
				//set the domain_name
				$domain_name = $result[0]["domain_name"];
			}
			unset ($prep_statement, $sql);
			$sql = "update v_phonebook set ";
			$sql .= "company_name = '$company_name' ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and phonebook_uuid = '$phonebook_uuid'";
			$db->exec(check_sql($sql));
			unset($sql);
            $sql = "update v_phonebook_details set ";
			$sql .= "phonenumber = '$phonenumber', ";
			$sql .= "contact_group = '$contact_group' ";
            $sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and phonebook_detail_uuid = '$phonebook_detail_uuid'";
			$db->exec(check_sql($sql));
			unset($sql);
			$_SESSION["message"] = $text['label-update-complete'];
			header("Location: phonebook.php");
			return;
			} 
		}
	}

	//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		if (!(strlen($_REQUEST["cid"]) > 0)){
			$phonebook_uuid = $_GET["id"];
		}
		$sql = "select v_phonebook.phonebook_uuid,company_name,phonenumber,contact_group from v_phonebook_details INNER JOIN v_phonebook ON v_phonebook_details.phonebook_uuid = v_phonebook.phonebook_uuid ";
		$sql .= "where v_phonebook.domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and v_phonebook_details.phonebook_detail_uuid = '$phonebook_detail_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll();

		foreach ($result as &$row) {
            $phonebook_uuid = $row["phonebook_uuid"];
			$company_name = $row["company_name"];
			$phonenumber = $row["phonenumber"];
			$contact_groups = $row["contact_group"];
			$contact_groups = explode(',',$contact_groups);
			break; //limit to 1 row
		}
		unset ($prep_statement, $sql);
	}

//show the header
	require_once "resources/header.php";

// retrieve groups
	$sql = "select distinct on (contact_group) contact_group from v_phonebook_details where domain_uuid = '".$_SESSION['domain_uuid']."'";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$groupsArray = $prep_statement->fetchAll(PDO::FETCH_ASSOC);

	foreach($groupsArray as $groupsArrayRow){
	$mystring .= $groupsArrayRow['contact_group'].",";
	}
        $mystring = rtrim($mystring, ",");
	$myarray = explode(',', $mystring);
	$myarray = array_unique($myarray);

	//show the content
	if (permission_exists('phonebook_group_add')){
	// PERMISSION
	}

	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($action == "add") {
		if($company_name){
			echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['phonebook-duplicate-title'].$company_name."</b></td>\n";
		}
		else{
			echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['phonebook-add-title']."</b></td>\n";
		}
	}

	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['phonebook-edit-title']." ".$company_name."</b></td>\n";
	}

	echo "<td width='70%' align='right'>";
	echo "	<input type='submit' id='saveGroup' class='btn' name='submit' style='display: none;' alt='Save Group' form='groupForm' value='Save Group'>";
	echo "	<input type='button' id='addGroup' class='btn' name='' alt='".$text['button-add-group']."' onclick=\"window.location=' phonebook.php\" value='".$text['button-add-group']."'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location=' phonebook.php'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	if ($action == "add") {
		if($company_name){
			echo $text['phonebook-duplicate-desc']."<br /><br />\n";
		}
		else{
			echo $text['phonebook-add-desc']."<br /><br />\n";
		}
	}
	if ($action == "update") {
		echo $text['phonebook-edit-desc']."<br /><br />\n";
	}
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='company_name' maxlength='255' value=\"$company_name\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

    echo "<tr>\n";
    echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
    echo "  ".$text['label-number']."\n";
    echo "</td>\n";
    echo "<td class='vtable' align='left'>\n";
    echo "  <input class='formfld' type='text' name='phonenumber' maxlength='255' value=\"$phonenumber\" required='required'>\n";
    echo "<br />\n";
    echo $text['description-number']."\n";
    echo "<br />\n";
    echo "</td>\n";
    echo "</tr>\n";

    echo "<tr>\n";
    echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
    echo "  ".$text['label-group']."\n";
    echo "</td>\n";
    echo "<td class='vtable' align='left'>\n";
    echo "	<input type='text' name='addGroupField' id='addGroupField' class='formfld' style='display: none; min-width: 200px;' placeholder='New Group Name...'>";
    echo "<select multiple id='groupField' class='formfld' name='contact_group[]' maxlength='255' value=\"$phonenumber\">\n";

    foreach($myarray as $group){
	if (in_array($group,$contact_groups)){
		$selected = "selected=selected";
	}
	else{
		$selected ='';
	}
    	echo "<option $selected>".$group."</option>";
    }

    echo "</select>";
    echo "<br />\n";
    echo $text['description-group']."\n";
    echo "<br />\n";
    echo "</td>\n";
    echo "</tr>\n";

	echo "<tr>\n";
	echo "<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "<input type='hidden' name='phonebook_uuid' value='$phonebook_uuid'>\n";
	}
	if ((strlen($_REQUEST["cid"]) > 0) and ($action == "add")){
            echo "<input type='hidden' name='phonebook_uuid' value='$phonebook_uuid'>\n";
        }
	echo "<br>";
	echo "<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>";
	echo "</table>";
	echo "<br><br>"; 
	echo "</form>";



//include the footer
	require_once "resources/footer.php";
?>

<script>
$(document).ready(function(){
    $("#addGroup").click(function(){
    	$("#groupField").hide();
        $("#addGroupField").slideToggle(15);
        $('#addGroupField').attr('name', 'contact_group');
        $('#groupField').attr('name', 'null[]');
        $('#addGroup').slideToggle(15);
        /* $("#saveGroup").show();*/
    });
});
</script>
