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
 * Get the Active Users of All Rooms
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
 *
 * @param int [time = time()] - Time in which to determine user activity. Default is the current time.
 * @param int [onlineThreshold = 15] - The period of time after which a user is no longer “active”. Default is 15, which may be overriden in the product configuration.
 * @param string [users = ''] - A comma-sperated list of user IDs to filter by. If not specified, all users will be shown.
 * @todo Update for join
*/

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
  'onlineThreshold' => array(
    'default' => (int) $config['defaultOnlineThreshold'],
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
  'getAllActiveUsers' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'sentData' => array(
      'onlineThreshold' => (int) $request['onlineThreshold'],
      'time' => (int) $time,
    ),
    'errStr' => ($errStr),
    'errDesc' => ($errDesc),
    'users' => array(),
  ),
);

$queryParts['activeUsersSelect']['columns'] = array(
  "{$sqlPrefix}users" => 'userName, userId, userFormatStart, userFormatEnd',
  "{$sqlPrefix}rooms" => 'roomName, roomId, defaultPermissions, owner, options',
  "{$sqlPrefix}ping" => 'time ptime, userId puserId, roomId proomId',
);
$queryParts['activeUsersSelect']['conditions'] = array(
  'both' => array(
    array(
      'type' => 'e',
      'left' => array(
        'type' => 'column',
        'value' => 'userId',
      ),
      'right' => array(
        'type' => 'column',
        'value' => 'puserId',
      ),
    ),
    array(
      'type' => 'e',
      'left' => array(
        'type' => 'column',
        'value' => 'roomId',
      ),
      'right' => array(
        'type' => 'column',
        'value' => 'proomId',
      ),
    ),
    array(
      'type' => 'gt',
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
$queryParts['activeUsersSelect']['limit'] = false;



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
($hook = hook('getAllActiveUsers_start') ? eval($hook) : '');



/* Get Active Users */
$activeUsers = $database->select($queryParts['activeUsersSelect']['columns'],
  $queryParts['activeUsersSelect']['conditions'],
  $queryParts['activeUsersSelect']['sort'],
  $queryParts['activeUsersSelect']['limit']);
$activeUsers = $activeUsers->getAsArray('userId');



/* Start Processing */
if (is_array($activeUsers)) {
  if (count($activeUsers) > 0) {
    foreach ($activeUsers AS $activeUser) {
      ($hook = hook('getAllActiveUsers_eachUser_start') ? eval($hook) : '');

      if (!isset($xmlData['getAllActiveUsers']['users']['user ' . $activeUser['userId']])) {
        $xmlData['getAllActiveUsers']['users']['user ' . $activeUser['userId']] = array(
          'userData' => array(
            'userId' => (int) $activeUser['userId'],
            'userName' => (string) $activeUser['userName'],
            'startTag' => (string) $activeUser['userFormatStart'],
            'endTag' => (string) $activeUser['userFormatEnd'],
          ),
          'rooms' => array(),
        );
      }

      if (fim_hasPermission($activeUser, $activeUser, 'view', false)) { // Only list the room the user is in if the active user has permission to view the room.
        $xmlData['getAllActiveUsers']['users']['user ' . $activeUser['userId']]['rooms']['room ' . $activeUser['roomId']] = array(
          'roomId' => (int) $activeUser['roomId'],
          'roomName' => (string) $activeUser['roomName'],
        );
      }

      ($hook = hook('getAllActiveUsers_eachUser_end') ? eval($hook) : '');
    }
  }
}



/* Update Data for Errors */
$xmlData['getAllActiveUsers']['errStr'] = (string) $errStr;
$xmlData['getAllActiveUsers']['errDesc'] = (string) $errDesc;



/* Plugin Hook End */
($hook = hook('getAllActiveUsers_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);
?>