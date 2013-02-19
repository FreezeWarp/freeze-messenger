<?php
/* FreezeMessenger Copyright © 2012 Joseph Todd Parsons

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
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 *
 * =POST Parameters=
 * @param action The action to be performed by the script, either: [[Required.]]
 ** 'create' - Creates a room with the data specified.
 ** 'edit' - Updates a room with the data specified.
 ** 'delete' - Marks a room as deleted. (It's data, messages, and permissions will remain on the server.)
 ** 'undelete' - Unmarks a room as deleted.
 *
 * ==Edit, Delete, and Undelete Parameters==
 * @param int roomId - The ID of the room to be modified, deleted, or undeleted.
 *
 * ==Create and Edit Paramters==
 * @param string roomName - The name the room should be set to. Required when creating a room.
 * @param int defaultPermissions=0 - The default permissions all users are granted to a room.
 * @param csv moderators - A comma-separated list of user IDs who will be allowed to moderate the room.
 * @param csv allowedUsers - A comma-separated list of user IDs who will be allowed access to the room.
 * @param csv allowedGroups - A comma-separated list of group IDs who will be allowed to access the room.
 * @param int parentalAge=$config['parentalAgeDefault'] - The parental age corresponding to the room.
 * @param csv parentalFlag=$config['parentalFlagsDefault'] - A comma-separated list of parental flags that apply to the room.
 *
 * =Errors=
 * @throws noPerm - The user does not have permission to perform the action specified.
 *
 * ==Creating and Editing Rooms==
 * @throws exists - The room name specified collides with an existing room.
 * @throws noName - A valid room name was not specified.
 * @throws shortName - The room name specified was too short.
 * @throws longName - The room name specified was too long.
 * @throws unknown - The action could not proceed for unknown reasons.
 *
 * ==Editing Rooms==
 * @throws noRoom - The room ID specified does not correspond with an existing room.
 * @throws deleted - The room specified has been deleted, and thus can not be edited.
 *
 * ==Deleting and Undeleting Rooms==
 * @throws nothingToDo - The room is already deleted or undeleted.
 *
 * =Response=
 * @return APIOBJ:
 ** editRoom
 *** activeUser
 **** userId
 **** userName
 *** errStr
 *** errDesc
 *** response
 **** insertId - If creating a room, the ID of the created room.
*/

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('p', array(
  'action' => array(
    'valid' => array(
      'create', 'edit',
      'delete', 'undelete'
    ),
    'require' => true,
  ),

  'roomId' => array(
    'cast' => 'int',
  ),

  'roomName' => array(
    'require' => false,
    'trim' => true,
  ),

  'defaultPermissions' => array(
    'default' => 0,
    'cast' => 'int',
  ),

  'moderators' => array(
    'cast' => 'csv',
    'filter' => 'int',
    'evaltrue' => true,
  ),

  'allowedUsers' => array(
    'cast' => 'csv',
    'filter' => 'int',
    'evaltrue' => true,
  ),

  'allowedGroups' => array(
    'cast' => 'csv',
    'filter' => 'int',
    'evaltrue' => true,
  ),

  'censor' => array(
    'cast' => 'array',
    'filter' => 'bool',
    'evaltrue' => false,
  ),

  'parentalAge' => array(
    'cast' => 'int',
    'valid' => $config['parentalAges'],
    'default' => $config['parentalAgeDefault'],
  ),

  'parentalFlags' => array(
    'default' => $config['parentalFlagsDefault'],
    'cast' => 'csv',
    'valid' => $config['parentalFlags'],
  ),
  
  'allowViewing' => array(
    'cast' => 'bool',
    'default' => false,
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
    if (!$request['roomName']) { $request['roomName'] = $room['roomName']; } // If only a user ID was provided, we will fill in the room name here.

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
    elseif ($data !== false && $data['roomId'] !== $room['roomId']) { // Make sure no other room with that name exists (if no room is found, the result is false), and that, of course, this only applies if the user just specified the current room's existing name.
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
      /* Censor */
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
          if ($list['type'] == 'black' && $listStatus[$list['listId']] == 'block') $checked = true;
          elseif ($list['type'] == 'white' && $listStatus[$list['listId']] != 'unblock') $checked = true;
          else $checked = false;

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

      
      /* Options */
      $options = 0;
      
      if ($config['officialRooms'] && $user['adminDefs']['modRooms']) $options += 1;
      if ($config['hiddenRooms'] && $request['hidden']) $options += 8;
      if ($request['allowViewing']) $options += 32;
      
      
      /* Submit */
      if ($request['action'] === 'create') {
        if ($database->insert("{$sqlPrefix}rooms", array(
          'roomName' => $request['roomName'],
          'owner' => (int) $user['userId'],
          'defaultPermissions' => (int) $request['defaultPermissions'],
          'parentalAge' => $request['parentalAge'],
          'parentalFlags' => implode(',', $request['parentalFlags']),
          'options' => $options,
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
            'options' => $options,
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
    }
  }
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