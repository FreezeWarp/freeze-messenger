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
 * Get the Active User's Unread Messages.
 * An "unread message" is created whenever a message is inserted into a room watched by a user, or a private room that that user is a part of, and the user does not appear to be online (according to the ping table/database->getActiveUsers()).
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 */

$apiRequest = true;

require('../global.php');


/* Get Request */
$requestHead = fim_sanitizeGPC('g', [
    '_action' => [],
]);

if ($requestHead['_action'] === 'delete') {
    $requestHead = array_merge($requestHead, fim_sanitizeGPC('g', [
        'roomId' => [
            'cast'    => 'roomId',
            'require' => true
        ]
    ]));
}


/* Make Sure the User is Valid */
if (!$user->isValid() || $user->isAnonymousUser())
    throw new fimError('loginRequired', 'You must be logged in to get your unread messages.');


/* Perform Action */
switch ($requestHead['_action']) {
    case 'get':
        \Fim\Database::instance()->accessLog('getUnreadMessages', []);

        $xmlData = [
            'unreadMessages' => \Fim\Database::instance()->getUnreadMessages()->getAsArray(true)
        ];
    break;

    case 'delete':
        \Fim\Database::instance()->accessLog('markMessageRead', $requestHead);
        \Fim\Database::instance()->markMessageRead($requestHead['roomId'], $user->id);

        $xmlData = [
            'markMessageRead' => []
        ];
    break;
}


/* Output Data */
echo new Http\ApiData($xmlData);
?>