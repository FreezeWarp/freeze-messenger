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

$roomId = (int) $_GET['roomId'];
$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE roomId = $roomId"); // Get all rooms

if (fim_hasPermission($room,$user,'view')) {

}
else {
  unset($room);

  $errorcode = 'noperm';
}

$xmlData = array(
  'getRoomInfo' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => fim_encodeXml($user['userName']),
    ),
    'sentData' => array(
      'roomId' => (int) $roomId,
    ),
    'errorcode' => $failCode,
    'errormessage' => $failMessage,
    'roomData' => array(
      'roomId' => $room['roomId'],
      'roomName' => $room['roomName'],
      'roomTopic' => $room['roomTopic'],
      'roomOwner' => $room['roomOwner'],
      'allowedUsers' => $room['allowedUsers'],
      'allowedGroups' => $room['allowedGroups'],
      'moderators' => $room['moderators'],
      'options' => $room['options'],
      'bbocde' => $room['bbcode'],
    ),
  ),
);


echo fim_outputXml($xmlData);

mysqlClose();
?>