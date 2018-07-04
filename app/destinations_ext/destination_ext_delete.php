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
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

if (permission_exists('destinations_ext_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

if (count($_GET)>0) {
	$id = escape(check_str($_GET["id"]));
}

$message = $text['message-delete'];

if (strlen($id) > 0) {

	// First - select corresponding routes
	$sql = "SELECT destination_ext_uuid, destination_ext_dialplan_main_uuid, destination_ext_dialplan_extensions_uuid"; 
	$sql .= " FROM v_destinations_ext WHERE destination_ext_uuid = '".$id."'";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetch(PDO::FETCH_ASSOC);

	if (isset($result['destination_ext_uuid'])) {

		$destination_ext_uuid = $result['destination_ext_uuid'];
		$destination_ext_dialplan_main_uuid = isset($result['destination_ext_dialplan_main_uuid'])?$result['destination_ext_dialplan_main_uuid']:"";
		$destination_ext_dialplan_extensions_uuid = isset($result['destination_ext_dialplan_extensions_uuid'])?$result['destination_ext_dialplan_extensions_uuid']:"";

		$db->beginTransaction();

		// Delete dialplan_details for main and extensions

		$sql = "DELETE FROM v_dialplan_details WHERE";
		$sql .= " dialplan_uuid = '".$destination_ext_dialplan_main_uuid."'";
		$sql .= " OR dialplan_uuid = '".$destination_ext_dialplan_extensions_uuid."'";

		$db->exec(check_sql($sql));
		unset($sql);

		// Delete dialplans for main and ext

		$sql = "DELETE FROM v_dialplans WHERE";
		$sql .= " dialplan_uuid = '".$destination_ext_dialplan_main_uuid."'";
		$sql .= " OR dialplan_uuid = '".$destination_ext_dialplan_extensions_uuid."'";

		$db->exec(check_sql($sql));
		unset($sql);

		// Delete v_destinations_ext
		$sql = "DELETE FROM v_destinations_ext WHERE";
		$sql .= " destination_ext_uuid = '".$destination_ext_uuid."'";

		$db->exec(check_sql($sql));
		unset($sql);

		$db->commit();

		save_dialplan_xml();

		$cache = new cache;
		$cache->delete("dialplan: public");
	}

}
$message = $text['message-delete'];

$_SESSION["message"] = $message;
header("Location: destinations_ext.php");
return;

?>
