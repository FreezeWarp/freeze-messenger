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
$requestG = fim_sanitizeGPC('g', array(
  /* Action */
  'action' => array(
    // 'create', 'edit', 'delete', and 'undelete' MUST be part of a POST request. 'get' MUST be part of a GET request. However, this parameter, as with other get parameters, are URL parameters -- and thus read using $_GET.
    'valid' => array(
      'get',
      'create', 'edit',
      'delete', 'undelete'
    ),
    'require' => true,
  ),

  /* GET Only fields */
  'permFilter' => array(   // No matter what, the user will not be able to see rooms that he is unable to view.
    'default' => 'view',
    'valid' => array('post', 'view', 'moderate', 'alter', 'admin', 'own'),
    'require' => false,
    ),

  'showDeleted' => array(
    'cast' => 'bool',
    'default' => false,
  ),

  'info' => array(
    'default' => array(),
    'cast' => 'jsonList',
    'valid' => array('permissions'),
  ),

  'sort' => array(
    'valid' => array('roomId', 'roomName'),
    'default' => 'roomId',
  ),

  /* GET, POST, DELETE fields
   * DELETE will not remove the field. It will instead mark as deleted. */
  'roomIds' => array(
    'default' => '',
    'cast' => 'jsonList',
    'filter' => 'int',
    'evaltrue' => true,
  ),

  // Rooms _can_ be identified as roomIds or roomNames (and the two can be mixed).
  'roomNames' => array(
    'default' => '',
    'cast' => 'jsonList',
    'filter' => 'string',
    'evaltrue' => true,
  ),

  // Using search with DELETE and PUT are never good ideas. But we don't want to treat our users like babies, do we?
  'search' => array(
    'cast' => 'string',
  ),
));

/* Get Post Data */
$requestP = fim_sanitizeGPC('p', array(
  'defaultPermissions' => array(
    'valid' => array('view', 'post', 'topic'),
    'cast' => 'jsonList',
  ),

  'moderators' => array(
    'cast' => 'jsonList',
    'filter' => 'int',
    'evaltrue' => true,
  ),

  // Note: Both are structured as { "(+|-|*){ID}" : ['view', 'post', ...] } -- too complicated to make an explicit validation, but they will be interpreted as such.
  'userPermissions' => array(
    'cast' => 'json',
  ),

  'groupPermissions' => array(
    'cast' => 'json',
  ),

  'censor' => array(
    'cast' => 'json',
    'filter' => 'bool',
    'filterKey' => 'int',
  ),

  'parentalAge' => array(
    'cast' => 'int',
    'valid' => $config['parentalAges'],
    'default' => $config['parentalAgeDefault'],
  ),

  'parentalFlags' => array(
    'cast' => 'jsonList',
    'valid' => $config['parentalFlags'],
    'default' => $config['parentalFlagsDefault'],
  ),
));


/* Manual Formatting for Some of the Request Variables */
$requestP['defaultPermissions'] = getPermissionsField($requestP['defaultPermissions']);

$permFilterMatches = array(
  'post' => ROOM_PERMISSION_POST,
  'view' => ROOM_PERMISSION_VIEW,
  'topic' => ROOM_PERMISSION_TOPIC,
  'moderate' => ROOM_PERMISSION_MODERATE,
  'properties' => ROOM_PERMISSION_PROPERTIES,
  'grant' => ROOM_PERMISSION_GRANT,
  'own' => ROOM_PERMISSION_VIEW
);



/* Helper Functions */
function getPermissionsField($permissionsArray) {
  global $permFilterMatches;

  $permissionsField = 0;

  foreach ($permFilterMatches AS $string => $byte) {
    if (in_array($string, $permissionsArray)) $permissionsField |= $byte;
  }
}

/**
 * Alters a room's permissions based on a specially formatted userArray and groupArray. This function does not check for permissions -- make sure that a user has permission to alter permissions before executing this function.
 *
 * @param $roomId
 * @param $userArray
 * @param $groupArray
 */
