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

require_once('../global.php');
require_once('../functions/parserFunctions.php');

switch($_GET['action']) {
  case 'kickuser':
  $userid = intval($_GET['userid']);
  $user2 = sqlArr("SELECT u1.settings, u2.userid, u2.username FROM {$sqlPrefix}users AS u1, user AS u2 WHERE u2.userid = $userid AND u2.userid = u1.userid");

  $room = intval($_GET['roomid']);
  $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");

  $time = floor($_GET['time'] * 60);

  if ($time <= 0) {
    echo 'Oh, stop entering "0". It\'s very annoying';
  }
  elseif (!$user2['userid']) {
    echo 'Uh... User detection thingy error.';
  }
  elseif ($user2['settings'] & 16) { // You can't kick admins.
    echo 'You\'re really not supposed to kick admins... I mean, sure, it sounds fun and all, but still... we don\'t like it >:D';

    $message = finalParse('/me fought the law and the law won.');

    list($messageRaw,$messageHtml,$messageVBnet,$saltNum,$iv) = $message;

    mysqlQuery("INSERT INTO {$sqlPrefix}messages (user, room, rawText, htmlText, vbText, salt, iv, microtime, ip) VALUES ($user[userid], $room[id], '$messageRaw', '$messageHtml', '$messageVBnet', $saltNum, '$iv', '" . microtime(true) . "', '$ip')");
  }
  elseif (!hasPermission($room,$user,'moderate')) {
    echo '...You\'re not a mod...';
  }
  else {
    mysqlQuery("INSERT INTO {$sqlPrefix}kick (userid, kickerid, length, room) VALUES ($user2[userid], $user[userid], $time, $room[id])");
    
    $message = finalParse('/me kicked ' . $user2['username']);

    list($messageRaw,$messageHtml,$messageVBnet,$saltNum,$iv) = $message;

    mysqlQuery("INSERT INTO {$sqlPrefix}messages (user, room, rawText, htmlText, vbText, salt, iv, microtime, ip) VALUES ($user[userid], $room[id], '$messageRaw', '$messageHtml', '$messageVBnet', $saltNum, '$iv', '" . microtime(true) . "', '$ip')");
  }
  break;

  case 'banuser':
  $userid = intval($_GET['userid']);
  $user2 = sqlArr("SELECT * FROM {$sqlPrefix}users WHERE userid = $userid");

  if ($user2['settings'] & 16) { } // You can't ban admins.
  elseif ($user['settings'] & 16 == false) { } // The user is not an administrator.
  else {  
    if ($user2['settings'] & 1) mysqlQuery("UPDATE {$sqlPrefix}users SET settings = settings - 1 WHERE userid = $userid");
    else mysqlQuery("UPDATE {$sqlPrefix}users SET settings = settings + 1 WHERE userid = $userid");
  }
  break;

  case 'deletepost':
  $postid = intval($_GET['postid']);
  $post = sqlArr("SELECT * FROM {$sqlPrefix}messages WHERE id = $postid");
  $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $post[room]");

  if ($user['settings'] & 16 == true || in_array($user['userid'],explode(',',$room['moderators'])) || $user['id'] == $room['owner']) {
    if ($post['deleted']) mysqlQuery("UPDATE {$sqlPrefix}messages SET deleted = 0 WHERE id = $post[id]");
    else mysqlQuery("UPDATE {$sqlPrefix}messages SET deleted = 1 WHERE id = $post[id]");
  }
  else {}
  break;

  case 'deleteroom':
  $room = intval($_GET['roomid']);
  $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");

  if ((($user['settings'] & 16) == true || $room['owner'] == $user['id']) && (($room['options'] & 1) == false)) { // The user most either own the room or be an administrator. Additionally, the room can not be an official room.
    if ($room['options'] & 4) mysqlQuery("UPDATE {$sqlPrefix}rooms SET options = options - 4 WHERE id = $room[id]");
    else mysqlQuery("UPDATE {$sqlPrefix}rooms SET options = options + 4 WHERE id = $room[id]");
  }
  else {echo $user['settings'] & 16; echo $room['options'] & 1;}
  break;

  case 'favroom': // Note that we won't check permissions since the worse a hacker could do is learn a room's name - which doesn't make a difference.
  $room = intval($_GET['roomid']);

  $currentRooms = explode(',',$user['favRooms']);
  if (!in_array($room,$currentRooms)) {
    $currentRooms[] = $room;
    foreach ($currentRooms as $room2) if ($room2 != '') $currentRooms2[] = $room2; // Rebuild the array without the room ID.
    $newRoomString = mysqlEscape(implode(',',$currentRooms2));
  }
  else {
    foreach ($currentRooms as $room2) if ($room2 != $room && $room2 != '') $currentRooms2[] = $room2; // Rebuild the array without the room ID.
    $newRoomString = mysqlEscape(implode(',',$currentRooms2));
  }

  mysqlQuery("UPDATE {$sqlPrefix}users SET favRooms = '$newRoomString' WHERE userid = $user[userid]");
  break;
}

mysqlClose();
?>