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
   along withis program.  If not, see <http://www.gnu.org/licenses/>. */

/**
 * Get Messages from the Server
 * Works with both private and normal rooms.
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2014

 * Primary Directives:
 * @param string roomId - The ID of the room to fetch messages from.
 * @param bool archive - Whether to query from the archive. Note that by design, the returned messages when not querying the archive will typically only be around ~10s old; if you are querying less than every ~10s, using the archive for every query may be ideal. (However, the archive is slower and harder on the server; you should try to query less than every 10s if at all possible, and only use the archive for initial requests and for viewing past messages.)
 * @param int [messageLimit=1000] - The maximum number of posts to receive, defaulting to the internal limit of (in most cases) 1000. This should be high, as all other conditions (roomId, deleted, etc.) are applied after this limit.
 * @param int [messageHardLimit=40] - An alternative, generally lower limit applied once all messages are obtained from the server (or via the LIMIT clause of applicable). In other words, this limits the number of results AFTER roomId, etc. restrictions have been applied.
 * @param timestamp [messageDateMin=null] - The earliest a post could have been made. Use of messageDateMax only makesense with no messageLimit. Do not specify to prevent checking.
 * @param timestamp [messageDateMax=null] - The latest a post could have been made. Use of messageDateMax only makesense with no messageLimit. Do not specify to prevent checking.
 * @param int [messageIdMin=null] - All posts must be after this ID. Use of messageDateMax only makesense with no messageLimit. Do not specify to prevent checking.
 * @param int [messageIdMax=null] - All posts must before this ID. Use of messageDateMax only makesense with no messageLimit. Do not specify to prevent checking.
 * @param int [messageIdStart=null] - When specified WITHOUT the above two directives, messageIdStart will return all posts from this ID to this ID plus the messageLimit directive. This strongly encouraged for all requests to the cache, e.g. for normal instant messenging sessions.

 * Misc Directives:
 * @param bool [noping=false] - Disables ping; useful for archive viewing.
 * @param bool [longPolling=false] - Whether or noto enablexperimentalongPolling. It will be replaced with "pollMethod=push|poll|longPoll" in version 4 when all three methods will be supported (though will be backwards compatible).
 * @param string [encode=plaintext] - Thencoding of messages to be used on retrieval. "plaintext" is the only accepted format currently.

 * Filters:
 * @param bool [showDeleted=false] - Whether or not to show deleted messages. You will need to be a room moderator. This directive only has an effect on the archive, as the cache does not retain deleted messages.
 * @param string [search=null] - A keyword that can be used for searching through the archive. It will overwrite messages.
 * @param string [messages=null] - A comma seperated list of message IDs thathe results will be limited to. It is only intended for use withe archive, and will be over-written withe results of the search directive if specified.
 * @param string users - A comma seperated list of users to restrict message retrieval to. This most useful for archive scanning, though can in theory be used withe message cache as well.
 *
 * @todo Add back unread message retrieval.
 *
 * -- Notes on Scalability --
 * As FreezeMessenger attempts to ecourage broad scalability wherever possible, sacrifices are atimes made to prevent badness from happening. getMessages illustrates one of the best examples of this:
 * the use of indexes is a must for any reliable message retrieval. As such, a standard "SELECT * WHERE roomId = xxx ORDER BY messageId DESC LIMIT 10" (the easiest way of getting the last 10 messages) is simply impossible. Instead, a few alternatives are recommended:
 ** Specify a "messageIdEnd" as the last message obtained from the room.
 * similarly, the messageLimit and messageHardLimit directives are applied for the sake of scalibility. messageHardLimit is after results have been retrieved and filtered by, say, the roomId, and messageLimit is a limit on messages retrieved from all rooms, etc.
 * a message cache is maintained, and it is the default means of obtaining messages. Specifying archive will be far slower, but is required for searching, and generally is recommended at other times as well (e.g. getting initial posts).
 *
 * -- TODO --
 * We need to use internal message boundaries via the messageIndex and messageDates table. Using these, we can approximate message dates for individual rooms. Here is how that will work:
 ** Step 1: Identify Crtiteria. If a criteria is date based (e.g. what was said in this room on this date?), we will rely on messageDates. If it is ID-based, we will rely on messageIndex.
 ** Step 2: If using date-based criteria, we lookup the approximate post ID that corresponds to the room and date. At this point, we are basically done. Simply set the messageIdStart to the date that occured before and mesageIdEnd to the date that occured after.
 ** If, however, we are using ID-based criteria, we will instead look into messageIndex. Here, we correlate room and ID, and try to find an approprimate messageIdEnd and messageIdStart.
 ** Step 3: Use a more narrow range if neccessary. The indexes we used may be too large. In this case, we need to do our best to approximate.
 */


