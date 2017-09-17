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
 * @package   fim3
 * @version   3.0
 * @author    Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 */


if (!defined('API_INROOM'))
    die();


/* Get Request Data */
$request = fim_sanitizeGPC('g', [
    // No matter what, the user will not be able to see rooms that he is unable to view.
    'permFilter' => [
        'default' => 'view',
        'valid'   => array_merge(array_keys(fimRoom::$permArray), ['own']),
    ],

    'roomIds' => [
        'default'  => [],
        'cast'     => 'list',
        'filter'   => 'int',
        'evaltrue' => true,
    ],

    'roomNames' => [
        'default'  => [],
        'cast'     => 'list',
        'filter'   => 'string',
        'evaltrue' => true,
    ],

    'roomNameSearch' => [
        'default' => '',
        'cast'    => 'string',
    ],

    'sort' => [
        'valid'   => ['id', 'name'],
        'default' => 'id',
    ],

    'showDeleted' => [
        'cast'    => 'bool',
        'default' => false,
    ],

    'showHidden' => [
        'cast'    => 'bool',
        'default' => false,
    ],

    'page' => [
        'cast'    => 'int',
        'default' => 0,
    ]
]);

if (!$user->hasPriv('modRooms')) {
    $request['showHidden'] = false;
    $request['showDeleted'] = false;
}

$database->accessLog('getRooms', $request);


/* Data Predefine */
$xmlData = [
    'rooms'    => [],
    'metadata' => [
        'nextPage' => 0,
    ]
];

do {
    if (isset($room)) // from api/room
        $rooms = [$room];

    else {
        $roomsQuery = $database->getRooms(array_merge(
            fim_arrayFilterKeys($request, ['roomIds', 'roomNames', 'showDeleted', 'showHidden', 'roomNameSearch']),
            ['ownerIds' => ($request['permFilter'] === 'own' ? [$user->id] : [])]
        ), [
            'id 1' => $database->in($user->favRooms),
            $request['sort'] => 'asc'
        ], $config['defaultRoomLimit'], $request['page']);

        $rooms = $roomsQuery->getAsRooms();
    }


    foreach ($rooms AS $number => $room) {
        $permission = $database->hasPermission($user, $room);

        if (!($permission & fimRoom::$permArray[$request['permFilter']])) continue;

        $xmlData['rooms']["room {$room->id}"] = array_merge(
            fim_objectArrayFilterKeys($room, ['id', 'name', 'ownerId', 'parentalAge', 'official', 'archived', 'hidden', 'deleted', 'topic', 'ownerId', 'lastMessageId', 'lastMessageTime', 'messageCount']),
            [
                'defaultPermissions' => $room->getPermissionsArray($room->defaultPermissions),
                'permissions'        => $room->getPermissionsArray($database->hasPermission($user, $room)),
                'parentalFlags'      => new ApiOutputList($room->parentalFlags)
            ]
        );

        if ($permission & fimRoom::ROOM_PERMISSION_MODERATE) { // Fetch the allowed users and allowed groups if the user is able to moderate the room.
            $xmlData['rooms']["room {$room->id}"]['userPermissions'] = [];
            foreach ($database->getRoomPermissions([$room->id], 'user')->getAsArray() AS $row) {
                $xmlData['rooms']["room {$room->id}"]['userPermissions'][$row['param']] = $room->getPermissionsArray($row['permissions']);
            }

            $xmlData['rooms']["room {$room->id}"]['groupPermissions'] = [];
            foreach ($database->getRoomPermissions([$room->id], 'group')->getAsArray() AS $row) {
                $xmlData['rooms']["room {$room->id}"]['groupPermissions'][$row['param']] = $room->getPermissionsArray($row['permissions']);
            }
        }
    }

    $request['page']++;
    $database->accessLog('editRoom', $request); // We relog so that the next query counts as part of the flood detection. The only big drawback is that we will throw an exception eventually, without properly informing the user of where to resume searching from. (TODO)
} while (!isset($room) && $roomsQuery->paginated && count($xmlData['rooms']) == 0);


$xmlData['metadata']['nextPage'] = $request['page'];



/* Output Data Structure */
echo new ApiData($xmlData);
?>