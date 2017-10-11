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

include "resources/classes/functions.php";

$date = check_str($_REQUEST["date"]);

if ($date == "") {
    $date = date("Y-m-d");     
}

if (!validateDate($date,"Y-m-d")) {
    send_api_answer("406", "Date format incorrect");
    exit;
}

var_dump($_SESSION);

?>
