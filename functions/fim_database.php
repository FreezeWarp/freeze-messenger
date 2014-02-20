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


class fimDatabase extends databaseSQL {


  public function getPermissions($rooms = array()) {
    // Modify Query Data for Directives (First for Performance)
    $columns = array(
      $this->sqlPrefix . "roomPermissions" => 'roomId, attribute, param, permissions',
    );

    if (count(rooms) > 0)
      $conditions['both']['roomId'] = $this->in((array) $rooms);

    return $permissionsDatabase = $this->select($columns);
  }

  public function getKicks ($users = array(), $rooms = array(), $kickers = array(), $sort = array('roomId' => 'asc', 'userId' => 'asc'), $limit, $pagination) {
    $columns = array(
      $this->sqlPrefix . "kicks" => 'kickerId kkickerId, userId kuserId, roomId kroomId, length klength, time ktime',
      $this->sqlPrefix . "users user" => 'userId, userName, userFormatStart, userFormatEnd',
      $this->sqlPrefix . "users kicker" => 'userId kickerId, userName kickerName, userFormatStart kickerFormatStart, userFormatEnd kickerFormatEnd',
      $this->sqlPrefix . "rooms" => 'roomId, roomName, owner, options, defaultPermissions',
    );


    // Modify Query Data for Directives (First for Performance)
    if (count($users) > 0)
      $conditions['both']['kuserId'] = $this->in((array) $users);

    if (count($rooms) > 0)
      $conditions['both']['roomId'] = $this->in((array) $rooms);

    if (count($kickers) > 0)
      $conditions['both']['kuserId'] = $this->in((array) $users);


    // Conditions
    $conditions = array(
      'both' => array(
        'kuserId' => $this->col('userId'),
        'kroomId' => $this->col('roomId'),
        'kkickerId' => $this->col('kickerId')
      ),
    );


    return $this->select($columns, $conditions, $sort);
  }

  /*
   * @TODO Limit handling (OFFSET = limit * pagination). 
   */
  public function getRooms($rooms = array(), $showDeleted = false, $globNameSearch = false, $limit = false, $pagination = 0, $sort = array('roomId' => 'asc'), $slave = false) {
  	// Defaults
  	$columns = array($this->sqlPrefix . "rooms" =>
  	  'roomId, roomName, roomTopic, owner, defaultPermissions, parentalFlags, parentalAge, options, lastMessageId, lastMessageTime, messageCount');
    $conditions = array(
      'both' => array(
  	    '!options' => $this->int(8, 'bAnd')
  	  ));
  	
	  	
  	// Modify Query Data for Directives
  	if ($showDeleted)
  	  $conditions['both']['!options'] = $this->bitChange($queryParts['roomSelect']['conditions']['both']['!options'], 8, 'remove'); // TODO: Permission?
  	
  	if (count($rooms) > 0)
  	  $conditions['both']['roomId'] = $this->type('array', $rooms, 'in');
  	
  	if ($globNameSearch)
  	  $conditions['both']['roomName'] = $this->type('string', $globNameSearch, 'search');
  	 	

  	// Perform Query
  	return $this->select(
  	  $columns,
  	  $conditions,
  	  $sort);
  }

