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
header('Content-type: text/plain');

$roomid = intval($_GET['roomid']);
$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $roomid",'id'); // Get all rooms

if (hasPermission($room,$user) {

}
else {
  unset($room);

  $errorcode = 'noperm';
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<getRoomInfo>
  <activeUser>
    <userid>$user[userid]</userid>
    <username>" . vrim_encodeXML($user['username']) . "</username>
  </activeUser>
  <sentData>
    <roomid>$roomid</roomid>
  </sentData>
  <errorcode>$failCode</errorcode>
  <errortext>$failMessage</errortext>
  <roomData>
    <roomId>$room[id]</roomId>
    <roomName>$room[name]</roomName>
    <roomTopic>$room[topic]</roomTopic>
    <roomOwner>$room[owner]</roomOwner>
    <allowedUsers>$room[allowedUsers]</allowedUsers>
    <allowedGroups>$room[allowedGroups]</allowedGroups>
    <moderators>$room[moderators]</moderators>
    <options>$room[options]</options>
    <bbcode>$room[bbcode]</bbcode>
  </userData>
</getRoomInfo>";


mysqlClose();
?>