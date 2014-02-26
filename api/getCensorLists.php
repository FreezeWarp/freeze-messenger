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
 * Get All Censor Lists, Optionally With the Active Status in One or More Rooms
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 *
 * @param string rooms - A comma-seperated list of room IDs to query for whether or not the list is active in that room.
 * @param string lists - A comma-seperated list of list IDs to filter by. If not specified all lists will be retrieved.
 *
 * @todo Implement room status.
*/

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
  'rooms' => array(
    'default' => '',
    'cast' => 'csv',
    'filter' => 'int',
    'evaltrue' => true,
  ),

  'lists' => array(
    'default' => '',
    'cast' => 'csv',
    'filter' => 'int',
    'evaltrue' => true,
  ),
));



/* Data Predefine */
$xmlData = array(
  'getCensorLists' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => ($errStr),
    'errDesc' => ($errDesc),
    'lists' => array(),
  ),
);



var_dump($slaveDatabase->getCensorLists(array(
  'listIds' => $request['lists'],
  'roomIds' => $request['rooms'],
))->sourceQuery);
/* Get Censor Lists from Slave Database */
$censorLists = $slaveDatabase->getCensorLists(array(
  'listIds' => $request['lists'],
  'roomIds' => $request['rooms'],
))->getAsArray(true);
var_dump($censorLists); die();


/* Start Processing */
foreach ($censorLists AS $list) { // Run through each censor list retrieved.
  $xmlData['getCensorLists']['lists']['list ' . $list['listId']] = array(
    'listId' => (int) $list['listId'],
    'listName' => ($list['listName']),
    'listType' => ($list['listType']),
    'listOptions' => (int) $list['listOptions'],
    'active' => array(),
  );

  if (count($request['rooms']) > 0) {
    foreach ($request['rooms'] AS $roomId) {
      if (!isset($listsActive2[$list['listId']]) || !isset($listsActive2[$list['listId']][$roomId])) {
        if ($list['listType'] === 'black') {
          $roomStatus = 'unblock';
        }
        elseif ($list['listType'] === 'white') {
          $roomStatus = 'block';
        }
      }
      else {
        $roomStatus = $listsActive2[$list['listId']][$roomId];
      }

      $xmlData['getCensorLists']['lists']['list ' . $list['listId']]['active']['roomStatus ' . $roomId] = array(
        'roomId' => $roomId,
        'status' => $roomStatus,
      );
    }
  }
}



/* Update Data for Errors */
$xmlData['getCensorLists']['errStr'] = ($errStr);
$xmlData['getCensorLists']['errDesc'] = ($errDesc);



/* Output Data */
echo fim_outputApi($xmlData);
?>