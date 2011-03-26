<?php
require_once('../global.php');
require_once('../functions/parserFunctions.php');
require_once('../functions/generalFunctions.php');

$userid = intval($_GET['userid']);
$user = sqlArr("SELECT * FROM {$sqlUserTable} WHERE {$sqlUserIdCol} = $userid");

$userdata = '<img title="" src="http://www.victoryroad.net/image.php?u=' . $user['userid'] . '" style="float: left;" />' . userFormat($user,false) . '<br />' . $user['usertitle'] . '<br />Posts: ' . $user['posts'] . '<br />Member Since: ' . vbdate('m/d/y g:i:sa',$user['joindate']);

echo $userdata;

?>