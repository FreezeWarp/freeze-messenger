<?php
/* FreezeMessenger Copyright © 2011 Joseph Todd Parsons

 * This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>. */

ini_set('max_execution_time','5');

require_once('../global.php');

$rooms = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE options & 4 = FALSE AND options & 8 = FALSE",'id'); // Get all rooms
foreach ($rooms AS $room3) {
  if (hasPermission($room3,$user)) {
    if ($room3['options'] & 16) $privateRoomHtml .= "            <li><a href=\"/chat.php?room=$room3[id]\" class=\"room\" data-roomid=\"$room3[id]\">$room3[name]</a></li>\n";
    elseif ($room3['options'] & 1) $officialRoomHtml .= "            <li><a href=\"/chat.php?room=$room3[id]\" class=\"room\" data-roomid=\"$room3[id]\">$room3[name]</a></li>\n";
    else $roomHtml .= "            <li><a href=\"/chat.php?room=$room3[id]\" class=\"room\" data-roomid=\"$room3[id]\">$room3[name]</a></li>\n";
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