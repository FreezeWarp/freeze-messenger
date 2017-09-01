<?php
/* FreezeMessenger Copyright © 2017 Joseph Todd Parsons

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
 * @copyright Joseph T. Parsons 2017
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
    'name' => array(
        'require' => $requestHead['_action'] == 'create',
        'trim' => true,
    ),

    'defaultPermissions' => array(
        'cast' => 'list',
        'transform' => 'bitfield',
        'bitTable' => $permFilterMatches
    ),

    'userPermissions' => array(
        'cast' => 'json',
    ),

    'groupPermissions' => array(
        'cast' => 'json',
    ),

    'censorLists' => array(
        'cast' => 'dict',
        'filter' => 'bool',
        'evaltrue' => false,
    ),

    'parentalAge' => array(
        'cast' => 'int',
        'valid' => $config['parentalAges'],
        'default' => ($requestHead['_action'] === 'create' ? $config['parentalAgeDefault'] : null),
    ),

    'parentalFlags' => array(
        'cast' => 'list',
        'transform' => 'csv',
        'default' => ($requestHead['_action'] === 'create' ? $config['parentalFlagsDefault'] : null),
        'valid' => $config['parentalFlags'],
    ),

    'options' => array(
        'cast' => 'bitfieldEquation',
        'flipTable' => [
            ROOM_HIDDEN => 'hidden',
            ROOM_OFFICIAL => 'official',
        ]
    ),
));

$database->accessLog('editRoom', $request);



/* Data Predefine */
$xmlData = array(
    'response' => array(
        'insertId' => null
    ),
);



if ($request['_action'] !== 'create') {
    $room = $slaveDatabase->getRoom($requestHead['id']);
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
switch($requestHead['_action']) {
    case 'create':
    case 'edit':
        if (strlen($request['name']) < $config['roomLengthMinimum'])
            new fimError('nameMinimumLength', 'The room name specified is too short. It should be at least ' . $config['roomLengthMinimum'] . ' characters.');

        elseif (strlen($request['name']) > $config['roomLengthMaximum'])
            new fimError('nameMaximumLength', 'The room name specified is too short. It should be at most ' . $config['roomLengthMaximum'] . ' characters.');

        else {
            if ($requestHead['_action'] === 'create') {
                if (!$user->hasPriv('createRooms'))
                    new fimError('noPerm', 'You do not have permission to create rooms.');

                elseif (!$user->hasPriv('modRooms') && count($user->ownedRoomIds) >= $config['userRoomMaximum'])
                    new fimError('maximumRooms', 'You have created the maximum number of rooms allowed for a single user.');

                elseif (!$user->hasPriv('modRooms') && ((time() - $user->joinDate / (60 * 60 * 24 * 365)) * count($user->ownedRoomIds)) >= $config['userRoomMaximumPerYear'])
                    new fimError('maximumRooms', 'You have created the maximum number of rooms allowed for the age of your account. You may eventually be allowed to create additional rooms.');

                elseif ($slaveDatabase->getRooms(array('roomNames' => array($request['name'])))->getCount() > 0)
                    new fimError('roomNameTaken', 'A room with the name specified already exists.');

                else
                    $room = new fimRoom(false);
            }

            elseif ($requestHead['_action'] === 'edit') {
                if ($room->type !== 'general')
                    new fimError('specialRoom', 'You are trying to edit a special room, which cannot be altered.');

                elseif ($room->deleted) // Make sure the room hasn't been deleted.
                    new fimError('deletedRoom', 'The room has been deleted - it can not be edited.');

                // TODO: only admins may change room name

                elseif ($data = $slaveDatabase->getRooms(array('roomNames' => array($request['name'])))->getAsArray(false)
                    && count($data)
                    && $data['roomId'] !== $room['roomId'])
                    new fimError('roomNameTaken', 'The room name specified already belongs to another room.');
            }


            if ($requestHead['_action'] === 'create' ||
                ($database->hasPermission($user, $room) & ROOM_PERMISSION_PROPERTIES)) {
                $room->setDatabase([
                    'roomName' => $request['name'],
                    'roomParentalFlags' => $request['parentalFlags'],
                    'roomParentalAge' => $request['parentalAge'],
                    'defaultPermissions' => $request['defaultPermissions'],
                    'options' => $database->type('equation', $request['options']),
                ], false);
                $database->setCensorLists($room->id, $request['censorLists']);
            }

            if ($requestHead['_action'] === 'create' ||
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