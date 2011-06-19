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
      'userName' => ($user['userName']),
    ),
    'sentData' => array(
      'roomId' => (int) $roomId,
    ),
    'errorcode' => ($failCode),
    'errormessage' => ($failMessage),
    'lists' => array(),
  ),
);


($hook = hook('getCensorLists_start') ? eval($hook) : '');


$censorLists = sqlArr("SELECT c.listId AS listId,
  c.name as listName,
  c.type AS listType,
  c.options AS listOptions
  {$censorLists_columns}
FROM {$sqlPrefix}censorLists AS c
  {$censorLists_tables}
WHERE TRUE
  {$censorLists_where}
ORDER BY c.name
  {$censorLists_order}
{$censorLists_end}",'groupId'); // Get all rooms


if ($censorLists) {
  foreach ($censorLists AS $list) {
    $xmlData['getCensorLists']['lists']['list ' . $list['listId']] = array(
      'listId' => (int) $list['listId'],
      'listName' => ($list['listName']),
      'listType' => ($list['listType']),
      'listOptions' => (int) $list['listOptions'],
    );

    ($hook = hook('getCensorLists_eachList') ? eval($hook) : '');
  }
}

$xmlData['getCensorLists']['errorcode'] = ($failCode);
$xmlData['getCensorLists']['errortext'] = ($failMessage);


($hook = hook('getCensorLists_end') ? eval($hook) : '');


echo fim_outputXml($xmlData);

mysqlClose();
?>