function alterRoomPermissions($roomId, $userArray, $groupArray) {
  global $database;

  foreach (array('users' => $userArray, 'groups' => $groupArray) AS $attribute => $array) {
    foreach ($array AS $code => $permissionsArray) {
      $operation = substr($code, 0, 1); // The first character of the code is going to be either '+', '-', or '*', representing which action we are taking.
      $param = (int) substr($code, 1); // Everything after the first character represents either a group or user ID.

      $permissionsField = getPermissionsField($permissionsArray);

      if ($attribute === 'users') $databasePermissionsField = $database->getPermissionsField($roomId, $param);
      elseif ($attribute === 'groups') $databasePermissionsField = $database->getPermissionsField($roomId, array(), $param);

      switch ($operation) {
        case '+': $database->setPermission($roomId, $attribute, $param, $databasePermissionsField | $permissionsField); break; // Add new permissions to any existing permissions.
        case '-': $database->setPermission($roomId, $attribute, $param, $databasePermissionsField &~ $permissionsField); break; // Remove permissions from any existing permissions.
        case '*': $database->setPermission($roomId, $attribute, $param, $permissionsField); break; // Replace permissions.
      }
    }
  }
}



/* Data Predefine */
$xmlData = array(
  'getRooms' => array(
    'rooms' => array(),
  ),
);


/* Create */
if ($requestG['action'] === 'create') {
  if (!($user->privs & USER_PRIV_CREATE_ROOMS)) {
    new fimError('noPerm', 'You do not have permission to create rooms.');
  }
  elseif ($data = $slaveDatabase->getRooms(array(
    'roomNames' => array($request['roomName'])
  ))->getCount() > 0) {
    new fimError('roomExists', 'A room with the name specified already exists.');
  }
  else {
    $room = new fimRoom(false);
    $room->set($requestP);
    alterRoomPermissions($room, $requestP['userPermissions'], $requestP['groupPermissions']);
  }
}

/* Edit, Delete, Undelete, or Get */
else {
  $rooms = $database->getRooms(array(
    'roomIds' => $requestG['roomIds'],
    'roomNames' => $requestG['roomNames'],
    'showDeleted' => $requestG['showDeleted'],
    'roomNameSearch' => $requestG['search'],
    'ownerIds' => ($requestG['permFilter'] === 'own' ? array($user->id) : array())
  ), array($request['sort'] => 'asc'))->getAsRooms();

  foreach ($rooms AS $roomId => $room) {
    $permissions = $database->hasPermission($user, $room);

    switch ($requestG['action']) {
      case 'get':
        if ($request['permFilter'] !== 'view' && !($permissions & $permFilterMatches[$request['permFilter']])) continue;

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

        if ($permissions & ROOM_PERMISSION_VIEW) { // These are not shown to users who are not allowed to access the room.
          $xmlData['getRooms']['rooms']['room ' . $roomId]['roomTopic'] = $roomData['roomTopic'];
          $xmlData['getRooms']['rooms']['room ' . $roomId]['ownerId'] = $roomData['owner'];
          $xmlData['getRooms']['rooms']['room ' . $roomId]['lastMessageId'] = $roomData['lastMessageId'];
          $xmlData['getRooms']['rooms']['room ' . $roomId]['lastMessageTime'] = $roomData['lastMessageTime'];
          $xmlData['getRooms']['rooms']['room ' . $roomId]['messageCount'] = $roomData['messageCount'];
        }

        if ($permissions & ROOM_PERMISSION_MODERATE && in_array('permissions', $requestG['info'])) { // Fetch user and group permissions if the active user is a moderator.
          foreach (array('user', 'group') AS $attribute) {
            foreach ($database->getRoomPermissions(array($room->id), $attribute) AS $permission) {
              $xmlData['getRooms']['rooms']['room ' . $roomId][$attribute . 'Permissions'][$permission['param']] = $permission['permissions'];
            }
          }
        }
        break;

      case 'edit':
        if ($permissions & ROOM_PERMISSION_PROPERTIES) $room->set($requestP);
        if ($permissions & ROOM_PERMISSION_GRANT) alterRoomPermissions($room, $requestP['userPermissions'], $requestP['groupPermissions']);
        break;
    }
  }
}



/* Output Data Structure */
new apiData($xmlData, true);
?>