$apiRequest = true;

require('../global.php');

/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
    'roomId' => array(
        'cast' => 'roomId',
        'require' => true,
    ),

    'userIds' => array(
        'default' => [],
        'cast' => 'list',
        'filter' => 'int',
        'evaltrue' => true,
    ),

    'messageIds' => array(
        'default' => [],
        'cast' => 'list',
        'filter' => 'int',
        'evaltrue' => true,
    ),

    'sortBy' => array(
        'valid' => array(
            'messageId',
        ),
        'default' => 'messageId',
    ),

    'sortOrder' => array(
        'valid' => array(
            'desc', 'asc'
        ),
        'default' => 'asc'
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
        'default' => 0,
        'cast' => 'int',
    ),

    'messageDateMin' => array(
        'default' => 0,
        'cast' => 'int',
    ),

    'messageIdStart' => array(
        'default' => 0,
        'cast' => 'int',
    ),

    'messageIdEnd' => array(
        'default' => 0,
        'cast' => 'int',
    ),

    'messageHardLimit' => array(
        'default' => $config['defaultMessageHardLimit'],
        'max' => $config['maxMessageHardLimit'],
        'min' => 1,
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


/* Get the roomdata. */
$room = new fimRoom($request['roomId']);


/* Data Predefine */
$xmlData = array(
    'messages' => array(),
);


if (!$room->roomExists())
    new fimError('badRoom', 'The specified room does not exist.'); // Room doesn't exist.

elseif (!($database->hasPermission($user, $room) & ROOM_PERMISSION_VIEW))
    new fimError('noPerm', 'You are not allowed to view this room.'); // Don't have permission.

else {
    /* Process Ping */
    if (!$request['noping']) $database->setUserStatus($room->id);


    /* Get Messages from Database */
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
        'messageIds' => $request['messageIds'],
        'messageHardLimit' => $request['messageHardLimit'],
        'page' => $request['page']
    ), array($request['sortBy'] => $request['sortOrder']), $request['messageLimit'], $request['page'])->getAsArray(true);// print($messages->sourceQuery); die('3');


    /* Process Messages */
    if (count($messages) > 0) {
        if (count($messages) > $request['messageHardLimit']) {
            if (isset($request['messageIdEnd'])) array_splice($messages, 0, -1 * $request['messageHardLimit']);
            else array_splice($messages, $request['messageHardLimit']);
        }

        foreach ($messages AS $id => $message) {
            $message['text'] = fim_decrypt($message['text'], $message['salt'], $message['iv']);

            switch ($request['encode']) {
            case 'plaintext': break; // All Good
            case 'base64': $message['text'] = base64_encode($message['text']); break;
            }

            $xmlData['messages'][] = array(
                'messageData' => array(
                    'roomId' => (int) $room->id,
                    'messageId' => (int) $message['messageId'],
                    'messageTime' => (int) $message['time'],
                    'messageText' => $message['text'],
                    'flags' => ($message['flag']),
                ),
                'userData' => array(
                    'userName' => ($message['userName']),
                    'userId' => (int) $message['userId'],
                    'userGroup' => (int) $message['userGroup'],
                    'avatar' => ($message['avatar']),
                    'socialGroups' => ($message['socialGroups']),
                    'userNameFormat' => ($message['userNameFormat']),
                    'defaultFormatting' => array(
                        'color' => ($message['defaultColor']),
                        'highlight' => ($message['defaultHighlight']),
                        'fontface' => ($message['defaultFontface']),
                        'general' => (int) $message['defaultFormatting']
                    ),
                ),
            );
        }
    }
}



/* Output Data */
echo new apiData($xmlData);
?>