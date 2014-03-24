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
 * Obtain a roomId corresponding with a private room between the provided userIds and, if not included, the active userId.
 * @internal This API, unlike most get*() APIs, will create a new room if one does not alredy exist. This is automatic and can not be controlled.
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2014
 * @param string users - JSONList of userIds (the active user may be omitted).
 *
 * TODO -- Ignore List
*/

$apiRequest = true;

require('../global.php');


/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
  'userIds' => array(
    'default' => '',
    'cast' => 'jsonList',
    'filter' => 'int',
    'evaltrue' => true,
  ),
));

/* Data Predefine */
$xmlData = array(
  'getPrivateRoom' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => $errStr,
    'room' => array(),
  ),
);



if (!$user['userDefs']['privateRoomsFriends']) {
  $errStr = 'noPerm';
}
else {
  /** TODO: FREINDLIST **/


  /* Get Rooms From Database */
  if (!in_array($user['userId'], $request['userIds'])) $request['userIds'][] = $user['userId']; // The active user is automatically added if not specified. This is to say, this API can _not_ be used to obtain a private room that doesn't involve a user (for administrative purposes, for instance). getRooms.php can be used for this by querying roomAlias.

  $privateAlias = fim_getPrivateRoomAlias($request['userIds']);

  $room = $database->getRooms(array(
    'roomAliases' => array($privateAlias),
  ))->getAsArray(false);


  if (!count($room)) {
    $roomId = $database->createPrivateRoom($request['userIds'])->insertId;
  }



  /* Process Rooms Obtained from Database */
  $xmlData['getPrivateRoom']['roomAlias'] = $privateAlias;
  $xmlData['getPrivateRoom']['roomId'] = $roomId;
}



/* Errors */
$xmlData['getPrivateRoom']['errStr'] = ($errStr);



/* Output Data Structure */
echo fim_outputApi($xmlData);
?>