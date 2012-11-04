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
 * Edit's a User's Room List
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
 * TODO -- Optimise
*/

$apiRequest = true;

require('../global.php');


/* Get Request Data */
$request = fim_sanitizeGPC('p', array(
  'roomIds' => array(
    'context' => array(
      'type' => 'csv',
      'filter' => 'int',
      'evaltrue' => true,
    ),
  ),

  'roomListId' => array(
    'context' => array(
      'type' => 'int',
    ),
  ),

  'method' => array(
    'context' => array(
      'allowedValues' => array('add', 'remove', 'replace'),
    ),
  ),
));


/* Data Predefine */
$xmlData = array(
  'editIgnoreList' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => ($errStr),
    'errDesc' => ($errDesc),
    'response' => array(),
  ),
);


/* Plugin Hook Start */
($hook = hook('editIgnoreList_start') ? eval($hook) : '');


switch ($request['method']) {
  case 'add':
  foreach ($request['ignoredUserId'] AS $ignoredUserId) {
    if ($slaveDatabase->getUser($ignoredUserId)) {
      $database->delete("{$sqlPrefix}ignoredUsers", array(
        'userId' => $user['userId'],
        'ignoredUserId' => $ignoredUserId,
      ));

      $database->insert("{$sqlPrefix}ignoredUsers", array(
        'userId' => $user['userId'],
        'ignoredUserId' => $ignoredUserId,
      ));
    }
  }
  break;

  case 'remove':
  foreach ($request['ignoredUserId'] AS $ignoredUserId) {
    $database->delete("{$sqlPrefix}ignoredUsers", array(
      'userId' => $user['userId'],
      'ignoredUserId' => $ignoredUserId,
    ));
  }
  break;

  case 'replace':
  $lists = explode(';', $request['roomLists']);

  foreach ($lists AS $list) {
    list($listName, $roomIds) = explode('=', $list);
    $roomIds = fim_arrayValidate(explode(',', $listIds), 'int');

    $queryParts['listSelect'] = array(
      'columns' => array(
        "{$sqlPrefix}roomListNames" => 'listId, listName',
      ),
      'conditions' => array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column', 'value' => 'listName',
            ),
            'right' => array(
              'type' => 'string', 'value' => $listName,
            ),
          ),
        ),
      )
    );

    $listData = $database->select(
      $queryParts['listSelect']['columns'],
      $queryParts['listSelect']['conditions']);
    $listData = $rooms->getAsArray('listId');


    $queryParts['roomSelect'] = array(
      'columns' => array(
        "{$sqlPrefix}rooms" => 'roomId, roomName, roomTopic, owner, defaultPermissions, parentalFlags, parentalAge, options, lastMessageId, lastMessageTime, messageCount',
      ),
      'conditions' => array(
        'both' => array(
          array(
            'type' => 'in',
            'left' => array(
              'type' => 'column', 'value' => 'roomId',
            ),
            'right' => array(
              'type' => 'array', 'value' => $roomIds,
            ),
          ),
        ),
      )
    );

    $roomData = $database->select(
      $queryParts['roomSelect']['columns'],
      $queryParts['roomSelect']['conditions']);
    $roomData = $rooms->getAsArray('roomId');

    foreach ($roomIds AS $roomId) {
      $database->delete("{$sqlPrefix}roomLists", array(
        'userId' => $user['userId'],
        'listId' => $listData[$listName]['listId'],
      ));

      if (fim_hasPermission($roomData[$roomId], $user, 'view')) {
        $this->insert("{$sqlPrefix}roomLists", array(
          'userId' => $user['userId'],
          'listId' => $listData[$listName]['listId'],
          'roomId' => $roomId,
        ));
      }
    }
  }
  break;
}


/* Plugin Hook Start */
($hook = hook('editIgnoreList_end') ? eval($hook) : '');
?>