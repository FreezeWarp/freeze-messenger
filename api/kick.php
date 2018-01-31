<?php
/**
 * Represents a "kick" object in some context, kicking or user or retrieiving user kicks depending on context:
 ** When used with a GET request, this will retrieve kicks. If a room ID is provided, it will retrieve kicks in a single room. If a user ID is provided, it will retrieve the rooms a user has been kicked in.
 ** When used with a POST request, this will kick a user in a room.
 ** When used with a DELETE request, this will unkick a user in a room.
 *
 * =Directives=
 * ==Common Directives (must be in URL parameters)==
 *
 * @param int $roomId The ID of the room to (un)kick a user in, or get kicked users in.
 * @param int $userId The ID of the user to (un)kick, or get the rooms they have been kicked in.
 *
 * ==Kicking a User==
 * @param int $length The number of seconds the user will be kicked for.
 *
 *
 * =Errors=
 * ==General==
 * @throws roomIdNoExist If the room ID is invalid/does not exist (or the user is not allowed to view the room).
 * @throws userIdNoExist If the user ID is invalid/does not exist.
 * @throws noPerm        If trying to perform an operation on a room that the logged in user does not have permission to do.
 *
 * ==Kicking a User=
 * @throws userUnkickable If the user may not be kicked (typically, because they are a room moderator).
 *
 *
 * =Respone Tree=
 * ==Getting Kicks==
 * When getting kicks, data is returned as a collection of users, each of which contain a collection of rooms they have been kicked in. The name, name format, and avatar of every user is included to ease display (as in most cases, this information will not be cached).
 *
 * + kicks
 *   + user {userId}
 *     + userId - The ID of the kicked user.
 *     + userName - The name of the kicked user.
 *     + userNameFormat - The name format of the kicked user.
 *     + userAvatar - The avatar of the kicked user.
 *       + kicks
 *         + roomId - The ID of the room for the kick.
 *         + roomName - The name of the room for the kick.
 *         + kickerId - The ID of the kicker.
 *         + kickerName - The name of the kicker.
 *         + kickerNameFormat - The name format of the kicker.
 *         + kickerAvatar - The avatar of the kicker.
 *         + length - The number of seconds the kick lasts for.
 *         + set - The timestamp when the kick was set.
 *         + expires - The timestamp when the kick expires.
 */



/* Common Resources */

use Fim\Error;
use Fim\Room;

$apiRequest = true;
require('../global.php');
define('API_INKICK', true);


/* Header parameters -- identifies what we're doing as well as the kick itself, if applicable. */
$requestHead = \Fim\Utilities::sanitizeGPC('g', [
    '_action' => [],
]);
$requestHead = array_merge($requestHead, \Fim\Utilities::sanitizeGPC('g', [
    'roomId' => [
        'cast' => 'roomId',
        'require' => ($requestHead['_action'] === 'delete' || $requestHead['_action'] === 'create')
    ],
    'userId' => [
        'cast' => 'int',
        'require' => ($requestHead['_action'] === 'delete' || $requestHead['_action'] === 'create')
    ],
]));


/* Early Validation */
if (!\Fim\Config::$kicksEnabled) {
    new \Fim\Error('kicksDisabled', 'Kicks are disabled on this server.');
}

if (isset($requestHead['roomId'])) {
    if (!($room = \Fim\RoomFactory::getFromId($requestHead['roomId']))->exists()
        || !(($permission = \Fim\Database::instance()->hasPermission($user, $room)) & Room::ROOM_PERMISSION_VIEW))
        new \Fim\Error('roomIdNoExist', 'The given "roomId" parameter does not correspond with a real room.');

    elseif (!(($permission & Room::ROOM_PERMISSION_MODERATE) || (isset($requestHead['userId']) && $requestHead['userId'] === $user->id)))
        new \Fim\Error('noPerm', 'You do not have permission to moderate this room.');
}

if (isset($requestHead['userId'])) {
    if (!($kickUser = \Fim\UserFactory::getFromId($requestHead['userId']))->exists())
        new \Fim\Error('userIdNoExist', 'The given "userId" parameter does not correspond with a real user.');
}


/* Launch Correct Processing File Based on Action */
switch ($requestHead['_action']) {
    case 'create': // Kick
    case 'delete': // Unkick
        require('kick/kickUser.php');
    break;

    case 'get':
        require('kick/getKicks.php');
    break;
}