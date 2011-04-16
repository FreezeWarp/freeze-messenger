<?php
require_once('../global.php');
header('Content-type: text/xml');

$rooms = $_GET['rooms'];
$roomsArray = explode(',',$rooms);
foreach ($roomsArray AS &$v) $v = intval($v);

$time = ($_GET['time'] ?: time());
$onlineThreshold = ($_GET['onlineThreshold'] ?: $onlineThreshold); 

if (!$rooms) {
  $failCode = 'badroomsrequest';
  $failMessage = 'The room string was not supplied or evaluated to false.';
}
if (!$roomsArray) {
  $failCode = 'badroomsrequest';
  $failMessage = 'The room string was not formatted properly in Comma-Seperated notation.';
}
else {
  foreach ($roomsArray AS $room) {
    $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");

    if (!hasPermission($room,$user,'know')) continue;

    $roomsXML .= "
      <room>$room[id]</room>";

    $users = sqlArr("SELECT u.username, u.userid, p.id, p.status, p.typing FROM {$sqlPrefix}ping AS p, {$sqlPrefix}rooms AS r, user AS u WHERE p.roomid = $room[id] AND p.roomid = r.id AND p.userid = u.userid AND UNIX_TIMESTAMP(p.time) >= ($time - $onlineThreshold) ORDER BY u.username",'id');

    $userXML .= "    <room>
      <roomData>
          <roomid>$user[id]</roomid>
          <roomname>$user[name]</roomname>
          <roomtopic>$user[topic]</roomtopic>
      </roomData>
      <users>
";

    if ($users) {
      foreach ($users AS $user) {
        $userXML .= "    <user>
      <userid>$user[userid]</userid>
      <username>$user[username]</username>
      <displaygroupid>$user[displaygroupid]</displaygroupid>
      <status>$user[status]</status>
      <typing>$user[typing]</typing>
    </user>
";
      }
    }

      $userXML .= "      </users>
    </room>
";
  }
}

$data = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<getActiveUsers>
  <activeUser>
    <userid>$user[userid]</userid>
    <username>" . vrim_encodeXML($user['username']) . "</username>
  </activeUser>
  <sentData>
    <rooms>$rooms</rooms>
    <roomsList>$roomsXML
    </roomsList>
    <onlineThreshold>$onlineThreshold</onlineThreshold>
    <time>$time</time>
  </sentData>
  <errorcode>$failCode</errorcode>
  <errortext>$failMessage</errortext>
  <rooms>
$userXML  </rooms>
</getActiveUsers>";

if ($_GET['gz']) {
 echo gzcompress($data);
}
else {
  echo $data;
}

mysqlClose();
?>