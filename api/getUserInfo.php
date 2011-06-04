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

$userid = intval($_GET['userid']);
$username = mysqlEscape(vrim_urldecode($_GET['username']));

if ($userid) {
  $where = "u.{$sqlUserTableCols[userid]} = $userid";
}
elseif ($username) {
  $where = "u.{$sqlUserTableCols[username]} = '$username'";
}
else {
  $failCode = 'nodata';
  $failMessage = 'Neither a username or userid was provided.';
}

switch ($loginMethod) {
  case 'vbulletin':
  if ($where) {
    $getuserf = sqlArr("SELECT * FROM {$sqlUserTable} AS u LEFT JOIN {$sqlUserGroupTable} AS g ON u.{$sqlUserTableCols[usergroup]} = g.{$sqlUserGroupTableCols[groupid]} WHERE {$where}");
    $getuserf['opentag'] = vrim_encodeXML($getuserf['opentag']);
    $getuserf['closetag'] = vrim_encodeXML($getuserf['closetag']);
    $getuserf['avatar'] = $forumUrl . '/image.php?u=' . $getuserf['userid'];
  }

  if ($getuserf) {
    $useridf = $getuserf[$sqlUserTableCols['userid']];
    $getuser = sqlArr("SELECT * FROM {$sqlPrefix}users AS u WHERE userid = $useridf");
  }
  break;

  case 'phpbb':
  if ($where) {
    $getuserf = sqlArr("SELECT u.user_id, u.username, u.user_posts AS posts, u.user_colour, u.user_avatar FROM {$sqlUserTable} AS u WHERE {$where}");
    $getuserf['opentag'] = vrim_encodeXML('<span style="color: #' . $getuserf['user_colour'] . ';">');
    $getuserf['closetag'] = vrim_encodeXML('</span>');
    $getuserf['avatar'] = $forumUrl . '/download/file.php?avatar=' . $getuserf['user_avatar'];
  }

  if ($getuserf) {
    $useridf = $getuserf[$sqlUserTableCols['userid']];
    $getuser = sqlArr("SELECT * FROM {$sqlPrefix}users AS u WHERE userid = $useridf");
  }
  break;
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<getUserInfo>
  <activeUser>
    <userId>$user[userid]</userId>
    <userName>" . vrim_encodeXML($user['username']) . "</userName>
  </activeUser>
  <sentData>
    <userId>$userid</userId>
    <userName>$username</userName>
  </sentData>
  <errorcode>$failCode</errorcode>
  <errortext>$failMessage</errortext>
  <userData>
    <userId>$getuser[userid]</userId>
    <userName>$getuserf[username]</userName>
    <settings>$getuser[settings]</settings>
    <startTag>$getuserf[opentag]</startTag>
    <endTag>$getuserf[closetag]</endTag>
    <favRooms>$getuser[favRooms]</favRooms>
    <postCount>$getuserf[posts]</postCount>
    <joinDate>$getuserf[joindate]</joinDate>
    <joinDateFormatted>" . vbdate(false,$getuserf['joindate']) . "</joinDateFormatted>
    <userTitle>$getuserf[usertitle]</userTitle>
    <avatar>$getuserf[avatar]</avatar>
  </userData>
</getUserInfo>";

mysqlClose();
?>