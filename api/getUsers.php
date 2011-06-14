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

$usersArray = explode(',',$_GET['users']);
foreach ($usersArray AS &$v) {
  $v = (int) $v;
}


if ($users) {
  $whereClause .= ' userId IN (' . implode(',',$usersArray) . ') AND ';
}


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



$xmlData = array(
  'getUsers' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'sentData' => array(
      'order' => fim_encodeXML($order),
    ),
    'errorcode' => ($failCode),
    'errortext' => ($failMessage),
    'users' => array(),
  ),
);


($hook = hook('getUsers_start') ? eval($hook) : '');


$users = sqlArr("SELECT u.userId,
 u.userName,
 u.userFormatStart,
 u.userFormatEnd
FROM {$sqlPrefix}users AS u
  {$join}
WHERE {$whereClause} TRUE
ORDER BY {$order}",'userId'); // Get all rooms


if ($users) {
  foreach ($users AS $userData) {
    ($hook = hook('getUsers_eachUser_start') ? eval($hook) : '');


    switch ($loginMethod) {
      case 'vbulletin':
      $userDataForums = sqlArr("SELECT joindate AS joinDate FROM {$sqlUserTable} AS u WHERE {$sqlUserTableCols[userId]} = $userData[userId]");
      break;

      case 'phpbb':
      $userDataForums = sqlArr("SELECT u.user_posts AS posts, u.user_colour, u.user_avatar, u.user_regdate AS joinDate FROM {$sqlUserTable} AS u WHERE {$sqlUserTableCols[userId]} = $userData[userId]");
      break;
    }


    ($hook = hook('getUsers_eachUser_postForums') ? eval($hook) : '');


    $xmlData['getUsers']['users']['user ' . $userData['userId']] = array(
      'userName' => ($userData['userName']),
      'userId' => (int) $userData['userId'],
      'userGroup' => (int) $userData['userGroup'],
      'avatar' => ($userData['avatar']),
      'profile' => ($userData['profile']),
      'socialGroups' => ($userData['socialGroups']),
      'startTag' => ($userData['userFormatStart']),
      'endTag' => ($userData['userFormatEnd']),
      'defaultFormatting' => array(
        'color' => ($userData['defaultColor']),
        'highlight' => ($userData['defaultHighlight']),
        'fontface' => ($userData['defaultFontface']),
        'general' => (int) $userData['defaultGeneral']
      ),
      'favRooms' => ($userData['favRooms']),
      'postCount' => (int) $userDataForums['posts'],
      'joinDate' => (int) $userDataForums['joinDate'],
      'joinDateFormatted' => (fim_date(false,$userDataForums['joinDate'])),
      'userTitle' => ($userDataForums['usertitle']),
    );


    ($hook = hook('getUsers_eachUser_end') ? eval($hook) : '');
  }
}


$xmlData['getUsers']['errorcode'] = ($failCode);
$xmlData['getUsers']['errortext'] = ($failMessage);


($hook = hook('getUsers_end') ? eval($hook) : '');


echo fim_outputXml($xmlData);

mysqlClose();
?>