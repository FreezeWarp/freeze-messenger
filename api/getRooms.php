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
  $order = 'roomId ' . ($reverseOrder ? 'DESC' : 'ASC');
  break;

  case 'name':
  $order = 'name ' . ($reverseOrder ? 'DESC' : 'ASC');
  break;

  case 'native':
  $order = '(options & 1) DESC, (options & 16) ASC';
  break;

  default:
  $order = 'roomId ' . ($reverseOrder ? 'DESC' : 'ASC');
  break;

}

$xmlData = array(
  'getRooms' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => fim_encodeXml($user['userName']),
    ),
    'sentData' => array(
      'order' => (int) $order,
      'showDeleted' => (bool) $showDeleted,
    ),
    'errorcode' => $failCode,
    'errormessage' => $failMessage,
    'rooms' => array(),
  ),
);


$rooms = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE $whereClause TRUE ORDER BY $order",'id'); // Get all rooms
foreach ($rooms AS $id => $room2) {
  if (fim_hasPermission($room2,$user,$permLevel)) {
    $rooms2[] = $room2;
  }
}


if ($rooms2) {
  foreach ($rooms2 AS $room) {
    $fav = (in_array($room['id'],$favRooms) ? 'true' : 'false');

    $xmlData['getRooms']['rooms']['room ' . $room['messageId']] = array(
      'roomId' => (int)$room['roomId'],
      'roomName' => fim_encodeXml($room['name']),
      'roomTopic' => fim_encodeXml($room['topic']),
      'roomOwner' => (int) $room['owner'],
      'allowedUsers' => fim_encodeXml($room['allowedUsers']),
      'allowedGroups' => fim_encodeXml($room['allowedGroups']),
      'moderators' => fim_encodeXml($room['moderators']),
      'favorite' => (bool) $fav,
      'options' => (int) $room['options'],
      'optionDefinitions' => array(
        'official' => (bool) ($row['options'] & 1),
        'deleted' => (bool) ($row['options'] & 4),
        'hidden' => (bool) ($row['options'] & 8),
        'privateIm' => (bool) ($row['options'] & 16),
      ),
      'bbcode' => $room['bbcode'],
    );
  }
}

echo fim_outputXml($xmlData);

mysqlClose();
?>
