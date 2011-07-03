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

$apiRequest = true;

require_once('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC(array(
  'get' => array(
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
      'default' => (int) time(),
      'context' => array(
        'type' => 'int',
      ),
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
      'onlineThreshold' => (int) $ewonlineThreshold,
      'time' => (int) $time,
    ),
    'errStr' => ($errStr),
    'errDesc' => ($errDesc),
    'users' => array(),
  ),
);



/* Plugin Hook Start */
($hook = hook('getAllActiveUsers_start') ? eval($hook) : '');



/* Get Active Users */
$activeUsers = dbRows("SELECT
  u.userName AS userName,
  u.userId AS userId,
  u.userFormatStart AS userFormatStart,
  u.userFormatEnd AS userFormatEnd,
  GROUP_CONCAT(r.roomName) AS roomNames,
  GROUP_CONCAT(r.roomId) AS roomIds
  {$activeUsers_columns}
FROM
  {$sqlPrefix}users AS u,
  {$sqlPrefix}rooms AS r,
  {$sqlPrefix}ping AS p
  {$activeUsers_tables}
WHERE
  u.userId = p.userId AND
  r.roomId = p.roomId AND
  UNIX_TIMESTAMP(p.time) >
  {$activeUsers_where}
GROUP BY
  p.userId
  {$activeUsers_group}
ORDER BY
  u.userName
  {$activeUsers_order}
{$activeUser_end}",'userId');

$activeUsers = $database->select(
  array(
    "{$sqlPRefix}users" => array(
      'userName' => 'userName',
      'userId' => 'userId',
      'userFormatStart' => 'userFormatStart',
      'userFormatEnd' => 'userFormatEnd',
    ),
    "{$sqlPrefix}rooms" => array(
      'roomName' => array(
        'context' => 'join',
        'separator' => ',',
        'name' => 'roomNames',
      ),
      'roomId' => array(
        'context' => 'join',
        'separator' => ',',
        'name' => 'roomIds',
      ),
    ),
    "{$sqlPrefix}ping" => array(
      'time' => 'ptime',
      'userId' => 'puserId',
      'roomId' => 'proomId',
    ),
  ),
  array(
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
        'type' => 'e',
        'left' => array(
          'type' => 'column',
          'value' => 'ptime',
        ),
        'right' => array(
          'type' => 'int',
          'value' => (int) ($request['time'] - $request['onlineThreshold'])
        ),
      ),
    ),
  ),

);
$activeUsers = $activeUsers->getAsArray('userId');



/* Start Processing */
if ($activeUsers) {
  foreach ($activeUsers AS $activeUser) {
    $rooms = array_combine(explode(',',$activeUser['roomIds']),explode(',',$activeUser['roomNames'])); // Combine the selected roomIds with their relevant roomNames using key -> value pairs. This is a performance technique, with the consequence of preventing access to any additional roomData.

    $xmlData['getAllActiveUsers']['users']['user ' . $activeUser['userId']] = array(
      'userData' => array(
        'userId' => (int) $activeUser['userId'],
        'userName' => ($activeUser['userName']),
        'startTag' => ($activeUser['userFormatStart']),
        'endTag' => ($activeUser['userFormatEnd']),
      ),
      'rooms' => array(),
    );


    ($hook = hook('getAllActiveUsers_eachUser_start') ? eval($hook) : '');

    if ($rooms) {
      foreach ($rooms AS $roomId => $name) {
        $xmlData['getAllActiveUsers']['users']['user ' . $activeUser['userId']]['rooms']['room ' . $roomId] = array(
          'roomId' => (int) $roomId,
          'roomName' => ($name),
        );


        ($hook = hook('getAllActiveUsers_eachRoom') ? eval($hook) : '');
      }
    }


    ($hook = hook('getAllActiveUsers_eachUser_end') ? eval($hook) : '');
  }
}


$xmlData['getAllActiveUsers']['errStr'] = (string) $errStr;
$xmlData['getAllActiveUsers']['errDesc'] = (string) $errDesc;


($hook = hook('getAllActiveUsers_end') ? eval($hook) : '');


echo fim_outputApi($xmlData);

dbClose();
?>