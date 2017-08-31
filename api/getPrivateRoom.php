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
 * Obtain a roomId corresponding with a private room between the provided userIds and, if not included, the active userId.
 * @internal This API, unlike most get*() APIs, will create a new room if one does not alredy exist. This is automatic and can not be controlled.
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 * @param string users - list of userIds (the active user may be omitted).
 *
 * TODO -- Ignore List
 */

$apiRequest = true;

require('../global.php');


/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
    'roomId' => array(
        'cast' => 'roomId',
    ),

    'userIds' => array(
        'default' => [],
        'cast' => 'list',
        'filter' => 'int',
        'evaltrue' => true,
        'removeDuplicates' => true,
    ),

    'otr' => array(
        'default' => false,
        'cast' => 'bool',
    )
));
$database->accessLog('getPrivateRoom', $request);



if (isset($request['roomId'])) {
    $room = new fimRoom($request['roomId']);
}
else {
    if (!in_array($user->id, $request['userIds'])) $request['userIds'][] = $user->id; // The active user is automatically added if not specified. This is to say, this API can _not_ be used to obtain a private room that doesn't involve a user (for administrative purposes, for instance) -- getMessages.php can be called directly with the relevant roomId, however, if an admin is allowed to view private rooms.

    $room = new fimRoom(($request['otr'] ? 'o' : 'p') . implode(',', $request['userIds']));
}


/* Data PreDefine */
$xmlData = ['room'];


if (!$room->isPrivateRoom())
    new fimError('logicError', 'A logic error has occurred.');

elseif (!$database->hasPermission($user, $room))
    new fimError('noPerm', 'You do not have permission.');

else {
    $xmlData = ['room' => [
        'type' => $room->type,
        'roomId' => $room->id,
        'roomName' => $room->name,
    ]];
}



/* Output Data Structure */
echo new apiData($xmlData);
?>