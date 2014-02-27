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
 * Performs a Moderation Action
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
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

    $database->sendMessage('/me fought the law and the law won.', '', $user, $roomData); // Perhaps this should be removed...
  }
  elseif (!fim_hasPermission($roomData, $user, 'moderate', true)) { // You have to be a mod yourself.
    $errStr = 'noPerm';
    $errDesc = 'You are not allowed to moderate this room.';
  }
  else {
    $database->modLog('kickUser', "$userData[userId],$roomData[roomId]");

    $database->insert("{$sqlPrefix}kicks", array(
        'userId' => (int) $userData['userId'],
        'kickerId' => (int) $user['userId'],
        'length' => (int) $request['length'],
        'roomId' => (int) $roomData['roomId'],
      ), array(
        'length' => (int) $request['length'],
        'kickerId' => (int) $user['userId'],
        'time' => $database->now(),
      )
    );

    $database->sendMessage('/me kicked ' . $userData['userName'], '', $user, $roomData);

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

  $xmlData['moderate']['response']['success'] = true;
    $errStr = 'nopermission';
    $errDesc = 'You are not allowed to moderate this room.';
  }
  else {
    $database->modLog('unkickUser', "$userData[userId],$roomData[roomId]");

    $database->delete("{$sqlPrefix}kicks", array(
      'userId' => $userData['userId'],
      'roomId' => $roomData['roomId'],
    ));

    $database->sendMessage('/me unkicked ' . $userData['userName'], '', $user, $roomData);

    $xmlData['moderate']['response']['success'] = true;
  }
  break;


  case 'markRoom':
  $queryParts['roomListSelect']['columns'] = array(
    "{$sqlPrefix}roomLists" => 'roomId, userId, listId',
  );
  $queryParts['fileSelect']['conditions'] = array(
    'both' => array(
      array(
        'type' => 'e',
        'left' => array(
          'type' => 'column',
          'value' => 'userId',
        ),
        'right' => array(
          'type' => 'int',
          'value' => $user['userId'],
        ),
      ),
      array(
        'type' => 'e',
        'left' => array(
          'type' => 'column',
          'value' => 'roomId',
        ),
        'right' => array(
          'type' => 'int',
          'value' => $request['roomId'],
        ),
      ),
      array(
        'type' => 'e',
        'left' => array(
          'type' => 'column',
          'value' => 'listId',
        ),
        'right' => array(
          'type' => 'int',
          'value' => $request['listId'],
        ),
      ),
    ),
  );
  $roomListData = $database->select(
    $queryParts['roomListSelect']['columns'],
    $queryParts['roomListSelect']['conditions']);
  $roomListData = $roomListData->getAsArray(true);


  if (count($roomListData) > 0) {
    $errStr = 'badRoom';
    $errDesc = 'The room specified is not valid.';
  }
  elseif (!$roomData['roomId']) {
    $errStr = 'badRoom';
    $errDesc = 'The room specified is not valid.';
  }
  elseif (!fim_hasPermission($roomData, $user, 'view')) {
    $errStr = 'nopermission';
    $errDesc = 'You are not allowed to access this room.';
  }
  else {
    $database->insert("{$sqlPrefix}roomLists", array(
      'userId' => (int) $user['userId'],
      'listId' => (int) $request['listId'],
      'roomId' => (int) $request['roomId'],
    ));
  }

  $xmlData['moderate']['response']['success'] = true;
  break;

  case 'unmarkRoom':
  $database->delete("{$sqlPrefix}roomLists", array(
    'userId' => (int) $user['userId'],
    'listId' => (int) $request['listId'],
    'roomId' => (int) $request['roomId'],
  ));

  $xmlData['moderate']['response']['success'] = true;
  break;


  case 'banUser':
  if ($user['adminDefs']['modUsers']) {
    $userData = $database->getUser($request['userId']);

    if ($userData['userPrivs'] & 16) { // The user is not banned
      $database->modLog('banUser', $request['userId']);

      $database->update("{$sqlPrefix}users", array(
        'userPrivs' => (int) $userData['userPrivs'] - 16,
      ), array(
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
    $errStr = 'noPerm';

    $xmlData['moderate']['response']['success'] = false;
  }
  break;

  case 'unbanUser':
  if ($user['adminDefs']['modUsers']) {
    $userData = $database->getUser($request['userId']);

    if ($userData['userPrivs'] ^ 16) { // The user is banned
      $database->modLog('unbanUser', $request['userId']);

      $database->update("{$sqlPrefix}users", array(
        'userPrivs' => (int) $userData['userPrivs'] + 16,
      ), array(
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
    $errStr = 'noPerm';

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
?>