<?php
/* FreezeMessenger Copyright © 2017 Joseph Todd Parsons

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
 * Performs a Moderation Action
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2014
 *
 * @param string action
 * @param integer userId
 * @param integer roomId
*/

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('p', array(
  'action' => array(
    'valid' => array(
      'kickUser', 'unkickUser',
      'favRoom', 'unfavRoom',
      'banUser', 'unbanUser',
      'markMessageRead',
    ),
  ),

  'roomId' => array(
    'default' => 0,
    'cast' => 'int',
  ),

  'userId' => array(
    'default' => 0,
    'cast' => 'int',
  ),

  'listId' => array(
    'default' => 0,
    'cast' => 'int',
  ),

  'length' => array(
    'default' => 0,
    'cast' => 'int',
  ),

  'quiet' => array(
    'default' => false,
    'cast' => 'bool',
  ),
));



/* Data Predefine */
$xmlData = array(
  'moderate' => array(
    'response' => array(),
  ),
);



/* Start Processing */
switch ($request['action']) {
  case 'kickUser':
  $userData = $slaveDatabase->getUser($request['userId']);
  $roomData = $slaveDatabase->getRoom($request['roomId']);

  foreach ($database->getRooms()->getAsRooms() AS $objectRoom) {
    if ($objectRoom->hasPermission($userId))
  }

  $database->getRoom()->hasPermission();
  $database->getRoom()->ownerId;

  if (!count($userData)) throw new Exception('badUserId');
  elseif (!count($roomData)) throw new Exception('badRoomId');
  elseif ($database->hasPermission($roomData, $userData) >= ROOM_PERMISSION_MODERATE) throw new Exception('noKickUser'); // You can't kick other moderators.
  elseif ($database->hasPermission($roomData, $user) < ROOM_PERMISSION_MODERATE) throw new Exception('noPerm'); // You have to be a mod yourself.
  else {
    $database->kickUser($userData['userId'], $roomData['roomId'], $request['length']);

    $database->storeMessage('/me kicked ' . $userData['userName'], '', $user, $roomData);
  }
  break;

  case 'unkickUser':
  $userData = $slaveDatabase->getUser($request['userId']);
  $roomData = $slaveDatabase->getRoom($request['roomId']);

  if (!count($userData)) throw new Exception('badUserId');
  elseif (!count($roomData)) throw new Exception('badRoomId');
  elseif ($database->hasPermission($roomData, $user) < ROOM_PERMISSION_MODERATE) throw new Exception('noPerm'); // You have to be a mod.
  else {
    $this->unkickUser($userData['userId'], $roomData['roomId']);

    $database->storeMessage('/me unkicked ' . $userData['userName'], '', $user, $roomData);

    $xmlData['moderate']['response']['success'] = true;
  }
  break;

  case 'markMessageRead':
  if ($database->markMessageRead($request['messageId'], $user['userId'])) {
    $xmlData['moderate']['response']['success'] = true;
  }
  else {
    $xmlData['moderate']['response']['success'] = false;
  }
  break;

  default:
  $errStr = 'noAction';

  $xmlData['moderate']['response']['success'] = false;
  break;
}



/* Update Data for Errors */
$xmlData['moderate']['errStr'] = ($errStr);
$xmlData['moderate']['errDesc'] = ($errDesc);



/* Output Data */
echo new apiData($xmlData);
?>