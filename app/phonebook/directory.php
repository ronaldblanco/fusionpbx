<?php
require_once "root.php";
require_once "resources/require.php";

$is_auth = isset($_SESSION['phonebook']['auth']['text']) ? filter_var($_SESSION['phonebook']['auth']['text'], FILTER_VALIDATE_BOOLEAN) : 'true';
$groupid = isset($_REQUEST["gid"]) ? check_str($_REQUEST["gid"]) : False;
$vendor  = isset($_REQUEST["vendor"]) ? strtolower(check_str($_REQUEST["vendor"])) : 'yealink';

if ($is_auth) {
	// Check auth (adding more security)
	require_once "resources/check_auth.php";

	if (!permission_exists('phonebook_phone_access')) {
		echo "Access denied";
		exit;
	}
	
} else {
	if (!$groupid) {
		// Can't get all of phonebook without specifying auth.
		echo "Access denied";
		exit;
	}
}

if ($groupid) {
    $sql = "SELECT DISTINCT v_phonebook.phonebook_uuid,";
    $sql .= " v_phonebook.name, v_phonebook.phonenumber, v_phonebook.phonebook_desc FROM v_phonebook ";
    $sql .= " INNER JOIN v_phonebook_to_groups ON";
    $sql .= " v_phonebook.phonebook_uuid = v_phonebook_to_groups.phonebook_uuid";
    $sql .= " WHERE v_phonebook.domain_uuid = '$domain_uuid'";
    $sql .= " AND v_phonebook_to_groups.group_uuid = '$groupid'";
} else {
    $sql = "SELECT name, phonenumber, phonebook_desc";
    $sql .= " FROM v_phonebook";
    $sql .= " WHERE domain_uuid = '$domain_uuid'";
}

$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll();
unset ($prep_statement, $sql);

$response = '';

if ($vendor == 'yealink') {

	$response .= '<PhonebookIPPhoneDirectory>' . "\n";

	foreach($result as $row) {
		$response .= '  <DirectoryEntry>' . "\n";
		$response .= '    <Name>' . $row['name'] . '</Name>' . "\n";
		$response .= '    <Telephone>' . $row['phonenumber'] . '</Telephone>' . "\n";
		$response .= '  </DirectoryEntry>' . "\n";
	}

	$response .= '</PhonebookIPPhoneDirectory>' . "\n";

	// End Yealink phonebook
} elseif ($vendor == 'snom') {

	$snom_embedded_settings = isset($_SESSION['phonebook']['snom_embedded_settings']['text']) ? filter_var($_SESSION['phonebook']['snom_embedded_settings']['text'], FILTER_VALIDATE_BOOLEAN) : 'true';
	if (!$snom_embedded_settings) {
		$response .= '<?xml version="1.0" encoding="utf-8"?>' . "\n";
	}

	$response .= '<tbook complete="true">' . "\n";

	foreach ($result as $index => $value) {
		$response .= '<item context="active" type="none" index="'. $index .'">' . "\n";
		$response .= '<name>' . $value['name'] . '</name>' . "\n";
		$response .= '<number>' . $value['phonenumber'] . '</number>' . "\n";
		$response .= '</item>' . "\n";
	}

	$response .= '</tbook>' . "\n";

} elseif ($vendor == 'cisco_xml_directory_service') {
	$response .= '<?xml version="1.0" encoding="utf-8" ?>' . "\n";
	$response .= ' <CiscoIPPhoneDirectory>' . "\n";
   	$response .= '  <Title>Phonebook</Title>' . "\n";
   	$response .= '  <Prompt>Choose entry</Prompt>' . "\n";
	foreach($result as $row) {
		$response .= '    <DirectoryEntry>' . "\n";
		$response .= '     <Name>' . $row['name'] . '</Name>' . "\n";
		$response .= '     <Telephone>' . $row['phonenumber'] . '</Telephone>' . "\n";
		$response .= '    </DirectoryEntry>' . "\n";
	}
	$response .= ' </CiscoIPPhoneDirectory>' . "\n";
} elseif ($vendor == 'cisco_paddrbook') {

    $response .= '<paddrbook>' . "\n";
    foreach($result as $row) {
        $response .= ' <entry>' . "\n";
        $response .= '  <name>' . $row['name'] . '<name>' . "\n";
        $response .= '  <workPhone>' . $row['phonenumber'] . '<workPhone>' . "\n";
        $response .= '  <ringToneID>1</ringToneID>' . "\n";
        $response .= ' </entry>' . "\n";
    }
    $response .= '</paddrbook>' . "\n";
}


header("Content-type: text/xml; charset=utf-8");
header("Content-Length: ".strlen($response));

echo $response;

?>