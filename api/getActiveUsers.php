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
header('Content-type: text/xml');

$rooms = $_GET['rooms'];
$roomsArray = explode(',',$rooms);
foreach ($roomsArray AS &$v) {
  $v = intval($v);
}

$time = (int) ($_GET['time'] ? $_GET['time'] : time());
$onlineThreshold = (int) ($_GET['onlineThreshold'] ? $_GET['onlineThreshold'] : $onlineThreshold);

$xmlData = array(
  'getActiveUsers' => array(
    'activeUser' => array(
      'userId' => $user['userId'],
      'userName' => fim_encodeXml($user['userName']),
    ),
    'sentData' => array(
      'rooms' => $rooms,
      'roomsList' => $roomsXML,
      'onlineThreshold' => $onlineThreshold,
      'time' => $time,
    ),
    'errorcode' => $failCode,
    'errormessage' => $failMessage,
    'rooms' => array(),
  ),
);

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

    if (!fim_hasPermission($room,$user,'know')) {
      continue;
    }

    $xmlData['getActiveUsers']['sentData']['roomsList']['room ' . $room['roomId']] = $room['roomId'];


    $ausers = sqlArr("SELECT
  u.{$sqlUserTableCols[userName]} AS userName,
  u.{$sqlUserTableCols[userId]} AS userId,
  p.status,
  p.typing
  $cols
FROM {$sqlPrefix}ping AS p,
  {$sqlPrefix}rooms AS r,
  {$sqlUserTable} AS u
  $tables
WHERE p.roomId = $room[roomId] AND
  p.roomId = r.roomId AND
  p.userId = u.$sqlUserTableCols[userId] AND
  UNIX_TIMESTAMP(p.time) >= ($time - $onlineThreshold)
  $where
ORDER BY u.{$sqlUserTableCols[userName]}
  $orderby
$query",true);

    $xmlData['getActiveUsers']['rooms']['room ' . $room['roomId']] = array(
      'roomData' => array(
        'roomId' => (int) $auser['roomId'],
        'roomName' => fim_encodeXml($auser['roomName']),
        'roomTopic' => fim_encodeXml($auser['roomTopic']),
      ),
      'users' => array(),
    );


    if ($ausers) {
      foreach ($ausers AS $auser) {
        $xmlData['getActiveUsers']['rooms']['room ' . $room['roomId']]['users']['user ' . $auser['userId']] = array(
          'userId' => (int) $auser['userId'],
          'userName' => fim_encodeXml($auser['userName']),
          'userGroup' => (int) $auser['userGroup'],
          'socialGroups' => fim_encodeXml($auser['socialGroups']),
          'startTag' => fim_encodeXml($auser['startTag']),
          'endTag' => fim_encodeXml($auser['endTag']),
          'status' => fim_encodeXml($auser['status']),
          'typing' => (bool) $auser['typing'],
        );
      }
    }
  }
}

echo fim_outputXml($xmlData);

mysqlClose();
?>