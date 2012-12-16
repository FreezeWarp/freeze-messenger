<?php
/* FreezeMessenger Copyright © 2012 Joseph Todd Parsons

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
 * @copyright Joseph T. Parsons 2012
 * @param string users - CSV list of users (the active user may be omitted).
 *
 * TODO -- Ignore List
*/

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
  'users' => array(
    'default' => '',
    'context' => array(
      'type' => 'csv',
      'filter' => 'int',
      'evaltrue' => true,
    ),
  ),
));

/* Data Predefine */
$xmlData = array(
  'getPrivateRoom' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => $errStr,
    'errDesc' => $errDesc,
    'room' => array(),
  ),
);



/* Plugin Hook Start */
($hook = hook('getPrivateRoom_start') ? eval($hook) : '');

if (!$user['userDefs']['privateRooms']) {
  $errStr = 'noPerm';
  $errDesc = 'You do not have permission to create private rooms.';
}
else {
  /* Get Rooms From Database */
  if (!in_array($user['userId'], $request['users'])) {
    $request['users'][] = $user['userId'];
  }


  $room = $database->getPrivateRoom($request['users']);



  /* Process Rooms Obtained from Database */
  $xmlData['getPrivateRoom']['room']['uniqueId'] = $room['uniqueId'];
  $xmlData['getPrivateRoom']['room']['roomUsersHash'] = $room['roomUsersHash'];
  $xmlData['getPrivateRoom']['room']['roomUsersList'] = $room['roomUsersList'];
  $xmlData['getPrivateRoom']['room']['lastMessageId'] = $room['lastMessageId'];
  $xmlData['getPrivateRoom']['room']['lastMessageTime'] = $room['lastMessageTime'];
  $xmlData['getPrivateRoom']['room']['messageCount'] = $room['messageCount'];

  $xmlData['getPrivateRoom']['room']['roomUsers'] = array();

  foreach (explode(',', $room['roomUsersList']) AS $roomUser) {
    $userData = $database->getUser($roomUser);

    $xmlData['getPrivateRoom']['room']['roomUsers']['user ' . $roomUser] = $userData;
  }
}



/* Errors */
$xmlData['getPrivateRoom']['errStr'] = ($errStr);
$xmlData['getPrivateRoom']['errDesc'] = ($errDesc);



/* Plugin Hook End */
($hook = hook('getPrivateRoom_end') ? eval($hook) : '');



/* Output Data Structure */
echo fim_outputApi($xmlData);
?>