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


$rooms = $_GET['rooms'];
$roomsArray = explode(',',$rooms);
foreach ($roomsArray AS &$v) {
  $v = intval($v);
  $roomData = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $v");
  if (!hasPermission($roomData,$user,'moderate')) unset($v);
}
$roomList = implode(',',$roomsArray);



die('3');
$users = $_GET['rooms'];
$usersArray = explode(',',$users);
foreach ($usersArray AS &$v) {
  $v = intval($v);
}
$userList = implode(',',$usersArray);


if ($roomList) {
  $where .= "roomId IN ($roomList)";
}
if ($userList) {
  $where .= "userId IN ($userList)";
}


$kicks = sqlArr("SELECT k.id,
  k.userId,
  u.$sqlUserTableCols[userName] AS userName,
  k.roomId,
  k.length,
  k.time,
  k.kickerId,
  i.$sqlUserTableCols[userName] AS kickerName
  r.name AS roomName
FROM {$sqlPrefix}kick
  LEFT JOIN {$sqlUserTable} AS u ON k.userId = u.{$sqlUserTableCols[userId]},
  LEFT JOIN {$sqlUserTable} AS i ON k.kickerId = i.{$sqlUserTableCols[userId]},
  LEFT JOIN {$sqlPrefix}rooms AS r ON k.roomId = r.id
WHERE $where TRUE",'id');


foreach ($kicks AS $kick) {
  $kicksXml .= "<kick>
    <room>
      <roomId>$kick[roomId]</roomId>
      <roomName>$kick[roomName]</roomName>
    </room>
    <user>
      <userId>$kick[userId]</userId>
      <userName>$kick[userName]</userName>
    </user>
    <kicker>
     <userId>$kick[kickerId]</userId>
     <userName>$kick[kickerName]</userName>
    </kicker>
    <length>$kick[length]</length>
    <set>$kick[time]</set>
    <expires>$kick[expires]</expires>
  </kick>  ";
}



///* Output *///
echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<!DOCTYPE html [
  <!ENTITY nbsp \" \">
]>
<getKicks>
  <activeUser>
    <userId>$user[userId]</userId>
    <userName>" . fim_encodeXml($user['userName']) . "</userName>
  </activeUser>

  <sentData>
    <rooms>$roomList</rooms>
    <users>$userList</users>
  </sentData>

  <errorcode>$failCode</errorcode>
  <errortext>$failMessage</errortext>

  <kicks>
    $kicksXml
  </kicks>
</getKicks>";




mysqlClose();
?>