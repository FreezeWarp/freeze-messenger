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
 * Obtains User Post Counts in Specified Rooms
 * Only works with normal rooms.
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2012
 *
 * @param string rooms - A comma-seperated list of room IDs to get.
 * @param int [number = 10] - The number of top posters to obtain.
*/

$apiRequest = true;

require('../global.php');



/* Get Request */
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
  'number' => array(
    'default' => 10,
    'context' => 'int',
  ),
));

$queryParts['roomSelect']['columns'] = array(
  "{$sqlPrefix}rooms" => 'roomId, roomName, options, defaultPermissions, owner, parentalAge, parentalFlags',
);
$queryParts['roomSelect']['conditions'] = array(
  'both' => array(
    array(
      'type' => 'in',
      'left' => array(
        'type' => 'column',
        'value' => 'roomId',
      ),
      'right' => array(
        'type' => 'array',
        'value' => $request['rooms'],
      ),
    ),
  ),
);
$queryParts['roomSelect']['sort'] = false;
$queryParts['roomSelect']['limit'] = false;



/* Data Predefine */
$xmlData = array(
  'getStats' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => $errStr,
    'errDesc' => $errDesc,
    'roomStats' => array(),
  ),
);



/* Plugin Hook Start */
($hook = hook('getStats_start') ? eval($hook) : '');



/* Start Processing */
if (count($request['rooms']) > 0) {
  $rooms = $database->select(
    $queryParts['roomSelect']['columns'],
    $queryParts['roomSelect']['conditions'],
    $queryParts['roomSelect']['sort'],
    $queryParts['roomSelect']['limit']
  );
  $rooms = $rooms->getAsArray('roomId');


  foreach ($rooms AS $room) {
    $room['type'] = 'normal'; // Set this for hasPermission.

    ($hook = hook('getStats_eachRoom_start') ? eval($hook) : '');

/*    if (!fim_hasPermission($room, $user, 'view', true)) { // Users must be able to view the room to see the respective post counts.
      ($hook = hook('getStats_noPerm') ? eval($hook) : '');

      continue;
    }*/



    $queryParts['statsSelect']['columns'] = array(
      "{$sqlPrefix}roomStats" => array(
        'roomId' => 'sroomId',
        'userId' => 'suserId',
        'messages' => 'messages',
      ),
      "{$sqlPrefix}users" => array(
        'userId' => 'userId',
        'userName' => 'userName',
        'userFormatStart' => 'userFormatStart',
        'userFormatEnd' => 'userFormatEnd',
      ),
    );
    $queryParts['statsSelect']['conditions'] = array(
      'both' => array(
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'suserId',
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
            'value' => 'sroomId',
          ),
          'right' => array(
            'type' => 'int',
            'value' => (int) $room['roomId'],
          ),
        ),
      ),
    );
    $queryParts['statsSelect']['sort'] = 'messages desc';
    $queryParts['statsSelect']['limit'] = $request['number'];


    if (count($request['users']) > 0) {
      $queryParts['statsSelect']['conditions']['both'][] = array(
        'type' => 'e',
        'left' => array(
          'type' => 'column',
          'value' => 'suserId',
        ),
        'right' => array(
          'type' => 'array',
          'value' => $request['users'],
        ),
      );
    }


    ($hook = hook('getStats_eachRoom_preRooms') ? eval($hook) : '');

    $totalPosts = $database->select(
      $queryParts['statsSelect']['columns'],
      $queryParts['statsSelect']['conditions'],
      $queryParts['statsSelect']['sort'],
      $queryParts['statsSelect']['limit']
    );
    $totalPosts = $totalPosts->getAsArray('userId');


    $xmlData['getStats']['roomStats']['room ' . $room['roomId']] = array(
      'roomData' => array(
        'roomId' => (int) $room['roomId'],
        'roomName' => $room['name'],
      ),
      'users' => array(),
    );


    ($hook = hook('getStats_eachRoom_postRooms') ? eval($hook) : '');


    if (is_array($totalPosts)) {
      if (count($totalPosts) > 0) {
        foreach ($totalPosts AS $totalPoster) {
          $position++;

          $xmlData['getStats']['roomStats']['room ' . $room['roomId']]['users']['user ' . $totalPoster['userId']] = array(
            'userData' => array(
              'userId' => (int) $totalPoster['userId'],
              'userName' => ($totalPoster['userName']),
              'startTag' => ($totalPoster['userFormatStart']),
              'endTag' => ($totalPoster['userFormatEnd']),
            ),
            'messageCount' => (int) $totalPoster['messages'],
            'position' => (int) $position,
          );

          ($hook = hook('getStats_eachUser') ? eval($hook) : '');
        }
      }
    }

    ($hook = hook('getStats_eachRoom_end') ? eval($hook) : '');
  }
}



/* Update Data for Errors */
$xmlData['getStats']['errStr'] = ($errStr);
$xmlData['getStats']['errDesc'] = ($errDesc);



/* Plugin Hook End */
($hook = hook('getStats_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);
?>