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
 * Performs a Moderation Action
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
 *
 * @param string action
 * @param integer userId
 * @param integer roomId
*/

$apiRequest = true;

require('../global.php');
require('../functions/fim_parsers.php');



/* Get Request Data */
$request = fim_sanitizeGPC(array(
  'post' => array(
    'action' => array(
      'valid' => array(
        'kickUser',
        'unkickUser',
        'favRoom',
        'unfavRoom',
        'banUser',
        'unbanUser',
        'markMessageRead',
      ),
    ),

    'roomId' => array(
      'default' => 0,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'userId' => array(
      'default' => 0,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'length' => array(
      'default' => 0,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'quiet' => array(
      'default' => false,
      'context' => array(
        'type' => 'bool',
      ),
    ),
  ),
));



/* Data Predefine */
$xmlData = array(
  'moderate' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => ($errStr),
    'errDesc' => ($errDesc),
    'response' => array(),
  ),
);



/* Start Processing */
switch ($request['action']) {
  case 'kickUser':
  $userData = $slaveDatabase->getUser($request['userId']);
  $roomData = $slaveDatabase->getRoom($request['roomId']);

  if ($userData === false) {
    $errStr = 'badUser';
    $errDesc = 'The room specified is not valid.';
  }
  elseif ($roomData === false) {
    $errStr = 'badRoom';
    $errDesc = 'The room specified is not valid.';
  }
  elseif (fim_hasPermission($roomData, $userData, 'moderate', true)) { // You can't kick other moderators.
    $errStr = 'noKickUser';
    $errDesc = 'The user specified may not be kicked.';

    fim_sendMessage('/me fought the law and the law won.', $user, $roomData, 'me');
  }
  elseif (!fim_hasPermission($roomData, $user, 'moderate', true)) { // You have to be a mod yourself.
    $errStr = 'noPermission';
    $errDesc = 'You are not allowed to moderate this room.';
  }
  else {
    $database->modLog('kickUser', "$userData[userId],$roomData[roomId]");

    $database->insert(array(
        'userId' => (int) $userData['userId'],
        'kickerId' => (int) $user['userId'],
        'length' => (int) $request['length'],
        'roomId' => (int) $roomData['roomId'],
      ),"{$sqlPrefix}kicks",array(
        'length' => (int) $request['length'],
        'kickerId' => (int) $user['userId'],
        'time' => $database->now(),
      )
    );

    fim_sendMessage('/me kicked ' . $userData['userName'], $user, $roomData, 'me');

    $xmlData['moderate']['response']['success'] = true;
  }
  break;

  case 'unkickUser':
  $userData = $slaveDatabase->getUser($request['userId']);
  $roomData = $slaveDatabase->getRoom($request['roomId']);

  if ($userData === false) {
    $errStr = 'badUser';
    $errDesc = 'The room specified is not valid.';
  }
  elseif ($roomData === false) {
    $errStr = 'badRoom';
    $errDesc = 'The room specified is not valid.';
  }
  elseif (!fim_hasPermission($roomData, $user, 'moderate', true)) {
    $errStr = 'nopermission';
    $errDesc = 'You are not allowed to moderate this room.';
  }
  else {
    $database->modLog('unkickUser', "$userData[userId],$roomData[roomId]");

    $database->delete("{$sqlPrefix}kicks",array(
      'userId' => $userData['userId'],
      'roomId' => $roomData['roomId'],
    ));

    fim_sendMessage('/me unkicked ' . $userData['userName'], $user, $roomData, 'me');

    $xmlData['moderate']['response']['success'] = true;
  }
  break;


  case 'favRoom':
  $currentRooms = fim_arrayValidate(explode(',', $user['favRooms']), 'int', false); // Get an array of the user's current rooms.

  if (!in_array($request['roomId'], $currentRooms)) { // Make sure the room is not already a favourite.
    $currentRooms[] = $request['roomId'];

    $newRoomString = implode(',', $currentRooms2);

    $database->update(array(
      'favRooms' => (string) $newRoomString,
    ), "{$sqlPrefix}users", array(
      'userId' => (int) $user['userId'],
    ));

    $xmlData['moderate']['response']['success'] = true;
  }
  else {
    $errStr = 'nothingToDo';

    $xmlData['moderate']['response']['success'] = false;
  }
  break;

  case 'unfavRoom':
  $currentRooms = fim_arrayValidate(explode(',', $user['favRooms']), 'int', false); // Get an array of the user's current rooms.

  if (in_array($request['roomId'], $currentRooms)) { // Make sure the room is already a favourite.
    foreach ($currentRooms as $room2) { // Run through each room.
      if ($room2 != $request['roomId'] && (int) $room2) { // If the room is not invalid and is not the one we are trying to remove, add it to the new list.
        $currentRooms2[] = (int) $room2;
      }
    }

    $newRoomString = implode(',', $currentRooms2);

    $database->update(array(
      'favRooms' => (string) $newRoomString,
    ), "{$sqlPrefix}users", array(
      'userId' => (int) $user['userId'],
    ));

    $xmlData['moderate']['response']['success'] = true;
  }
  else {
    $errStr = 'nothingToDo';

    $xmlData['moderate']['response']['success'] = false;
  }
  break;


  case 'banUser':
  if ($user['adminDefs']['modUsers']) {
    $userData = $database->getUser($request['userId']);

    if ($userData['userPrivs'] & 16) { // The user is not banned
      $database->modLog('banUser', $request['userId']);

      $database->update(array(
        'userPrivs' => (int) $userData['userPrivs'] - 16,
      ), "{$sqlPrefix}users", array(
        'userId' => (int) $userData['userId'],
      ));

      $xmlData['moderate']['response']['success'] = true;
    }
    else {
      $errStr = 'nothingToDo';

      $xmlData['moderate']['response']['success'] = false;
    }
  }
  else {
    $errStr = 'noPermission';

    $xmlData['moderate']['response']['success'] = false;
  }
  break;

  case 'unbanUser':
  if ($user['adminDefs']['modUsers']) {
    $userData = $database->getUser($request['userId']);

    if ($userData['userPrivs'] ^ 16) { // The user is banned
      $database->modLog('unbanUser', $request['userId']);

      $database->update(array(
        'userPrivs' => (int) $userData['userPrivs'] + 16,
      ), "{$sqlPrefix}users", array(
        'userId' => (int) $userData['userId'],
      ));

      $xmlData['moderate']['response']['success'] = true;
    }
    else {
      $errStr = 'nothingToDo';

      $xmlData['moderate']['response']['success'] = false;
    }
  }
  else {
    $errStr = 'noPermission';

    $xmlData['moderate']['response']['success'] = false;
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
echo fim_outputApi($xmlData);



/* Close Database Connection */
dbClose();
?>