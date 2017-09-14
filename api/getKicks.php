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
 * Only works with normal roooms.
 * You must have moderator permission of the room for successful retrieval!
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 *
 * @param string rooms - A comma-seperated list of room IDs to get.
 * @param string users - A comma-seperated list of user IDs to get.
*/

$apiRequest = true;

require('../global.php');



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
$database->accessLog('getKicks', $request);



/* Data Predefine */
$xmlData = array(
    'kicks' => array(),
);


/* Get Kicks from Database */
$kicks = $database->getKicks(array(
  'userIds' => $request['userIds'],
  'roomIds' => $request['roomIds']
))->getAsArray(true);


/* Start Processing */
foreach ($kicks AS $kick) {
    if ($kick['userId'] == $user->id || $database->hasPermission($user, new fimRoom((int) $kick['roomId'])) & fimRoom::ROOM_PERMISSION_MODERATE) { // The user is allowed to know of all kicks they are subject to, and of all kicks in any rooms they moderate.
        $xmlData['kicks']['kick ' . $kick['kickId']] = [
            'roomData'   => [
                'roomId'   => (int)$kick['roomId'],
                'roomName' => (string)$kick['roomName'],
            ],
            'userData'   => [
                'userId'         => (int)$kick['userId'],
                'userName'       => (string)$kick['userName'],
                'userNameFormat' => (string)$kick['userNameFormat'],
            ],
            'kickerData' => [
                'userId'         => (int)$kick['kickerId'],
                'userName'       => (string)$kick['kickerName'],
                'userNameFormat' => (string)$kick['userNameFormat'],
            ],
            'length'     => (int)$kick['klength'],

            'set'     => (int)$kick['time'],
            'expires' => (int)($kick['time'] + $kick['length']),
        ];
    }
}


/* Output Data */
echo new ApiData($xmlData);
?>