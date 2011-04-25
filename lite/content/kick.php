<?php
require_once('../functions/parserFunctions.php');

$phase = $_GET['phase'];
if (!$phase) $phase = '1'; // Default to phase 1.

if ($phase == '1') {
  $roomSelect = mysqlReadThrough(mysqlQuery("SELECT * FROM {$sqlPrefix}rooms WHERE " . ((($user['settings'] & 16) == false) ? "(owner = '$user[userid]' OR moderators REGEXP '({$user[userid]},)|{$user[userid]}$') AND " : '') . "(options & 16) = false AND (options & 4) = false AND (options & 8) = false"),'<option value="$id"{{' . intval($_GET['roomid']) . ' == $id}}{{ selected="selected"}{}}>$name</option>
');
  $userSelect = mysqlReadThrough(mysqlQuery("SELECT u2.userid, u2.username FROM {$sqlPrefix}users AS u, user AS u2 WHERE u2.userid = u.userid ORDER BY username"),'<option value="$userid">$username</option>
');
  echo container('<h3>Kick a User</h3>','<form action="./index.php?action=kick&phase=2" method="post">
  <label for="userid">User</label>: <select name="userid">' . $userSelect . '</select><br />
  <label for="roomid">Room</label>: <select name="roomid">' . $roomSelect . '</select><br />
  <label for="time">Time</label>: <input type="text" name="time" id="time" style="width: 50px;" />
  <select name="interval">
    <option value="1">Seconds</option>
    <option value="60">Minutes</option>
    <option value="3600">Hours</option>
    <option value="86400">Days</option>
    <option value="604800">Weeks</option>
  </select><br /><br />

  <input type="submit" value="Kick User" /><input type="reset" value="Reset" />
</form>');
}

elseif ($phase == '2') {
  $userid = intval($_POST['userid']);
  $user2 = sqlArr("SELECT u1.settings, u2.userid, u2.username FROM {$sqlPrefix}users AS u1, user AS u2 WHERE u2.userid = $userid AND u2.userid = u1.userid");

  $room = intval($_POST['roomid']);
  $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");

  $time = floor($_POST['time'] * $_POST['interval']);

  if (!$user2['userid']) {
    trigger_error('Invalid User',E_USER_ERROR);
  }
  elseif (!$room['id']) {
    trigger_error('Invalid Room',E_USER_ERROR);
  }
  elseif ($user2['settings'] & 16 && false) { // You can't kick admins.
    trigger_error('You\'re really not supposed to kick admins... I mean, sure, it sounds fun and all, but still... we don\'t like it >:D');

    sendMessage('/me fought the law and the law won.',$user['userid'],$room['id']);
  }
  elseif (!hasPermission($room,$user,'moderate')) {
    trigger_error('No Permission',E_USER_ERROR);
  }
  else {
    modLog('kick',"$user2[userid],$room[id]");

    mysqlQuery("INSERT INTO {$sqlPrefix}kick (userid, kickerid, length, room) VALUES ($user2[userid], $user[userid], $time, $room[id])");

    sendMessage('/me kicked ' . $user2['username'],$user,$room);

    echo container('Success','The user has been kicked');
  }
}
else {
  trigger_error('Unknown Action',E_USER_ERROR);
}
?>