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
 * Get the Active Users of a One or More Rooms
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 * @todo Support Private Rooms
 *
 * =GET Parameters=
 * @param csv rooms - Comma-separated list of rooms to obtain active users for. [[Required.]]
 * @param int onlineThreshold - How recent the user's last ping must be to be considered active. The default is generally recommended, but for special purposes you may wish to increase or decrease this.
 * @param csv users - Restrict the active users result to these users, if specified.
 *
 * =Errors=
 *
 * =Response=
 * @return APIOBJ
 ** getActiveUsers
 *** activeUser
 *** userId
 *** userName
 *** errStr
 *** errDesc
 *** rooms
 **** room $roomId
 ***** roomData
 ****** roomId
 ****** roomName
 ****** roomTopic
 ***** users
 ****** user $userId
 ******* userId
 ******* userName
 ******* userGroup
 ******* socialGroups
 ******* startTag
 ******* endTag
 ******* status
 ******* typing

*/

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
  'rooms' => array(
    'default' => '',
    'require' => true,
    'context' => array(
      'type' => 'csv',
      'filter' => 'int',
      'evaltrue' => true,
    ),
  ),

  'onlineThreshold' => array(
    'default' => (int) $config['defaultOnlineThreshold'],
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
  "{$sqlPrefix}ping" => 'status, typing, time ptime, roomId proomId, userId puserId',
  "{$sqlPrefix}rooms" => 'roomId',
  "{$sqlPrefix}users" => 'userId, userName, userFormatStart, userFormatEnd, userGroup, socialGroups, typing, status',
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
        'value' => (int) ((int) time() - $request['onlineThreshold']),
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
foreach ($request['rooms'] AS $roomId) { // Run through each room.
  // Get the room data.
  $room = $database->select($queryParts['roomSelect']['columns'],
    $queryParts['roomSelect']['conditions'],
    false,
    1);
  $room = $room->getAsArray(false);


  ($hook = hook('getActiveUsers_eachRoom_start') ? eval($hook) : ''); // Hook that will be run at the start of each room.


  if (fim_hasPermission($room, $user, 'know', true) === false) { // The user must be able to know the room exists.
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
	    'startTag' => (string) $activeUser['userFormatStart'],
	    'endTag' => (string) $activeUser['userFormatEnd'],
	    'status' => (string) $activeUser['status'],
	    'typing' => (bool) $activeUser['typing'],
	  );
	}
      }
    }


    ($hook = hook('getActiveUsers_eachRoom_end') ? eval($hook) : ''); // Hook that will be run at the end of each room.
  }
}



/* Update Data for Errors */
$xmlData['getActiveUsers']['errStr'] = (string) $errStr;
$xmlData['getActiveUsers']['errDesc'] = (string) $errDesc;



/* Plugin Hook End */
($hook = hook('getActiveUsers_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);
?>