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

  public function getRoom($roomId, $roomName = false, $cache = true) {
    global $sqlPrefix, $config, $user;

    $queryParts['roomSelect']['columns'] = array(
      "{$sqlPrefix}rooms" => 'roomId, roomName, roomTopic, owner, defaultPermissions, options, lastMessageId, lastMessageTime, messageCount',
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
      "{$sqlPrefix}censorLists" => 'listId, listName, listType, options',
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

  public function getCensorWord($wordId) {
    global $sqlPrefix, $config, $user;

    $queryParts['wordSelect']['columns'] = array(
      "{$sqlPrefix}censorWords" => array(
        'wordId' => 'wordId',
        'listId' => 'listId',
        'word' => 'word',
        'severity' => 'severity',
        'param' => 'param',
      ),
    );

    if ($wordId) {
      $queryParts['wordSelect']['conditions'] = array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'wordId'
            ),
            'right' => array(
              'type' => 'int',
              'value' => (int) $wordId,
            ),
          ),
        ),
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


  public function getBBCode($bbcodeId) {
    global $sqlPrefix, $config, $user;

    $queryParts['bbcodeSelect']['columns'] = array(
      "{$sqlPrefix}bbcode" => 'bbcodeId, bbcodeName, searchRegex, replacement, options',
    );

    if ($bbcodeId) {
      $queryParts['bbcodeSelect']['conditions'] = array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'bbcodeId'
            ),
            'right' => array(
              'type' => 'int',
              'value' => (int) $bbcodeId,
            ),
          ),
        ),
      );
    }
    else {
      return false;
    }

    $bbcodeData = $this->select(
      $queryParts['bbcodeSelect']['columns'],
      $queryParts['bbcodeSelect']['conditions'],
      false,
      1);
    return $bbcodeData->getAsArray(false);
  }


  public function getMessage($messageId) {
    global $sqlPrefix, $config, $user;

    $queryParts['messageSelect']['columns'] = array(
      "{$sqlPrefix}messages" => 'messageId, roomId, iv, salt, text, deleted',
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


  public function getFile($fileId) {
    global $sqlPrefix, $config, $user;

    $queryParts['fileSelect']['columns'] = array(
      "{$sqlPrefix}files" => 'fileId, fileName, fileType, creationTime, userId, source, rating, flags, deleted',
    );

    if ($fileId) {
      $queryParts['fileSelect']['conditions'] = array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'fileId'
            ),
            'right' => array(
              'type' => 'int',
              'value' => (int) $fileId,
            ),
          ),
        ),
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


  public function getRoomCensorLists($roomId) {
    global $user, $sqlPrefix, $slaveDatabase;

    $block = array();
    $unblock = array();

    if ($roomId > 0) {
      $listsActive = $slaveDatabase->select(
        array(
          "{$sqlPrefix}censorBlackWhiteLists" => 'status, roomId, listId',
        ),
        array(
          'both' => array(
            array(
              'type' => 'e',
              'left' => array(
                'type' => 'column',
                'value' => 'roomId',
              ),
              'right' => array(
                'type' => 'int',
                'value' => (int) $roomId,
              ),
            ),
          ),
        )
      );
      $listsActive = $listsActive->getAsArray();


      if (is_array($listsActive)) {
        if (count($listsActive) > 0) {
          foreach ($listsActive AS $active) {
            if ($active['status'] == 'unblock') {
              $unblock[] = $active['listId'];
            }
            elseif ($active['status'] == 'block') {
              $block[] = $active['listId'];
            }
          }
        }
      }
    }

    $queryParts['listsSelect']['columns'] = array(
      "{$sqlPrefix}censorLists" => 'listId, listName, listType, options',
    );
    $queryParts['listsSelect']['conditions'] = array(
      'both' => array(
        'either' => array(
          'both 1' => array(
            array(
              'type' => 'e',
              'left' => array(
                'type' => 'column',
                'value' => 'listType',
              ),
              'right' => array(
                'type' => 'string',
                'value' => 'white',
              ),
            ),
            array(
              'type' => 'notin',
              'left' => array(
                'type' => 'column',
                'value' => 'listId',
              ),
              'right' => array(
                'type' => 'array',
                'value' => (array) $unblock,
              ),
            ),
          ),
          'both 2' => array(
            array(
              'type' => 'e',
              'left' => array(
                'type' => 'column',
                'value' => 'listType',
              ),
              'right' => array(
                'type' => 'string',
                'value' => 'black',
              ),
            ),
            array(
              'type' => 'in',
              'left' => array(
                'type' => 'column',
                'value' => 'listId',
              ),
              'right' => array(
                'type' => 'array',
                'value' => (array) $block,
              ),
            ),
          ),
        ),
      ),
    );
    $queryParts['listsSelect']['sort'] = false;
    $queryParts['listsSelect']['limit'] = false;

/*    if ($roomOptions & 16) {
      $queryParts['listsSelect']['conditions']['both']['both'] = array(
        array(
          'type' => 'xor',
          'left' => array(
            'type' => 'column',
            'value' => 'options',
          ),
          'right' => array(
            'type' => 'int',
            'value' => 4,
          ),
        ),
      );
    }*/

    $lists = $slaveDatabase->select(
      $queryParts['listsSelect']['columns'],
      $queryParts['listsSelect']['conditions'],
      $queryParts['listsSelect']['sort'],
      $queryParts['listsSelect']['limit']
    );

    return $lists->getAsArray('listId');
  }


  public function getConfiguration($directive) {
    global $sqlPrefix, $config, $user;

    $queryParts['configSelect']['columns'] = array(
      "{$sqlPrefix}configuration" => 'directive, type, value',
    );

    if ($directive) {
      $queryParts['configSelect']['conditions'] = array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'directive'
            ),
            'right' => array(
              'type' => 'string',
              'value' => $directive,
            ),
          ),
        ),
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


  public function getPhrase($phraseName, $languageCode, $interfaceId) {
    global $sqlPrefix, $config, $user;

    $queryParts['phraseSelect']['columns'] = array(
      "{$sqlPrefix}phrases" => "phraseName, languageCode, interfaceId, text",
    );
    $queryParts['phraseSelect']['conditions'] = array(
      'both' => array(
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'phraseName',
          ),
          'right' => array(
            'type' => 'string',
            'value' => $phraseName,
          ),
        ),
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'languageCode',
          ),
          'right' => array(
            'type' => 'string',
            'value' => $languageCode,
          ),
        ),
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'interfaceId',
          ),
          'right' => array(
            'type' => 'int',
            'value' => $interfaceId,
          ),
        ),
      ),
    );
    $queryParts['phraseSelect']['sort'] = false;
    $queryParts['phraseSelect']['limit'] = 1;

    if (!$phraseName || !$languageCode || !$interfaceId) {
      return false;
    }

    $phraseData = $this->select(
      $queryParts['phraseSelect']['columns'],
      $queryParts['phraseSelect']['conditions'],
      $queryParts['phraseSelect']['sort'],
      $queryParts['phraseSelect']['limit']);
    return $phraseData->getAsArray(false);
  }


  public function getTemplate($templateName, $interfaceId) {
    global $sqlPrefix, $config, $user;

    $queryParts['templateSelect']['columns'] = array(
      "{$sqlPrefix}templates" => "templateName, interfaceId, data, vars, templateId",
    );
    $queryParts['templateSelect']['conditions'] = array(
      'both' => array(
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'templateName',
          ),
          'right' => array(
            'type' => 'string',
            'value' => $templateName,
          ),
        ),
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'interfaceId',
          ),
          'right' => array(
            'type' => 'int',
            'value' => $interfaceId,
          ),
        ),
      ),
    );
    $queryParts['templateSelect']['sort'] = false;
    $queryParts['templateSelect']['limit'] = 1;

    if (!$templateName || !$interfaceId) {
      return false;
    }

    $templateData = $this->select(
      $queryParts['templateSelect']['columns'],
      $queryParts['templateSelect']['conditions'],
      $queryParts['templateSelect']['sort'],
      $queryParts['templateSelect']['limit']);
    return $templateData->getAsArray(false);
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


  public function storeMessage($userData, $roomData, $messageText, $messageTextEncrypted, $encryptIV, $encryptSalt, $flag) {
    global $sqlPrefix, $config, $user, $permissionsCache, $generalCache;

    if ($roomData['options'] ^ 64) { // Off the record communication - messages are stored only in the cache.
      // Insert into permenant datastore.
      $this->insert("{$sqlPrefix}messages", array(
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


      // Update the messageIndex if appropriate
      $roomDataNew = $this->getRoom($roomData['roomId'], false, false); // Get the new room data.


      if ($roomDataNew['messageCount'] % $config['messageIndexCounter'] === 0) { // If the current messages in the room is divisible by the messageIndexCounter, insert into the messageIndex cache. Note that we are hoping this is because of the very last query which incremented this value, but it is impossible to know for certain (if we tried to re-order things to get the room data first, we still run this risk, so that doesn't matter; either way accuracy isn't critical).
        $this->insert("{$sqlPrefix}messageIndex", array(
          'roomId' => $roomData['roomId'],
          'interval' => (int) $roomDataNew['messageCount'],
          'messageId' => $messageId
        ), array(
          'messageId' => array(
            'type' => 'equation',
            'value' => '$messageId + 0',
          )
        )); error_log('333');
      }


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


      $lastDayCache = (int) $generalCache->get('fim3_lastDayCache');

      $currentTime = time();
      $lastMidnight = $currentTime - ($currentTime % $config['messageTimesCounter']); // Using some cool math (look it up if you're not familiar), we determine the distance from the last even day, then get the time of the last even day itself. This is the midnight referrence point.

      if ($lastDayCache < $lastMidnight) { // If the most recent midnight comes after the period at which the time cache was last updated, handle that.
        $this->insert("{$sqlPrefix}messageDates", array(
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


      // Increment the messages counter.
      $this->incrementCounter('messages');
    }


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
      'text' => $messageText,
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
  * ModLog container
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
    global $sqlPrefix, $config, $user;

    if ($this->insert("{$sqlPrefix}fulllog", array(
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

    asort($userList);

    $queryParts['columns'] = array(
      "{$sqlPrefix}privateRooms" => 'roomUsersString, roomUsersHash, roomUsersString, options',
    );

    $userCount = count($userList);

    $queryParts['conditions'] = array(
      'both' => array(
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'roomUsersHash',
          ),
          'right' => array(
            'type' => 'string',
            'value' => md5(implode(',', $userList)),
          ),
        ),
      ),
    );

    $privateRoom = $this->select($queryParts['columns'],
      $queryParts['conditions']);
    return $privateRoom->getAsArray(false);
  }

  public function storeKeyWords($words, $messageId, $userId, $roomId) {
    global $config, $sqlPrefix;

    $phraseData = $this->select(
      array(
        "{$sqlPrefix}searchPhrases" => 'phraseName, phraseId',
      )
    );
    $phraseData = $phraseData->getAsArray('phraseName');

    foreach ($words AS $piece) {
      if (!isset($phraseData[$piece])) {
        $this->insert("{$sqlPrefix}searchPhrases", array(
          'phraseName' => $piece,
        ));
        $phraseId = $this->insertId;
      }
      else {
        $phraseId = $phraseData[$piece]['phraseId'];
      }

      $this->insert("{$sqlPrefix}searchMessages", array(
        'phraseId' => (int) $phraseId,
        'messageId' => (int) $messageId,
        'userId' => (int) $userId,
        'roomId' => (int) $roomId,
      ));
    }
  }
}
?>