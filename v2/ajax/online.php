<?php
ini_set('max_execution_time','5');

require_once('../global.php');

$time = time();

$users = sqlArr("SELECT
  u.username, u.userid, GROUP_CONCAT(p.roomid) AS roomids
FROM
  user AS u, {$sqlPrefix}ping AS p
WHERE
  u.userid = p.userid AND
  UNIX_TIMESTAMP(p.time) > UNIX_TIMESTAMP(NOW()) - $onlineThreshold
GROUP BY
  p.userid
ORDER BY
  u.username",'userid');
$allRooms = sqlArr("SELECT * FROM {$sqlPrefix}rooms",'id');

if ($users) {
  foreach ($users AS $user2) {
    $rooms = explode(',',$user2['roomids']);
    foreach ($rooms AS $id) {
      $room2 = $allRooms[$id];
      if ($hideRoomsOnline) {
        if (!hasPermission($room2,$user,'know')) continue;
      }
      $user2['rooms'][] = "<a href=\"/index.php?room=$id\" target=\"_blank\">{$room2[name]}</a>";
    }

    $user2['rooms'] = implode(', ',$user2['rooms']);

    $users2 .= "<tr><td><a href=\"http://www.victoryroad.net/member.php?u=$user2[userid]\" target=\"_blank\">$user2[username]</a></td><td>$user2[rooms]</td></tr>";
  }
}

echo $users2;

mysqlClose();
?>