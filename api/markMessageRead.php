<?php
$apiRequest = true;

require('../global.php');

$request = fim_sanitizeGPC('p', array(
    'roomId' => array(
        'require' => true,
        'cast' => 'roomId',
    ),
));


/* Data Predefine */
$xmlData = array(
    'editUserOptions' => array(
    ),
);

$database->markMessageRead($request['roomId'], $user->id);


/* Output Data */
echo new apiData($xmlData);