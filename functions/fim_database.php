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

/* Design Rational
 * I wanted to move all SELECT logic into this file so that it could be more easily maintained. This seemed fine at first, but then it became clear that in order to do so effectively, I would need several, hard to organise parameters for each and every function, which would surely change in the future. This bugged me. Named parameters would work just fine, but PHP doesn't have those. I _do not_ like the array() syntax as a matter of principle, but it is _far_ more practical. Plus, calls can easily use the shorthand array syntax if they aren't worried about old versions -- for now I am, but that could change.
 * So, yeah, unfortunately this will be using ugly, ugly arrays. I felt this was the best compromise, but I'm not exactly happy about it. It's hard to document, etc, etc.
 */

/*
 * @TODO Limit handling (OFFSET = limit * pagination).
 */

class fimDatabase extends databaseSQL
{


  /**
   * Run a query to obtain users who appear "active."
   * Scans table `ping`, and links in tables `rooms` and `users`, particularly for use in hasPermission().
   * Returns columns ping[status, typing, ptime, proomId, puserId], rooms[roomId, roomName, roomTopic, owner, defaultPermissions, roomType, roomParentalAge, roomParentalFlags, options], and users[userId, userName, userFormatStart, userFormatEnd, userGroup, socialGroups, status]
   *
   * @param       $options
   *              int ['onlineThreshold']
   *              array ['roomIds']
   *              array ['userIds']
   *              bool ['typing']
   *              array ['statuses']
   * @param array $sort
   * @param bool  $limit
   * @param bool  $pagination
   *
   * @return bool|object|resource
   */
  public function getActiveUsers($options, $sort = array('userName' => 'asc'), $limit = false, $pagination = false)
  {
    global $config;

    $options = array_merge(array(
      'onlineThreshold' => $config['defaultOnlineThreshold'],
      'roomIds'         => array(),
      'userIds'         => array(),
      'typing'          => null,
      'statuses'        => array()
    ), $options);


    $columns = array(
      $this->sqlPrefix . "ping"  => 'status, typing, time ptime, roomId proomId, userId puserId',
      $this->sqlPrefix . "rooms" => 'roomId, roomName, roomTopic, owner, defaultPermissions, roomType, roomParentalAge, roomParentalFlags, options',
      $this->sqlPrefix . "users" => 'userId, userName, userFormatStart, userFormatEnd, userGroup, socialGroups, status',
    );


    if (count($options['roomIds']) > 0) $conditions['both']['proomId'] = $this->in($options['roomIds']);
    if (count($options['userIds']) > 0) $conditions['both']['puserId'] = $this->in($options['userIds']);
    if (count($options['statuses']) > 0) $conditions['both']['status'] = $this->in($options['statuses']);

    if (isset($options['typing'])) $conditions['both']['typing'] = $this->bool($options['typing']);


    $conditions['both'] = array(
      'ptime'   => $this->int(time() - $options['onlineThreshold'], 'lt'),
      'proomId' => $this->col('roomId'),
      'puserId' => $this->col('userId'),
    );


    return $this->select($columns, $conditions, $sort);
  }



  /**
   * Note: Censor active status is calculated outside of the database, and thus can not be selected.
   * Note: Due to database limitations, it is not possible to restrict by roomId.
   * Note: This will multiple duplicate censorList columns for each matching censorBlackWhiteList entry. If a matching roomId is found for a listId, it should be used. Otherwise, any matching listId can be used, ignoring the associated room data.
   */
  public function getCensorLists($options = array(), $sort = array('listId' => 'asc'), $limit = false, $pagination = false)
  {
    $options = array_merge(array(
      'listIds'        => array(),
      'listNameSearch' => '',
      'activeStatus'   => '',
      'forcedStatus'   => '',
      'hiddenStatus'   => '',
      'privateStatus'  => '',
      'includeStatus'  => true,
    ), $options);


    $columns = array(
      $this->sqlPrefix . "censorLists" => 'listId, listName, listType, options',
    );


    if ($options['includeStatus']) {
      $columns[$this->sqlPrefix . "censorBlackWhiteLists"] = array(
        'listId' => array(
          'alias'  => 'bwListId',
          'joinOn' => 'listId',
        ),
        'roomId',
        'status',
      );
    }


    /* Modify Query Data for Directives */
    if (count($options['listIds']) > 0) $conditions['both']['listId'] = $this->in((array) $options['listIds']);
    if ($options['listNameSearch']) $conditions['both']['listName'] = $this->type('string', $options['listNameSearch'], 'search');

    if ($options['activeStatus'] === 'active') $conditions['both']['options'] = $this->int(1, 'bAnd'); // TODO: Test!
    elseif ($options['activeStatus'] === 'inactive') $conditions['both']['!options'] = $this->int(1, 'bAnd'); // TODO: Test!

    if ($options['forcedStatus'] === 'forced') $conditions['both']['!options'] = $this->int(2, 'bAnd'); // TODO: Test!
    elseif ($options['forcedStatus'] === 'notforced') $conditions['both']['options'] = $this->int(2, 'bAnd'); // TODO: Test!

    if ($options['hiddenStatus'] === 'hidden') $conditions['both']['options'] = $this->int(4, 'bAnd'); // TODO: Test!
    elseif ($options['hiddenStatus'] === 'unhidden') $conditions['both']['!options'] = $this->int(4, 'bAnd'); // TODO: Test!


    return $this->select($columns, $conditions, $sort);
  }



