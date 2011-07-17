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

/**
 * Creates, Edits, or Deletes a Room
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
 *
 * @param string action - "create", "edit", "delete", or "private"
 * Note for FIMv4: This will add several additional methods ("contact" being the main one) that allow for improved IM-like communication. This will essentially return any stream that has x users involved, and will replace private for the most part (private will instead be used for OTR communication).
*/

$apiRequest = true;

require_once('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC(array(
  'post' => array(
    'action' => array(
      'type' => 'string',
      'valid' => array(
        'create',
        'edit',
        'private',
        'delete',
      ),
      'require' => true,
    ),

    'roomId' => array(
      'type' => 'string',
      'require' => false,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'roomName' => array(
      'type' => 'string',
      'require' => false,
    ),

    'moderators' => array(
      'type' => 'string',
      'require' => false,
      'context' => array(
        'type' => 'csv',
        'filter' => 'int',
        'evaltrue' => true,
      ),
    ),

    'allowedUsers' => array(
      'type' => 'string',
      'require' => false,
      'context' => array(
        'type' => 'csv',
        'filter' => 'int',
        'evaltrue' => true,
      ),
    ),

    'allowedGroups' => array(
      'type' => 'string',
      'require' => false,
      'context' => array(
        'type' => 'csv',
        'filter' => 'int',
        'evaltrue' => true,
      ),
    ),

    'reverseOrder' => array(
      'type' => 'string',
      'require' => false,
      'default' => false,
      'context' => array(
        'type' => 'bool',
      ),
    ),

    'userId' => array(
      'type' => 'string',
      'require' => false,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'userName' => array(
      'type' => 'string',
      'require' => false,
    ),
  ),
));



/* Data Predefine */
$xmlData = array(
  'editRoom' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => $user['userName'],
    ),
    'errStr' => $errStr,
    'errDesc' => $errDesc,
    'response' => array(),
  ),
);



/* Plugin Hook Start */
($hook = hook('editRoom_start') ? eval($hook) : '');



