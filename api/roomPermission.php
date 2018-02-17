<?php

/**
 * Represents a "room permission" object between a room and either a user or a group. Actions act on the existing room permissions object like set operations:
 ** When used with a PUT request, this will replace the existing permissions.
 ** When used with a POST request, this will add new permissions.
 ** When used with a DELETE request, this will remove existing permissions.
 *
 * Note that, to reset a user's permissions, you should use a DELETE operation on all permissions.
 * To revoke all of a user's permissions (banning them), you should use a PUT operation with no permissions.
 */


/* Common Resources */

class roomPermission {
    static $xmlData;

    static $requestHead;

    static $requestBody;

    /**
     * @var \Fim\Room
     */
    static $room;

    static $databasePermissionsField;

    static $attribute;

    static $param;


    /**
     * Request Head Directives:
     *
     * @param int    $roomId             The room ID.
     * @param int    $userId             The user ID, if altering a user's permission.
     * @param int    $groupId            The group ID, if altering a group's permission.
     *
     * Request Body Directives:
     *
     * @param list   $permissions        A list of permissions to replace the current permissions with (if PUT), add to the current permissions (if POST), or remove from the current permissions (if DELETE).
     */
    static function init()
    {
        self::$requestHead = (array)\Fim\Utilities::sanitizeGPC('g', [
            '_action' => [],

            'roomId' => [
                'cast' => 'roomId',
                'require' => true,
            ],

            'userId' => [
                'conflict' => ['groupId'],
                'cast' => 'int'
            ],

            'groupId' => [
                'conflict' => ['userId'],
                'cast' => 'int'
            ],
        ]);

        self::$requestBody = \Fim\Utilities::sanitizeGPC('p', [
            'permissions' => [
                'cast'      => 'list',
                'transform' => 'bitfield',
                'bitTable'  => \Fim\Room::$permArray
            ]
        ]);


        /* Early Validation */
        try {
            self::$room = \Fim\Database::instance()->getRoom(self::$requestHead['roomId']);

            if (!(\Fim\Database::instance()->hasPermission(\Fim\LoggedInUser::instance(), self::$room) & \Fim\Room::ROOM_PERMISSION_VIEW)) {
                new \Fim\Error('idNoExist', 'The given "id" parameter does not correspond with a real room.');
            }
        } catch (Exception $ex) {
            new \Fim\Error('idNoExist', 'The given "id" parameter does not correspond with a real room.');
        }


        /* Get the current permissions field from the database */
        if (isset(self::$requestHead['userId'])) {
            self::$param = self::$requestHead['userId'];
            self::$databasePermissionsField = \Fim\Database::instance()->getPermissionsField(self::$requestHead['roomId'], self::$param);
            self::$attribute = 'user';
        }
        elseif (isset(self::$requestHead['groupId'])) {
            self::$param = self::$requestHead['groupId'];
            self::$databasePermissionsField = \Fim\Database::instance()->getPermissionsField(self::$requestHead['roomId'], [], self::$param);
            self::$attribute = 'group';
        }
        else
            new \Fim\Error('userIdOrGroupIdRequired', 'You must pass either a user ID or a group ID.');

        // If the received permission field is -1, it is invalid; default to 0.
        if (self::$databasePermissionsField === -1)
            self::$databasePermissionsField = 0;


        self::{self::$requestHead['_action']}();

        self::$xmlData = ['roomPermission' => \Fim\Utilities::objectArrayFilterKeys(self::$room, ['id', 'name'])];
    }


    /**
     * Add new permissions to any existing permissions.
     */
    static function create()
    {
        \Fim\Database::instance()->setPermission(
            self::$requestHead['roomId'],
            self::$attribute,
            self::$param,
            self::$databasePermissionsField | self::$requestBody['permissions']
        );
    }


    static function edit()
    {
        \Fim\Database::instance()->setPermission(
            self::$requestHead['roomId'],
            self::$attribute,
            self::$param,
            self::$requestBody['permissions']
        );
    }


    /**
     * If removing permissions results in a user having none, reset their permissions entirely.
     * Otherwise, set them to the new permissions field. (To set a user's permission to 0, use the replace method.)
     */
    static function delete()
    {
        if (self::$databasePermissionsField & ~self::$requestBody['permissions'] === 0)
            \Fim\Database::instance()->clearPermission(self::$requestHead['roomId'], self::$attribute, self::$param);

        else
            \Fim\Database::instance()->setPermission(
                self::$requestHead['roomId'],
                self::$attribute,
                self::$param,
                self::$databasePermissionsField & ~self::$requestBody['permissions']
            );
    }
}

//new \Fim\Error('invalidRequestMethod', 'An invalid request method was used for this request.',null,false,Error::HTTP_405_METHOD_NOT_ALLOWED);



/* Entry Point Code */
$apiRequest = true;
require('../global.php');
roomPermission::init();
echo new Http\ApiData(roomPermission::$xmlData);