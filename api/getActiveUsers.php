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
 * Get Messages from the Server
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
 *
 * @param string rooms - The rooms to query.
 * @param int onlineThreshold - The period of time after which a user is no longer “active”.
*/

$apiRequest = true;

require_once('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC(array(
  'get' => array(
    'rooms' => array(
      'type' => 'string',
      'require' => false,
      'default' => '',
      'context' => array(
         'type' => 'csv',
         'filter' => 'int',
         'evaltrue' => true,
      ),
    ),

    'onlineThreshold' => array(
      'type' => 'string',
      'require' => false,
      'default' => ($onlineThreshold ? $onlineThreshold : 15),
      'context' => array(
        'type' => 'int',
      ),
    ),

    'time' => array(
      'type' => 'string',
      'require' => false,
      'default' => time(),
      'context' => array(
        'type' => 'int',
      ),
    ),
  ),
));



/* Data Predefine */
$xmlData = array(
  'getActiveUsers' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'sentData' => array(
      'rooms' => ($rooms),
      'roomsList' => array(),
      'onlineThreshold' => (int) $onlineThreshold,
      'time' => (int) $time,
    ),
    'errStr' => ($errStr),
    'errDesc' => ($errDesc),
    'rooms' => array(),
  ),
);



/* Plugin Hook Start */
($hook = hook('getActiveUsers_start') ? eval($hook) : '');



/* Start Processing */
if (count($request['rooms']) === 0) { // No valid rooms specified.
  $errStr = 'badroomsrequest';
  $errDesc = 'The room string was not supplied or evaluated to false.';
}
elseif (!is_array($request['rooms'])) { // Something wacky happened.
  $errStr = 'badroomsrequest';
  $errDesc = 'The room string was not supplied or evaluated to false.';
}
else { // All is good so far.
  foreach ($request['rooms'] AS $roomId) { // Run through each room.
    //$room = dbRows("SELECT * FROM {$sqlPrefix}rooms WHERE roomId = $roomId");

    // Get the room data.
    $room = $database->select(
      array(
        "{$sqlPrefix}rooms" => array(
          'roomId' => 'roomId',
          'roomName' => 'roomName',
          'roomTopic' => 'roomTopic',
        ),
      ),
      array(
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
      )
    );
    $room = $room->getAsArray(false);


    ($hook = hook('getActiveUsers_eachRoom_start') ? eval($hook) : '');


    if (fim_hasPermission($room,$user,'know',true) === false) { // The user must be able to know the room exists.
      ($hook = hook('getActiveUsers_eachRoom_noPerm') ? eval($hook) : '');

      continue;
    }
    else {
      $xmlData['getActiveUsers']['sentData']['roomsList']['room ' . $room['roomId']] = $room['roomId'];

      $activeUsers = $database->select(
        array(
          "{$sqlPrefix}ping" => array(
            'status' => 'status',
            'typing' => 'typing'
            'time' => 'ptime'
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
        ),
        array(
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
                'context' => 'time',
              ),
              'right' => array(
                'type' => 'int',
                'value' => (int) ($time - $onlineThreshold),
              ),
            ),
          ),
        ),
        array(
          'userName' => 'asc',
        )
      );

      $xmlData['getActiveUsers']['rooms']['room ' . $room['roomId']] = array(
        'roomData' => array(
          'roomId' => (int) $activeUser['roomId'],
          'roomName' => (string) $activeUser['roomName'],
          'roomTopic' => (string) $activeUser['roomTopic'],
        ),
        'users' => array(),
      );

      if ($activeUsers) {
        foreach ($activeUsers AS $activeUser) {
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

          ($hook = hook('getActiveUsers_eachUser') ? eval($hook) : '');
        }
      }

      ($hook = hook('getActiveUsers_eachRoom_end') ? eval($hook) : '');
    }
  }
}


$xmlData['getActiveUsers']['errStr'] = ($errStr);
$xmlData['getActiveUsers']['errDesc'] = ($errDesc);


($hook = hook('getActiveUsers_end') ? eval($hook) : '');


echo fim_outputApi($xmlData);

dbClose();
?>