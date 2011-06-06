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

$action = fim_urldecode($_POST['action']);

switch ($action) {
  case 'createRoom':
  $name = substr(mysqlEscape($_POST['name']),0,20); // Limits to 20 characters.

  if (!$name) {
    $failCode = 'noName';
    $failMessage = 'A room name was not supplied.';
  }
  else {
    if (sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE name = '$name'")) {
      $failCode = 'exists';
      $failMessage = 'The room specified already exists.';
    }
    else {
      $allowedGroups = mysqlEscape($_POST['allowedGroups']);
      $allowedUsers = mysqlEscape($_POST['allowedUsers']);
      $moderators = mysqlEscape($_POST['moderators']);
      $options = ($_POST['mature'] ? 2 : 0);
      $bbcode = intval($_POST['bbcode']);

      mysqlQuery("INSERT INTO {$sqlPrefix}rooms (name,allowedGroups,allowedUsers,moderators,owner,options,bbcode) VALUES ('$name','$allowedGroups','$allowedUsers','$moderators',$user[userId],$options,$bbcode)");
      $insertId = mysql_insert_id();

      if ($insertId) {
        $data = "<insertId>$insertId</insertId>";
      }
      else {
        $failCode = 'unknown';
        $failMessage = 'Room created failed for unknown reasons.';
      }
    }
  }
  break;

  case 'editRoom':
  $name = substr(mysqlEscape($_POST['name']),0,20); // Limits to 20 characters.

  if (!$name) {
    $failCode = 'noName';
    $failMessage = 'A room name was not supplied.';
  }
  elseif ($user['userId'] != $room['owner'] && !($user['settings'] & 16)) { // Again, check to make sure the user is the group's owner or an admin.
    $failCode = 'noperm';
    $failMessage = 'You do not have permission to edit this room.';
  }
  elseif ($room['settings'] & 4) { // Make sure the room hasn't been deleted.
    $failCode = 'deleted';
    $failMessage = 'The room has been deleted - it can not be edited.';
  }
  else {
    $data = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE name = '$name'"); // Get existing data.

    if ($data && $data['id'] != $room['id']) {
      $failCode = 'exists';
      $failMessage = 'The room name specified already exists.';
    }
    else {
      $listsActive = sqlArr("SELECT * FROM {$sqlPrefix}censorBlackWhiteLists WHERE roomId = $room[id]",'id');
      if ($listsActive) {
        foreach ($listsActive AS $active) {
          $listStatus[$active['listId']] = $active['status'];
        }
      }

      $censorLists = $_POST['censor'];
      foreach($censorLists AS $id => $list) {
        $listsNew[$id] = $list;
      }

      $lists = sqlArr("SELECT * FROM {$sqlPrefix}censorLists AS l WHERE options & 2",'id');
      foreach ($lists AS $list) {
        if ($list['type'] == 'black' && $listStatus[$list['id']] == 'block') {
          $checked = true;
        }
        elseif ($list['type'] == 'white' && $listStatus[$list['id']] != 'unblock') {
          $checked = true;
        }
        else {
          $checked = false;
        }

        if ($checked == true && !$listsNew[$list['id']]) {
          mysqlQuery("INSERT INTO ${sqlPrefix}censorBlackWhiteLists (roomId, listId, status) VALUES ($room[id], $id, 'unblock') ON DUPLICATE KEY UPDATE status = 'unblock'");
        }
        elseif ($checked == false && $listsNew[$list['id']]) {
          mysqlQuery("INSERT INTO ${sqlPrefix}censorBlackWhiteLists (roomId, listId, status) VALUES ($room[id], $id, 'block') ON DUPLICATE KEY UPDATE status = 'block'");
        }
      }

      $allowedGroups = mysqlEscape($_POST['allowedGroups']);
      $allowedUsers = mysqlEscape($_POST['allowedUsers']);
      $moderators = mysqlEscape($_POST['moderators']);
      $options = ($room['options'] & 1) + ($_POST['mature'] ? 2 : 0) + ($room['options'] & 4) + ($room['options'] & 8) + ($_POST['disableModeration'] ? 32 + 0 : 0);
      $bbcode = intval($_POST['bbcode']);
      mysqlQuery("UPDATE {$sqlPrefix}rooms SET name = '$name', allowedGroups = '$allowedGroups', allowedUsers = '$allowedUsers', moderators = '$moderators', options = '$options', bbcode = '$bbcode' WHERE id = $room[id]");
    }
  }
  break;

  case 'deleteRoom':

  break;


  case 'deletePost':

  break;

  case 'undeletePost':

  break;


  case 'banuser':

  break;

  case 'unbanuser':

  break;


  case 'kickuser':
  $userId = intval($_POST['userId']);
  $user2 = sqlArr("SELECT u1.settings, u2.userId, u2.userName FROM {$sqlPrefix}users AS u1, user AS u2 WHERE u2.userId = $userId AND u2.userId = u1.userId");

  $room = intval($_POST['roomId']);
  $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");

  $time = floor($_POST['time'] * $_POST['interval']);

  if (!$user2['userId']) {
    trigger_error('Invalid User',E_USER_ERROR);
  }
  elseif (!$room['id']) {
    trigger_error('Invalid Room',E_USER_ERROR);
  }
  elseif ($user2['settings'] & 16 && false) { // You can't kick admins.
    trigger_error('You\'re really not supposed to kick admins... I mean, sure, it sounds fun and all, but still... we don\'t like it >:D');

    sendMessage('/me fought the law and the law won.',$user['userId'],$room['id']);
  }
  elseif (!hasPermission($room,$user,'moderate')) {
    trigger_error('No Permission',E_USER_ERROR);
  }
  else {
    modLog('kick',"$user2[userId],$room[id]");

    mysqlQuery("INSERT INTO {$sqlPrefix}kick (userId, kickerid, length, room) VALUES ($user2[userId], $user[userId], $time, $room[id])");

    sendMessage('/me kicked ' . $user2['userName'],$user,$room);

    echo 'The user has been kicked';
  }
  break;

  case 'unkickuser':
  $userId = intval($_POST['userId']);
  $user2 = sqlArr("SELECT u1.settings, u2.userId, u2.userName FROM {$sqlPrefix}users AS u1, user AS u2 WHERE u2.userId = $userId AND u2.userId = u1.userId");

  $room = intval($_POST['roomId']);
  $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");

  if (!$user2['userId']) {
    trigger_error('Invalid User',E_USER_ERROR);
  }
  elseif (!$room['id']) {
    trigger_error('Invalid Room',E_USER_ERROR);
  }
  elseif (!hasPermission($room,$user,'moderate')) {
    trigger_error('No Permission',E_USER_ERROR);
  }
  else {
    modLog('unkick',"$user2[userId],$room[id]");

    mysqlQuery("DELETE FROM {$sqlPrefix}kick WHERE userId = $user2[userId] AND room = $room[id]");

    sendMessage('/me unkicked ' . $user2['userName'],$user,$room);

    echo $user2['userName'] . ' has been unbanned.';
  }
  break;

  default:

  break;
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<moderate>
  <activeUser>
    <userId>$user[userId]</userId>
    <userName>" . fim_encodeXml($user['userName']) . "</userName>
  </activeUser>
  <sentData>
    <action>" . fim_encodeXml($_POST['action']) . "</action>
    <roomId>" . (int) $_POST['roomId'] . "</roomId>
    <userId>" . (int) $_POST['userId'] . "</userId>
  </sentData>
  <errorcode>$failCode</errorcode>
  <errortext>$failMessage</errortext>
  <response>
    $data
  </response>
</moderate>";

mysqlClose();
?>