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


$xmlData = array(
  'moderate' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => fim_encodeXml($user['userName']),
    ),
    'sentData' => array(
      'action' => fim_encodeXML($_POST['action']),
      'roomId' => (int) $_POST['roomId'],
      'userId' => (int) $_POST['userId'],
    ),
    'errorcode' => fim_encodeXml($failCode),
    'errortext' => fim_encodeXml($failMessage),
    'response' => array(),
  ),
);


switch ($action) {
  case 'createRoom':
  $name = substr(dbEscape($_POST['name']),0,20); // Limits to 20 characters.

  if (!$name) {
    $failCode = 'noName';
    $failMessage = 'A room name was not supplied.';
  }
  else {
    if (dbRows("SELECT * FROM {$sqlPrefix}rooms WHERE name = '$name'")) {
      $failCode = 'exists';
      $failMessage = 'The room specified already exists.';
    }
    else {
      $allowedGroups = dbEscape($_POST['allowedGroups']);
      $allowedUsers = dbEscape($_POST['allowedUsers']);
      $moderators = dbEscape($_POST['moderators']);
      $options = ($_POST['mature'] ? 2 : 0);
      $bbcode = intval($_POST['bbcode']);

      dbQuery("INSERT INTO {$sqlPrefix}rooms (name,allowedGroups,allowedUsers,moderators,owner,options,bbcode) VALUES ('$name','$allowedGroups','$allowedUsers','$moderators',$user[userId],$options,$bbcode)");
      $insertId = mysql_insert_id();

      if ($insertId) {
        $xmlData['moderate']['response']['insertId'] = $insertId;
      }
      else {
        $failCode = 'unknown';
        $failMessage = 'Room created failed for unknown reasons.';
      }
    }
  }
  break;

  case 'editRoom':
  $name = substr(dbEscape($_POST['name']),0,20); // Limits to 20 characters.

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
    $data = dbRows("SELECT * FROM {$sqlPrefix}rooms WHERE name = '$name'"); // Get existing data.

    if ($data && $data['id'] != $room['id']) {
      $failCode = 'exists';
      $failMessage = 'The room name specified already exists.';
    }
    else {
      $listsActive = dbRows("SELECT * FROM {$sqlPrefix}censorBlackWhiteLists WHERE roomId = $room[id]",'id');
      if ($listsActive) {
        foreach ($listsActive AS $active) {
          $listStatus[$active['listId']] = $active['status'];
        }
      }

      $censorLists = $_POST['censor'];
      foreach($censorLists AS $id => $list) {
        $listsNew[$id] = $list;
      }

      $lists = dbRows("SELECT * FROM {$sqlPrefix}censorLists AS l WHERE options & 2",'id');
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
          dbQuery("INSERT INTO ${sqlPrefix}censorBlackWhiteLists (roomId, listId, status) VALUES ($room[id], $id, 'unblock') ON DUPLICATE KEY UPDATE status = 'unblock'");
        }
        elseif ($checked == false && $listsNew[$list['id']]) {
          dbQuery("INSERT INTO ${sqlPrefix}censorBlackWhiteLists (roomId, listId, status) VALUES ($room[id], $id, 'block') ON DUPLICATE KEY UPDATE status = 'block'");
        }
      }

      $allowedGroups = dbEscape($_POST['allowedGroups']);
      $allowedUsers = dbEscape($_POST['allowedUsers']);
      $moderators = dbEscape($_POST['moderators']);
      $options = ($room['options'] & 1) + ($_POST['mature'] ? 2 : 0) + ($room['options'] & 4) + ($room['options'] & 8) + ($_POST['disableModeration'] ? 32 + 0 : 0);
      $bbcode = intval($_POST['bbcode']);
      dbQuery("UPDATE {$sqlPrefix}rooms SET name = '$name', allowedGroups = '$allowedGroups', allowedUsers = '$allowedUsers', moderators = '$moderators', options = '$options', bbcode = '$bbcode' WHERE id = $room[id]");
    }
  }
  break;

  case 'privateRoom':
  $userName = ($_POST['userName']);
  $userId = (int) ($_POST['userId']);

  if ($userName) {
    $safename = dbEscape($_POST['userName']); // Escape the userName for MySQL.
    $user2 = dbRows("SELECT * FROM user WHERE userName = '$safename'"); // Get the user information.
  }
  elseif ($userId) {
    $user2 = dbRows("SELECT * FROM user WHERE userId = $userId");
  }
  else {
    $failCode = 'baduser';
    $failMessage = 'That user does not exist.';
  }

  if (!$user2) { // No user exists.
  }
  elseif ($user2['userId'] == $user['userId']) { // Don't allow the user to, well, talk to himself.
    $failCode = 'sameuser';
    $failMessage = 'The user specified is yourself.';
  }
  else {
    $room = dbRows("SELECT * FROM {$sqlPrefix}rooms WHERE (allowedUsers = '$user[userId],$user2[userId]' OR allowedUsers = '$user2[userId],$user[userId]') AND options & 16"); // Query a group that would match the criteria for a private room.
    if ($room) {
      $xmlData['moderate']['response']['insertId'] = $room['roomId']; // Already exists; return ID
    }
    else {
      $allowedGroups = ''; // Empty
      $allowedUsers = "$user[userId],$user2[userId]"; // The two people who are talking!
      $moderators = ''; // Empty
      $options = 48; // 32 - No Moderation; 16 - Private
      $bbcode = 1; // Everything!
      $name = dbEscape("Private IM ($user[userName] and $user2[userName])");

      dbQuery("INSERT INTO {$sqlPrefix}rooms (name,allowedGroups,allowedUsers,moderators,owner,options,bbcode) VALUES ('$name','$allowedGroups','$allowedUsers','$moderators',$user[userId],$options,$bbcode)");
      $insertId = mysql_insert_id();

      $xmlData['moderate']['response']['insertId'] = $insertId;
    }
  }
  break;

  case 'deleteRoom':

  break;

  case 'userOptions':
  $userId = (int) $_POST['userId'];

  $userData = dbRows("SELECT * FROM {$sqlPrefix}users WHERE userId = $userId");

  /*** Web Interface Options ***/

  $settingsOfficialAjaxIndex = array(
    'disableFormatting' => 16,
    'disableVideos' => 32,
    'disableImages' => 64,
    'reversePostOrder' => 1024,
    'showAvatars' => 2048,
    'audioDing' => 8192,
  );

  if ($user['adminPrivs']['modUsers'] || $user['userId'] == $userId) { echo 5;
    if (isset($_POST['settingsOfficialAjax'])) { echo 6;
      foreach ($settingsOfficialAjaxIndex AS $name => $val) {
        if ($_POST['settingsOfficialAjax_' . $name]) {
          if ($userData['settingsOfficialAjax'] & $val) { echo 1;}
          else { echo 2;
            $userData['settingsOfficialAjax'] += $val;

            $xmlData['moderate']['response']['modified']['value ' . $name] = $name;
          }
        }
        else { echo 3;
          if ($userData['settingsOfficialAjax'] & $val) {
            $userData['settingsOfficialAjax'] -= $val;

            $xmlData['moderate']['response']['modified']['value ' . $name] = $name;
          }
          else { echo 4;}
        }
      }
    }
  }
  else {
    // No Permission
  }


  /*** User Options ***/

  $userIndex = array(
    'modPrivs' => 16,
    'modUsers' => 32,
  );

  if ($user['adminPrivs']['modUsers'] || $user['userId'] == $userId) {
    if (isset($_POST['userPrivs'])) {
      foreach ($userIndex AS $name => $val) {
        if ($_POST['user_' . $name]) {
          if ($userData['userPrivs'] & $val) {}
          else {
            $userData['userPrivs'] += $val;

            $xmlData['moderate']['response']['modified']['value ' . $name] = $name;
          }
        }
        else {
          if ($userData['userPrivs'] & $val) {
            $userData['userPrivs'] -= $val;

            $xmlData['moderate']['response']['modified']['value ' . $name] = $name;
          }
          else {}
        }
      }
    }
  }
  else {
    // No Permission
  }

  /*** Admin Options ***/

  $adminIndex = array(
    'modPrivs' => 1,
    'modUsers' => 16,
    'modImages' => 64,
    'modCensorWords' => 256,
    'modCensorLists' => 512,
    'modPlugins' => 4096,
    'modTemplates' => 8192,
    'modHooks' => 16384,
    'modTranslations' => 32768,
  );

  if ($user['adminPrivs']['modPrivs']) {
    if (isset($_POST['adminPrivs'])) {
      foreach ($adminIndex AS $name => $val) {
        if ($_POST['admin_' . $name]) {
          if ($userData['adminPrivs'] & $val) {}
          else {
            $userData['adminPrivs'] += $val;

            $xmlData['moderate']['response']['modified']['value ' . $name] = $name;
          }
        }
        else {
          if ($userData['adminPrivs'] & $val) {
            $userData['adminPrivs'] -= $val;

            $xmlData['moderate']['response']['modified']['value ' . $name] = $name;
          }
          else {}
        }
      }
    }
  }
  else {
    // No Permission
  }


  if (isset($_POST['settingsOfficialAjax_theme'])) {
    $theme = (int) $_POST['settingsOfficialAjax_theme'];

    $userData['theme'] = $theme;

    $xmlData['moderate']['response']['theme'] = $theme;
  }


  if (isset($_POST['defaultColor'])) {
    $color = dbEscape($_POST['defaultColor']);

    $userData['theme'] = $theme;

    $xmlData['moderate']['response']['theme'] = $theme;
  }



  dbQuery("UPDATE {$sqlPrefix}users SET themeOfficialAjax = $userData[theme], adminPrivs = $userData[adminPrivs], userPrivs = $userData[userPrivs], settingsOfficialAjax = $userData[settingsOfficialAjax]");

  break;


  case 'deletePost':
  $messageId = (int) $_POST['messageId'];
  $messageData = dbRows("SELECT * FROM {$sqlPrefix}messages WHERE messageId = $messageId");
  $roomData = dbRows("SELECT * FROM {$sqlPrefix}rooms WHERE roomId = $messageData[roomId]");

  if (fim_hasPermission($roomData,$user,'moderate',true)) {
    $xmlData['moderate']['response']['success'] = true;
  }
  else {
    $failCode = 'nopermission';
    $failMessage = 'You are not allowed to moderate this room.';
  }
  break;


  case 'undeletePost':
  $messageId = (int) $_POST['messageId'];
  $messageData = dbRows("SELECT * FROM {$sqlPrefix}messages WHERE messageId = $messageId");
  $roomData = dbRows("SELECT * FROM {$sqlPrefix}rooms WHERE roomId = $messageData[roomId]");

  if (fim_hasPermission($roomData,$user,'moderate')) {
    $xmlData['moderate']['response']['success'] = true;
  }
  else {
    $failCode = 'nopermission';
    $failMessage = 'You are not allowed to moderate this room.';
  }
  break;


  case 'kickUser':
  $userId = (int) $_POST['userId'];
  $user2 = dbRows("SELECT * FROM {$sqlPrefix}users WHERE userId = $userId");

  $roomId = (int) $_POST['roomId'];
  $room = dbRows("SELECT * FROM {$sqlPrefix}rooms WHERE roomId = $roomId");

  $time = (int) floor($_POST['time'] * $_POST['interval']);

  if (!$user2['userId']) {
    $failCode = 'baduser';
    $failMessage = 'The room specified is not valid.';
  }
  elseif (!$room['roomId']) {
    $failCode = 'badroom';
    $failMessage = 'The room specified is not valid.';
  }
  elseif (fim_hasPermission($room,$user2,'moderate',true)) { // You can't kick admins.
    $failCode = 'nokickuser';
    $failMessage = 'The user specified may not be kicked.';

    require_once('../functions/parserFunctions.php');
    fim_sendMessage('/me fought the law and the law won.',$user,$room);
  }
  elseif (!fim_hasPermission($room,$user,'moderate',true)) {
    $failCode = 'nopermission';
    $failMessage = 'You are not allowed to moderate this room.';
  }
  else {
    modLog('kick',"$user2[userId],$room[roomId]");

    dbQuery("INSERT INTO {$sqlPrefix}kick (userId, kickerId, length, room) VALUES ($user2[userId], $user[userId], $time, $room[roomId])");

    require_once('../functions/parserFunctions.php');
    fim_sendMessage('/me kicked ' . $user2['userName'],$user,$room);

    $xmlData['moderate']['response']['success'] = true;
  }
  break;


  case 'unkickuser':
  $userId = intval($_POST['userId']);
  $user2 = dbRows("SELECT u1.settings, u2.userId, u2.userName FROM {$sqlPrefix}users AS u1, user AS u2 WHERE u2.userId = $userId AND u2.userId = u1.userId");

  $room = intval($_POST['roomId']);
  $room = dbRows("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");

  if (!$user2['userId']) {
    $failCode = 'baduser';
    $failMessage = 'The room specified is not valid.';
  }
  elseif (!$room['id']) {
    $failCode = 'badroom';
    $failMessage = 'The room specified is not valid.';
  }
  elseif (!fim_hasPermission($room,$user,'moderate',true)) {
    $failCode = 'nopermission';
    $failMessage = 'You are not allowed to moderate this room.';
  }
  else {
    modLog('unkick',"$user2[userId],$room[id]");

    dbQuery("DELETE FROM {$sqlPrefix}kick WHERE userId = $user2[userId] AND room = $room[id]");

    require_once('../functions/parserFunctions.php');
    fim_sendMessage('/me unkicked ' . $user2['userName'],$user,$room);

    $xmlData['moderate']['response']['success'] = true;
  }
  break;


  default:

  break;
}



$xmlData['moderate']['errorcode'] = fim_encodeXml($failCode);
$xmlData['moderate']['errortext'] = fim_encodeXml($failMessage);

echo fim_outputApi($xmlData);


dbClose();
?>