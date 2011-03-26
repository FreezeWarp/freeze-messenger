<?php
header('Content-type: text/plain');
require_once('../global.php');

if ($_GET['roomList']) {
  $rooms = explode(',',$_GET['roomList']);
}
elseif ($_GET['room']) {
  $rooms = array($_GET['room']);
}
else {
  die();
}

$time = ($_GET['time'] ?: time());
$onlineThreshold = ($_GET['onlineThreshold'] ?: $onlineThreshold); 

foreach ($rooms AS $room) {
  $users = sqlArr("SELECT u.username, u.userid, p.id FROM {$sqlPrefix}ping AS p, user AS u WHERE p.roomid = $room AND p.userid = u.userid AND UNIX_TIMESTAMP(p.time) >= ($time - $onlineThreshold) ORDER BY u.username",'id');

  if ($users) {
    foreach ($users AS $user) {
      echo "'$user[userid]','$user[username]'\n";
    }
  }
}

mysqlClose();
?>