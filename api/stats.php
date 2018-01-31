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
 * @copyright Joseph T. Parsons 2017
 *
 * @param string rooms - A comma-seperated list of room IDs to get.
 * @param int [number = 10] - The number of top posters to obtain.
 */

use Fim\Error;
use Fim\Room;

$apiRequest = true;

require('../global.php');



/* Get Request */
$request = \Fim\Utilities::sanitizeGPC('g', array(
    'roomId' => array(
        'cast' => 'roomId',
        'require' => true,
    ),

    'userIds' => array(
        'default' => [],
        'cast' => 'list',
        'filter' => 'int',
        'evaltrue' => true,
    ),

    'page' => [
        'cast' => 'int',
        'default' => 0
    ]
));
\Fim\Database::instance()->accessLog('getStats', $request);



/* Data Predefine */
$xmlData = array(
    'roomStats' => array(),
);



/* Start Processing */

if (!($room = \Fim\RoomFactory::getFromId($request['roomId']))->exists() || !(\Fim\Database::instance()->hasPermission($user, $room) & Room::ROOM_PERMISSION_VIEW)) {
    new \Fim\Error('roomIdNoExist', 'The given "roomId" parameter does not correspond with a real room.');
}

else {
    $totalPosts = \Fim\DatabaseSlave::instance()->getPostStats(array(
        'roomId' => $request['roomId'],
    ), array('messages' => 'desc', 'roomId' => 'asc', 'userId' => 'asc'), 10, $request['page'])->getAsArray(['userId']);

    foreach ($totalPosts AS $roomId => $totalPoster) {
        $xmlData['roomStats']['room ' . $totalPoster['roomId']]['users']['user ' . $totalPoster['userId']] = [
            'id' => (int) $totalPoster['userId'],
            'name' => $totalPoster['userName'],
            'format' => $totalPoster['userNameFormat'],
            'avatar' => $totalPoster['avatar'],
            'messageCount' => (int) $totalPoster['messages'],
        ];
    }
}



/* Output Data */
echo new Http\ApiData($xmlData);
?>