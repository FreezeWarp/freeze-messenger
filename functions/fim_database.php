<?php
/* FreezeMessenger Copyright © 2014 Joseph Todd Parsons

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

  /** Run a query to obtain files.
   * Scans tables `files` and `fileVersions`. These two tables cannot be queried individually using fimDatabase.
   * Returns columns files.fileId, files.fileName, files.fileType, files.creationTime, files.userId, files.parentalAge, files.parentalFlags, files.roomIdLink, files.fileId, fileVersions.vfileId (= files.fileId), fileVersions.md5hash, fileVersions.sha256hash, fileVersions.size.
   *
   * @param array $users          An array of userIDs corresponding with file ownership.
   * @param array $files          An array of fileIDs.
   * @param array $fileParams     An associative array of additional file parameters, which may be added to in the future. Valid keys include:
   *                                array  ['fileTypes']       Array of file extensions (e.g. jpg).
   *                                int    ['creationTimeMin'] File's creation time must be after.
   *                                int    ['creationTimeMax'] File's creation time must be before.
   *                                string ['fileNameBlob']    String that must appear within the fileName.
   * @param array $rooms          An array of roomIDs corresponding with where a file was posted. Some files do not have a corresponding roomId
   * @param array $parentalParams An associative array of additional parental control parameters, which may be added to in the future. Valid keys include:
   *                                int ['ageMin']
   *                                int ['ageMax']
   * @param array $sort           A standard sort array.
   * @param int   $limit          Maximum number of results (or 0 for no limit).
   * @param int   $pagination     Result page if limit exceeded, starting at 1.
   *
   * @return bool|object|resource
   *
   * @TODO: Test filters for other file properties.
   */
  public function getFiles(
    $users = array(),
    $files = array(),
    $fileParams = array(),
    $rooms = array(),
    $parentalParams = array(),
    $sort = array('fileId' => 'asc'),
    $limit = 0,
    $pagination = 1)
  {
    $columns = array(
      "files"        => 'fileId, fileName, fileType, creationTime, userId, parentalAge, parentalFlags, roomIdLink',
      "fileVersions" => 'fileId vfileId, md5hash, sha256hash, size',
    );


    if (count($users) > 0) $conditions['both']['userId'] = $this->in($users);
    if (count($files) > 0) $conditions['both']['fileId'] = $this->in($files);
    if (count($rooms) > 0) $conditions['both']['roomLinkId'] = $this->in($rooms);

    if (isset($fileParams['fileTypes'])) $conditions['both']['fileType'] = $this->in($fileParams['fileTypes']);
    if (isset($fileParams['creationTimeMin'])) $conditions['both']['creationTime'] = $this->int($fileParams['creationTime'], 'gte');
    if (isset($fileParams['creationTimeMax'])) $conditions['both']['creationTime'] = $this->int($fileParams['creationTime'], 'lte');
    if (isset($fileParams['fileNameGlob'])) $conditions['both']['filelName'] = $this->type('string', $fileParams['fileNameGlob'], 'search');

    if (isset($parentalParams['ageMin'])) $conditions['both']['parentalAge'] = $this->int($parentalParams['ageMin'], 'gte');
    if (isset($parentalParams['ageMax'])) $conditions['both']['parentalAge'] = $this->int($parentalParams['ageMax'], 'lte');


    $conditions['both']['fileId'] = $this->col('vfileId');


    if (isset($fileParams['sizeMin'])) $conditions['both']['size'] = $this->int($fileParams['size'], 'gte');
    if (isset($fileParams['sizeMax'])) $conditions['both']['size'] = $this->int($fileParams['size'], 'lte');


    return $this->select($columns, $conditions, $sort);
  }


  public function getRoomLists($user, $roomLists = array(), $sort = array('listId' => 'asc')) {
    $columns = array(
      $this->sqlPrefix . "roomLists" => 'listId, userId, listName, options',
      $this->sqlPrefix . "roomListRooms" => 'listId llistId, roomId lRoomid',
    );

    $conditions['both'] = array(
      'userId' => $this->int($user['userId']),
      'llistId' => $this->col('listId'),
    );

    if (count($roomLists) > 0) {
      $conditions['both']['listId'] = $database->in($roomLists1);
    }

    return $this->select($columns, $conditions, $sort);
  }



  public function getActiveUsers($onlineThreshold, $rooms = array(), $users = array(), $sort = array('userName' => 'asc'), $limit = false, $pagination = false) {
    $columns = array(
      $this->sqlPrefix . "rooms" => 'roomId, roomName, roomTopic, defaultPermissions',
    );

    $columns = array(
      $this->sqlPrefix . "ping" => 'status, typing, time ptime, roomId proomId, userId puserId',
      $this->sqlPrefix . "rooms" => 'roomId',
      $this->sqlPrefix . "users" => 'userId, userName, userFormatStart, userFormatEnd, userGroup, socialGroups, typing, status',
    );

    if (count($rooms) > 0) $conditions['both']['roomId'] = $this->in($rooms);

    $conditions['both'] = array(
      'roomid' => $this->col('proomid'),
      'puserid' => $this->col('userid'),
      'ptime' => $this->int(time() - $onlineThreshold, 'gte')
    );


    /* Modify Query Data for Directives */
    if (count($users) > 0) {
      $conditions['both']['puserId'] = $this->in($users);
    }


    return $this->select($columns, $conditions, $sort);
  }



  public function getAllActiveUsers($time, $threshold, $users = array(), $sort = array('userName' => 'asc'), $limit = false, $pagination = false) {
    $columns = array(
      $this->sqlPrefix . "users" => 'userId, userName, userFormatStart, userFormatEnd, userGroup, socialGroups',
      //"{$sqlPrefix}rooms" => 'roomName, roomId, defaultPermissions, owner, options',
      $this->sqlPrefix . "rooms" => 'roomName, roomId, owner, options, defaultPermissions, parentalAge, parentalFlags',
      $this->sqlPrefix . "ping" => 'time ptime, userId puserId, roomId proomId, typing, status',
    );


    $conditions['both'] = array(
      'userId' => $this->col('puserId'),
      'roomId' => $this->col('proomId'),
      'ptime' => $this->int($time - $threshold, 'lt'),
    );


    /* Modify Query Data for Directives */
    if (count($users) > 0) {
      $conditions['both']['puserId'] = $this->in($users);
    }


    /* Get Active Users */
    return $this->select($columns, $conditions, $sort);
  }


  /* getMessages is by far the most advanced set of database calls in the whole application, and is still in need of much fine-tuning. The mesagEStream file uses its own query and must be teste seerately. */
  public function getMessages($rooms = array(),
    $messages = array(),
    $users = array(),
    $messageTextGlob = '',
    $showDeleted = true,
    $messageIdConstraints = array(),
    $archive = false,
    $longPolling = false,
    $sort = array('messageId' => 'asc'),
    $limit = false
  ) {

    global $config;


    /* Create a $messages list based on search parameter. */
    if (strlen($messageTextGlob) > 0 && $archive) {
      $searchArray = explode(',', $messageTextGlob);

      foreach ($searchArray AS $searchVal) {
        $searchArray2[] = str_replace(
          array_keys($config['searchWordConverts']),
          array_values($config['searchWordConverts']),
          $searchVal
        );
      }

      /* Establish Base Data */
      $columns = array(
        $this->sqlPrefix . "searchPhrases" => 'phraseName, phraseId pphraseId',
        $this->sqlPrefix . "searchMessages" => 'phraseId mphraseId, messageId, userId, roomId'
      );

      $conditions['both']['mphraseId'] = $this->col('pphraseId');


      /* Apply User and Room Filters */

      if (count($rooms) > 1)
        $conditions['both']['roomId'] = $this->in((array) $rooms);

      if (count($users) > 1)
        $conditions['both']['userId'] = $this->in((array) $users);


      /* Determine Whether to Use the Fast or Slow Algorithms */
      if (!$config['fullTextArchive']) { // Original, Fastest Algorithm
        $conditions['both']['phraseName'] = $this->in((array) $searchArray2);
      }
      else { // Slower Algorithm
        foreach ($searchArray2 AS $phrase)
          $conditions['both']['either'][]['phraseName'] = '*' . $phrase . '*';
      }


      /* Run the Query */
      $searchMessageIds = $this->select($columns, $conditions, $sort)->getAsArray('messageId');

      $searchMessages = array_keys($searchMessageIds);


      /* Modify the Request Filter for Messages */
      if ($searchMessages) $messages = fim_arrayValidate($searchMessages, 'int', true);
      else                 $messages = array(0); // This is a fairly dirty approach, but it does work for now. TODO
    }


    /* Query via the Archive */
    if ($archive) {
      $columns = array(
        $this->sqlPrefix . "messages" => 'messageId, time, iv, salt, roomId, userId, deleted, flag, text',
        $this->sqlPrefix . "users" => 'userId muserId, userName, userGroup, socialGroups, userFormatStart, userFormatEnd, avatar, defaultColor, defaultFontface, defaultHighlight, defaultFormatting'
      );

      $conditions['both']['muserId'] = $this->col('userId');
    }


    /* Access the Stream */
    else {
      $columns = array(
        $this->sqlPrefix . "messagesCached" => "messageId, roomId, time, flag, userId, userName, userGroup, socialGroups, userFormatStart, userFormatEnd, avatar, defaultColor, defaultFontface, defaultHighlight, defaultFormatting, text",
      );
    }



    /* Modify Query Data for Directives */
    if (isset($messageIdConstraints['messageIdMax']))
      $conditions['both']['messageId'] = $this->val('int', $request['messageIdMax'], 'lte');

    if (isset($messageIdConstraints['messageIdMin']))
      $conditions['both']['messageId'] = $this->val('int', $request['messageIdMin'], 'gte');

    if (isset($messageIdConstraints['messageDateMax']))
      $conditions['both']['time'] = $this->val('int', $request['messageDateMax'], 'lte');

    if (isset($messageIdConstraints['messageDateMin']))
      $conditions['both']['time'] = $this->val('int', $request['messageDateMin'], 'gte');

    if (isset($messageIdConstraints['messageIdStart'])) {
      $conditions['both']['messageId'] = $this->val('int', $request['messageIdStart'], 'gte');
      $conditions['both']['messageId b'] = $this->val('int', $request['messageIdStart'] + $request['messageLimit'], 'lt');
    }
    elseif (isset($messageIdConstraints['messageIdEnd'])) {
      $conditions['both']['messageId'] = $this->val('int', $request['messageIdEnd'], 'lte');
      $conditions['both']['messageId b'] = $this->val('int', $request['messageIdEnd'] - $request['messageLimit'], 'gt');
    }

    if ($showDeleted === true && $archive === true)
      $conditions['both']['deleted'] = $this->bool(false);

    if (count($messages) > 0) // Overrides all other message ID parameters.
      $conditions['both']['messageId'] = $this->in($messages);

    if (count($users) > 0)
      $conditions['both']['userId'] = $this->in($user);

    if (count($rooms) > 0)
      $conditions['both']['roomId'] = $this->in($rooms);


    $messages = $this->select($columns, $conditions, $sort);


    if ($longPolling) {
      $longPollingRetries = 0;

      while (!$messages) {
        $longPollingRetries++;

        $messages = $this->select($columns, $conditions, $sort);

        if ($longPollingRetries <= $config['longPollingMaxRetries']) { sleep($config['longPollingWait']); }
        else break;
      }
    }


    return $messages;
  }


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
  	  $conditions['both']['!options'] = $this->bitChange($conditions['both']['!options'], 8, 'remove'); // TODO: Permission?

  	if (count($rooms) > 0)
  	  $conditions['both']['roomId'] = $this->type('array', $rooms, 'in');

  	if ($globNameSearch)
  	  $conditions['both']['roomName'] = $this->type('string', $globNameSearch, 'search');


    $this->roomType = 'normal';


  	// Perform Query
  	return $this->select(
  	  $columns,
  	  $conditions,
  	  $sort);
  }


  public function getRoom($roomId) {
    $roomData = $this->getRooms(array($roomId))->getAsArray(true);
    $roomData = $roomData[$roomId];
    $roomData['type'] = 'normal'; // TODO

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



  /**
   * Sends a message. Requires the database to be active.
   *
   * @param string messageText - Text of message.
   * @param string messageFlag - Flag of message, used by clients to automatically display URLs, images, etc.
   * @param string userData - The data of the user sending the message. (This is not validated with the current user, and is left up to plugins).
   * @param string roomData - The data of the room. Must be fully populated.
   *
   *
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  public function sendMessage($messageText, $messageFlag, $userData, $roomData) {
    $messageParse = new messageParse($messageText, $messageFlag, $userData, $roomData);

    $messageText = $messageParse->getParsed();
    list($messageTextEncrypted, $iv, $saltNum) = $messageParse->getEncrypted();

    $messageId = $this->storeMessage($userData, $roomData, $messageText, $messageTextEncrypted, $iv, $saltNum, $messageFlag);

    $keyWords = $messageParse->getKeyWords();
    $this->storeKeyWords($keyWords, $messageId, $userData['userId'], $roomData['roomId']);

//  $database->storeUnreadMessage();
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