<?php
/**
 * Represents a "kick" object in some context, kicking or user or retrieiving user kicks depending on context:
 ** When used with a GET request, this will retrieve kicks. If a room ID is provided, it will retrieve kicks in a single room. If a user ID is provided, it will retrieve the rooms a user has been kicked in.
 ** When used with a POST request, this will kick a user in a room.
 ** When used with a DELETE request, this will unkick a user in a room.
 */

class kick
{
    static $xmlData;

    static $requestHead;

    static $permission;

    /**
     * @var \Fim\Room
     */
    static $room;

    /**
     * @var \Fim\User
     */
    static $user;

    /**
     * @param int $roomId The ID of the room to (un)kick a user in, or get kicked users in.
     * @param int $userId The ID of the user to (un)kick, or get the rooms they have been kicked in.
     *
     * @throws roomIdNoExist If the room ID is invalid/does not exist (or the user is not allowed to view the room).
     * @throws userIdNoExist If the user ID is invalid/does not exist.
     * @throws noPerm        If trying to perform an operation on a room that the logged in user does not have permission to do.
     */
    static function init()
    {
        if (!\Fim\Config::$kicksEnabled)
            new \Fim\Error('kicksDisabled', 'Kicks are disabled on this server.');


        self::$requestHead = \Fim\Utilities::sanitizeGPC('g', [
            '_action' => [],
        ]);

        self::$requestHead = array_merge(self::$requestHead, \Fim\Utilities::sanitizeGPC('g', [
            'roomId' => [
                'cast' => 'roomId',
                'require' => (self::$requestHead['_action'] === 'delete' || self::$requestHead['_action'] === 'create')
            ],
            'userId' => [
                'cast' => 'int',
                'require' => (self::$requestHead['_action'] === 'delete' || self::$requestHead['_action'] === 'create')
            ],
        ]));


        if (isset(self::$requestHead['roomId'])) {
            if (!(self::$room = \Fim\RoomFactory::getFromId(self::$requestHead['roomId']))->exists()
                || !((self::$permission = \Fim\Database::instance()->hasPermission(\Fim\LoggedInUser::instance(), self::$room)) & \Fim\Room::ROOM_PERMISSION_VIEW))
                new \Fim\Error('roomIdNoExist', 'The given "roomId" parameter does not correspond with a real room.');

            elseif (!(
                (self::$permission & \Fim\Room::ROOM_PERMISSION_MODERATE)
                || (
                    isset(self::$requestHead['userId'])
                    && self::$requestHead['userId'] === \Fim\LoggedInUser::instance()->id
                )
            ))
                new \Fim\Error('noPerm', 'You do not have permission to moderate this room.');
        }

        if (isset(self::$requestHead['userId'])) {
            if (!(self::$user = \Fim\UserFactory::getFromId(self::$requestHead['userId']))->exists())
                new \Fim\Error('userIdNoExist', 'The given "userId" parameter does not correspond with a real user.');
        }


        self::{self::$requestHead['_action']}();
    }


    /**
     * @return {
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
     * }
     */
    static function get()
    {

        \Fim\Database::instance()->accessLog('getKicks', self::$requestHead);


        /* Data Predefine */
        self::$xmlData = ['kicks' => []];


        /* Start Processing */
        if (!isset(self::$requestHead['roomId']) // Disallow looking at kicks sitewide...
            && !( // Unless just checking the logged in user.
                isset(self::$requestHead['userId'])
                && self::$requestHead['userId'] == \Fim\LoggedInUser::instance()->id
            )
            && !\Fim\LoggedInUser::instance()->hasPriv('modRooms') // Or unless we're a site moderator.
        )
            new \Fim\Error('roomIdRequired', 'A roomId must be included unless you are a site administrator.');

        else {
            /* Get Kicks from Database */
            $kicks = \Fim\Database::instance()->getKicks([
                'userIds' => !empty(self::$user) ? [self::$user->id] : [],
                'roomIds' => !empty(self::$room) ? [self::$room->id] : [],
            ])->getAsArray(true);


            /* Process Kicks from Database */
            foreach ($kicks AS $kick) {
                if (!isset(self::$xmlData['kicks']['user ' . $kick['userId']])) {
                    self::$xmlData['kicks']['user ' . $kick['userId']] = [
                        'userId'         => (int)$kick['userId'],
                        'userName'       => $kick['userName'],
                        'kicks'          => []
                    ];
                }

                self::$xmlData['kicks']['user ' . $kick['userId']]['kicks']['room ' . $kick['roomId']] =
                    \Fim\Utilities::arrayFilterKeys($kick, ['roomId', 'roomName', 'kickerId', 'kickerName', 'set', 'expires']);
            }
        }
    }


    /**
     * @param int $length The number of seconds the user will be kicked for.
     *
     * @throws userUnkickable If the user may not be kicked (typically, because they are a room moderator).
     */
    static function create()
    {
        $request = \Fim\Utilities::sanitizeGPC('p', [
            'length' => [
                'require' => true,
                'min' => \Fim\Config::$kickMinimumLength
            ],
        ]);

        if (!(self::$permission & \Fim\Room::ROOM_PERMISSION_MODERATE))
            new \Fim\Error('noPerm', 'You do not have permission to moderate this room.');

        if (\Fim\Database::instance()->hasPermission(self::$user, self::$room) & \Fim\Room::ROOM_PERMISSION_MODERATE)
            new \Fim\Error('unkickableUser', 'Other room moderators may not be kicked.');

        else {
            \Fim\Database::instance()->kickUser(self::$user->id, self::$room->id, $request['length']);

            if (\Fim\Config::$kickSendMessage)
                \Fim\Database::instance()->storeMessage(new \Fim\Message([
                    'user' => \Fim\LoggedInUser::instance(),
                    'room' => self::$room,
                    'text'    => '/me kicked ' . self::$user->name
                ]));
        }
    }


    static function delete()
    {
        if (!(self::$permission & \Fim\Room::ROOM_PERMISSION_MODERATE))
            new \Fim\Error('noPerm', 'You do not have permission to moderate this room.');

        \Fim\Database::instance()->unkickUser(self::$user->id, self::$room->id);

        if (\Fim\Config::$unkickSendMessage)
            \Fim\Database::instance()->storeMessage(new \Fim\Message([
                'user' => \Fim\LoggedInUser::instance(),
                'room' => self::$room,
                'text'    => '/me unkicked ' . self::$user->name
            ]));
    }
}


/* Entry Point Code */
$apiRequest = true;
require('../global.php');
kick::init();
echo new Http\ApiData(kick::$xmlData);