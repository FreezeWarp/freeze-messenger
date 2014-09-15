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
  private $userColumns = 'userId, userName, userNameFormat, profile, avatar, userGroupId, socialGroupIds, messageFormatting, options, defaultRoomId, userParentalAge, userParentalFlags, privs';
  protected $config;



  public function setConfig($config) {
    $this->config = $config;
  }


  /****** Helper Functions ******/
  function makeSearchable($string) {
    $string = str_replace(array_keys($this->config['romanisation']), array_values($this->config['romanisation']), $string); // Romanise.
    $string = str_replace($this->config['searchWordPunctuation'], ' ', $string); // Get rid of punctuation.
    $string = preg_replace('/\s+/', ' ', $string); // Get rid of extra spaces.
    $string = strtolower($string); // Lowercase the string.

    return $string;
  }



  /****** Get Functions *****/


  /**
   * Run a query to obtain users who appear "active."
   * Scans table `ping`, and links in tables `rooms` and `users`, particularly for use in hasPermission().
   * Returns columns ping[status, typing, ptime, proomId, puserId], rooms[roomId, roomName, roomTopic, owner, defaultPermissions, roomType, roomParentalAge, roomParentalFlags, options], and users[userId, userName, userNameFormat, userGroupId, socialGroupIds, status]
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

    $options = array_merge(array(
      'onlineThreshold' => $this->config['defaultOnlineThreshold'],
      'roomIds'         => array(),
      'userIds'         => array(),
      'typing'          => null,
      'statuses'        => array()
    ), $options);


    $columns = array(
      $this->sqlPrefix . "ping"  => 'status, typing, time ptime, roomId proomId, userId puserId',
      $this->sqlPrefix . "rooms" => 'roomId, roomName, roomTopic, ownerId, defaultPermissions, roomType, roomParentalAge, roomParentalFlags, options',
      $this->sqlPrefix . "users" => 'userId, userName, userNameFormat, userGroupId, socialGroupIds, status',
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
      'roomIds'        => array(),
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
/*      $columns[$this->sqlPrefix . "censorBlackWhiteLists"] = array(
        'listId' => array(
          'alias'  => 'bwListId',
          'joinOn' => 'listId',
          ),
          'roomId',
          'status',
        );*/



      $subColumns = array(
        $this->sqlPrefix . 'censorBlackWhiteLists' => 'listId, roomId, status'
      );
      $subConditions = array();
      if (count($options['roomIds']) > 0) $subConditions['both']['roomId'] = $this->in($options['roomIds']);

      $columns['sub ' . $this->sqlPrefix . "censorBlackWhiteLists"] = $this->subSelect($subColumns, $subConditions);

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
      'roomIds' => array($roomId),
    ))->getAsArray(array('listId'));

    foreach ($censorLists AS $listId => $list) { // Run through each censor list retrieved.
      if ($list['status'] === 'unblock' || $list['listType'] === 'black') continue;

      $censorListsReturn[$list['listId']] = $list;
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



  public function getCensorWordsActive($roomId, $types = array('replace')) {
    return $this->getCensorWords(array(
      'listIds' => array_keys($this->getCensorListsActive($roomId)),
      'severities' => $types
    ));
  }



  public function getConfigurations($options = array(), $sort = array('directive' => 'asc'))
  {
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
    return $this->getConfigurations(array(
      'directives' => array($directive)
    ))->getAsArray(false);
  }



  public function getCounterValue($counterName)
  {
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
      'timeMax'   => 0,
      'includeUserData' => true,
      'includeKickerData' => true,
      'includeRoomData' => true,
    ), $options);



    // Base Query
    $columns = array(
      $this->sqlPrefix . "kicks" => 'kickerId, userId, roomId, length, time',
    );
    $conditions = array();



    // Modify Columns (and Joins)
    if ($options['includeUserData']) {
      $columns[$this->sqlPrefix . "users user"]  = 'userId kuserId, userName, userNameFormat';
      $conditions['both']['userId'] = $this->col('kuserId');
    }
    if ($options['includeKickerData']) {
      $columns[$this->sqlPrefix . "users kicker"] = 'userId kkickerId, userName kickerName, userNameFormat kickerNameFormat';
      $conditions['both']['roomId'] = $this->col('kroomId');
    }
    if ($options['includeRoomData']) {
      $columns[$this->sqlPrefix . "rooms"] = 'roomId kroomId, roomName, ownerId, options, defaultPermissions, roomType, roomParentalFlags, roomParentalAge';
      $conditions['both']['kickerId'] = $this->col('kkickerId');
    }



    // Modify Query Data for Directives (First for Performance)
    if (count($options['userIds']) > 0) $conditions['both']['userId'] = $this->in((array) $options['userIds']);
    if (count($options['roomIds']) > 0) $conditions['both']['roomId'] = $this->in((array) $options['roomIds']);
    if (count($options['kickerIds']) > 0) $conditions['both']['userId'] = $this->in((array) $options['kickerIds']);

    if ($options['lengthIdMin'] > 0) $conditions['both']['length 1'] = $this->int($options['lengthMin'], 'gte');
    if ($options['lengthIdMax'] > 0) $conditions['both']['length 2'] = $this->int($options['lengthMax'], 'lte');

    if ($options['timeMin'] > 0) $conditions['both']['time 1'] = $this->int($options['timeMin'], 'gte');
    if ($options['timeMax'] > 0) $conditions['both']['time 2'] = $this->int($options['timeMax'], 'lte');



    // Return
    return $this->select($columns, $conditions, $sort);
  }



  public function kickUser($userId, $roomId, $length) {
    global $user; // TODO

    $this->modLog('kickUser', "$userId,$roomId");

    $this->upsert($this->sqlPrefix . "kicks", array(
        'userId' => (int) $userId,
        'roomId' => (int) $roomId,
      ), array(
        'length' => (int) $length,
        'kickerId' => (int) $user['kickerId'],
        'time' => $this->now(),
      )
    );
  }


  public function unkickUser($userId, $roomId) {
    global $user; // TODO

    $this->modLog('unkickUser', "$userId,$roomId");

    $this->delete($this->sqlPrefix . "kicks", array(
      'userId' => $userId,
      'roomId' => $roomId,
    ));
  }



  public function getMessagesFromPhrases($options, $sort = array('messageId' => 'asc')) {
    $options = array_merge(array(
      'roomIds'           => array(),
      'userIds'           => array(),
      'messageTextSearch' => '',
    ), $options);

    $searchArray = array();
    foreach (explode(',', $options['messageTextSearch']) AS $searchVal) {
      $searchArray[] = str_replace(
        array_keys($this->config['searchWordConverts']),
        array_values($this->config['searchWordConverts']),
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
    if (!$this->config['fullTextArchive']) { // Original, Fastest Algorithm
      $conditions['both']['phraseName'] = $this->in((array) $searchArray);
    } else { // Slower Algorithm
      foreach ($searchArray AS $phrase) $conditions['both']['either'][]['phraseName'] = $this->type('string', $phrase, 'search');
    }


    /* Run the Query */
    return $this->select($columns, $conditions, $sort);
 }



  public function getMessageIdsFromSearchCache($options, $limit, $page) {
    $options = array_merge(array(
      'roomIds'           => array(),
      'userIds'           => array(),
      'phraseNames' => array(),
    ), $options);


    $columns = array(
      $this->sqlPrefix . "searchCache"  => 'phraseName, userId, roomId, resultPage, resultLimit, messageIds, expires',
    );

    $conditions['both']['phraseName'] = $options['phraseNames'];
    $conditions['both']['resultPage'] = $page;
    $conditions['both']['resultLimit'] = $limit;


    /* Apply User and Room Filters */
    if (count($options['roomIds']) > 1) $conditions['both']['roomId'] = $this->in((array) $options['roomIds']);
    if (count($options['userIds']) > 1) $conditions['both']['userId'] = $this->in((array) $options['userIds']);


    /* Run the Query */
    $messageIds = implode(',', $this->select($columns, $conditions)->getColumnValues('messageIds'));
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
  public function getMessages($options = array(), $sort = array('messageId' => 'asc'), $limit = false, $page = 0)
  {
    $options = array_merge(array(
      'roomIds'           => array(),
      'messageIds'        => array(),
      'userIds'           => array(),
      'messageTextSearch' => '', // Overwrites messageIds.
      'showDeleted'       => true,
      'messagesSince'     => 0,
      'messageIdMax'      => 0,
      'messageIdMin'      => 0,
      'messageIdStart'    => 0,
      'messageIdEnd'      => 0,
      'messageDateMax'    => 0,
      'messageDateMin'    => 0,
      'archive'           => false
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
        ), null, $limit, $page)->getAsArray('messageId');

        $searchMessages = array_keys($searchMessageIds);


        /* Modify the Request Filter for Messages */
        if ($searchMessages) $options['messageIds'] = fim_arrayValidate($searchMessages, 'int', true);
        else                 $options['messageIds'] = array(0); // This is a fairly dirty approach, but it does work for now. TODO
      }
    }


    /* Query via the Archive */
    if ($options['archive']) {
      $columns = array(
        $this->sqlPrefix . "messages" => 'messageId, time, iv, salt, roomId, userId, deleted, flag, text',
        $this->sqlPrefix . "users"    => 'userId muserId, userName, userGroupId, socialGroupIds, userNameFormat, avatar, messageFormatting'
      );

      $conditions['both']['muserId'] = $this->col('userId');
    } /* Access the Stream */
    else {
      $columns = array(
        $this->sqlPrefix . "messagesCached" => "messageId, roomId, time, flag, userId, userName, userGroupId, socialGroupIds, userNameFormat, avatar, messageFormatting, text",
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



  public function getRoomPermissions($rooms = array(), $attribute = false, $params = array())
  {
    // Modify Query Data for Directives (First for Performance)
    $columns = array(
      $this->sqlPrefix . "roomPermissions" => 'roomId, attribute, param, permissions',
    );

    if (count($rooms) > 0) $conditions['both']['roomId'] = $this->in((array) $rooms);
    if ($attribute) $conditions['both']['attribute'] = $this->str($attribute);
    if (count($params) > 0) $conditions['both']['param'] = $this->in((array) $params);

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
      $this->sqlPrefix . 'users'     => 'userId, userName, privs, userNameFormat, userParentalFlags, userParentalAge',
      $this->sqlPrefix . 'rooms'     => 'roomId, roomName, ownerId, defaultPermissions, roomParentalFlags, roomParentalAge, options, messageCount, roomType',
    );


    $conditions['both'] = array(
      'suserId' => $this->col('userId'),
      'sroomId' => $this->col('roomId'),
    );


    if (count($options['roomIds']) > 0) $conditions['both']['sroomId a'] = $this->in($options['roomIds']);
    if (count($options['userIds']) > 0) $conditions['both']['suserId a'] = $this->in($options['userIds']);


    return $this->select($columns, $conditions, $sort);
  }



  public function getRooms($options, $sort = array('roomId' => 'asc'), $limit = 0, $pagination = 1)
  {
    $options = array_merge(array(
      'roomIds'            => array(),
      'roomNames'          => array(),
      'roomAliases'        => array(),
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
    $columns = array($this->sqlPrefix . "rooms" => 'roomId, roomName, roomAlias, roomTopic, ownerId, defaultPermissions, roomParentalFlags, roomParentalAge, options, lastMessageId, lastMessageTime, messageCount, roomType');


    // Modify Query Data for Directives
//  	if ($options['showDeleted']) $conditions['both']['options'] = $this->int(8, 'bAnd'); // TODO: Permission?
//    else $conditions['both'] = array('!options' => $this->int(8, 'bAnd'));

    if (count($options['roomIds']) > 0) $conditions['both']['either']['roomId'] = $this->in($options['roomIds']);
    if (count($options['roomNames']) > 0) $conditions['both']['either']['roomName'] = $this->in($options['roomNames']);
    if (count($options['roomAliases']) > 0) $conditions['both']['either']['roomAlias'] = $this->in($options['roomAliases']);
    if ($options['roomNameSearch']) $conditions['both']['either']['roomName'] = $this->type('string', $options['roomNameSearch'], 'search');

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
    return $this->getRooms(array(
      'roomIds' => array($roomId)
    ))->getAsRoom();
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
      'includePasswords' => false,
    ), $options);


    $columns = array(
      $this->sqlPrefix . "users" => $this->userColumns . ($options['includePasswords'] ? ', passwordHash, passwordFormat, passwordResetNow, passwordLastReset' : '') // For this particular request, you can also access user password information using the includePasswords flag.
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



  public function getSessions($options = array(), $sort = array('sessionId' => 'asc'), $limit = 0, $pagination = 1)
  {
    $options = array_merge(array(
      'sessionIds' => array(),
      'sessionHashes'        => array(),
      'userIds'      => array(),
      'ips' => array(),
      'combineUserData' => true,
    ), $options);


    $columns = array(
      $this->sqlPrefix . "sessions" => 'sessionId, anonId, sessionHash, userId suserId, sessionTime, sessionIp, userAgent',
    );

    $conditions = array();

    if ($options['combineUserData']) {
      $columns[$this->sqlPrefix . "users"] = $this->userColumns;
      $conditions['both']['userId'] = $this->col('suserId');
    }

    if (count($options['userIds']) > 0) $conditions['both']['either']['userId'] = $this->in($options['userIds']);
    if (count($options['sessionIds']) > 0) $conditions['both']['either']['sessionId'] = $this->in($options['sessionIds']);
    if (count($options['sessionHashes']) > 0) $conditions['both']['either']['sessionHash'] = $this->in($options['sessionHashes']);
    if (count($options['ips']) > 0) $conditions['both']['either']['sessionIp'] = $this->in($options['ips']);

    return $this->select($columns, $conditions, $sort);
  }


  public function getEvents($options = array(), $sort = array('eventId' => 'asc')) {
    $options = array_merge(array(
      'roomIds' => array(),
      'userIds'        => array(),
      'lastEvent'      => 0,
    ), $options);


    $columns = array(
      $this->sqlPrefix . "events" => 'eventId, eventName, userId, roomId, messageId, param1, param2, param3, time',
    );

    $conditions = array(
      'both' => array()
    );

    if (count($options['roomIds'])) $conditions['both']['either']['roomId'] = $this->in($options['roomIds']);
    if (count($options['userIds'])) $conditions['both']['either']['userId'] = $this->in($options['userIds']);

    if ($options['lastEvent']) $conditions['both']['eventId']  = $this->int($options['lastEvent'], 'gt');

    return $this->select($columns, $conditions, $sort);
  }


  public function getUserEventsForId($userId, $lastEvent) {
    $columns = array(
      $this->sqlPrefix . "userEvents" => 'eventId, eventName, userId, param1, param2, time',
    );

    $conditions = array(
      'userId' => $this->int($userId),
      'eventId' => $this->int($lastEvent, 'gt')
    );

    return $this->select($columns, $conditions);
  }


  public function getRoomEventsForId($roomId, $lastEventTime) {
    $columns = array(
      $this->sqlPrefix . "userEvents" => 'eventId, eventName, roomId, param1, param2, time',
    );

    $conditions = array(
      'roomId' => $this->int($roomId),
      'eventId' => $this->int($lastEventTime, 'gt')
    );

    return $this->select($columns, $conditions);
  }


  public function getWatchRoomIds($roomId) {
    $watchRoomIds = $this->select(array(
      $this->sqlPrefix . 'watchRooms' => 'userId, roomId'
    ), array(
      'both' => array(
        'roomId' => $this->int($roomId)
      )
    ))->getColumnValues('userId');

    return $watchRoomIds;
  }


  /**
   * This is a bit of a silly function that obtains all rows that correspond either with a userId, a list of groups, or both, and returns a bitfield such that, if a user permission exist, it overrides all groups, or, alternatively, an OR of all group permissions. Thus, it can be used to only query the permission of a single group or a single user, or can be used with both a user and all groups the user belongs to.
   *
   * @param $roomId
   * @param $attribute
   * @param $param
   */
  public function getPermissionsField($roomId, $userId, $groups = array()) {
    $permissions = $this->select(array(
      $this->sqlPrefix . "roomPermissions" => 'roomId, attribute, param, permissions',
    ), array(
      'roomId' => $this->int($roomId),
      'either' => array(
        'both 1' => array(
          'attribute' => 'user',
          'param' => $this->int($userId)
        ),
        'both 2' => array(
          'attribute' => 'group',
          'param' => $this->in($groups)
        )
      )
    ))->getAsArray('attribute');

    $groupBitfield = 0;
    foreach ($permissions AS $permission) {
      if ($permission['attribute'] === 'user') return $permission['permissions']; // If a user permission exists, then it overrides group permissions.
      else $groupBitfield &= $permission['permissions']; // Group permissions, on the other hand, stack. If one group has ['view', 'post'], and another has ['view', 'moderate'], then a user in both groups has all three.
    }

    return $groupBitfield;
  }


  public function getPermissionCache($roomId, $userId) {
    if (!$this->config['roomPermissionsCacheEnabled']) return -1;
    else {
      $permissions = $this->select(array(
        $this->sqlPrefix . 'roomPermissionsCache' => 'roomId, userId, permissions, expires'
      ), array(
        'roomId' => $this->int($roomId),
        'userId' => $this->int($userId)
      ))->getAsArray(false);

      if (!count($permissions)) return -1;
      elseif (time() > $permissions['expires']) return -1;
      else return (int) $permissions['permissions'];
    }
  }


  /**
   * Determines if a given user has certain permissions in a given room.
   *
   * @param fimUser $user
   * @param fimRoom $room
   *
   * @return int A bitfield corresponding with roomPermissions.
   *
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  public function hasPermission($user, $room) {
    $permissionsCached = $this->getPermissionCache($room->id, $user->id);
    if ($permissionsCached > -1) return $permissionsCached; // -1 equals an outdated permission.


    if (!$room->resolve(array('type', 'alias'))) throw new Exception('hasPermission was called without a valid room.'); // Make sure we know the room type and alias in addition to ID.

    if ($room->type === 'otr' || $room->type === 'private') {
      if (!$this->config['privateRoomsEnabled']) return 0;
      elseif (in_array($user->id, fim_reversePrivateRoomAlias($room->alias))) return ROOM_PERMISSION_VIEW | ROOM_PERMISSION_POST | ROOM_PERMISSION_TOPIC; // The logic with private rooms is fairly self-explanatory: roomAlias lists all valid userIds, so check to see if the user is in there.
      else return 0;
    }
    else {
      if (!$user->resolve()) throw new Exception('hasPermission was called without a valid user.'); // Require all user information.
      if (!$room->resolve()) throw new Exception('hasPermission was called without a valid room.'); // Require all room information.



      /* Obtain Data from roomPermissions Table
       * This table is seen as the "final word" on matters. */
      $permissionsBitfield = $this->getPermissionsField($room->id, $user->id, $user->socialGroupIds);



      /* Base calculation -- these are what permisions a user is supposed to have, before userPrivs and certain room properties are factored in. */
      if ($user->privs & ADMIN_ROOMS) $returnBitfield = 65535; // Super moderators have all permissions.
      elseif (in_array($user->groupId, $this->config['bannedUserGroups'])) $returnBitfield = 0; // A list of "banned" user groups can be specified in config. These groups lose all permissions, similar to having userPrivs = 0. But, in the interest of sanity, we don't check it elsewhere.
      elseif ($room->ownerId === $user->id) $returnBitfield = 65535; // Owners have all permissions.
      elseif (($kicks = $this->getKicks(array(
            'userIds' => array($user->id),
            'roomIds' => array($room->id)
          ))->getCount() > 0)
        || $room->parentalAge > $user->parentalAge
        || fim_inArray($user->parentalFlags, $room->parentalFlags)) $returnBitfield = 0; // A kicked user (or one blocked by parental controls) has no permissions. This cannot apply to the room owner.
      elseif ($permissionsBitfield === -1) $returnBitfield = $room->defaultPermissions;
      else $returnBitfield = $permissionsBitfield;



      /* Remove priviledges under certain circumstances. */
      // Remove priviledges that a user does not have for any room.
      if (!($user->privs & USER_PRIV_VIEW)) $returnBitfield &= ~ROOM_PERMISSION_VIEW; // If banned, a user can't view anything.
      if (!($user->privs & USER_PRIV_POST)) $returnBitfield &= ~ROOM_PERMISSION_POST; // If silenced, a user can't post anywhere.
      if (!($user->privs & USER_PRIV_TOPIC)) $returnBitfield &= ~ROOM_PERMISSION_TOPIC;

      // Deleted and archived rooms act similarly: no one may post in them, while only admins can view deleted rooms.
      if ($room->deleted || $room->archived) { // that is, check if a room is either deleted or archived.
        if ($room->deleted && !($user->privs & ADMIN_ROOMS)) $returnBitfield &= ~(ROOM_PERMISSION_VIEW); // Only super moderators may view deleted rooms.

        $returnBitfield &= ~(ROOM_PERMISSION_POST | ROOM_PERMISSION_TOPIC); // And no one can post in them - a rare case where even admins are denied certain abilities.
      }



      /* Update cache and return. */
      $this->updatePermissionsCache($user->id, $room->id, $returnBitfield, ($kicks > 0 ? true : false));

      return $returnBitfield;
    }
  }


  public function updatePermissionsCache($roomId, $userId, $permissions, $isKicked = false) {
    if ($this->config['roomPermissionsCacheEnabled']) {
      $this->upsert($this->sqlPrefix . 'roomPermissionsCache', array(
        'roomId' => $roomId,
        'userId' => $userId,
      ), array(
        'permissions' => $permissions,
        'expires' => $this->now($this->config['roomPermissionsCacheExpires']),
        'isKicked' => $isKicked,
      ));
    }
  }



  /****** Insert/Update Functions *******/

  public function createSession($user) {
    /* Hash is prettttty simple. We create a random 64-bit integer (done as two 32-bit integers, since mt_rand isn't reliable for anything bigger), append microtime (as an integer to it). The resulting integer is around 100 bits, and should be treated as a 128-bit integer.
     * This may seem insecure. It's not, because we prevent guessing by locking users out after x incorrect logins (and a non-existent session token counts as this).
     * Thus, we just need to make sure that there's enough entropy here that the vast, vast majority of possible session tokens are unused. By having a ~64-bit random integer appended to the ~40-bit microtime, we can be pretty sure that no one's going to be able to guess anytime soon. */
    $sessionHash = mt_rand(0x0, 0x7FFFFFFF) . mt_rand(0x0, 0x7FFFFFFF) . (int) (microtime(true) * 10000);

    $this->cleanSessions(); // Whenever a new user logs in, delete all sessions from 15 or more minutes in the past.

    $this->insert($this->sqlPrefix . 'sessions', array(
      'userId' => $user->id,
      'anonId' => $user->anonId,
      'sessionTime' => $this->now(),
      'sessionHash' => $sessionHash,
      'userAgent' => $_SERVER['HTTP_USER_AGENT'],
      'sessionIp' => $_SERVER['REMOTE_ADDR'],
      'clientCode' => $_REQUEST['clientCode']
    ));

    return $sessionHash;
  }



  public function cleanSessions() {
    $this->delete($this->sqlPrefix . 'sessions', array(
      'sessionTime' => $this->now($this->config['sessionExpires'], 'lte')
    ));

    $this->delete($this->sqlPrefix . 'sessionLockout', array(
      'expires' => $this->now(0, 'lte')
    ));
  }



  public function refreshSession($sessionId) {
    $this->update($this->sqlPrefix . "sessions", array(
      'sessionTime' => $this->now(),
    ), array(
      "sessionId" => $sessionId,
    ));
  }



  public function updateUserCaches() {
  }


  /**
   * @param        $roomList - Either 'watchRooms' or 'userFavRooms' (representing those tables)
   * @param        $userData
   * @param        $roomIds
   * @param string $method
   */
  public function editRoomList($roomList, $userData, $roomIds, $method = 'PUT') {
    $rooms = $this->getRooms(array(
      'roomIds' => $roomIds
    ))->getAsArray('roomId');

    if ($method === 'DELETE') {
      foreach ($rooms AS $roomId => $room) {
        $this->delete($this->sqlPrefix . $roomList, array(
          'userId' => $userData['userId'],
          'roomId' => $roomId,
        ));
      }
    }

    if ($method === 'PUT') {
      foreach ($rooms AS $roomId => $room) {
        $this->delete($this->sqlPrefix . $roomList, array(
          'userId' => $userData['userId'],
        ));
      }
    }

    if ($method === 'POST' || $method === 'PUT') {
      foreach ($rooms AS $roomId => $room) {
        if (fim_hasPermission($room, $userData, 'view')) {
          $this->insert($this->sqlPrefix . $roomList, array(
            'userId' => $userData['userId'],
            'roomId' => $roomId,
          ));
        }
      }
    }

    $this->editListCache($roomList, $userData, $roomIds, $method);
  }



  public function editUserLists($userList, $userData, $userIds, $method = 'PUT') {
    $users = $this->getUsers(array(
      'userIds' => $userIds
    ))->getAsArray('userId');


    if ($method === 'DELETE') {
      foreach ($users AS $userId => $userData) {
        $this->delete($this->sqlPrefix . $userList, array(
          'userId' => $userData['userId'],
          'subjectId' => $userId,
        ));
      }
    }

    if ($method === 'PUT') {
      $this->delete($this->sqlPrefix . $userList, array(
        'userId' => $userData['userId'],
      ));
    }

    if ($method === 'POST' || $method === 'PUT') {
      foreach ($users AS $userId => $userData) {
        $conditionArray = array(
          'userId' => $userData['userId'],
          'subjectId' => $userId,
        );
        if ($userList === 'userFriendsList') $conditionArray['status'] = 'request';

        $this->insert($this->sqlPrefix . $userList, $conditionArray);

        if ($userList === 'userFriendsList') $this->createUserEvent('friendRequest', $userId, $userData['userId']);
      }
    }

    $this->editListCache($userList, $userData, $userIds, $method);
  }



  public function editListCache($list, $userData, $itemIds, $method = 'PUT') {
    $listMap = array(
      'userFavRooms' => 'favRooms',
      'watchRooms' => 'watchRooms',
      'userIgnoreList' => 'ignoredUsers',
      'userFriendsList' => 'friendedUsers'
    );

    $listEntries = explode(',', $userData[$listMap[$list]]);

    if ($method === 'PUT') $listEntries = $itemIds;
    elseif ($method === 'DELETE') $listEntries = array_diff($listEntries, $itemIds);
    elseif ($method === 'POST') {
      foreach ($itemIds AS $item) $listEntries[] = $item;
    }

    $listEntries = array_unique($listEntries);
    sort($listEntries);

    $this->update($this->sqlPrefix . 'users', array(
      $this->sqlPrefix . $listMap[$list] => implode(',', $listEntries)
    ), array(
      'userId' => $userData['userId'],
    ));
  }



  /**
   * @param $action string - Either 'friend' or 'deny'.
   * @param $userId
   * @param $subjectId
   */
  public function alterFriendRequest($action, $userId, $subjectId) {
    $conditionArray = array(
      'userId' => $userId,
      'subjectId' => $subjectId
    );

    if ($action === 'friend' || $action === 'deny') {
      $this->update($this->sqlPrefix . 'userFriendsList', array(
        'status' => $action,
      ), $conditionArray);
    }
    elseif ($action === 'unfriend') {
      $this->delete($this->sqlPrefix . 'userFriendsList', $conditionArray);
    }
  }



  public function setCensorList($roomId, $listId, $status) {
    $this->startTransaction();

    $this->modLog('unblockCensorList', "$roomId,$listId");

    $this->insert($this->sqlPrefix . "censorBlackWhiteLists", array(
      'roomId' => $roomId,
      'listId' => $listId,
      'status' => $status
    ), array(
      'status' => $status,
    ));

    $this->endTransaction();
  }



  public function createPrivateRoom($roomAlias, $userIds) {
    $userNames = $this->getUsers(array(
      'userIds' => $userIds
    ))->getColumnValues('userName');

    if (count($userNames) !== count($userIds)) throw new Exception('Invalid userIds in createPrivateRooms().');

    $roomId = $this->editRoom(false, array(
      'roomType' => 'private',
      'roomAlias' => $roomAlias,
      'roomName' => 'Private Conversation Between ' . implode(', ', $userNames),
      'defaultPermissions' => 0
    ));

    foreach ($userIds AS $userId) $this->setPermission($roomId, 'user', $userId, ROOM_PERMISSION_VIEW + ROOM_PERMISSION_POST + ROOM_PERMISSION_TOPIC); // Note: Originally, I had intentioned that this would be automatic. Right now, it is not, but it would be fairly easy to remedy by adding the appropriate code to hasPermission. For now, I think it would be best to do both in some regard.

    return $roomId;
  }



  public function setPermission($roomId, $attribute, $param, $permissionsMask) {
    /* Start Transaction */
    $this->startTransaction();


    /* Modlog */
    $this->modLog('setPermission', "$roomId,$attribute,$param,$permissionsMask");


    /* Insert or Replace The Old Permission Setting */
    $this->insert($this->sqlPrefix . 'roomPermissions', array(
      'roomId' => $roomId,
      'attribute' => $attribute,
      'param' => $param,
      'permissions' => $permissionsMask
    ), array(
      'permissions' => $permissionsMask
    ));


    /* Delete Relevant Cached Entries, Forcing Cache Regeneration When Next Needed */
    /* TODO (obviously) */
    switch ($attribute) {
      case 'user':
        $users = array($param);
        break;

      case 'group':
        $users = $this->getSocialGroupMembers(array(
          'groupIds' => array($param),
          'type' => array('member', 'moderator')
        ))->getColumnValues('userId');
        break;
    }

    $this->delete($this->prefix . 'roomPermissionsCache', array(
      'roomId' => $roomId,
      'userId' => $this->in($users)
    ));


    /* End Transaction */
    $this->endTransaction();
  }



  public function markMessageRead($messageId, $userId)
  {
    if ($this->config['enableUnreadMessages']) {
      $this->delete($this->sqlPrefix . "unreadMessages", array(
        'messageId' => $messageId,
        'userId'    => $userId
      ));
    }
  }


  /**
   * Changes the user's status.
   *
   * @param string $status
   * @param bool   $typing
   */
  public function setUserStatus($roomId, $status = null, $typing = null) {
    global $user; // TODO

    $conditions = array(
      'userId' => $user->id,
      'roomId' => $roomId
    );

    $data = array(
      'time' => $this->now()
    );

    if (!is_null($typing)) $data['typing'] = (bool) $typing;
    if (!is_null($status)) $data['status'] = $status;

    $this->upsert($this->sqlPrefix . 'ping', $conditions, $data);
  }


  /*
   * Store message does not check for permissions. Make sure that all permissions are cleared before calling storeMessage.
   */
  public function storeMessage($messageText, $messageFlag, $user, $room)
  {
    global $generalCache; // TODO


    $user->resolve();
    $room->resolve();


    /* Format Message Text */
    if (!in_array($messageFlag, array('image', 'video', 'url', 'email', 'html', 'audio', 'text'))) {
      $messageText = $this->censorParse($messageText, $room->id);
    }

    list($messageTextEncrypted, $encryptIV, $encryptSalt) = $this->getEncrypted($messageText);


    $this->startTransaction();


    /* Insert Message Data */
    // Insert into permanent datastore.
    $this->insert($this->sqlPrefix . "messages", array(
      'roomId'   => $room->id,
      'userId'   => $user->id,
      'text'     => $messageTextEncrypted,
      'textSha1' => sha1($messageText),
      'salt'     => $encryptSalt,
      'iv'       => $encryptIV,
      'ip'       => $_SERVER['REMOTE_ADDR'],
      'flag'     => $messageFlag,
      'time'     => $this->now(),
    ));
    $messageId = $this->insertId;


    // Insert into cache/memory datastore.
    $this->insert($this->sqlPrefix . "messagesCached", array(
      'messageId'         => $messageId,
      'roomId'            => $room->id,
      'userId'            => $user->id,
      'userName'          => $user->name,
      'userGroupId'       => $user->mainGroupId,
      'avatar'            => $user->avatar,
      'profile'           => $user->profile,
      'userNameFormat'    => $user->nameFormat,
      'messageFormatting' => $user->messageFormatting,
      'text'              => $messageText,
      'flag'              => $messageFlag,
      'time'              => $this->now(),
    ));
    $messageId2 = $this->insertId;



    /* Generate (and Insert) Key Words */
    $keyWords = $this->getKeyWordsFromText($messageText);
    $this->storeKeyWords($keyWords, $messageId, $user->id, $room->id);



    /* Update the Various Caches */
    // Update room caches.
    $this->update($this->sqlPrefix . "rooms", array(
      'lastMessageTime' => $this->now(),
      'lastMessageId'   => $messageId,
      'messageCount'    => $this->type('equation', '$messageCount + 1')
    ), array(
      'roomId' => $room->id,
    ));


    // Update the messageIndex if appropriate
    $room = $this->getRoom($room->id); // Get the new room data. (TODO: UPDATE ... RETURNING for PostGreSQL)

    if ($room->messageCount % $this->config['messageIndexCounter'] === 0) { // If the current messages in the room is divisible by the messageIndexCounter, insert into the messageIndex cache. Note that we are hoping this is because of the very last query which incremented this value, but it is impossible to know for certain (if we tried to re-order things to get the room data first, we still run this risk, so that doesn't matter; either way accuracy isn't critical). Postgres would avoid this issue, once implemented.
      $this->insert($this->sqlPrefix . "messageIndex", array(
        'roomId'    => $room->id,
        'interval'  => $room->messageCount,
        'messageId' => $messageId
      ));
    }


    // Update the messageDates if appropriate
    $lastDayCache = (int) $generalCache->get('fim3_lastDayCache');

    $currentTime = time();
    $lastMidnight = $currentTime - ($currentTime % $this->config['messageTimesCounter']); // Using some cool math (look it up if you're not familiar), we determine the distance from the last even day, then get the time of the last even day itself. This is the midnight reference point.

    if ($lastDayCache < $lastMidnight) { // If the most recent midnight comes after the period at which the time cache was last updated, handle that. Note that, though rare-ish, this query may be executed by a few different messages. It's not a big deal, since the primary key will prevent any duplicate entries, but still.
      $generalCache->set('fim3_lastDayCache', $lastMidnight); // Update the quick cache.

      $this->insert($this->sqlPrefix . "messageDates", array(
        'time'      => $lastMidnight,
        'messageId' => $messageId
      ));
    }


    // Update user caches
    $this->update($this->sqlPrefix . "users", array(
      'messageCount' => $this->type('equation', '$messageCount + 1'),
    ), array(
      'userId' => $user->id,
    ));


    // Insert or update a user's room stats.
    $this->upsert($this->sqlPrefix . "roomStats", array(
      'userId'   => $user->id,
      'roomId'   => $room->id,
    ), array(
      'messages' => $this->type('equation', '$messages + 1')
    ));


    // Increment the messages counter.
    $this->incrementCounter('messages');


    // Delete old messages from the cache, based on the maximum allowed rows.
    if ($messageId2 > $this->config['cacheTableMaxRows']) {
      $this->delete($this->sqlPrefix . "messagesCached",
        array('id' => array(
          'cond'  => 'lte',
          'value' => (int) ($messageId2 - $this->config['cacheTableMaxRows'])
        )
        ));
    }


    // If the contact is a private communication, create an event and add to the message unread table.
    if ($room->type === ROOM_TYPE_PRIVATE) {
      foreach (fim_reversePrivateRoomAlias($room->alias) AS $sendToUserId) { // Todo: use roomAlias.
        if ($sendToUserId == $user['userId']) {
          continue;
        } else {
          createUnreadMessage($sendToUserId, $user, $room, $messageId);
        }
      }
    }
    else {
      foreach ($this->getWatchRoomIds($room->id) AS $sendToUserId) {
        createUnreadMessage($sendToUserId, $user, $room, $messageId);
      }
    }


    $this->endTransaction();


    // Return the ID of the inserted message.
    return $messageId;
  }


  public function createUnreadMessage($sendToUserId, $user, $room, $messageId) {
    $this->createUserEvent('missedMessage', $sendToUserId, $room->id, $messageId);

    if ($this->config['enableUnreadMessages']) {
      $this->upsert($this->sqlPrefix . "unreadMessages", array(
        'userId'            => $sendToUserId,
        'roomId'            => $room->id
      ), array(
        'senderId'          => $user->id,
        'senderName'        => $user->name,
        'senderNameFormat'  => $user->nameFormat,
        'roomName'          => $room->name,
        'messageId'         => $messageId,
        'time'              => $this->now(),
      ));
    }
  }



  /**
   * @param     $counterName
   * @param int $incrementValue
   *
   * @return bool
   */
  public function incrementCounter($counterName, $incrementValue = 1)
  {
    if ($this->update($this->sqlPrefix . "counters", array(
      'counterValue' => $this->type('equation', '$counterValue + ' . (int) $incrementValue)
    ), array(
      'counterName' => $counterName,
    ))) {
      return true;
    } else {
      return false;
    }
  }



  /**
   * @param $eventName
   * @param $userId
   * @param $roomId
   * @param $messageId
   * @param $param1
   * @param $param2
   * @param $param3
   */
  public function createEvent($eventName, $userId = 0, $roomId = 0, $messageId = 0, $param1 = '', $param2 = '', $param3 = '')
  {
    if ($this->config['enableEvents']) {
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


  public function createUserEvent($eventName, $userId, $param1 = '', $param2 = '')
  {
    if ($this->config['enableEvents']) {
      $this->insert($this->sqlPrefix . "userEvents", array(
        'eventName' => $eventName,
        'userId'    => $userId,
        'param1'    => $param1,
        'param2'    => $param2,
        'time'      => $this->now(),
      ));
    }
  }



  /**
   * @param $words
   * @param $messageId
   * @param $userId
   * @param $roomId
   */
  public function storeKeyWords($words, $messageId, $userId, $roomId)
  {
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
    global $user; // TODO

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
    global $user;// TODO

    if ($this->insert($this->sqlPrefix . "fullLog", array(
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



  /**
   * Accesslog container.
   * Accesslog is mainly interested in analytical information about requests -- not about a security log. It can be used to see which users are most active, which clients are most popular, how long a script takes to execute, and where users visit from most.
   *
   * @param string $action
   * @param array  $data
   *
   * @return bool
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */

  public function accessLog($action, $info)
  {
    global $user, $globalTime; // TODO

    if ($this->config['accessLogEnabled']) {
      if ($this->insert($this->sqlPrefix . "accessLog", array(
        'userId' => $user['userId'],
        'action' => $action,
        'info ' => json_encode($info),
        'time'   => $globalTime,
        'executionTime' => $globalTime - time(),
        'userAgent' => $_SERVER['HTTP_USER_AGENT'],
        'ipAddress' => $_SERVER['REMOTE_ADDR'],
      ))
      ) {
        return true;
      } else {
        return false;
      }
    }

    return false;
  }



  /* Originally from fim_general.php TODO */
//  protected function explodeEscaped($delimiter, $string, $escapeChar = '\\') {
//    $string = str_replace($escapeChar . $escapeChar, fim_encodeEntities($escapeChar), $string);
//    $string = str_replace($escapeChar . $delimiter, fim_encodeEntities($delimiter), $string);
//    return array_map('fim_decodeEntities', explode($delimiter, $string));
//  }

  public function generateBitfield($array) {
    $bitfield = 0;

    foreach ($array AS $bit => $true) {
      if ($true)  $bitfield += $bit;
    }
  }




/****** MESSAGE TEXT FUNCTIONS ******/

  /**
   * Generates keywords to enter into the archive search store.
   *
   * @param string $text - The text to generate the big keywords from.
   * @return array - The keywords found.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  // TODO: Shouldn't be part of fim_database.php.
  public function getKeyWordsFromText($text) {
    global $sqlPrefix, $user; // TODO

    $string = $this->makeSearchable($text);

    $stringPieces = array_unique(explode(' ', $string));
    $stringPiecesAdd = array();

    foreach ($stringPieces AS $piece) {
      if (strlen($piece) >= $this->config['searchWordMinimum'] &&
        strlen($piece) <= $this->config['searchWordMaximum'] &&
        !in_array($piece, $this->config['searchWordOmissions'])) $stringPiecesAdd[] = str_replace($this->config['searchWordConvertsFind'], $this->config['searchWordConvertsReplace'], $piece);
    }

    if (count($stringPiecesAdd) > 0) {
      sort($stringPiecesAdd);

      return $stringPiecesAdd;
    }
    else {
      return array();
    }
  }



  /**
   * Automatically censors text using censorWords replace.
   *
   * @param text Text to censor.
   * @param roomId The room ID to use to get applicable censor words.
   *
   * @TODO: Update to remove noparse, and maybe the other stuff. I'm not really sure.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  public function censorParse($text, $roomId = 0) {
    foreach ($this->getCensorWordsActive($roomId, array('replace'))->getAsArray(true) AS $word) {
      $text = str_ireplace($word, $word['param'], $text);
    }

    return $text;
  }



  /**
   * Retrieve an encrypted version of text.
   *
   * @param $messageText
   *
   * @return [$messageTextEncrypted, $iv, $saltNum]
   *
   */
  public function getEncrypted($messageText) {
    global $salts, $encrypt;

    // Encrypt Message Text
    if ($salts && $encrypt) { // Only encrypt if we have both set salts and encrypt is enabled.
      list($messageTextEncrypted, $iv, $saltNum) = fim_encrypt($messageText); // Encrypt the values and return the new data, IV, and saltNum.
    }
    else { // No encyption
      $messageTextEncrypted = $messageText;
      $iv = ''; // Use an empty IV - it will be ignored by the decryptor.
      $saltNum = 0; // Same as with the IV, salt keys of "0" are ignored.
    }

    return array($messageTextEncrypted, $iv, $saltNum);
  }



  /**
   * OVERRIDE
   * Overrides the normal function to use fimDatabaseResult instead.
   */
  protected function databaseResultPipe($queryData, $query, $driver) {
    return new fimDatabaseResult($queryData, $query, $driver);
  }
}

class fimDatabaseResult extends databaseResult {

  /**
   * @return array
   *
   * @internal This function may use too much memory. I'm not... exactly sure how to fix this.
   */
  function getAsRooms() {
    $rooms = $this->getAsArray('roomId');
    $return = array();

    foreach ($rooms AS $roomId => $room) {
      $return[$roomId] = new fimRoom($room);
    }

    return $return;
  }

  
  /**
   * @return array
   *
   * @internal This function may use too much memory. I'm not... exactly sure how to fix this.
   */
  function getAsUsers() {
    $users = $this->getAsArray('userId');
    $return = array();

    foreach ($users AS $userId => $user) {
      $return[$userId] = new fimUser($user);
    }

    return $return;
  }



  function getAsRoom() {
    return new fimRoom($this->getAsArray(false));
  }



  function getAsUser() {
    return new fimUser($this->getAsArray(false));
  }

}

?>