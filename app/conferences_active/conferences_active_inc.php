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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('conference_active_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//show content
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if (!$fp) {
		$msg = "<div align='center'>".$text['message-connection']."<br /></div>";
		echo "<div align='center'>\n";
		echo "<table width='40%'>\n";
		echo "<tr>\n";
		echo "<th align='left'>".$text['label-message']."</th>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td class='row_style1'><strong>$msg</strong></td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "</div>\n";
	}
	else {
		$xml_string = trim(event_socket_request($fp, 'api conference xml_list'));
		try {
			$xml = new SimpleXMLElement($xml_string);
		}
		catch(Exception $e) {
			//echo $e->getMessage();
		}

		$c = 0;
		$row_style["0"] = "row_style0";
		$row_style["1"] = "row_style1";

		echo "<table class='list'>\n";
		echo "<tr class='list-header'>\n";
		echo "	<th>".$text['label-name']."</th>\n";
		echo "	<th>".$text['label-participant-pin']."</th>\n";
		echo "	<th>".$text['label-member-count']."</th>\n";
		if (permission_exists('conference_interactive_view')) {
			echo "	<td class='action-button'>&nbsp;</td>\n";
		}
		echo "</tr>\n";
		$x = 0;
		foreach ($xml->conference as $row) {
			//set the variables
				$name = $row['name'];
				$member_count = $row['member-count'];
			//show the conferences that have a matching domain
				$name_array = explode('@', $name);
				if ($name_array[1] == $_SESSION['domain_name']) {
					$conference_uuid = $name_array[0];
					if (is_uuid($conference_uuid)) {
						//check for the conference center room
						$sql = "select ";
						$sql .= "cr.conference_room_name, ";
						$sql .= "v.participant_pin ";
						$sql .= "from ";
						$sql .= "v_meetings as v, ";
						$sql .= "v_conference_rooms as cr ";
						$sql .= "where ";
						$sql .= "v.meeting_uuid = cr.meeting_uuid ";
						$sql .= "and v.meeting_uuid = :meeting_uuid  ";
						$parameters['meeting_uuid'] = $conference_uuid;
						$database = new database;
						$conference = $database->select($sql, $parameters, 'row');
						$conference_name = $conference['conference_room_name'];
						$participant_pin = $conference['participant_pin'];
						unset ($parameters, $conference, $sql);
						$conference_uuid = $name_array[0];

						//check the conference table
						if (strlen($conference_name) == 0) {
							$sql = "select ";
							$sql .= "conference_name, ";
							$sql .= "conference_pin_number ";
							$sql .= "from ";
							$sql .= "v_conferences ";
							$sql .= "where ";
							$sql .= "domain_uuid = :domain_uuid ";
							$sql .= "and conference_uuid = :conference_uuid ";
							$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
							$parameters['conference_uuid'] = $conference_uuid;
							$database = new database;
							$conference = $database->select($sql, $parameters, 'row');
							$conference_name = $conference['conference_name'];
							$participant_pin = $conference['conference_pin_number'];
							unset ($parameters, $sql);
						}
					}

					if (permission_exists('conference_interactive_view')) {
						$list_row_url = 'conference_interactive.php?c='.urlencode($conference_uuid);
						$list_row_onclick = "onclick=\"document.location.href='".$list_row_url."';\"";
					}
					echo "<tr class='list-row' href='".$list_row_url."'>\n";
					echo "	<td ".$list_row_onclick.">";
					if (permission_exists('conference_interactive_view')) {
						echo "	<a href='".$list_row_url."'>".escape($conference_name)."</a>";
					}
					else {
						echo escape($conference_name);
					}
					echo "	</td>\n";
					echo "	<td ".$list_row_onclick.">".escape($participant_pin)."</td>\n";
					echo "	<td ".$list_row_onclick.">".escape($member_count)."</td>\n";
					if (permission_exists('conference_interactive_view')) {
						echo "	<td class='action-button'>";
						echo button::create(['type'=>'button','title'=>$text['button-view'],'icon'=>$_SESSION['theme']['button_icon_view'],'link'=>$list_row_url]);
						echo "	</td>\n";
					}
					echo "</tr>\n";
					$x++;
				}
		}
		echo "</table>\n";
		echo "<br /><br />";
	}

?>