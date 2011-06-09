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

$apiRequest = true;

require_once('../global.php');
header('Content-type: text/xml');

$rooms = $_GET['rooms'];
$roomsArray = explode(',',$rooms);
foreach ($roomsArray AS &$v) {
  $v = intval($v);
}

$time = ($_GET['time'] ?: time());
$onlineThreshold = (int) ($_GET['onlineThreshold'] ? $_GET['onlineThreshold'] : $onlineThreshold);

if (!$rooms) {
  $failCode = 'badroomsrequest';
  $failMessage = 'The room string was not supplied or evaluated to false.';
}
if (!$roomsArray) {
  $failCode = 'badroomsrequest';
  $failMessage = 'The room string was not formatted properly in Comma-Seperated notation.';
}
else {
  foreach ($roomsArray AS $room) {
    $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");

    if (!fim_hasPermission($room,$user,'know')) continue;

    $roomsXML .= "
      <room>$room[id]</room>";

    $ausers = sqlArr("SELECT
  u.{$sqlUserTableCols[userName]} AS userName,
  u.{$sqlUserTableCols[userId]} AS userId,
  p.status,
  p.typing
  $cols
FROM {$sqlPrefix}ping AS p,
  {$sqlPrefix}rooms AS r,
  {$sqlUserTable} AS u
  $tables
WHERE p.roomId = $room[roomId] AND
  p.roomId = r.roomId AND
  p.userId = u.$sqlUserTableCols[userId] AND
  UNIX_TIMESTAMP(p.time) >= ($time - $onlineThreshold)
  $where
ORDER BY u.{$sqlUserTableCols[userName]}
  $orderby
$query",true);

    $auserXML .= "    <room>
      <roomData>
        <roomId>$auser[roomId]</roomId>
        <roomName>$auser[name]</roomName>
        <roomTopic>$auser[topic]</roomTopic>
      </roomData>
      <users>
";

    if ($ausers) {
      foreach ($ausers AS $auser) {
        $auserXML .= "    <user>
      <userId>$auser[userId]</userId>
      <userName>$auser[userName]</userName>
      <userGroup>$auser[displaygroupid]</userGroup>
      <status>$auser[status]</status>
      <typing>$auser[typing]</typing>
    </user>
";
      }
    }

      $auserXML .= "      </users>
    </room>
";
  }
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<getActiveUsers>
  <activeUser>
    <userId>$user[userId]</userId>
    <userName>" . fim_encodeXml($user['userName']) . "</userName>
  </activeUser>
  <sentData>
    <rooms>$rooms</rooms>
    <roomsList>$roomsXML
    </roomsList>
    <onlineThreshold>$onlineThreshold</onlineThreshold>
    <time>$time</time>
  </sentData>
  <errorcode>$failCode</errorcode>
  <errortext>$failMessage</errortext>
  <rooms>
$auserXML  </rooms>
</getActiveUsers>";

mysqlClose();
?>