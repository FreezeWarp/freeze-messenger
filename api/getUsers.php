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

$usersArray = explode(',',$_GET['rooms']);
foreach ($usersArray AS &$v) {
  $v = intval($v);
}


if ($users) $whereClause .= ' userId IN (' . implode(',',$usersArray) . ') AND ';


switch ($_GET['order']) {
  case 'id':
  case 'userId':
  $order = 'userId ' . ($reverseOrder ? 'DESC' : 'ASC');
  break;

  case 'name':
  case 'userName':
  $order = 'userName ' . ($reverseOrder ? 'DESC' : 'ASC');
  break;

  default:
  $order = 'userId ' . ($reverseOrder ? 'DESC' : 'ASC');
  break;
}


switch ($loginMethod) {
  case 'vbulletin':
  case 'phpbb':
  $join = "LEFT JOIN {$sqlUserTable} AS u2 ON u2.{$sqlUserTableCols[userId]} = u.userId";
  $cols = ", u2.{$sqlUserTableCols[userName]} AS userName, u2.{$sqlUserTableCols[userGroup] AS userGroup";
  break;
}


$users = sqlArr("SELECT u.userId {$cols} FROM {$sqlPrefix}users AS u {$join} WHERE {$whereClause} TRUE ORDER BY {$order}",'userId'); // Get all rooms
if ($users) {
  foreach ($users AS $row) {
    $userXML .= "    <user>
      <userId>$row[userId]</userId>
      <userName>" . vrim_encodeXML($row['userName']) . "</userName>
      <userGroup>" . vrim_encodeXML($row['userName']) . "</userGroup>
    </user>";
  }
}


echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<getRooms>
  <activeUser>
    <userId>$user[userId]</userId>
    <userName>" . vrim_encodeXML($user['userName']) . "</userName>
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

mysqlClose();
?>