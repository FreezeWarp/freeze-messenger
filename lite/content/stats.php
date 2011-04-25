<?php
$roomList = mysqlEscape($_GET['roomList'] ?: '1,2,3,4,5,6,7,8,9,10');
$number = (intval($_GET['number']) ?: 10);

echo container('Change Settings','
<form action="./index.php" method="GET">
<label for="roomList">Room List (IDs): </label><input type="text" id="roomList" name="roomList" value="' . $roomList . '" /><br />
<label for="number">Number of Results: </label><select name="number" id="number">
  <option value="10">10</option>
  <option value="25">25</option>
  <option value="50">50</option>
</select><br /><br />

<input type="hidden" name="action" value="stats" />
<input type="submit" value="Go" /><input type="reset" value="Reset" />
</form>');

$rooms = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id IN ($roomList)",'id');
foreach ($rooms AS $room) {
  $tableHeader[] = $room;

  $totalPosts = sqlArr("SELECT m.messages AS count, u.userid, u.username FROM {$sqlPrefix}ping AS m, user AS u WHERE m.roomid = $room[id] AND u.userid = m.userid ORDER BY count DESC LIMIT $number",'userid');

  $i = 0;
  foreach ($totalPosts AS $totalPoster) {
    $i++;
    $table[$i] .= '
    <td>' . $totalPoster['username'] . ' (' . ($totalPoster['count'] ?: 0) . ')' . '</td>';
  }
  while ($i < $number) {
    $i++;
    $table[$i] .= '
    <td>&nbsp;</td>';
  }
}

echo '<table class="page rowHover">
  <tr class="hrow">
    <td>Place</td>
';
foreach ($tableHeader AS $headRow) {
  echo '    <td>' . $headRow['name'] . '</td>
';
}
echo '  </tr>
';

$i = 0;
foreach ($table AS $row) {
  $i++;
  echo '  <tr>
    <th>' . $i . '</td>' . $row;
  $j = 0;
  echo '
  </tr>
';
}

echo '</table>';
?>