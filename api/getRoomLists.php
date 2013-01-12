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
 * @param string [roomLists] - If specified, only specific room lists are listed. By default, all of the user's roomLists are listed.
*/

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
  'roomLists' => array(
    'default' => '',
    'context' => array(
      'type' => 'csv',
      'filter' => 'int',
      'evaltrue' => true,
    ),
  ),
  
  'permissionCheck' => array(
    'context' => array(
      'type' => 'bool',
    ),
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

$queryParts['roomListSelect'] = array(
  'columns' => array(
    "{$sqlPrefix}roomLists" => 'userId, listId, roomId',
  ),
  'conditions' => array(
    'both' => array(
       'userId' => $user['userId'],
       'roomId' => 22,
     ),
  ),
);

/*if (count($request['roomLists']) > 0) {
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
}*/


/* Plugin Hook Start */
($hook = hook('getRoomLists_start') ? eval($hook) : '');



/* Get Rooms From Database */
$roomLists = $database->select(
  $queryParts['roomListSelect']['columns'],
  $queryParts['roomListSelect']['conditions']);
  die($roomLists->sourceQuery);
$roomLists = $roomLists->getAsArray(true);


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