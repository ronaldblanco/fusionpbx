<?php

	//application details
		$apps[$x]['name'] = "BDR Management";
		$apps[$x]['uuid'] = "399cd584-e2eb-4993-afa9-4292593600a3";
		$apps[$x]['category'] = "Switch";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "A tool to manage BDR.";
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
		$apps[$x]['permissions'][$y]['name'] = "bdr_status_view";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "7ed61deb-14dd-49f6-940d-6cf2023f8481";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "bdr_status_delete";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		
		//default settings
		$y = 0;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = '44fe8455-6c5c-4792-a2b7-f3827f32b520';
		$apps[$x]['default_settings'][$y]['default_setting_category'] = 'server';
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = 'bdr_fusionpbx_enable';
		$apps[$x]['default_settings'][$y]['default_setting_name'] = 'boolean';
		$apps[$x]['default_settings'][$y]['default_setting_value'] = 'true';
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = 'false';
		$apps[$x]['default_settings'][$y]['default_setting_description'] = 'BDR status on FusionPBX database';
		$y++;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = '445e8455-7c5c-a792-a2b7-f3d27f32b520';
		$apps[$x]['default_settings'][$y]['default_setting_category'] = 'server';
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = 'bdr_freeswitch_enable';
		$apps[$x]['default_settings'][$y]['default_setting_name'] = 'boolean';
		$apps[$x]['default_settings'][$y]['default_setting_value'] = 'true';
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = 'false';
		$apps[$x]['default_settings'][$y]['default_setting_description'] = 'BDR status on FreeSWITCH database';
?>