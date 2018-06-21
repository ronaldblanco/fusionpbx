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
	Jamie Sorensen <jamie@directvoip.co.uk>
*/
require_once "root.php";
require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('phonebook_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

	echo "<script language='JavaScript' type='text/javascript'>\n";

	echo "  function check_filetype(file_input) {\n";
	echo "          file_ext = file_input.value.substr((~-file_input.value.lastIndexOf('.') >>> 0) + 2);\n";
	echo "          if (file_ext != 'xml' && file_ext != '') {\n";
	echo "                  display_message(\"".$text['message-unsupported_file_type']."\", 'negative', '2750');\n";
	echo "          }\n";
	echo "          var selected_file_path = file_input.value;\n";
	echo "          selected_file_path = selected_file_path.replace(\"C:\\\\fakepath\\\\\",'');\n";
	echo "          document.getElementById('file_label').innerHTML = selected_file_path;\n";
	echo "          document.getElementById('button_reset').style.display='inline';\n";
	echo "  }\n";

	echo "</script>";
	echo "<script language='JavaScript' type='text/javascript' src='".PROJECT_PATH."/resources/javascript/reset_file_input.js'></script>\n";

	echo "<div style='float: right; white-space: nowrap;'>\n";
	// TODO - Make phonebook import
	/*
	if (permission_exists('phonebook_import')) {
			echo "          <form name='frmimport' method='POST' enctype='multipart/form-data' action='/app/phonebook/phonebookimport.php'>\n";
			echo "          <input name='file' id='file' type='file' style='display: none;' onchange='check_filetype(this);'>";
			echo "          <label id='file_label' for='file' class='txt' style='width: 200px; overflow: hidden; white-space: nowrap;'>".$text['label-select_a_file']."</label>\n";
			echo "          <input id='button_reset' type='reset' class='btn' style='display: none;' value='".$text['button-reset']."' onclick=\"reset_file_input('file'); document.getElementById('file_label').innerHTML = '".$text['label-select_a_file']."'; this.style.display='none'; return true;\">\n";
			echo "          <input name='submit' type='submit' class='btn' id='upload' value=\"".$text['button-import']."\">\n";
			echo "          </form>";
	}
	*/
	echo "</div>";


	echo "<table width='100%' cellpadding='0' cellspacing='0 border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-phonebook']."</b></td>\n";
	echo "		<td width='50%' align='right'>&nbsp;</td>\n";
	echo "		<td width='70%' align='right'>";
	echo "			<input type='button' id='addGroup' class='btn' name='' alt='".$text['button-groups']."' onclick=\"window.location=' groups.php'\" value='".$text['button-groups']."'>";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			".$text['description-phonebook']."<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//set domain variable
	$domain_name = $_SESSION['domain_name'];

//prepare to page the results
	$sql = "SELECT count(*) AS num_rows FROM v_phonebook ";
	$sql .= "WHERE domain_uuid = '".$_SESSION['domain_uuid']."' ";
	if (strlen($order_by)> 0) { 
		$sql .= "order by $order_by $order ";
	}
	$prep_statement = $db->prepare($sql);
	if ($prep_statement) {
	$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		if ($row['num_rows'] > 0) {
			$num_rows = $row['num_rows'];
		}
		else {
			$num_rows = '0';
		}
	}

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the  list
	// Get all phonebook enties for this domain.
	$sql = "SELECT * FROM v_phonebook WHERE domain_uuid = '".$_SESSION['domain_uuid']."' ";
	if (strlen($order_by)> 0) { 
		$sql .= "ORDER BY $order_by $order "; 
	}else{
		$sql .= "ORDER BY name ASC, phonenumber ASC ";
	}
	$sql .= " LIMIT $rows_per_page OFFSET $offset ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll();
	$result_count = count($result);
	unset ($prep_statement, $sql);

	// TODO - Get group per user
	foreach ($result as $row) {
		$row['group'] = "TBD";
	}


//table headers
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('name', $text['label-name'], $order_by, $order);
    echo th_order_by('number', $text['label-number'], $order_by, $order);
	echo "<th>" .$text['label-group']. "</th>";
	echo "<td class='list_control_icons'>";
	if (permission_exists('phonebook_add')) {
		echo "<a href='phonebook_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	echo "</td>\n";
	echo "</tr>\n";
//show the results
	if ($result_count > 0) {
		foreach($result as $row) {

			$tr_link = (permission_exists('phonebook_edit')) ? "href='phonebook_edit.php?id=".$row['phonebook_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
		        //echo "  <td valign='top' class='".$row_style[$c]."' style='text-align: left;'>".($lastcompanyname != $row['name']?$row['name']:'')."</td>\n";
			echo "  <td valign='top' class='".$row_style[$c]."' style='text-align: left;'>".$row['name']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('phonebook_edit')) {
				echo "<a href='phonebook_edit.php?id=".$row['phonebook_uuid']."'>".$row['phonenumber']."</a>";
			}
			else {
				echo $row['phonenumber'];
			}
			echo "	</td>\n";
            echo "  <td valign='top' class='".$row_style[$c]."' style='text-align: left;'>".$row['group']."</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('phonebook_edit')) {
				echo "<a href='phonebook_edit.php?id=".$row['phonebook_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('phonebook_delete')) {
				echo "<a href='phonebook_delete.php?id=".$row['phonebook_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			};
			echo "  </td>";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

//complete the content
	echo "<tr>\n";
	echo "<td colspan='11' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('phonebook_add')) {
		echo "<a href='phonebook_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

//include link provider
// TODO - Modify link provider
/*
	if(!$result_count == "0"){
		require_once "link_provider.php";
	}
*/
//include the footer
	require_once "resources/footer.php";

?>