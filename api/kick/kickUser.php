<?php
/**
 * @global fimConfig $config
 * @global fimDatabase $database
 * @global fimUser $user
 * @global fimUser $kickUser
 * @global fimRoom $room
 * @global int $permission
*/

/* Prevent Direct Access to Script */
if (!defined('API_INKICK'))
    die();


/* Get Parameters */
$request = fim_sanitizeGPC('p', [
    'length' => [
        'require' => $requestHead['_action'] === 'create',
        'min' => $config->kickMinimumLength
    ],
]);


/* Logging */
$database->accessLog('getKicks', $requestHead + $request);


/* Data Predefine */
$xmlData = array(
    'kick' => array(
    )
);


/* Script */
if (!($permission & fimRoom::ROOM_PERMISSION_MODERATE))
    new fimError('noPerm', 'You do not have permission to moderate this room.');

elseif ($requestHead['_action'] === 'create') {
    if ($database->hasPermission($kickUser, $room) & fimRoom::ROOM_PERMISSION_MODERATE)
        throw new fimError('unkickableUser', 'Other room moderators may not be kicked.');

    else {
        $database->kickUser($kickUser->id, $room->id, $request['length']);

        if ($config->kickSendMessage)
            $database->storeMessage(new fimMessage([
                'user' => $user,
                'room' => $room,
                'text' => '/me kicked ' . $kickUser->name
            ]));
    }
}

elseif ($requestHead['_action'] === 'delete') {
    $database->unkickUser($kickUser->id, $room->id);

    if ($config->unkickSendMessage)
        $database->storeMessage(new fimMessage([
            'user' => $user,
            'room' => $room,
            'text' => '/me unkicked ' . $kickUser->name
        ]));
}


/* Output Data */
echo new ApiData($xmlData);