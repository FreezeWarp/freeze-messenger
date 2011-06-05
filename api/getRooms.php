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


$showDeleted = (int) $_GET['showDeleted'];
$reverseOrder = (int) $_GET['reverseOrder'];


$favRooms = explode(',',$user['favRooms']);


$whereClause = ($showDeleted ? '' : '(options & 4 = FALSE) AND ');
if ($rooms) $whereClause .= ' id IN (' . implode(',',$roomsArray) . ') AND ';


switch ($_GET['permLevel']) {
  case 'post':
  case 'view':
  case 'moderate':
  case 'know':
  $permLevel = $_GET['permLevel'];
  break;

  default:
  $permLevel = 'post';
  break;
}


switch ($_GET['order']) {
  case 'id':
  $order = 'id ' . ($reverseOrder ? 'DESC' : 'ASC');
  break;

  case 'name':
  $order = 'name ' . ($reverseOrder ? 'DESC' : 'ASC');
  break;

  case 'native':
  $order = '(options & 1) DESC, (options & 16) ASC';
  break;

  default:
  $order = 'id ' . ($reverseOrder ? 'DESC' : 'ASC');
  break;
}


$rooms = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE $whereClause TRUE ORDER BY $order",'id'); // Get all rooms
foreach ($rooms AS $id => $room2) {
  if (hasPermission($room2,$user,$permLevel)) $rooms2[] = $room2;
}



if ($rooms2) {
  foreach ($rooms2 AS $row) {
    $row['name'] = htmlspecialchars($row['name']);
    $fav = (in_array($row['id'],$favRooms) ? 'true' : 'false');

    $roomXML .= "    <room>
      <roomId>$row[id]</roomId>
      <roomName>" . vrim_encodeXML($row['name']) . "</roomName>
      <roomTopic>" . vrim_encodeXML($row['title']) . "</roomTopic>
      <owner>$row[owner]</owner>
      <allowedUsers>$row[allowedUsers]</allowedUsers>
      <allowedGroups>$row[allowedGroups]</allowedGroups>
      <favorite>$fav</favorite>
      <options>$row[options]</options>
      <optionDefinitions>
        <official>" . (($row['options'] & 1) ? 'true' : 'false') . "</official>
        <deleted>" . (($row['options'] & 4) ? 'true' : 'false') . "</deleted>
        <hidden>" . (($row['options'] & 8) ? 'true' : 'false') . "</hidden>
        <privateIm>" . (($row['options'] & 16) ? 'true' : 'false') . "</privateIm>
      </optionDefinitions>
    </room>";
  }
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<getRooms>
  <activeUser>
    <userId>$user[userId]</userId>
    <userName>" . vrim_encodeXML($user['userName']) . "</userName>
  </activeUser>

  <sentData>
    <order>" . htmlspecialchars($order) . "</order>
    <showDeleted>" . ($_GET['showDeleted'] ? 'true' : 'false') . "</showDeleted>
  </sentData>

  <errorcode>$failCode</errorcode>
  <errortext>$failMessage</errortext>
  <rooms>
    $roomXML
  </rooms>
</getRooms>";

mysqlClose();
?>
