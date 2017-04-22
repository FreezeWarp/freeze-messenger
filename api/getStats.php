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
 * Obtains User Post Counts in Specified Rooms
 * Only works with normal rooms.
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2014
 *
 * @param string rooms - A comma-seperated list of room IDs to get.
 * @param int [number = 10] - The number of top posters to obtain.
 */

$apiRequest = true;

require('../global.php');



/* Get Request */
$request = fim_sanitizeGPC('g', array(
    'rooms' => array(
        'default' => [],
        'cast' => 'list',
        'filter' => 'int',
        'evaltrue' => true,
    ),

    'users' => array(
        'default' => [],
        'cast' => 'list',
        'filter' => 'int',
        'evaltrue' => true,
    ),

    'number' => array(
        'default' => 10,
        'cast' => 'int',
    ),
));



/* Data Predefine */
$xmlData = array(
    'roomStats' => array(),
);



/* Start Processing */

$totalPosts = $database->getPostStats(array(
    'roomIds' => $request['rooms'],
))->getAsArray(array('roomId', 'userId'), false);


foreach ($totalPosts AS $room) {
    foreach ($room AS $roomId => $totalPoster) {
        // Users must be able to view the room to see the respective post counts.
        if (!($database->hasPermission($user, new fimRoom($roomId)) & ROOM_PERMISSION_VIEW)) {
            continue;
        }

        if (!isset($xmlData['roomStats']['room ' . $totalPoster['roomId']])) {
            $xmlData['roomStats']['room ' . $totalPoster['roomId']] = array(
                'roomData' => array(
                    'roomId' => (int) $totalPoster['roomId'],
                    'roomName' => $totalPoster['roomName'],
                ),
                'users' => array(),
            );
        }

        $xmlData['roomStats']['room ' . $totalPoster['roomId']]['users']['user ' . $totalPoster['userId']] = array(
            'userData' => array(
                'userId' => (int) $totalPoster['userId'],
                'userName' => $totalPoster['userName'],
                'userNameFormat' => $totalPoster['userNameFormat'],
            ),
            'messageCount' => (int) $totalPoster['messageCount'],
        );
    }
}



/* Output Data */
echo new apiData($xmlData);
?>