  /* TODO: Alias for getRooms? */
  public function getRoom($roomIds, $roomName = false, $cache = true) {
    global $config, $user;
    

/*    if (substr($roomId, 0, 1) === 'o') { // OTR Room
      $queryParts['roomSelect']['columns'] = array(
        "{$sqlPrefix}privateRooms" => array(
          'uniqueId' => 'roomId'.
          'roomUsersList',
          'roomUsersHash',
          'options',
        ),
      );

      $queryParts['roomSelect']['conditions'] = array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'uniqueId'
            ),
            'right' => array(
              'type' => 'int',
              'value' => substr($roomId, 1),
            ),
          ),
        ),
      );

      $roomType = 'otr'; // We return this because it will be easier to change the roomId schema.
    }
    else*/
    
    /* Private Rooms - These Are "Special" Rooms That Don't Conform to Standard Crap
     *
     * A Few Notes:
     * The $generalCache->getRooms() call must understand these rooms properly, reading the $roomType to be either "private" or "general".
     * These rooms exist in the "privateRooms" table.
     * These rooms must not be mass-managed. They must be read using the getPrivateRoom.php API, which transparently creates a private room if one did not already exist. Certain APIs may use this function to query the room information (getMessages.php is the primary one), but none may "list" multiple private rooms, nor query information for multiple private rooms. As a result, this function will _only_ support reading a single private room at any time. */
    
    if (is_int($roomIds) && substr($roomIds, 0, 1) === 'p') { // Private Room
      $queryParts['roomSelect']['columns'] = array(
        $this->sqlPrefix . "privateRooms" => 'uniqueId roomId, roomUsersList, roomUsersHash, options, lastMessageId, lastMessageTime, messageCount'
      );

      $queryParts['roomSelect']['conditions'] = array(
        'both' => array('roomId' => $this->int(substr($roomId, 1))),
      );

      $roomType = 'private'; // We return this because it will be easier to change the roomId schema.
    }
    
    /* Room Name Handling
     *
     * This function supports querying rooms by room name in rare circumstances. These must be queried one-at-a-time, and should be avoided whenever possible (the main use is for determining if a room by a certain name already exists, though other purposes could in theory exist). */
    elseif (false !== $roomName) {
    
    }
    
    /* Room ID Handling
     *
     * An IN clause is used to support multiple rooms. If a single roomId was specified, a flat array will be returned; otherwise, an array using roomId as an index will be used. */
    else {
      $queryParts['roomSelect']['columns'] = array(
        $this->sqlPrefix . "rooms" => 'roomId, roomName, roomTopic, owner, defaultPermissions, parentalFlags, parentalAge, options, lastMessageId, lastMessageTime, messageCount',
      );

      if ($roomId) {
        $queryParts['roomSelect']['conditions'] = array(
          'both' => array('roomId' => $this->in($roomIds)),
        );
      }
      elseif ($roomName) {
        $queryParts['roomSelect']['conditions'] = array(
          'both' => array('roomName' => $this->str($roomName)),
        );
      }
      else {
        return false;
      }

      $roomType = 'normal'; // We return this because it will be easier to change the roomId schema.
    }
    
    $roomData = $this->select(
      $queryParts['roomSelect']['columns'],
      $queryParts['roomSelect']['conditions'],
      false,
      1);
    $roomData = $roomData->getAsArray(false);
    $roomData['type'] = $roomType;

    return $roomData;
  }


