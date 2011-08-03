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


global $sqlPrefix, $config, $user;

class fimDatabase extends database {

  public function getRoom($roomId, $roomName = false) {
    $queryParts['roomSelect']['columns'] = array(
      "{$sqlPrefix}rooms" => array(
        'roomId' => 'roomId',
        'roomName' => 'roomName',
        'roomTopic' => 'roomTopic',
        'owner' => 'owner',
        'defaultPermissions' => 'defaultPermissions',
        'options' => 'options',
      ),
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


    $room = $this->select(
      $queryParts['roomSelect']['columns'],
      $queryParts['roomSelect']['conditions'],
      false,
      false,
      1
    );
    return $room->getAsArray(false);
  }


  public function getUser($userId, $userName = false) {
    $queryParts['userSelect']['columns'] = array(
      "{$sqlPrefix}users" => array(
        'userId' => 'userId',
        'userName' => 'userName',
        'userFormatStart' => 'userFormatStart',
        'userFormatEnd' => 'userFormatEnd',
        'userGroup' => 'userGroup',
        'allGroups' => 'allGroups',
        'socialGroups' => 'socialGroups',
      ),
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


    $user = $this->select(
      $queryParts['userSelect']['columns'],
      $queryParts['userSelect']['conditions'],
      false,
      false,
      1
    );
    return $user->getAsArray(false);
  }


  public function getFont($fontId) {
    $queryParts['fontSelect']['columns'] = array(
      "{$sqlPrefix}fonts" => array(
        'fontId' => 'fontId',
        'fontName' => 'fontName',
      ),
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

    $font = $this->select(
      $queryParts['fontSelect']['columns'],
      $queryParts['fontSelect']['conditions'],
      false,
      false,
      1
    );
    return $font->getAsArray(false);
  }


  public function getCensorList($listId) {
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

    $list = $this->select(
      $queryParts['listSelect']['columns'],
      $queryParts['listSelect']['conditions'],
      false,
      false,
      1
    );
    return $list->getAsArray(false);
  }


  public function getMessage($messageId) {
    $queryParts['messageSelect']['columns'] = array(
      "{$sqlPrefix}messages" => array(
        'messageId' => 'messageId',
        'roomId' => 'roomId',
        'iv' => 'iv',
        'salt' => 'salt',
        'htmlText' => 'htmlText',
        'apiText' => 'apiText',
        'rawText' => 'rawText',
        'deleted' => 'deleted',
      ),
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

    $message = $this->select(
      $queryParts['messageSelect']['columns'],
      $queryParts['messageSelect']['conditions'],
      false,
      false,
      1
    );
    return $message->getAsArray(false);
  }


  public function markMessageRead($messageId, $userId) {
    $this->delete("{$sqlPrefix}unreadMessages",array(
      'messageId' => $messageId,
      'userId' => $userId),
    "{$sqlPrefix}events");
  }


  public function createEvent($eventName, $userId, $roomId, $messageId, $param1, $param2, $param3) {
    $this->insert(array(
      'eventName' => $eventName,
      'userId' => $userId,
      'roomId' => $roomId,
      'messageId' => $messageId,
      'param1' => $param1,
      'param2' => $param2,
      'param3' => $param3),
    "{$sqlPrefix}events");
  }


  public function storeMessage($userData, $roomData, $messageDataPlain, $messageDataEncrypted, $flag) {
    // Insert into permenant datastore.
    $this->insert(array(
      'roomId' => (int) $roomData['roomId'],
      'userId' => (int) $userData['userId'],
      'rawText' => $messageDataEncrypted['rawText'],
      'htmlText' => $messageDataEncrypted['htmlText'],
      'apiText' => $messageDataEncrypted['apiText'],
      'salt' => $messageDataEncrypted['saltNum'],
      'iv' => $messageDataEncrypted['iv'],
      'ip' => $_SERVER['REMOTE_ADDR'],
      'flag' => $flag,
    ),"{$sqlPrefix}messages");
    $messageId = $this->insertId;


   // Insert into cache/memory datastore.
    $this->insert(array(
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
    ),"{$sqlPrefix}messagesCached");
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
    $this->update(array(
      'lastMessageTime' => '__TIME__',
      'lastMessageId' => $messageId,
    ), "{$sqlPrefix}rooms", array(
      'roomId' => $roomData['roomId'],
    ));


    // Insert or update a user's room stats.
    $this->insert(array(
      'userId' => $userData['userId'],
      'roomId' => $roomData['roomId'],
      'messages' => 1
    ), "{$sqlPrefix}roomStats", array(
      'messages' => array(
        'type' => 'equation',
        'value' => '$messages + 1',
      )
    ));


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

  function modLog($action, $data) {
    if ($this->insert(array(
      'userId' => (int) $user['userId'],
      'ip' => $_SERVER['REMOTE_ADDR'],
      'action' => $action,
      'data' => $data,
    ),"{$sqlPrefix}modlog")) {
      return true;
    }
    else {
      return false;
    }
  }
}
?>