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
 * @param string action - "create", "edit", "delete", "undelete", or "private"
 *
 * Create, Edit Room Parameters:
 * @param int defaultPermissions
 * @param csv allowedUsers
 * @param csv allowedGroups
 * @param csv moderators
 * @param string roomName
 * @param int parentalAge
 * @param csv parentalFlags
 *
 * Edit, Delete, Undelete Room Parameters:
 * @param int roomId
 *
 * Private Room Parameters:
 * @param int userId
 * @param string userName
 *
 * Note for FIMv4: This will add several additional methods ("contact" being the main one) that allow for improved IM-like communication. This will essentially return any stream that has x users involved, and will replace private for the most part (private will instead be used for OTR communication).
*/

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('p', array(
  'action' => array(
    'valid' => array(
      'create', 'edit',
      'delete', 'private',
      'contact', // FIMv4
    ),
    'require' => true,
  ),

  'roomId' => array(
    'context' => 'int',
  ),

  'roomName' => array(
    'require' => false,
  ),

  'defaultPermissions' => array(
    'default' => 0,
    'context' => 'int',
  ),

  'moderators' => array(
    'context' => array(
      'type' => 'csv',
      'filter' => 'int',
      'evaltrue' => true,
    ),
  ),

  'allowedUsers' => array(
    'context' => array(
      'type' => 'csv',
      'filter' => 'int',
      'evaltrue' => true,
    ),
  ),

  'allowedGroups' => array(
    'context' => array(
      'type' => 'csv',
      'filter' => 'int',
      'evaltrue' => true,
    ),
  ),

  'censor' => array(
    'context' => array(
      'type' => 'array',
      'filter' => 'bool',
      'evaltrue' => false,
    ),
  ),

  'parentalAge' => array(
    'context' => 'int',
    'valid' => array(
      6, 10, 13, 16, 18
    ),
    'default' => 6,
  ),

  'parentalFlags' => array(
    'context' => array(
      'type' => 'csv',
      'valid' => array(
        'violence', 'weapons', 'gore',
        'nudity', 'pnudity', 'suggestive',
        'drugs', 'language',
      ),
    ),
  ),

  'otr' => array( // This will be used in v4.
    'default' => false,
    'context' => array(
      'type' => 'bool',
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
  case 'edit':
  if ($request['action'] === 'create') {
    if (!$user['userDefs']['createRooms']) { // Gotta be able to create dem rooms.
      $errStr = 'noPerm';
      $errDesc = 'You do not have permission to create rooms.';
      $continue = false;
    }
    elseif ($slaveDatabase->getRoom(false, $request['roomName']) !== false) { // Make sure no other room exists with the same name.
      $errStr = 'exists';
      $errDesc = 'The room specified already exists.';
      $continue = false;
    }

    $request['roomId'] = 0;
  }
  elseif ($request['action'] === 'edit') {
    $room = $slaveDatabase->getRoom($request['roomId']);
    $data = $slaveDatabase->getRoom(false, $request['roomName']);

    if ($room === false) {
      $errStr = 'noRoom';
      $errDesc = 'The room specified does not exist.';
      $continue = false;
    }
    elseif (!fim_hasPermission($room, $user, 'admin', true)) { // The user must be an admin (or, inherently, the room's owner) to edit rooms.
      $errStr = 'noPerm';
      $errDesc = 'You do not have permission to edit this room.';
      $continue = false;
    }
    elseif ($room['settings'] & 4) { // Make sure the room hasn't been deleted.
      $errStr = 'deleted';
      $errDesc = 'The room has been deleted - it can not be edited.';
      $continue = false;
    }
    elseif ($data !== false && $data['roomId'] != $room['roomId']) { // Make sure no other room with that name exists (if no room is found, the result is false), and that, of course, this only applies if the user just specified the current room's existing name.
      $errStr = 'exists';
      $errDesc = 'The room name specified already exists.';
      $continue = false;
    }
  }
  else { die('Internal Logic Error'); }


  if ($continue) {
    if (strlen($request['roomName']) == 0) {
      $errStr = 'noName';
      $errDesc = 'A room name was not supplied.';
    }
    elseif (strlen($request['roomName']) < $config['roomLengthMinimum']) {
      $errStr = 'shortName';
      $errParam = $config['roomLengthMinimum'];
      $errDesc = 'The room name specified is too short.';
    }
    elseif (strlen($request['roomName']) > $config['roomLengthMaximum']) {
      $errStr = 'longName';
      $errParam = $config['roomLengthMaximum'];
      $errDesc = 'The room name specified is too long.';
    }
    else {
      if (count($request['censor']) > 0) {
        $lists = $slaveDatabase->select(
          array(
            "{$sqlPrefix}censorLists" => 'listId, listName, listType, options'
          ),
          array(
            'both' => array(
              array(
                'type' => 'and',
                'left' => array(
                  'type' => 'column',
                  'value' => 'options',
                ),
                'right' => array(
                  'type' => 'int',
                  'value' => 1,
                ),
              ),
            ),
          )
        );
        $lists = $lists->getAsArray(true);

        $listsActive = $database->getRoomCensorLists($request['roomId']);


        if (is_array($listsActive)) {
          if (count($listsActive) > 0) {
            foreach ($listsActive AS $active) {
              $listStatus[$active['listId']] = $active['status'];
            }
          }
        }

        foreach($request['censor'] AS $listId => $status) {
          $listsNew[$listId] = (bool) $status;
        }

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
            $database->insert("{$sqlPrefix}censorBlackWhiteLists", array(
              'roomId' => $room['roomId'],
              'listId' => $list['listId'],
              'status' => 'unblock'
            ), array(
              'status' => 'unblock',
            ));
          }
          elseif ($checked == false && $listsNew[$list['listId']]) {
            $database->insert("{$sqlPrefix}censorBlackWhiteLists", array(
              'roomId' => $room['roomId'],
              'listId' => $list['listId'],
              'status' => 'block'
            ), array(
              'status' => 'block',
            ));
          }
        }
      }

      if ($request['action'] === 'create') {
        if ($database->insert("{$sqlPrefix}rooms", array(
          'roomName' => $request['roomName'],
          'owner' => (int) $user['userId'],
          'defaultPermissions' => (int) $request['defaultPermissions'],
          'parentalAge' => $request['parentalAge'],
          'parentalFlags' => implode(',', $request['parentalFlags']),
        ))) {
          $roomId = $database->insertId;

          $xmlData['editRoom']['response']['insertId'] = $roomId;
        }
        else {
          $errStr = 'unknown';
          $errDesc = 'Room created failed for unknown reasons.';

          $roomId = 0;
        }
      }
      elseif ($request['action'] === 'edit') {
        if ($database->update("{$sqlPrefix}rooms", array(
            'roomName' => $request['roomName'],
            'defaultPermissions' => (int) $request['defaultPermissions'],
            'parentalAge' => $request['parentalAge'],
            'parentalFlags' => implode(',', $request['parentalFlags']),
          ), array(
            'roomId' => $room['roomId'],
          )
        )) {
          $roomId = $room['roomId'];
        }
        else {
          $errStr = 'unknown';
          $errDesc = 'Room created failed for unknown reasons.';

          $roomId = 0;
        }
      }
      else {
        die('Internal Logic Error');
      }


      if ((int) $roomId) {
        // Clear Existing Permissions
        $database->delete("{$sqlPrefix}roomPermissions", array(
          'roomId' => $roomId,
        ));

        foreach ($request['allowedUsers'] AS &$allowedUser) {
          if (in_array($allowedUser, $request['moderators'])) { // Don't process as an allowed user if the user is to be a modederator as well.
            unset($allowedUser);
          }
          else {
            $database->insert("{$sqlPrefix}roomPermissions", array(
                'roomId' => $roomId,
                'attribute' => 'user',
                'param' => $allowedUser,
                'permissions' => 7,
              ), array(
                'permissions' => 7,
              )
            );
          }
        }

        foreach ($request['allowedGroups'] AS &$allowedGroup) {
          $database->insert("{$sqlPrefix}roomPermissions", array(
              'roomId' => $roomId,
              'attribute' => 'group',
              'param' => $allowedGroup,
              'permissions' => 7,
            ), array(
              'permissions' => 7,
            )
          );
        }

        foreach ($request['moderators'] AS &$moderator) {
          $database->insert("{$sqlPrefix}roomPermissions", array(
              'roomId' => $roomId,
              'attribute' => 'user',
              'param' => $moderator,
              'permissions' => 15,
            ), array(
              'permissions' => 15,
            )
          );
        }
      }
    }
  }
  break;

  case 'private':
  if (!$user['userDefs']['privateRooms']) {
    $errStr = 'noPerm';
    $errDesc = 'You do not have permission to create private rooms.';
  }
  else {
    if (strlen($request['userName']) > 0) {
      $user2 = $slaveDatabase->getUser(false, $request['userName']); // Get the user information.
    }
    elseif ((int) $request['userId'] > 0) {
      $user2 = $slaveDatabase->getUser($request['userId']); // Get the user information.
    }
    else {
      $errStr = 'noUser';
      $errDesc = 'You did not specify a user.';
    }

    if (!isset($user2)) {} // noUser thing above...
    elseif ($user2 === false) { // No user exists.
      $errStr = 'badUser';
      $errDesc = 'That user does not exist.';
    }
    elseif ($user2['userId'] == $user['userId']) { // Don't allow the user to, well, talk to himself.
      $errStr = 'sameUser';
      $errDesc = 'The user specified is yourself.';
    }
    else {
      $user['ignoreList'] = fim_arrayValidate(explode(',', $user2['ignoreList']), 'int', false);

      if (in_array($user['userId'], $user['ignoreList']) ||
        ($user2['options'] & 512)) {
        $errStr = 'ignored';
        $errDesc = 'The user has blocked private contact.';
      }
      else {
        $room = $database->getPrivateRoom(array($user['userId'], $user2['userId']));

        if ($room) {
          $xmlData['editRoom']['response']['insertId'] = $room['roomId']; // Already exists; return ID
        }
        else {
          $database->insert("{$sqlPrefix}rooms", array(
              'roomName' => "Private IM ($user[userName] and $user2[userName])",
              'options' => 48,
              'defaultPermissions' => 0,
            )
          );
          $roomId = $database->insertId;

          $database->insert("{$sqlPrefix}privateRooms", array(
            'roomId' => $roomId,
            'user1' => $user['userId'],
            'user2' => $user2['userId'],
          ));

          foreach (array($user['userId'], $user2['userId']) AS $userId) {
            $database->insert("{$sqlPrefix}roomPermissions", array(
                'roomId' => $roomId,
                'attribute' => 'user',
                'param' => $userId,
                'permissions' => 7,
              ), array(
                'permissions' => 7,
              )
            );
          }

          $xmlData['editRoom']['response']['insertId'] = $roomId;
        }
      }
    }
  }
  break;

  case 'contact':
  // FIMv4
  break;

  case 'delete':
  $room = $slaveDatabase->getRoom($request['roomId']);

  if (fim_hasPermission($room, $user, 'admin', true)) {
    if ($room['options'] & 4) {
      $errStr = 'nothingToDo';
      $errDesc = 'The room is already deleted.';
    }
    else {
      $room['options'] += 4; // options & 4 = deleted

      $database->update("{$sqlPrefix}rooms", array(
          'options' => (int) $room['options'],
        ), array(
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

  if (fim_hasPermission($room, $user, 'admin', true)) {
    if ($room['options'] & 4) {
      $errStr = 'nothingToDo';
      $errDesc = 'The room is already deleted.';
    }
    else {
      $room['options'] += 4; // options & 4 = deleted

      $database->update("{$sqlPrefix}rooms", array(
          'options' => (int) $room['options'],
        ), array(
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
?>