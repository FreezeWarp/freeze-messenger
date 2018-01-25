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
 * @global    $room Room
 * @package   fim3
 * @version   3.0
 * @author    Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 */


use Fim\Error;
use Fim\Room;

if (!defined('API_INROOM'))
    die();


/* Get Request Data */
$request = fim_sanitizeGPC('p', [
    'name' => [
        'require' => $requestHead['_action'] == 'create',
        'trim'    => true,
    ],

    'defaultPermissions' => [
        'cast'      => 'list',
        'transform' => 'bitfield',
        'bitTable'  => Room::$permArray
    ],

    'censorLists' => [
        'cast'     => 'dict',
        'filter'   => 'bool',
        'evaltrue' => false,
    ],

    'parentalAge' => [
        'cast'    => 'int',
        'valid'   => \Fim\Config::$parentalAges,
        'default' => ($requestHead['_action'] === 'create' ? \Fim\Config::$parentalAgeDefault : null),
    ],

    'parentalFlags' => [
        'cast'      => 'list',
        'transform' => 'csv',
        'default'   => ($requestHead['_action'] === 'create' ? \Fim\Config::$parentalFlagsDefault : null),
        'valid'     => \Fim\Config::$parentalFlags,
    ],
]);

\Fim\Database::instance()->accessLog('editRoom', $request);



/* Start Processing */
\Fim\Database::instance()->startTransaction();

switch ($requestHead['_action']) {
    case 'create':
    case 'edit':
        // Handle Room Creation Exceptions
        if ($requestHead['_action'] === 'create') {
            if (strlen($request['name']) < \Fim\Config::$roomLengthMinimum)
                new \Fim\Error('nameMinimumLength', 'The room name specified is too short. It should be at least ' . \Fim\Config::$roomLengthMinimum . ' characters.');

            elseif (strlen($request['name']) > \Fim\Config::$roomLengthMaximum)
                new \Fim\Error('nameMaximumLength', 'The room name specified is too short. It should be at most ' . \Fim\Config::$roomLengthMaximum . ' characters.');

            elseif (!$user->hasPriv('createRooms'))
                new \Fim\Error('noPerm', 'You do not have permission to create rooms.');

            elseif (!$user->hasPriv('modRooms') && $user->ownedRooms >= \Fim\Config::$userRoomMaximum)
                new \Fim\Error('maximumRooms', 'You have created the maximum number of rooms allowed for a single user.');

            elseif (!$user->hasPriv('modRooms') && ((time() - $user->joinDate / (60 * 60 * 24 * 365)) * $user->ownedRooms) >= \Fim\Config::$userRoomMaximumPerYear)
                new \Fim\Error('maximumRooms', 'You have created the maximum number of rooms allowed for the age of your account. You may eventually be allowed to create additional rooms.');

            elseif (\Fim\DatabaseSlave::instance()->getRooms(['roomNames' => [$request['name']]])->getCount() > 0)
                new \Fim\Error('nameTaken', 'A room with the name specified already exists.');

            else
                $room = new Room(false);
        }


        // Handle Room Edit Exceptions
        elseif ($requestHead['_action'] === 'edit') {

            if (isset($request['name'])) {
                if (!$user->hasPriv('modRooms'))
                    new \Fim\Error('nameExtra', 'The room\'s name cannot be edited except by administrators.');

                elseif ($request['name'] != $room->name
                    && \Fim\DatabaseSlave::instance()->getRooms(['roomNames' => [$request['name']]])->getCount() > 0)
                    new \Fim\Error('nameTaken', 'The room name specified already belongs to another room.');
            }

            if ($room->type !== 'general')
                new \Fim\Error('specialRoom', 'You are trying to edit a special room, which cannot be altered.');

            elseif ($room->deleted) // Make sure the room hasn't been deleted.
                new \Fim\Error('deletedRoom', 'The room has been deleted - it can not be edited.');
        }


        // Handle Options Flags
        if ($user->hasPriv('modRooms')) {
            $request = array_merge($request, fim_sanitizeGPC('p', [
                'options' => [
                    'cast'      => 'bitfieldShift',
                    'source'    => ($request['_action'] === 'edit'
                        ? $room->options
                        : 0
                    ),
                    'flipTable' => [
                        Room::ROOM_HIDDEN   => 'hidden',
                        Room::ROOM_OFFICIAL => 'official',
                    ]
                ]
            ]));
        }

        // Handle ownerId when create a room
        if ($requestHead['_action'] === 'create') {
            $request['ownerId'] = $user->id;
        }


        // Handle Room Properties
        if ($requestHead['_action'] === 'create' ||
            (\Fim\Database::instance()->hasPermission($user, $room) & Room::ROOM_PERMISSION_PROPERTIES)) {
            $room->setDatabase(array_merge(
                fim_arrayFilterKeys($request, ['name', 'parentalFlags', 'parentalAge', 'defaultPermissions', 'options', 'ownerId'])
            ));

            if (isset($request['censorLists']))
                \Fim\Database::instance()->setCensorLists($room->id, $request['censorLists']);
        }
    break;


    case 'delete':
        if ($room->deleted) new \Fim\Error('nothingToDo', 'The room is already deleted.');
        else $room->setDatabase(['deleted' => true]);
    break;

    case 'undelete':
        if (!$room->deleted) new \Fim\Error('nothingToDo', 'The room isn\'t deleted.');
        else $room->setDatabase(['deleted' => false]);
    break;
}

\Fim\Database::instance()->endTransaction();


/* Output Data */
$xmlData = ['room' => fim_objectArrayFilterKeys($room, ['id', 'name']), 'request' => $request];
echo new Http\ApiData($xmlData);
?>