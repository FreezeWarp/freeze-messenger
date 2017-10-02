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
 * Get the Kicks of One or More Rooms, Optionally Restricted To One or More Users
 * Only works with normal rooms.
 *
 * @package   fim3
 * @version   3.0
 * @author    Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 *
 * @global fimConfig   $config
 * @global fimDatabase $database
 * @global fimUser     $user
 * @global fimUser     $kickUser
 * @global fimRoom     $room
 * @global int         $permission
 */

if (!defined('API_INKICK'))
    die();

$database->accessLog('getKicks', $requestHead);



/* Data Predefine */
$xmlData = [
    'kicks' => [],
];




/* Start Processing */
if (!isset($requestHead['roomId']) && !$user->hasPriv('modRooms'))
    new fimError('roomIdRequired', 'A roomId must be included unless you are a site administrator.');

else {
    /* Get Kicks from Database */
    $kicks = $database->getKicks([
        'userIds' => isset($kickUser) ? [$kickUser->id] : [],
        'roomIds' => isset($room) ? [$room->id] : [],
    ])->getAsArray(true);


    /* Process Kicks from Database */
    foreach ($kicks AS $kick) {
        if (!isset($xmlData['kicks']['user ' . $kick['userId']])) {
            $xmlData['kicks']['user ' . $kick['userId']] = [
                'userId'         => (int)$kick['userId'],
                'userName'       => $kick['userName'],
                'userAvatar'     => $kick['userAvatar'],
                'userNameFormat' => $kick['userNameFormat'],
                'kicks'          => []
            ];
        }

        $xmlData['kicks']['user ' . $kick['userId']]['kicks']['room ' . $kick['roomId']] = array_merge(
            fim_arrayFilterKeys($kick, ['roomId', 'roomName', 'kickerId', 'kickerName', 'kickerNameFormat', 'kickerAvatar', 'length']),
            [
                'set'              => (int)$kick['time'],
                'expires'          => (int)($kick['time'] + $kick['length']),
            ]
        );
    }
}


/* Output Data */
echo new Http\ApiData($xmlData);
?>