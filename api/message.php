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
 * @param roomid    $roomId         The ID of the room the message(s) belongs in. Required.
 * @param int       $id             The message ID. Required except with message retrievals (GETs).
 *
 * Message Retrieval (GET) Directives:
 * @param bool      $archive        Whether to query from the archive. Note that by design, the returned messages when not querying the archive will typically only be around ~10s old; if you are querying less than every ~10s, using the archive for every query may be ideal. (However, the archive is slower and harder on the server; you should try to query less than every 10s if at all possible, and only use the archive for initial requests and for viewing past messages.)
 * @param int       $messageLimit   The maximum number of messages to return. Default is approximately 40.
 * @param timestamp $messageDateMin A lower limit on the date of the messages.
 * @param timestamp $messageDateMax An upper limit on the date of the messages.
 * @param int       $messageIdStart A lower limit on the ID of the messages.
 * @param int       $messageIdEnd   An upper limit on the ID of the messages.
 *
 * @param string    $encode         The encoding of messages to be used on retrieval. "plaintext" is the only accepted format currently. Default is "plaintext".
 * @param bool      $showDeleted    Whether or not to show deleted messages. You will need to be a room moderator. If enabled, archive MUST be enabled as well. Default is false.
 * @param string    $search         A search keyword to restrict messages to. When used, all returned messages will contain this keyword.
 * @param list      $userIds        A list of user IDs to restrict message results to.
 *
 * Send (PUT) and Edit (POST) Message Directives:
 * @param string    $message        The message text.
 * @param string    $flag           A message content-type/context flag, used for sending images, urls, etc.
 * @param bool      $ignoreBlock    If true, the system will ignore censor warnings. You must pass this to resend a message that was denied because of a censor warning. Should not be used otherwise. Default false.
 *
 * Common Exceptions:
 *
 * @throws roomIdNoExist when the passed roomId does not correspond with a valid room.
 * @throws idNoExist     when the given id does not correspond with a valid message.
 * @throws idRequired    when an id is required but not passed.
 * @throws noPerm        when the user does not have permission to perform the attempted action.
 *
 * Create Message Exceptions:
 * @throws idExtra             when an id is passed but isn't allowed.
 * @throws kickUserNameInvalid a message started with "/kick" but the username that followed was invalid.
 *
 * Edit Message Exceptions:
 * @throws noChange The sent message text appears unchanged.
 *
 * Create and Edit Message Exceptions
 * @throws messageLength when the message is too long
 * @throws spaceMessage  when a message appears to be exclusively whitespace
 * @throws badUrl        A message with a URL flag (image, video, url, html, and audio) is invalid. You may resend as an unflagged message if you encounter this error.
 * @throws badEmail      A message with the email flag is invalid. You may resend as an unflagged message if you encounter this error.
 *
 * Get Message Exceptions:
 * @throws archiveShowDeletedConflict When showDeleted is used but archive is not.
 * @throws messageDateMaxMessageDateMinMessageIdEndConflictMessageIdStart More than one message constraint (messageDateMin, messageDateMax, messageIdStart, messageIdEnd) was used.
 */

use Fim\Error;
use Fim\ErrorThrown;
use Fim\Room;

$apiRequest = true;
require(__DIR__ . '/../global.php');
define('API_INMESSAGE', true);


/* Header parameters -- identifies what we're doing as well as the message itself, if applicable. */
$requestHead = \Fim\Utilities::sanitizeGPC('g', [
    'roomId'  => ['cast' => 'roomId', 'require' => true],
    'id'      => ['cast' => 'int'],
    '_action' => [],
]);


/* Early Validation */
if (!($room = \Fim\RoomFactory::getFromId($requestHead['roomId']))->exists() || !(\Fim\Database::instance()->hasPermission($user, $room) & Room::ROOM_PERMISSION_VIEW))
    new \Fim\Error('idNoExist', 'The given "id" parameter does not correspond with a real room.'); // Make sure we have a valid room.

if (isset($requestHead['id'])) {
    if ($requestHead['_action'] == 'create') // ID shouldn't be used here.
        new \Fim\Error('idExtra', 'Parameter ID should not be used with POST/create requests.');

    try {
        $message = \Fim\Database::instance()->getMessage($room, $requestHead['id']); // Get message object.
    } catch (ErrorThrown $ex) { // If getMessage() fails, it usually indicates in invalid ID.
        new \Fim\Error('idNoExist', 'The given "id" parameter does not correspond with a real message.');
    }
}

else if ($requestHead['_action'] != 'get' && $requestHead['_action'] != 'create')
    new \Fim\Error('idRequired', 'Parameter "ID" must be passed unless POSTing or  GETing.');


/* Load the correct file to perform the action */
switch ($requestHead['_action']) {
    case 'delete':
    case 'undelete':
        require('message/deleteUndeleteMessage.php');
    break;

    case 'edit':
    case 'create':
        require('message/sendMessage.php');
    break;

    case 'get':
        require('message/getMessages.php');
    break;
}

?>