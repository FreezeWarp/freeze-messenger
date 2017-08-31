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
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2014
 */


/* Prevent Direct Access of File */
if (!defined('API_INMESSAGE'))
    die();


/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
    'userIds' => array(
        'default' => [],
        'cast' => 'list',
        'filter' => 'int',
        'evaltrue' => true,
    ),

    'showDeleted' => array(
        'default' => false,
        'cast' => 'bool',
    ),

    'archive' => array(
        'default' => false,
        'cast' => 'bool',
    ),

    'noping' => array(
        'default' => false,
        'cast' => 'bool',
    ),

    'messageDateMax' => array(
        'min' => 0,
        'default' => 0,
        'cast' => 'int',
    ),

    'messageDateMin' => array(
        'min' => 0,
        'default' => 0,
        'cast' => 'int',
    ),

    'messageIdStart' => array(
        'min' => 0,
        'default' => 0,
        'cast' => 'int',
    ),

    'messageIdEnd' => array(
        'min' => 0,
        'default' => 0,
        'cast' => 'int',
    ),

    'search' => array(
        'default' => false,
    ),

    'encode' => array(
        'default' => 'plaintext',
        'valid' => array(
            'plaintext', 'base64',
        ),
    ),

    'page' => array(
        'default' => 0,
        'cast' => 'int',
    ),
));


if (!$request['archive'] && $request['showDeleted'])
    new fimError('archiveShowDeletedConflict', 'archive and showDeleted must be used together.');

if ((((int) (bool) $request['messageDateMin']) + ((int) (bool) $request['messageDateMax']) + ((int) (bool) $request['messageIdStart']) + ((int) (bool) $request['messageIdEnd'])) > 1)
    new fimError('messageDateMaxMessageDateMinMessageIdEndConflictMessageIdStart', 'Only one of messageDateMin, messageDateMax, messageIdStart, messageIdEnd may be used.');


$database->accessLog('getMessages', $request);


/* Data Predefine */
$xmlData = array(
    'messages' => array(),
);

if (!($database->hasPermission($user, $room) & ROOM_PERMISSION_VIEW))
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
        $messages = $database->getMessages(array(
            'room' => $room,
            'messageIdEnd' => $request['messageIdEnd'],
            'messageIdStart' => $request['messageIdStart'],
            'messageDateMin' => $request['messageDateMax'],
            'messageDateMax' => $request['messageDateMax'],
            'showDeleted' => $request['showDeleted'],
            'messageTextSearch' => $request['search'],
            'archive' => $request['archive'],
            'userIds' => $request['userIds'],
        ), ['messageId' => ($request['messageIdEnd'] || $request['messageDateMax'] ? 'desc' : 'asc')], $config['defaultMessageLimit'], $request['page'])->getAsMessages();
    }


    /* Process Messages */
    if (count($messages) > 0) {
        foreach ($messages AS $id => $message) {
            $xmlData['messages'][] = array(
                'messageId' => (int) $message->id,
                'messageTime' => (int) $message->time,
                'messageText' => ($request['encode'] == 'base64' ? base64_encode($message->text) : $message->text),
                'messageFormatting' => $message->formatting,
                'flags' => ($message->flag),
                'userId' => $message->user->id,
            );
        }
    }
}



/* Output Data */
echo new apiData($xmlData);
?>