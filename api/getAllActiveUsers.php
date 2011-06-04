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

$time = ($_GET['time'] ?: time());
$onlineThreshold = ($_GET['onlineThreshold'] ?: $onlineThreshold);

switch ($loginMethod) {

}

$ausers = sqlArr("SELECT
  u.$sqlUserTableCols[userName] AS userName,
  u.$sqlUserTableCols[userId] AS userId,
  GROUP_CONCAT(r.name) AS roomNames,
  GROUP_CONCAT(r.id) AS roomIds
  $cols
FROM
  $sqlUserTable AS u,
  {$sqlPrefix}rooms AS r,
  {$sqlPrefix}ping AS p
  $tables
WHERE
  u.$sqlUserTableCols[userId] = p.userId AND
  r.id = p.roomId AND
  UNIX_TIMESTAMP(p.time) > $time - $onlineThreshold
  $where
GROUP BY
  p.userId
  $groupby
ORDER BY
  u.userName
  $orderby
$query",'userId');

switch ($loginMethod) {

}

if ($ausers) {
  foreach ($ausers AS $auser) {
    unset($roomsXML);

    $rooms = array_combine(explode(',',$auser['roomIds']),explode(',',$auser['roomNames']));
    foreach ($rooms AS $id => $name) $roomsXML .= "      <room>
        <roomId>$id</roomId>
        <roomName>$name</roomName>
      </room>";

    $ausersXML .= "    <user>
      <userData>
        <userId>$auser[userId]</userId>
        <userName>$auser[userName]</userName>
        <startTag>$auser[userName]</startTag>
        <endTag>$auser[userName]</endTag>
      </userData>
      <rooms>
      $roomsXML
      </rooms>
    </user>
";
  }
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<getAllActiveUsers>
  <activeUser>
    <userId>$user[userId]</userId>
    <userName>" . vrim_encodeXML($user['userName']) . "</userName>
  </activeUser>
  <sentData>
    <onlineThreshold>$onlineThreshold</onlineThreshold>
    <time>$time</time>
  </sentData>
  <errorcode>$failCode</errorcode>
  <errortext>$failMessage</errortext>
  <users>
    $ausersXML
  </users>
</getAllActiveUsers>";

mysqlClose();
?>