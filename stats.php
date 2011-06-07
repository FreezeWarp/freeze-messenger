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

$reqPhrases = true;
$reqHooks = true;
$title = 'Room Stats';

require_once('global.php');
require_once('functions/container.php');
require_once('templateStart.php');

$roomList = mysqlEscape($_GET['roomList'] ?: '1,2,3,4,5,6,7,8,9,10');
$number = (intval($_GET['number']) ?: 10);

$rooms = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id IN ($roomList)",'id');

eval(hook('statsStart'));

echo container($phrases['statsChooseSettings'],template('statsChooseSettings'));

foreach ($rooms AS $room) {
  eval(hook('statsRoomEachStart'));

  if ($hidePostCounts) {
    if (!fim_hasPermission($room,$user,'know')) {
      continue;
    }
  }

  $tableHeader .= '<td>' . $room['name'] . '</td>';

  $totalPosts = sqlArr("SELECT m.messages AS count, u.userId, u.userName FROM {$sqlPrefix}roomStats AS m, user AS u WHERE m.roomId = $room[id] AND u.userId = m.userId ORDER BY count DESC LIMIT $number",'userId');

  $i = 0;
  foreach ($totalPosts AS $totalPoster) {
    $i++;
    $table[$i] .= '
    <td>' . $totalPoster['userName'] . ' (' . ($totalPoster['count'] ?: 0) . ')' . '</td>';
  }

  while ($i < $number) {
    $i++;
    $table[$i] .= '
    <td>&nbsp;</td>';
  }

  eval(hook('statsRoomEachStart'));
}

$i = 0;
foreach ($table AS $row) {
  $i++;

  $tableContents .= '  <tr>
    <th>' . $i . '</td>' . $row . '
  </tr>
';
}

eval(hook('statsRoomPreoutput'));

echo template('statsView');

eval(hook('statsEnd'));

require_once('templateEnd.php');
?>