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

$userid = intval($_GET['userid']);
$username = mysqlEscape(vrim_urldecode($_GET['username']));

if ($userid) {
  $getuservb = sqlArr("SELECT * FROM user WHERE userid = $userid");
}
elseif ($username) {
  $getuservb = sqlArr("SELECT * FROM user WHERE username = '$username'");
}
else {
  $failCode = 'nodata';
  $failMessage = 'Neither a username or userid was privided.';
}

if ($getuservb) {
  if ($user['userid'] > 0) {
    $getuser = sqlArr("SELECT * FROM {$sqlPrefix}users WHERE userid = $getuservb[userid]");
  }
  else {
    $failCode = 'logicerror';
  }
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<getUserInfo>
  <activeUser>
    <userid>$user[userid]</userid>
    <username>" . vrim_encodeXML($user['username']) . "</username>
  </activeUser>
  <sentData>
    <userid>$userid</userid>
    <username>$username</username>
  </sentData>
  <errorcode>$failCode</errorcode>
  <errortext>$failMessage</errortext>
  <userData>
    <userid>$geteuser[userid]</userid>
    <username>$getuser[username]</username>
    <settings>$getuser[settings]</settings>
    <favRooms>$getuser[favRooms]</favRooms>
  </userData>
</getUserInfo>";

mysqlClose();
?>