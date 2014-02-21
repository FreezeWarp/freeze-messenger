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
 * @copyright Joseph T. Parsons 2012
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
    'valid' => array('post', 'view', 'moderate', 'know', 'admin', ''),
    'require' => false,
  ),
  
  'permFilter' => array(
    'default' => 'view',
    'valid' => array('post', 'view', 'moderate', 'own'),
    'require' => false,
  ),

  'rooms' => array(
    'default' => '',
    'cast' => 'csv',
    'filter' => 'int',
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
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => $errStr,
    'errDesc' => $errDesc,
    'rooms' => array(),
  ),
);

$rooms = $database->getRooms($request['rooms'], $request['showDeleted'], $request['search'], null, null, array($request['sort'] => 'asc'), true)->getAsArray(true);

/* Process Rooms Obtained from Database */
// I'll fix the spacing when I get a better editor. Eclipse is seriously the worst thing ever, and I miss KDevelop.
    foreach ($rooms AS $roomData) {
      $roomData['type'] = 'normal'; // hasPermission requires this; it is returned by default from the getRoom function.

      $permissions = fim_hasPermission($roomData, $user, array('post', 'view', 'moderate', 'admin'), false);

      if ($request['permLevel']) {
        if ($permissions[0][$request['permLevel']] === false) {
          continue;
        }
      }

      $xmlData['getRooms']['rooms']['room ' . $roomData['roomId']] = array(
        'roomId' => (int)$roomData['roomId'],
        'roomName' => ($roomData['roomName']),
        'defaultPermissions' => (int) $roomData['defaultPermissions'],
        'parentalFlags' => explode(',', $roomData['parentalFlags']),
        'parentalAge' => $roomData['parentalAge'],
        'options' => (int) $roomData['options'],
        'optionDefinitions' => array(
          'official' => (bool) ($roomData['options'] & 1),
          'deleted' => (bool) ($roomData['options'] & 4),
          'hidden' => (bool) ($roomData['options'] & 8),
          'allowViewing' => (bool) ($roomData['options'] & 32),
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
        if (isset($permissionsCache['byRoomId'][$roomData['roomId']])) {
          $xmlData['getRooms']['rooms']['room ' . $roomData['roomId']]['allowedUsers'] = (array) $generalCache->getPermissions($roomData['roomId'], 'user');
          $xmlData['getRooms']['rooms']['room ' . $roomData['roomId']]['allowedGroups'] = (array) $generalCache->getPermissions($roomData['roomId'], 'group');
        }
      }

      ($hook = hook('getRooms_eachRoom') ? eval($hook) : '');
    }



/* Errors */
$xmlData['getRooms']['errStr'] = ($errStr);
$xmlData['getRooms']['errDesc'] = ($errDesc);



/* Plugin Hook End */
($hook = hook('getRooms_end') ? eval($hook) : '');



/* Output Data Structure */
echo fim_outputApi($xmlData);
?>