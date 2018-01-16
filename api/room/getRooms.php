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


use Fim\ErrorThrown;
use Fim\Room;

if (!defined('API_INROOM'))
    die();


/* Get Request Data */
$request = fim_sanitizeGPC('g', [
    // No matter what, the user will not be able to see rooms that he is unable to view.
    'permFilter' => [
        'default' => 'view',
        'valid'   => array_merge(array_keys(Room::$permArray), ['own']),
    ],

    'roomIds' => [
        'default'  => [],
        'cast'     => 'list',
        'filter'   => 'roomId',
        'evaltrue' => true,
        'max'      => 50,
    ],

    'roomNames' => [
        'default'  => [],
        'cast'     => 'list',
        'filter'   => 'string',
        'evaltrue' => true,
        'max'      => 50,
    ],

    'roomNameSearch' => [
        'default' => '',
        'cast'    => 'string',
    ],

    'sort' => [
        'valid'   => ['id', 'name', 'lastMessageTime'],
        'default' => 'lastMessageTime',
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

\Fim\Database::instance()->accessLog('getRooms', $request);


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
        $privateRoomIds = [];
        if (isset($request['roomIds'])) {
            foreach ($request['roomIds'] AS $index => $roomId) {
                if (Room::isPrivateRoomId($roomId)) {
                    unset($request['roomIds'][$index]);
                    $privateRoomIds[] = $roomId;
                }
            }
        }

        if (!(count($privateRoomIds) > 0 && count($request['roomIds']) == 0)) {
            $roomsQuery = \Fim\DatabaseSlave::instance()->getRooms(array_merge(
                fim_arrayFilterKeys($request, ['roomIds', 'roomNames', 'showDeleted', 'showHidden', 'roomNameSearch']),
                ['ownerIds' => ($request['permFilter'] === 'own' ? [$user->id] : [])]
            ), [
                'id 1' => \Fim\Database::instance()->in($user->favRooms),
                'options' => \Fim\DatabaseSlave::instance()->int(\Fim\Room::ROOM_OFFICIAL, 'bAnd'),
                $request['sort'] => ($request['sort'] === 'lastMessageTime' ? 'desc' : 'asc')
            ], \Fim\Config::$defaultRoomLimit, $request['page']);

            $rooms = $roomsQuery->getAsRooms();
        }
        else {
            $rooms = [];
        }

        foreach ($privateRoomIds AS $privateRoomId)
            $rooms[] = new Room($privateRoomId);
    }

    foreach ($rooms AS $number => &$roomLocal) {
        $permission = \Fim\Database::instance()->hasPermission($user, $roomLocal);

        if ($request['permFilter'] != 'own'
            && ($permission & Room::$permArray[$request['permFilter']]) != Room::$permArray[$request['permFilter']]) {
            continue;
        }

        $xmlData['rooms']["room {$roomLocal->id}"] = array_merge(
            fim_objectArrayFilterKeys($roomLocal, ['id', 'name', 'ownerId', 'parentalAge', 'official', 'archived', 'hidden', 'deleted', 'topic', 'ownerId', 'lastMessageId', 'lastMessageTime', 'messageCount']),
            [
                'defaultPermissions' => $roomLocal->getPermissionsArray($roomLocal->defaultPermissions),
                'permissions'        => $roomLocal->getPermissionsArray(\Fim\Database::instance()->hasPermission($user, $roomLocal)),
                'parentalFlags'      => new Http\ApiOutputList($roomLocal->parentalFlags)
            ]
        );

        if ($permission & Room::ROOM_PERMISSION_MODERATE) { // Fetch the allowed users and allowed groups if the user is able to moderate the room.
            $xmlData['rooms']["room {$roomLocal->id}"]['userPermissions'] = [];
            foreach (\Fim\Database::instance()->getRoomPermissions([$roomLocal->id], 'user')->getAsArray() AS $row) {
                $xmlData['rooms']["room {$roomLocal->id}"]['userPermissions'][$row['param']] = $roomLocal->getPermissionsArray($row['permissions']);
            }

            $xmlData['rooms']["room {$roomLocal->id}"]['groupPermissions'] = [];
            foreach (\Fim\Database::instance()->getRoomPermissions([$roomLocal->id], 'group')->getAsArray() AS $row) {
                $xmlData['rooms']["room {$roomLocal->id}"]['groupPermissions'][$row['param']] = $roomLocal->getPermissionsArray($row['permissions']);
            }
        }
    }

    $request['page']++;


    // We relog so that the next query counts as part of the flood detection. (If we go over the flood limit, catch the exception and return with where to continue searching from.)
    try {
        \Fim\Database::instance()->accessFlood('getRooms');
    } catch (ErrorThrown $ex) {
        // TODO: test
        $xmlData['metadata']['nextPage'] = $request['page'];
        echo new Http\ApiData($xmlData);
    }

} while (isset($roomsQuery) && $roomsQuery->paginated && count($xmlData['rooms']) == 0);


$xmlData['metadata']['nextPage'] = $request['page'];



/* Output Data Structure */
echo new Http\ApiData($xmlData);
?>