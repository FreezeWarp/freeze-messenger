<?php
require_once('../global.php');
header('Content-type: text/plain');

$favRooms = explode(',',$user['favRooms']);

if (!$user['userid']) {
  die();
}
else {
  $rooms = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE options & 4 = FALSE ORDER BY id ASC",'id'); // Get all rooms
  foreach ($rooms AS $id => $room2) {
    if (hasPermission($room2,$user)) {
      /*if ($room2['options'] & 16) $room2['class'] = '2';
      elseif ($room2['options'] & 1) $room2['class'] = '0';
      else $room2['class'] = '1';*/

      $rooms2[] = $room2;
    }
  }

  if ($rooms2) {
    foreach ($rooms2 AS $row) {
      $row['name'] = addslashes($row['name']);

      if (in_array($row['id'],$favRooms)) $fav = 1;
      else $fav = 0;

      echo "'$row[id]','$row[name]','$fav','$row[options]'\r\n";
    }
  }
}

mysqlClose();
?>