  public function getCensorListsActive($roomId) {
    $censorListsReturn = array();

    $censorLists = $this->getCensorLists(array(
      'listIds' => $request['lists'],
    ))->getAsArray(array('listId', 'roomId'));

    foreach ($censorLists AS $listId => $lists) { // Run through each censor list retrieved.
      foreach ($lists AS $roomId => $list) {
        if (!isset($censorListsReturn[$list['listId']])) {
          $censorListsReturn[$list['listId']] = array(
            'listId' => (int) $list['listId'],
            'listName' => ($list['listName']),
            'listType' => ($list['listType']),
            'options' => (int) $list['options'],
            'words' => array(),
            'roomStatuses' => array(),
          );
        }


        $censorListsReturn['lists']['list ' . $list['listId']]['roomStatuses'][$roomId] = array(
          'roomId' => $roomId,
          'status' => $list['status'],
        );
      }
    }

    return $censorListsReturn;
  }



  public function getCensorList($censorListId)
  {
    return $this->getCensorLists(array(
      'listIds' => array($censorListId)
    ))->getAsArray(false);
  }



  public function getCensorWords($options, $sort = array('listId' => 'asc', 'word' => 'asc'), $limit = 0, $pagination = 1)
  {
    $options = array_merge(array(
      'listIds'     => array(),
      'wordIds'     => array(),
      'wordSearch'  => '',
      'severities'  => array(),
      'paramSearch' => '',
    ), $options);

    $columns = array(
      $this->sqlPrefix . "censorWords" => 'listId, word, wordId, severity, param',
    );

    $conditions = array();

    if (count($options['listIds']) > 0) $conditions['both']['listId'] = $this->in($options['listIds']);
    if (count($options['wordIds']) > 0) $conditions['both']['wordId'] = $this->in($options['wordIds']);
    if ($options['wordSearch']) $conditions['both']['word'] = $this->type('string', $options['wordSearch'], 'search');
    if (count($options['severities']) > 0) $conditions['both']['severity'] = $this->in($options['severities']);
    if ($options['paramSearch']) $conditions['both']['param'] = $this->type('string', $options['paramSearch'], 'search');

    return $this->select($columns, $conditions, $sort);
  }



  public function getCensorWord($censorWordId)
  {
    return $this->getCensorWords(array(
      'wordIds' => array($censorWordId)
    ))->getAsArray(false);
  }



  public function getConfigurations($options = array(), $sort = array('directive' => 'asc'))
  {
    global $config, $user;

    $options = array_merge(array(
      'directives' => array(),
    ), $options);

    $columns = array(
      $this->sqlPrefix . "configuration" => 'directive, type, value',
    );

    if (count($options['directives']) > 0) {
      $conditions['both']['directive'] = $this->in($options['directives']);
    }

    return $this->select($columns, $conditions, $sort);
  }



  public function getConfiguration($directive)
  {
    return $this->getConfiguration(array(
      'directives' => array($directive)
    ))->getAsArray(false);
  }



