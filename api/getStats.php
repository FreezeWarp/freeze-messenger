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

$apiRequest = true;
require_once('../global.php');

$rooms = $_GET['rooms'];
$roomsArray = explode(',',$rooms);
foreach ($roomsArray AS &$v) {
  $v = intval($v);
}
$roomList = implode(',',$roomsArray);


$resultLimit = (int) ($_GET['number'] ? $_GET['number'] : 10);


$xmlData = array(
  'getStats' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'sentData' => array(
       'rooms' => $roomList,
       'resultLimit' => $resultLimit,
    ),
    'errorcode' => $failCode,
    'errortext' => $failMessage,
    'roomStats' => array(),
  ),
);


($hook = hook('getStats_start') ? eval($hook) : '');


$rooms = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE roomId IN ($roomList)",'roomId');


if ($rooms) {
  foreach ($rooms AS $room) {
    ($hook = hook('getStats_eachRoom_start') ? eval($hook) : '');

    if ($hidePostCounts) {
      if (!fim_hasPermission($room,$user,'know')) {
        ($hook = hook('getStats_noPerm') ? eval($hook) : '');

        continue;
      }
    }


    ($hook = hook('getStats_eachRoom_preRooms') ? eval($hook) : '');


    $totalPosts = sqlArr("SELECT m.messages AS count,
      u.userId AS userId,
      u.userName AS userName,
      u.userFormatStart,
      u.userFormatEnd
      {$totalPosts_columns}
    FROM {$sqlPrefix}roomStats AS m,
      {$sqlPrefix}users AS u
      {$totalPosts_tables}
    WHERE m.roomId = $room[roomId] AND
      u.userId = m.userId
      $where
      {$totalPosts_where}
    ORDER BY count DESC
      {$totalPosts_order}
    LIMIT $resultLimit
    {$totalPosts_end}",'userId');


    $xmlData['getStats']['roomStats']['room ' . $room['roomId']] = array(
      'roomData' => array(
        'roomId' => (int) $room['roomId'],
        'roomName' => $room['name'],
      ),
      'users' => array(),
    );


    ($hook = hook('getStats_eachRoom_postRooms') ? eval($hook) : '');


    if ($totalPosts) {
      foreach ($totalPosts AS $totalPoster) {
        $position++;

        $xmlData['getStats']['roomStats']['room ' . $room['roomId']]['users']['user ' . $totalPoster['userId']] = array(
          'userData' => array(
            'userId' => (int) $totalPoster['userId'],
            'userName' => ($totalPoster['userName']),
            'startTag' => ($totalPoster['userFormatStart']),
            'endTag' => ($totalPoster['userFormatEnd']),
          ),
          'messageCount' => (int) $totalPoster['count'],
          'position' => (int) $position,
        );

        ($hook = hook('getStats_eachUser') ? eval($hook) : '');
      }
    }

    ($hook = hook('getStats_eachRoom_end') ? eval($hook) : '');
  }
}



$xmlData['getStats']['errorcode'] = ($failCode);
$xmlData['getStats']['errortext'] = ($failMessage);


($hook = hook('getStats_end') ? eval($hook) : '');


echo fim_outputXml($xmlData);

mysqlClose();
?>