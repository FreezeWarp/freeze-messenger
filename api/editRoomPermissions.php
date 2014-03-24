<?php
/* FreezeMessenger Copyright © 2014 Joseph Todd Parsons

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
 * @copyright Joseph T. Parsons 2014
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
 ** editRoomPermissions
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
      'add', 'remove',
      'replace',
    ),
    'require' => true,
  ),

  'roomId' => array(
    'cast' => 'int',
  ),

  'entries' => array(
    'cast' => 'csv',
    'filter' => 'int',
    'evaltrue' => true,
  ),
));



/* Data Predefine */
$xmlData = array(
  'editRoomPermissions' => array(
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
($hook = hook('editRoomPermissions_start') ? eval($hook) : '');



if (hasPermission()) {
  switch ($request['action']) {
      if ((int) $roomId) {
        // Clear Existing Permissions
        $database->delete("{$sqlPrefix}roomPermissions", array(
          'roomId' => $roomId,
        ));

        foreach ($request['allowedUsers'] AS &$allowedUser) {
          if (in_array($allowedUser, $request['moderators'])) { // Don't process as an allowed user if the user is to be a moderator as well.
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
      


/* Update Data for Errors */
$xmlData['editRoomPermissions']['errStr'] = (string) $errStr;
$xmlData['editRoomPermissions']['errDesc'] = (string) $errDesc;



/* Plugin Hook End */
($hook = hook('editRoomPermissions_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);

?>