  public function getCounterValue($counterName)
  {
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



  /** Run a query to obtain files.
   * Scans tables `files` and `fileVersions`. These two tables cannot be queried individually using fimDatabase.
   * Returns columns files.fileId, files.fileName, files.fileType, files.creationTime, files.userId, files.parentalAge, files.parentalFlags, files.roomIdLink, files.fileId, fileVersions.vfileId (= files.fileId), fileVersions.md5hash, fileVersions.sha256hash, fileVersions.size.
   * Optimisation notes: If an index is kept on the number of files posted in a given room, or to a given user, then this index can be used to see if it can be used to quickly narrow down results. Of-course, if such an index results in hundreds of results, no efficiency gain is likely to be made from doing such an elimination first. Similar optimisations might be doable wwith age, creationTime, fileTypes, etc., but these should wait for now.
   *
   * @param array $users            An array of userIDs corresponding with file ownership.
   * @param array $files            An array of fileIDs.
   * @param array $fileParams       An associative array of additional file parameters, which may be added to in the future. Valid keys include:
   *                                array  ['usrIds']       Array of user IDs corresponding to the file uploader.
   *                                array  ['roomIds']       Array of room IDs, corresponding to the room uploaded to (if provided).
   *                                array  ['fileIds']       Array of file IDs.
   *                                array  ['vfileIds']       Array of version IDs.
   *                                array  ['md5hashes']       Array of MD5 hashes.
   *                                array  ['sha256hashes']       Array of SHA256 hashes.
   *                                array  ['fileTypes']       Array of file extensions (e.g. jpg).
   *                                int    ['creationTimeMin'] File's creation time must be after.
   *                                int    ['creationTimeMax'] File's creation time must be before.
   *                                string ['fileNameSearch']    String that must appear within the fileName.
   *                                int    ['parentalAgeMin']
   *                                int    ['parentalAgeMax']
   *                                bool    ['includeContent'] If true, will also select the file's content. _This should not be set if it will not be used._
   * @param array $rooms            An array of roomIDs corresponding with where a file was posted. Some files do not have a corresponding roomId
   * @param array $parentalParams   An associative array of additional parental control parameters, which may be added to in the future. Valid keys include:
   *                                int ['ageMin']
   *                                int ['ageMax']
   * @param array $sort             A standard sort array.
   * @param int   $limit            Maximum number of results (or 0 for no limit).
   * @param int   $pagination       Result page if limit exceeded, starting at 1.
   *
   * @return bool|object|resource
   *
   * @TODO: Test filters for other file properties.
   */
  public function getFiles($options = array(), $sort = array('fileId' => 'asc'), $limit = 0, $pagination = 1)
  {
    $options = array_merge(array(
      'userIds'         => array(),
      'roomIds'         => array(),
      'fileIds'         => array(),
      'vfileIds'        => array(),
      'md5hashes'       => array(),
      'sha256hashes'    => array(),
      'fileTypes'       => array(),
      'creationTimeMax' => 0,
      'creationTimeMin' => 0,
      'fileNameSearch'  => '',
      'parentalAgeMin'  => 0,
      'parentalAgeMax'  => 0,
      'includeContent'  => false,
    ), $options);


    $columns = array(
      $this->sqlPrefix . "files"        => 'fileId, fileName, fileType, creationTime, userId, fileParentalAge, fileParentalFlags, roomIdLink, source',
      $this->sqlPrefix . "fileVersions" => 'fileId vfileId, md5hash, sha256hash, size',
    );

    if ($options['includeContent']) $columns[$this->sqlPrefix . 'fileVersions'] .= ', salt, iv, contents';


    // This is a method of optimisation I'm trying. Basically, if a very small sample is requested, then we can optimise by doing those first. Otherwise, the other filters are usually better performed first.
    foreach (array('fileIds' => 'fileId', 'vfileIds' => 'vfileId', 'md5hashes' => 'md5hash', 'sha256hashes' => 'sha256hash') AS $group => $key) {
      if (count($options[$group]) > 0 && count($options[$group]) <= 10) {
        $conditions['both'][$key] = $this->in($options[$group]);
      }
    }


    // Narrow down files _before_ matching to fileVersions. Try to perform the quickest searchest first (those which act on integer indexes).
    if (!isset($conditions['both']['fileIds']) && count($options['fileIds']) > 0) $conditions['both']['fileId'] = $this->in($options['fileIds']);
    if (count($options['userIds']) > 0) $conditions['both']['userId'] = $this->in($options['userIds']);
    if (count($options['roomIds']) > 0) $conditions['both']['roomLinkId'] = $this->in($options['roomIds']);

    if ($options['parentalAgeMin'] > 0) $conditions['both']['fileParentalAge'] = $this->int($options['parentalAgeMin'], 'gte');
    if ($options['parentalAgeMax'] > 0) $conditions['both']['fileParentalAge'] = $this->int($options['parentalAgeMax'], 'lte');

    if ($options['creationTimeMin'] > 0) $conditions['both']['creationTime'] = $this->int($options['creationTime'], 'gte');
    if ($options['creationTimeMax'] > 0) $conditions['both']['creationTime'] = $this->int($options['creationTime'], 'lte');
    if (count($options['fileTypes']) > 0) $conditions['both']['fileType'] = $this->in($options['fileTypes']);
    if ($options['fileNameSearch']) $conditions['both']['filelName'] = $this->type('string', $options['fileNameSearch'], 'search');


    // Match files to fileVersions.
    $conditions['both']['fileId'] = $this->col('vfileId');


    // Narrow down fileVersions _after_ it has been restricted to matched files.
    if (!isset($conditions['both']['vfileIds']) && count($options['vfileIds']) > 0) $conditions['both']['vfileId'] = $this->in($options['vfileIds']);
    if (!isset($conditions['both']['md5hashes']) && count($options['md5hashes']) > 0) $conditions['both']['md5hash'] = $this->in($options['md5hashes']);
    if (!isset($conditions['both']['sha256hashes']) && count($options['md5hashes']) > 0) $conditions['both']['sha256hash'] = $this->in($options['sha256hashes']);

    if ($options['sizeMin'] > 0) $conditions['both']['size'] = $this->int($options['size'], 'gte');
    if ($options['sizeMax'] > 0) $conditions['both']['size'] = $this->int($options['size'], 'lte');


    return $this->select($columns, $conditions, $sort);
  }



  /**
   * Run a query to obtain current kicks.
   *
   * @param array $options
   * @param array $sort
   * @param int   $limit
   * @param int   $pagination
   *
   * @return bool|object|resource
   */
  public function getKicks($options = array(), $sort = array('roomId' => 'asc', 'userId' => 'asc'), $limit = 0, $pagination = 1)
  {
    $options = array_merge(array(
      'userIds'   => array(),
      'roomIds'   => array(),
      'kickerIds' => array(),
      'lengthMin' => 0,
      'lengthMax' => 0,
      'timeMin'   => 0,
      'timeMax'   => 0
    ), $options);


    $columns = array(
      $this->sqlPrefix . "kicks"        => 'kickerId kkickerId, userId kuserId, roomId kroomId, length klength, time ktime',
      $this->sqlPrefix . "users user"   => 'userId, userName, userFormatStart, userFormatEnd',
      $this->sqlPrefix . "users kicker" => 'userId kickerId, userName kickerName, userFormatStart kickerFormatStart, userFormatEnd kickerFormatEnd',
      $this->sqlPrefix . "rooms"        => 'roomId, roomName, owner, options, defaultPermissions, roomType',
    );


    // Modify Query Data for Directives (First for Performance)
    if (count($options['userIds']) > 0) $conditions['both']['kuserId'] = $this->in((array) $options['userIds']);
    if (count($options['roomIds']) > 0) $conditions['both']['roomId'] = $this->in((array) $options['roomIds']);
    if (count($options['kickerIds']) > 0) $conditions['both']['kuserId'] = $this->in((array) $options['kickerIds']);

    if ($options['lengthIdMin'] > 0) $conditions['both']['klength 1'] = $this->int($options['lengthMin'], 'gte');
    if ($options['lengthIdMax'] > 0) $conditions['both']['klength 2'] = $this->int($options['lengthMax'], 'lte');

    if ($options['timeMin'] > 0) $conditions['both']['ktime 1'] = $this->int($options['timeMin'], 'gte');
    if ($options['timeMax'] > 0) $conditions['both']['ktime 2'] = $this->int($options['timeMax'], 'lte');


    // Default Conditions
    $conditions = array(
      'both' => array(
        'kuserId'   => $this->col('userId'),
        'kroomId'   => $this->col('roomId'),
        'kkickerId' => $this->col('kickerId')
      ),
    );


    return $this->select($columns, $conditions, $sort);
  }


  public function getMessagesFromPhrases($options, $sort = array('messageId' => 'asc')) {
    global $config;

    $options = array_merge(array(
      'roomIds'           => array(),
      'userIds'           => array(),
      'messageTextSearch' => '',
    ), $options);

    $searchArray = array();
    foreach (explode(',', $options['messageTextSearch']) AS $searchVal) {
      $searchArray[] = str_replace(
        array_keys($config['searchWordConverts']),
        array_values($config['searchWordConverts']),
        $searchVal
      );
    }

    $columns = array(
      $this->sqlPrefix . "searchPhrases"  => 'phraseName, phraseId pphraseId',
      $this->sqlPrefix . "searchMessages" => 'phraseId mphraseId, messageId, userId, roomId'
    );

    $conditions['both']['mphraseId'] = $this->col('pphraseId');


    /* Apply User and Room Filters */
    if (count($options['rooms']) > 1) $conditions['both']['roomId'] = $this->in((array) $options['rooms']);
    if (count($options['users']) > 1) $conditions['both']['userId'] = $this->in((array) $options['users']);


    /* Determine Whether to Use the Fast or Slow Algorithms */
    if (!$config['fullTextArchive']) { // Original, Fastest Algorithm
      $conditions['both']['phraseName'] = $this->in((array) $searchArray);
    } else { // Slower Algorithm
      foreach ($searchArray AS $phrase) $conditions['both']['either'][]['phraseName'] = $this->type('string', $phrase, 'search');
    }


    /* Run the Query */
    return $this->select($columns, $conditions, $sort);
 }


  /**
   * Run a query to obtain messages.
   * getMessages is by far the most advanced set of database calls in the whole application, and is still in need of much fine-tuning. The mesagEStream file uses its own query and must be teste seerately.
   *
   * @param array $options
   * @param array $sort
   * @param bool  $limit
   *
   * @return array|bool|object|resource
   */
  public function getMessages($options = array(), $sort = array('messageId' => 'asc'), $limit = false)
  {
    global $config;

    $options = array_merge(array(
      'roomIds'           => array(),
      'messageIds'        => array(),
      'userIds'           => array(),
      'messageTextSearch' => '',
      'showDeleted'       => true,
      'messagesSince'     => 0,
      'messageIdMax'      => 0,
      'messageIdMin'      => 0,
      'messageIdStart'    => 0,
      'messageIdEnd'      => 0,
      'messageDateMax'    => 0,
      'messageDateMin'    => 0,
      'archive'           => false,
      'longPolling'       => false
    ), $options);


    /* Create a $messages list based on search parameter. */
    if (strlen($options['messageTextSearch']) > 0) {
      if (!$options['archive']) {
        $this->triggerError('The "messageTextSearch" option in getMessages can only be used if "archive" is set to true.', array('Options' => $options), 'validation');
      } else {
        /* Run the Query */
        $searchMessageIds = $this->getMessagesFromPhrases(array(
          'roomIds' => $options['roomIds'],
          'userIds' => $options['userIds'],
          'messageTextSearch' => $options['messageTextSearch'],
        ))->getAsArray('messageId');

        $searchMessages = array_keys($searchMessageIds);


        /* Modify the Request Filter for Messages */
        if ($searchMessages) $messages = fim_arrayValidate($searchMessages, 'int', true);
        else                 $messages = array(0); // This is a fairly dirty approach, but it does work for now. TODO
      }
    }


    /* Query via the Archive */
    if ($options['archive']) {
      $columns = array(
        $this->sqlPrefix . "messages" => 'messageId, time, iv, salt, roomId, userId, deleted, flag, text',
        $this->sqlPrefix . "users"    => 'userId muserId, userName, userGroup, socialGroups, userFormatStart, userFormatEnd, avatar, defaultColor, defaultFontface, defaultHighlight, defaultFormatting'
      );

      $conditions['both']['muserId'] = $this->col('userId');
    } /* Access the Stream */
    else {
      $columns = array(
        $this->sqlPrefix . "messagesCached" => "messageId, roomId, time, flag, userId, userName, userGroup, socialGroups, userFormatStart, userFormatEnd, avatar, defaultColor, defaultFontface, defaultHighlight, defaultFormatting, text",
      );
    }


    /* Modify Query Data for Directives
     * TODO: Remove messageIdStart and messageIdEnd, replacing with $limit and $pagination (combined with other operators). */
    if ($options['messageIdMax'] > 0)   $conditions['both']['messageId 2'] = $this->int($options['messageIdMax'], 'lte');
    if ($options['messageIdMin'] > 0)   $conditions['both']['messageId 1'] = $this->int($options['messageIdMin'], 'gte');
    if ($options['messageDateMax'] > 0) $conditions['both']['time 1'] = $this->int($options['messageDateMax'], 'lte');
    if ($options['messageDateMin'] > 0) $conditions['both']['time 2'] = $this->int($options['messageDateMin'], 'gte');

    if ($options['messageIdStart'] > 0) {
      $conditions['both']['messageId 3'] = $this->int($options['messageIdStart'], 'gte');
      $conditions['both']['messageId 4'] = $this->int($options['messageIdStart'] + $options['messageLimit'], 'lt');
    } elseif ($options['messageIdEnd'] > 0) {
      $conditions['both']['messageId 3'] = $this->int($options['messageIdEnd'], 'lte');
      $conditions['both']['messageId 4'] = $this->int($options['messageIdEnd'] - $options['messageLimit'], 'gt');
    }

    if ($options['messagesSince'] > 0)
      $conditions['both']['messageId 5'] = $this->int($options['messagesSince'], 'gt');

    if ($options['showDeleted'] === true && $options['archive'] === true) $conditions['both']['deleted'] = $this->bool(false);
    if (count($options['messageIds']) > 0) $conditions['both']['messageId'] = $this->in($options['messageIds']); // Overrides all other message ID parameters; TODO
    if (count($options['userIds']) > 0) $conditions['both']['userId'] = $this->in($options['userIds']);
    if (count($options['roomIds']) > 0) $conditions['both']['roomId'] = $this->in($options['roomIds']);


    $messages = $this->select($columns, $conditions, $sort);


    if ($options['longPolling']) {
      $longPollingRetries = 0;

      while (!count($messages->getAsArray(true))) { // This could be a little inefficient, but is probably fine. Might be worth testing (e.g. replace with ->resultCount or something.
        $longPollingRetries++;

        $messages = $this->select($columns, $conditions, $sort);

        if ($longPollingRetries <= $config['longPollingMaxRetries']) {
          sleep($config['longPollingWait']);
        } else break;
      }
    }


    return $messages;
  }



  public function getModLog($options, $sort = array('time' => 'asc'), $limit = 0, $pagination = 1) {

    $options = array_merge(array(
      'userIds' => array(),
      'ips' => array(),
      'timeMin' => 0,
      'timeMax' => 0,
      'actions' => array(),
    ), $options);

    $columns = array(
      $this->sqlPrefix => 'id, userId, time, ip, action, data'
    );




    $this->select($columns, $conditions, $sort);


  }



  public function getPermissions($rooms = array())
  {
    // Modify Query Data for Directives (First for Performance)
    $columns = array(
      $this->sqlPrefix . "roomPermissions" => 'roomId, attribute, param, permissions',
    );

    if (count($rooms) > 0) {
      $conditions['both']['roomId'] = $this->in((array) $rooms);
    }

    return $this->select($columns, $conditions);
  }



  /**
   * Run a query to obtain the number of posts made to a room by a user.
   * Use of groupBy highly recommended.
   *
   * @param       $options
   * @param array $sort
   * @param int   $limit
   * @param int   $pagination
   *
   * @return bool|object|resource
   */
  public function getPostStats($options, $sort = array('roomId' => 'asc', 'userId' => 'asc'), $limit = 0, $pagination = 1)
  {
    $options = array_merge(array(
      'userIds' => array(),
      'roomIds' => array(),
    ), $options);


    $columns = array(
      $this->sqlPrefix . 'roomStats' => 'roomId sroomId, userId suserId, messages',
      $this->sqlPrefix . 'users'     => 'userId, userName, adminPrivs, userFormatStart, userFormatEnd, userParentalFlags, userParentalAge',
      $this->sqlPrefix . 'rooms'     => 'roomId, roomName, owner, defaultPermissions, roomParentalFlags, roomParentalAge, options, messageCount, roomType',
    );


    $conditions['both'] = array(
      'suserId' => $this->col('userId'),
      'sroomId' => $this->col('roomId'),
    );


    if (count($options['roomIds']) > 0) $conditions['both']['sroomId'] = $this->in($options['roomIds']);
    if (count($options['userIds']) > 0) $conditions['both']['suserId'] = $this->in($options['userIds']);


    return $this->select($columns, $conditions, $sort);
  }



  public function getRooms($options, $sort = array('roomId' => 'asc'), $limit = 0, $pagination = 1)
  {
    $options = array_merge(array(
      'roomIds'            => array(),
      'ownerIds'           => array(),
      'parentalAgeMin'     => 0,
      'parentalAgeMax'     => 0,
      'messageCountMin'    => 0,
      'messageCountMax'    => 0,
      'lastMessageTimeMin' => 0,
      'lastMessageTimeMax' => 0,
      'showDeleted'        => false,
      'roomNameSearch'     => false,
    ), $options);


    // Defaults
    $columns = array($this->sqlPrefix . "rooms" => 'roomId, roomName, roomTopic, owner, defaultPermissions, roomParentalFlags, roomParentalAge, options, lastMessageId, lastMessageTime, messageCount, roomType');


    // Modify Query Data for Directives
//  	if ($options['showDeleted']) $conditions['both']['options'] = $this->int(8, 'bAnd'); // TODO: Permission?
//    else $conditions['both'] = array('!options' => $this->int(8, 'bAnd'));

    if (count($options['roomIds']) > 0) $conditions['both']['roomId'] = $this->in($options['roomIds']);
    if ($options['roomNameSearch']) $conditions['both']['roomName'] = $this->type('string', $options['roomNameSearch'], 'search');

    if ($options['parentalAgeMin'] > 0) $conditions['both']['roomParentalAge'] = $this->int($options['parentalAgeMin'], 'gte');
    if ($options['parentalAgeMax'] > 0) $conditions['both']['roomParentalAge'] = $this->int($options['parentalAgeMax'], 'lte');

    if ($options['messageCountMin'] > 0) $conditions['both']['messageCount'] = $this->int($options['messageCount'], 'gte');
    if ($options['messageCountMax'] > 0) $conditions['both']['messageCount'] = $this->int($options['messageCount'], 'lte');

    if ($options['lastMessageTimeMin'] > 0) $conditions['both']['lastMessageTime'] = $this->int($options['lastMessageTime'], 'gte');
    if ($options['lastMessageTimeMax'] > 0) $conditions['both']['lastMessageTime'] = $this->int($options['lastMessageTime'], 'lte');


    // Perform Query
    return $this->select($columns, $conditions, $sort);
  }



  public function getRoom($roomId)
  {
    return $room = $this->getRooms(array(
      'roomIds' => array($roomId)
    ))->getAsArray(false);
  }



  /**
   * Run a query to obtain room lists.
   * Use of groupBy _highly_ recommended.
   *
   * @param       $options
   * @param array $sort
   * @param int   $limit
   * @param int   $pagination
   *
   * @return bool|object|resource
   */
  public function getRoomLists($options, $sort = array('listId' => 'asc'), $limit = 0, $pagination = 1)
  {
    $options = array_merge(array(
      'userIds'     => array(),
      'roomIds'     => array(),
      'roomListIds' => array(),
    ), $options);

    $columns = array(
      $this->sqlPrefix . "roomLists"     => 'listId, userId, listName, options',
      $this->sqlPrefix . "roomListRooms" => 'listId llistId, roomId lroomid',
    );


    $conditions['both'] = array(
      'llistId' => $this->col('listId'),
    );


    if (count($options['roomListIds']) > 0) $conditions['both']['listId'] = $this->in($options['roomListIds']);

    if (count($options['userIds']) > 0) $conditions['both']['userId'] = $this->in($options['userIds']);
    if (count($options['roomIds']) > 0) $conditions['both']['lroomId'] = $this->in($options['userIds']);


    return $this->select($columns, $conditions, $sort);
  }



  public function getUsers($options = array(), $sort = array('userId' => 'asc'), $limit = 0, $pagination = 1)
  {
    $options = array_merge(array(
      'userIds'        => array(),
      'userNames'      => array(),
      'userNameSearch' => false,
      'bannedStatus'   => false,
    ), $options);


    $columns = array(
      $this->sqlPrefix . "users" => 'userId, userName, userFormatStart, userFormatEnd, profile, avatar, socialGroups, defaultColor, defaultHighlight, defaultFontface, defaultFormatting, userGroup, options, defaultRoom, userParentalAge, userParentalFlags, adminPrivs',
    );


    $conditions['both'] = array();


    /* Modify Query Data for Directives */
    if ($options['bannedStatus'] === 'banned') $conditions['both']['!options'] = $this->int(1, 'bAnd'); // TODO: Test!
    if ($options['bannedStatus'] === 'unbanned') $conditions['both']['options'] = $this->int(1, 'bAnd'); // TODO: Test!


    if (count($options['hasAdminPrivs']) > 0) {
      foreach ($options['hasAdminPrivs'] AS $adminPriv) $conditions['both']['adminPrivs'] = $this->int($adminPriv, 'bAnd');
    }


    if (count($options['userIds']) > 0 || count($options['userNames']) > 0 || $options['userNameSearch']) {
      if (count($options['userIds']) > 0) $conditions['both']['either']['userId'] = $this->in($options['userIds']);
      if (count($options['userNames']) > 0) $conditions['both']['either']['userName 1'] = $this->in($options['userNames']);
      if ($options['userNameSearch']) $conditions['both']['either']['userName 2'] = $this->type('string', $options['userNameSearch'], 'search');
    }


    return $this->select($columns, $conditions, $sort);
  }



  public function getUser($userId)
  {
    return $user = $this->getUsers(array(
      'userIds' => array($userId)
    ))->getAsArray(false);
  }



  public function markMessageRead($messageId, $userId)
  {
    global $config, $user;

    if ($config['enableUnreadMessages']) {
      $this->delete($this->sqlPrefix . "unreadMessages", array(
        'messageId' => $messageId,
        'userId'    => $userId
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
  public function sendMessage($messageText, $messageFlag, $userData, $roomData)
  {
    $messageParse = new messageParse($messageText, $messageFlag, $userData, $roomData);

    $messageText = $messageParse->getParsed();
    list($messageTextEncrypted, $iv, $saltNum) = $messageParse->getEncrypted();

    $messageId = $this->storeMessage($userData, $roomData, $messageText, $messageTextEncrypted, $iv, $saltNum, $messageFlag);

    $keyWords = $messageParse->getKeyWords();
    $this->storeKeyWords($keyWords, $messageId, $userData['userId'], $roomData['roomId']);

//  $database->storeUnreadMessage();
  }



  public function storeMessage($userData, $roomData, $messageText, $messageTextEncrypted, $encryptIV, $encryptSalt, $flag)
  {
    global $config, $user, $generalCache;

    if (!isset($roomData['options'], $roomData['roomId'], $roomData['roomName'], $roomData['roomType'])) throw new Exception('database->storeMessage requires roomData[options], roomData[roomId], roomData[roomName], and roomData[type].');
    if (!isset($userData['userId'], $userData['userName'], $userData['userGroup'], $userData['avatar'], $userData['profile'], $userData['userFormatStart'], $userData['userFormatEnd'], $userData['defaultFormatting'], $userData['defaultColor'], $userData['defaultHighlight'], $userData['defaultFontface'])) throw new Exception('database->storeMessage requires userData[userId], userData[userName], userData[userGroup], userData[avatar]. userData[profile], userData[userFormatStart], userData[userFormatEnd], userData[defaultFormatting], userData[defaultColor], userData[defaultHighlight], and userData[defaultFontface]');


    // Insert into permenant datastore.
    $this->insert($this->sqlPrefix . "messages", array(
      'roomId'   => (int) $roomData['roomId'],
      'userId'   => (int) $userData['userId'],
      'text'     => $messageTextEncrypted,
      'textSha1' => sha1($messageText),
      'salt'     => $encryptSalt,
      'iv'       => $encryptIV,
      'ip'       => $_SERVER['REMOTE_ADDR'],
      'flag'     => $flag,
      'time'     => $this->now(),
    ));
    $messageId = $this->insertId;


    // Update room caches.
    $this->update($this->sqlPrefix . "rooms", array(
      'lastMessageTime' => $this->now(),
      'lastMessageId'   => $messageId,
      'messageCount'    => array(
        'type'  => 'equation',
        'value' => '$messageCount + 1',
      )
    ), array(
      'roomId' => $roomData['roomId'],
    ));


    // Update the messageIndex if appropriate
    $roomDataNew = $this->getRoom($roomData['roomId'], false, false); // Get the new room data.

    if ($roomDataNew['messageCount'] % $config['messageIndexCounter'] === 0) { // If the current messages in the room is divisible by the messageIndexCounter, insert into the messageIndex cache. Note that we are hoping this is because of the very last query which incremented this value, but it is impossible to know for certain (if we tried to re-order things to get the room data first, we still run this risk, so that doesn't matter; either way accuracy isn't critical).
      $this->insert($this->sqlPrefix . "messageIndex", array(
        'roomId'    => $roomData['roomId'],
        'interval'  => (int) $roomDataNew['messageCount'],
        'messageId' => $messageId
      ), array(
        'messageId' => array(
          'type'  => 'equation',
          'value' => '$messageId + 0',
        )
      ));
    }


    // Update user caches
    $this->update($this->sqlPrefix . "users", array(
      'messageCount' => array(
        'type'  => 'equation',
        'value' => '$messageCount + 1',
      )
    ), array(
      'userId' => $userData['userId'],
    ));


    // Insert or update a user's room stats.
    $this->insert($this->sqlPrefix . "roomStats", array(
      'userId'   => $userData['userId'],
      'roomId'   => $roomData['roomId'],
      'messages' => 1
    ), array(
      'messages' => array(
        'type'  => 'equation',
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
        'time'      => $lastMidnight,
        'messageId' => $messageId
      ), array(
        'messageId' => array(
          'type'  => 'equation',
          'value' => '$messageId + 0',
        )
      ));

      $generalCache->set('fim3_lastDayCache', $lastMidnight); // Update the quick cache.
    }


    // Insert into cache/memory datastore.
    $this->insert($this->sqlPrefix . "messagesCached", array(
      'messageId'         => (int) $messageId,
      'roomId'            => (int) $roomData['roomId'],
      'userId'            => (int) $userData['userId'],
      'userName'          => $userData['userName'],
      'userGroup'         => (int) $userData['userGroup'],
      'avatar'            => $userData['avatar'],
      'profile'           => $userData['profile'],
      'userFormatStart'   => $userData['userFormatStart'],
      'userFormatEnd'     => $userData['userFormatEnd'],
      'defaultFormatting' => $userData['defaultFormatting'],
      'defaultColor'      => $userData['defaultColor'],
      'defaultHighlight'  => $userData['defaultHighlight'],
      'defaultFontface'   => $userData['defaultFontface'],
      'text'              => $messageText,
      'flag'              => $flag,
      'time'              => $this->now(),
    ));
    $messageId2 = $this->insertId;


    // Delete old messages from the cache, based on the maximum allowed rows.
    if ($messageId2 > $config['cacheTableMaxRows']) {
      $this->delete($this->sqlPrefix . "messagesCached",
        array('id' => array(
          'cond'  => 'lte',
          'value' => (int) ($messageId2 - $config['cacheTableMaxRows'])
        )
        ));
    }


    // If the contact is a private communication, create an event and add to the message unread table.
    if ($roomData['roomType'] === 'private') {
      foreach ($generalCache->getPermissions($roomData['roomId'], 'user') AS $sendToUserId => $permissionLevel) {
        if ($sendToUserId == $user['userId']) {
          continue;
        } else {
          $this->createEvent('missedMessage', $sendToUserId, $roomData['roomId'], $messageId, false, false, false);

          if ($config['enableUnreadMessages']) {
            $this->insert($this->sqlPrefix . "unreadMessages", array(
              'userId'            => $sendToUserId,
              'senderId'          => $userData['userId'],
              'senderName'        => $userData['userName'],
              'senderFormatStart' => $userData['userFormatStart'],
              'senderFormatEnd'   => $userData['userFormatEnd'],
              'roomId'            => $roomData['roomId'],
              'roomName'          => $roomData['roomName'],
              'messageId'         => $messageId,
              'time'              => $this->now(),
            ), array(
              'senderId'          => $userData['userId'],
              'senderName'        => $userData['userName'],
              'senderFormatStart' => $userData['userFormatStart'],
              'senderFormatEnd'   => $userData['userFormatEnd'],
              'roomName'          => $roomData['roomName'],
              'messageId'         => $messageId,
              'time'              => $this->now(),
            ));
          }
        }
      }
    }


    // Return the ID of the inserted message.
    return $messageId;
  }

  public function incrementCounter($counterName, $incrementValue = 1)
  {
    global $config;

    if ($this->update($this->sqlPrefix . "counters", array(
      'counterValue' => array(
        'type'  => 'equation',
        'value' => '$counterValue + ' . (int) $incrementValue,
      )
    ), array(
      'counterName' => $counterName,
    ))
    ) {
      return true;
    } else {
      return false;
    }
  }

  public function createEvent($eventName, $userId, $roomId, $messageId, $param1, $param2, $param3)
  {
    global $config, $user;

    if ($config['enableEvents']) {
      $this->insert($this->sqlPrefix . "events", array(
        'eventName' => $eventName,
        'userId'    => $userId,
        'roomId'    => $roomId,
        'messageId' => $messageId,
        'param1'    => $param1,
        'param2'    => $param2,
        'param3'    => $param3,
        'time'      => $this->now(),
      ));
    }
  }

  public function storeKeyWords($words, $messageId, $userId, $roomId)
  {
    global $config;


    $phraseData = $this->select(
      array(
        $this->sqlPrefix . 'searchPhrases' => 'phraseName, phraseId'
      )
    )->getAsArray('phraseName');


    foreach (array_unique($words) AS $piece) {
      if (!isset($phraseData[$piece])) {
        $this->insert($this->sqlPrefix . "searchPhrases", array(
          'phraseName' => $piece,
        ));
        $phraseId = $this->insertId;
      } else {
        $phraseId = $phraseData[$piece]['phraseId'];
      }

      $this->insert($this->sqlPrefix . "searchMessages", array(
        'phraseId'  => (int) $phraseId,
        'messageId' => (int) $messageId,
        'userId'    => (int) $userId,
        'roomId'    => (int) $roomId,
      ));

    }
  }

  /**
   * ModLog container
   *
   * @param string $action
   * @param string $data
   *
   * @return bool
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */

  public function modLog($action, $data)
  {
    global $config, $user;

    if (!isset($user['userId'])) throw new Exception('database->modLog requires user[userId]');

    if ($this->insert($this->sqlPrefix . "modlog", array(
      'userId' => (int) $user['userId'],
      'ip'     => $_SERVER['REMOTE_ADDR'],
      'action' => $action,
      'data'   => $data,
      'time'   => $this->now(),
    ))
    ) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Fulllog container
   *
   * @param string $action
   * @param array  $data
   *
   * @return bool
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */

  public function fullLog($action, $data)
  {
    global $config, $user;

    if ($this->insert($this->sqlPrefix . "fulllog", array(
      'user'   => json_encode($user),
      'server' => json_encode($_SERVER),
      'action' => $action,
      'time'   => $this->now(),
      'data'   => json_encode($data),
    ))
    ) {
      return true;
    } else {
      return false;
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