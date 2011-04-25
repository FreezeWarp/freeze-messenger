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
header('Content-type: text/xml');

$statusType = vrim_urldecode($_GET['statusType']); // typing, status
$statusValue = vrim_urldecode($_GET['statusValue']);

$room = intval($_GET['room']);
$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");


if (!$room) { // Bad room.
  $failCode = 'badroom';
  $failMessage = 'That room could not be found.';
}
elseif (!hasPermission($room,$user,'view')) { // Not allowed to post.
  $failCode = 'noperm';
  $failMessage = 'You are not allowed to post in this room.';
}
else {
  if ($statusType == 'typing') {
    $value = intval($statusValue);
  }
  elseif ($statusType == 'status') {
    $value = mysqlEscape($statusValue);

    if (!in_array($value,array('available','away','busy','invisible','offline'))) {
      mysqlQuery("UPDATE vrc_ping SET status = '$value' WHERE userid = $user[userid] AND roomid = $room[id]");
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
}



  echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<setStatus>
  <activeUser>
    <userid>$user[userid]</userid>
    <username>" . vrim_encodeXML($user['username']) . "</username>
  </activeUser>
  <sentData>
    <roomid>" . vrim_encodeXML($_GET['roomid']) . "</room>
    <userid>" . vrim_encodeXML($_GET['userid']) . "</message>
  </sentData>
  <errorcode>$failCode</errorcode>
  <errortext>$failMessage</errortext>
</sendMessage>";

mysqlClose();
?>