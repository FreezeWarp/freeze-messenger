<?php
/* FreezeMessenger Copyright © 2012 Joseph Todd Parsons

 * This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>. */

/**
 * Get Rooms from the Server
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 * @param string [listIds] - If specified, only specific room lists are listed. By default, all of the user's roomLists are listed.
 
*/

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
  'listIds' => array(
    'default' => '',
    'cast' => 'csv',
    'filter' => 'int',
    'evaltrue' => true,
  ),
  
  'permissionCheck' => array(
    'cast' => 'bool',
  ),
));

/* Data Predefine */
$xmlData = array(
  'getRoomLists' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => $errStr,
    'errDesc' => $errDesc,
    'roomLists' => array(),
  ),
);

/* Non-favourite rooms. */
$queryParts['roomListSelect'] = array(
  'columns' => array(
    "{$sqlPrefix}roomLists" => 'listId, userId, listName, options',
    "{$sqlPrefix}roomListRooms" => 'listId llistId, roomId lRoomid',
  ),
  'conditions' => array(
    'both' => array(
       'userId' => $database->int($user['userId']),
       'llistId' => $database->either($database->int(0), $database->col('listId')),
     ),
  ),
);

if (count($request['roomLists']) > 0) {
  $queryParts['roomListSelect']['conditions']['both']['listId'] = $database->type('array', $request['listIds'], 'in');
  $queryParts['roomSelect']['conditions']['both'][] = array(
    'type' => 'in',
    'left' => array(
      'type' => 'column',
      'value' => 'roomId',
    ),
    'right' => array(
      'type' => 'array',
      'value' => $request['rooms'],
    ),
  );
}


/* Favourite rooms. */
$queryParts['favSelect'] = array(
  'columns' => array(
    "{$sqlPrefix}roomListRooms" => 'listId llistId, roomId lRoomid',
  ),
  'conditions' => array(
    'both' => array(
       'userId' => $database->int($user['userId']),
       'llistId' => $database->int(0),
     ),
  ),
);


/* Plugin Hook Start */
($hook = hook('getRoomLists_start') ? eval($hook) : '');



/* Get Rooms From Database */
if ($request['listIds'] !== array(0)) { // Only run this if listIds is not array(1).
  $roomLists = $database->select(
    $queryParts['roomListSelect']['columns'],
    $queryParts['roomListSelect']['conditions']);
  $roomLists = $roomLists->getAsArray(true);
}

if (count($request['listIds']) === 0 || in_array(0, $request['listIds')) {
  $favRooms = $database->select(
    $queryParts['favSelect']['columns'],
    $queryParts['favSelect']['conditions']);
  $favRooms = $favRooms->getAsArray(true);
}


/* Process Room Lists Obtained from Database */
if (is_array($roomLists) && count($roomLists) > 0) {
  foreach ($roomLists AS $roomList) {
    $xmlData['getRoomLists']['roomLists'][$roomList['listId']][] = $roomList['roomId'];

    ($hook = hook('getRoomLists_eachList') ? eval($hook) : '');
  }
}



/* Errors */
$xmlData['getRooms']['errStr'] = ($errStr);
$xmlData['getRooms']['errDesc'] = ($errDesc);



/* Plugin Hook End */
($hook = hook('getRoomLists_end') ? eval($hook) : '');



/* Output Data Structure */
echo fim_outputApi($xmlData);
?>