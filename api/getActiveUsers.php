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

$rooms = $_GET['rooms'];
$roomsArray = explode(',',$rooms);
foreach ($roomsArray AS &$v) {
  $v = (int) $v;
}

$time = (int) ($_GET['time'] ? $_GET['time'] : time());
$onlineThreshold = (int) ($_GET['onlineThreshold'] ? $_GET['onlineThreshold'] : $onlineThreshold);

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
    'errorcode' => ($failCode),
    'errormessage' => ($failMessage),
    'rooms' => array(),
  ),
);

($hook = hook('getActiveUsers_start') ? eval($hook) : '');

if (!$rooms) {
  $failCode = 'badroomsrequest';
  $failMessage = 'The room string was not supplied or evaluated to false.';
}
if (!$roomsArray) {
  $failCode = 'badroomsrequest';
  $failMessage = 'The room string was not formatted properly in Comma-Seperated notation.';
}
else {
  foreach ($roomsArray AS $roomId) {
    $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE roomId = $roomId");

    ($hook = hook('getActiveUsers_eachRoom_start') ? eval($hook) : '');

    if (!fim_hasPermission($room,$user,'know')) {
      ($hook = hook('getActiveUsers_eachRoom_noPerm') ? eval($hook) : '');

      continue;
    }

    $xmlData['getActiveUsers']['sentData']['roomsList']['room ' . $room['roomId']] = $room['roomId'];

    $activeUsers = sqlArr("SELECT
  u.userName AS userName,
  u.userId AS userId,
  p.status,
  p.typing
  {$activeUsers_columns}
FROM {$sqlPrefix}ping AS p,
  {$sqlPrefix}rooms AS r,
  {$sqlUserTable} AS u
  {$activeUsers_tables}
WHERE p.roomId = $room[roomId] AND
  p.roomId = r.roomId AND
  p.userId = u.$sqlUserTableCols[userId] AND
  UNIX_TIMESTAMP(p.time) >= ($time - $onlineThreshold)
  {$activeUsers_where}
ORDER BY u.{$sqlUserTableCols[userName]}
  {$activeUsers_order}
{$activeUsers_end}",true);

    $xmlData['getActiveUsers']['rooms']['room ' . $room['roomId']] = array(
      'roomData' => array(
        'roomId' => (int) $activeUser['roomId'],
        'roomName' => ($activeUser['roomName']),
        'roomTopic' => ($activeUser['roomTopic']),
      ),
      'users' => array(),
    );

    if ($activeUsers) {
      foreach ($activeUsers AS $activeUser) {
        $xmlData['getActiveUsers']['rooms']['room ' . $room['roomId']]['users']['user ' . $activeUser['userId']] = array(
          'userId' => (int) $activeUser['userId'],
          'userName' => ($activeUser['userName']),
          'userGroup' => (int) $activeUser['userGroup'],
          'socialGroups' => ($activeUser['socialGroups']),
          'startTag' => ($activeUser['startTag']),
          'endTag' => ($activeUser['endTag']),
          'status' => ($activeUser['status']),
          'typing' => (bool) $activeUser['typing'],
        );

        ($hook = hook('getActiveUsers_eachUser') ? eval($hook) : '');
      }
    }

    ($hook = hook('getActiveUsers_eachRoom_end') ? eval($hook) : '');
  }
}


$xmlData['getActiveUsers']['errorcode'] = ($failCode);
$xmlData['getActiveUsers']['errortext'] = ($failMessage);


($hook = hook('getActiveUsers_end') ? eval($hook) : '');


echo fim_outputXml($xmlData);

mysqlClose();
?>