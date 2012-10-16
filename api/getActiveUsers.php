<?php
/* FreezeMessenger Copyright © 2011 Joseph Todd Parsons

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
 * Get the Active Users of a Single Room
 * Works with both normal and private rooms.
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
 *
 * @param string rooms - A comma-seperated list of room IDs to query.
 * @param int [time = time()] - Time in which to determine user activity. Default is the current time.
 * @param int [onlineThreshold = 15] - The period of time after which a user is no longer “active”. Default is 15, which may be overriden in the product configuration.
 * @param string [users = ''] - A comma-sperated list of user IDs to filter by. If not specified, all users will be shown.
*/

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
  'rooms' => array(
    'default' => '',
    'context' => array(
      'type' => 'csv',
      'filter' => 'int',
      'evaltrue' => true,
    ),
  ),

  'onlineThreshold' => array(
    'default' => ($onlineThreshold ? $onlineThreshold : 15),
    'context' => 'int',
  ),

  'time' => array(
    'default' => (int) time(),
    'context' => 'int',
  ),

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
  'getActiveUsers' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => (string) $user['userName'],
    ),
    'errStr' => (string) $errStr,
    'errDesc' => (string) $errDesc,
    'rooms' => array(),
  ),
);

$queryParts['roomSelect'] = array(
  'columns' => array(
    "{$sqlPrefix}rooms" => array(
      'roomId' => 'roomId',
      'roomName' => 'roomName',
      'roomTopic' => 'roomTopic',
      'defaultPermissions' => 'defaultPermissions',
    ),
  ),
  'conditions' => array(
    'both' => array(
      array(
        'type' => 'e',
        'left' => array(
          'type' => 'column',
          'value' => 'roomId',
        ),
        'right' => array(
          'type' => 'int',
          'value' => (int) $roomId,
        ),
      ),
    ),
  ),
);

$queryParts['activeUsersSelect']['columns'] = array(
  "{$sqlPrefix}ping" => array(
    'status' => 'status',
    'typing' => 'typing',
    'time' => 'ptime',
    'roomId' => 'proomId',
    'userId' => 'puserId',
  ),
  "{$sqlPrefix}rooms" => array(
    'roomId' => 'roomId',
  ),
  "{$sqlPrefix}users" => array(
    'userId' => 'userId',
    'userName' => 'userName',
  ),
);
$queryParts['activeUsersSelect']['conditions'] = array(
  'both' => array(
    array(
      'type' => 'e',
      'left' => array(
        'type' => 'column',
        'value' => 'roomId',
      ),
      'right' => array(
        'type' => 'int',
        'value' => (int) $room['roomId'],
      ),
    ),
    array(
      'type' => 'e',
      'left' => array(
        'type' => 'column',
        'value' => 'proomId',
      ),
      'right' => array(
        'type' => 'column',
        'value' => 'roomId',
      ),
    ),
    array(
      'type' => 'e',
      'left' => array(
        'type' => 'column',
        'value' => 'puserId',
      ),
      'right' => array(
        'type' => 'column',
        'value' => 'userId',
      ),
    ),
    array(
      'type' => 'gte',
      'left' => array(
        'type' => 'column',
        'value' => 'ptime',
      ),
      'right' => array(
        'type' => 'int',
        'value' => (int) ($request['time'] - $request['onlineThreshold']),
      ),
    ),
  ),
);
$queryParts['activeUsersSelect']['sort'] = array(
  'userName' => 'asc',
);



/* Modify Query Data for Directives */
if (count($request['users']) > 0) {
  $queryParts['activeUsersSelect']['conditions']['both'][] = array(
    'type' => 'in',
    'left' => array(
      'type' => 'column',
      'value' => 'puserId',
    ),
    'right' => array(
       'type' => 'array',
       'value' => (array) $request['users'],
    ),
  );
}



/* Plugin Hook Start */
($hook = hook('getActiveUsers_start') ? eval($hook) : '');



/* Start Processing */
if (count($request['rooms']) > 0) {
  foreach ($request['rooms'] AS $roomId) { // Run through each room.
    // Get the room data.
    $room = $database->select($queryParts['roomSelect']['columns'],
      $queryParts['roomSelect']['conditions'],
      false,
      1);
    $room = $room->getAsArray(false);


    ($hook = hook('getActiveUsers_eachRoom_start') ? eval($hook) : ''); // Hook that will be run at the start of each room.


    if (fim_hasPermission($room,$user,'know',true) === false) { // The user must be able to know the room exists.
      ($hook = hook('getActiveUsers_eachRoom_noPerm') ? eval($hook) : ''); // Hook that will be executed if the user does not have permission here.

      continue; // Skip to next iteration (strictly speaking, redundant)
    }
    else {
      $activeUsers = $database->select($queryParts['activeUsersSelect']['columns'],
        $queryParts['activeUsersSelect']['conditions'],
        $queryParts['activeUsersSelect']['sort']);



      /* Define Room Summary */
      $xmlData['getActiveUsers']['rooms']['room ' . $room['roomId']] = array(
        'roomData' => array(
          'roomId' => (int) $activeUser['roomId'],
          'roomName' => (string) $activeUser['roomName'],
          'roomTopic' => (string) $activeUser['roomTopic'],
        ),
        'users' => array(),
      );



      /* Process Active Users */
      if (is_array($activeUsers)) {
        if (count($activeUsers) > 0) {
          foreach ($activeUsers AS $activeUser) {
            ($hook = hook('getActiveUsers_eachUser') ? eval($hook) : ''); // Hook that will be run for each active user.

            $xmlData['getActiveUsers']['rooms']['room ' . $room['roomId']]['users']['user ' . $activeUser['userId']] = array(
              'userId' => (int) $activeUser['userId'],
              'userName' => (string) $activeUser['userName'],
              'userGroup' => (int) $activeUser['userGroup'],
              'socialGroups' => (string) $activeUser['socialGroups'],
              'startTag' => (string) $activeUser['startTag'],
              'endTag' => (string) $activeUser['endTag'],
              'status' => (string) $activeUser['status'],
              'typing' => (bool) $activeUser['typing'],
            );
          }
        }
      }


      ($hook = hook('getActiveUsers_eachRoom_end') ? eval($hook) : ''); // Hook that will be run at the end of each room.
    }
  }
}
else {
  $errStr = 'badRoomsRequest';
  $errDesc = 'The room string was not supplied or evaluated to false.';
}



/* Update Data for Errors */
$xmlData['getActiveUsers']['errStr'] = (string) $errStr;
$xmlData['getActiveUsers']['errDesc'] = (string) $errDesc;



/* Plugin Hook End */
($hook = hook('getActiveUsers_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);
?>