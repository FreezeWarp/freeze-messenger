<?php
require_once('../global.php');
header('Content-type: text/plain');

if ($_GET['roomList']) {
  $rooms = explode(',',$_GET['roomList']);
}
elseif ($_GET['room']) {
  $rooms = array($_GET['room']);
}
else { 
  die();
}

$time = ($_GET['lastCheck'] ?: 0);
$lastMessage = ($_GET['lastMessage'] ?: 0);
$messageLimit = ($_GET['messageLimit'] ?: $messageLimit);

foreach ($rooms AS $room2) {
  $room2 = intval($room2);
  $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room2");

  if ($room) {
    if (!hasPermission($room,$user)) { } // Gotta make sure the user can view that room.
    else {
      $messages = sqlArr("SELECT m.id, UNIX_TIMESTAMP(m.time) AS time, m.rawText, m.vbText, m.htmlText, m.iv, m.salt, u.userid, u.username, u.displaygroupid, u2.defaultColour, u2.defaultFontface, u2.defaultHighlight FROM {$sqlPrefix}messages AS m, user AS u, {$sqlPrefix}users AS u2 WHERE room = $room[id] AND deleted != true AND m.user = u.userid AND m.user = u2.userid AND m.microtime >= $time AND m.id > $lastMessage ORDER BY m.id DESC LIMIT $messageLimit",'id');

      if ($messages) {
        if ($_GET['order'] == 'reverse') $messages = array_reverse($messages);
        foreach ($messages AS $id => $message) {
          $message = vrim_decrypt($message);

          $message['username'] = addslashes($message['username']);
          $message['vbText'] = addslashes($message['vbText']);
          $message['displaygroupid'] = displayGroupToColour($message['displaygroupid']);
          echo "'$room[id]','$message[id]','$message[time]','$message[username]','$message[userid]','$message[displaygroupid]','$message[vbText]','$message[defaultColour]','$message[defaultHighlight]','$message[defaultFontface]'\r\n";
        }
      }
    }
  }
}

mysqlClose();
?>