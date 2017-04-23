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
 * Sets a User's Activity Status
 *
 * @param roomIds - A list of room IDs to update a user's status to.
 * @param status - While this can be set, it can not currently be retrieved (and other support may be missing). It should be one of "away," "busy," "available," "invisible," and "offline." The former three are primarily cosmetic; "invisible" indicates that a user will only appear as an active user in the active users list of a private room (and thus is not shown in general room active users lists, or the global active users list), and offline indicates that a user is logging off or exiting a room. If sent, a user's previous status (whatever it is) will be removed.
 * @param typing - Whether a user is typing. In practice, this should only be called for a single room, though we don't necessarily enforce the change. (If used with "offline", it is discarded. If used with any other status, it will be exposed in the active users list unless a user is invisible and in a non-private room.)
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2014
 */

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('p', array(
    'roomIds' => array(
        'cast' => 'list',
        'filter' => 'roomId',
        'require' => true,
    ),

    'status' => array(
        'valid' => array('away', 'busy', 'available', 'invisible', 'offline')
    ),

    'typing' => array(
        'cast' => 'bool',
    )
));
$database->accessLog('editUserStatus', $request);



/* Get Room Data */
foreach ($request['roomIds'] AS $roomId) {
    $room = new fimRoom($roomId);

    if ($database->hasPermission($user, $room) & ROOM_PERMISSION_VIEW)
        $database->setUserStatus($room->id, $request['status'],  $request['typing']);
}


$xmlData = array(
    'response' => array(),
);



/* Output Data */
echo new apiData($xmlData);
?>