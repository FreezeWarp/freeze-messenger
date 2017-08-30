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
 * Represents a "message" object in some context, performing message posts, message edits, message deletions, message undeletions, and message retrievals:
 ** When used with a GET request, this will retrieve messages.
 ** When used with a PUT request, this will send a new message.
 ** When used with a POST request, this will update an existing message.
 ** When used with a DELETE request, this will delete a message.
 ** When used with a UNDELETE request, this will undelete a deleted message.
 *
 * Common Directives (must be in URL parameters):
 * @param RoomID roomId The ID of the room the message(s) belongs in. Required.
 * @param int id The message ID. Required except with message retrievals (GETs).
 *
 * Message Retrieval (GET) Directives:
 * @param bool archive - Whether to query from the archive. Note that by design, the returned messages when not querying the archive will typically only be around ~10s old; if you are querying less than every ~10s, using the archive for every query may be ideal. (However, the archive is slower and harder on the server; you should try to query less than every 10s if at all possible, and only use the archive for initial requests and for viewing past messages.)
 * @param int messageLimit The maximum number of messages to return. Default is approximately 40.
 * @param timestamp messageDateMin A lower limit on the date of the messages.
 * @param timestamp messageDateMax An upper limit on the date of the messages.
 * @param int messageIdStart A lower limit on the ID of the messages.
 * @param int messageIdEnd An upper limit on the ID of the messages.
 *
 * @param bool noping Disables ping; useful for archive viewing. Default is false.
 * @param string encode The encoding of messages to be used on retrieval. "plaintext" is the only accepted format currently. Default is "plaintext".

 * @param bool showDeleted Whether or not to show deleted messages. You will need to be a room moderator. If enabled, archive MUST be enabled as well. Default is false.
 * @param string search A search keyword to restrict messages to. When used, all returned messages will contain this keyword.
 * @param list userIds A list of user IDs to restrict message results to.
 *
 * Send (PUT) and Edit (POST) Message Directives:
 * @param string message - The message text.
 * @param string flag - A message content-type/context flag, used for sending images, urls, etc.
 * @param bool ignoreBlock - If true, the system will ignore censor warnings. You must pass this to resend a message that was denied because of a censor warning. Should not be used otherwise. Default false.
 *
 * -- TODO --
 * We need to use internal message boundaries via the messageIndex and messageDates table. Using these, we can approximate message dates for individual rooms. Here is how that will work:
 ** Step 1: Identify Crtiteria. If a criteria is date based (e.g. what was said in this room on this date?), we will rely on messageDates. If it is ID-based, we will rely on messageIndex.
 ** Step 2: If using date-based criteria, we lookup the approximate post ID that corresponds to the room and date. At this point, we are basically done. Simply set the messageIdStart to the date that occured before and mesageIdEnd to the date that occured after.
 ** If, however, we are using ID-based criteria, we will instead look into messageIndex. Here, we correlate room and ID, and try to find an approprimate messageIdEnd and messageIdStart.
 ** Step 3: Use a more narrow range if neccessary. The indexes we used may be too large. In this case, we need to do our best to approximate.
 * Add back unread message retrieval.
 */

$apiRequest = true;
require('../global.php');
define('API_INMESSAGE', true);


/* Header parameters -- identifies what we're doing as well as the message itself, if applicable. */
$requestHead = fim_sanitizeGPC('g', [
    'roomId' => ['cast' => 'roomId', 'require' => true],
    'id' => [ 'cast' => 'int' ],
    '_action' => [],
]);

if (!($room = new fimRoom($requestHead['roomId']))->roomExists())
    new fimError('badRoom', 'The specified room does not exist.'); // Room doesn't exist.



/* Load the correct file to perform the action */
switch ($requestHead['_action']) {
    case 'delete':
    case 'undelete':
        $message = $database->getMessage($room, $requestHead['id']);

        if (!$message->id)
            new fimError('invalidMessage', 'The message specified is invalid.');

        else if (($message->user->id = $user->id && $user->hasPriv('editOwnPosts'))
            || ($database->hasPermission($user, $room) & ROOM_PERMISSION_MODERATE)) {

            if ($requestHead['_action'] == 'delete')
                $message->setDeleted(true);
            else
                $message->setDeleted(false);

            $database->updateMessage($message);

        }

        else
            new fimError('noPerm', 'You are not allowed to delete this message.');

        echo new apiData();
    break;

    case 'edit': case 'create':
        require('message/sendMessage.php');
    break;

    case 'get':
        require('message/getMessages.php');
    break;
}

?>