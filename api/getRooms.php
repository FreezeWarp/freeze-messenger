<?php
/* FreezeMessenger Copyright © 2014 Joseph Todd Parsons

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

/**
 * Get Rooms from the Server
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2014
 * @param bool [showDeleted=false] - Will attempt to show deleted rooms, assuming the user has access to them (that is, is an administrator). Defaults to false.
 * @param string [order=roomId] - How the rooms should be ordered (either roomId or roomName).
 * @param string [rooms] - If specified, only specific rooms are listed. By default, all rooms are listed.
*/

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
  // No matter what, the user will not be able to see rooms that he is unable to view.
  'permFilter' => array(
    'default' => 'view',
    'valid' => array('post', 'view', 'moderate', 'alter', 'admin', 'own'),
    'require' => false,
  ),

  'roomIds' => array(
    'default' => '',
    'cast' => 'jsonList',
    'filter' => 'int',
    'evaltrue' => true,
  ),

  'roomNames' => array(
    'default' => '',
    'cast' => 'jsonList',
    'filter' => 'string',
    'evaltrue' => true,
  ),
  
/*  'info' => array(
    'default' => array(),
    'cast' => 'csv',
    'evaltrue' => true,
    'valid' => array('basic', 'perm'),
  ),*/

  'search' => array(
    'cast' => 'string',
  ),

  'sort' => array(
    'valid' => array('roomId', 'roomName'),
    'default' => 'roomId',
  ),

  'showDeleted' => array(
    'cast' => 'bool',
    'default' => false,
  ),
));

/* Data Predefine */
$xmlData = array(
  'getRooms' => array(
    'activeUser' => array(
      'userId' => $user->id,
      'userName' => $user->name,
    ),
    'rooms' => array(),
  ),
);


$permFilterMatches = array(
  'post' => ROOM_PERMISSION_POST,
  'view' => ROOM_PERMISSION_VIEW,
  'moderate' => ROOM_PERMISSION_MODERATE,
  'alter' => ROOM_PERMISSION_PROPERTIES,
  'admin' => ROOM_PERMISSION_GRANT,
  'own' => ROOM_PERMISSION_VIEW
);


$rooms = $database->getRooms(array(
  'roomIds' => $request['roomIds'],
  'roomNames' => $request['roomNames'],
  'showDeleted' => $request['showDeleted'],
  'roomNameSearch' => $request['search'],
  'ownerIds' => ($request['permFilter'] === 'own' ? array($user->id) : array())
), array($request['sort'] => 'asc'))->getAsRooms();

foreach ($rooms AS $roomId => $room) {
  $permissions = $database->hasPermission($user, $room);

//  if (!($permissions & $permFilterMatches[$request['permFilter']])) continue;

  $xmlData['getRooms']['rooms']['room ' . $roomId] = array(
    'roomId' => $room->id,
    'roomName' => $room->name,
    'ownerId' => $room->ownerId,
    'defaultPermissions' => $room->defaultPermissions,
    'parentalFlags' => new apiOutputList($room->parentalFlags),
    'parentalAge' => $room->parentalAge,
    'official' => $room->official,
    'archived' => $room->archived,
    'hidden' => $room->hidden,
    'deleted' => $room->deleted,
    'permissions' => array(
      'view' => (bool) ($permissions & ROOM_PERMISSION_VIEW),
      'post' => (bool) ($permissions & ROOM_PERMISSION_POST),
      'topic' => (bool) ($permissions & ROOM_PERMISSION_TOPIC),
      'moderate' => (bool) ($permissions & ROOM_PERMISSION_MODERATE),
      'alter' => (bool) ($permissions & ROOM_PERMISSION_PROPERTIES),
      'admin' => (bool) ($permissions & ROOM_PERMISSION_GRANT),
    ),
  );

  if ($permissions[0]['view']) { // These are not shown to users who are not allowed to access the room.
    $xmlData['getRooms']['rooms']['room ' . $roomId]['roomTopic'] = $roomData['roomTopic'];
    $xmlData['getRooms']['rooms']['room ' . $roomId]['owner'] = $roomData['owner'];
    $xmlData['getRooms']['rooms']['room ' . $roomId]['lastMessageId'] = $roomData['lastMessageId'];
    $xmlData['getRooms']['rooms']['room ' . $roomId]['lastMessageTime'] = $roomData['lastMessageTime'];
    $xmlData['getRooms']['rooms']['room ' . $roomId]['messageCount'] = $roomData['messageCount'];
  }

  if ($permissions[0]['moderate']) { // Fetch the allowed users and allowed groups if the user is able to moderate the room.
    if (isset($permissionsCache['byRoomId'][$roomId])) { // TODO: Fix both of these.
      $xmlData['getRooms']['rooms']['room ' . $roomId]['allowedUsers'] = (array) $generalCache->getPermissions($roomId, 'user');
      $xmlData['getRooms']['rooms']['room ' . $roomId]['allowedGroups'] = (array) $generalCache->getPermissions($roomId, 'group');
    }
  }
}



/* Output Data Structure */
new apiData($xmlData, true);
?>