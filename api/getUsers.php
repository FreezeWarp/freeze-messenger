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

$apiRequest = true;

require_once('../global.php');
header('Content-type: text/xml');

$users = $_GET['rooms'];
$usersArray = explode(',',$rooms);
foreach ($usersArray AS &$v) $v = intval($v);


if ($users) $whereClause .= ' userid IN (' . implode(',',$usersArray) . ') AND ';


switch ($_GET['order']) {
  case 'id':
  case 'userid':
  $order = 'userid ' . ($reverseOrder ? 'DESC' : 'ASC');
  break;

  case 'name':
  case 'username':
  $order = 'username ' . ($reverseOrder ? 'DESC' : 'ASC');
  break;

  default:
  $order = 'userid ' . ($reverseOrder ? 'DESC' : 'ASC');
  break;
}

switch ($loginMethod) {
  case 'vbulletin':
  $join = "LEFT JOIN {$sqlUserTable} AS u2 ON u2.userid = u.userid";
  $cols = ", u2.username";
  break;
}


$users = sqlArr("SELECT u.userid {$cols} FROM {$sqlPrefix}users AS u {$join} WHERE {$whereClause} TRUE ORDER BY {$order}",'userid'); // Get all rooms
if ($users) {
  foreach ($users AS $row) {
    $row['username'] = htmlspecialchars($row['username']);

    $userXML .= "    <user>
      <userid>$row[userid]</userid>
      <username>" . vrim_encodeXML($row['username']) . "</username>
    </user>";
  }
}

$data = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<getRooms>
  <activeUser>
    <userid>$user[userid]</userid>
    <username>" . vrim_encodeXML($user['username']) . "</username>
  </activeUser>

  <sentData>
    <order>" . htmlspecialchars($order) . "</order>
  </sentData>

  <errorcode>$failCode</errorcode>
  <errortext>$failMessage</errortext>
  <users>
    $userXML
  </users>
</getRooms>";

if ($_GET['gz']) {
 echo gzcompress($data);
}
else {
  echo $data;
}

mysqlClose();
?>