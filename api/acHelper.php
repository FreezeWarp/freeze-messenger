<?php

$apiRequest = true;

require('../global.php');

/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
  'list' => array(
    'valid' => array(
      'users', 'rooms',
    ),
    'require' => true,
  ),

  'search' => array(
    'cast' => 'string',
    'require' => true,
  ),
));


switch ($request['list']) {

  case 'users':
    $entries = new apiOutputDict($slaveDatabase->getUsers(array(
      'userNameSearch' => $request['search'],
    ))->getColumnValues('userName', 'userId'));
    break;

  case 'rooms':
    $entries = new apiOutputDict($slaveDatabase->getRooms(array(
      'roomNameSearch' => $request['search'],
    ))->getColumnValues('roomName', 'roomId'));
     break;

}



/* Data Predefine */
$xmlData = array(
    'entries' => $entries,
);


/* Output Data */
echo new apiData($xmlData);
?>