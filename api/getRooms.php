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


$showDeleted = (bool) $_GET['showDeleted'];
$reverseOrder = (bool) $_GET['reverseOrder'];


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
  case 'roomId':
  $order = 'roomId ' . ($reverseOrder ? 'DESC' : 'ASC');
  break;

  case 'name':
  case 'roomName':
  $order = 'roomName ' . ($reverseOrder ? 'DESC' : 'ASC');
  break;

  case 'smart':
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
      'userName' => ($user['userName']),
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


($hook = hook('getRooms_start') ? eval($hook) : '');


$rooms = dbRows("SELECT *
FROM {$sqlPrefix}rooms
WHERE $whereClause TRUE
  {$messagesCached_where}
ORDER BY $order
  {$messagesCached_order}
{$messagesCached_end}",'id'); // Get all rooms

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
      'roomName' => ($room['roomName']),
      'roomTopic' => ($room['roomTopic']),
      'roomOwner' => (int) $room['owner'],
      'allowedUsers' => ($room['allowedUsers']),
      'allowedGroups' => ($room['allowedGroups']),
      'moderators' => ($room['moderators']),
      'favorite' => (bool) $fav,
      'options' => (int) $room['options'],
      'optionDefinitions' => array(
        'official' => (bool) ($room['options'] & 1),
        'deleted' => (bool) ($room['options'] & 4),
        'hidden' => (bool) ($room['options'] & 8),
        'privateIm' => (bool) ($room['options'] & 16),
      ),
      'bbcode' => (int) $room['bbcode'],
    );

    ($hook = hook('getRooms_eachRoom') ? eval($hook) : '');
  }
}


$xmlData['getRooms']['errorcode'] = ($failCode);
$xmlData['getRooms']['errortext'] = ($failMessage);



($hook = hook('getRooms_end') ? eval($hook) : '');


echo fim_outputApi($xmlData);

dbClose();
?>
