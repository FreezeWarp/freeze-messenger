<?php
/**
 * @global \Fim\Config      $config
 * @global DatabaseInstance $database
 * @global User             $user
 * @global User             $kickUser
 * @global Room             $room
 * @global int              $permission
*/

/* Prevent Direct Access to Script */

use Fim\Error;
use Fim\Room;
use Fim\User;

if (!defined('API_INKICK'))
    die();


/* Get Parameters */
$request = \Fim\Utilities::sanitizeGPC('p', [
    'length' => [
        'require' => $requestHead['_action'] === 'create',
        'min' => \Fim\Config::$kickMinimumLength
    ],
]);


/* Logging */
\Fim\Database::instance()->accessLog('getKicks', $requestHead + $request);


/* Data Predefine */
$xmlData = array(
    'kick' => array(
    )
);


/* Script */
if (!($permission & Room::ROOM_PERMISSION_MODERATE))
    new \Fim\Error('noPerm', 'You do not have permission to moderate this room.');

elseif ($requestHead['_action'] === 'create') {
    if (\Fim\Database::instance()->hasPermission($kickUser, $room) & Room::ROOM_PERMISSION_MODERATE)
        throw new \Fim\Error('unkickableUser', 'Other room moderators may not be kicked.');

    else {
        \Fim\Database::instance()->kickUser($kickUser->id, $room->id, $request['length']);

        if (\Fim\Config::$kickSendMessage)
            \Fim\Database::instance()->storeMessage(new \Fim\Message([
                'user' => $user,
                'room' => $room,
                'text'    => '/me kicked ' . $kickUser->name
            ]));
    }
}

elseif ($requestHead['_action'] === 'delete') {
    \Fim\Database::instance()->unkickUser($kickUser->id, $room->id);

    if (\Fim\Config::$unkickSendMessage)
        \Fim\Database::instance()->storeMessage(new \Fim\Message([
            'user' => $user,
            'room' => $room,
            'text'    => '/me unkicked ' . $kickUser->name
        ]));
}


/* Output Data */
echo new Http\ApiData($xmlData);