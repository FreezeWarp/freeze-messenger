<?php

/**
 * Represents a "room permission" object between a room and either a user or a group. Actions act on the existing room permissions object like set operations:
 ** When used with a PUT request, this will replace the existing permissions.
 ** When used with a POST request, this will add new permissions.
 ** When used with a DELETE request, this will remove existing permissions.
 *
 * Note that, to reset a user's permissions, you should use a DELETE operation on all permissions.
 * To revoke all of a user's permissions (banning them), you should use a PUT operation with no permissions.
 *
 * = GET Directives =
 *
 * @param int    $roomId             The room ID.
 * @param int    $userId             The user ID, if altering a user's permission.
 * @param int    $groupId            The group ID, if altering a group's permission.
 *
 * = POST/PUT/DELETE Directives =
 * @param list   $permissions        A list of permissions to replace the current permissions with (if PUT), add to the current permissions (if POST), or remove from the current permissions (if DELETE).
 */


/* Common Resources */

use Fim\Room;

$apiRequest = true;
require(__DIR__ . '/../global.php');



/* Header parameters -- identifies what we're doing as well as the message itself, if applicable. */
$requestHead = (array)fim_sanitizeGPC('g', [
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

$requestBody = fim_sanitizeGPC('p', [
    'permissions' => [
        'cast'      => 'list',
        'transform' => 'bitfield',
        'bitTable'  => Room::$permArray
    ]
]);



/* Early Validation */
try {
    $room = \Fim\Database::instance()->getRoom($requestHead['roomId']);

    if (!(\Fim\Database::instance()->hasPermission($user, $room) & Room::ROOM_PERMISSION_VIEW)) {
        new fimError('idNoExist', 'The given "id" parameter does not correspond with a real room.');
    }
} catch (Exception $ex) {
    new fimError('idNoExist', 'The given "id" parameter does not correspond with a real room.');
}



/* Perform Updates */

// Get the current permissions field from the database
if (isset($request['userId'])) {
    $param = $request['userId'];
    $databasePermissionsField = \Fim\Database::instance()->getPermissionsField($requestHead['roomId'], $param);
    $attribute = 'user';
}
elseif (isset($request['groupId'])) {
    $param = $request['groupId'];
    $databasePermissionsField = \Fim\Database::instance()->getPermissionsField($requestHead['roomId'], [], $param);
    $attribute = 'group';
}
else
    new fimError('roomIdOrGroupIdRequired', 'You must pass either a room ID or a group ID.');


// If the received permission field is -1, it is invalid; default to 0.
if ($databasePermissionsField === -1)
    $databasePermissionsField = 0;


// Remove, add, or replace permissions, depending on the action.
switch ($requestHead['_action']) {
    // Add new permissions to any existing permissions.
    case 'create':
        \Fim\Database::instance()->setPermission($requestHead['roomId'], $attribute, $param, $databasePermissionsField | $requestBody['permissions']);
    break;

    // If removing permissions results in a user having none, reset their permissions entirely.
    // Otherwise, set them to the new permissions field. (To set a user's permission to 0, use the replace method.)
    case 'delete':
        if ($databasePermissionsField & ~$requestBody['permissions'] === 0)
            \Fim\Database::instance()->clearPermission($requestHead['roomId'], $attribute, $param);

        else
            \Fim\Database::instance()->setPermission($requestHead['roomId'], $attribute, $param, $databasePermissionsField & ~$requestBody['permissions']);
    break;

    // Replace a user's permissions entirely with new permissions.
    case 'edit':
        \Fim\Database::instance()->setPermission($requestHead['roomId'], $attribute, $param, $requestBody['permissions']);
    break;

    default:
        new fimError('invalidRequestMethod', 'An invalid request method was used for this request.',null,false,fimError::HTTP_405_METHOD_NOT_ALLOWED);
    break;
}

$xmlData = ['roomPermission' => fim_objectArrayFilterKeys($room, ['id', 'name']), 'request' => $request];
echo new Http\ApiData($xmlData);
?>
