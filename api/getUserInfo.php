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
      $getuserf = sqlArr("SELECT joindate AS joinDate FROM {$sqlUserTable} AS u WHERE {$sqlUserTableCols[userId]} = $getuser[userId]");
      $getuserf['avatar'] = $forumUrl . '/image.php?u=' . $getuserf['userId'];
    }
    break;

    case 'phpbb':
    if ($where) {
      $getuserf = sqlArr("SELECT u.user_posts AS posts, u.user_colour, u.user_avatar, u.user_regdate AS joinDate FROM {$sqlUserTable} AS u WHERE {$sqlUserTableCols[userId]} = $getuser[userId]");

      if ($getuserf['user_avatar']) {
        $getuserf['avatar'] = $forumUrl . '/download/file.php?avatar=' . $getuserf['user_avatar'];
      }
    }
    break;
  }
}



$xmlData = array(
  'getUserInfo' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => fim_encodeXml($user['userName']),
    ),
    'sentData' => array(
      'userId' => (int) $userId,
      'userName' => fim_encodeXml($userName),
    ),
    'errorcode' => fim_encodeXml($failCode),
    'errortext' => fim_encodeXml($failMessage),
    'userData' => array(
      'userName' => fim_encodeXml($getuser['userName']),
      'userId' => (int) $getuser['userId'],
      'userGroup' => (int) $getuser['userGroup'],
      'socialGroups' => fim_encodeXml($getuser['socialGroups']),
      'startTag' => fim_encodeXml($getuser['userFormatStart']),
      'endTag' => fim_encodeXml($getuser['userFormatEnd']),
      'defaultFormatting' => array(
        'color' => fim_encodeXml($getuser['defaultColour']),
        'highlight' => fim_encodeXml($getuser['defaultHighlight']),
        'fontface' => fim_encodeXml($getuser['defaultFontface']),
        'general' => (int) $getuser['defaultGeneral']
      ),
      'favRooms' => fim_encodeXml($getuser['favRooms']),
      'postCount' => (int) $getuserf['posts'],
      'joinDate' => (int) $getuserf['joinDate'],
      'joinDateFormatted' => fim_encodeXml(fim_date(false,$getuserf['joinDate'])),
      'userTitle' => fim_encodeXml($getuserf['usertitle']),
      'avatar' => fim_encodeXml($getuserf['avatar']),
    ),
  ),
);

echo fim_outputXml($xmlData);

mysqlClose();
?>