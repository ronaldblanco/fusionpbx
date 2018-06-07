<?php
require_once "root.php";
require_once "resources/require.php";

if (isset($_REQUEST["gid"])) {
        $groupid = check_str($_REQUEST["gid"]);
}
else{
	$groupid = 'Global';
}
$sql = "select * from v_phonebook_details INNER JOIN v_phonebook ON v_phonebook_details.phonebook_uuid = v_phonebook.phonebook_uuid ";
$sql .= "where v_phonebook.domain_uuid = '".$_SESSION['domain_uuid']."' ";
$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll();
$result_count = count($result);
unset ($prep_statement, $sql);
$response = '<PhonebookIPPhoneDirectory>';

foreach($result as $row){
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
