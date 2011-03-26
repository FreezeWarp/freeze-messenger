<?php
require_once('../global.php');
header('Content-type: text/plain');

$roomid = intval($_GET['roomid']);
$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $roomid",'id'); // Get all rooms

if (hasPermission($room,$user) {
  echo "'$room[id]','$room[name]','$room[title]','$room[owner]','$room[allowedUsers]','$room[allowedGroups]','$room[moderators]','$room[options]','$room[bbcode]'";
}
else {

}

mysqlClose();
?>