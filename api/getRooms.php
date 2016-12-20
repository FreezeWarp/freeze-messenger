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
        'default' => [],
        'cast' => 'list',
        'filter' => 'int',
        'evaltrue' => true,
    ),

    'roomNames' => array(
        'default' => [],
        'cast' => 'list',
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
        'default' => '',
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
    'rooms' => array(),
);

$rooms = $database->getRooms(array(
    'roomIds' => $request['roomIds'],
    'roomNames' => $request['roomNames'],
    'showDeleted' => $request['showDeleted'],
    'roomNameSearch' => $request['search'],
    'ownerIds' => ($request['permFilter'] === 'own' ? array($user->id) : array())
), array($request['sort'] => 'asc'))->getAsRooms();

foreach ($rooms AS $roomId => $room) {
//  if (!($permissions & $permFilterMatches[$request['permFilter']])) continue;

    $xmlData['rooms'][$roomId] = array(
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
        'permissions' => $room->getPermissionsArray($database->hasPermission($user, $room))
    );

    if ($database->hasPermission($user, $room) & ROOM_PERMISSION_VIEW) { // These are not shown to users who are not allowed to access the room.
        $xmlData['rooms'][$roomId]['roomTopic'] = $room->topic;
        $xmlData['rooms'][$roomId]['owner'] = $room->ownerId;
        $xmlData['rooms'][$roomId]['lastMessageId'] = $roomData['lastMessageId'];
        $xmlData['rooms'][$roomId]['lastMessageTime'] = $roomData['lastMessageTime'];
        $xmlData['rooms'][$roomId]['messageCount'] = $roomData['messageCount'];
    }

    if ($database->hasPermission($user, $room) & ROOM_PERMISSION_MODERATE) { // Fetch the allowed users and allowed groups if the user is able to moderate the room.
        foreach ($database->getRoomPermissions(array($roomId), 'user')->getAsArray() AS $row) { var_dump($row);
            $xmlData['rooms'][$roomId]['userPermissions'][$row['param']] = $row['permissions'];
        }
        foreach ($database->getRoomPermissions(array($roomId), 'group')->getAsArray() AS $row) {
            $xmlData['rooms'][$roomId]['groupPermissions'][$row['param']] = $row['permissions'];
        }
    }
}



/* Output Data Structure */
echo new apiData($xmlData);
?>