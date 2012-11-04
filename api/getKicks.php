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
 * Get the Kicks of One or More Rooms, Optionally Restricted To One or More Users
 * Only works with normal roooms.
 * You must have moderator permission of the room for successful retrieval!
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2012
 *
 * @param string rooms - A comma-seperated list of room IDs to get.
 * @param string users - A comma-seperated list of user IDs to get.
*/

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
  'rooms' => array(
    'default' => '',
    'context' => array(
      'type' => 'csv',
      'filter' => 'int',
      'evaltrue' => true,
    ),
  ),

  'users' => array(
    'default' => '',
    'context' => array(
      'type' => 'csv',
      'filter' => 'int',
      'evaltrue' => true,
    ),
  ),
));



/* Data Predefine */
$xmlData = array(
  'getKicks' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => fim_encodeXml($user['userName']),
    ),
    'errStr' => fim_encodeXml($errStr),
    'errDesc' => fim_encodeXml($errDesc),
    'kicks' => array(),
  ),
);

$queryParts['kicksSelect']['columns'] = array(
  "{$sqlPrefix}kicks" => 'kickerId kkickerId, userId kuserId, roomId kroomId, length klength, time ktime',
  "{$sqlPrefix}users user" => 'userId, userName, userFormatStart, userFormatEnd',
  "{$sqlPrefix}users kicker" => 'userId kickerId, userName kickerName, userFormatStart kickerFormatStart, userFormatEnd kickerFormatEnd',
  "{$sqlPrefix}rooms" => 'roomId, roomName, owner, options',
);
$queryParts['kicksSelect']['conditions'] = array(
  'both' => array(
    array(
      'type' => 'e',
      'left' => array(
        'type' => 'column',
        'value' => 'kuserId',
      ),
      'right' => array(
        'type' => 'column',
        'value' => 'userId',
      ),
    ),
    array(
      'type' => 'e',
      'left' => array(
        'type' => 'column',
        'value' => 'kroomId',
      ),
      'right' => array(
        'type' => 'column',
        'value' => 'roomId',
      ),
    ),
    array(
      'type' => 'e',
      'left' => array(
        'type' => 'column',
        'value' => 'kkickerId',
      ),
      'right' => array(
        'type' => 'column',
        'value' => 'kickerId',
      ),
    ),
  ),
);
$queryParts['kicksSelect']['sort'] = 'roomId, userId';
$queryParts['kicksSelect']['limit'] = false;



/* Modify Query Data for Directives */
if (count($request['users']) > 0) {
  $queryParts['usersSelect']['conditions']['both'][] = array(
    'type' => 'in',
    'left' => array(
      'type' => 'column',
      'value' => 'kuserId',
    ),
    'right' => array(
       'type' => 'array',
       'value' => (array) $request['users'],
    ),
  );
}

if (count($request['rooms']) > 0) {
  $queryParts['usersSelect']['conditions']['both'][] = array(
    'type' => 'in',
    'left' => array(
      'type' => 'column',
      'value' => 'kroomId',
    ),
    'right' => array(
       'type' => 'array',
       'value' => (array) $request['rooms'],
    ),
  );
}



/* Plugin Hook Start */
($hook = hook('getKicks_start') ? eval($hook) : '');



/* Get Kicks from Database */
$kicks = $database->select($queryParts['kicksSelect']['columns'],
  $queryParts['kicksSelect']['conditions'],
  $queryParts['kicksSelect']['sort'],
  $queryParts['kicksSelect']['limit']);
$kicks = $kicks->getAsArray(true);



/* Start Processing */
if (is_array($kicks)) {
  if (count($kicks) > 0) {
    foreach ($kicks AS $kick) {
      if (fim_hasPermission($kick,$user,'moderate') || $kick['userId'] == $user['userId']) { // The user is allowed to know of all kicks they are subject to, and of all kicks in any rooms they moderate.
        $xmlData['getKicks']['kicks']['kick ' . $kick['kickId']] = array(
          'roomData' => array(
            'roomId' => (int) $kick['roomId'],
            'roomName' => (string) $kick['roomName'],
          ),
          'userData' => array(
            'userId' => (int) $kick['userId'],
            'userName' => (string) $kick['userName'],
            'userFormatStart' => (string) $kick['userFormatStart'],
            'userFormatEnd' => (string) $kick['userFormatEnd'],
          ),
          'kickerData' => array(
            'userId' => (int) $kick['kickerId'],
            'userName' => (string) $kick['kickerName'],
            'userFormatStart' => (string) $kick['kickerFormatStart'],
            'userFormatEnd' => (string) $kick['kickerFormatEnd'],
          ),
          'length' => (int) $kick['klength'],

          'set' => (int) $kick['ktime'],
          'expires' => (int) ($kick['ktime'] + $kick['klength']),
        );

        ($hook = hook('getKicks_eachKick') ? eval($hook) : '');
      }
    }
  }
}



/* Update Data for Errors */
$xmlData['getKicks']['errStr'] = (string) $errStr;
$xmlData['getKicks']['errDesc'] = (string) $errDesc;



/* Plugin Hook End */
($hook = hook('getKicks_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);
?>