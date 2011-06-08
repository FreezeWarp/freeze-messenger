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

/* This script, unfortunately, mixes not-nicely with forums. So, its kinda messy. */
$apiRequest = true;

require_once('../global.php');
header('Content-type: text/xml');

$userId = (int) $_GET['userId'];
$userName = mysqlEscape(fim_urldecode($_GET['userName']));


if ($userId) {
  $where = "u.userId = $userId";
}
elseif ($userName) {
  $where = "u.userName = '$userName'";
}
else {
  $failCode = 'nodata';
  $failMessage = 'Neither a username or userid was provided.';
}


if ($where) {
  $getuser = sqlArr("SELECT * FROM {$sqlPrefix}users AS u WHERE {$where}");
}
if ($getuser) {
  switch ($loginMethod) {
    case 'vbulletin':
    if ($where) {
      $getuserf = sqlArr("SELECT * FROM {$sqlUserTable} AS u WHERE {$sqlUserTableCols[userId]} = $getuser[userId]");
      $getuserf['avatar'] = $forumUrl . '/image.php?u=' . $getuserf['userId'];
    }
    break;

    case 'phpbb':
    if ($where) {
      $getuserf = sqlArr("SELECT u.user_id, u.userName, u.user_posts AS posts, u.user_colour, u.user_avatar FROM {$sqlUserTable} AS u WHERE {$sqlUserTableCols[userId]} = $getuser[userId]");
      $getuserf['avatar'] = $forumUrl . '/download/file.php?avatar=' . $getuserf['user_avatar'];
    }
    break;
  }
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<getUserInfo>
  <activeUser>
    <userId>$user[userId]</userId>
    <userName>" . fim_encodeXml($user['userName']) . "</userName>
  </activeUser>
  <sentData>
    <userId>$userId</userId>
    <userName>$userName</userName>
  </sentData>
  <errorcode>$failCode</errorcode>
  <errortext>$failMessage</errortext>
  <userData>
    <userId>$getuser[userId]</userId>
    <userName>$getuser[userName]</userName>
    <settings>$getuser[settings]</settings>
    <startTag>$getuser[userFormatStart]</startTag>
    <endTag>$getuser[userFormatEnd]</endTag>
    <favRooms>$getuser[favRooms]</favRooms>
    <postCount>$getuserf[posts]</postCount>
    <joinDate>$getuserf[joinDate]</joinDate>
    <joinDateFormatted>" . fim_date(false,$getuserf['joinDate']) . "</joinDateFormatted>
    <userTitle>$getuserf[usertitle]</userTitle>
    <avatar>$getuserf[avatar]</avatar>
  </userData>
</getUserInfo>";

mysqlClose();
?>