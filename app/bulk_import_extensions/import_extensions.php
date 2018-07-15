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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Igor Olhovskiy <igorolhovskiy@gmail.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

if (permission_exists('import_extensions')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
$text = (new text)->get();

//$csv_file_path = '/var/www/fusionpbx/app/bulk_import_extensions/';
$csv_file_path = '';

require_once "resources/header.php";
	$document['title'] = $text['title-import_extensions'];

require_once "resources/paging.php";

// Get variables here

$rows_to_show =isset($_SESSION['import_extensions']['rows_to_show']['numeric']) ? (int) $_SESSION['import_extensions']['rows_to_show']['numeric'] : 4;
$is_import = isset($_REQUEST['is_import']) ? ($_REQUEST['is_import'] == 'true') : False;

// Show the content

// Get table row width. 90 - cause 10% is always to show selector.
$table_row_width = (int) 90 / $rows_to_show;

//show the content
echo "<table width='100%' cellpadding='0' cellspacing='0 border='0'>\n";
echo "	<tr>\n";
echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['header-import_extensions']."</b></td>\n";
echo "		<td width='50%' align='right'>&nbsp;</td>\n";
echo "	</tr>\n";
echo "	<tr>\n";
echo "		<td align='left' colspan='2'>\n";
echo "			".$text['description-import_extensions']."<br /><br />\n";
echo "		</td>\n";
echo "	</tr>\n";
echo "</table>\n";


$c = 0;
$row_style["0"] = "row_style0";
$row_style["1"] = "row_style1";

// Check if we have CSV file on place
$import_file = new csv_file_process($csv_file_path."import.csv");


if ($import_file->is_valid() && $is_import) {
	// Import data to database
	echo "<pre>";
	var_dump($_POST);
	echo "</pre>";

	// Unlink the file!
	$import_file->delete();
} elseif ($import_file->is_valid()) {

	// Here we got first 4 lines of file. As usual, CSV holds first line as a fields desccription.
	// And we will use it to count number of fields in file.
	$import_lines = $import_file->read_first();
	$row_count = count($import_lines[0]);

	// Initialize array if not full for normal show after.
	for ($i = 1; $i <= 3; $i++) {
		if (!isset($import_lines[$i])) {
			$import_lines[$i] = array();
		}
		for ($j = 0; $j < $row_count; $j++) {
			if (!isset($import_lines[$i][$j])) {
				$import_lines[$i][$j] = '';
			}
		}
	}

	$selector = new bulk_import_extensions_options_selector();


	// Show content in a case of valid file
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "<tr class='" . $row_style[$c] . "'>\n";
	echo "<th width='10%' align='center' nowrap='nowrap'>" . $text['description-selector'] . "</th>\n";
	for ($i = 1; $i <= $rows_to_show; $i++) {
		echo "<th align='left' nowrap='nowrap' width='" . $table_row_width . "%'>" . $text['description-file_column'] . " " .$i . "</th>\n";
	}
	echo "</tr>\n";
	$c = 1 - $c;
	// Show table rows
	for ($row_index = 0; $row_index < $row_count; $row_index++) {
		// Show table columns. By default - show 3 first columns to check.
		echo "<tr class='" . $row_style[$c] . "'>\n";
		// Show selector
		echo "<td width='10%' align='center' nowrap='nowrap'>";
		echo $selector->draw_selector("csv_field[$row_index]", $row_index);
		echo "</td>\n";
		for ($i = 0; $i < $rows_to_show; $i++) {
			echo "<td align='left' nowrap='nowrap' width='" . $table_row_width . "%'>" . $import_lines[$i][$row_index] . "</td>\n";
		}
		echo "</tr>\n";
		$c = 1 - $c;
	}
	echo "</table>\n";
	echo "<input type='hidden' name='is_import' value='true'>\n";
	echo "<br/>";
	echo "<input type='submit' name='submit' style='float: right;' class='btn' value='".$text['button-import']."'>\n";
	echo "<br/><br/>";
	echo "</form>";
	
 } // End show content for CSV file is present

// Paging controls?
/*
echo "<table>\n";
echo "<tr>\n";
echo "<td colspan='10' align='left'>\n";
echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
echo "	<tr>\n";
echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
echo "		<td class='list_control_icons'>";
echo "		</td>\n";
echo "	</tr>\n";
echo "	</table>\n";
echo "</td>\n";
echo "</tr>\n";
echo "</table>";
*/

//include the footer
require_once "resources/footer.php";
?>
