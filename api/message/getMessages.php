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
if (!defined('API_INMESSAGE'))
    die();


/* Get Request Data */
$request = fim_sanitizeGPC('g', [
    'userIds' => [
        'default'  => [],
        'cast'     => 'list',
        'filter'   => 'int',
        'evaltrue' => true,
    ],

    'showDeleted' => [
        'default' => false,
        'cast'    => 'bool',
    ],

    'archive' => [
        'default' => false,
        'cast'    => 'bool',
    ],

    'noping' => [
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

    'encode' => [
        'default' => 'plaintext',
        'valid'   => [
            'plaintext', 'base64',
        ],
    ],

    'page' => [
        'default' => 0,
        'cast'    => 'int',
    ],
]);


if (!$request['archive'] && $request['showDeleted'])
    new fimError('archiveShowDeletedConflict', 'archive and showDeleted must be used together.');


$database->accessLog('getMessages', $request);


/* Data Predefine */
$xmlData = [
    'messages' => [],
];

if (!($database->hasPermission($user, $room) & fimRoom::ROOM_PERMISSION_VIEW))
    new fimError('noPerm', 'You are not allowed to view this room.'); // Don't have permission.

else {
    /* Process Ping */
    if (!$request['noping'])
        $database->setUserStatus($room->id);

    if (!$request['archive'])
        $database->markMessageRead($room->id, $user->id);


    /* Get Messages from Database */
    if (isset($message)) { // From message.php
        $messages = [$message];
    }
    else {
        $messages = $database->getMessages(
            array_merge([
                'room' => $room,
            ], fim_arrayFilterKeys($request, ['messageIdEnd', 'messageIdStart', 'messageDateMin', 'messageDateMax', 'showDeleted', 'messageTextSearch', 'archive', 'userIds'])),

            ['id' => ($request['messageIdStart'] || $request['messageDateMin'] ? 'asc' : 'desc')],
            $config['defaultMessageLimit'],
            $request['page']
        )->getAsMessages();
    }


    /* Process Messages */
    if (count($messages) > 0) {
        foreach ($messages AS $id => $message) {
            $xmlData['messages'][] = array_merge([
                'text'   => ($request['encode'] == 'base64' ? base64_encode($message->text) : $message->text),
                'userId' => $message->user->id,
            ], fim_objectArrayFilterKeys($message, ['id', 'time', 'formatting', 'flag']));
        }
    }
}



/* Output Data */
echo new apiData($xmlData);
?>