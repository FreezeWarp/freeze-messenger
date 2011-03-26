<?php
ini_set('max_execution_time','3');

require_once('../global.php');

$room = intval($_GET['room']);
$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");

$time = time();
$light = ($_GET['light'] ? true : false);

mysqlQuery("INSERT INTO {$sqlPrefix}ping (userid,roomid,time) VALUES ($user[userid],$room[id],CURRENT_TIMESTAMP()) ON DUPLICATE KEY UPDATE time = CURRENT_TIMESTAMP()");

if ($light) {
  $users = sqlArr("SELECT COUNT(p.userid) AS total FROM {$sqlPrefix}ping AS p WHERE p.roomid = $room[id] AND UNIX_TIMESTAMP(p.time) >= UNIX_TIMESTAMP(NOW()) - $onlineThreshold");
  echo 'Current Users in This Room: ' . $users['total'];
}
else {
  $users = sqlArr("SELECT u.username, u.userid, u.displaygroupid, p.id, u2.settings AS usersettings FROM {$sqlPrefix}ping AS p, user AS u, {$sqlPrefix}users AS u2 WHERE p.roomid = $room[id] AND p.userid = u.userid AND u2.userid = u.userid AND UNIX_TIMESTAMP(p.time) >= UNIX_TIMESTAMP(NOW()) - $onlineThreshold ORDER BY u.username",'id');

  if ($users) {
    foreach ($users AS $user) {
      $users2[] = userFormat($user, $room, false);
    }
  }

  echo implode(', ',$users2);
}

mysqlClose();
?>