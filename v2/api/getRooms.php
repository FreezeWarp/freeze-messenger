<?php
require_once('../global.php');
header('Content-type: text/xml');

$rooms = $_GET['rooms'];
$roomsArray = explode(',',$rooms);
foreach ($roomsArray AS &$v) $v = intval($v);

$favRooms = explode(',',$user['favRooms']);

$whereClause = ($_GET['showDeleted'] ? '' : '(options & 4 = FALSE) AND ');
if ($rooms) $whereClause .= ' id IN (' . implode(',',$roomsArray) . ') AND ';

switch ($_GET['order']) {
  case 'id': $order = 'id ' . ($_GET['orderReverse'] ? 'DESC' : 'ASC'); break;
  case 'name': $order = 'name ' . ($_GET['orderReverse'] ? 'DESC' : 'ASC'); break;
  case 'vrim': $order = '(options & 1) DESC, (options & 16) ASC'; break;
  default: $order = 'id ' . ($_GET['orderReverse'] ? 'DESC' : 'ASC'); break;
}


$rooms = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE $whereClause (options & 8 = FALSE) ORDER BY $order",'id'); // Get all rooms
foreach ($rooms AS $id => $room2) {
  if (hasPermission($room2,$user)) $rooms2[] = $room2;
}

if ($rooms2) {
  foreach ($rooms2 AS $row) {
    $row['name'] = htmlspecialchars($row['name']);
    $fav = (in_array($row['id'],$favRooms) ? 'true' : 'false');

    $roomXML .= "    <room>
      <roomid>$row[id]</roomid>
      <roomname>" . vrim_encodeXML($row['name']) . "</roomname>
      <roomtopic>" . vrim_encodeXML($row['title']) . "</roomtopic>
      <allowedUsers>$row[allowedUsers]</allowedUsers>
      <allowedGroups>$row[allowedGroups]</allowedGroups>
      <favourite>$fav</favourite>
      <options>$row[options]</options>
      <optionDefinitions>
        <official>" . (($row['options'] & 1) ? 'true' : 'false') . "</official>
        <deleted>" . (($row['options'] & 4) ? 'true' : 'false') . "</deleted>
        <hidden>" . (($row['options'] & 8) ? 'true' : 'false') . "</hidden>
        <privateim>" . (($row['options'] & 16) ? 'true' : 'false') . "</privateim>
      </optionDefinitions>
    </room>";
  }
}

$data = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<getRooms>
  <sentData>
    <order>" . htmlspecialchars($order) . "</order>
    <showDeleted>" . ($_GET['showDeleted'] ? 'true' : 'false') . "</showDeleted>
  </sentData>

  <errorcode>$failCode</errorcode>
  <errortext>$failMessage</errortext>
  <rooms>
    $roomXML
  </rooms>
</getRooms>";

if ($_GET['gz']) {
 echo gzcompress($data);
}
else {
  echo $data;
}

mysqlClose();
?>
