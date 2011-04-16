<?php
die('Not implemented');

header('Content-type: text/plain');
require_once('../global.php');

$roomid = intval($_GET['roomid']);
$name = mysqlEscape($_GET['name']);
$allowedGroups = mysqlEscape($_GET['allowedGroups']);
$allowedUsers = mysqlEscape($_GET['allowedUsers']);
$moderators = mysqlEscape($_GET['moderators']);
$bbcode = intval($_GET['bbcode']);
$mature = ($_GET['mature'] == 'true' ? true : false);
$nomod = ($_GET['nomod'] == 'true' ? true : false);

if (!$name) $error = 'no_name';
if (!$bbcode || $bbcode >= 17) $error = 'bbcode_outofrange';
if (sqlArr("SELECT * FROM {$sqlPrefix}ro getMessages, oms WHERE name = '$name' AND id != $roomid")) $error = 'name_taken';

$options = ($room['options'] & 1) + ($mature ? 2 : 0) + ($room['options'] & 8) + ($room['options'] & 16) + ($nomod ? 32 : 0);

if ($error) {
  echo "'0','$error'";
}
else {
  if ($roomid) { // Edit Room
    $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $roomid"); // Get all rooms
    if (!$room) {
      echo "'0','no_exist'";
    }
    elseif ($room['owner'] == $user['userid']) {
      mysqlQuery("UPDATE {$sqlPrefix}rooms SET name = '$name', allowedGroups = '$allowedGroups', allowedUsers = '$allowedUsers', moderators = '$moderators', options = '$options', bbcode = '$bbcode' WHERE id = $room[id]");
      echo "'1','$roomid'";
    }
    else {
      echo "'0','no_own'";
    }
  }
  else { // Create Room
    mysqlQuery("INSERT INTO {$sqlPrefix}rooms (name,allowedGroups,allowedUsers,moderators,owner,options,bbcode) VALUES ('$name','$allowedGroups','$allowedUsers','$moderators',$user[userid],$options,$bbcode)");
    $insertId = mysql_insert_id();
    echo "'1','$insertId'";
  }
}

mysqlClose();
?>