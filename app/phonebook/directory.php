<?php
require_once "root.php";
require_once "resources/require.php";

$is_auth = isset($_SESSION['phonebook']['text']['auth']) ? isset($_SESSION['phonebook']['text']['auth']) : 'true';

if (strtolower($is_auth) != 'false') {
	// Check auth (adding more security)
	require_once "resources/check_auth.php";

	if (!permission_exists('phonebook_phone_access')) {
		echo "Access denied";
		exit;
	}
}

$groupid = isset($_REQUEST["gid"]) ? check_str($_REQUEST["gid"]) : 'global';
$vendor = isset($_REQUEST["vendor"]) ? strtolower(check_str($_REQUEST["vendor"])) : 'yealink';

$sql = "select * from v_phonebook_details INNER JOIN v_phonebook ON v_phonebook_details.phonebook_uuid = v_phonebook.phonebook_uuid ";
$sql .= "where v_phonebook.domain_uuid = '".$_SESSION['domain_uuid']."' ";

$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll();
$result_count = count($result);
unset ($prep_statement, $sql);

if ($vendor == 'yealink') {

	$response = '<PhonebookIPPhoneDirectory>';

	foreach($result as $row) {
		$exploded = explode(',', $row['contact_group']);
		if(in_array($groupid, $exploded)){
			$response .= '  <DirectoryEntry>';
			$response .= '    <Name>'.$row['company_name'].'</Name>';
			$response .= '    <Telephone>'.$row['phonenumber'].'</Telephone>';
			$response .= '  </DirectoryEntry>';
		}

	}

	$response .= '</PhonebookIPPhoneDirectory>';

	header("Content-type: text/xml; charset=utf-8");
	header("Content-Length: ".strlen($response));

	echo $response;
}

?>