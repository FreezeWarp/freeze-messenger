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
    $entries = $slaveDatabase->getUsers(array(
      'userNameSearch' => $request['search'],
    ))->getColumnValues('userName');
    break;

  case 'rooms':
    $entries = $slaveDatabase->getRooms(array(
      'roomNameSearch' => $request['search'],
    ))->getColumnValues('roomName');
     break;

}



/* Data Predefine */
$xmlData = array(
  'editRoom' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => $user['userName'],
    ),
    'errStr' => $errStr,
    'errDesc' => $errDesc,
    'entries' => $entries,
  ),
);


/* Output Data */
echo fim_outputApi($xmlData);
?>