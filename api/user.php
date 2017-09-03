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
 * Represents a "user" object in some context, performing user creations and user retrievals:
 ** When used with a GET request, this will retrieve users. If a user ID is provided, it will retrieve a single user, otherwise it will retrieve users who match certain parameters. Unlike GET message.php and GET room.php, it is not possible to page through users, as such functionality should not be necessary. api/acHelper.php is available to search users by username.
 ** When used with a PUT request, this will create a new user.
 *
 * To edit a user's options, use api/editUserOptions.php.
 *
 * =Directives=
 * ==Common Directives (must be in URL parameters):==
 *
 * @param int         $id        The user's ID.
 *
 * ==Create User Directives==
 * @param string     $userName  The user's name. Required.
 * @param string     $password  The user's password. Required.
 * @param string     $email     The email of the user. Possibly required; use getServerStatus to find out.
 * @param int        $birthDate The date-of-birth of the user as a unix timestamp.  Possibly required; use getServerStatus to find out.
 *
 * ==Get User Directives==
 * @param list       $userIds   A list of user IDs to filter by.
 * @param list       $userNames A list of user names to filter by.
 * @param string     $sort      How to sort the users, either by id or name. Default is id.
 * @param list       $info      {
 *      Which fields to include. Valid options are:
 *
 *      @param string "profile" User profile information: avatar, nameFormat, messageFormatting, joinDate
 *      @param string "groups"  User group information: mainGroupId and socialGroupIds
 *      @param string "self"    User's own information: defaultRoomId, options, parentalAge, parentalFlags, ignoredUsers, friendedUsers, favRooms, and watchRooms. Only applies to the logged-in user.
 * }
 * @param string     $showOnly  {
 *      A specific filter to apply to users. Options are:
 *
 *      @param string "banned"    Only include banned users. Only allowed if the user is allowed to administer users.
 *      @param string "!banned"   Only include unbanned users. Only allowed if the user is allowed to administer users.
 *      @param string "friends"   Only include users the user is friends with.
 *      @param string "ignored"   Only include users the user is ignoring.
 *
 *      Prepending a bang ("!") to any value will reverse the filter - thus, "!banned" will only show users who have not been banned. It is possible to apply multiple filters by comma-separating values.
 * }
 *
 *
 * =Exceptions=
 * ==Get User Exceptions==
 * idUserIdsUserNamesConflict
 * ==Create User Exceptions==
 *
 * @throws notSupported       If trying to create a user when the vanilla login subsystem is not enabled.
 * @throws loggedIn           If a session token was provided.
 * @throws emailInvalid       If the specified email is invalid.
 * @throws ageMinimum         If the specified birthdate is too young.
 * @throws userNameTaken      If the specified userName is already in use.
 * @throws userCreationFailed If the user creation failed for unknown reasons.
 *
 *
 * =Todo=
 * @todo showOnly
 * @todo getUser params
 */


/* Header parameters -- identifies what we're doing as well as the message itself, if applicable. */
require('../functions/fim_general.php');
$requestHead = fim_sanitizeGPC('g', [
    'id'      => ['cast' => 'int'],
    '_action' => [],
]);


/* If we're creating, make sure that a lack of login isn't a problem. */
if ($requestHead['_action'] == 'create')
    $ignoreLogin = true;


/* Normal API Setup */
$apiRequest = true;
require('../global.php');
define('API_INUSER', true);


/* Early Validation */
if (isset($requestHead['id'])) {
    if ($requestHead['action'] == 'create') // ID shouldn't be used here.
        new fimError('idExtra', 'Parameter ID should not be used with PUT requests.');

    if (!$userData = $database->getUser($requestHead['id']))
        new fimError('idNoExist', 'The given "id" parameter does not correspond with a real user.');
}


/* Load the correct file to perform the action */
switch ($requestHead['_action']) {
    case 'create':
        require('user/sendUser.php');
    break;

    case 'get':
        require('user/getUsers.php');
    break;
}