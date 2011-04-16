<?php
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
    <roomid>$room[id]</roomid>
    <roomname>$room[name]</roomname>
    <roomtopic>$room[topic]</roomtopic>
    <roomowner>$room[owner]</roomowner>
    <allowedUsers>$room[allowedUsers]</allowedUsers>
    <allowedGroups>$room[allowedGroups]</allowedGroups>
    <moderators>$room[moderators]</moderators>
    <options>$room[options]</options>
    <bbcode>$room[bbcode]</bbcode>
  </userData>
</getRoomInfo>";


mysqlClose();
?>