/* Start Processing */
switch($request['action']) {
  case 'create':
  $roomLengthLimit = ($config['roomLengthLimit'] ? $config['roomLengthLimit'] : 20);

  if (strlen($request['roomName']) == 0) {
    $errStr = 'noName';
    $errDesc = 'A room name was not supplied.';
  }
  elseif (strlen($request['roomName'] > $roomLengthLimit)) {
    $errStr = 'longName';
    $errDesc = 'The room name specified is too long.';
  }
  else {
    if ($slaveDatabase->getRoom(false,$request['roomName']) !== false) {
      $errStr = 'exists';
      $errDesc = 'The room specified already exists.';
    }
    else {
      //$options = ($_POST['mature'] ? 2 : 0);
      //$bbcode = intval($_POST['bbcode']);

      $database->insert(array(
        'roomName' => $roomName,
        'allowedGroups' => implode(',',$request['allowedGroups']),
        'allowedUsers' => implode(',',$request['$allowedUsers']),
        'moderators' => implode(',',$request['$moderators']),
        'owner' => (int) $user['userId'],
//        'options' => (int) $options,
//        'bbcode' => (int) $bbcode,
        ),"{$sqlPrefix}rooms"
      );

      if ((int) $database->insertId) {
        $xmlData['editRoom']['response']['insertId'] = $database->insertId;
      }
      else {
        $errStr = 'unknown';
        $errDesc = 'Room created failed for unknown reasons.';
      }
    }
  }
  break;

  case 'edit':
  $roomLengthLimit = ($config['roomLengthLimit'] ? $config['roomLengthLimit'] : 20);

  $roomName = substr($_POST['name'],0,$roomLengthLimit); // Limits to x characters.

  $room = $slaveDatabase->getRoom($request['roomId']);

  if (strlen($request['roomName']) == 0) {
    $errStr = 'noName';
    $errDesc = 'A room name was not supplied.';
  }
  elseif (strlen($request['roomName'] > $roomLengthLimit)) {
    $errStr = 'longName';
    $errDesc = 'The room name specified is too long.';
  }
  elseif (hasPermission($room,$user,'admin')) {
    $errStr = 'noPerm';
    $errDesc = 'You do not have permission to edit this room.';
  }
  elseif ($room['settings'] & 4) { // Make sure the room hasn't been deleted.
    $errStr = 'deleted';
    $errDesc = 'The room has been deleted - it can not be edited.';
  }
  else {
    $data = $slaveDatabase->getRoom(false,$request['roomName']);

    if ((count($data) > 0) && $data['roomId'] != $room['roomId']) {
      $errStr = 'exists';
      $errDesc = 'The room name specified already exists.';
    }
    else {
      $listsActive = dbRows("SELECT listId, status FROM {$sqlPrefix}censorBlackWhiteLists WHERE roomId = $room[roomId]",'listId'); // TODO

      if (is_array($listsActive)) {
        if (count($listsActive) > 0) {
          foreach ($listsActive AS $active) {
            $listStatus[$active['listId']] = $active['status'];
          }
        }
      }

      $censorLists = $_POST['censor'];
      foreach($censorLists AS $id => $list) {
        $listsNew[$id] = $list;
      }

      $lists = dbRows("SELECT listId, type FROM {$sqlPrefix}censorLists AS l WHERE options & 2",'listId'); /// TODO

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
          $database->insert(array(
            'roomId' => $room['roomId'],
            'listId' => $list['listId'],
            'status' => 'unblock'
          ),"{$sqlPrefix}censorBlackWhiteLists",array(
            'status' => 'unblock',
          ));
        }
        elseif ($checked == false && $listsNew[$list['listId']]) {
          $database->insert(array(
            'roomId' => $room['roomId'],
            'listId' => $list['listId'],
            'status' => 'block'
          ),"{$sqlPrefix}censorBlackWhiteLists",array(
            'status' => 'block',
          ));
        }
      }

      //$options = ($room['options'] & 1) + ($_POST['mature'] ? 2 : 0) + ($room['options'] & 4) + ($room['options'] & 8) + ($room['options'] & 16);
      //$bbcode = intval($_POST['bbcode']);

      $database->update(array(
          'roomName' => $roomName,
          'allowedGroups' => implode(',',$request['allowedGroups']),
          'allowedUsers' => implode(',',$request['allowedUsers']),
          'moderators' => implode(',',$request['moderators']),
          //'options' => (int) $options,
          //'bbcode' => (int) $_POST['bbcode'],
        ),
        "{$sqlPrefix}rooms",
        array(
          'roomId' => $room['roomId'],
        )
      );
    }
  }
  break;

  case 'private':
  if (strlen($request['userName']) > 0) {
    $user2 = $slaveDatabase->getUser(false,$request['userName']); // Get the user information.
  }
  elseif ($reqest['userId'] > 0) {
    $user2 = $slaveDatabase->getUser($request['userId']); // Get the user information.
  }
  else {
    $errStr = 'baduser';
    $errDesc = 'That user does not exist.';
  }

  if (!$user2) { // No user exists.
    $errStr = 'badUser';
    $errDesc = 'That user does not exist.';
  }
  elseif ($user2['userId'] == $user['userId']) { // Don't allow the user to, well, talk to himself.
    $errStr = 'sameUser';
    $errDesc = 'The user specified is yourself.';
  }
  else {
    $room = dbRows("SELECT * FROM {$sqlPrefix}rooms WHERE (allowedUsers = '$user[userId],$user2[userId]' OR allowedUsers = '$user2[userId],$user[userId]') AND options & 16"); // Query a group that would match the criteria for a private room. // TODO
    if ($room) {
      $xmlData['editRoom']['response']['insertId'] = $room['roomId']; // Already exists; return ID
    }
    else {
      $database->insert(array(
          'roomName' => "Private IM ($user[userName] and $user2[userName])",
          'allowedUsers' => "$user[userId],$user2[userId]",
          'options' => 48,
          'bbcode' => 1,
        ),"{$sqlPrefix}rooms"
      );

      $xmlData['editRoom']['response']['insertId'] = $database->insertId;
    }
  }
  break;

  case 'delete':
  $room = $slaveDatabase->getRoom($request['roomId']);

  if (hasPermission($room,$user,'admin')) {
    if ($room['options'] & 4) {
      $errStr = 'alreadydeleted';
      $errDesc = 'The room is already deleted.';
    }
    else {
      $room['options'] += 4; // options & 4 = deleted

      $database->update(array(
          'options' => (int) $room['options'],
        ),"{$sqlPrefix}rooms",array(
          'roomId' => (int) $room['roomId'],
        )
      );
    }
  }
  else {
    $errStr = 'noPerm';
    $errDesc = 'You are not allowed to undelete this room.';
  }
  break;

  case 'undelete':
  $room = $slaveDatabase->getRoom($request['roomId']);

  if (hasPermission($room,$user,'admin')) {
    if ($room['options'] & 4) {
      $errStr = 'alreadydeleted';
      $errDesc = 'The room is already deleted.';
    }
    else {
      $room['options'] += 4; // options & 4 = deleted

      $database->update(array(
          'options' => (int) $room['options'],
        ),"{$sqlPrefix}rooms",array(
          'roomId' => (int) $room['roomId'],
        )
      );
    }
  }
  else {
    $errStr = 'noPerm';
    $errDesc = 'You are not allowed to undelete this room.';
  }
  break;
}

/* Update Data for Errors */
$xmlData['editRoom']['errStr'] = (string) $errStr;
$xmlData['editRoom']['errDesc'] = (string) $errDesc;



/* Plugin Hook End */
($hook = hook('editRoom_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);



/* Close Database */
dbClose();
?>