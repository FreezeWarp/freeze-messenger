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
$roomList = implode(',',$rooms);

$resultLimit = (int) ($_GET['number'] ? $_GET['number'] : 10);



$rooms = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id IN ($roomList)",'id');

foreach ($rooms AS $room) {
  if ($hidePostCounts) {
    if (!hasPermission($room,$user,'know')) {
      continue;
    }
  }


  $totalPosts = sqlArr("SELECT m.messages AS count,
  u.userId,
  u.userName
FROM {$sqlPrefix}roomStats AS m,
  user AS u
WHERE m.roomId = $room[id] AND
  u.userId = m.userId
ORDER BY count DESC
LIMIT $resultLimit",'userId');


  $roomXml = "<room>
  <roomData>
    <roomId>$room[roomId]</roomId>
    <roomName>$room[roomName]</roomName>
  </roomData>";


  foreach ($totalPosts AS $totalPoster) {
    $position++;

    $roomXml .= "<user>
    <userData>

    </userData>
    <messageCount></messageCount>
    <position>$position</position>";
  }


  $roomXml = "</room>";
}



///* Output *///
echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<!DOCTYPE html [
  <!ENTITY nbsp \" \">
]>
<getMessages>
  <activeUser>
    <userId>$user[userId]</userId>
    <userName>" . vrim_encodeXML($user['userName']) . "</userName>
  </activeUser>

  <sentData>
    <rooms>$rooms</rooms>
    <roomsList>
    $roomsXML
    </roomsList>
    <resultLimit>$resultLimit</resultLimit>
  </sentData>

  <errorcode>$failCode</errorcode>
  <errortext>$failMessage</errortext>

  <roomStats>
    $roomStatsXML
  </roomStats>
</getMessages>";




mysqlClose();
?>