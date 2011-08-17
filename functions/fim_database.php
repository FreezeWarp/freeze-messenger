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


class fimDatabase extends database {

  public function getRoom($roomId, $roomName = false) {
    global $sqlPrefix, $config, $user;

    $queryParts['roomSelect']['columns'] = array(
      "{$sqlPrefix}rooms" => 'roomId, roomName, roomTopic, owner, defaultPermissions, options',
    );

    if ($roomId) {
      $queryParts['roomSelect']['conditions'] = array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'roomId'
            ),
            'right' => array(
              'type' => 'int',
              'value' => (int) $roomId,
            ),
          ),
        ),
      );
    }
    elseif ($roomName) {
      $queryParts['roomSelect']['conditions'] = array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'roomName'
            ),
            'right' => array(
              'type' => 'string',
              'value' => $roomName,
            ),
          ),
        ),
      );
    }
    else {
      return false;
    }


    $roomData = $this->select(
      $queryParts['roomSelect']['columns'],
      $queryParts['roomSelect']['conditions'],
      false,
      1);
    return $roomData->getAsArray(false);
  }


  public function getUser($userId, $userName = false) {
    global $sqlPrefix, $config, $user;

    $queryParts['userSelect']['columns'] = array(
      "{$sqlPrefix}users" => 'userId, userName, userFormatStart, userFormatEnd, userGroup, allGroups, socialGroups',
    );

    if ($userId) {
      $queryParts['userSelect']['conditions'] = array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'userId'
            ),
            'right' => array(
              'type' => 'int',
              'value' => (int) $userId,
            ),
          ),
        ),
      );
    }
    elseif ($userName) {
      $queryParts['userSelect']['conditions'] = array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'userName'
            ),
            'right' => array(
              'type' => 'string',
              'value' => $userName,
            ),
          ),
        ),
      );
    }
    else {
      return false;
    }


    $userData = $this->select(
      $queryParts['userSelect']['columns'],
      $queryParts['userSelect']['conditions'],
      false,
      1);
    return $userData->getAsArray(false);
  }


  public function getFont($fontId) {
    global $sqlPrefix, $config, $user;

    $queryParts['fontSelect']['columns'] = array(
      "{$sqlPrefix}fonts" => 'fontId, fontName',
    );

    if ($fontId) {
      $queryParts['fontSelect']['conditions'] = array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'fontId'
            ),
            'right' => array(
              'type' => 'int',
              'value' => (int) $fontId,
            ),
          ),
        ),
      );
    }
    else {
      return false;
    }

    $fontData = $this->select(
      $queryParts['fontSelect']['columns'],
      $queryParts['fontSelect']['conditions'],
      false,
      1);
    return $fontData->getAsArray(false);
  }


  public function getCensorList($listId) {
    global $sqlPrefix, $config, $user;

    $queryParts['listSelect']['columns'] = array(
      "{$sqlPrefix}lists" => array(
        'listId' => 'listId',
        'listName' => 'listName',
      ),
    );

    if ($listId) {
      $queryParts['listSelect']['conditions'] = array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'listId'
            ),
            'right' => array(
              'type' => 'int',
              'value' => (int) $listId,
            ),
          ),
        ),
      );
    }
    else {
      return false;
    }

    $listData = $this->select(
      $queryParts['listSelect']['columns'],
      $queryParts['listSelect']['conditions'],
      false,
      1);
    return $listData->getAsArray(false);
  }


  public function getMessage($messageId) {
    global $sqlPrefix, $config, $user;

    $queryParts['messageSelect']['columns'] = array(
      "{$sqlPrefix}messages" => 'messageId, roomId, iv, salt, htmlText, apiText, rawText, deleted',
    );

    if ($messageId) {
      $queryParts['messageSelect']['conditions'] = array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'messageId'
            ),
            'right' => array(
              'type' => 'int',
              'value' => (int) $messageId,
            ),
          ),
        ),
      );
    }
    else {
      return false;
    }

    $messageData = $this->select(
      $queryParts['messageSelect']['columns'],
      $queryParts['messageSelect']['conditions'],
      false,
      1);
    return $messageData->getAsArray(false);
  }


  public function markMessageRead($messageId, $userId) {
    global $sqlPrefix, $config, $user;

    if ($config['enableUnreadMessages']) {
      $this->delete("{$sqlPrefix}unreadMessages",array(
        'messageId' => $messageId,
        'userId' => $userId));
    }
  }


  public function createEvent($eventName, $userId, $roomId, $messageId, $param1, $param2, $param3) {
    global $sqlPrefix, $config, $user;

    if ($config['enableEvents']) {
      $this->insert("{$sqlPrefix}events", array(
        'eventName' => $eventName,
        'userId' => $userId,
        'roomId' => $roomId,
        'messageId' => $messageId,
        'param1' => $param1,
        'param2' => $param2,
        'param3' => $param3,
        'time' => $this->now(),
      ));
    }
  }


  public function storeMessage($userData, $roomData, $messageDataPlain, $messageDataEncrypted, $flag) {
    global $sqlPrefix, $config, $user, $permissionsCache;

    // Insert into permenant datastore.
    $this->insert("{$sqlPrefix}messages", array(
      'roomId' => (int) $roomData['roomId'],
      'userId' => (int) $userData['userId'],
      'rawText' => $messageDataEncrypted['rawText'],
      'htmlText' => $messageDataEncrypted['htmlText'],
      'apiText' => $messageDataEncrypted['apiText'],
      'salt' => $messageDataEncrypted['saltNum'],
      'iv' => $messageDataEncrypted['iv'],
      'ip' => $_SERVER['REMOTE_ADDR'],
      'flag' => $flag,
      'time' => $this->now(),
    ));
    $messageId = $this->insertId;


   // Insert into cache/memory datastore.
    $this->insert("{$sqlPrefix}messagesCached", array(
      'messageId' => (int) $messageId,
      'roomId' => (int) $roomData['roomId'],
      'userId' => (int) $userData['userId'],
      'userName' => $userData['userName'],
      'userGroup' => (int) $userData['userGroup'],
      'avatar' => $userData['avatar'],
      'profile' => $userData['profile'],
      'userFormatStart' => $userData['userFormatStart'],
      'userFormatEnd' => $userData['userFormatEnd'],
      'defaultFormatting' => $userData['defaultFormatting'],
      'defaultColor' => $userData['defaultColor'],
      'defaultHighlight' => $userData['defaultHighlight'],
      'defaultFontface' => $userData['defaultFontface'],
      'htmlText' => $messageDataPlain['htmlText'],
      'apiText' => $messageDataPlain['apiText'],
      'flag' => $flag,
      'time' => $this->now(),
    ));
    $messageId2 = $this->insertId;


    // Delete old messages from the cache, based on the maximum allowed rows.
    if ($messageId2 > $config['cacheTableMaxRows']) {
      $this->delete("{$sqlPrefix}messagesCached",
        array('id' => array(
          'cond' => 'lte',
          'value' => (int) ($messageId2 - $config['cacheTableMaxRows'])
        )
      ));
    }


    // Update room caches.
    $this->update("{$sqlPrefix}rooms", array(
      'lastMessageTime' => $this->now(),
      'lastMessageId' => $messageId,
      'messageCount' => array(
        'type' => 'equation',
        'value' => '$messageCount + 1',
      )
    ), array(
      'roomId' => $roomData['roomId'],
    ));


    // Update user caches
    $this->update("{$sqlPrefix}users", array(
      'messageCount' => array(
        'type' => 'equation',
        'value' => '$messageCount + 1',
      )
    ), array(
      'userId' => $userData['userId'],
    ));


    // Insert or update a user's room stats.
    $this->insert("{$sqlPrefix}roomStats", array(
      'userId' => $userData['userId'],
      'roomId' => $roomData['roomId'],
      'messages' => 1
    ), array(
      'messages' => array(
        'type' => 'equation',
        'value' => '$messages + 1',
      )
    ));


    // Increment the messages counter.
    $this->incrementCounter('messages');


    // If the contact is a private communication, create an event and add to the message unread table.
    if ($roomData['options'] & 16) {// error_log(print_r($permissionsCache[$roomData['roomId']]['user'],true));
      foreach ($permissionsCache[$roomData['roomId']]['user'] AS $sendToUserId => $permissionLevel) {
        if ($sendToUserId == $user['userId']) {
          continue;
        }
        else {
          $this->createEvent('missedMessage', $sendToUserId, $roomData['roomId'], $messageId, false, false, false); // name, user, room, message, p1, p2, p3

          if ($config['enableUnreadMessages']) {
            $this->insert("{$sqlPrefix}unreadMessages", array(
              'userId' => $sendToUserId,
              'senderId' => $userData['userId'],
              'roomId' => $roomData['roomId'],
              'messageId' => $messageId,
              'time' => $this->now(),
            ));
          }
        }
      }
    }


    // Return the ID of the inserted message.
    return $messageId;
  }


  /**
  * MySQL modLog container
  *
  * @param string $action
  * @param string $data
  * @return bool
  * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */

  public function modLog($action, $data) {
    global $sqlPrefix, $config, $user;

    if ($this->insert("{$sqlPrefix}modlog", array(
      'userId' => (int) $user['userId'],
      'ip' => $_SERVER['REMOTE_ADDR'],
      'action' => $action,
      'data' => $data,
    ))) {
      return true;
    }
    else {
      return false;
    }
  }


  public function incrementCounter($counterName, $incrementValue = 1) {
   global $sqlPrefix, $config;

    if ($this->update("{$sqlPrefix}counters", array(
      'counterValue' => array(
        'type' => 'equation',
        'value' => '$counterValue + ' . (int) $incrementValue,
      )
    ), array(
      'counterName' => $counterName,
    ))) {
      return true;
    }
    else {
      return false;
    }
  }


  public function getCounterValue($counterName) {
    global $sqlPrefix, $config;

    $queryParts['counterSelect']['columns'] = array(
      "{$sqlPrefix}counters" => 'counterName, counterValue',
    );
    $queryParts['counterSelect']['conditions'] = array(
      'both' => array(
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'messageName'
          ),
          'right' => array(
            'type' => 'string',
            'value' => (int) $counterName,
          ),
        ),
      ),
    );

    $counterData = $this->select(
      $queryParts['counterSelect']['columns'],
      $queryParts['counterSelect']['conditions'],
      false,
      1);
    $counterData = $counterData->getAsArray(false);

    return $counterData['counterValue'];
  }


  public function getPrivateRoom($userList) {
    global $sqlPrefix, $config;

    $queryParts['columns'] = array(
      "{$sqlPrefix}privateRooms" => 'roomId, userCount, user1, user2, user3, user4, user5, user6, user7, user8, user9, user10',
    );

    $userCount = count($userList);

    $queryParts['conditions'] = array(
      'both' => array(
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'userCount',
          ),
          'right' => array(
            'type' => 'int',
            'value' => $userCount,
          ),
        ),
      ),
    );

    foreach ($userList AS $userId) {
      for ($i = 1; $i <= $userCount; $i++) {
        $queryParts['conditions']['both']['either ' . $userId][] = array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'user' . $i,
          ),
          'right' => array(
            'type' => 'int',
            'value' => $userId,
          ),
        );
      }
    }

    $privateRoom = $this->select($queryParts['columns'],
      $queryParts['conditions']);
    return $privateRoom->getAsArray(false);
  }
}
?>