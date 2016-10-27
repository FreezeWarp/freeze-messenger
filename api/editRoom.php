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


/* Special Functions */
$permFilterMatches = array(
    'post' => ROOM_PERMISSION_POST,
    'view' => ROOM_PERMISSION_VIEW,
    'topic' => ROOM_PERMISSION_TOPIC,
    'moderate' => ROOM_PERMISSION_MODERATE,
    'properties' => ROOM_PERMISSION_PROPERTIES,
    'grant' => ROOM_PERMISSION_GRANT,
    'own' => ROOM_PERMISSION_VIEW
);



/* Helper Functions */
function getPermissionsField($permissionsArray) {
    global $permFilterMatches;

    $permissionsField = 0;

    foreach ($permFilterMatches AS $string => $byte) {
        if (in_array($string, $permissionsArray)) $permissionsField |= $byte;
    }
}

/**
 * Alters a room's permissions based on a specially formatted userArray and groupArray. This function does not check for permissions -- make sure that a user has permission to alter permissions before executing this function.
 *
 *
 * @param $roomId
 * @param $userArray
 * @param $groupArray
 */
function alterRoomPermissions($roomId, $userArray, $groupArray) {
    global $database;

    foreach (array('users' => $userArray, 'groups' => $groupArray) AS $attribute => $array) {
        foreach ((array) $array AS $code => $permissionsArray) {
            $operation = substr($code, 0, 1); // The first character of the code is going to be either '+', '-', or '*', representing which action we are taking.
            $param = (int) substr($code, 1); // Everything after the first character represents either a group or user ID.

            $permissionsField = getPermissionsField($permissionsArray);

            if ($attribute === 'users') $databasePermissionsField = $database->getPermissionsField($roomId, $param);
            elseif ($attribute === 'groups') $databasePermissionsField = $database->getPermissionsField($roomId, array(), $param);

            switch ($operation) {
                case '+': $database->setPermission($roomId, $attribute, $param, $databasePermissionsField | $permissionsField); break; // Add new permissions to any existing permissions.
                case '-': $database->setPermission($roomId, $attribute, $param, $databasePermissionsField &~ $permissionsField); break; // Remove permissions from any existing permissions.
                case '*': $database->setPermission($roomId, $attribute, $param, $permissionsField); break; // Replace permissions.
            }
        }
    }
}



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
        'cast' => 'list',
    ),

    'userPermissions' => array(
        'cast' => 'json',
    ),

    'groupPermissions' => array(
        'cast' => 'json',
    ),

    'censor' => array(
        'cast' => 'list',
        'filter' => 'bool',
        'evaltrue' => false,
    ),

    'roomParentalAge' => array(
        'cast' => 'int',
        'valid' => $config['parentalAges'],
        'default' => $config['parentalAgeDefault'],
    ),

    'roomParentalFlags' => array(
        'default' => $config['parentalFlagsDefault'],
        'cast' => 'list',
        'valid' => $config['parentalFlags'],
    ),
));

$requestDB = $request;
unset($requestDB['action']);



/* Data Predefine */
$xmlData = array(
    'response' => array(
        'insertId' => null
    ),
);



if ($action !== 'create') {
    $room = $slaveDatabase->getRoom($request['roomId']);
}



/* Start Processing */
if (isset($request['defaultPermissions'])) {
    $permissionsField = 0;

    foreach ($request['defaultPermissions'] AS $priv) {
        $permissionsField &= $permFilterMatches[$priv];
    }

    $request['defaultPermissions'] = $permissionsField;
}


$database->startTransaction();
switch($request['action']) {
    case 'create':
    case 'edit':
        if (strlen($request['roomName']) == 0)
            new fimError('noName', 'A room name was not supplied.');

        elseif (strlen($request['roomName']) < $config['roomLengthMinimum'])
            new fimError('shortName', 'The room name specified is too short. It should be at least ' . $config['roomLengthMinimum'] . ' characters.');

        elseif (strlen($request['roomName']) > $config['roomLengthMaximum'])
            new fimError('longName', 'The room name specified is too short. It should be at most ' . $config['roomLengthMaximum'] . ' characters.');

        else {
            if ($request['action'] === 'create') {
                if (!$user->hasPriv('createRooms'))
                    new fimError('noPermCreate', 'You do not have permission to create rooms.');

                elseif ($slaveDatabase->getRooms(array('roomNames' => array($request['roomName'])))->getCount() > 0)
                    new fimError('roomExists', 'A room with the name specified already exists.');

                else {
                    $room = new fimRoom(false);
                }
            }

            elseif ($request['action'] === 'edit') {
                if ($room === false)
                    new fimError('roomNotFound', "A room with the specified roomId, {$request['roomId']} does not exist.");

                elseif ($room['roomType'] !== 'general')
                    new fimError('specialRoom', 'You are trying to edit a special room, which cannot be altered.');

                elseif ($room->deleted) // Make sure the room hasn't been deleted.
                    new fimError('deletedRoom', 'The room has been deleted - it can not be edited.');

                elseif ($data = $slaveDatabase->getRooms(array('roomNames' => array($request['roomName'])))->getAsArray(false)
                    && count($data)
                    && $data['roomId'] !== $room['roomId'])
                    new fimError('duplicateRoomName', 'The room name specified already belongs to another room.');
            }


            if ($request['action'] === 'create' ||
                ($database->hasPermission($user, $room) & ROOM_PERMISSION_PROPERTIES)) {
                $room->setDatabase($requestDB);
                $database->setCensorLists($room->id, $request['censorLists']);
            }

            if ($request['action'] === 'create' ||
                ($database->hasPermission($user, $room) & ROOM_PERMISSION_GRANT)) {
                alterRoomPermissions($room->id, $request['userPermissions'], $request['groupPermissions']);
            }

            $xmlData['response']['insertId'] = $room->id;
        }
    break;


    case 'delete':
        if ($room->deleted) new fimError('nothingToDo', 'The room is already deleted.');
        else $room->setDatabase(array('deleted' => true));
    break;

    case 'undelete':
        if (!$room->deleted) new fimError('nothingToDo', 'The room isn\'t deleted.');
        else $room->setDatabase(array('deleted' => false));
    break;
}
$database->endTransaction();


/* Output Data */
echo new apiData($xmlData);
?>