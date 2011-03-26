<?php
ini_set('max_execution_time','5');

require_once('../global.php');

$rooms = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE options & 4 = FALSE AND options & 8 = FALSE",'id'); // Get all rooms
foreach ($rooms AS $room3) {
  if (hasPermission($room3,$user)) {
    if ($room3['options'] & 16) $privateRoomHtml .= "            <li><a href=\"/index.php?room=$room3[id]\" class=\"room\" data-roomid=\"$room3[id]\">$room3[name]</a></li>\n";
    elseif ($room3['options'] & 1) $officialRoomHtml .= "            <li><a href=\"/index.php?room=$room3[id]\" class=\"room\" data-roomid=\"$room3[id]\">$room3[name]</a></li>\n";
    else $roomHtml .= "            <li><a href=\"/index.php?room=$room3[id]\" class=\"room\" data-roomid=\"$room3[id]\">$room3[name]</a></li>\n";
  }
}
echo "<ul>
  <li>Official Rooms</li>
  <ul>
   $officialRoomHtml
  </ul>
  <li>Unofficial Rooms</li>
  <ul>
  $roomHtml
  </ul>
  <li>Private Rooms</li>
  <ul>
  $privateRoomHtml
  </ul>
</ul>";

mysqlClose();
?>