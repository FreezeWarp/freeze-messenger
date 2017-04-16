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
    public $userColumns = 'userId, userName, userNameFormat, profile, avatar, userGroupId, socialGroupIds, messageFormatting, options, defaultRoomId, userParentalAge, userParentalFlags, privs, lastSync';
    public $userPasswordColumns = 'passwordHash, passwordFormat, passwordResetNow, passwordLastReset';
    public $userHistoryColumns = 'userId, userName, userNameFormat, profile, avatar, userGroupId, socialGroupIds, messageFormatting, options, userParentalAge, userParentalFlags, privs';
    public $roomHistoryColumns = 'roomId, roomName, roomTopic, options, ownerId, defaultPermissions, roomParentalAge, roomParentalFlags';
    public $errorFormatFunction = 'fimError';
    protected $config;


    const decodeError = "CORRUPTED";
    const decodeExpired = "EXPIRED";

    /***
     * Static String Manipulation
     */

    /**
     * Converts a CSV list (e.g. "1,2,3") to a packed binary equivilent.
     * As currently implemented, this should achieve the lowest possible size without performing compression. Numbers in the CSV list are converted to base-15, which are then packed to a hexadecimal string, with "f" being the delimiter.
     * Thus, "1,2,3" (6  bytes) will convert to 0x1a2a3, which is 20 bits/2.5 bytes.
     * For the purposes of ensuring a string is fully read back, however, it will start and end with "ff." (We could also specify the size of the string at the beginning, but the "ff" is marginally faster, and easier to encode.)
     * @param string $csv
     */
    public static function packList(array $list) {
        if (count($list) === 0) {
            return pack("H*", 'ffff');
        }
        else {
            $packString = '';

            foreach ($list AS $int)
                $packString .= 'f' . base_convert($int, 10, 15);

            return pack("H*", 'f' . $packString . 'ff');
        }
    }


    public static function unpackList($blob) {
        if (strlen($blob) === 0)
            return [];

        else {
            $decoded = rtrim(unpack("H*", $blob)[1], '0');

            if (substr($decoded, -2) !== 'ff' || substr($decoded, 0, 2) !== 'ff')
                return fimDatabase::decodeError;
                //throw new Exception('Bad parity: ' . $decoded); // For development only

            elseif (strlen($decoded) === 4)
                return [];

            else {
                $array = explode('f', substr($decoded, 2, -2));

                return array_map(function($value) {
                    return base_convert($value, 15, 10);
                }, $array);
            }
        }
    }

    /**
     * This operates like fimDatabase::packCSV, except it also stores a cache expiration time as the first entry.
     *
     * @param string $csv
     * @param int $cacheLength
     */
    public static function packListCache(array $list, int $cacheLength = 5 * 60) {
        array_unshift($list, time() + $cacheLength);

        return fimDatabase::packList($list);
    }

    public static function unpackListCache($blob) { //var_dump($blob); die(';7');
        if (strlen($blob) === 0) { // Happens when uninitialised, generally, but could also happen as part of a cache cleanup -- the most efficient way to invalidate is simply to null.
            return fimDatabase::decodeExpired;
        }
        else {
            $list = fimDatabase::unpackList($blob);

            if ($list === fimDatabase::decodeError)
                return fimDatabase::decodeError;

            elseif ($list[0] < time())
                return fimDatabase::decodeExpired;

            else {
                unset($list[0]); // One imagines this is faster than array_slice
                return $list;
            }
        }
    }


    public static function makeSearchable($string) {
        global $config;

        // Romanise first, to allow us to apply custom replacements before letting the built-in functions do their job
        $string = str_replace(array_keys($config['romanisation']), array_values($config['romanisation']), $string);

        // Apply the built-in functions, if available
        if (function_exists('transliterator_transliterate'))
            $string = transliterator_transliterate($config['searchTransliteration'], $string);
        elseif (function_exists('iconv'))
            $string = strtolower(iconv('utf-8', 'us-ascii//TRANSLIT', $string));

        // Replace punctuation with space (e.g. "a.b" should be treated as "a b" not "ab").
        $string = str_replace($config['searchWordPunctuation'], ' ', $string);

        // If searchWhiteList is set, then we remove any characters not in the whitelist. By default, it is simply a-zA-Z
        if ($config['searchWhiteList'])
            $string = preg_replace('/[^' . $config['searchWhiteList'] . ']/', '', $string);

        // Get rid of extra spaces.
        $string = preg_replace('/\s+/', ' ', $string);

        return $string;
    }



    /****** Utility Functions ******/
    public function argumentMerge($defaults, $args) {
        $returnArray = [];

        foreach ($args AS $name => $arg) {
            if (!in_array($name, array_keys($defaults))) throw new Exception('Unknown argument: ' . $name);
        }

        foreach ($defaults AS $name => $default) {
            if (in_array($name, array_keys($args))) $returnArray[$name] = $args[$name];
            elseif (!is_null($default)) $returnArray[$name] = $default;
        }

        return $returnArray;
    }



    /****** Get Functions *****/


    /**
     * Run a query to obtain users who appear "active."
     * Scans table `ping`, and links in tables `rooms` and `users`, particularly for use in hasPermission().
     * Returns columns ping[status, typing, ptime, proomId, puserId], rooms[roomId, roomName, roomTopic, owner, defaultPermissions, roomParentalAge, roomParentalFlags, options], and users[userId, userName, userNameFormat, userGroupId, socialGroupIds, status]
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

        $options = $this->argumentMerge(array(
            'onlineThreshold' => $config['defaultOnlineThreshold'],
            'roomIds'         => array(),
            'userIds'         => array(),
            'typing'          => null,
            'statuses'        => array()
        ), $options);


        $columns = array(
            $this->sqlPrefix . "ping"  => 'status pstatus, typing, time ptime, roomId proomId, userId puserId',
            $this->sqlPrefix . "rooms" => 'roomId, roomIdEncoded, roomName, ownerId, defaultPermissions, roomParentalAge, roomParentalFlags, options',
            $this->sqlPrefix . "users" => 'userId, userName, userNameFormat, userGroupId, socialGroupIds, status',
        );


        if (count($options['roomIds']) > 0) $conditions['both']['proomId'] = $this->in($options['roomIds']);
        if (count($options['userIds']) > 0) $conditions['both']['puserId'] = $this->in($options['userIds']);
        if (count($options['statuses']) > 0) $conditions['both']['status'] = $this->in($options['statuses']);

        if (isset($options['typing'])) $conditions['both']['typing'] = $this->bool($options['typing']);


        $conditions['both'] = array(
            'ptime'   => $this->int(time() - $options['onlineThreshold'], 'gt'),
            'proomId' => $this->col('roomIdEncoded'),
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
        $options = $this->argumentMerge(array(
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



    public function getCensorWords($options = array(), $sort = array('listId' => 'asc', 'word' => 'asc'), $limit = 0, $pagination = 1)
    {
        $options = $this->argumentMerge(array(
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
        $options = $this->argumentMerge(array(
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
        $options = $this->argumentMerge(array(
            'userIds'         => array(),
            'roomIds'         => array(),
            'fileIds'         => array(),
            'vfileIds'        => array(),
            'sha256hashes'    => array(),
            'fileTypes'       => array(),
            'creationTimeMax' => 0,
            'creationTimeMin' => 0,
            'fileNameSearch'  => '',
            'parentalAgeMin'  => 0,
            'parentalAgeMax'  => 0,
            'includeContent'  => false,
            'includeThumbnails'=> false,
        ), $options);


        $columns = array(
            $this->sqlPrefix . "files"        => 'fileId, fileName, fileType, creationTime, userId, fileParentalAge, fileParentalFlags, roomIdLink, source',
            $this->sqlPrefix . "fileVersions" => 'fileId vfileId, sha256hash, size',
        );

        if ($options['includeContent']) $columns[$this->sqlPrefix . 'fileVersions'] .= ', salt, iv, contents';


        // This is a method of optimisation I'm trying. Basically, if a very small sample is requested, then we can optimise by doing those first. Otherwise, the other filters are usually better performed first.
        foreach (array('fileIds' => 'fileId', 'vfileIds' => 'vfileId', 'sha256hashes' => 'sha256hash') AS $group => $key) {
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
        if (!isset($conditions['both']['sha256hashes']) && count($options['sha256hashes']) > 0) $conditions['both']['sha256hash'] = $this->in($options['sha256hashes']);

        if ($options['sizeMin'] > 0) $conditions['both']['size'] = $this->int($options['size'], 'gte');
        if ($options['sizeMax'] > 0) $conditions['both']['size'] = $this->int($options['size'], 'lte');


        // Get Thumbnails, if Requested
        if ($options['includeThumbnails']) {
            $columns[$this->sqlPrefix . 'fileVersions'] .= ', versionId';
            $columns[$this->sqlPrefix . 'fileVersionThumbnails'] = 'versionId tversionId, scaleFactor, width, height';

            $conditions['both']['versionId'] = $this->col('tversionId');
        }


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
        $options = $this->argumentMerge(array(
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
            $columns[$this->sqlPrefix . "rooms"] = 'roomId kroomId, roomName, ownerId, options, defaultPermissions, roomParentalFlags, roomParentalAge';
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
        ));
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
        global $config;

        $options = $this->argumentMerge(array(
            'roomIds'           => array(),
            'userIds'           => array(),
            'messageTextSearch' => '',
        ), $options);

        $searchArray = array();
        foreach (explode(',', $options['messageTextSearch']) AS $searchVal) {
            $searchArray[] = str_replace(
                array_keys($config['romanisation']),
                array_values($config['romanisation']),
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



    public function getMessageIdsFromSearchCache($options, $limit, $page) {
        $options = $this->argumentMerge(array(
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
        $options = $this->argumentMerge(array(
            'room'              => false,
            'messageIds'        => array(),
            'userIds'           => array(),
            'messageTextSearch' => '', // Overwrites messageIds.
            'showDeleted'       => false,
            'messagesSince'     => 0,
            'messageIdStart'    => 0,
            'messageIdEnd'      => 0,
            'messageDateMax'    => 0,
            'messageDateMin'    => 0,
            'messageHardLimit'  => 40,
            'page'              => 0,
            'archive'           => false
        ), $options);


        if (!($options['room'] instanceof fimRoom))
            throw new Exception('fim_database->getMessages requires the \'room\' option to be an instance of fimRoom.');


        /* Create a $messages list based on search parameter. */
        if (strlen($options['messageTextSearch']) > 0) {
            if (!$options['archive']) {
                $this->triggerError('The "messageTextSearch" option in getMessages can only be used if "archive" is set to true.', array('Options' => $options), 'validation');
            } else {
                /* Run the Query */
                $searchMessageIds = $this->getMessagesFromPhrases(array(
                    'roomIds' => $options['room']->id,
                    'userIds' => $options['userIds'],
                    'messageTextSearch' => $options['messageTextSearch'],
                ), null, $limit, $page)->getAsArray('messageId');

                $searchMessages = array_keys($searchMessageIds);


                /* Modify the Request Filter for Messages */
                if ($searchMessages) $options['messageIds'] = fim_arrayValidate($searchMessages, 'int', true);
                else                 $options['messageIds'] = array(0); // This is a fairly dirty approach, but it does work for now. TODO
            }
        }


        /* roomId */
        $conditions['both']['roomId'] = $options['room']->id;


        /* Query via the Archive */
        if ($options['archive']) {
            $columns = array(
                $this->sqlPrefix . "messages" => 'messageId, time, iv, salt, roomId, userId, deleted, flag, text',
                $this->sqlPrefix . "users"    => 'userId muserId, userName, userGroupId, socialGroupIds, userNameFormat, avatar, messageFormatting'
            );

            $conditions['both']['muserId'] = $this->col('userId');
        }

        /* Access the Stream */
        else {
            if ($options['room']->isPrivateRoom())
                $columns = [$this->sqlPrefix . "messagesCached" => "messageId, roomId, time, flag, userId, text"];
            else
                $columns = [$this->sqlPrefix . "messagesCached" => "messageId, roomId, time, flag, userId, userName, userGroupId, socialGroupIds, userNameFormat, avatar, messageFormatting, text"];
        }


        /* Modify Query Data for Directives
         * TODO: Remove messageIdStart and messageIdEnd, replacing with $limit and $pagination (combined with other operators). */
        if ($options['messageDateMax'] > 0) $conditions['both']['time 1'] = $this->int($options['messageDateMax'], 'lte');
        if ($options['messageDateMin'] > 0) $conditions['both']['time 2'] = $this->int($options['messageDateMin'], 'gte');

        if ($options['messageIdStart'] > 0) {
            $conditions['both']['messageId 3'] = $this->int($options['messageIdStart'], 'gte');
            $conditions['both']['messageId 4'] = $this->int($options['messageIdStart'] + $options['messageHardLimit'], 'lt');
        } elseif ($options['messageIdEnd'] > 0) {
            $conditions['both']['messageId 3'] = $this->int($options['messageIdEnd'], 'lte');
            $conditions['both']['messageId 4'] = $this->int($options['messageIdEnd'] - $options['messageHardLimit'], 'gt');
        }

        if ($options['showDeleted'] === false && $options['archive'] === true) $conditions['both']['deleted'] = $this->bool(false);
        if (count($options['messageIds']) > 0) $conditions['both']['messageId'] = $this->in($options['messageIds']); // Overrides all other message ID parameters; TODO
        if (count($options['userIds']) > 0) $conditions['both']['userId'] = $this->in($options['userIds']);


        $messages = $this->select($columns, $conditions, $sort, $options['messageHardLimit'], $options['page']);

        return $messages;
    }


    public function getMessage($room, $messageId) {
        return $this->getMessages(array(
            'room' => $room,
            'messageIds' => array($messageId),
            'archive' => true,
        ));
    }



    public function getModLog($options, $sort = array('time' => 'asc'), $limit = 0, $pagination = 1) {

        $options = $this->argumentMerge(array(
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
        $options = $this->argumentMerge(array(
            'userIds' => array(),
            'roomIds' => array(),
        ), $options);


        $columns = array(
            $this->sqlPrefix . 'roomStats' => 'roomId sroomId, userId suserId, messages',
            $this->sqlPrefix . 'users'     => 'userId, userName, privs, userNameFormat, userParentalFlags, userParentalAge',
            $this->sqlPrefix . 'rooms'     => 'roomId, roomIdEncoded, roomName, ownerId, defaultPermissions, roomParentalFlags, roomParentalAge, options, messageCount',
        );


        $conditions['both'] = array(
            'suserId' => $this->col('userId'),
            'sroomId' => $this->col('roomIdEncoded'),
        );


        if (count($options['roomIds']) > 0) $conditions['both']['sroomId a'] = $this->in($options['roomIds']);
        if (count($options['userIds']) > 0) $conditions['both']['suserId a'] = $this->in($options['userIds']);


        return $this->select($columns, $conditions, $sort);
    }



    public function getRooms($options, $sort = array('roomId' => 'asc'), $limit = 0, $pagination = 1)
    {
        $options = $this->argumentMerge(array(
            'roomIds'            => [],
            'roomNames'          => [],
            'roomAliases'        => [],
            'ownerIds'           => [],
            'parentalAgeMin'     => 0,
            'parentalAgeMax'     => 0,
            'messageCountMin'    => 0,
            'messageCountMax'    => 0,
            'lastMessageTimeMin' => 0,
            'lastMessageTimeMax' => 0,
            'showDeleted'        => false,
            'roomNameSearch'     => false,
            'columns'            => ['roomId', 'roomName', 'roomAlias', 'roomTopic', 'ownerId', 'defaultPermissions', 'roomParentalFlags', 'roomParentalAge', 'options', 'lastMessageId', 'lastMessageTime', 'messageCount'],
        ), $options);

        $columns = [$this->sqlPrefix . 'rooms' => $options['columns']];


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
        return $this->where($conditions)->sortBy($sort)->select($columns);
    }



    public function getRoom($roomId) : fimRoom
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
        $options = $this->argumentMerge(array(
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
        $options = $this->argumentMerge(array(
            'userIds'        => array(),
            'userNames'      => array(),
            'userNameSearch' => false,
            'bannedStatus'   => false,
            'columns' => $this->userColumns, // csvstring a list of columns to include in the return; if not specified, this will default to almost everything except passwords
            'includePasswords' => false, // bool shorthand to add password fields -- whatever they are -- to the otherwise specified columns
        ), $options);


        $columns = array(
            $this->sqlPrefix . "users" => $options['columns'] . ($options['includePasswords'] ? ', ' . $this->userPasswordColumns : '') // For this particular request, you can also access user password information using the includePasswords flag.
        );


        $conditions['both'] = array();


        /* Modify Query Data for Directives */
        if ($options['bannedStatus'] === 'banned')
            $conditions['both']['!options'] = $this->int(1, 'bAnd'); // TODO: Test!
        if ($options['bannedStatus'] === 'unbanned')
            $conditions['both']['options'] = $this->int(1, 'bAnd'); // TODO: Test!


        if (isset($options['hasAdminPrivs']) && count($options['hasAdminPrivs']) > 0) {
            foreach ($options['hasAdminPrivs'] AS $adminPriv) $conditions['both']['adminPrivs'] = $this->int($adminPriv, 'bAnd');
        }


        if (count($options['userIds']) > 0)
            $conditions['both']['either']['userId'] = $this->in($options['userIds']);
        if (count($options['userNames']) > 0)
            $conditions['both']['either']['userName 1'] = $this->in($options['userNames']);
        if ($options['userNameSearch'])
            $conditions['both']['either']['userName 2'] = $this->type('string', $options['userNameSearch'], 'search');


        return $this->select($columns, $conditions, $sort);
    }



    public function getUser($userId)
    {
        return $this->getUsers(array(
            'userIds' => array($userId)
        ))->getAsUser();
    }



    public function getSessions($options = array(), $sort = array('sessionId' => 'asc'), $limit = 0, $pagination = 1)
    {
        $options = $this->argumentMerge(array(
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
        $options = $this->argumentMerge(array(
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
            $this->sqlPrefix . "roomEvents" => 'eventId, eventName, roomId, param1, param2, time',
        );

        $conditions = array(
            'roomId' => $this->int($roomId),
            'eventId' => $this->int($lastEventTime, 'gt')
        );

        return $this->select($columns, $conditions);
    }


    /**
     * Gets the entries from the watchRooms table corresponding with a single roomId. fimRoom($roomId)->watchRooms should generally be used instead, since it implements additional caching.
     *
     * @param $roomId
     * @return mixed
     * @throws Exception
     */
    public function getWatchRoomUsers($roomId) {
        $watchRoomIds = $this->select(array(
            $this->sqlPrefix . 'watchRooms' => 'userId, roomId'
        ), array(
            'roomId' => $this->int($roomId)
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
        global $config;

        if (!$config['roomPermissionsCacheEnabled']) return -1;
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



    /* todo: cache calls */
    /**
     * @param array $rooms
     * @param bool $attribute enum("user", "group")
     * @param array $params
     * @return mixed
     * @throws Exception
     */
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
     * Determines if a given user has certain permissions in a given room.
     *
     * @param fimUser $user
     * @param fimRoom $room
     *
     * @return int A bitfield corresponding with roomPermissions.
     *
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function hasPermission(fimUser $user, fimRoom $room) {
        global $config;

        if ($room->isPrivateRoom()) {
            $userIds = $room->getPrivateRoomMemberIds();

            if ($room->type === 'otr' && !$config['otrRoomsEnabled'])
                return 0;

            elseif ($room->type === 'private' && !$config['privateRoomsEnabled'])
                return 0;

            elseif (count($userIds) > $config['privateRoomMaxUsers'])
                return 0;

            else {
                $users = $room->getPrivateRoomMembers(); // TODO: rewrite to use cached $user->exists() checks

                if (count($users) !== count($userIds)) // This checks for invalid users, as getPrivateRoomMembers() will only return members who exist in the database, while getPrivateRoomMemberIds() returns all ids who were specified when the fimRoom object was created.
                    return 0;

                elseif (in_array($user->id, $userIds))
                    return ROOM_PERMISSION_VIEW | ROOM_PERMISSION_POST; // The logic with private rooms is fairly self-explanatory: roomAlias lists all valid userIds, so check to see if the user is in there.

                else
                    return 0;
            }
        }
        else {
            $permissionsCached = $this->getPermissionCache($room->id, $user->id);
            if ($permissionsCached > -1) return $permissionsCached; // -1 equals an outdated permission.

            if (!$user->resolve(array('socialGroupIds', 'parentalAge', 'parentalFlags'))) throw new Exception('hasPermission was called without a valid user.'); // Make sure we know the room type and alias in addition to ID.



            /* Obtain Data from roomPermissions Table
             * This table is seen as the "final word" on matters. */
            $permissionsBitfield = $this->getPermissionsField($room->id, $user->id, $user->socialGroupIds);


            /* Base calculation -- these are what permisions a user is supposed to have, before userPrivs and certain room properties are factored in. */
            if ($user->hasPriv('modRooms')) $returnBitfield = 65535; // Super moderators have all permissions.
            elseif (in_array($user->groupId, $config['bannedUserGroups'])) $returnBitfield = 0; // A list of "banned" user groups can be specified in config. These groups lose all permissions, similar to having userPrivs = 0. But, in the interest of sanity, we don't check it elsewhere.
            elseif ($room->ownerId === $user->id) $returnBitfield = 65535; // Owners have all permissions.
            elseif ($room->parentalAge > $user->parentalAge
                || fim_inArray($user->parentalFlags, $room->parentalFlags)
                || ($kicks = $this->getKicks(array(
                        'userIds' => array($user->id),
                        'roomIds' => array($room->id)
                    ))->getCount() > 0)) $returnBitfield = 0; // A kicked user (or one blocked by parental controls) has no permissions. This cannot apply to the room owner.
            elseif ($permissionsBitfield === -1) $returnBitfield = $room->defaultPermissions;
            else $returnBitfield = $permissionsBitfield;


            /* Remove priviledges under certain circumstances. */
            // Remove priviledges that a user does not have for any room.
            if (!($user->hasPriv('view'))) $returnBitfield &= ~ROOM_PERMISSION_VIEW; // If banned, a user can't view anything.
            if (!($user->hasPriv('post'))) $returnBitfield &= ~ROOM_PERMISSION_POST; // If silenced, a user can't post anywhere.
            if (!($user->hasPriv('changeTopic'))) $returnBitfield &= ~ROOM_PERMISSION_TOPIC;

            // Deleted and archived rooms act similarly: no one may post in them, while only admins can view deleted rooms.
            if ($room->deleted || $room->archived) { // that is, check if a room is either deleted or archived.
                if ($room->deleted && !$user->hasPriv('modRooms')) $returnBitfield &= ~(ROOM_PERMISSION_VIEW); // Only super moderators may view deleted rooms.

                $returnBitfield &= ~(ROOM_PERMISSION_POST | ROOM_PERMISSION_TOPIC); // And no one can post in them - a rare case where even admins are denied certain abilities.
            }


            /* Update cache and return. */
            $this->updatePermissionsCache($room->id, $user->id, $returnBitfield, ($kicks > 0 ? true : false));

            return $returnBitfield;
        }
    }


    public function updatePermissionsCache($roomId, $userId, $permissions, $isKicked = false) {
        global $config;

        if ($config['roomPermissionsCacheEnabled']) {
            $this->upsert($this->sqlPrefix . 'roomPermissionsCache', array(
                'roomId' => $roomId,
                'userId' => $userId,
            ), array(
                'permissions' => $permissions,
                'expires' => $this->now($config['roomPermissionsCacheExpires']),
                'isKicked' => $this->bool($isKicked),
            ));
        }
    }



    /****** Insert/Update Functions *******/

    public function createSession($user) {
        // TODO: implement using database.php
        global $dbConnect;

        require_once('oauth2-server-php/src/OAuth2/Autoloader.php');
        OAuth2\Autoloader::register();
        $storage = new OAuth2\Storage\Pdo(array('dsn' => 'mysql:dbname=' . $this->connectionInformation['database'] . ';host=' . $this->connectionInformation['host'], 'username' => $this->connectionInformation['user'], 'password' => $this->connectionInformation['password']));// $dsn is the Data Source Name for your database, for exmaple "mysql:dbname=my_oauth2_db;host=localhost"
        $server = new OAuth2\Server($storage); // Pass a storage object or array of storage objects to the OAuth2 server class
        $server->addGrantType(new OAuth2\GrantType\ClientCredentials($storage)); // Add the "Client Credentials" grant type (it is the simplest of the grant types)
        $server->addGrantType(new OAuth2\GrantType\AuthorizationCode($storage)); // Add the "Authorization Code" grant type (this is where the oauth magic happens)
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
        $this->delete($this->sqlPrefix . 'oauth_access_tokens', array(
            'expires' => $this->now(-3600, 'lte')
        ));

        $this->delete($this->sqlPrefix . 'oauth_authorization_codes', array(
            'expires' => $this->now(-3600, 'lte')
        ));

        $this->delete($this->sqlPrefix . 'sessionLockout', array(
            'expires' => $this->now(0, 'lte')
        ));
    }


    public function lockoutIncrement() {
        global $config;

        // Note: As defined, attempts will further increase, and expires will further increase, with each additional query beyond the "lockout". As a result, this function generally shouldn't be called if a user is already lockedout -- otherwise, further attempts just lock them out further, when they could be the user checking to see if they are still locked out. So always call lockoutActive before calling lockoutIncrement.
        $this->upsert($this->sqlPrefix . 'sessionLockout', array(
            'ip' => $_SERVER['REMOTE_ADDR'],
        ), array(
            'attempts' => $this->equation('$attempts + 1'),
            'expires' => $this->now($config['lockoutExpires']) // TOOD: Config
        ));

        return true;
    }

    public function lockoutActive() {
        global $config;

        // Note: Select condition format is experimental and untested, and numRows is not yet implemented. So, uh, do that. Lockout count is also unimplemented.
        if ($this->select(array(
                $this->sqlPrefix . 'sessionLockout' => 'ip, attempts, expires'
            ), array(
                'ip' => $_SERVER['REMOTE_ADDR'],
            ))->getColumnValue('attempts') >= $config['lockoutCount']) return true;

        return false;
    }



    public function updateUserCaches() {
    }


    /**
     * @param        $roomList - Either 'watchRooms' or 'userFavRooms' (representing those tables)
     * @param        $userData
     * @param        $roomIds
     * @param string $method
     */
    public function editRoomList(string $roomListName, fimUser $userData, array $roomIds, string $action = 'create') {
        $rooms = $this->getRooms(array(
            'roomIds' => $roomIds
        ))->getAsRooms();


        $table = $this->sqlPrefix . ($roomListName === 'favRooms' ? 'userFavRooms' : $roomListName);


        if ($action === 'delete') {
            foreach ($rooms AS $roomId => $room) {
                $this->delete($table, array(
                    'userId' => $userData->id,
                    'roomId' => $roomId,
                ));
            }
        }

        if ($action === 'edit') {
            foreach ($rooms AS $roomId => $room) {
                $this->delete($table, array(
                    'userId' => $userData->id,
                ));
            }
        }

        if ($action === 'create' || $action === 'edit') {
            foreach ($rooms AS $roomId => $room) {
                if ($this->hasPermission($userData, $room) & ROOM_PERMISSION_VIEW) {
                    $this->insert($table, array(
                        'userId' => $userData->id,
                        'roomId' => $roomId,
                    ));
                }
            }
        }
    }



    public function editUserList(string $userListName, fimUser $user, array $userIds, string $action = 'create') {
        /* Get the user data corresponding with $userIds
         * This will filter out any non-existing userIds, as well. */
        $userIds = $this->getUsers(array(
            'userIds' => $userIds,
            'columns' => 'userId'
        ))->getColumnValues('userId');


        $table = $this->sqlPrefix . 'user' . $userListName;


        /* If the action is delete, delete users in $userIds */
        if ($action === 'delete') {
            foreach ($userIds AS $userId) {
                $this->delete($table, array(
                    'userId' => $user->id,
                    'subjectId' => $userId,
                ));
            }
        }


        /* If the action is edit (which replaces existing users), delete all existing users. */
        if ($action === 'edit') {
            $this->delete($table, array(
                'userId' => $user->id,
            ));
        }


        /* If the action is either edit or create, add users from $userIds */
        if ($action === 'edit' || $action === 'create') {
            foreach ($userIds AS $userId) {
                // Base data
                $dataArray = array(
                    'userId' => $user->id,
                    'subjectId' => $userId,
                );

                // TODO: If it is the friends list, status should be request
                // if ($userListName === 'friendsList')
                //    $dataArray['status'] = 'request';

                // Perform insertion
                $this->insert($table, $dataArray);

                // TODO: If it is the friends list, create a friendRequest event
                // if ($userListName === 'friendsList')
                //    $this->createUserEvent('friendRequest', $userId, $user->id);
            }
        }
    }



    public function editListCache(string $listName, fimUser $user, array $itemIds, $action = 'create') {
        $userColNames = [
            'favRooms' => 'favRoomIds',
            'watchRooms' => 'watchRoomIds',
            'ignoreList' => 'ignoredUserIds',
            'friendsList' => 'friendedUserIds'
        ];

        /* Process user caches */
        if (isset($userColNames[$listName])) {

            if ($action === 'edit')
                $listEntries = $itemIds;

            else {
                $listEntries = $user->__get($listName);

                if ($action === 'delete')
                    $listEntries = array_diff($listEntries, $itemIds);

                elseif ($action === 'create')
                    foreach ($itemIds AS $item) $listEntries[] = $item;
            }


            $listEntries = array_unique($listEntries);
            sort($listEntries);

            $this->update($this->sqlPrefix . 'users', [
                $userColNames[$listName] => $listEntries
            ], [
                'userId' => $user->id,
            ]);
        }
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

        $this->modLog('setCensorList', "$roomId,$listId,$status");

        $this->upsert($this->sqlPrefix . "censorBlackWhiteLists", array(
            'roomId' => $roomId,
            'listId' => $listId,
        ), array(
            'status' => $status,
        ));

        $this->endTransaction();
    }



    /**
     * Enables/disables listed censor lists.
     *
     * @param int $roomId
     * @param array $lists - An array where each key is a list ID and each value is true/false -- true to enable a censor, false to disable a censor.
     */
    public function setCensorLists($roomId, $lists) {
        $dbLists = $this->getCensorLists(array(
            'activeStatus' => 'active',
            'roomIds' => array($roomId),
        ))->getAsArray(array('listId'));// var_dump($lists); die();

        foreach ($dbLists AS $listId => $list) {
            if ($list['type'] == 'black'
                && $list['status'] == 'block') $checked = true;
            elseif ($list['type'] == 'white'
                && $list['status'] != 'unblock') $checked = true;
            else $checked = false;

            if ($checked == true && !$lists[$listId])
                $this->setCensorList($roomId, $listId, 'unblock');
            elseif ($checked == false && $lists[$listId])
                $this->setCensorList($roomId, $listId, 'block');
        }
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

        /* Delete Permissions Cache */
        $this->deletePermissionCache($roomId, $attribute, $param);

        /* End Transaction */
        $this->endTransaction();
    }

    public function clearPermission($roomId, $attribute, $param) {
        $this->startTransaction();

        $this->modLog('deletePermission', "$roomId,$attribute,$param");

        $this->delete($this->sqlPrefix . 'roomPermissions', array(
            'roomId' => $roomId,
            'attribute' => $attribute,
            'param' => $param,
        ));

        $this->deletePermissionCache($roomId, $attribute, $param);

        $this->endTransaction();
    }


    public function deletePermissionCache($roomId, $attribute, $param) {
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
    }



    public function markMessageRead($messageId, $userId)
    {
        global $config;

        if ($config['enableUnreadMessages']) {
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
    public function storeMessage($messageText, $messageFlag, fimUser $user, fimRoom $room, $ignoreBlock = false, &$censorMatches = array())
    {
        global $generalCache, $config; // TODO


        /**
         * Flood limit check.
         * As this is... pretty important to ensure, we perform this check at the last possible moment, here in storeMessage.
         */
        $time = time();
        $minute = $this->ts($time - ($time % 60));
        $messageFlood = $this->select([
            $this->sqlPrefix . 'messageFlood' => 'userId, roomId, messages, time'
        ], [
            'userId' => $user->id,
            'roomId' => $this->in([$room->id, 0]),
            'time' => $minute,
        ])->getAsArray('roomId');

        if ($messageFlood[$room->id]['messages'] >= $config['floodRoomLimitPerMinute'])
            new fimError('roomFlood', 'Room flood limit breached.');

        if ($messageFlood[0]['messages'] >= $config['floodSiteLimitPerMinute'])
            new fimError('siteFlood', 'Site flood limit breached.');




        $user->resolve(array("messageFormatting", "userNameFormat", "profile", "avatar", "mainGroupId", "name"));


        /* Format Message Text */
        if (!in_array($messageFlag, array('image', 'video', 'url', 'email', 'html', 'audio', 'text'))) {
            $messageText = $generalCache->censorScan($messageText, $room->id, $ignoreBlock, $censorMatches);
        }

        list($messageTextEncrypted, $encryptIV, $encryptSalt) = $this->getEncrypted($messageText);


        $this->startTransaction();


        /* Insert Message Data */
        // Insert into permanent datastore, unless it's an off-the-record room (since that's the only way it's different from a normal private room), in which case we just try to get an autoincremented messageId, storing nothing else.
        if ($room->type === 'otr') {
            $this->insert($this->sqlPrefix . "messages", array(
                'roomId'   => $room->id,
            ));
            $messageId = $this->insertId;
        }
        else {
            $this->insert($this->sqlPrefix . "messages", array(
                'roomId'   => $room->id,
                'userId'   => $user->id,
                'text'     => $this->blob($messageTextEncrypted),
                'textSha1' => sha1($messageText),
                'salt'     => $encryptSalt,
                'iv'       => $this->blob($encryptIV),
                'ip'       => $_SERVER['REMOTE_ADDR'],
                'flag'     => $messageFlag,
                'time'     => $this->now(),
            ));
            $messageId = $this->insertId;
        }


        // Insert into cache/memory datastore.
        if ($room->isPrivateRoom()) {
            $this->insert($this->sqlPrefix . "messagesCachedPrivate", array(
                'messageId'         => $messageId,
                'roomId'            => $room->id,
                'userId'            => $user->id,
                'text'              => $messageText,
                'flag'              => $messageFlag,
                'time'              => $this->now(),
            ));
        }
        else {
            $this->insert($this->sqlPrefix . "messagesCached", array(
                'messageId'         => $messageId,
                'roomId'            => $room->id,
                'userId'            => $user->id,
                'userName'          => $user->name,
                'userGroupId'       => $user->mainGroupId,
                'avatar'            => $user->avatar,
                'profile'           => $user->profile,
                'userNameFormat'    => $user->userNameFormat,
                'messageFormatting' => $user->messageFormatting,
                'text'              => $messageText,
                'flag'              => $messageFlag,
                'time'              => $this->now(),
            ));
        }
        $messageId2 = $this->insertId;



        /* Generate (and Insert) Key Words, Unless an Off-the-Record Room */
        if ($room->type !== 'otr') {
            $keyWords = $this->getKeyWordsFromText($messageText);
            $this->storeKeyWords($keyWords, $messageId, $user->id, $room->id);
        }



        /* Update the Various Caches */
        // Update room caches.
        $this->update($this->sqlPrefix . "rooms", array(
            'lastMessageTime' => $this->now(),
            'lastMessageId'   => $messageId,
            'messageCount'    => $this->equation('$messageCount + 1')
        ), array(
            'roomId' => $room->id,
        ));


        // Update the messageIndex if appropriate
        if (!$room->isPrivateRoom()) {
            $room = $this->getRoom($room->id); // Get the new room data. (TODO: UPDATE ... RETURNING for PostGreSQL)

            if ($room->messageCount % $config['messageIndexCounter'] === 0) { // If the current messages in the room is divisible by the messageIndexCounter, insert into the messageIndex cache. Note that we are hoping this is because of the very last query which incremented this value, but it is impossible to know for certain (if we tried to re-order things to get the room data first, we still run this risk, so that doesn't matter; either way accuracy isn't critical). Postgres would avoid this issue, once implemented.
                $this->insert($this->sqlPrefix . "messageIndex", array(
                    'roomId'    => $room->id,
                    'interval'  => $room->messageCount,
                    'messageId' => $messageId
                ));
            }
        }


        // Update the messageDates if appropriate
        $lastDayCache = (int) $generalCache->get('fim3_lastDayCache');

        $currentTime = time();
        $lastMidnight = $currentTime - ($currentTime % $config['messageTimesCounter']); // Using some cool math (look it up if you're not familiar), we determine the distance from the last even day, then get the time of the last even day itself. This is the midnight reference point.

        if ($lastDayCache < $lastMidnight) { // If the most recent midnight comes after the period at which the time cache was last updated, handle that. Note that, though rare-ish, this query may be executed by a few different messages. It's not a big deal, since the primary key will prevent any duplicate entries, but still.
            $generalCache->set('fim3_lastDayCache', $lastMidnight); // Update the quick cache.

            $this->insert($this->sqlPrefix . "messageDates", array(
                'time'      => $lastMidnight,
                'messageId' => $messageId
            ));
        }



        // Update Flood Counter
        $time = time();
        $minute = $this->ts($time - ($time % 60));
        foreach ([$room->id, 0] AS $roomId) {
            $this->upsert($this->sqlPrefix . "messageFlood", array(
                'userId' => $user->id,
                'roomId' => $roomId,
                'time' => $minute,
                'messages' => 1,
            ), array(
                'ip' => $_SERVER['REMOTE_ADDR'],
                'messages' => $this->equation('$messages + 1'),
            ));
        }


        // Update user caches
        $this->update($this->sqlPrefix . "users", array(
            'messageCount' => $this->equation('$messageCount + 1'),
        ), array(
            'userId' => $user->id,
        ));


        // Insert or update a user's room stats.
        $this->upsert($this->sqlPrefix . "roomStats", array(
            'userId'   => $user->id,
            'roomId'   => $room->id,
        ), array(
            'messages' => $this->equation('$messages + 1')
        ));


        // Increment the messages counter.
        $this->incrementCounter('messages');


        // Delete old messages from the cache, based on the maximum allowed rows.
        if ($messageId2 > $config['messageCacheTableMaxRows']) { echo $messageId2; echo ' '; echo $config['messageCacheTableMaxRows']; die('huh?');
            $this->partitionAt(['roomId' => $room->id])->delete($this->sqlPrefix . "messagesCached" . ($room->isPrivateRoom() ? 'Private' : ''), [
                'id' => $this->int($messageId2 - $config['messageCacheTableMaxRows'], 'lte')
            ]);
        }


        // If the contact is a private communication, create an event and add to the message unread table.
        if ($room->isPrivateRoom()) {
            foreach (($room->getPrivateRoomMemberIds()) AS $sendToUserId) {
                if ($sendToUserId == $user->id)
                    continue;
                else
                    $this->createUnreadMessage($sendToUserId, $user, $room, $messageId);
            }
        }
        else {
            foreach ($room->watchedBy AS $sendToUserId) {
                $this->createUnreadMessage($sendToUserId, $user, $room, $messageId);
            }
        }


        $this->endTransaction();


        // Return the ID of the inserted message.
        return $messageId;
    }


    /* TODO: require roomId */
    public function editMessage(int $roomId, int $messageId, $options) {
        global $user;

        $options = $this->argumentMerge(array(
            'deleted' => null,
            'text'    => null,
            'flag'    => null,
        ), $options);

        $oldMessage = $this->getMessage($messageId)->getAsArray(false);
        $room = new fimRoom((int) $oldMessage['roomId']);


        $this->startTransaction();

        $this->modLog('editMessage', $messageId);

        if ($options['text']) {
            list($messageTextEncrypted, $encryptIV, $encryptSalt) = $this->getEncrypted($options['text']);

            $this->insert($this->sqlPrefix . "messageEditHistory", array(
                'messageId' => $messageId,
                'user' => $user->id,
                'oldText' => $oldMessage['text'],
                'newText' => $messageTextEncrypted,
                'iv1' => $oldMessage['iv'],
                'iv2' => $encryptIV,
                'salt1' => $oldMessage['salt'],
                'salt2' => $encryptSalt,
                'ip' => $_SERVER['REMOTE_ADDR'],
            ));

            $options = $this->argumentMerge($options, array(
                'text' => $messageTextEncrypted,
                'salt' => $encryptSalt,
                'iv' => $encryptIV,
            ));

            $this->dropKeyWords($messageId);
            $keyWords = $this->getKeyWordsFromText($options['text']);
            $this->storeKeyWords($keyWords, $messageId, $user->id, $room->id);
        }

        $this->update($this->sqlPrefix . "messages", $options, array(
            "messageId" => (int) $messageId
        ));

        $this->delete($this->sqlPrefix . "messagesCached", array(
            "messageId" => $messageId
        ));

        $this->delete($this->sqlPrefix . "messagesCachedPrivate", array(
            "messageId" => $messageId
        ));

        $this->createEvent('editedMessage', $user->id, false, $messageId, false, false, false); // name, user, room, message, p1, p2, p3

        $this->endTransaction();
    }




    public function createUnreadMessage($sendToUserId, fimUser $user, fimRoom $room, $messageId) {
        global $config;

        $this->createUserEvent('missedMessage', $sendToUserId, $room->id, $messageId);

        if ($config['enableUnreadMessages']) {
            $this->upsert($this->sqlPrefix . "unreadMessages", array(
                'userId'            => $sendToUserId,
                'roomId'            => $room->id
            ), array(
                'senderId'          => $user->id,
                'senderName'        => $user->name,
                'senderNameFormat'  => $user->userNameFormat,
                'roomName'          => $room->name,
                'messageId'         => $messageId,
                'otherMessages'     => 0,
            ), array(
                'time'              => $this->now(),
                'otherMessages'     => $this->equation('$otherMessages + 1'),
            ));
        }
    }


    public function storeFile(fimFile $file, fimUser $user, fimRoom $room) {
        global $encryptUploads, $config;

        if ($encryptUploads) {
            list($contentsEncrypted, $iv, $saltNum) = fim_encrypt($file->contents);
        }
        else {
            $contentsEncrypted = $file->contents;
            $iv = '';
            $saltNum = -1;
        }

        $this->startTransaction();

        $this->insert($this->sqlPrefix . "files", array(
            'userId' => $user->id,
            'roomIdLink' => $room->id,
            'fileName' => $file->name,
            'fileType' => $file->mime,
            'fileParentalAge' => $file->parentalAge,
            'fileParentalFlags' => implode(',', $file->parentalFlags),
            'creationTime' => time(),
            'fileSize' => $file->size,
        ));
        $fileId = $this->insertId;

        $this->insert($this->sqlPrefix . "fileVersions", array(
            'fileId' => $fileId,
            'sha256hash' => $file->sha256hash,
            'salt' => $this->int($saltNum),
            'iv' => $this->blob($iv),
            'size' => $file->size,
            'contents' => $this->blob($contentsEncrypted),
            'time' => time(),
        ));
        $versionId = $this->insertId;

        $this->update($this->sqlPrefix . "users", array(
            'fileCount' => $this->type('equation', '$fileCount + 1'),
            'fileSize' => $this->type('equation', '$fileSize + ' . (int) $file->size),
        ), array(
            'userId' => $user->id,
        ));

        $this->incrementCounter('uploads');
        $this->incrementCounter('uploadSize', $file->size);

        if ($room->id)
            $this->storeMessage($file->webLocation, $file->container, $user, $room);

        if (in_array($file->extension, $config['imageTypes'])) {
            list($width, $height) = getimagesizefromstring($file->contents);

            if (!$imageOriginal = imagecreatefromstring($file->contents)) {
                throw new fimError('resizeFailed', 'The image could not be thumbnailed. The file was still uploaded.');
            }
            else {
                foreach ($config['imageThumbnails'] AS $resizeFactor) {
                    if ($resizeFactor < 0 || $resizeFactor > 1) {
                        $this->rollbackTransaction();
                        throw new fimError('badServerConfigImageThumbnails', 'The server is configured with an incorrect thumbnail factor, ' . $resizeFactor . '. Image file uploads will be disabled until this issue is rectified.');
                    }

                    $newWidth = $resizeFactor * $width;
                    $newHeight = $resizeFactor * $height;

                    $imageThumb = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($imageThumb, $imageOriginal, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                    ob_start();
                    imagejpeg($imageThumb);
                    $thumbnail = ob_get_clean();

                    if ($encryptUploads) {
                        list($thumbnailEncrypted, $iv, $keyNum) = fim_encrypt($thumbnail);
                    }
                    else {
                        $thumbnailEncrypted = $file->contents;
                        $iv = '';
                        $keyNum = -1;
                    }

                    $this->insert($this->sqlPrefix . "fileVersionThumbnails", array(
                        'versionId' => $fileId,
                        'scaleFactor' => $this->float($resizeFactor),
                        'width' => $newWidth,
                        'height' => $newHeight,
                        'salt' => $keyNum,
                        'iv' => $iv,
                        'contents' => $thumbnailEncrypted
                    ));
                }
            }
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
            'counterValue' => $this->equation('$counterValue + ' . (int) $incrementValue)
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
        global $config;

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


    public function createUserEvent($eventName, $userId, $param1 = '', $param2 = '')
    {
        global $config;

        if ($config['enableEvents']) {
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
        )->getAsArray('phraseName'); // TODO: what the hell were you thinking, exactly?


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


    public function dropKeyWords($messageId) {
        $this->delete($this->sqlPrefix . "searchMessages", array(
            'messageId' => (int) $messageId,
        ));
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

        if (!isset($user->id)) throw new Exception('database->modLog requires user->id');

        if ($this->insert($this->sqlPrefix . "modlog", array(
            'userId' => (int) $user->id,
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
        global $user, $globalTime, $config; // TODO

        if ($config['accessLogEnabled']) {
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
        global $config; // TODO

        $string = $this->makeSearchable($text);

        $stringPieces = array_unique(explode(' ', $string));
        $stringPiecesAdd = array();

        foreach ($stringPieces AS $piece) {
            if (strlen($piece) >= $config['searchWordMinimum'] &&
                strlen($piece) <= $config['searchWordMaximum'] &&
                !in_array($piece, $config['searchWordOmissions'])) $stringPiecesAdd[] = $piece;
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
     * Retrieve an encrypted version of text.
     *
     * @param $messageText
     *
     * @return [$messageTextEncrypted, $iv, $saltNum]
     *
     */
    public function getEncrypted($messageText) {
        return fim_encrypt($messageText);
    }




    /****** TRIGGERS ******/
    public function triggerUserFavRoomIds($set, $dataChanges) {
        $this->triggerUserListCache($set, "favRooms", $dataChanges);
    }
    public function triggerUserWatchedRoomIds($set, $dataChanges) {
        $this->triggerUserListCache($set, "watchRooms", $dataChanges);
    }
    public function triggerUserIgnoredUserIds($set, $dataChanges) {
        $this->triggerUserListCache($set, "ignoreList", $dataChanges);
    }
    public function triggerUserFriendedUserIds($set, $dataChanges) {
        $this->triggerUserListCache($set, "friendsList", $dataChanges);
    }

    public function triggerUserListCache($userId, $cacheColumn, $dataChanges) {
        $userColNames = [
            'favRooms' => 'favRoomIds',
            'watchRooms' => 'watchRoomIds',
            'ignoreList' => 'ignoredUserIds',
            'friendsList' => 'friendedUserIds'
        ];

        $user = new fimUser($userId);
        $listEntries = $user->__get($cacheColumn);

        foreach ($dataChanges AS $operation => $values) {
            switch ($operation) {
                case 'delete':
                    $listEntries = is_string($values) && $values === '*' ? [] : array_diff($listEntries, $values);
                    break;
                case 'insert':
                    $listEntries = array_merge($listEntries, $values);
                    break;
            }
        }

        $user->setDatabase([
            $userColNames[$cacheColumn] => $listEntries
        ]);
    }




    /**
     * OVERRIDE
     * Overrides the normal function to use fimDatabaseResult instead.
     */
    protected function databaseResultPipe($queryData, $reverseAlias, $query, $database) {
        return new fimDatabaseResult($queryData, $reverseAlias, $query, $database);
    }
}

class fimDatabaseResult extends databaseResult {

    /**
     * @return fimRoom[]
     *
     * @internal This function may use too much memory. I'm not... exactly sure how to fix this.
     */
    function getAsRooms() : array {
        $rooms = $this->getAsArray('roomId');
        $return = array();

        foreach ($rooms AS $roomId => $room) {
            $return[$roomId] = new fimRoom($room);
        }

        return $return;
    }


    /**
     * @return fimUser[]
     *
     * @internal This function may use too much memory. I'm not... exactly sure how to fix this.
     */
    function getAsUsers() : array {
        $users = $this->getAsArray('userId');
        $return = array();

        foreach ($users AS $userId => $user) {
            $return[$userId] = new fimUser($user);
        }

        return $return;
    }


    /**
     * @return fimRoom
     */
    function getAsRoom() : fimRoom {
        return new fimRoom($this->getAsArray(false));
    }


    /**
     * @return fimUser
     */
    function getAsUser() : fimUser {
        return new fimUser($this->getAsArray(false));
    }

}

?>