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
require_once('../functions/parserFunctions.php');

$statusType = fim_urldecode($_POST['statusType']); // typing, status
$statusValue = fim_urldecode($_POST['statusValue']);

$roomId = (int) $_POST['roomId'];
$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE roomId = $roomId");


($hook = hook('setStatus_start') ? eval($hook) : '');


if (!$room) { // Bad room.
  $failCode = 'badroom';
  $failMessage = 'That room could not be found.';
}
elseif (!fim_hasPermission($room,$user,'view')) { // Not allowed to post.
  $failCode = 'noperm';
  $failMessage = 'You are not allowed to post in this room.';
}
else {
  ($hook = hook('setStatus_inner_start') ? eval($hook) : '');

  if ($statusType == 'typing') {
    $value = (int) $statusValue;
  }
  elseif ($statusType == 'status') {
    $value = mysqlEscape($statusValue);

    if (!in_array($value,array('available','away','busy','invisible','offline'))) {
      ($hook = hook('setStatus_inner_query') ? eval($hook) : '');

      mysqlQuery("UPDATE vrc_ping SET status = '$value' WHERE userId = $user[userId] AND roomId = $room[id]");
    }
    else {
      $failCode = 'badstatusvalue';
      $failMessage = 'That status value is not recognized. Only "available", "away", "busy", "invisible", "offline" are supported.';
    }
  }
  else {
    $failCode = 'badstatustype';
    $failMessage = 'That status type is not recognized. Only "status" and "typing" are supported.';
  }

  ($hook = hook('setStatus_inner_end') ? eval($hook) : '');
}


$xmlData = array(
  'setStatus' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => fim_encodeXml($user['userName']),
    ),
    'sentData' => array(
      'roomId' => (int) $_POST['roomId'],
      'userId' => (int) $_POST['userId'],
    ),
    'errorcode' => fim_encodeXml($failCode),
    'errortext' => fim_encodeXml($failMessage),
  ),
);


($hook = hook('setStatus_end') ? eval($hook) : '');


echo fim_outputXml($xmlData);

mysqlClose();
?>