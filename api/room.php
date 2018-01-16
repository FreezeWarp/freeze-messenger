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
 * Represents a "room" object in some context, performing room creations, room edits, and room retrievals:
 ** When used with a GET request, this will retrieve rooms. If a room ID is provided, it will retrieve a single room, otherwise it will retrieve rooms who match certain parameters. api/acHelper.php is available to search rooms by roomName.
 ** When used with a PUT request, this will create a new room.
 ** When used with a POST request, this will edit an existing room.
 ** When used with a DELETE request, this will mark the room as deleted.
 ** When used with an UNDELETE request, this will unmark the room as deleted.
 *
 * =Common Directives (must be in URL parameters)=
 *
 * @param int    $id                 The room's ID.
 *
 * =Get Rooms Directives=
 * @param bool   $showDeleted        Will include deleted rooms in results, assuming the user has access to them (that is, is an administrator). Default false.
 * @param enum   $sort               How the rooms should be ordered (either roomId or roomName). Default roomId.
 * @param list   $roomIds            Narrows the result to these specific roomIds. Cannot be used with id
 * @param list   $roomNames          Narrows the result to these specific roomNames. Cannot be used with id.
 * @param string $roomNameSearch     Narrows the result to room names containing this phrase. Cannot be used with id.
 * @param string $permFilter         {
 *      Narrows the result to rooms the user has this level of access to. Options:
 *
 *      @param string "view"       You may view. Default.
 *      @param string "post"       You may post.
 *      @param string "topic"      You may change the room's topic.
 *      @param string "moderate"   You may moderate the room, such as by deleting other users' messages.
 *      @param string "properties" You may alter room properties, such as allowed users and moderators.
 *      @param string "grant"      You may alter room properties, such as allowed users and moderators.
 *      @param string "own"        You own/created the room.
 * }
 *
 * =Edit/Create Room Directives=
 * @param string $name               The name the room should be set to. Required when creating a room
 * @param list $defaultPermissions   {
 *      A list of the default permissions all users are granted in the room. Options:
 *
 *      @param string "view"       Users may view.
 *      @param string "post"       Users may post.
 *      @param string "topic"      Users may change the room's topic.
 *      @param string "moderate"   Users may moderate the room, such as by deleting other users' messages. Should typically not be supported by clients.
 *      @param string "properties" Users may alter room properties, such as allowed users and moderators. Should typically not be supported by clients.
 *      @param string "grant"      Users may alter room properties, such as allowed users and moderators. Should typically not be supported by clients.
 * }
 * @param json   $userPermissions    A special JSON representation of allowed users. It should be an object containing properties such that the name of the property is either "+ID", "-ID", or "*ID", where ID is the ID of a user, and the value of the property is the list of permissions to add (if "+ID"), remove (if "-ID"), or replace with "*ID".
 * @param json   $groupPermissions   A special JSON representation of allowed groups, in the same format as userPermissions.
 * @param dict   $censorLists        A list of censor lists to enable or disable. The index of the dictionary should be the list ID, while the value should be boolean (true to enable, false to disable). If a list is not included, it's state will not be changed. Active lists can be found through getCensorLists.php.
 * @param int    $parentalAge        The parental age corresponding to the room. This will default to a site-configured value. Possibilities can be fetched with serverStatus.php.
 * @param list   $parentalFlag       A list of parental flags that will apply to the room. This will default to a site-configured value. Possibilities can be fetched with serverStatus.php.
 * @param list   $options            {
 *     A list of special options to apply to the room. Options:
 *
 *     @param string "hidden"   The room should be hidden in search results.
 *     @param string "official" The room should be promoted in search results.
 * }
 *
 * =Errors=
 * ==Creating Rooms==
 *
 * @throws maximumRooms The logged in user is not allowed to create any more rooms. In general, they will be allowed to create more after time passes; deleting rooms will not lower their threshold.
 *
 * ==Editing Rooms=
 * @throws nameExtra         The room name can only be editted by administrators.
 * @throws specialRoom       The given room may not be edited.
 * @throws deletedRoom       The given room may not be edited until it is undeleted.
 *
 * ==Editing and Creating Rooms==
 * @throws nameMinimumLength The room name specified was too short.
 * @throws nameMaximumLength The room name specified was too long.
 * @throws nameTaken         The room name is already in use by another room.
 *
 * ==Deleting and Undeleting Rooms==
 * @throws nothingToDo - The room is already deleted or undeleted.
 */

/* Common Resources */

use Fim\Error;
use Fim\Room;

$apiRequest = true;
require(__DIR__ . '/../global.php');
define('API_INROOM', true);


/* Header parameters -- identifies what we're doing as well as the message itself, if applicable. */
$requestHead = fim_sanitizeGPC('g', [
    '_action' => [],
]);
$requestHead = array_merge($requestHead, (array)fim_sanitizeGPC('g', [
    'id' => [
        'cast' => 'roomId',
        'require' => $requestHead['_action'] == 'edit'
    ],
]));


/* Early Validation */
if (isset($requestHead['id'])) {
    if ($requestHead['_action'] === 'create') // ID shouldn't be used here.
        new \Fim\Error('idExtra', 'Parameter ID should not be used with PUT requests.');

    try {
        $room = \Fim\Database::instance()->getRoom($requestHead['id']);

        if (!(\Fim\Database::instance()->hasPermission($user, $room) & Room::ROOM_PERMISSION_VIEW)) {
            new \Fim\Error('idNoExist', 'The given "id" parameter does not correspond with a real room.');
        }
    } catch (Exception $ex) {
        new \Fim\Error('idNoExist', 'The given "id" parameter does not correspond with a real room.');
    }
}


/* Load the correct file to perform the action */
switch ($requestHead['_action']) {
    case 'create':
    case 'edit':
    case 'delete':
    case 'undelete':
        require(__DIR__ . '/room/editRoom.php');
    break;

    case 'get':
        require(__DIR__ . '/room/getRooms.php');
    break;
}