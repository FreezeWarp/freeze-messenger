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

$usersArray = explode(',',$_GET['rooms']);
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
      'userName' => fim_encodeXml($user['userName']),
    ),
    'sentData' => array(
      'order' => fim_encodeXML($order),
    ),
    'errorcode' => fim_encodeXml($failCode),
    'errortext' => fim_encodeXml($failMessage),
    'users' => array(),
  ),
);



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
    $xmlData['getUsers']['users']['user ' . $userData['userId']] = array(
      'userName' => fim_encodeXml($userData['userName']),
      'userId' => (int) $userData['userId'],
      'userGroup' => (int) $userData['userGroup'],
      'avatar' => fim_encodeXml($userData['avatar']),
      'profile' => fim_encodeXml($userData['profile']),
      'socialGroups' => fim_encodeXml($userData['socialGroups']),
      'startTag' => fim_encodeXml($userData['userFormatStart']),
      'endTag' => fim_encodeXml($userData['userFormatEnd']),
      'defaultFormatting' => array(
        'color' => fim_encodeXml($userData['defaultColor']),
        'highlight' => fim_encodeXml($userData['defaultHighlight']),
        'fontface' => fim_encodeXml($userData['defaultFontface']),
        'general' => (int) $userData['defaultGeneral']
      ),
    );
  }
}


$xmlData['getUsers']['errorcode'] = fim_encodeXml($failCode);
$xmlData['getUsers']['errortext'] = fim_encodeXml($failMessage);

echo fim_outputXml($xmlData);


mysqlClose();
?>