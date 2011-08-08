<?php
/* FreezeMessenger Copyright © 2011 Joseph Todd Parsons

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
 * Sets a User's Activity Status
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
 *
 * @param int roomId - A comma-seperated list of room IDs to get.
 * @param string statusType - The type of status.
 * @param string statusValue - The value of the status type.
*/

$apiRequest = true;

require('../global.php');
require('../functions/parserFunctions.php');



/* Get Request Data */
$request = fim_sanitizeGPC(array(
  'post' => array(
    'roomId' => array(
      'type' => 'string',
      'default' => 'raw',
      'context' => array(
         'type' => 'int',
      ),
      'require' => false,
    ),

    'statusType' => array(
      'type' => 'string',
      'require' => false,
    ),

    'statusValue' => array(
      'type' => 'string',
      'require' => false,
    ),
  ),
));



/* Get Room Data */
$room = $slaveDatabase->getRoom($request['roomId']);



/* Plugin Hook Start */
($hook = hook('setStatus_start') ? eval($hook) : '');



/* Start Processing */
if (!$room) { // Bad room.
  $errStr = 'badroom';
  $errDesc = 'That room could not be found.';
}
elseif (!fim_hasPermission($room,$user,'view',true)) { // Not allowed to see room.
  $errStr = 'noperm';
  $errDesc = 'You are not allowed to post in this room.';
}
else {
  ($hook = hook('setStatus_inner_start') ? eval($hook) : '');

  if ($statusType == 'typing') {
    $value = (int) $statusValue;
  }
  elseif ($statusType == 'status') {
    if (in_array($value,array('available','away','busy','invisible','offline'))) {
      ($hook = hook('setStatus_inner_query ') ? eval($hook) : '');

      $database->update(array(
        'status' => $value,
      ),
      "{$sqlPrefix}ping",
      array(
        'userId' => $user['userId'],
        'roomId' => $room['roomId'],
      ));

      ),array());
    }
    else {
      $errStr = 'badstatusvalue';
      $errDesc = 'That status value is not recognized. Only "available", "away", "busy", "invisible", "offline" are supported.';
    }
  }
  else {
    $errStr = 'badstatustype';
    $errDesc = 'That status type is not recognized. Only "status" and "typing" are supported.';
  }

  ($hook = hook('setStatus_inner_end') ? eval($hook) : '');
}


$xmlData = array(
  'setStatus' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => ($errStr),
    'errDesc' => ($errDesc),
  ),
);


($hook = hook('setStatus_end') ? eval($hook) : '');


echo fim_outputApi($xmlData);

dbClose();
?>