<style>
.rpbLink{
    width: 92% !important;
    max-width: 92% !important;
}
.rpbLinkTitle span{
    color: #952424;
}
.rpbLinkTitle{
    display: block;
    text-indent: 3px;
    margin: 15px 0 3px 0;
    font-weight: bold;
    font-family: arial;
}
</style>
<?php

// Link Provider

// Select distinct groups from the database
$sql = "select distinct on (contact_group) contact_group from v_phonebook_details where domain_uuid = '".$_SESSION['domain_uuid']."'";
// Check SQL
$prep_statement = $db->prepare(check_sql($sql));
// Execute SQL
$prep_statement->execute();
// Retrieve result
$fetchedArray = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
// Loop fetched array and concatenate into a single string
foreach($fetchedArray as $fetched) {
	$groupString .= $fetched['contact_group'].",";
}
// Trim the comma from that string
$groupString = rtrim($groupString, ",");
// Explode the string
$groupArray = explode(',', $groupString);
// Remove duplicates from group array
$groupArray = array_unique($groupArray);

// Write a copy link button for each group
$tNum = 1;

//show title
echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
echo "  <tr>\n";
echo "      <td width='50%' align='left' nowrap='nowrap'><b>".$text['phonebook-links-title']."</b></td>\n";
echo "      <td width='50%' align='right'>&nbsp;</td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "      <td align='left' colspan='2'>\n";
echo "          ".$text['phonebook-links-desc']."<br />\n";
echo "      </td>\n";
echo "  </tr>\n";
echo "</table>\n";

foreach ($groupArray as $group) {
	$link = "https://".$domain_name."/app/phonebook/directory.php?gid=".$group."&amp;duuid=".$domain_uuid;
    echo "<div class='rpbLinkTitle'>".$text['phonebook-links-label']."<span>".$group."</span></div>";
	echo "<input type='text' class='formfld rpbLink' id='copyTarget".$tNum."' value='".$link."'> <input type='button' class='btn' id='copyButton".$tNum."' value='".$text['phonebook-links-copy-text']."' />";
	echo "<script>";
		echo "document.getElementById('copyButton".$tNum."').addEventListener('click', function() {";
		echo "copyToClipboard(document.getElementById('copyTarget".$tNum."'));";
		echo "});";
	echo "</script>";
	$tNum++;
}
?>

<!-- Copy to clipboard script -->
<script>
function copyToClipboard(elem) {
	  // create hidden text element, if it doesn't already exist
    var targetId = "_hiddenCopyText_";
    var isInput = elem.tagName === "INPUT" || elem.tagName === "TEXTAREA";
    var origSelectionStart, origSelectionEnd;
    if (isInput) {
        // can just use the original source element for the selection and copy
        target = elem;
        origSelectionStart = elem.selectionStart;
        origSelectionEnd = elem.selectionEnd;
    } else {
        // must use a temporary form element for the selection and copy
        target = document.getElementById(targetId);
        if (!target) {
            var target = document.createElement("textarea");
            target.style.position = "absolute";
            target.style.left = "-9999px";
            target.style.top = "0";
            target.id = targetId;
            document.body.appendChild(target);
        }
        target.textContent = elem.textContent;
    }
    // select the content
    var currentFocus = document.activeElement;
    target.focus();
    target.setSelectionRange(0, target.value.length);
    
    // copy the selection
    var succeed;
    try {
    	  succeed = document.execCommand("copy");
    } catch(e) {
        succeed = false;
    }
    // restore original focus
    if (currentFocus && typeof currentFocus.focus === "function") {
        currentFocus.focus();
    }
    
    if (isInput) {
        // restore prior selection
        elem.setSelectionRange(origSelectionStart, origSelectionEnd);
    } else {
        // clear temporary content
        target.textContent = "";
    }
    return succeed;
}
</script>
