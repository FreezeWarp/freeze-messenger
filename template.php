<?php

$noReqLogin = true;
$reqPhrases = true;
$reqHooks = true;

require_once('global.php');

echo template($_GET['template']);

?>