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
 * @package   fim3
 * @version   3.0
 * @author    Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 */


if (!defined('API_INROOM'))
    die();


/* Helper Functions */
/**
 * Generates a permissions bitfield from a list of permission strings.
 *
 * @param $permissionsArray array List of strings corresponding with permissions in {@link fimRoom::$permArray}.
 */
function getPermissionsField($permissionsArray)
{
    $permissionsField = 0;

    foreach (fimRoom::$permArray AS $string => $byte) {
        if (in_array($string, $permissionsArray)) $permissionsField |= $byte;
    }

    return $permissionsField;
}

/**
 * Alters a room's permissions based on a specially formatted userArray and groupArray. This function does not check for permissions -- make sure that a user has permission to alter permissions before executing this function.
 *
 *
 * @param $roomId
 * @param $userArray
 * @param $groupArray
 */
function alterRoomPermissions($roomId, $userArray, $groupArray)
{
    global $database;

    foreach (['user' => $userArray, 'group' => $groupArray] AS $attribute => $array) {
        foreach ((array)$array AS $code => $permissionsArray) {
            $operation = substr($code, 0, 1); // The first character of the code is going to be either '+', '-', or '*', representing which action we are taking.
            $param = (int)substr($code, 1); // Everything after the first character represents either a group or user ID.

            $permissionsField = getPermissionsField($permissionsArray);

            if ($attribute === 'user')
                $databasePermissionsField = $database->getPermissionsField($roomId, $param);
            elseif ($attribute === 'group')
                $databasePermissionsField = $database->getPermissionsField($roomId, [], $param);

            if ($databasePermissionsField === -1) $databasePermissionsField = 0;

            switch ($operation) {
                case '+':
                    @$database->setPermission($roomId, $attribute, $param, $databasePermissionsField | $permissionsField);
                break; // Add new permissions to any existing permissions.
                case '-':
                    $database->setPermission($roomId, $attribute, $param, $databasePermissionsField & ~$permissionsField);
                break; // Remove permissions from any existing permissions.
                case '*':
                    $database->setPermission($roomId, $attribute, $param, $permissionsField);
                break; // Replace permissions.
            }
        }
    }
}



/* Get Request Data */
$request = fim_sanitizeGPC('p', [
    'name' => [
        'require' => $requestHead['_action'] == 'create',
        'trim'    => true,
    ],

    'defaultPermissions' => [
        'cast'      => 'list',
        'transform' => 'bitfield',
        'bitTable'  => fimRoom::$permArray
    ],

    'userPermissions' => [
        'cast' => 'dict',
        'filter' => 'array',
        'default' => [],
    ],

    'groupPermissions' => [
        'cast' => 'dict',
        'filter' => 'array',
        'default' => [],
    ],

    'censorLists' => [
        'cast'     => 'dict',
        'filter'   => 'bool',
        'evaltrue' => false,
    ],

    'parentalAge' => [
        'cast'    => 'int',
        'valid'   => fimConfig::$parentalAges,
        'default' => ($requestHead['_action'] === 'create' ? fimConfig::$parentalAgeDefault : null),
    ],

    'parentalFlags' => [
        'cast'      => 'list',
        'transform' => 'csv',
        'default'   => ($requestHead['_action'] === 'create' ? fimConfig::$parentalFlagsDefault : null),
        'valid'     => fimConfig::$parentalFlags,
    ],
]);

$database->accessLog('editRoom', $request);



/* Start Processing */
$database->startTransaction();

switch ($requestHead['_action']) {
    case 'create':
    case 'edit':
        // Handle Room Creation Exceptions
        if ($requestHead['_action'] === 'create') {
            if (strlen($request['name']) < fimConfig::$roomLengthMinimum)
                new fimError('nameMinimumLength', 'The room name specified is too short. It should be at least ' . fimConfig::$roomLengthMinimum . ' characters.');

            elseif (strlen($request['name']) > fimConfig::$roomLengthMaximum)
                new fimError('nameMaximumLength', 'The room name specified is too short. It should be at most ' . fimConfig::$roomLengthMaximum . ' characters.');

            elseif (!$user->hasPriv('createRooms'))
                new fimError('noPerm', 'You do not have permission to create rooms.');

            elseif (!$user->hasPriv('modRooms') && $user->ownedRooms >= fimConfig::$userRoomMaximum)
                new fimError('maximumRooms', 'You have created the maximum number of rooms allowed for a single user.');

            elseif (!$user->hasPriv('modRooms') && ((time() - $user->joinDate / (60 * 60 * 24 * 365)) * $user->ownedRooms) >= fimConfig::$userRoomMaximumPerYear)
                new fimError('maximumRooms', 'You have created the maximum number of rooms allowed for the age of your account. You may eventually be allowed to create additional rooms.');

            elseif ($slaveDatabase->getRooms(['roomNames' => [$request['name']]])->getCount() > 0)
                new fimError('nameTaken', 'A room with the name specified already exists.');

            else
                $room = new fimRoom(false);
        }


        // Handle Room Edit Exceptions
        elseif ($requestHead['_action'] === 'edit') {
            if (isset($request['name'])) {
                if (!$user->hasPriv('modRooms'))
                    new fimError('nameExtra', 'The room\'s name cannot be edited except by administrators.');

                elseif ($request['name'] != $room->name
                    && $slaveDatabase->getRooms(['roomNames' => [$request['name']]])->getCount() > 0)
                    new fimError('nameTaken', 'The room name specified already belongs to another room.');
            }

            elseif ($room->type !== 'general')
                new fimError('specialRoom', 'You are trying to edit a special room, which cannot be altered.');

            elseif ($room->deleted) // Make sure the room hasn't been deleted.
                new fimError('deletedRoom', 'The room has been deleted - it can not be edited.');
        }


        // Handle Options Flags
        if ($user->hasPriv('modRooms')) {
            $request = array_merge($request, fim_sanitizeGPC('p', [
                'options' => [
                    'cast'      => 'bitfieldShift',
                    'source'    => $room->options,
                    'flipTable' => [
                        fimRoom::ROOM_HIDDEN   => 'hidden',
                        fimRoom::ROOM_OFFICIAL => 'official',
                    ]
                ]
            ]));
        }


        // Handle Room Properties
        if ($requestHead['_action'] === 'create' ||
            ($database->hasPermission($user, $room) & fimRoom::ROOM_PERMISSION_PROPERTIES)) {
            $room->setDatabase(array_merge(
                fim_arrayFilterKeys($request, ['name', 'parentalFlags', 'parentalAge', 'defaultPermissions', 'options'])
            ));

            if (isset($request['censorLists']))
                $database->setCensorLists($room->id, $request['censorLists']);
        }


        // Handle Room Grants
        if ($requestHead['_action'] === 'create' ||
            ($database->hasPermission($user, $room) & fimRoom::ROOM_PERMISSION_GRANT)) {
            alterRoomPermissions($room->id, $request['userPermissions'], $request['groupPermissions']);
        }
    break;


    case 'delete':
        if ($room->deleted) new fimError('nothingToDo', 'The room is already deleted.');
        else $room->setDatabase(['deleted' => true]);
    break;

    case 'undelete':
        if (!$room->deleted) new fimError('nothingToDo', 'The room isn\'t deleted.');
        else $room->setDatabase(['deleted' => false]);
    break;
}

$database->endTransaction();


/* Output Data */
$xmlData = ['room' => fim_objectArrayFilterKeys($room, ['id', 'name']), 'request' => $request];
echo new Http\ApiData($xmlData);
?>