  public function getUser($userId, $userName = false) {
    global $config, $user;

    $queryParts['userSelect']['columns'] = array(
      $this->sqlPrefix . "users" => 'userId, userName, profile, userFormatStart, userFormatEnd, userGroup, allGroups, socialGroups, parentalAge, parentalFlags, adminPrivs, defaultFormatting, defaultColor, defaultHighlight, defaultFontface',
    );

    if ($userId) {
      $queryParts['userSelect']['conditions'] = array(
        'both' => array('userId' => $this->int($userId)),
      );
    }
    elseif ($userName) {
      $queryParts['userSelect']['conditions'] = array(
        'both' => array('userName' => $this->str($userName)),
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

  public function getCensorLists($lists = array(), $rooms = array(), $sort = array('listName' => 'asc'), $limit = false, $pagination = false) {
    $columns = array(
      $this->sqlPrefix . "censorLists" => 'listId, listName, listType, options',
    );

    $conditions = array();


    /* Modify Query Data for Directives */
    if (count($lists) > 0) {
      $conditions['both']['listId'] = $this->in((array) $lists);
    }


    // Extend to determine which lists are active in rooms.
    if (count($rooms) > 0) {
      $columns = array(
        $this->sqlPrefix . "censorBlackWhiteLists" => 'status, roomId, listId rlistId',
      );

      $conditions['both']['roomId'] = $this->in((array) $rooms);
      $conditions['both']['listId'] = $this->col('rlistId');
    }


    return $this->select($columns, $conditions, $sort);
  }


  public function getCensorWords($words) {
    $columns = array(
      $this->sqlPrefix . "censorWords" => 'listId, word, severity, param',
    );

    return $this->select($columns);
  }


  public function getCensorList($listId) {
    throw new Exception('Deprecated');
    global $config, $user;

    $queryParts['listSelect']['columns'] = array(
      $this->sqlPrefix . "censorLists" => 'listId, listName, listType, options',
    );

    if ($listId) {
      $queryParts['listSelect']['conditions'] = array(
        'both' => array('listId' => $this->int($listId)),
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
  

  public function getCensorWord($wordId) { // TODO

    throw new Exception('Deprecated');
    global $config, $user;

    $queryParts['wordSelect']['columns'] = array(
      $this->sqlPrefix . "censorWords" => 'wordId, listId, word, severity, param',
    );

    if ($wordId) {
      $queryParts['wordSelect']['conditions'] = array(
        'both' => array('wordId' => $this->int($wordId)),
      );
    }
    else {
      return false;
    }

    $wordData = $this->select(
      $queryParts['wordSelect']['columns'],
      $queryParts['wordSelect']['conditions'],
      false,
      1);
    return $wordData->getAsArray(false);
  }


  public function getMessage($messageId) {
    global $config, $user;

    $queryParts['messageSelect']['columns'] = array(
      $this->sqlPrefix . "messages" => 'messageId, roomId, iv, salt, text, deleted',
    );

    if ($messageId) {
      $queryParts['messageSelect']['conditions'] = array(
        'both' => array('messageId' => $this->int($messageId)),
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


  public function getFile($fileId) {
    global $config, $user;

    $queryParts['fileSelect']['columns'] = array(
      $this->sqlPrefix . "files" => 'fileId, fileName, fileType, creationTime, userId, source, rating, flags, deleted',
    );

    if ($fileId) {
      $queryParts['fileSelect']['conditions'] = array(
        'both' => array('fileId' => $this->int($fileId)),
      );
    }
    else {
      return false;
    }

    $fileData = $this->select(
      $queryParts['fileSelect']['columns'],
      $queryParts['fileSelect']['conditions'],
      false,
      1);
    return $fileData->getAsArray(false);
  }


  public function getRoomCensorLists($roomId) { // TODO: Cache
    global $user, $slaveDatabase, $censorListsCache;

    $block = array();
    $unblock = array();
    $lists = array();

    if ($roomId > 0) {
      // We will need to get these fresh every time for the most part, as it is not easily cached (just as rooms themselves are not easily cached).
      $listsActive = $slaveDatabase->select(
        array(
          $this->sqlPrefix . "censorBlackWhiteLists" => 'status, roomId, listId',
        ),
        array(
          'both' => array('roomId' => $this->int($roomId)),
        )
      );
      $listsActive = $listsActive->getAsArray();


      if (is_array($listsActive)) {
        if (count($listsActive) > 0) {
          foreach ($listsActive AS $active) {
            if ($active['status'] == 'unblock') $unblock[] = $active['listId'];
            elseif ($active['status'] == 'block') $block[] = $active['listId'];
          }
        }
      }
    }

    foreach ($censorListsCache['listId'] AS $listId => $censorList) {
      $lists[$listId] = $censorList;
    }

    return $lists;
  }


  public function getConfiguration($directive) {
    global $config, $user;

    $queryParts['configSelect']['columns'] = array(
      $this->sqlPrefix . "configuration" => 'directive, type, value',
    );

    if ($directive) {
      $queryParts['configSelect']['conditions'] = array(
        'both' => array('directive' => $this->str($directive)),
      );
    }
    else {
      return false;
    }

    $configData = $this->select(
      $queryParts['configSelect']['columns'],
      $queryParts['configSelect']['conditions'],
      false,
      1);
    return $configData->getAsArray(false);
  }


  public function markMessageRead($messageId, $userId) {
    global $config, $user;

    if ($config['enableUnreadMessages']) {
      $this->delete($this->sqlPrefix . "unreadMessages",array(
        'messageId' => $messageId,
        'userId' => $userId
      ));
    }
  }


  public function createEvent($eventName, $userId, $roomId, $messageId, $param1, $param2, $param3) {
    global $config, $user;

    if ($config['enableEvents']) {
      $this->insert($this->sqlPrefix . "events", array(
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


  public function storeMessage($userData, $roomData, $messageText, $messageTextEncrypted, $encryptIV, $encryptSalt, $flag) {
    global $config, $user, $generalCache;

    if (!isset($roomData['options'], $roomData['roomId'], $roomData['roomName'], $roomData['type'])) throw new Exception('database->storeMessage requires roomData[options], roomData[roomId], roomData[roomName], and roomData[type].');
    if (!isset($userData['userId'], $userData['userName'], $userData['userGroup'], $userData['avatar'], $userData['profile'], $userData['userFormatStart'], $userData['userFormatEnd'], $userData['defaultFormatting'], $userData['defaultColor'], $userData['defaultHighlight'], $userData['defaultFontface'])) throw new Exception('database->storeMessage requires userData[userId], userData[userName], userData[userGroup], userData[avatar]. userData[profile], userData[userFormatStart], userData[userFormatEnd], userData[defaultFormatting], userData[defaultColor], userData[defaultHighlight], and userData[defaultFontface]');


    // Insert into permenant datastore.
    $this->insert($this->sqlPrefix . "messages", array(
      'roomId' => (int) $roomData['roomId'],
      'userId' => (int) $userData['userId'],
      'text' => $messageTextEncrypted,
      'textSha1' => sha1($messageText),
      'salt' => $encryptSalt,
      'iv' => $encryptIV,
      'ip' => $_SERVER['REMOTE_ADDR'],
      'flag' => $flag,
      'time' => $this->now(),
    ));
    $messageId = $this->insertId;


    // Update room caches.
    $this->update($this->sqlPrefix . "rooms", array(
      'lastMessageTime' => $this->now(),
      'lastMessageId' => $messageId,
      'messageCount' => array(
        'type' => 'equation',
        'value' => '$messageCount + 1',
      )
    ), array(
      'roomId' => $roomData['roomId'],
    ));


    // Update the messageIndex if appropriate
    $roomDataNew = $this->getRoom($roomData['roomId'], false, false); // Get the new room data.

    if ($roomDataNew['messageCount'] % $config['messageIndexCounter'] === 0) { // If the current messages in the room is divisible by the messageIndexCounter, insert into the messageIndex cache. Note that we are hoping this is because of the very last query which incremented this value, but it is impossible to know for certain (if we tried to re-order things to get the room data first, we still run this risk, so that doesn't matter; either way accuracy isn't critical).
      $this->insert($this->sqlPrefix . "messageIndex", array(
        'roomId' => $roomData['roomId'],
        'interval' => (int) $roomDataNew['messageCount'],
        'messageId' => $messageId
      ), array(
        'messageId' => array(
          'type' => 'equation',
          'value' => '$messageId + 0',
        )
      ));
    }


    // Update user caches
    $this->update($this->sqlPrefix . "users", array(
      'messageCount' => array(
        'type' => 'equation',
        'value' => '$messageCount + 1',
      )
    ), array(
      'userId' => $userData['userId'],
    ));


    // Insert or update a user's room stats.
    $this->insert($this->sqlPrefix . "roomStats", array(
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


    // Update the messageDates if appropriate
    $lastDayCache = (int) $generalCache->get('fim3_lastDayCache');

    $currentTime = time();
    $lastMidnight = $currentTime - ($currentTime % $config['messageTimesCounter']); // Using some cool math (look it up if you're not familiar), we determine the distance from the last even day, then get the time of the last even day itself. This is the midnight referrence point.

    if ($lastDayCache < $lastMidnight) { // If the most recent midnight comes after the period at which the time cache was last updated, handle that.
      $this->insert($this->sqlPrefix . "messageDates", array(
        'time' => $lastMidnight,
        'messageId' => $messageId
      ), array(
        'messageId' => array(
          'type' => 'equation',
          'value' => '$messageId + 0',
        )
      ));

      $generalCache->set('fim3_lastDayCache', $lastMidnight); // Update the quick cache.
    }


   // Insert into cache/memory datastore.
    $this->insert($this->sqlPrefix . "messagesCached", array(
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
      'text' => $messageText,
      'flag' => $flag,
      'time' => $this->now(),
    ));
    $messageId2 = $this->insertId;


    // Delete old messages from the cache, based on the maximum allowed rows.
    if ($messageId2 > $config['cacheTableMaxRows']) {
      $this->delete($this->sqlPrefix . "messagesCached",
        array('id' => array(
          'cond' => 'lte',
          'value' => (int) ($messageId2 - $config['cacheTableMaxRows'])
        )
      ));
    }


    // If the contact is a private communication, create an event and add to the message unread table.
    if ($roomData['type'] === 'private') {
      foreach ($generalCache->getPermissions($roomData['roomId'], 'user') AS $sendToUserId => $permissionLevel) {
        if ($sendToUserId == $user['userId']) {
          continue;
        }
        else {
          $this->createEvent('missedMessage', $sendToUserId, $roomData['roomId'], $messageId, false, false, false);

          if ($config['enableUnreadMessages']) {
            $this->insert($this->sqlPrefix . "unreadMessages", array(
              'userId' => $sendToUserId,
              'senderId' => $userData['userId'],
              'senderName' => $userData['userName'],
              'senderFormatStart' => $userData['userFormatStart'],
              'senderFormatEnd' => $userData['userFormatEnd'],
              'roomId' => $roomData['roomId'],
              'roomName' => $roomData['roomName'],
              'messageId' => $messageId,
              'time' => $this->now(),
            ), array(
              'senderId' => $userData['userId'],
              'senderName' => $userData['userName'],
              'senderFormatStart' => $userData['userFormatStart'],
              'senderFormatEnd' => $userData['userFormatEnd'],
              'roomName' => $roomData['roomName'],
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
  * ModLog container
  *
  * @param string $action
  * @param string $data
  * @return bool
  * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */

  public function modLog($action, $data) {
    global $config, $user;

    if (!isset($user['userId'])) throw new Exception('database->modLog requires user[userId]');

    if ($this->insert($this->sqlPrefix . "modlog", array(
      'userId' => (int) $user['userId'],
      'ip' => $_SERVER['REMOTE_ADDR'],
      'action' => $action,
      'data' => $data,
      'time' => $this->now(),
    ))) {
      return true;
    }
    else {
      return false;
    }
  }


  /**
  * Fulllog container
  *
  * @param string $action
  * @param array $data
  * @return bool
  * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */

  public function fullLog($action, $data) {
    global $config, $user;

    if ($this->insert($this->sqlPrefix . "fulllog", array(
      'user' => json_encode($user),
      'server' => json_encode($_SERVER),
      'action' => $action,
      'time' => $this->now(),
      'data' => json_encode($data),
    ))) {
      return true;
    }
    else {
      return false;
    }
  }


  public function incrementCounter($counterName, $incrementValue = 1) {
   global $config;

    if ($this->update($this->sqlPrefix . "counters", array(
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
    global $config;

    $queryParts['counterSelect']['columns'] = array(
      $this->sqlPrefix . "counters" => 'counterName, counterValue',
    );
    $queryParts['counterSelect']['conditions'] = array(
      'both' => array(
        'counterName' => $this->str($counterName),
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
    global $config;

    if (!is_array($userList)) throw new Exception('userList is not an array in getPrivateRoom');
    elseif (count($userList) < 1) throw new Exception('userList is empty in getPrivateRoom');

    asort($userList);

    $queryParts['columns'] = array(
      $this->sqlPrefix . "privateRooms" => 'uniqueId, roomUsersList, roomUsersHash, options, lastMessageTime, lastMessageId, messageCount',
    );

    $userCount = count($userList);

    $queryParts['conditions'] = array(
      'both' => array(
        'roomUsersHash' => $this->str(md5(implode(',', $userList))),
      ),
    );

    $privateRoom = $this->select($queryParts['columns'],
      $queryParts['conditions']);
    return $privateRoom->getAsArray(false);
  }
  

  public function storeKeyWords($words, $messageId, $userId, $roomId) {
    global $config;

    $phraseData = $this->select(
      array(
        $this->sqlPrefix . "searchPhrases" => 'phraseName, phraseId',
      )
    );
    $phraseData = $phraseData->getAsArray('phraseName');

    foreach (array_unique($words) AS $piece) {
      if (!isset($phraseData[$piece])) {
        $this->insert($this->sqlPrefix . "searchPhrases", array(
          'phraseName' => $piece,
        ));
        $phraseId = $this->insertId;
      }
      else {
        $phraseId = $phraseData[$piece]['phraseId'];
      }

      $this->insert($this->sqlPrefix . "searchMessages", array(
        'phraseId' => (int) $phraseId,
        'messageId' => (int) $messageId,
        'userId' => (int) $userId,
        'roomId' => (int) $roomId,
      ));
    }
  }
  
  /* Originally from fim_general.php TODO */
//  protected function explodeEscaped($delimiter, $string, $escapeChar = '\\') {
//    $string = str_replace($escapeChar . $escapeChar, fim_encodeEntities($escapeChar), $string);
//    $string = str_replace($escapeChar . $delimiter, fim_encodeEntities($delimiter), $string);
//    return array_map('fim_decodeEntities', explode($delimiter, $string));
//  }
}
?>