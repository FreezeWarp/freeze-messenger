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

require_once('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC(array(
  'post' => array(
    'action' => array(
      'type' => 'string',
      'require' => true,
      'valid' => array(
        'kickUser',
        'unkickUser',
        'favRoom',
        'unfavRoom',
        'banUser',
        'unbanUser',
      ),
    ),

    'roomId' => array(
      'type' => 'string',
      'require' => false,
      'default' => 0,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'userId' => array(
      'type' => 'string',
      'require' => false,
      'default' => 0,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'length' => array(
      'type' => 'string',
      'require' => false,
      'default' => 0,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'quiet' => array(
      'type' => 'string',
      'require' => false,
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

  if (!$userData) {
    $errStr = 'baduser';
    $errDesc = 'The room specified is not valid.';
  }
  elseif (!$roomData) {
    $errStr = 'badroom';
    $errDesc = 'The room specified is not valid.';
  }
  elseif (fim_hasPermission($roomData,$userData,'moderate',true)) { // You can't kick other moderators.
    $errStr = 'nokickuser';
    $errDesc = 'The user specified may not be kicked.';

    require_once('../functions/parserFunctions.php');
    fim_sendMessage('/me fought the law and the law won.',$user,$roomData);
  }
  elseif (!fim_hasPermission($roomData,$user,'moderate',true)) { // You have to be a mod yourself.
    $errStr = 'nopermission';
    $errDesc = 'You are not allowed to moderate this room.';
  }
  else {
    modLog('kick',"$userData[userId],$roomData[roomId]");

    // Delete any preexisting entries - the replace prolly negates this, but I'm not sure; is one query better than two?
//    $database->delete("{$sqlPrefix}kick",array(
//      'userId' => $userData['userId'],
//      'roomId' => $roomData['roomId'],
//    ));

    $database->insert(array(
        'userId' => (int) $userData['userId'],
        'kickerId' => (int) $user['userId'],
        'length' => (int) $request['length'],
        'roomId' => (int) $roomData['roomId'],
      ),"{$sqlPrefix}kick",array(
        'length' => (int) $request['length'],
        'kickerId' => (int) $user['userId'],
        'time' => array(
          'type' => 'raw',
          'value' => 'NOW()',
        ),
      )
    );

    require_once('../functions/parserFunctions.php');
    fim_sendMessage('/me kicked ' . $userData['userName'],$user,$room);

    $xmlData['moderate']['response']['success'] = true;
  }
  break;

  case 'unkickUser':
  $userData = $slaveDatabase->getUser($request['userId']);
  $roomData = $slaveDatabase->getRoom($request['roomId']);

  if (!$userData['userId']) {
    $errStr = 'baduser';
    $errDesc = 'The room specified is not valid.';
  }
  elseif (!$roomData['roomId']) {
    $errStr = 'badroom';
    $errDesc = 'The room specified is not valid.';
  }
  elseif (!fim_hasPermission($roomData,$user,'moderate',true)) {
    $errStr = 'nopermission';
    $errDesc = 'You are not allowed to moderate this room.';
  }
  else {
    modLog('unkick',"$userData[userId],$roomData[roomId]");

    $database->delete("{$sqlPrefix}kick",array(
      'userId' => $userData['userId'],
      'roomId' => $roomData['roomId'],
    ));

    require_once('../functions/parserFunctions.php');
    fim_sendMessage('/me unkicked ' . $userData['userName'],$user,$roomData);

    $xmlData['moderate']['response']['success'] = true;
  }
  break;


  case 'favRoom':
  $roomId = (int) $_POST['roomId'];

  $currentRooms = explode(',',$user['favRooms']); // Get an array of the user's current rooms.

  if (!in_array($roomId,$currentRooms)) { // Make sure the room is not already a favourite.
    $currentRooms[] = $roomId;

    foreach ($currentRooms as $room2) {
      if ((int) $room2) {
        $currentRooms2[] = (int) $room2;
      }
    } // Rebuild the array without the room ID.

    $newRoomString = implode(',',$currentRooms2);

    $database->update(array(
      'favRooms' => (string) $newRoomString,
    ),"{$sqlPrefix}users",array(
      'userId' => (int) $user['userId'],
    ));

    $xmlData['moderate']['response']['success'] = true;
  }
  else {
    $errStr = 'nothingtodo';

    $xmlData['moderate']['response']['success'] = false;
  }
  break;

  case 'unfavRoom':
  $roomId = (int) $_POST['roomId'];

  $currentRooms = explode(',',$user['favRooms']); // Get an array of the user's current rooms.

  if (in_array($roomId,$currentRooms)) { // Make sure the room is already a favourite.
    foreach ($currentRooms as $room2) { // Run through each room.
      if ($room2 != $roomId && (int) $room2) { // If the room is not invalid and is not the one we are trying to remove, add it to the new list.
        $currentRooms2[] = (int) $room2;
      }
    }

    $newRoomString = implode(',',$currentRooms2);

    $database->update(array(
      'favRooms' => (string) $newRoomString,
    ),"{$sqlPrefix}users",array(
      'userId' => (int) $user['userId'],
    ));

    $xmlData['moderate']['response']['success'] = true;
  }
  else {
    $errStr = 'nothingtodo';

    $xmlData['moderate']['response']['success'] = false;
  }
  break;


  case 'banUser':
  if ($user['adminDefs']['modUsers']) {
    $userId = intval($_GET['userId']);

    modLog('banuser',$userId);

    dbQuery("UPDATE {$sqlPrefix}users SET settings = IF(settings & 1 = false,settings + 1,settings) WHERE userId = $userId");

    echo container('User Banned','The user has been banned.');
  }
  break;

  case 'unbanUser':
  if ($user['adminDefs']['modImages']) {
    $userId = intval($_GET['userId']);

    modLog('unbanuser',$userId);

    dbQuery("UPDATE {$sqlPrefix}users SET settings = IF(settings & 1,settings - 1,settings) WHERE userId = $userId");

    echo container('User Unbanned','The user has been unbanned.');
  }
  break;

  default:

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