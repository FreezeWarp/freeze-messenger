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
 * Get the Kicks of One or More Rooms, Optionally Restricted To One or More Users
 * Only works with normal roooms.
 * You must have moderator permission of the room for successful retrieval!
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2014
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
    'cast' => 'jsonList',
    'filter' => 'int',
    'evaltrue' => true,
  ),

  'users' => array(
    'default' => '',
    'cast' => 'jsonList',
    'filter' => 'int',
    'evaltrue' => true,
  ),
));



/* Data Predefine */
$xmlData = array(
  'getKicks' => array(
    'kicks' => array(),
  ),
);


/* Get Kicks from Database */
$kicks = $database->getKicks(array(
  'userIds' => $request['users'],
  'roomIds' => $request['rooms']
))->getAsArray(true);


/* Start Processing */
foreach ($kicks AS $kick) {
  $kick['parentalAge'] = 100; // Over-ride parentalAge/parentalFlags; TODO: needed?
  $kick['parentalFlags'] = '';

  if (fim_hasPermission($kick, $user, 'moderate') || $kick['userId'] == $user['userId']) { // The user is allowed to know of all kicks they are subject to, and of all kicks in any rooms they moderate.
    $xmlData['getKicks']['kicks']['kick ' . $kick['kickId']] = array(
      'roomData' => array(
        'roomId' => (int) $kick['roomId'],
        'roomName' => (string) $kick['roomName'],
      ),
      'userData' => array(
        'userId' => (int) $kick['userId'],
        'userName' => (string) $kick['userName'],
        'userNameFormat' => (string) $kick['userNameFormat'],
      ),
      'kickerData' => array(
        'userId' => (int) $kick['kickerId'],
        'userName' => (string) $kick['kickerName'],
        'userNameFormat' => (string) $kick['userNameFormat'],
      ),
      'length' => (int) $kick['klength'],

      'set' => (int) $kick['ktime'],
      'expires' => (int) ($kick['ktime'] + $kick['klength']),
    );
  }
}



/* Update Data for Errors */
$xmlData['getKicks']['errStr'] = (string) $errStr;
$xmlData['getKicks']['errDesc'] = (string) $errDesc;
if ($config['dev']) $xmlData['request'] = $request;



/* Output Data */
echo new apiData($xmlData);
?>