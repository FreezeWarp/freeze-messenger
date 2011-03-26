<?php
require_once('../global.php');
header('Content-type: text/plain');

$userid = intval($_GET['userid']);
$username = mysqlEscape($_GET['username']);

if ($userid) $getuservb = sqlArr("SELECT * FROM user WHERE userid = $userid");
elseif ($username) $getuservb = sqlArr("SELECT * FROM user WHERE username = '$username'");
else die('');

if ($user['userid'] > 0) {
  $getuser = sqlArr("SELECT * FROM {$sqlPrefix}users WHERE userid = $getuservb[userid]");

  echo "'$getuservb[userid]','$getuservb[username]','$getuser[settings]','$getuser[favRooms]'";
}

mysqlClose();
?>