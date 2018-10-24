<?php

//application details
	$apps[$x]['name'] = "Domain Maintenance";
	$apps[$x]['uuid'] = "f6e5dcdc-f885-430e-841a-87201fb52b88";
	$apps[$x]['category'] = "Switch";
	$apps[$x]['subcategory'] = "";
	$apps[$x]['version'] = "";
	$apps[$x]['license'] = "Mozilla Public License 1.1";
	$apps[$x]['url'] = "http://www.fusionpbx.com";
	$apps[$x]['description']['en-us'] = "Provides cleanup for space (recordings/database) on automated level";
	$apps[$x]['description']['es-cl'] = "";
	$apps[$x]['description']['de-de'] = "";
	$apps[$x]['description']['de-ch'] = "";
	$apps[$x]['description']['de-at'] = "";
	$apps[$x]['description']['fr-fr'] = "";
	$apps[$x]['description']['fr-ca'] = "";
	$apps[$x]['description']['fr-ch'] = "";
	$apps[$x]['description']['pt-pt'] = "";
	$apps[$x]['description']['pt-br'] = "";

//permission details
	$y = 0;
	$apps[$x]['permissions'][$y]['name'] = "domain_maintenance_view";
	$apps[$x]['permissions'][$y]['menu']['uuid'] = "8b414bc2-a64b-4c20-b220-9b3e7bb0b452";
	$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
	$apps[$x]['permissions'][$y]['groups'][] = "admin";
	$y++;
	$apps[$x]['permissions'][$y]['name'] = "domain_maintenance_change";
	$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
	$apps[$x]['permissions'][$y]['groups'][] = "admin";
	$y++;
	$apps[$x]['permissions'][$y]['name'] = "domain_maintenance_change_global";
	$apps[$x]['permissions'][$y]['groups'][] = "superadmin";

?>