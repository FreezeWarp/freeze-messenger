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
 * Get the Active Users of a One or More Rooms
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 * @todo Support Private Rooms
 */


/* Prevent Direct Access of File */

use Fim\Room;

if (!defined('API_INUSERSTATUS'))
    die();


/* Get Request Data */
$request = \Fim\Utilities::sanitizeGPC('g', array(
    'roomIds' => array(
        'default' => [],
        'cast' => 'list',
        'filter' => 'roomId',
        'evaltrue' => true,
        'max' => 10
    ),

    'userIds' => array(
        'default' => [],
        'cast' => 'list',
        'filter' => 'int',
        'evaltrue' => true,
        'max' => 10
    ),
));


/* Access Log */
\Fim\Database::instance()->accessLog('getActiveUsers', $request);


/* Request Data Extra Processing */
if (count($request['roomIds']) > 0) {
    // Only include the room \if the active user has permission to know about the room.
    foreach ($request['roomIds'] AS $index => $roomId) {
        if (!(\Fim\Database::instance()->hasPermission($user, new Room($roomId)) & Room::ROOM_PERMISSION_VIEW)) {
            unset($request['roomIds'][$index]);
        }
    }
}


/* Data Predefine */
$xmlData = array(
    'users' => array()
);


/* Get Users from DB */
$activeUsers = \Fim\Database::instance()->getActiveUsers(array(
    'onlineThreshold' => \Fim\Config::$defaultOnlineThreshold,
    'roomIds' => $request['roomIds'],
    'userIds' => $request['userIds']
))->getAsArray(true);


/* Process Users for Output */
foreach ($activeUsers AS $activeUser) {
    if (!isset($xmlData['users']['user ' . $activeUser['userId']])) {
        $xmlData['users']['user ' . $activeUser['userId']] = array(
            'id' => (int) $activeUser['userId'],
            'name' => (int) $activeUser['userName'],
            'rooms' => array(),
        );
    }

    $xmlData['users']['user ' . $activeUser['userId']]['rooms']['room ' . $activeUser['roomId']] = array(
        'id' => (int) $activeUser['roomId'],
        'name' => (string) $activeUser['roomName'],
        'status' => $activeUser['pstatus'] ?: $activeUser['status'],
        'typing' => (bool) $activeUser['typing']
    );
}


/* Output Data */
echo new Http\ApiData($xmlData);