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
 * Get the Kicks of One or More Rooms, Optionally Restricted To One or More Users
 *
 * You must have moderator permission of the room for successful retrieval!
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
 *
 * @param string rooms - A comma-seperated list of room IDs to get.
 * @param string users - A comma-seperated list of user IDs to get.
*/

$apiRequest = true;

require_once('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC(array(
  'get' => array(
    'rooms' => array(
      'type' => 'string',
      'require' => true,
      'default' => '',
      'context' => array(
         'type' => 'csv',
         'filter' => 'int',
         'evaltrue' => true,
      ),
    ),

    'users' => array(
      'type' => 'string',
      'require' => true,
      'default' => '',
      'context' => array(
         'type' => 'csv',
         'filter' => 'int',
         'evaltrue' => true,
      ),
    ),
  ),
));

if (count($request['rooms']) > 0) {
  $roomRows = dbRows("SELECT * FROM {$sqlPrefix}rooms WHERE roomId IN ($roomList)");

  foreach ($roomRows AS $roomData) {
    if (hasPermission($roomData,$user,'moderate',true)) {
      $roomArray[] = $roomData['roomId'];
    }
  }

  $roomList = implode(',',$roomArray);
}


if ($roomList) {
  $where .= "roomId IN ($roomList)";
}
if ($userList) {
  $where .= "userId IN ($userList)";
}



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



/* Plugin Hook Start */
($hook = hook('getKicks_start') ? eval($hook) : '');



/* Get Kicks from Database */
$kicks = dbRows("SELECT CONCAT(k.userId, '-', k.roomId) AS id,
  k.userId AS userId,
  u.userName AS userName,
  u.userFormatStart AS userFormatStart,
  u.userFormatEnd AS userFormatEnd,
  k.roomId AS roomId,
  k.length AS length,
  UNIX_TIMESTAMP(k.time) AS time,
  k.kickerId AS kickerId,
  i.userName AS kickerName,
  i.userFormatStart AS kickerFormatStart,
  i.userFormatEnd AS kickerFormatEnd,
  r.roomName AS roomName
  {$kicks_columns}
FROM {$sqlPrefix}kick AS k
  LEFT JOIN {$sqlPrefix}users AS u ON k.userId = u.userId
  LEFT JOIN {$sqlPrefix}users AS i ON k.kickerId = i.userId
  LEFT JOIN {$sqlPrefix}rooms AS r ON k.roomId = r.roomId
  {$kicks_tables}
WHERE $where TRUE
  {$kicks_where}
ORDER BY k.roomId
  {$kicks_order}
{$kicks_end}",'id');



/* Start Processing */
foreach ($kicks AS $kick) {
  $xmlData['getKicks']['kicks']['kick ' . $kick['kickId']] = array(
    'roomData' => array(
      'roomId' => (int) $kick['roomId'],
      'roomName' => $kick['roomName'],
    ),
    'userData' => array(
      'userId' => (int) $kick['userId'],
      'userName' => $kick['userName'],
      'userFormatStart' => $kick['userFormatStart'],
      'userFormatEnd' => $kick['userFormatEnd'],
    ),
    'kickerData' => array(
      'userId' => (int) $kick['kickerId'],
      'userName' => $kick['kickerName'],
      'userFormatStart' => $kick['kickerFormatStart'],
      'userFormatEnd' => $kick['kickerFormatEnd'],
    ),
    'length' => (int) $kick['length'],

    'set' => (int) $kick['time'],
    'setFormatted' => fim_date(false,$kick['time']),
    'expires' => (int) ($kick['set'] + $kick['length']),
    'expiresFormatted' => fim_date(false,$kick['time'] + $kick['length']),
  );

  ($hook = hook('getKicks_eachKick') ? eval($hook) : '');
}



/* Update Data for Errors */
$xmlData['getKicks']['errStr'] = fim_encodeXml($errStr);
$xmlData['getKicks']['errDesc'] = fim_encodeXml($errDesc);



/* Plugin Hook End */
($hook = hook('getKicks_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);



/* Close Database Connection */
dbClose();
?>