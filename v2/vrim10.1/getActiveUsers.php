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
  foreach ($roomsArray AS $roomXML) $roomsXML .= "<room>$roomXML</room>";

  foreach ($roomsArray AS $room) {
    $users = sqlArr("SELECT u.username, u.userid, r.id AS roomid, r.name AS roomname, r.title AS roomtopic, p.id FROM {$sqlPrefix}ping AS p, {$sqlPrefix}rooms AS r, user AS u WHERE p.roomid = $room AND p.roomid = r.id AND p.userid = u.userid AND UNIX_TIMESTAMP(p.time) >= ($time - $onlineThreshold) ORDER BY u.username",'id');

    if ($users) {
      foreach ($users AS $user) {
        $userXML .= "    <user>
      <userid>$user[userid]</userid>
      <username>$user[username]</username>
      <displaygroupid>$user[displaygroupid]</displaygroupid>
      <room>
        <roomid>$user[roomid]</roomid>
        <roomname>$user[roomname]</roomname>
        <roomtopic>$user[roomtopic]</roomtopic>
      </room>'
    </user>";
      }
    }
  }
}

$data = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<getActiveUsers>
  <sentData>
    <rooms>$rooms</rooms>
    <roomsList>
    $roomsXML
    </roomsList>
    <onlineThreshold>$onlineThreshold</onlineThreshold>
    <time>$time</time>
  </sentData>
  <errorcode>$failCode</errorcode>
  <errortext>$failMessage</errortext>
  <users>
    $userXML
  </users>
</getActiveUsers>";

if ($_GET['gz']) {
 echo gzcompress($data);
}
else {
  echo $data;
}

mysqlClose();
?>