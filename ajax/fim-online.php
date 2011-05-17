<?php
/* FreezeMessenger Copyright © 2011 Joseph Todd Parsons

 * This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>. */
die('Dep');
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
      $user2['rooms'][] = "<a href=\"/chat.php?room=$id\" target=\"_blank\">{$room2[name]}</a>";
    }

    $user2['rooms'] = implode(', ',$user2['rooms']);

    $users2 .= "<tr><td><a href=\"http://www.victoryroad.net/member.php?u=$user2[userid]\" target=\"_blank\" class=\"username\" data-userid=\"$user2[userid]\">$user2[username]</a></td><td>$user2[rooms]</td></tr>";
  }
}

echo $users2;

mysqlClose();
?>