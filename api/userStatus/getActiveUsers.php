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
if (!defined('API_INUSERSTATUS'))
    die();


/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
    'roomIds' => array(
        'default' => [],
        'cast' => 'list',
        'filter' => 'roomId',
        'evaltrue' => true,
    ),

    'userIds' => array(
        'default' => [],
        'cast' => 'list',
        'filter' => 'int',
        'evaltrue' => true,
    ),
));

$database->accessLog('getActiveUsers', $request);


/* Request Data Extra Processing */
if (count($request['roomIds']) > 0) {
    // Only include the room \if the active user has permission to know about the room.
    foreach ($request['roomIds'] AS $index => $roomId) {
        if (!($database->hasPermission($user, new fimRoom($roomId)) & fimRoom::ROOM_PERMISSION_VIEW)) {
            unset($request['roomIds'][$index]);
        }
    }
}


/* Data Predefine */
$xmlData = array(
    'users' => array()
);


$activeUsers = $database->getActiveUsers(array(
    'onlineThreshold' => $config['defaultOnlineThreshold'],
    'roomIds' => $request['roomIds'],
    'userIds' => $request['userIds']
))->getAsArray(true);


foreach ($activeUsers AS $activeUser) {
    if (!isset($xmlData['users']['user ' . $activeUser['userId']])) {
        $xmlData['users']['user ' . $activeUser['userId']] = array(
            'userData' => array(
                'userId' => (int) $activeUser['userId'],
                'userName' => (string) $activeUser['userName'],
                'userNameFormat' => (string) $activeUser['userNameFormat'],
            ),
            'rooms' => array(),
        );
    }

    $xmlData['users']['user ' . $activeUser['userId']]['rooms']['room ' . $activeUser['roomId']] = array(
        'roomId' => (int) $activeUser['roomId'],
        'roomName' => (string) $activeUser['roomName'],
        'status' => (bool) $activeUser['status']
    );

    // Data that is only applicable on a per-room basis.
    if (count($request['roomIds']) > 0) {
        $xmlData['users']['user ' . $activeUser['userId']]['rooms']['room ' . $activeUser['roomId']]['typing'] = (bool) $activeUser['typing'];
    }
}



/* Output Data */
echo new apiData($xmlData);
?>