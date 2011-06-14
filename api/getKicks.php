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

if (isset($_GET['rooms'])) {
  $rooms = (string) $_GET['rooms'];
  $roomsArray = explode(',',$rooms);
}
if ($roomsArray) {
  foreach ($roomsArray AS &$v) {
    $v = intval($v);
    $roomData = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $v");
    if (!hasPermission($roomData,$user,'moderate')) unset($v);
  }
  $roomList = implode(',',$roomsArray);
}

if (isset($_GET['users'])) {
  $users = (string) $_GET['users'];
  $usersArray = explode(',',$users);
}
if ($usersArray) {
  foreach ($usersArray AS &$v) {
    $v = intval($v);
  }
  $userList = implode(',',$usersArray);
}


if ($roomList) {
  $where .= "roomId IN ($roomList)";
}
if ($userList) {
  $where .= "userId IN ($userList)";
}


$xmlData = array(
  'getKicks' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => fim_encodeXml($user['userName']),
    ),
    'sentData' => array(
      'rooms' => $roomList,
      'users' => $userList,
    ),
    'errorcode' => fim_encodeXml($failCode),
    'errormessage' => fim_encodeXml($failMessage),
    'kicks' => array(),
  ),
);


($hook = hook('getKicks_start') ? eval($hook) : '');


$kicks = sqlArr("SELECT k.id AS kickId,
  k.userId,
  u.userName AS userName,
  k.roomId,
  k.length,
  k.time,
  k.kickerId,
  i.userName AS kickerName,
  r.name AS roomName
FROM {$sqlPrefix}kick AS k
  LEFT JOIN {$sqlPrefix}users AS u ON k.userId = u.userId
  LEFT JOIN {$sqlPrefix}users AS i ON k.kickerId = i.userId
  LEFT JOIN {$sqlPrefix}rooms AS r ON k.roomId = r.roomId
WHERE $where TRUE",'id');


foreach ($kicks AS $kick) {
  $xmlData['getKicks']['kicks']['kick ' . $kick['kickId']] = array(
    'roomData' => array(
      'roomId' => (int) $kick['roomId'],
      'roomName' => $kick['roomName'],
    ),
    'userData' => array(
      'userId' => (int) $kick['userId'],
      'userName' => $kick['userName'],
    ),
    'kickerData' => array(
      'userId' => (int) $kick['kickerId'],
      'userName' => $kick['kickerName'],
    ),
    'length' => (int) $kick['length'],
    'set' => (int) $kick['time'],
    'expires' => (int) $kick['expires'],
  );

  ($hook = hook('getKicks_eachKick') ? eval($hook) : '');
}


$xmlData['getKicks']['errorcode'] = fim_encodeXml($failCode);
$xmlData['getKicks']['errortext'] = fim_encodeXml($failMessage);


($hook = hook('getKicks_end') ? eval($hook) : '');


echo fim_outputXml($xmlData);

mysqlClose();
?>