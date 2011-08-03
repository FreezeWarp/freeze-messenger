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
 * Obtains User Post Counts in Specified Rooms
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
 *
 * @param string rooms - A comma-seperated list of room IDs to get.
 * @param int [number = 10] - The number of top posters to obtain.
*/

$apiRequest = true;

require_once('../global.php');



/* Get Request */
$request = fim_sanitizeGPC(array(
  'get' => array(
    'rooms' => array(
      'type' => 'string',
      'require' => false,
      'default' => '',
      'context' => array(
         'type' => 'csv',
         'filter' => 'int',
         'evaltrue' => true,
      ),
    ),

    'number' => array(
      'type' => 'string',
      'require' => false,
      'default' => 10,
      'context' => array(
        'type' => 'int',
      ),
    ),
  ),
));



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
    array(
      "{$sqlPrefix}rooms" => array(
        'roomId' => 'roomId',
        'roomName' => 'roomName',
      ),
    ),
    array(
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
    )
  );
  $rooms = $rooms->getAsArray('roomId');


  foreach ($rooms AS $room) {
    ($hook = hook('getStats_eachRoom_start') ? eval($hook) : '');

    if ($hidePostCounts) {
      if (!fim_hasPermission($room,$user,'view',true)) {
        ($hook = hook('getStats_noPerm') ? eval($hook) : '');

        continue;
      }
    }


    ($hook = hook('getStats_eachRoom_preRooms') ? eval($hook) : '');



    $totalPosts = $database->select(
      array(
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
      ),
      array(
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
      ),
      array(
        'messages' => 'desc',
      ),
      false,
      $request['number']
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



/* Close Database Connection */
dbClose();
?>