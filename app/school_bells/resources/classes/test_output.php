<?php

require_once("school_bell_selector.php");

$school_bell_selector = new school_bell_selector;

echo $school_bell_selector->draw_yea('*', true);

?>