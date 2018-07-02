<?php

	//application details
		$apps[$x]['name'] = "Phonebook";
		$apps[$x]['uuid'] = "63dadfd0-ec76-11e6-b006-92361f002671";
		$apps[$x]['category'] = "Switch";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "A tool to implement a Phonebook.";
		$apps[$x]['description']['es-cl'] = "A tool to implement a Phonebook.";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['fr-fr'] = "A tool to implement a Phonebook.";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "A tool to implement a Phonebook.";
		$apps[$x]['description']['pt-br'] = "A tool to implement a Phonebook.";

	//permission details
		$y = 0;
		$apps[$x]['permissions'][$y]['name'] = "phonebook_view";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "54e525be-ec76-11e6-b006-92361f002671";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "phonebook_add";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "phonebook_edit";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "phonebook_delete";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
                $y++;
                $apps[$x]['permissions'][$y]['name'] = "phonebook_group_add";
                $apps[$x]['permissions'][$y]['groups'][] = "superadmin";
                $apps[$x]['permissions'][$y]['groups'][] = "admin";
                $y++;
                $apps[$x]['permissions'][$y]['name'] = "phonebook_group_edit";
                $apps[$x]['permissions'][$y]['groups'][] = "superadmin";
                $apps[$x]['permissions'][$y]['groups'][] = "admin";
                $y++;
                $apps[$x]['permissions'][$y]['name'] = "phonebook_group_delete";
                $apps[$x]['permissions'][$y]['groups'][] = "superadmin";
                $apps[$x]['permissions'][$y]['groups'][] = "admin";
                $y++;
                $apps[$x]['permissions'][$y]['name'] = "phonebook_import";
                $apps[$x]['permissions'][$y]['groups'][] = "superadmin";
                $apps[$x]['permissions'][$y]['groups'][] = "admin";
                $y++;
                $apps[$x]['permissions'][$y]['name'] = "phonebook_phone_access";
                $apps[$x]['permissions'][$y]['groups'][] = "superadmin";
                $apps[$x]['permissions'][$y]['groups'][] = "admin";
                $y++;

	//schema details
                $y = 0; //table array index
                $z = 0; //field array index

                // Main table with entries
                $apps[$x]['db'][$y]['table'] = "v_phonebook";
                $apps[$x]['db'][$y]['fields'][$z]['name'] = "domain_uuid";
                $apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
                $apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
                $apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
                $apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "foreign";
                $apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = "v_domains";
                $apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = "domain_uuid";
                $apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
                $z++;
                $apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "phonebook_uuid";
                $apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
                $apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
                $apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
                $apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "primary";
                $z++;
                $apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "name";
                $apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
                $apps[$x]['db'][$y]['fields'][$z]['description']['en'] = "Enter the name.";
                $apps[$x]['db'][$y]['fields'][$z]['description']['pt-br'] = "Enter the name.";
                $z++;
                $apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "phonenumber";
                $apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
                $apps[$x]['db'][$y]['fields'][$z]['description']['en'] = "Enter the phone number.";
                $apps[$x]['db'][$y]['fields'][$z]['description']['pt-br'] = "Insira o número de telefone.";
                $z++;
                $apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "phonebook_desc";
                $apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
                $apps[$x]['db'][$y]['fields'][$z]['description']['en'] = "Enter the description.";
                $apps[$x]['db'][$y]['fields'][$z]['description']['pt-br'] = "Enter the description.";

                $y = 1; //table array index
                $z = 0; //field array index
                // Groups description
                $apps[$x]['db'][$y]['table'] = "v_phonebook_groups";
                $apps[$x]['db'][$y]['fields'][$z]['name'] = "domain_uuid";
                $apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
                $apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
                $apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
                $apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "foreign";
                $apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = "v_domains";
                $apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = "domain_uuid";
                $apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
                $z++;
                $apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "group_uuid";
                $apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
                $apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
                $apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
                $apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "primary";
                $z++;
                $apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "group_name";
                $apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
                $apps[$x]['db'][$y]['fields'][$z]['description']['en'] = "Enter the name.";
                $apps[$x]['db'][$y]['fields'][$z]['description']['pt-br'] = "Enter the name.";
                $z++;
                $apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "group_desc";
                $apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
                $apps[$x]['db'][$y]['fields'][$z]['description']['en'] = "Enter the description.";
                $apps[$x]['db'][$y]['fields'][$z]['description']['pt-br'] = "Enter the description.";


                $y = 2; //table array index
                $z = 0; //field array index
                // Link between groups and phonebook entries. Classic many-to-many
                $apps[$x]['db'][$y]['table'] = "v_phonebook_to_groups";
                $apps[$x]['db'][$y]['fields'][$z]['name'] = "domain_uuid";
                $apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
                $apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
                $apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
                $apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "foreign";
                $apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = "v_domains";
                $apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = "domain_uuid";
                $apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
                $z++;
                $apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "phonebook_uuid";
                $apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
                $apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
                $apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
                $apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "foreign";
                $apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = "v_phonebook";
                $apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = "phonebook_uuid";
                $z++;
                $apps[$x]['db'][$y]['fields'][$z]['name']['text'] = "group_uuid";
                $apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
                $apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
                $apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
                $apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "foreign";
                $apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = "v_phonebook_groups";
                $apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = "group_uuid";
?>
