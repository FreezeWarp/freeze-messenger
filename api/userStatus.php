<?php

/**
 * Represents a "user status" object in some context, performing user status updates/pings and retrieval of online users, depending on HTTP request:
 ** When used with a GET request, this will retrieve active users. If a room ID is provided, it will retrieve the active users in a given room. If a room ID is omitted, it will retrieve active users across the entire server.
 ** When used with a PUT request, this will replace the logged-in user's status with a new status.
 *
 * = Update User Status Directives: =
 *
 * @param list   $roomIds A list of room IDs to update a user's status to.
 * @param string $status  One of "away," "busy," "available," "invisible," and "offline," with "available" default. The former three are primarily cosmetic; "invisible" indicates that a user will only appear as an active user in the active users list of a private room (and thus is not shown in general room active users lists, or the global active users list), and "offline" indicates that a user is logging off or exiting a room; if sent, a user's previous status (whatever it is) will be removed. Full support for statuses is not yet implemented.
 * @param bool   $typing  Whether a user is typing. In practice, this should only be called for a single room, though we don't necessarily enforce the change. (If used with "offline", it is discarded. If used with any other status, it will be exposed in the active users list unless a user is invisible and in a non-private room.)
 *
 * = Get User Status Directives: =
 * @param list   $roomIds Retrieve the list of users active in these rooms. If omitted, the users active site-wide will be retrieved.
 * @param list   $userIds Restrict the active users result to these users, if specified.
 */


class userStatus
{
    static $xmlData;

    static $requestHead;

    static function init()
    {
        self::$requestHead = \Fim\Utilities::sanitizeGPC('g', [
            '_action' => [],
        ]);

        self::{self::$requestHead['_action']}();
    }

    static function get()
    {
        $request = \Fim\Utilities::sanitizeGPC('g', [
            'roomIds' => [
                'default'  => [],
                'cast'     => 'list',
                'filter'   => 'roomId',
                'evaltrue' => true,
                'max'      => 10
            ],

            'userIds' => [
                'default'  => [],
                'cast'     => 'list',
                'filter'   => 'int',
                'evaltrue' => true,
                'max'      => 10
            ],
        ]);


        /* Access Log */
        \Fim\Database::instance()->accessLog('getActiveUsers', $request);


        /* Request Data Extra Processing */
        if (count($request['roomIds']) > 0) {
            // Only include the room \if the active user has permission to know about the room.
            foreach ($request['roomIds'] AS $index => $roomId) {
                if (!(\Fim\Database::instance()->hasPermission(\Fim\LoggedInUser::instance(), \Fim\RoomFactory::getFromId($roomId)) & \Fim\Room::ROOM_PERMISSION_VIEW)) {
                    unset($request['roomIds'][$index]);
                }
            }
        }


        /* Data Predefine */
        self::$xmlData = [
            'users' => []
        ];


        /* Get Users from DB */
        $activeUsers = \Fim\Database::instance()->getActiveUsers([
            'onlineThreshold' => \Fim\Config::$defaultOnlineThreshold,
            'roomIds'         => $request['roomIds'],
            'userIds'         => $request['userIds']
        ])->getAsArray(true);


        /* Process Users for Output */
        foreach ($activeUsers AS $activeUser) {
            if (!isset(self::$xmlData['users']['user ' . $activeUser['userId']])) {
                self::$xmlData['users']['user ' . $activeUser['userId']] = [
                    'id'    => (int)$activeUser['userId'],
                    'name'  => (int)$activeUser['userName'],
                    'rooms' => [],
                ];
            }

            self::$xmlData['users']['user ' . $activeUser['userId']]['rooms']['room ' . $activeUser['roomId']] = [
                'id'     => (int)$activeUser['roomId'],
                'name'   => (string)$activeUser['roomName'],
                'status' => $activeUser['pstatus'] ?: $activeUser['status'],
                'typing' => (bool)$activeUser['typing']
            ];
        }
    }


    static function edit()
    {
        self::$requestHead = array_merge(self::$requestHead, \Fim\Utilities::sanitizeGPC('g', [
            'roomIds' => [
                'cast'    => 'list',
                'filter'  => 'roomId',
                'require' => true,
            ]
        ]));

        $request = \Fim\Utilities::sanitizeGPC('p', [
            'status' => [
                'default' => 'available',
                'valid'   => ['', 'away', 'busy', 'available', 'invisible', 'offline']
            ],

            'typing' => [
                'cast' => 'bool',
            ]
        ]);
        \Fim\Database::instance()->accessLog('editUserStatus', $request);



        /* Validate Request */
        if (isset($request['typing']) && !\Fim\Config::$userTypingStatus) {
            new \Fim\Error('typingDisabled', 'User typing functionality is disabled on this server.');
        }



        /* Get Room Data */
        foreach (self::$requestHead['roomIds'] AS $roomId) {
            $room = new \Fim\Room($roomId);

            if (\Fim\Database::instance()->hasPermission(\Fim\LoggedInUser::instance(), $room) & \Fim\Room::ROOM_PERMISSION_VIEW)
                \Fim\Database::instance()->setUserStatus($room->id, $request['status'], $request['typing'] ?? null);
        }


        self::$xmlData = [
            'response' => [],
        ];
    }

    static function delete()
    {
        // TODO: offline
    }
}


/* Entry Point Code */
$apiRequest = true;
require('../global.php');
userStatus::init();
echo new Http\ApiData(userStatus::$xmlData);