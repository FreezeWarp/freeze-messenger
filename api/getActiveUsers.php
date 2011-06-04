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
foreach ($roomsArray AS &$v) $v = intval($v);

$time = ($_GET['time'] ?: time());
$onlineThreshold = ($_GET['onlineThreshold'] ?: $onlineThreshold); 

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

    if (!hasPermission($room,$user,'know')) continue;

    $roomsXML .= "
      <room>$room[id]</room>";

    $ausers = sqlArr("SELECT u.userName, u.userId, p.id, p.status, p.typing FROM {$sqlPrefix}ping AS p, {$sqlPrefix}rooms AS r, user AS u WHERE p.roomId = $room[id] AND p.roomId = r.id AND p.userId = u.userId AND UNIX_TIMESTAMP(p.time) >= ($time - $onlineThreshold) ORDER BY u.userName",'id');

    $auserXML .= "    <room>
      <roomData>
        <roomId>$auser[id]</roomId>
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

$data = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<getActiveUsers>
  <activeUser>
    <userId>$user[userId]</userId>
    <userName>" . vrim_encodeXML($user['userName']) . "</userName>
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

if ($_GET['gz']) {
 echo gzcompress($data);
}
else {
  echo $data;
}

mysqlClose();
?>