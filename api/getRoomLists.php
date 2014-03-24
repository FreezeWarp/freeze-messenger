<?php
/* FreezeMessenger Copyright © 2014 Joseph Todd Parsons

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
 * @copyright Joseph T. Parsons 2014
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
    'roomLists' => array(),
  ),
);



/* Query */
$roomLists = $database->getRoomLists($user, $request['roomLists'])->getAsArray(true);



/* Process Room Lists Obtained from Database */
foreach ($roomLists AS $roomList) {
  $xmlData['getRoomLists']['roomLists'][$roomList['listId']][] = $roomList['roomId'];
}



/* Errors */
$xmlData['getRooms']['errStr'] = ($errStr);



/* Output Data Structure */
echo fim_outputApi($xmlData);
?>