<?php 

//restrict to command line only
	if(defined('STDIN')) {
		$document_root = str_replace("\\", "/", $_SERVER["PHP_SELF"]);
		preg_match("/^(.*)\/app\/.*$/", $document_root, $matches);
		$document_root = $matches[1];
		set_include_path($document_root);
		include "root.php";
		require_once "resources/require.php";
		require_once "resources/classes/text.php";
		$_SERVER["DOCUMENT_ROOT"] = $document_root;
		$format = 'text'; //html, text
	
		//add multi-lingual support
		$language = new text;
		$text = $language->get();
	}
	else {
		die('access denied');
	}

	set_time_limit(55); // Cannot run more than 55 seconds

	$current_minute = (int)date('i');

	$sql = "SELECT v_domains.domain_name AS context,";
	$sql .= " school_bell_leg_a_data AS extension,";
	$sql .= " school_bell_leg_b_data AS app,";
	$sql .= " school_bell_ring_timeout AS ring_timeout,";
	$sql .= " school_bell_hour as hour,";
	$sql .= " school_bell_dom as dom,";
	$sql .= " school_bell_mon as mon,";
	$sql .= " school_bell_timezone as timezone ";
	$sql .= "FROM v_school_bells ";
	$sql .= "JOIN v_domains ON v_domains.domain_uuid = v_school_bells.domain_uuid ";
	$sql .= " WHERE school_bell_min = :current_minute";
	$sql .= " OR school_bell_min = -1";
	
	$prep_statement = $db->prepare(check_sql($sql));
	if (!$prep_statement) {
		die('SQL forming error');
	}
	$prep_statement->bindValue('current_minute', $current_minute);
	
	if (!$prep_statement->execute()) {
		die('SQL execute error');
	}

	$school_bells = $prep_statement->fetchAll(PDO::FETCH_NAMED);

	if (count($school_bells) == 0) {
		return;
	}

	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if (!$fp) {
		die("Cannot connect to Event socket");
	}

	$switch_result = event_socket_request($fp, 'api sofia status');
	print($switch_result);

//	foreach ($school_bells as $school_bell) {
//
//	}

?>
