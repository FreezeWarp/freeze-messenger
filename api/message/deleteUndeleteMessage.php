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
   along withis program.  If not, see <http://www.gnu.org/licenses/>. */

/**
 * Delete or undelete a message.
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 */


/* Prevent Direct Access of File */
if (!defined('API_INMESSAGE'))
    die();


if (($message->user->id = $user->id && $user->hasPriv('editOwnPosts'))
    || ($database->hasPermission($user, $room) & fimRoom::ROOM_PERMISSION_MODERATE)) {

    if ($requestHead['_action'] == 'delete')
        $message->setDeleted(true);
    else
        $message->setDeleted(false);

    $database->updateMessage($message);
}

else
    new fimError('noPerm', 'You are not allowed to delete this message.');

echo new Http\ApiData([
    'message' => [
        'id'     => $message->id,
    ],
]);