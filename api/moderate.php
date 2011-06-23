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
  $roomLengthLimit = ($roomLengthLimit ? $roomLengthLimit : 20)

  $name = substr($_POST['name'],0,$roomLengthLimit); // Limits to x characters.

  if (!$name) {
    $failCode = 'noname';
    $failMessage = 'A room name was not supplied.';
  }
  else {
    $safeName = dbEscape($name);

    if (dbRows("SELECT * FROM {$sqlPrefix}rooms WHERE roomName = '$safeName'")) {
      $failCode = 'exists';
      $failMessage = 'The room specified already exists.';
    }
    else {
      $allowedGroups = dbEscape($_POST['allowedGroups']);
      $allowedUsers = dbEscape($_POST['allowedUsers']);
      $moderators = dbEscape($_POST['moderators']);
      $options = ($_POST['mature'] ? 2 : 0);
      $bbcode = intval($_POST['bbcode']);

      dbInsert(array(
        'roomName' => $name,
        'allowedGroups' => $allowedGroups,
        'allowedUsers' => $allowedUsers,
        'moderators' => $moderators,
        'owner' => $user['userId'],
        'options' => (int) $options,
        'bbcode' => (int) $bbcode,
        ),"{$sqlPrefix}rooms"
      );
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
  $roomLengthLimit = ($roomLengthLimit ? $roomLengthLimit : 20)

  $name = substr($_POST['name'],0,$roomLengthLimit); // Limits to x characters.

  $room = dbRows("SELECT roomId, roomName, options, allowedUsers, allowedGroups, moderators FROM {$sqlPrefix}rooms WHERE roomId = " . (int) $_POST['roomId']);

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
    $data = dbRows("SELECT roomId FROM {$sqlPrefix}rooms WHERE roomName = '$name'"); // Get existing data.

    if ($data && $data['roomId'] != $room['roomId']) {
      $failCode = 'exists';
      $failMessage = 'The room name specified already exists.';
    }
    else {
      $listsActive = dbRows("SELECT listId, status FROM {$sqlPrefix}censorBlackWhiteLists WHERE roomId = $room[roomId]",'listId');

      if ($listsActive) {
        foreach ($listsActive AS $active) {
          $listStatus[$active['listId']] = $active['status'];
        }
      }

      $censorLists = $_POST['censor'];
      foreach($censorLists AS $id => $list) {
        $listsNew[$id] = $list;
      }

      $lists = dbRows("SELECT listId, type FROM {$sqlPrefix}censorLists AS l WHERE options & 2",'listId');

      foreach ($lists AS $list) {
        if ($list['type'] == 'black' && $listStatus[$list['listId']] == 'block') {
          $checked = true;
        }
        elseif ($list['type'] == 'white' && $listStatus[$list['listId']] != 'unblock') {
          $checked = true;
        }
        else {
          $checked = false;
        }

        if ($checked == true && !$listsNew[$list['listId']]) {
          dbInsert(array(
            'roomId' => $room['roomId'],
            'listId' => $list['listId'],
            'status' => 'unblock'
          ),"{$sqlPrefix}censorBlackWhiteLists",array(
            'status' => 'unblock',
          ));
        }
        elseif ($checked == false && $listsNew[$list['listId']]) {
          dbInsert(array(
            'roomId' => $room['roomId'],
            'listId' => $list['listId'],
            'status' => 'block'
          ),"{$sqlPrefix}censorBlackWhiteLists",array(
            'status' => 'block',
          ));
        }
      }

      $allowedGroups = $_POST['allowedGroups'];
      $allowedUsers = $_POST['allowedUsers'];
      $moderators = $_POST['moderators'];
      $options = ($room['options'] & 1) + ($_POST['mature'] ? 2 : 0) + ($room['options'] & 4) + ($room['options'] & 8) + ($room['options'] & 16);
      $bbcode = intval($_POST['bbcode']);

      dbUpdate(array(
          'roomName' => $name,
          'allowedGroups' => $allowedGroups,
          'allowedUsers' => $allowedUsers,
          'moderators' => $moderators,
          'options' => (int) $options,
          'bbcode' => (int) $_POST['bbcode'],
        ),
        "{$sqlPrefix}rooms",
        array(
          'roomId' => $room['roomId'],
        )
      );
    }
  }
  break;

  case 'privateRoom':
  $userName = ($_POST['userName']);
  $userId = (int) ($_POST['userId']);

  if ($userName) {
    $safename = dbEscape($_POST['userName']); // Escape the userName for MySQL.
    $user2 = dbRows("SELECT * FROM {$sqlPrefix}users WHERE userName = '$safename'"); // Get the user information.
  }
  elseif ($userId) {
    $user2 = dbRows("SELECT * FROM {$sqlPrefix}users WHERE userId = $userId");
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
      dbInsert(array(
          'roomName' => "Private IM ($user[userName] and $user2[userName])",
          'allowedUsers' => "$user[userId],$user2[userId]",
          'options' => 48,
          'bbcode' => 1,
        ),"{$sqlPrefix}rooms"
      );

      $insertId = mysql_insert_id();

      $xmlData['moderate']['response']['insertId'] = $insertId;
    }
  }
  break;

  case 'deleteRoom':
  $room = dbRows("SELECT roomId, roomName, options, allowedUsers, allowedGroups, moderators FROM {$sqlPrefix}rooms WHERE roomId = " . (int) $_POST['roomId']);

  if ($room['options'] & 4) {
    $failCode = 'alreadydeleted';
    $failMessage = 'The room is already deleted.';
  }
  else {
    $room['options'] += 4; // options & 4 = deleted

    dbUpdate(array(
        'options' => (int) $room['options'],
      ),"{$sqlPrefix}rooms",array(
        'roomId' => (int) $room['roomId'],
      )
    );
  }
  break;

  case 'userOptions':
  $userId = (int) $_POST['userId'];

  $userData = dbRows("SELECT *
  FROM {$sqlPrefix}users
  WHERE userId = $userId
  LIMIT 1");

  if ($user['userId'] == $userData['userId']) {

    if (isset($_POST['defaultColor'])) {
      $color = dbEscape($_POST['defaultColor']);

      $userData['theme'] = $theme;

      $xmlData['moderate']['response']['theme'] = $theme;
    }

    if ($_POST['defaultRoomId']) {
      $defaultRoomData = dbRows("SELECT *
      FROM {$sqlPrefix}rooms
      WHERE roomId = " . (int) $_POST['defaultRoomId'] . "
      LIMIT 1");

      if (fim_hasPermission($defaultRoomData,$user,'view')) {
        $updateArray['defaultRoom'] = (int) $_POST['defaultRoomId'];

        $xmlData['moderate']['response']['defaultRoom']['status'] = true;
        $xmlData['moderate']['response']['defaultRoom']['newValue'] = (int) $_POST['defaultRoomId'];
      }
      else {
        $xmlData['moderate']['response']['defaultRoom']['status'] = false;
        $xmlData['moderate']['response']['defaultRoom']['errorcode'] = 'outofrange1';
        $xmlData['moderate']['response']['defaultRoom']['errortext'] = 'The first value ("red") was out of range.';
      }
    }

    if ($_POST['favRooms']) {
      $favRooms = arrayValidate(explode(',',$_POST['favRooms']),'int',false);
      $updateArray['favRooms'] = (string) implode(',',$favRooms);

      $xmlData['moderate']['response']['favRooms']['status'] = true;
      $xmlData['moderate']['response']['favRooms']['newValue'] = (string) implode(',',$favRooms);
    }

    if ($_POST['watchRooms']) {
      $watchRooms = arrayValidate(explode(',',$_POST['watchRooms']),'int',false);
      $updateArray['watchRooms'] = (string) implode(',',$watchRooms);

      $xmlData['moderate']['response']['watchRooms']['status'] = true;
      $xmlData['moderate']['response']['watchRooms']['newValue'] = (string) implode(',',$watchRooms);
    }

    if ($_POST['defaultFormatting']) {
      $updateArray['defaultFormatting'] = (int) $_POST['defaultFormatting'];

      $xmlData['moderate']['response']['defaultFormatting']['status'] = true;
      $xmlData['moderate']['response']['defaultFormatting']['newValue'] = (string) implode(',',$defaultFormatting);
    }

    foreach (array('defaultHighlight','defaultColor') AS $value) {
      if ($_POST[$value]) {
        $rgb = arrayValidate(explode(',',$_POST[$value]),'int',true);

        if (count($rgb) === 3) { // Too many entries.
          if ($rgb[0] < 0 || $rgb[0] > 255) { // First val out of range.
            $xmlData['moderate']['response'][$value]['status'] = false;
            $xmlData['moderate']['response'][$value]['errorcode'] = 'outofrange1';
            $xmlData['moderate']['response'][$value]['errortext'] = 'The first value ("red") was out of range.';
          }
          elseif ($rgb[1] < 0 || $rgb[1] > 255) { // Second val out of range.
            $xmlData['moderate']['response'][$value]['status'] = false;
            $xmlData['moderate']['response'][$value]['errorcode'] = 'outofrange2';
            $xmlData['moderate']['response'][$value]['errortext'] = 'The first value ("green") was out of range.';
          }
          elseif ($rgb[2] < 0 || $rgb[2] > 255) { // Third val out of range.
            $xmlData['moderate']['response'][$value]['status'] = false;
            $xmlData['moderate']['response'][$value]['errorcode'] = 'outofrange3';
            $xmlData['moderate']['response'][$value]['errortext'] = 'The third value ("blue") was out of range.';
          }
          else {
            $updateArray[$value] = implode(',',$rgb);

            $xmlData['moderate']['response'][$value]['status'] = true;
            $xmlData['moderate']['response'][$value]['newValue'] = (string) implode(',',$rgb);
          }
        }
        else {
          $xmlData['moderate']['response'][$value]['status'] = false;
          $xmlData['moderate']['response'][$value]['errorcode'] = 'badformat';
          $xmlData['moderate']['response'][$value]['errortext'] = 'The default highlight value was not properly formatted.';
        }
      }
    }

    if ($_POST['defaultFontface']) {
      $fontData = dbRows("SELECT fontId,
        name,
        data,
        category
      FROM {$sqlPrefix}fonts
      WHERE fontId = " . (int) $_POST['defaultFontface'] . "
      LIMIT 1");

      if ((int) $fontData['fontId']) {
        $xmlData['moderate']['response'][$value]['status'] = true;
        $xmlData['moderate']['response'][$value]['newValue'] = (int) $fontData['fontId'];
      }
      else {
        $xmlData['moderate']['response']['defaultHighlight']['status'] = false;
        $xmlData['moderate']['response']['defaultHighlight']['errorcode'] = 'nofont';
        $xmlData['moderate']['response']['defaultHighlight']['errortext'] = 'The specified font does not exist.';
      }
    }

    dbUpdate(
      $updateArray,
      "{$sqlPrefix}users",
      array(
        'userId' => $user['userId'],
      )
    );

  }
  else {
    $failCode = 'usermismatch';
    $failMessage = 'The specified user is not the currently logged in one.'; // We do this because, unlike other things, it is reasonably possible two people may switch off at the same terminal and not realize the other one is logged in, thus inadvertently changing the wrong user's settings.
  }

  break;


  case 'deleteMessage':
  $messageId = (int) $_POST['messageId'];
  $messageData = dbRows("SELECT * FROM {$sqlPrefix}messages WHERE messageId = $messageId");
  $roomData = dbRows("SELECT * FROM {$sqlPrefix}rooms WHERE roomId = $messageData[roomId]");

  if (fim_hasPermission($roomData,$user,'moderate',true)) {
    dbUpdate(array(
      'deleted' => 1
      ),
      "{$sqlPrefix}messages",
      array(
        "messageId" => $messageId
      )
    );

    dbUpdate(array(
      'deleted' => 1
      ),
      "{$sqlPrefix}messagesCached",
      array(
        "messageId" => $messageId
      )
    );

    $xmlData['moderate']['response']['success'] = true;
  }
  else {
    $failCode = 'nopermission';
    $failMessage = 'You are not allowed to moderate this room.';
  }
  break;


  case 'undeleteMessage':
  $messageId = (int) $_POST['messageId'];
  $messageData = dbRows("SELECT * FROM {$sqlPrefix}messages WHERE messageId = $messageId");
  $roomData = dbRows("SELECT * FROM {$sqlPrefix}rooms WHERE roomId = $messageData[roomId]");

  if (fim_hasPermission($roomData,$user,'moderate')) {
    dbUpdate(array(
      'deleted' => 0
      ),
      "{$sqlPrefix}messages",
      array(
        "messageId" => $messageId
      )
    );

    $xmlData['moderate']['response']['success'] = true;
  }
  else {
    $failCode = 'nopermission';
    $failMessage = 'You are not allowed to moderate this room.';
  }
  break;


  case 'kickUser':
  $userId = (int) $_POST['userId'];
  $userData = dbRows("SELECT * FROM {$sqlPrefix}users WHERE userId = $userId");

  $roomId = (int) $_POST['roomId'];
  $roomData = dbRows("SELECT * FROM {$sqlPrefix}rooms WHERE roomId = $roomId");

  $time = (int) $_POST['length'];

  if (!$userData['userId']) {
    $failCode = 'baduser';
    $failMessage = 'The room specified is not valid.';
  }
  elseif (!$roomData['roomId']) {
    $failCode = 'badroom';
    $failMessage = 'The room specified is not valid.';
  }
  elseif (fim_hasPermission($roomData,$userData,'moderate',true)) { // You can't kick other moderators.
    $failCode = 'nokickuser';
    $failMessage = 'The user specified may not be kicked.';

    require_once('../functions/parserFunctions.php');
    fim_sendMessage('/me fought the law and the law won.',$user,$roomData);
  }
  elseif (!fim_hasPermission($roomData,$user,'moderate',true)) { // You have to be a mod yourself.
    $failCode = 'nopermission';
    $failMessage = 'You are not allowed to moderate this room.';
  }
  else {
    modLog('kick',"$userData[userId],$roomData[roomId]");

    dbInsert(array(
        'userId' => (int) $userData['userId'],
        'kickerId' => (int) $user['userId'],
        'length' => (int) $time,
        'roomId' => (int) $roomData['roomId'],
      ),"{$sqlPrefix}kick",array(
        'length' => (int) $time,
        'kickerId' => (int) $user['userId'],
        'time' => array(
          'type' => 'raw',
          'value' => 'NOW()',
        ),
      )
    );

    require_once('../functions/parserFunctions.php');
    fim_sendMessage('/me kicked ' . $userData['userName'],$user,$room);

    $xmlData['moderate']['response']['success'] = true;
  }
  break;


  case 'unkickUser':
  $userId = (int) $_POST['userId'];
  $userData = dbRows("SELECT * FROM {$sqlPrefix}users WHERE userId = $userId");

  $roomId = (int) $_POST['roomId'];
  $roomData = dbRows("SELECT * FROM {$sqlPrefix}rooms WHERE roomId = $roomId");

  if (!$userData['userId']) {
    $failCode = 'baduser';
    $failMessage = 'The room specified is not valid.';
  }
  elseif (!$roomData['roomId']) {
    $failCode = 'badroom';
    $failMessage = 'The room specified is not valid.';
  }
  elseif (!fim_hasPermission($roomData,$user,'moderate',true)) {
    $failCode = 'nopermission';
    $failMessage = 'You are not allowed to moderate this room.';
  }
  else {
    modLog('unkick',"$userData[userId],$roomData[roomId]");

    dbDelete("{$sqlPrefix}kick",array(
      'userId' => $userData['userId'],
      'roomId' => $roomData['roomId'],
    ));

    require_once('../functions/parserFunctions.php');
    fim_sendMessage('/me unkicked ' . $userData['userName'],$user,$roomData);

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