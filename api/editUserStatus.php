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
 * Sets a User's Activity Status
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2014
 *
 * @param int roomId - A comma-seperated list of room IDs to get.
 * @param string statusType - The type of status.
 * @param string statusValue - The value of the status type.
*/

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('p', array(
  'roomIds' => array(
    'cast' => 'jsonList',
    'filter' => 'int'
  ),

  'status' => array(
    'valid' => array('away', 'busy', 'available', 'invisible', 'offline')
  ),

  'type' => array(
    'cast' => 'bool',
  )
));



/* Get Room Data */
$rooms = $slaveDatabase->getRooms(array(
  'roomIds' => $request['roomIds'],
))->getAsArray('roomId');



if (!count($rooms)) throw new Exception('invalidRooms');
else {
  foreach ($rooms AS $room) {
    if (!fim_hasPermission($room, $user, 'view', true)) continue;

    $database->setUserStatus($room['roomId']);
  }
}


$xmlData = array(
  'setUserStatus' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
  ),
);



/* Output Data */
echo new apiData($xmlData);
?>