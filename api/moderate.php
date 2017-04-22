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
 * Performs a Moderation Action
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2014
 *
 * @param string action
 * @param integer userId
 * @param integer roomId
 */

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('p', array(
    'action' => array(
        'valid' => array(
            'kickUser', 'unkickUser',
            'favRoom', 'unfavRoom',
            'banUser', 'unbanUser',
            'markMessageRead',
        ),
    ),

    'roomId' => array(
        'cast' => 'roomId',
    ),

    'userId' => array(
        'cast' => 'int',
    ),

    'listId' => array(
        'cast' => 'int',
    ),

    'length' => array(
        'cast' => 'int',
    ),

    'quiet' => array(
        'default' => false,
        'cast' => 'bool',
    ),
));



/* Data Predefine */
$xmlData = array(
    'moderate' => array(
        'response' => array(),
    ),
);



/* Start Processing */
if ($request['action'] === 'kickUser' || $request['action'] === 'unkickUser') {
    if (!isset($request['userId']))
        throw new fimError('noUserId');

    elseif (!isset($request['roomId']))
        throw new fimError('noRoomId');

    $kickUser = $database->getUser($request['userId']);
    $room = $database->getRoom($request['roomId']);

    if (!$user->id)
        throw new fimError('badUserId');

    elseif (!$room->id)
        throw new fimError('badRoomId');

    elseif (!($database->hasPermission($user, $room) & ROOM_PERMISSION_MODERATE))
        throw new fimError('noPerm'); // You have to be a mod yourself.


    if ($request['action'] === 'kickUser') {
        if ($request['length'] < 10)
            throw new fimError('tooShortKick', 'The kick length specified is too short.');

        elseif ($database->hasPermission($kickUser, $room) & ROOM_PERMISSION_MODERATE)
            throw new fimError('noKickUser', 'Other room moderators may not be kicked.');

        else {
            $database->kickUser($kickUser->id, $room->id, $request['length']);


            if ($config['kickSendMessage'])
                $database->storeMessage('/me kicked ' . $kickUser->name, '', $user, $room);
        }
    }
    elseif ($request['action'] === 'unkickUser') {
        $database->unkickUser($kickUser->id, $room->id);

        if ($config['unkickSendMessage'])
            $database->storeMessage('/me unkicked ' . $kickUser->name, '', $user, $room);
    }
    else {
        throw new Exception('Defensive login error.');
    }
}

else {
    new fimError('badAction');
}


/* Output Data */
echo new apiData($xmlData);
?>