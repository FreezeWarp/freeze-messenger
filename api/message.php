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

$apiRequest = true;
require('../global.php');
define('API_INMESSAGE', true);


/* Header parameters -- identifies what we're doing as well as the message itself, if applicable. */
$requestHead = fim_sanitizeGPC('g', [
    'roomId' => ['cast' => 'roomId', 'require' => true],
    'id' => [ 'cast' => 'int' ],
    '_action' => [],
]);

if (!($room = new fimRoom($requestHead['roomId']))->roomExists())
    new fimError('badRoom', 'The specified room does not exist.'); // Room doesn't exist.



/* Load the correct file to perform the action */
switch ($requestHead['_action']) {
    case 'delete':
    case 'undelete':
        $message = $database->getMessage($room, $requestHead['id']);

        if (!$message->id)
            new fimError('invalidMessage', 'The message specified is invalid.');

        else if (($message->user->id = $user->id && $user->hasPriv('editOwnPosts'))
            || ($database->hasPermission($user, $room) & ROOM_PERMISSION_MODERATE)) {

            if ($requestHead['_action'] == 'delete')
                $message->setDeleted(true);
            else
                $message->setDeleted(false);

            $database->updateMessage($message);

        }

        else
            new fimError('noPerm', 'You are not allowed to delete this message.');

        echo new apiData();
    break;

    case 'edit': case 'create':
        require('message/sendMessage.php');
    break;

    case 'get':
        require('message/getMessages.php');
    break;
}

?>