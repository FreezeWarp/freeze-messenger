<?php
/* FreezeMessenger Copyright © 2014 Joseph Todd Parsons

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
 * Deletes or Undeletes a Message
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2014
 * @todo - Document.
 */

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('p', array(
    'action' => array(
        'valid' => array(
            'delete',
            'undelete',
            'edit', // FIMv4
        ),
    ),

    'messageId' => array(
        'cast' => 'int',
    ),
));



/* Data Predefine */
$xmlData = array(
    'response' => array(),
);


if (!$messageData = $slaveDatabase->getMessage($request['messageId']))
    new fimError('invalidMessage', 'The message specified is invalid.');

$room = new fimRoom($messageData['roomId']);



/* Start Processing */
switch ($request['action']) {
    case 'delete': case 'undeleted':
        if (($messageData['userId'] = $user->id && !$user->isAnonymousUser() && $generalCache->getConfig('usersCanDeleteOwnPosts'))
            || ($database->hasPermission($user, $room) & ROOM_PERMISSION_MODERATE)) {
            $database->editMessage($messageData['messageId'], array('deleted' => ($request['action'] === 'delete' ? true : false)));
        }
        else
            new fimError('noPerm', 'You are not allowed to delete this message.');
    break;

    case 'edit':
    if ($messageData['userId'] = $user->id && !$user->isAnonymousUser() && $generalCache->getConfig('usersCanEditOwnPosts')) {
        $database->editMessage($messageData['messageId'], array('deleted' => ($request['action'] === 'delete' ? true : false)));
    }
    else

        new fimError('noPerm', 'You are not allowed to delete this message.');
    break;
}


/* Output Data */
echo new apiData($xmlData);
?>