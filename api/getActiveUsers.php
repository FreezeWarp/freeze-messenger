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
 * Get the Active Users of a One or More Rooms
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2014
 * @todo Support Private Rooms
 *
 * =GET Parameters=
 * @param csv rooms - Comma-separated list of rooms to obtain active users for. [[Required.]]
 * @param int onlineThreshold - How recent the user's last ping must be to be considered active. The default is generally recommended, but for special purposes you may wish to increase or decrease this.
 * @param csv users - Restrict the active users result to these users, if specified.
 */

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
  'roomIds' => array(
    'default' => '',
    'cast' => 'jsonList',
    'filter' => 'int',
    'evaltrue' => true,
  ),

  'onlineThreshold' => array(
    'default' => (int) $config['defaultOnlineThreshold'],
    'cast' => 'int',
  ),

  'userIds' => array(
    'default' => '',
    'cast' => 'jsonList',
    'filter' => 'int',
    'evaltrue' => true,
  ),
));



/* Data Predefine */
$xmlData = array(
  'getActiveUsers' => array(
    'rooms' => array(),
  ),
);


$activeUsers = $database->getActiveUsers(array(
  'onlineThreshold' => $request['onlineThreshold'],
  'roomIds' => $request['roomIds'],
  'userIds' => $request['userIds']
));

//var_dump($activeUsers); die();


/* Start Processing */
if (count($request['roomIds']) > 0) { /* TODO: Remove? */
  foreach ($activeUsers->getAsArray('roomId', true) AS $roomId => $room) { // Run through each room.
    if (fim_hasPermission($room, $user, 'know', true) === false) { // The user must be able to know the room exists.
      continue; // Skip to next iteration (strictly speaking, redundant)
    }
    else {
      /* Define Room Summary */
      $xmlData['rooms']['room ' . $room['roomId']] = array(
        'roomData' => array(
          'roomId' => (int) $activeUser['roomId'],
          'roomName' => (string) $activeUser['roomName'],
          'roomTopic' => (string) $activeUser['roomTopic'],
        ),
        'users' => array(),
      );

      foreach ($room AS $activeUser) {
        $xmlData['rooms']['room ' . $room['roomId']]['users']['user ' . $activeUser['userId']] = array(
          'userId' => (int) $activeUser['userId'],
          'userName' => (string) $activeUser['userName'],
          'userGroup' => (int) $activeUser['userGroup'],
          'socialGroups' => (string) $activeUser['socialGroups'],
          'userNameFormat' => (string) $activeUser['userNameFormat'],
          'status' => (string) $activeUser['status'],
          'typing' => (bool) $activeUser['typing'],
        );
      }
    }
  }
}
else {
  foreach ($activeUsers->getAsArray(true) AS $activeUser) {
    $activeUser['type'] = 'normal';

    if (!isset($xmlData['users']['user ' . $activeUser['userId']])) {
      $xmlData['users']['user ' . $activeUser['userId']] = array(
        'userData' => array(
          'userId' => (int) $activeUser['userId'],
          'userName' => (string) $activeUser['userName'],
          'userGroup' => (string) $activeUser['userGroup'],
          'socialGroups' => (string) $activeUser['socialGroups'],
          'userNameFormat' => (string) $activeUser['userNameFormat'],
        ),
        'rooms' => array(),
      );
    }

    if (fim_hasPermission($activeUser, $user, 'view', false)) { // Only list the room the user is in if the active user has permission to view the room.
      $xmlData['users']['user ' . $activeUser['userId']]['rooms']['room ' . $activeUser['roomId']] = array(
        'roomId' => (int) $activeUser['roomId'],
        'roomName' => (string) $activeUser['roomName'],
      );
    }
  }
}


/* Update Data for Errors */
$xmlData['errStr'] = (string) $errStr;



/* Output Data */
echo new apiData($xmlData);
?>