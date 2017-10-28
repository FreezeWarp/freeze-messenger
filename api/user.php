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
    if ($requestHead['_action'] === 'create') // ID shouldn't be used here.
        new fimError('idExtra', 'Parameter ID should not be used with PUT requests.');

    try {
        $userData = $database->getUser($requestHead['id']);
    } catch (Exception $ex) {
        new fimError('idNoExist', 'The given "id" parameter does not correspond with a real user.');
    }
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