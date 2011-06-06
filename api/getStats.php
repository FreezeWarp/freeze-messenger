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
$roomList = implode(',',$roomsArray);


$resultLimit = (int) ($_GET['number'] ? $_GET['number'] : 10);



$rooms = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id IN ($roomList)",'id');


foreach ($rooms AS $room) {
  if ($hidePostCounts) {
    if (!hasPermission($room,$user,'know')) {
      continue;
    }
  }


  switch ($loginMethod) {
    case 'phpbb':
    $cols = ', u.user_colour AS userColour';
    break;

    case 'vbulletin':
    break;
  }


  $totalPosts = sqlArr("SELECT m.messages AS count,
  u.{$sqlUserTableCols[userId]} AS userId,
  u.{$sqlUserTableCols[userName]} AS userName
  $cols
FROM {$sqlPrefix}roomStats AS m,
  {$sqlUserTable} AS u
  $tables
WHERE m.roomId = $room[id] AND
  u.{$sqlUserTableCols[userId]} = m.userId
  $where 
ORDER BY count DESC
  $orderby
LIMIT $resultLimit
  $limit",'userId');


  $roomStatsXml .= "<room>
  <roomData>
    <roomId>$room[id]</roomId>
    <roomName>$room[name]</roomName>
  </roomData>";


  foreach ($totalPosts AS $totalPoster) {
    switch ($loginMethod) {
      case 'phpbb':
      $totalPoster['startTag'] = "<span style=\"color: #$totalPoster[userColour]\">";
      $totalPoster['endTag'] = "</span>";
      break;
    }

    $position++;

    $roomStatsXml .= "<user>
    <userData>
      <userId>$totalPoster[userId]</userId>
      <userName>$totalPoster[userName]</userName>
      <startTag>" . fim_encodeXml($totalPoster['startTag']) . "</startTag>
      <endTag>" . fim_encodeXml($totalPoster['endTag']) . "</endTag>
    </userData>
    <messageCount>$totalPoster[count]</messageCount>
    <position>$position</position>
</user>";
  }


  $roomStatsXml .= "</room>";
}



///* Output *///
echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<!DOCTYPE html [
  <!ENTITY nbsp \" \">
]>
<getMessages>
  <activeUser>
    <userId>$user[userId]</userId>
    <userName>" . fim_encodeXml($user['userName']) . "</userName>
  </activeUser>

  <sentData>
    <rooms>$roomList</rooms>
    <resultLimit>$resultLimit</resultLimit>
  </sentData>

  <errorcode>$failCode</errorcode>
  <errortext>$failMessage</errortext>

  <roomStats>
    $roomStatsXml
  </roomStats>
</getMessages>";




mysqlClose();
?>