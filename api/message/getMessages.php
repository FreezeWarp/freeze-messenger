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
 * Get Messages from the Server
 * Works with both private and normal rooms.
 *
 * @package   fim3
 * @version   3.0
 * @author    Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 */


/* Prevent Direct Access of File */

use Fim\Error;
use Fim\Room;

if (!defined('API_INMESSAGE'))
    die();


/* Get Request Data */
$request = \Fim\Utilities::sanitizeGPC('g', [
    'userIds' => [
        'default'  => [],
        'cast'     => 'list',
        'filter'   => 'int',
        'evaltrue' => true,
        'max'      => 10,
    ],

    'showDeleted' => [
        'default' => false,
        'cast'    => 'bool',
    ],

    'messageDateMax' => [
        'conflict' => ['id', 'messageDateMin', 'messageIdStart', 'messageIdEnd'],
        'min'      => 0,
        'cast'     => 'int',
    ],

    'messageDateMin' => [
        'conflict' => ['id', 'messageDateMax', 'messageIdStart', 'messageIdEnd'],
        'min'      => 0,
        'cast'     => 'int',
    ],

    'messageIdStart' => [
        'conflict' => ['id', 'messageDateMax', 'messageDateMin', 'messageIdEnd'],
        'min'      => 0,
        'cast'     => 'int',
    ],

    'messageIdEnd' => [
        'conflict' => ['id', 'messageDateMax', 'messageIdStart', 'messageDateMin'],
        'min'      => 0,
        'cast'     => 'int',
    ],

    'messageTextSearch' => [
    ],

    'page' => [
        'default' => 0,
        'cast'    => 'int',
    ]
]);


\Fim\Database::instance()->accessLog('getMessages', $request);


/* Data Predefine */
$xmlData = [
    'messages' => [],
];

if (!(\Fim\Database::instance()->hasPermission($user, $room) & Room::ROOM_PERMISSION_VIEW))
    new \Fim\Error('noPerm', 'You are not allowed to view this room.'); // Don't have permission.

else {
    /* Get Messages from Database */
    if (isset($message)) { // From message.php
        $messages = [$message];
    }
    else {
        $messageResults = \Fim\Database::instance()->getMessages(
            array_merge([
                'room' => $room,
            ], \Fim\Utilities::arrayFilterKeys($request, ['messageIdEnd', 'messageIdStart', 'messageDateMin', 'messageDateMax', 'showDeleted', 'messageTextSearch', 'userIds'])),
            ['id' => (isset($request['messageIdStart']) || isset($request['messageDateMin']) ? 'asc' : 'desc')],
            \Fim\Config::$defaultMessageLimit,
            $request['page']
        );
        $messages = $messageResults->getAsMessages();
    }


    /* Process Messages */
    if (count($messages) > 0) {
        foreach ($messages AS $id => $message) {
            $xmlData['messages'][] = \Fim\Utilities::objectArrayFilterKeys($message, ['id', 'text', 'roomId', 'userId', 'anonId', 'time', 'formatting', 'flag']);
        }
    }
}


$xmlData['messages'] = new Http\ApiOutputList($xmlData['messages']); // output the messages as a list
$xmlData['metadata']['moreResults'] = $messageResults->paginated ?? false;

/* Output Data */
echo new Http\ApiData($xmlData);