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

require_once('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC(array(
  'post' => array(
    'action' => array(
      'type' => 'string',
      'valid' => array(
        'create',
        'edit',
        'delete',
        'private',
        'contact', // FIMv4
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

    'defaultPermissions' => array(
      'type' => 'string',
      'require' => false,
      'default' => 0,
      'context' => array(
        'type' => 'int',
      ),
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

    'otr' => array( // This will be used in v4.
      'type' => 'string',
      'require' => false,
      'default' => false,
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
  if (!$user['userDefs']['createRooms']) {
    $errStr = 'noPerm';
    $errDesc = 'You do not have permission to create rooms.';
  }
  else {
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
      if ($slaveDatabase->getRoom(false,$request['roomName']) !== false) {
        $errStr = 'exists';
        $errDesc = 'The room specified already exists.';
      }
      else {
        $database->insert(array(
          'roomName' => $request['roomName'],
          'owner' => (int) $user['userId'],
          ),"{$sqlPrefix}rooms"
        );
        $roomId = $database->insertId;

        if ((int) $roomId) {
          foreach ($request['allowedUsers'] AS &$allowedUser) {
            if (in_array($allowedUser,$request['moderators'])) {
              unset($allowedUser);
            }
            else {
              $database->insert(
                array(
                  'roomId' => $roomId,
                  'attribute' => 'user',
                  'param' => $allowedUser,
                  'permissions' => 7,
                ),
                "{$sqlPrefix}roomPermissions",
                array(
                  'permissions' => 7,
                )
              );
            }
          }

          foreach ($request['allowedGroups'] AS &$allowedGroup) {
            $database->insert(
              array(
                'roomId' => $roomId,
                'attribute' => 'group',
                'param' => $allowedGroup,
                'permissions' => 7,
              ),
              "{$sqlPrefix}roomPermissions",
              array(
                'permissions' => 7,
              )
            );
          }

          foreach ($request['moderators'] AS &$moderator) {
            $database->insert(
              array(
                'roomId' => $roomId,
                'attribute' => 'user',
                'param' => $moderator,
                'permissions' => 15,
              ),
              "{$sqlPrefix}roomPermissions",
              array(
                'permissions' => 15,
              )
            );
          }

          $xmlData['editRoom']['response']['insertId'] = $roomId;
        }
        else {
          $errStr = 'unknown';
          $errDesc = 'Room created failed for unknown reasons.';
        }
      }
    }
  }
  break;

  case 'edit':
  $room = $slaveDatabase->getRoom($request['roomId']);

  if ($room === false) {
    $errStr = 'noRoom';
    $errDesc = 'The room specified does not exist.';
  }
  elseif (strlen($request['roomName']) == 0) { // The name must exist :P
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
  elseif (!fim_hasPermission($room, $user, 'admin', true)) { // The user must be an admin (or, inherently, the room's owner) to edit rooms.
    $errStr = 'noPerm';
    $errDesc = 'You do not have permission to edit this room.';
  }
  elseif ($room['settings'] & 4) { // Make sure the room hasn't been deleted.
    $errStr = 'deleted';
    $errDesc = 'The room has been deleted - it can not be edited.';
  }
  else {
    $data = $slaveDatabase->getRoom(false,$request['roomName']);

    if ($data !== false && $data['roomId'] != $room['roomId']) { // Make sure no other room with that name exists (if no room is found, the result is false), and that, of course, this only applies if the user just specified the current room's existing name.
      $errStr = 'exists';
      $errDesc = 'The room name specified already exists.';
    }
    else {
//      $listsActive = dbRows("SELECT listId, status FROM {$sqlPrefix}censorBlackWhiteLists WHERE roomId = $room[roomId]",'listId'); // TODO
      $lists = $slaveDatabase->select(
        array(
          "{$sqlPrefix}censorLists" => array(
            'listId' => 'listId',
            'listName' => 'listName',
            'listType' => 'listType',
            'options' => 'options',
          ),
        ),
        array(
          'both' => array(
            array(
              'type' => 'bitwise',
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

      $listsActive = $slaveDatabase->select(
        array(
          "{$sqlPrefix}censorBlackWhiteLists" => array(
            'status' => 'status',
            'roomId' => 'roomId',
            'listId' => 'listId',
          ),
        ),
        array(
          'both' => array(
            array(
              'type' => 'e',
              'left' => array(
                'type' => 'column',
                'value' => 'roomId',
              ),
              'right' => array(
                'type' => 'int',
                'value' => (int) $room['roomId'],
              ),
            ),
          ),
        )
      );
      $listsActive = $listsActive->getAsArray(true);

      if (is_array($listsActive)) {
        if (count($listsActive) > 0) {
          foreach ($listsActive AS $active) {
            $listStatus[$active['listId']] = $active['status'];
          }
        }
      }

      $censorLists = $request['censor']; // TODO
      foreach($censorLists AS $id => $list) {
        $listsNew[$id] = $list;
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

      $database->update(array(
          'roomName' => $request['roomName'],
          'allowedGroups' => implode(',',$request['allowedGroups']),
          'allowedUsers' => implode(',',$request['allowedUsers']),
          'moderators' => implode(',',$request['moderators']),
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
  if (!$user['userDefs']['privateRooms']) {
    $errStr = 'noPerm';
    $errDesc = 'You do not have permission to create private rooms.';
  }
  else {
    if (strlen($request['userName']) > 0) {
      $user2 = $slaveDatabase->getUser(false,$request['userName']); // Get the user information.
    }
    elseif ((int) $reqest['userId'] > 0) {
      $user2 = $slaveDatabase->getUser($request['userId']); // Get the user information.
    }
    else {
      $errStr = 'noUser';
      $errDesc = 'You did not specify a user.';
    }

    if ($user2 === false) { // No user exists.
      $errStr = 'badUser';
      $errDesc = 'That user does not exist.';
    }
    elseif ($user2['userId'] == $user['userId']) { // Don't allow the user to, well, talk to himself.
      $errStr = 'sameUser';
      $errDesc = 'The user specified is yourself.';
    }
    else {
      $room = $database->select(
        array(
          "{$sqlPrefix}rooms" => 'roomId, roomName, allowedUsers',
        ),
        array(
          'both' => array(
            array(
              'type' => 'in',
              'left' => array(
                'type' => 'int',
                'value' => $user['userId'],
              ),
              'right' => array(
                'type' => 'column',
                'value' => 'allowedUsers',
              ),
            ),
            array(
              'type' => 'in',
              'left' => array(
                'type' => 'int',
                'value' => $user2['userId'],
              ),
              'right' => array(
                'type' => 'column',
                'value' => 'allowedUsers',
              ),
            ),
          ),
        )
      );
      $room = $room->getAsArray(false);

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

  if (fim_hasPermission($room, $user, 'admin', true)) {
    if ($room['options'] & 4) {
      $errStr = 'nothingToDo';
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