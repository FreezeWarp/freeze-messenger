<?php
/* FreezeMessenger Copyright © 2017 Joseph Todd Parsons

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
 * @copyright Joseph T. Parsons 2017
 */


if (!defined('API_INROOM'))
    die();


/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
    // No matter what, the user will not be able to see rooms that he is unable to view.
    'permFilter' => array(
        'default' => 'view',
        'valid' => array_keys(fimRoom::$permArray),
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

    'page' => [
        'cast' => 'int',
        'default' => 0,
    ]
));

$database->accessLog('getRooms', $request);


/* Data Predefine */
$xmlData = [
    'rooms' => [],
    'metadata' => [
        'nextPage' => 0,
    ]
];

do {
    if (isset($room)) // from api/room
        $rooms = [$room];

    else {
        $roomsQuery = $database->getRooms(array(
            'roomIds' => $request['roomIds'],
            'roomNames' => $request['roomNames'],
            'showDeleted' => $request['showDeleted'],
            'roomNameSearch' => $request['search'],
            'ownerIds' => ($request['permFilter'] === 'own' ? array($user->id) : array())
        ), array($request['sort'] => 'asc'), $config['defaultRoomLimit'], $request['page']);
        $rooms = $roomsQuery->getAsRooms();
    }

    foreach ($rooms AS $roomId => $room) {
        $permission = $database->hasPermission($user, $room);

        if (!($permission & fimRoom::$permArray[$request['permFilter']])) continue;

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
            $xmlData['rooms'][$roomId]['lastMessageId'] = $room->lastMessageId;
            $xmlData['rooms'][$roomId]['lastMessageTime'] = $room->lastMessageTime;
            $xmlData['rooms'][$roomId]['messageCount'] = $room->messageCount;
        }

        if ($database->hasPermission($user, $room) & ROOM_PERMISSION_MODERATE) { // Fetch the allowed users and allowed groups if the user is able to moderate the room.
            foreach ($database->getRoomPermissions(array($roomId), 'user')->getAsArray() AS $row) {
                var_dump($row);
                $xmlData['rooms'][$roomId]['userPermissions'][$row['param']] = $row['permissions'];
            }
            foreach ($database->getRoomPermissions(array($roomId), 'group')->getAsArray() AS $row) {
                $xmlData['rooms'][$roomId]['groupPermissions'][$row['param']] = $row['permissions'];
            }
        }
    }

    $request['page']++;
    $database->accessLog('editRoom', $request); // We relog so that the next query counts as part of the flood detection. The only big drawback is that we will throw an exception eventually, without properly informing the user of where to resume searching from. (TODO)
} while(!isset($room) && $roomsQuery->paginated && count($xmlData['rooms']) == 0);


$xmlData['metadata']['nextPage'] = $request['page'];



/* Output Data Structure */
echo new apiData($xmlData);
?>