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

/**
 * Get Rooms from the Server
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
 * @param bool [showDeleted=false] - Will attempt to show deleted rooms, assuming the user has access to them (that is, is an administrator). Defaults to false.
 * @param string [order=roomId] - How the rooms should be ordered (either roomId or roomName).
 * @param string [rooms] - If specified, only specific rooms are listed. By default, all rooms are listed.
*/

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
  'permLevel' => array(
    'default' => '',
    'valid' => array(
      'post', 'view', 'moderate', 'know', 'admin', ''
    ),
    'require' => false,
  ),

  'rooms' => array(
    'default' => '',
    'context' => array(
      'type' => 'csv',
      'filter' => 'int',
      'evaltrue' => true,
    ),
  ),

  'sort' => array(
    'valid' => array(
      'roomId', 'roomName', 'smart',
    ),
    'default' => 'roomId',
  ),

  'showDeleted' => array(
    'context' => 'bool',
    'default' => false,
  ),
));

/* Data Predefine */
$xmlData = array(
  'getRooms' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => $errStr,
    'errDesc' => $errDesc,
    'rooms' => array(),
  ),
);

$queryParts['roomSelect'] = array(
  'columns' => array(
    "{$sqlPrefix}rooms" => 'roomId, roomName, options, defaultPermissions, owner, roomTopic, lastMessageId, lastMessageTime, messageCount',
  ),
  'conditions' => array(
    'both' => array(

     ),
  ),
);



/* Modify Query Data for Directives */
if ($request['showDeleted'] === false) { // We will also check to make sure the user has moderation priviledges after the select. (TODO: Wait, what the heck does this do?)
  $queryParts['roomSelect']['conditions']['both'][] = array(
    'type' => 'xor',
    'left' => array(
      'type' => 'column',
      'value' => 'options',
    ),
    'right' => array(
      'type' => 'int',
      'value' => 4,
    ),
  );
}
if (count($request['rooms']) > 0) {
  $queryParts['roomSelect']['conditions']['both'][] = array(
    'type' => 'in',
    'left' => array(
      'type' => 'column',
      'value' => 'roomId',
    ),
    'right' => array(
      'type' => 'array',
      'value' => $request['rooms'],
    ),
  );
}



/* Query Results Order
 * roomId*, roomName */
switch ($request['sort']) {
  case 'roomName':
  $queryParts['roomSelect']['sort'] = array(
    'roomName' => 'asc',
  );
  break;

  case 'roomId':
  default:
  $queryParts['roomSelect']['sort'] = array(
    'roomId' => 'asc',
  );
  break;
}



/* Get User's Favourite Rooms as Array */
if (isset($user['favRooms'])) {
  $favRooms = fim_arrayValidate(explode(',', $user['favRooms']), 'int', false); // All entries cast as integers, will not preserve entries of zero.
}
else {
  $favRooms = array();
}



/* Plugin Hook Start */
($hook = hook('getRooms_start') ? eval($hook) : '');



/* Get Rooms From Database */
$rooms = $database->select(
  $queryParts['roomSelect']['columns'],
  $queryParts['roomSelect']['conditions'],
  $queryParts['roomSelect']['sort']);
$rooms = $rooms->getAsArray(true);



/* Process Rooms Obtained from Database */
if (is_array($rooms)) {
  if (count($rooms) > 0) {
    foreach ($rooms AS $roomData) {
      $permissions = fim_hasPermission($roomData, $user, array('post', 'view', 'topic', 'moderate', 'admin'), false);

      if ($request['permLevel']) {
        if ($permissions[0][$request['permLevel']] === false) {
          continue;
        }
      }

      $xmlData['getRooms']['rooms']['room ' . $roomData['roomId']] = array(
        'roomId' => (int)$roomData['roomId'],
        'roomName' => ($roomData['roomName']),
        'defaultPermissions' => (int) $roomData['defaultPermissions'],
        'favorite' => (bool) (in_array($roomData['roomId'],$favRooms) ? true : false),
        'options' => (int) $roomData['options'],
        'optionDefinitions' => array(
          'official' => (bool) ($roomData['options'] & 1),
          'mature' => (bool) ($roomData['options'] & 2),
          'deleted' => (bool) ($roomData['options'] & 4),
          'hidden' => (bool) ($roomData['options'] & 8),
          'privateIm' => (bool) ($roomData['options'] & 16),
        ),
        'permissions' => array(
          'canModerate' => (bool) $permissions[0]['moderate'],
          'canAdmin' => (bool) $permissions[0]['admin'],
          'canPost' => (bool) $permissions[0]['post'],
          'canView' => (bool) $permissions[0]['view'],
        ),
      );

      if ($permissions[0]['view']) { // These are not shown to users who are not allowed to access the room.
        $xmlData['getRooms']['rooms']['room ' . $roomData['roomId']]['roomTopic'] = $roomData['roomTopic'];
        $xmlData['getRooms']['rooms']['room ' . $roomData['roomId']]['owner'] = $roomData['owner'];
        $xmlData['getRooms']['rooms']['room ' . $roomData['roomId']]['lastMessageId'] = $roomData['lastMessageId'];
        $xmlData['getRooms']['rooms']['room ' . $roomData['roomId']]['lastMessageTime'] = $roomData['lastMessageTime'];
        $xmlData['getRooms']['rooms']['room ' . $roomData['roomId']]['messageCount'] = $roomData['messageCount'];
      }

      if ($permissions[0]['moderate']) { // Fetch the allowed users and allowed groups if the user is able to moderate the room.
        if (isset($permissionsCache[$roomData['roomId']])) {
          $xmlData['getRooms']['rooms']['room ' . $roomData['roomId']]['allowedUsers'] = (array) $permissionsCache[$roomData['roomId']]['user'];
          $xmlData['getRooms']['rooms']['room ' . $roomData['roomId']]['allowedGroups'] = (array) $permissionsCache[$roomData['roomId']]['group'];
        }
      }

      ($hook = hook('getRooms_eachRoom') ? eval($hook) : '');
    }
  }
}



/* Errors */
$xmlData['getRooms']['errStr'] = ($errStr);
$xmlData['getRooms']['errDesc'] = ($errDesc);



/* Plugin Hook End */
($hook = hook('getRooms_end') ? eval($hook) : '');



/* Output Data Structure */
echo fim_outputApi($xmlData);
?>