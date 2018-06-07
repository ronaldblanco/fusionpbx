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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>

	Call Block is written by Gerrit Visser <gerrit308@gmail.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('phonebook_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the variable
	if (count($_GET)>0) {
		$id = $_GET["id"];
	}

//delete the extension
	if (strlen($id)>0) {
		//Check if this is the only number  for this company and if it is delete the company too
                        $sql = "select phonebook_uuid from v_phonebook_details ";
                        $sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
                        $sql .= "and phonebook_detail_uuid = '$id' ";
                        $prep_statement = $db->prepare(check_sql($sql));
                        $prep_statement->execute();
			$result = $prep_statement->fetchAll();
			$phonebook_uuid = $result[0][phonebook_uuid];
                        unset($prep_statement, $sql);
                        $sql = "select count(phonebook_uuid) from v_phonebook_details ";
                        $sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
                        $sql .= "and phonebook_uuid = '$phonebook_uuid' ";
                        $prep_statement = $db->prepare(check_sql($sql));
                        $prep_statement->execute();
			$result = $prep_statement->fetchAll();
                        unset($prep_statement, $sql);
			if ($result[0]['count'] == 1){
	                //delete the company
                        $sql = "delete from v_phonebook ";
                        $sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
                        $sql .= "and phonebook_uuid = '$phonebook_uuid' ";
                        $prep_statement = $db->prepare(check_sql($sql));
                        $prep_statement->execute();
                        //unset($prep_statement, $sql);
			}

		//delete the number
			$sql = "delete from v_phonebook_details ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and phonebook_detail_uuid = '$id' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			unset($prep_statement, $sql);

	}

	//redirect the browser
		$_SESSION["message"] = $text['label-delete-complete'];
		header("Location:  phonebook.php");
		return;

?>
