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

$time = (int) ($_GET['time'] ? $_GET['time'] : time());
$onlineThreshold = (int) ($_GET['onlineThreshold'] ? $_GET['onlineThreshold'] : $onlineThreshold);

$xmlData = array(
  'getAllActiveUsers' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'sentData' => array(
      'onlineThreshold' => (int) $onlineThreshold,
      'time' => (int) $time,
    ),
    'errorcode' => ($failCode),
    'errormessage' => ($failMessage),
    'users' => array(),
  ),
);

($hook = hook('getAllActiveUsers_start') ? eval($hook) : '');

$ausers = sqlArr("SELECT
  u.userName,
  u.userId,
  u.userFormatStart,
  u.userFormatEnd,
  GROUP_CONCAT(r.name) AS roomNames,
  GROUP_CONCAT(r.roomId) AS roomIds
  $cols
FROM
  {$sqlPrefix}users AS u,
  {$sqlPrefix}rooms AS r,
  {$sqlPrefix}ping AS p
  $tables
WHERE
  u.userId = p.userId AND
  r.roomId = p.roomId AND
  UNIX_TIMESTAMP(p.time) > $time - $onlineThreshold
  $where
GROUP BY
  p.userId
  $groupby
ORDER BY
  u.userName
  $orderby
$query",'userId');

if ($ausers) {
  foreach ($ausers AS $auser) {
    $rooms = array_combine(explode(',',$auser['roomIds']),explode(',',$auser['roomNames']));

    $xmlData['getAllActiveUsers']['users']['user ' . $auser['userId']] = array(
      'userData' => array(
        'userId' => (int) $auser['userId'],
        'userName' => ($auser['userName']),
        'startTag' => ($auser['userFormatStart']),
        'endTag' => ($auser['userFormatEnd']),
      ),
      'rooms' => array(),
    );

    ($hook = hook('getAllActiveUsers_eachUser_start') ? eval($hook) : '');

    foreach ($rooms AS $roomId => $name) {
      $xmlData['getAllActiveUsers']['users']['user ' . $auser['userId']]['rooms']['room ' . $roomId] = array(
        'roomId' => (int) $roomId,
        'roomName' => ($name),
      );

      ($hook = hook('getAllActiveUsers_eachRoom') ? eval($hook) : '');
    }

    ($hook = hook('getAllActiveUsers_eachUser_end') ? eval($hook) : '');
  }
}


$xmlData['getAllActiveUsers']['errorcode'] = ($failCode);
$xmlData['getAllActiveUsers']['errortext'] = ($failMessage);


($hook = hook('getAllActiveUsers_end') ? eval($hook) : '');


echo fim_outputXml($xmlData);

mysqlClose();
?>