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


$roomId = (int) $_GET['roomid'];


$xmlData = array(
  'getCensorLists' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => fim_encodeXml($user['userName']),
    ),
    'sentData' => array(
      'roomId' => (int) $roomId,
    ),
    'errorcode' => fim_encodeXml($failCode),
    'errormessage' => fim_encodeXml($failMessage),
    'lists' => array(),
  ),
);


$censorLists = sqlArr("SELECT c.id AS listId,
  c.name as listName,
  c.type AS listType,
  c.options AS listOptions
FROM {$sqlPrefix}censorLists AS c
  {$tables}
WHERE TRUE
  {$where}
ORDER BY c.name
  {$order}",'groupId'); // Get all rooms


if ($censorLists) {
  foreach ($censorLists AS $list) {
    $xmlData['getCensorLists']['lists']['list ' . $list['listId']] = array(
      'listId' => (int) $list['listId'],
      'listName' => fim_encodeXml($list['listName']),
      'listType' => fim_encodeXml($list['listType']),
      'listOptions' => (int) $list['listOptions'],
    );
  }
}


echo fim_outputXml($xmlData);

mysqlClose();
?>