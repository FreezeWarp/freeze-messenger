<?php
require_once('../functions/parserFunctions.php');

$phase = $_GET['phase'];
if (!$phase) $phase = '1'; // Default to phase 1.

if ($phase == '1') {
  echo container('Kick a User','<form action="./index.php?action=unkick&phase=2" method="post">
  <label for="userid">User ID</label>: <input type="text" name="userid" id="userid" value="' . $_GET['userid'] . '" style="width: 50px;" /><br />
  <label for="roomid">Room ID</label>: <input type="text" name="roomid" id="roomid" value="' . $_GET['roomid'] . '" style="width: 50px;" /><br />

  <input type="submit" value="Unkick User" /><input type="reset" value="Reset" />
</form>');
}

elseif ($phase == '2') {
  $userid = intval($_POST['userid']);
  $user2 = sqlArr("SELECT u1.settings, u2.userid, u2.username FROM {$sqlPrefix}users AS u1, user AS u2 WHERE u2.userid = $userid AND u2.userid = u1.userid");

  $room = intval($_POST['roomid']);
  $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");

  if (!$user2['userid']) {
    trigger_error('Invalid User',E_USER_ERROR);
  }
  elseif (!$room['id']) {
    trigger_error('Invalid Room',E_USER_ERROR);
  }
  elseif (!hasPermission($room,$user,'moderate')) {
    trigger_error('No Permission',E_USER_ERROR);
  }
  else {
    modLog('unkick',"$user2[userid],$room[id]");

    mysqlQuery("DELETE FROM {$sqlPrefix}kick WHERE userid = $user2[userid] AND room = $room[id]");

    sendMessage('/me unkicked ' . $user2['username'],$user,$room);

    echo container('Success',$user2['username'] . ' has been unbanned.');
  }
}
else {
  trigger_error('Unknown Action',E_USER_ERROR);
}
?>