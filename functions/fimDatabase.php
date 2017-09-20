<?php
/* FreezeMessenger Copyright © 2017 Joseph Todd Parsons

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


/**
 * FreezeMessenger-specific database functionality. Attempts to define a function for effectively every needed database call; most database calls, that is, will be through these methods instead of custom query logic.
 * Current work-in-progress stuff:
 *  - Limit handling is needed for most functions.
 *
 * @author Joseph T. Parsons <josephtparsons@gmail.com>
 */
class fimDatabase extends DatabaseSQL
{
    /**
     * @var string The columns containing all user data.
     */
    public $userColumns = 'id, name, nameFormat, profile, avatar, mainGroupId, socialGroupIds, messageFormatting, options, defaultRoomId, parentalAge, parentalFlags, privs, lastSync';

    /**
     * @var string The columns containing all user login data.
     */
    public $userPasswordColumns = 'passwordHash, passwordFormat, passwordResetNow, passwordLastReset';

    /**
     * @var string The columns containing all user data that is recorded in the user history.
     */
    public $userHistoryColumns = 'id, name, nameFormat, profile, avatar, mainGroupId, socialGroupIds, messageFormatting, options, parentalAge, parentalFlags, privs';

    /**
     * @var string The columns containing all room data that is recorded in the room history.
     */
    public $roomHistoryColumns = 'id, name, topic, options, ownerId, defaultPermissions, parentalAge, parentalFlags';

    /**
     * @var string An error format function to be used when errors are encountered. Overrides {@link database:errorFormatFunction}
     */
    public $errorFormatFunction = 'fimError';

    /**
     * @var fimConfig A pointer to the site configuration class.
     */
    protected $config;

    /**
     * @var fimUser A pointer to the logged-in user.
     */
    protected $user;


    const decodeError = "CORRUPTED";
    const decodeExpired = "EXPIRED";



    /*********************************************************
     ************************ START **************************
     ***************** List Packing Operations ***************
     *********************************************************/

    /**
     * Converts an array list of integers to a packed binary equivilent.
     * As currently implemented, this should achieve the lowest possible size without performing compression. Numbers in the CSV list are converted to base-15, which are then packed to a hexadecimal string, with "f" being the delimiter.
     * Thus, "1,2,3" (6  bytes) will convert to 0x1a2a3, which is 20 bits/2.5 bytes.
     * For the purposes of ensuring a string is fully read back, however, it will start and end with "ff." (We could also specify the size of the string at the beginning, but the "ff" is marginally faster, and easier to encode.)
     *
     * @param array $list An array of integers to pack.
     * @return string A binary string representation.
     */
    public static function packList(array $list) {
        if (count($list) === 0)
            return pack("H*", 'ffff');

        else {
            $packString = '';

            foreach ($list AS $int)
                $packString .= 'f' . base_convert($int, 10, 15);

            return pack("H*", 'f' . $packString . 'ff');
        }
    }


    /**
     * Reverses the {@link fimDatabase::packList} procedure, converting a binary string into an array of integers.
     *
     * @param string $blob The binary blob to convert.
     * @return array|mixed The array of integers that were packed, or fimDatabase::decodeError if unpacking failed.
     */
    public static function unpackList($blob) {
        if (strlen($blob) === 0)
            return [];

        else {
            $decoded = rtrim(unpack("H*", $blob)[1], '0');

            if (substr($decoded, -2) !== 'ff' || substr($decoded, 0, 2) !== 'ff')
                return fimDatabase::decodeError;

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
     * This operates like {@link fimDatabase::packCSV}, except it also stores a cache expiration time as the first entry.
     *
     * @param array $list The list of integers to pack.
     * @param int $cacheLength How long the binary string should be considered valid.
     */
    public static function packListCache(array $list, int $cacheLength = 5 * 60) {
        array_unshift($list, time() + $cacheLength);

        return fimDatabase::packList($list);
    }

    /**
     * The reverse of {@link fimDatabase:packListCache}, this unpacks a binary string, returning its contents or a constant if expired/corrupted.
     *
     * @param string $blob The binary string to unpack.
     * @return array|string The unpacked list of integer values, or {@link fimDatabase::decodeError} if corrupted, or {@link fimDatabase::decodeExpired} if expired.
     */
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

    /*********************************************************
     ************************* END ***************************
     ***************** List Packing Operations ***************
     *********************************************************/






    /*********************************************************
     ************************ START **************************
     ******************** String Operations ******************
     *********************************************************/

    /**
     * Encodes a text string to be easily searched, such as through romanisation and transliteration, removing punctuation, and switch to lowercase.
     * This works as follows:
     *  - Always apply configuration-specified romanisation
     *  - If PHP Transliterator exists, apply config-specified transliteration expression.
     *  - If IconV is available, and PHP Transliterator is not, apply IconV's UTF-8 to Ascii transliteration.
     *  - Remove punctuation, as specified in configuration.
     *  - Remove all characters not specified by the searchWhiteList configuration regex range.
     *  - Get rid of extra spaces, and convert all space characters to " "
     *
     * @param string $string The string to make searchable.
     *
     * @return string The searchable string.
     */
    public function makeSearchable($string) {
        // Romanise first, to allow us to apply custom replacements before letting the built-in functions do their job
        $string = str_replace(array_keys($this->config['romanisation']), array_values($this->config['romanisation']), $string);

        // Apply the built-in functions, if available
        if (function_exists('transliterator_transliterate'))
            $string = transliterator_transliterate($this->config['searchTransliteration'], $string);
        elseif (function_exists('iconv'))
            $string = strtolower(iconv('utf-8', 'us-ascii//TRANSLIT', $string));

        // Replace punctuation with space (e.g. "a.b" should be treated as "a b" not "ab").
        $string = str_replace($this->config['searchWordPunctuation'], ' ', $string);

        // If searchWhiteList is set, then we remove any characters not in the whitelist. By default, it is simply a-zA-Z
        if ($this->config['searchWhiteList'])
            $string = preg_replace('/[^' . $this->config['searchWhiteList'] . ']/', '', $string);

        // Get rid of extra spaces. (Also replaces all space characters with " ")
        $string = preg_replace('/\s+/', ' ', $string);

        return $string;
    }

    /*********************************************************
     ************************* END ***************************
     ******************** String Operations ******************
     *********************************************************/






    /*********************************************************
     ************************ START **************************
     ******************** Utility Functions ******************
     *********************************************************/

    /****** Utility Functions ******/
    /**
     * Combines a set of default arguments with a set of passed arguments, and throws an error if a passed argument is not "present" in the default arguments.
     *
     * @param array $defaults The argument defaults.
     * @param array $args     The new arguments; all must have a corresponding entry in $defaults.
     *
     * @return array The two sets of arguments combined.
     *
     * @throws Exception If an argument in $args isn't present in $defaults.
     */
    private function argumentMerge($defaults, $args) {
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

    /*********************************************************
     ************************* END ***************************
     ******************** Utility Functions ******************
     *********************************************************/






    /*********************************************************
     ************************ START **************************
     *********************** Setters *************************
     *********************************************************/

        /**
     * Associates $user with the database instance, such as for auditing.
     *
     * @param fimUser $user
     */
    public function registerUser(fimUser $user) {
        $this->user = $user;
    }


    /**
     * Associates $config (used to specify many defaults, limits, and so-on) with the database instance.
     *
     * @param fimConfig $config
     */
    public function registerConfig(fimConfig $config) {
        $this->config = $config;
    }

    /*********************************************************
     ************************* END ***************************
     *********************** Setters *************************
     *********************************************************/








    /*********************************************************
     ************************ START **************************
     *********************** Getters *************************
     *********************************************************/

    /**
     * Return a list of users who are actively using the instant messenger.
     * Scans table `ping`, and links in tables `rooms` and `users`, mainly for use in hasPermission(): TODO
     * Returns columns ping[status, typing, ptime, proomId, puserId], rooms[roomId, roomName, roomTopic, owner, defaultPermissions, roomParentalAge, roomParentalFlags, options], and users[userId, userName, userNameFormat, userGroupId, socialGroupIds, status]
     *
     * @param array $options {
     *      An array of options to filter by.
     *
     *      @param array ['userIds']         Filter by a list of user IDs.
     *      @param array ['roomIds']         Filter by the list of room IDs a user is active in. (Matches any -- if a user is active in any of the specified rooms, they will be in the returned resultset.)
     *      @param array ['statuses']        Filter by a list of user statuses, any of (from ping.status) "away", "busy", "available", "invisible", "offline"
     *      @param bool  ['typing']          Filter by whether or not the user is typing.
     *      @param int   ['onlineThreshold'] The period of time that may elapse before a user should no longer be conisdered "active." Defaults to a site-configurable threshold, typically 15 minutes.
     * }
     *
     * @param array $sort  Sort columns (see standard definition).
     * @param int   $limit The maximum number of returned rows (with 0 being unlimited).
     * @param int   $page  The page of the resultset to return.
     *
     * @return databaseResult
     */
    public function getActiveUsers($options, $sort = array('userName' => 'asc'), $limit = false, $page = false)
    {
        $options = $this->argumentMerge(array(
            'onlineThreshold' => $this->config['defaultOnlineThreshold'],
            'roomIds'         => array(),
            'userIds'         => array(),
            'typing'          => null,
            'statuses'        => array()
        ), $options);

        $columns = array(
            $this->sqlPrefix . "ping"  => 'status pstatus, typing, time ptime, roomId proomId, userId puserId',
            $this->sqlPrefix . "rooms" => 'id roomId, idEncoded roomIdEncoded, name roomName, ownerId, defaultPermissions, options',
            $this->sqlPrefix . "users" => 'id userId, name userName, nameFormat userNameFormat, status, avatar',
        );


        if (count($options['roomIds']) > 0)  $conditions['both']['proomId 1'] = $this->in($options['roomIds']);
        if (count($options['userIds']) > 0)  $conditions['both']['puserId 1'] = $this->in($options['userIds']);
        if (count($options['statuses']) > 0) $conditions['both']['status']  = $this->in($options['statuses']);

        if (isset($options['typing']))       $conditions['both']['typing']  = $this->bool($options['typing']);


        $conditions['both']['ptime'] = $this->int(time() - $options['onlineThreshold'], 'gt');
        $conditions['both']['proomId 2'] = $this->col('roomIdEncoded');
        $conditions['both']['puserId 2'] = $this->col('userId');


        return $this->select($columns, $conditions, $sort, $limit, $page);
    }








    /*********************************************************
     ************************ START **************************
     ******************** Censor Stuff ***********************
     *********************************************************/

    /**
     * Gets the censor list rows, filtered by $options, and optionally with the "on"/"off" status associated with a given roomId.
     * Scans table `censorLists`. Also joins `censorBlackWhiteLists` if $options['includeStatus'] is set.
     *
     * Note: Censor active status is calculated outside of the database, and thus can not be selected for. Use getCensorListsActive for this purpose, which returns an array of active censor lists instead of a databaseResult.
     *
     * @param array $options {
     *     Options to filter by.
     *
     *     @var array  ['listIds']        An array of listIds to match.
     *     @var string ['listNameSearch'] A string for listName to match search-wise.
     *     @var int    ['includeStatus']  A roomId for whom the list's status should be included.
     *
     *     The following are not fully supported:
     *     @var string ['activeStatus']   Restrict to active or inactive lists. An inactive list is "disabled" -- that is, it does not apply to rooms, but rooms remember whether they enabled the list if it is turned back on. (Values: "active"|"inactive")
     *     @var string ['forcedStatus']   Restrict to forced or unforced lists. A forced list cannot be disabled. (Values: "forced"|"unforced")
     *     @var string ['hiddenStatus']   Restrict to hidden or unhidden lists. A hidden list is not made visible to room moderators when editing rooms. (Values: "hidden"|"unhidden")
     *     @var string ['privateStatus']  Restrict to private or normal lists. A private list only applies to private rooms. (Values: "private"|"normal")
     * }
     * @param array $sort  Sort columns (see standard definition).
     * @param int   $limit The maximum number of results.
     * @param int   $page  The page for the selected resultset.
     *
     * @return databaseResult
     */
    public function getCensorLists($options = array(), $sort = array('listId' => 'asc'), $limit = false, $page = false)
    {
        $options = $this->argumentMerge(array(
            'listIds'        => array(),
            'listNameSearch' => '',
            'includeStatus'  => false,
            'activeStatus'   => '',
            'forcedStatus'   => '',
            'hiddenStatus'   => '',
            'privateStatus'  => '',
        ), $options);


        $columns = array(
            $this->sqlPrefix . "censorLists" => 'listId, listName, listType, options',
        );

        if ($options['includeStatus']) {
            $columns['join ' . $this->sqlPrefix . "censorBlackWhiteLists"] = [
                'columns' => 'listId bwlistId, roomId, status',
                'conditions' => [
                    'roomId' => $options['includeStatus'],
                    'bwlistId' => $this->col('listId')
                ]
            ];
        }


        /* Modify Query Data for Directives */
        $conditions = [
            'both' => [],
        ];

        if (count($options['listIds']) > 0) $conditions['both']['listId'] = $this->in((array) $options['listIds']);
        if ($options['listNameSearch']) $conditions['both']['listName'] = $this->type('string', $options['listNameSearch'], 'search');

        if ($options['activeStatus'] === 'active') $conditions['both']['options'] = $this->int(1, 'bAnd'); // TODO: Test!
        elseif ($options['activeStatus'] === 'inactive') $conditions['both']['!options'] = $this->int(1, 'bAnd'); // TODO: Test!

        if ($options['forcedStatus'] === 'forced') $conditions['both']['!options'] = $this->int(2, 'bAnd'); // TODO: Test!
        elseif ($options['forcedStatus'] === 'unforced') $conditions['both']['options'] = $this->int(2, 'bAnd'); // TODO: Test!

        if ($options['hiddenStatus'] === 'hidden') $conditions['both']['options'] = $this->int(4, 'bAnd'); // TODO: Test!
        elseif ($options['hiddenStatus'] === 'unhidden') $conditions['both']['!options'] = $this->int(4, 'bAnd'); // TODO: Test!

        return $this->select($columns, $conditions, $sort, $limit, $page);
    }

    /**
     * Retrieves a single censor list row corresponding with $censorListId.
     *
     * @param int $censorListId The listId's row retrieve.
     *
     * @return array The list properties.
     */
    public function getCensorList($censorListId)
    {
        return $this->getCensorLists(array(
            'listIds' => array($censorListId)
        ))->getAsArray(false);
    }


    /**
     * Retrieves all censor lists active in $roomId.
     *
     * @param int $roomId The roomId whose active lists are being retrieved.
     *
     * @return array[listId] A list of censor lists, keyed by the list ID.
     */
    public function getCensorListsActive($roomId) {
        $censorListsReturn = array();

        $censorLists = $this->getCensorLists(array(
            'includeStatus' => $roomId,
        ))->getAsArray(array('listId'));

        foreach ($censorLists AS $listId => $list) { // Run through each censor list retrieved.
            if ($list['status'] === 'unblock' || ($list['listType'] === 'black' && $list['status'] !== 'block')) continue;

            $censorListsReturn[$list['listId']] = $list;
        }

        return $censorListsReturn;
    }


    /**
     * Gets the censor word rows, filtered by $options.
     * Scans table `censorWords`.
     *
     * @param array $options {
     *      An array of options to filter the resultset by.
     *
     *      @var array  ['listIds']     An array of list IDs to filter by. Only words in these lists will be returned.
     *      @var array  ['wordIds']     An array of word IDs to filter by. Only words with these IDs will be returned.
     *      @var string ['wordSearch']  A search phrase for words; only words containing this phrase will be returned.
     *      @var array  ['severities']  A list of severities to include; only words with these severities will be returned.
     *      @var string ['paramSearch'] A search phrase for the word parameter; only parameters containing this phrase will be returned.
     * }
     * @param array $sort  Sort columns (see standard definition).
     * @param int   $limit The maximum number of returned rows (with 0 being unlimited).
     * @param int   $page  The page of the resultset to return.
     *
     * @return databaseResult The resultset corresponding with the matched censorWords.
     */
    public function getCensorWords($options = array(), $sort = array('listId' => 'asc', 'word' => 'asc'), $limit = false, $page = false)
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

        return $this->select($columns, $conditions, $sort, $limit, $page);
    }

    /**
     * Retrieves a single censorWord row corresponding with $censorWordId.
     *
     * @param int $censorWordId The word to return.
     *
     * @return array The word properties.
     */
    public function getCensorWord($censorWordId)
    {
        return $this->getCensorWords(array(
            'wordIds' => array($censorWordId)
        ))->getAsArray(false);
    }


    /**
     * Retrieves all censor words active in $roomId. Use getCensorListsActive() to retrieve lists instead.
     *
     * @param int   $roomId     The roomId whose active words are being retrieved.
     * @param array $severities If specified, only fetches results with a matching severity (valid values, as defined for censorWord.severity: "replace", "warn", "confirm", "block")
     *
     * @return databaseResult The resultset corresponding with all censor words active in $roomId.
     */
    public function getCensorWordsActive($roomId, $severities = []) {
        return $this->getCensorWords(array(
            'listIds' => array_keys($this->getCensorListsActive($roomId)),
            'severities' => $severities
        ));
    }

    /*********************************************************
     ************************* END ***************************
     ******************** Censor Stuff ***********************
     *********************************************************/



    /**
     * Retrieves all configuration directives matching $options.
     * Use the global $config (or the fimConfig class) for viewing the values of configuration directives during normal operation.
     *
     * @param array $options {
     *      An array of options to filter the resultset by.
     *
     *      @var array ['directives'] A list of configuration directives to select.
     * }
     * @param array $sort Sort columns (see standard definition).
     *
     * @return databaseResult The resultset corresponding with the matched configuration directives.
     */
    public function getConfigurations($options = array(), $sort = array('directive' => 'asc'))
    {
        $options = $this->argumentMerge(array(
            'directives' => array(),
        ), $options);

        $columns = array(
            $this->sqlPrefix . "configuration" => 'directive, type, value',
        );

        $conditions = [
            'both' => []
        ];

        if (count($options['directives']) > 0) {
            $conditions['both']['directive'] = $this->in($options['directives']);
        }

        return $this->select($columns, $conditions, $sort);
    }



    /**
     * Retrieves a single configuration directive.
     * Use the global $config (or the fimConfig class) for viewing the values of configuration directives during normal operation.
     *
     * @param string $directive The directive to return.
     * @return array The directive's properties.
     */
    public function getConfiguration($directive)
    {
        return $this->getConfigurations(array(
            'directives' => array($directive)
        ))->getAsArray(false);
    }



    /**
     * Retrieves a single global counter value.
     *
     * @param string $counterName THe name of the counter to retreive.
     * @return int The counter's current value.
     */
    public function getCounterValue($counterName)
    {
        $queryParts['columns']    = [$this->sqlPrefix . "counters" => 'counterName, counterValue'];
        $queryParts['conditions'] = ['counterName' => $this->str($counterName), ];

        $counterData = $this->select($queryParts['columns'], $queryParts['conditions'], false, 1)->getAsArray(false);

        return $counterData['counterValue'];
    }



    /**
     * Retrieves files matching the given conditions.
     *
     * Scans tables `files` and `fileVersions`. These two tables cannot be queried individually using fimDatabase.
     *
     * Returns columns files.fileId, files.fileName, files.fileType, files.creationTime, files.userId, files.parentalAge, files.parentalFlags, files.roomIdLink, files.fileId, fileVersions.vfileId (= files.fileId), fileVersions.md5hash, fileVersions.sha256hash, fileVersions.size.
     *
     * Optimisation notes: If an index is kept on the number of files posted in a given room, or to a given user, then this index can be used to see if it can be used to quickly narrow down results. Of-course, if such an index results in hundreds of results, no efficiency gain is likely to be made from doing such an elimination first. Similar optimisations might be doable wwith age, creationTime, fileTypes, etc., but these should wait for now.
     *
     * @param array $options {
     *      An array of options to filter by.
     *
     *      @param array ['userIds']         An array of userIds corresponding with file ownership to filter by.
     *      @param array ['roomIds']         An array of roomIds corresponding with room origin to filter by.
     *      @param array ['fileIds']         An array of fileIds to filter by.
     *      @param array ['versionIds']      An array of file version IDs to filter by.
     *      @param array ['sha256hashes']    An array of SHA256 hashes to filter by. (As files are identified by these, this will generally be the fastest way to select individual files.)
     *      @param array ['fileTypes']       An array of MIME file types (as detected at upload time) to filter by.
     *      @param array ['creationTimeMax'] An upper-range for original upload time to filter by.
     *      @param array ['creationTimeMin'] A lower-range for original upload time to filter by.
     *      @param array ['fileNameSearch']  A string that filenames should contain.
     *      @param array ['parentalAgeMax']  An upper-rangefor the file's marked "parental age" (or maturity rating) to filter by.
     *      @param array ['parentalAgeMin']  A lower-range for the file's marked "parental age" (or maturity rating) to filter by.
     *      @param bool  ['includeContent']  If true, a file's entire contents (and encryption keys/IVs) will be returned. This is very slow, and should be avoided unless the file is being shown. (TODO) If file content is stored to the filesystem, it will be retrieved through the filesystem instead.
     *      @param bool ['includeThumbnails'] If true, a record will be returned for each available file thumbnail. Thumbnail contents can only be retrieved separately.
     * }
     *
     * @param array $sort  Sort columns (see standard definition).
     * @param int   $limit The maximum number of returned rows (with 0 being unlimited).
     * @param int   $page  The page of the resultset to return.
     *
     * @return databaseResult
     *
     * @TODO: Test filters for other file properties.
     */
    public function getFiles($options = array(), $sort = array('fileId' => 'asc'), $limit = false, $page = false)
    {
        $options = $this->argumentMerge(array(
            'userIds'         => array(),
            'roomIds'         => array(),
            'fileIds'         => array(),
            'versionIds'      => array(),
            'sha256hashes'    => array(),
            'fileTypes'       => array(),
            'creationTimeMax' => 0,
            'creationTimeMin' => 0,
            'fileNameSearch'  => '',
            'parentalAgeMin'  => 0,
            'parentalAgeMax'  => 0,
            'includeContent'  => false,
            'includeThumbnails'=> false,
            'sizeMin' => 0,
            'sizeMax' => 0,
        ), $options);


        $columns = array(
            $this->sqlPrefix . "files"        => 'fileId, fileName, fileType, creationTime, userId, fileParentalAge parentalAge, fileParentalFlags parentalFlags, roomIdLink, source',
            $this->sqlPrefix . "fileVersions" => 'fileId versionId, sha256hash, size',
        );

        if ($options['includeContent']) $columns[$this->sqlPrefix . 'fileVersions'] .= ', salt, iv, contents';


        // This is a method of optimisation I'm trying. Basically, if a very small sample is requested, then we can optimise by doing those first. Otherwise, the other filters are usually better performed first.
        foreach (array('fileIds' => 'fileId', 'versionIds' => 'versionId', 'sha256hashes' => 'sha256hash') AS $group => $key) {
            if (count($options[$group]) > 0 && count($options[$group]) <= 10) {
                $conditions['both'][$key] = $this->in($options[$group]);
            }
        }


        // Narrow down files _before_ matching to fileVersions. Try to perform the quickest searchest first (those which act on integer indexes).
        if (!isset($conditions['both']['fileIds']) && count($options['fileIds']) > 0) $conditions['both']['fileId'] = $this->in($options['fileIds']);
        if (count($options['userIds']) > 0) $conditions['both']['userId'] = $this->in($options['userIds']);
        if (count($options['roomIds']) > 0) $conditions['both']['roomLinkId'] = $this->in($options['roomIds']);

        if ($options['parentalAgeMin'] > 0) $conditions['both']['parentalAge'] = $this->int($options['parentalAgeMin'], 'gte');
        if ($options['parentalAgeMax'] > 0) $conditions['both']['parentalAge'] = $this->int($options['parentalAgeMax'], 'lte');

        if ($options['creationTimeMin'] > 0) $conditions['both']['creationTime'] = $this->int($options['creationTime'], 'gte');
        if ($options['creationTimeMax'] > 0) $conditions['both']['creationTime'] = $this->int($options['creationTime'], 'lte');
        if (count($options['fileTypes']) > 0) $conditions['both']['fileType'] = $this->in($options['fileTypes']);
        if ($options['fileNameSearch']) $conditions['both']['fileName'] = $this->type('string', $options['fileNameSearch'], 'search');


        // Match files to fileVersions.
        $conditions['both']['fileId'] = $this->col('versionId');


        // Narrow down fileVersions _after_ it has been restricted to matched files.
        if (!isset($conditions['both']['versionIds']) && count($options['versionIds']) > 0) $conditions['both']['versionId'] = $this->in($options['versionIds']);
        if (!isset($conditions['both']['sha256hashes']) && count($options['sha256hashes']) > 0) $conditions['both']['sha256hash'] = $this->in($options['sha256hashes']);

        if ($options['sizeMin'] > 0) $conditions['both']['size'] = $this->int($options['size'], 'gte');
        if ($options['sizeMax'] > 0) $conditions['both']['size'] = $this->int($options['size'], 'lte');


        // Get Thumbnails, if Requested
        if ($options['includeThumbnails']) {
            $columns[$this->sqlPrefix . 'fileVersions'] .= ', versionId';
            $columns[$this->sqlPrefix . 'fileVersionThumbnails'] = 'versionId tversionId, scaleFactor, width, height';

            $conditions['both']['versionId'] = $this->col('tversionId');
        }


        return $this->select($columns, $conditions, $sort, $limit, $page);
    }






    /*********************************************************
     ************************ START **************************
     ********************* Kick Stuff ************************
     *********************************************************/

    /**
     * Retrieve current kicks from the database.
     *
     * Scans table `kicks.` Optionally scans `users` and `rooms.`
     * Returns columns kicks.kickerId, kicks.userId, kicks.roomId, kicks.length, kicks.time.
     *
     * @param array $options {
     *      An array of options to filter by.
     *
     *      @param array ['userIds']           An array of userIds corresponding with the kick recipient to filter by.
     *      @param array ['roomIds']           An array of roomIds corresponding with the kicked-from room to filter by.
     *      @param array ['kickerIds']         An array of userIds corresponding with the kicking user (the moderator, that is) to filter by.
     *      @param array ['lengthMax']         An upper-range for the duration of the kick to filter by.
     *      @param array ['lengthMin']         A lower-range for duration of the kick to filter by.
     *      @param array ['timeMax']           An upper-range for the time of the start of the kick to filter by.
     *      @param array ['timeMin']           A lower-range for the time of the end of the kick to filter by.
     *      @param bool  ['includeUserData']   Whether to include the userdata of the kickee: columns users.id AS kuserId, users.name AS userName, users.nameFormat AS userNameFormat
     *      @param bool  ['includeKickerData'] Whether to include the userdata of the kicker: columns users.id AS kkickerId, users.name AS kickerName, users.nameFormat AS kickerNameFormat
     *      @param bool  ['includeRoomData']   Whether to include the rooomdata of the room the kick occurred in: columns rooms.id AS kroomId, rooms.name AS roomName, rooms.ownerId, rooms.options, rooms.defaultPermissions, rooms.roomParentalFlags, rooms.roomParentalAge
     * }
     * @param array $sort        Sort columns (see standard definition).
     * @param int   $limit       The maximum number of returned rows (with 0 being unlimited).
     * @param int   $page        The page of the resultset to return.
     *
     * @return databaseResult
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
            $columns[$this->sqlPrefix . "users user"]  = 'id kuserId, name userName, nameFormat userNameFormat, avatar userAvatar';
            $conditions['both']['userId 0'] = $this->col('kuserId');
        }
        if ($options['includeKickerData']) {
            $columns[$this->sqlPrefix . "users kicker"] = 'id kkickerId, name kickerName, nameFormat kickerNameFormat, avatar kickerAvatar';
            $conditions['both']['kickerId 0'] = $this->col('kkickerId');
        }
        if ($options['includeRoomData']) {
            $columns[$this->sqlPrefix . "rooms"] = 'id kroomId, name roomName';
            $conditions['both']['roomId 0'] = $this->col('kroomId');
        }



        // Modify Query Data for Directives (First for Performance)
        if (count($options['userIds']) > 0) $conditions['both']['userId'] = $this->in((array) $options['userIds']);
        if (count($options['roomIds']) > 0) $conditions['both']['roomId'] = $this->in((array) $options['roomIds']);
        if (count($options['kickerIds']) > 0) $conditions['both']['userId'] = $this->in((array) $options['kickerIds']);

        if ($options['lengthMin'] > 0) $conditions['both']['length 1'] = $this->int($options['lengthMin'], 'gte');
        if ($options['lengthMax'] > 0) $conditions['both']['length 2'] = $this->int($options['lengthMax'], 'lte');

        if ($options['timeMin'] > 0) $conditions['both']['time 1'] = $this->int($options['timeMin'], 'gte');
        if ($options['timeMax'] > 0) $conditions['both']['time 2'] = $this->int($options['timeMax'], 'lte');



        // Return
        return $this->select($columns, $conditions, $sort);
    }


    /**
     * Kicks the user, userId, in the room, roomId.
     *
     * @param int $userId The ID of the user to kick.
     * @param int $roomId The ID of the room for the kick to occur in.
     * @param int $length The duration of the kick, in seconds.
     */
    public function kickUser($userId, $roomId, $length) {
        $this->modLog('kickUser', "$userId,$roomId");

        $this->upsert($this->sqlPrefix . "kicks", array(
            'userId' => (int) $userId,
            'roomId' => (int) $roomId,
        ), array(
            'length' => (int) $length,
            'kickerId' => (int) $this->user->id,
            'time' => $this->now(),
        ));

        $this->deletePermissionsCache($roomId, $userId);
    }


    /**
     * Unlicks the user, userId, in the room, roomId.
     *
     * @param int $userId The ID of the user to unkick.
     * @param int $roomId The ID of the room for the kick to be removed from.
     */
    public function unkickUser($userId, $roomId) {
        $this->modLog('unkickUser', "$userId,$roomId");

        $this->delete($this->sqlPrefix . "kicks", array(
            'userId' => (int) $userId,
            'roomId' => (int) $roomId,
        ));

        $this->deletePermissionsCache($roomId, $userId);
    }

    /*********************************************************
     ************************* END ***************************
     ********************* Kick Stuff ************************
     *********************************************************/






    /*********************************************************
     ************************ START **************************
     ****************** Message Retrieval ********************
     *********************************************************/
//TODO: matches any phrase, not all phrases
    public function getMessagesFromPhrases($options, $sort = array('messageId' => 'asc')) {
        $options = $this->argumentMerge(array(
            'roomIds'           => array(),
            'userIds'           => array(),
            'messageTextSearch' => '',
        ), $options);


        $searchArray = [];
        $searchResults = [];
        if ($options['messageTextSearch']) {
            foreach (explode(' ', $this->makeSearchable($options['messageTextSearch'])) AS $searchPhrase) {

                $searchArray[] = $searchPhrase;
            }
        }


        $columns = array(
            $this->sqlPrefix . "searchPhrases"  => 'phraseName, phraseId pphraseId',
            $this->sqlPrefix . "searchMessages" => 'phraseId mphraseId, messageId, userId, roomId'
        );

        $conditions['both']['mphraseId'] = $this->col('pphraseId');


        /* Apply User and Room Filters */
        if (count($options['rooms']) > 1) $conditions['both']['roomId'] = $this->in((array) $options['rooms']);
        if (count($options['users']) > 1) $conditions['both']['userId'] = $this->in((array) $options['users']);


        if (count($searchArray)) {
            /* Determine Whether to Use the Fast or Slow Algorithms */
            if ($this->config['fullTextArchive']) // Slower Algorithm, uses subphrase searching (e.g. the sentence "a quick brown fox jumped over the lazy dog" will match every single individual letter, as well as, among others, "qu", "qui", "quic", "quick", "br", and so-on.)
                foreach ($searchArray AS $phrase) $conditions['both']['either'][]['phraseName'] = $this->type('string', $phrase, 'search');

            else // Original, Fastest Algorithm (only matches whole-words)
                $conditions['both']['phraseName'] = $this->in((array)$searchArray);
        }


        /* Run the Query */
        $matchedMessages = $this->select($columns, $conditions, $sort)->getAsArray('phraseId', true);
        foreach ($matchedMessages AS $phraseId => $message) {

        }
    }



    /**
     * Run a query to obtain messages based on query parameters.
     *
     * @param array $options {
     *      An array of options to filter by.
     *
     *      @param fimRoom ['room']            The room to filter by. Required.
     *      @param array ['messageIds']        An array of messageIds to filter by. Overrides other message ID filter parameters.
     *      @param array ['userIds']           An array of sender userIds to filter the messages by.
     *      @param array ['messageTextSearch'] Filter results by searching for this string in messageText.
     *      @param bool  ['showDeleted']       Whether to include deleted messages. Default false.
     *      @param bool  ['archive']           Whether to query the message archive instead of the main table. Default false. (On average, the main table only includes around 100 messages, so this must be true for archive viewing.)
     *
     *      The following are still being revised:
     *      @param array ['messagesSince']
     *      @param array ['messageIdStart']
     *      @param array ['messageIdEnd']
     *      @param array ['messageDateMax']
     *      @param array ['messageDateMin']
     * }
     * @param array $sort        Sort columns (see standard definition).
     * @param int   $limit       The maximum number of returned rows (with 0 being unlimited).
     * @param int   $page        The page of the resultset to return.
     *
     * @return databaseResult
     */
    public function getMessageIdsFromSearchCache($options, $limit, $page) {
        $options = $this->argumentMerge(array(
            'roomId'           => 0,
            'userId'           => 0,
            'phrases'          => array(),
        ), $options);


        $columns = array(
            $this->sqlPrefix . "searchCache"  => 'phraseName, userId, roomId, resultPage, resultLimit, messageIds, expires',
        );

        $conditions['both']['phraseName'] = $options['phraseNames'];
        $conditions['both']['resultPage'] = $page;
        $conditions['both']['resultLimit'] = $limit;


        /* Apply User and Room Filters */
        if (count($options['roomIds']) > 1)
            $conditions['both']['roomId'] = $this->in((array) $options['roomIds']);
        else
            $conditions['both']['roomId'] = $this->int(0);

        if (count($options['userIds']) > 1) $conditions['both']['userId'] = $this->in((array) $options['userIds']);


        /* Run the Query */
        $cacheRow = $this->select($columns, $conditions)->getAsArray(false);
        if (count($cacheRow) && $cacheRow['expires'] > time()) {
            return explode(',', $cacheRow['messageIds']);
        }
    }




    /**
     * Run a query to obtain messages.
     * getMessages is by far the most advanced set of database calls in the whole application, and is still in need of much fine-tuning. The message stream file uses its own query and must be tested seerately.
     *
     * @param array $options {
     *      An array of options to filter by.
     *
     *      @param fimRoom ['room']            The room to filter by. Required.
     *      @param array ['messageIds']        An array of messageIds to filter by. Overrides other message ID filter parameters.
     *      @param array ['userIds']           An array of sender userIds to filter the messages by.
     *      @param array ['messageTextSearch'] Filter results by searching for this string in messageText.
     *      @param bool  ['showDeleted']       Whether to include deleted messages. Default false.
     *      @param bool  ['archive']           Whether to query the message archive instead of the main table. Default false. (On average, the main table only includes around 100 messages, so this must be true for archive viewing.)
     *
     *      The following are still being revised:
     *      @param array ['messageIdStart']
     *      @param array ['messageIdEnd']
     *      @param array ['messageDateMax']
     *      @param array ['messageDateMin']
     * }
     * @param array $sort        Sort columns (see standard definition).
     * @param int   $limit       The maximum number of returned rows (with 0 being unlimited).
     * @param int   $page        The page of the resultset to return.
     *
     * @return databaseResult
     */
    public function getMessages($options = array(), $sort = array('id' => 'asc'), $limit = 40, $page = 0)
    {
        $options = $this->argumentMerge(array(
            'room'              => false,
            'messageIds'        => array(),
            'userIds'           => array(),
            'messageTextSearch' => '', // Overwrites messageIds.
            'showDeleted'       => false,
            'messageIdStart'    => null,
            'messageIdEnd'      => null,
            'messageDateMax'    => null,
            'messageDateMin'    => null,
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
                    'roomIds' => [$options['room']->id],
                    'userIds' => $options['userIds'],
                    'messageTextSearch' => $options['messageTextSearch'],
                ), null, $limit, $page)->getAsArray('messageId');

                $searchMessageIds = $this->getMessagesFromPhrases(array(
                    'roomIds' => [$options['room']->id],
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
                $this->sqlPrefix . "messages" => 'id, time, iv, salt, roomId, userId, anonId, deleted, flag, text',
            );
        }

        /* Access the Stream */
        else {
            if ($options['room']->isPrivateRoom())
                $columns = [$this->sqlPrefix . "messagesCachedPrivate" => "id cacheId, messageId id, roomId, time, flag, userId, text"];
            else
                $columns = [$this->sqlPrefix . "messagesCached" => "id cacheId, messageId id, roomId, time, flag, userId, messageFormatting, text"];
        }


        /* Conditions Template */
        $conditions = [
            'both' => [],
            'either' => [],
        ];


        /* roomId */
        $conditions['roomId'] = $options['room']->id;


        /* Modify Query Data for Directives */
        if (isset($options['messageDateMax']))
            $conditions['time 1'] = $this->int($options['messageDateMax'], 'lte');
        if (isset($options['messageDateMin']))
            $conditions['time 2'] = $this->int($options['messageDateMin'], 'gte');

        if (isset($options['messageIdStart']))
            $conditions['either']['both']['id 3'] = $this->int($options['messageIdStart'], 'gte');
        if (isset($options['messageIdEnd']))
            $conditions['either']['both']['id 4'] = $this->int($options['messageIdEnd'], 'lte');

        if (!$options['showDeleted'] && $options['archive'])
            $conditions['deleted'] = $this->bool(false);

        if (count($options['messageIds']) > 0)
            $conditions['either']['id'] = $this->in($options['messageIds']);

        if (count($options['userIds']) > 0)
            $conditions['userId'] = $this->in($options['userIds']);



        /* Perform Select */
        $messages = $this->select($columns, $conditions, $sort, $limit, $page);

        return $messages;
    }


    /**
     * Gets a single message, identified by messageId, in the specified room.
     *
     * @param fimRoom $room      The room the message was made in.
     * @param int     $messageId The ID given to the message.
     *
     * @return databaseResult
     */
    public function getMessage(fimRoom $room, $messageId) : fimMessage {
        return $this->getMessages(array(
            'room' => $room,
            'messageIds' => array($messageId),
            'archive' => true,
            'showDeleted' => true,
        ))->getAsMessage();
    }

    /*********************************************************
     ************************* END ***************************
     ****************** Message Retrieval ********************
     *********************************************************/



    public function getModLog($options, $sort = array('time' => 'desc'), $limit = 0, $page = 1) {
        $options = $this->argumentMerge(array(
            'log' => 'mod',
            'userIds' => array(),
            'ips' => array(),
            'combineUserData' => true,
            'actions' => array(),
        ), $options);

        $columns = array(
             $this->sqlPrefix . $options['log'] . 'Log' => 'userId luserId, time, ip, action, data' . ($options['log'] === 'full' ? ', server' : '') . ($options['log'] === 'access' ? ', userAgent, clientCode' : '')
        );

        if (count($options['userIds']) > 0) $conditions['both']['luserId'] = $this->in($options['userIds']);
        if (count($options['ips']) > 0) $conditions['both']['ip'] = $this->in($options['ips']);
        if (count($options['actions']) > 0) $conditions['both']['action'] = $this->in($options['actions']);

        if ($options['combineUserData']) {
            $columns[$this->sqlPrefix . "users"] = $this->userColumns;
            $conditions['both']['id'] = $this->col('luserId');
        }

        return $this->select($columns, $conditions, $sort, $limit, $page);
    }




    function getUnreadMessages() {
        $columns = [$this->sqlPrefix . 'unreadMessages' => 'userId, senderId, senderName, senderNameFormat, roomId, roomName, messageId, time, otherMessages'];

        $conditions = [
            'userId' => $this->user->id,
        ];

        $sort = [
            'messageId' => 'asc',
        ];

        return $this->select($columns, $conditions, $sort);
    }



    /**
     * Run a query to obtain the number of posts made to a room by a user.
     * Use of groupBy highly recommended.
     *
     * @param array $options
     * @param array $sort
     * @param int   $limit
     * @param int   $pagination
     *
     * @return bool|object|resource
     */
    public function getPostStats($options, $sort = array('messages' => 'desc', 'roomId' => 'asc', 'userId' => 'asc'))
    {
        $options = $this->argumentMerge(array(
            'userIds' => array(),
            'roomIds' => array(),
        ), $options);


        $columns = array(
            $this->sqlPrefix . 'roomStats' => 'roomId sroomId, userId suserId, messages',
            $this->sqlPrefix . 'users'     => 'id userId, name userName, nameFormat userNameFormat, avatar',
            $this->sqlPrefix . 'rooms'     => 'id roomId, idEncoded roomIdEncoded, name roomName',
        );


        $conditions['both'] = array(
            'suserId' => $this->col('userId'),
            'sroomId' => $this->col('roomIdEncoded'),
        );


        if (count($options['roomIds']) > 0) $conditions['both']['sroomId 2'] = $this->in($options['roomIds']);
        if (count($options['userIds']) > 0) $conditions['both']['suserId 2'] = $this->in($options['userIds']);


        return $this->select($columns, $conditions, $sort);
    }


    /**
     * @param $options
     * @param array $sort
     * @param int $limit
     * @param int $pagination
     * @return bool|object|resource
     */
    public function getRooms($options, $sort = array('id' => 'asc'), $limit = 50, $pagination = 0)
    {
        $options = $this->argumentMerge(array(
            'roomIds'            => [],
            'roomNames'          => [],
            'ownerIds'           => [],
            'parentalAgeMin'     => 0,
            'parentalAgeMax'     => 0,
            'messageCountMin'    => 0,
            'messageCountMax'    => 0,
            'lastMessageTimeMin' => 0,
            'lastMessageTimeMax' => 0,
            'showHidden'         => true, // This is true by default, because getRoom() should be able to load hidden rooms by default.
            'showDeleted'        => false,
            'roomNameSearch'     => false,
            'columns'            => ['id', 'name', 'topic', 'ownerId', 'defaultPermissions', 'parentalFlags', 'parentalAge', 'options', 'lastMessageId', 'lastMessageTime', 'messageCount'],
        ), $options);

        $columns = [$this->sqlPrefix . 'rooms' => $options['columns']];



        $conditions = [
            'both' => [
                'either' => []
            ]
        ];
        // Modify Query Data for Directives
      	if (!$options['showDeleted']) $conditions['both']['!options'] = $this->int(fimRoom::ROOM_DELETED, 'bAnd');
        if (!$options['showHidden']) $conditions['both']['!options'] = $this->int(fimRoom::ROOM_HIDDEN, 'bAnd');

        if (count($options['roomIds']) > 0) $conditions['both']['either']['id'] = $this->in($options['roomIds']);
        if (count($options['roomNames']) > 0) $conditions['both']['either']['name'] = $this->in($options['roomNames']);
        if ($options['roomNameSearch']) $conditions['both']['either']['name'] = $this->type('string', $options['roomNameSearch'], 'search');

        if ($options['parentalAgeMin'] > 0) $conditions['both']['parentalAge'] = $this->int($options['parentalAgeMin'], 'gte');
        if ($options['parentalAgeMax'] > 0) $conditions['both']['parentalAge'] = $this->int($options['parentalAgeMax'], 'lte');

        if ($options['messageCountMin'] > 0) $conditions['both']['messageCount'] = $this->int($options['messageCount'], 'gte');
        if ($options['messageCountMax'] > 0) $conditions['both']['messageCount'] = $this->int($options['messageCount'], 'lte');

        if ($options['lastMessageTimeMin'] > 0) $conditions['both']['lastMessageTime'] = $this->int($options['lastMessageTime'], 'gte');
        if ($options['lastMessageTimeMax'] > 0) $conditions['both']['lastMessageTime'] = $this->int($options['lastMessageTime'], 'lte');


        // Perform Query
        return $this->where($conditions)->sortBy($sort)->limit($limit)->page($pagination)->select($columns);
    }



    public function getRoom($roomId) : fimRoom
    {
        return $this->getRooms(array(
            'roomIds' => array($roomId)
        ))->getAsRoom();
    }



    public function getUsers($options = array(), $sort = array('id' => 'asc'), $limit = 0, $pagination = 0)
    {
        $options = $this->argumentMerge(array(
            'userIds'        => array(),
            'userNames'      => array(),
            'userNameSearch' => false,
            'bannedStatus'   => false,
            'hasPrivs' => [],
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


        if (count($options['hasPrivs']) > 0) {
            foreach ($options['hasPrivs'] AS $adminPriv) $conditions['both']['either']["privs $adminPriv"] = $this->int($adminPriv, 'bAnd');
        }


        if (count($options['userIds']) > 0)
            $conditions['both']['either']['id'] = $this->in($options['userIds']);
        if (count($options['userNames']) > 0)
            $conditions['both']['either']['name 1'] = $this->in($options['userNames']);
        if ($options['userNameSearch'])
            $conditions['both']['either']['name 2'] = $this->type('string', $options['userNameSearch'], 'search');


        return $this->select($columns, $conditions, $sort);
    }



    public function getUser($userId)
    {
        return $this->getUsers(array(
            'userIds' => array($userId)
        ))->getAsUser();
    }



    public function getSessions($options = array(), $sort = array('expires' => 'desc'), $limit = 0, $pagination = 1)
    {
        $options = $this->argumentMerge(array(
            'sessionIds' => array(),
            'userIds'      => array(),
            'ips' => array(),
            'combineUserData' => true,
        ), $options);


        $columns = array(
            $this->sqlPrefix . "oauth_access_tokens" => 'access_token, client_id clientId, user_id suserId, anon_id anonId, expires, scope, ip_address sessionIp, http_user_agent userAgent',
        );

        $conditions = array();

        if (count($options['userIds']) > 0) $conditions['both']['either']['suserId'] = $this->in($options['userIds']);
        if (count($options['ips']) > 0) $conditions['both']['either']['sessionIp'] = $this->in($options['ips']);

        if ($options['combineUserData']) {
            $columns[$this->sqlPrefix . "users"] = $this->userColumns;
            $conditions['both']['id'] = $this->col('suserId');
        }

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


    /********************************************************
     ******************* PERMISSIONS ************************
     ********************************************************/


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
        if ($room->isPrivateRoom()) {
            $userIds = $room->getPrivateRoomMemberIds();

            if ($room->type === fimRoom::ROOM_TYPE_OTR && !$this->config['otrRoomsEnabled']) // Disallow OTR rooms if disabled.
                return 0;

            elseif ($room->type === fimRoom::ROOM_TYPE_PRIVATE && !$this->config['privateRoomsEnabled']) // Disallow private rooms if disabled.
                return 0;

            elseif (count($userIds) > $this->config->privateRoomMaxUsers) // Disallow private rooms with too many members.
                return 0;

            else {
                $users = $room->getPrivateRoomMembers();

                if (count($users) !== count($userIds)) // This checks for invalid users, as getPrivateRoomMembers() will only return members who exist in the database, while getPrivateRoomMemberIds() returns all ids who were specified when the fimRoom object was created.
                    return 0;

                elseif (in_array($user->id, $userIds))
                    return fimRoom::ROOM_PERMISSION_VIEW | fimRoom::ROOM_PERMISSION_POST; // The logic with private rooms is fairly self-explanatory: roomAlias lists all valid userIds, so check to see if the user is in there.

                else
                    return 0;
            }
        }
        else {
            /* Check for Cached Entry */
            $permissionsCached = $this->getPermissionCache($room->id, $user->id);
            if ($permissionsCached > -1) // -1 equals an outdated permission.
                return $permissionsCached;


            /* Resolve Needed User Parameters */
            if (!$user->resolve(array('socialGroupIds', 'parentalAge', 'parentalFlags')))
                throw new Exception('hasPermission was called without a valid user.'); // Make sure we know the room type and alias in addition to ID.


            /* Obtain Data from roomPermissions Table */
            $permissionsBitfield = $this->getPermissionsField($room->id, $user->id, $user->socialGroupIds);


            /* Base calculation -- these are what permisions a user is supposed to have, before userPrivs and certain room properties are factored in. */
            // Super moderators have all permissions (and can't be banned).
            if ($user->hasPriv('modRooms'))
                $returnBitfield = 65535;

            // A list of "banned" user groups can be specified in config. These groups lose all permissions, similar to having userPrivs = 0. But, in the interest of sanity, we don't check it elsewhere.
            elseif (in_array($user->mainGroupId, $this->config['bannedUserGroups']))
                $returnBitfield = 0;

            // Owners have all permissions (but can still be banned).
            elseif ($room->ownerId === $user->id)
                $returnBitfield = 65535;

            // A user blocked by parental controls has no permissions. This cannot apply to the room owner.
            elseif ($room->parentalAge > $user->parentalAge) //|| fim_inArray($user->parentalFlags, $room->parentalFlags) TODO!
                $returnBitfield = 0;

            else {
                if ($permissionsBitfield === -1) // No permissions bitfield was found in the permission's table, use the room's default.
                    $returnBitfield = $room->defaultPermissions;
                else
                    $returnBitfield = $permissionsBitfield;

                // Kicked users may ONLY view.
                if ($this->getKicks(array(
                        'userIds' => array($user->id),
                        'roomIds' => array($room->id),
                        'includeUserData' => false,
                        'includeKickerData' => false,
                        'includeRoomData' => false,
                    ))->getCount() > 0) {
                    $returnBitfield &= fimRoom::ROOM_PERMISSION_VIEW;
                }
            }


            /* Remove priviledges under certain circumstances. */
            // Remove priviledges that a user does not have for any room.
            if (!($user->hasPriv('view')))
                $returnBitfield &= ~fimRoom::ROOM_PERMISSION_VIEW; // If banned, a user can't view anything.

            if (!($user->hasPriv('post')))
                $returnBitfield &= ~fimRoom::ROOM_PERMISSION_POST; // If silenced, a user can't post anywhere.

            if (!($user->hasPriv('changeTopic')))
                $returnBitfield &= ~fimRoom::ROOM_PERMISSION_TOPIC;


            // Deleted and archived rooms act similarly: no one may post in them, while only admins can view deleted rooms.
            if ($room->deleted || $room->archived) { // that is, check if a room is either deleted or archived.
                if ($room->deleted && !$user->hasPriv('modRooms')) $returnBitfield &= ~(fimRoom::ROOM_PERMISSION_VIEW); // Only super moderators may view deleted rooms.

                $returnBitfield &= ~(fimRoom::ROOM_PERMISSION_POST | fimRoom::ROOM_PERMISSION_TOPIC); // And no one can post in them - a rare case where even admins are denied certain abilities.
            }


            /* Update cache and return. */
            $this->updatePermissionsCache($room->id, $user->id, $returnBitfield, (isset($kicks) && $kicks > 0 ? true : false));

            return $returnBitfield;
        }
    }


    /**
     * Gets all permission entries in one or more rooms.
     *
     * @param array $roomIds
     * @param string $attribute enum("user", "group")
     * @param array $params
     * @todo cache calls
     *
     * @return databaseResult
     */
    public function getRoomPermissions($roomIds = array(), $attribute = false, $params = array())
    {
        // Modify Query Data for Directives (First for Performance)
        $columns = array(
            $this->sqlPrefix . "roomPermissions" => 'roomId, attribute, param, permissions',
        );

        if (count($roomIds) > 0) $conditions['both']['roomId'] = $this->in((array) $roomIds);
        if ($attribute) $conditions['both']['attribute'] = $this->str($attribute);
        if (count($params) > 0) $conditions['both']['param'] = $this->in((array) $params);

        return $this->select($columns, $conditions);
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

        if (!count($permissions)) {
            return -1;
        }
        else {
            $groupBitfield = 0;
            foreach ($permissions AS $permission) {
                if ($permission['attribute'] === 'user') return $permission['permissions']; // If a user permission exists, then it overrides group permissions.
                else $groupBitfield &= $permission['permissions']; // Group permissions, on the other hand, stack. If one group has ['view', 'post'], and another has ['view', 'moderate'], then a user in both groups has all three.
            }

            return $groupBitfield;
        }
    }


    public function setPermission($roomId, $attribute, $param, $permissionsMask) {
        /* Start Transaction */
        $this->startTransaction();

        /* Modlog */
        $this->modLog('setPermission', "$roomId,$attribute,$param,$permissionsMask");

        /* Insert or Replace The Old Permission Setting */
        $this->upsert($this->sqlPrefix . 'roomPermissions', array(
            'permissions' => $this->type(DatabaseTypeType::bitfield, $permissionsMask)
        ), array(
            'roomId' => $roomId,
            'attribute' => $attribute,
            'param' => $param,
            'permissions' => $this->type(DatabaseTypeType::bitfield, $permissionsMask)
        ));

        /* Delete Permissions Cache */
        if ($attribute === 'user')
            $this->deletePermissionsCache($roomId, $param);
        elseif ($attribute === 'group')
            $this->deleteGroupPermissionsCache($roomId, $param);

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

        $this->deletePermissionsCache($roomId, $attribute, $param);

        $this->endTransaction();
    }


    /**
     * Gets the a user's cached permission bitfield for a room, or -1 if none/expired.
     *
     * @param int $roomId The room the permission applies in.
     * @param int $userId The user the permission applies to.
     *
     * @return int A permission bitfield, or -1 if none/expired.
     */
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
     * Updates an entry in the room permissions cache. When a permission change occurs, this or deletePermissionCache should be called.
     *
     * @param int $roomId The room ID a permission change has occured in.
     * @param int $userId The user ID for whom a permission has changed.
     * @param int $permissions The new permissions bitfield.
     * @param bool $isKicked Whether the permission cache is because of a kick (this is not generally used internally, but can be used to indicate to a user that they have been denied permission because of a kick)
     */
    public function updatePermissionsCache($roomId, $userId, $permissions, $isKicked = false) {
        if ($this->config['roomPermissionsCacheEnabled']) {
            $this->upsert($this->sqlPrefix . 'roomPermissionsCache', array(
                'roomId' => $roomId,
                'userId' => $userId,
            ), array(
                'permissions' => $permissions,
                'expires' => $this->now($this->config['roomPermissionsCacheExpires']),
                'isKicked' => $this->bool($isKicked),
            ));
        }
    }

    /**
     * Deleletes an entry in the room permissions cache. When a permission change occurs, this or updatePermissionCache should be called.
     *
     * @param int $roomId The room ID a permission change has occured in.
     * @param int $userId The user ID for whom a permission has changed.
     */
    public function deletePermissionsCache($roomId = false, $userId = false) {
        if ($this->config['roomPermissionsCacheEnabled']) {
            $conditions = [];

            if ($roomId !== false) $conditions['roomId'] = $roomId;
            if ($userId !== false) $conditions['userId'] = $userId;

            $this->delete($this->sqlPrefix . 'roomPermissionsCache', $conditions);
        }
    }


    /**
     * Deletes a permission cache based on a change to a room permission entry -- and thus able to resolve social group members, etc.
     *
     * @param int $roomId
     * @param string $attribute
     * @param int $param The new permission.
     *
     * @todo still a work in progress
     */
    public function deleteGroupPermissionsCache($roomId, $groupId) {
        $users = $this->getSocialGroupMembers(array(
            'groupIds' => array($groupId),
            'type' => array('member', 'moderator')
        ))->getColumnValues('userId');

        $this->delete($this->prefix . 'roomPermissionsCache', array(
            'roomId' => $roomId,
            'userId' => $this->in($users)
        ));
    }


    /****** Insert/Update Functions *******/

    /********************************************************
     ******************* SESSIONS ***************************
     ********************************************************/

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
            'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
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
        // Note: As defined, attempts will further increase, and expires will further increase, with each additional query beyond the "lockout". As a result, this function generally shouldn't be called if a user is already lockedout -- otherwise, further attempts just lock them out further, when they could be the user checking to see if they are still locked out. So always call lockoutActive before calling lockoutIncrement.
        $this->upsert($this->sqlPrefix . 'sessionLockout', array(
            'ip' => $_SERVER['REMOTE_ADDR'],
        ), array(
            'attempts' => $this->equation('$attempts + 1'),
            'expires' => $this->now($this->config['lockoutExpires']) // TOOD: Config
        ), array(
            'attempts' => 1,
        ));

        return true;
    }

    public function lockoutActive() {
        // Note: Select condition format is experimental and untested, and numRows is not yet implemented. So, uh, do that. Lockout count is also unimplemented.
        if ($this->select(array(
                $this->sqlPrefix . 'sessionLockout' => 'ip, attempts, expires'
            ), array(
                'ip' => $_SERVER['REMOTE_ADDR'],
            ))->getColumnValue('attempts') >= $this->config['lockoutCount']) return true;

        return false;
    }



    public function updateUserCaches() {
    }



    /********************************************************
     ******************* LISTS ******************************
     ********************************************************/

    /**
     * Gets an array list of all room IDs a user has favorited.
     *
     * @param int $userId The user to filter by.
     * @return int[]
     */
    public function getUserFavRooms($userId) {
        return $this->select([
            $this->sqlPrefix . 'userFavRooms' => 'userId, roomId'
        ], [
            'userId' => $userId
        ])->getColumnValues('roomId');
    }

    /**
     * Gets an array list of all room IDs a user has favorited.
     *
     * @param int $userId The user to filter by.
     * @return int[]
     */
    public function getUserFriendsList($userId) {
        return $this->select([
            $this->sqlPrefix . 'userFriendsList' => 'userId, subjectId, status'
        ], [
            'userId' => $userId,
            'status' => 'friend',
        ])->getColumnValues('subjectId');
    }

    /**
     * Gets an array list of all room IDs a user has favorited.
     *
     * @param int $userId The user to filter by.
     * @return int[]
     */
    public function getUserIgnoreList($userId) {
        return $this->select([
            $this->sqlPrefix . 'userIgnoreList' => 'userId, subjectId'
        ], [
            'userId' => $userId,
        ])->getColumnValues('subjectId');
    }

    /**
     * Gets an array list of all room IDs a user has favorited.
     *
     * @param int $userId The user to filter by.
     * @return int[]
     */
    public function getUserWatchRoom($userId) {
        return $this->select([
            $this->sqlPrefix . 'watchRooms' => 'userId, roomId'
        ], [
            'userId' => $userId
        ])->getColumns('roomId');
    }

    /**
     * Gets an array list of all social group IDs a user has joined.
     *
     * @param int $userId The user to filter by.
     * @return int[]
     */
    public function getUserSocialGroupIds($userId) {
        return $this->select([
            $this->sqlPrefix . 'socialGroupMembers' => 'groupId, userId, type'
        ], [
            'userId' => $userId,
            'type' => $this->in(['member', 'moderator'])
        ])->getColumns('roomId');
    }


    /**
     * Gets the entries from the watchRooms table corresponding with a single roomId. fimRoom($roomId)->watchRooms should generally be used instead, since it implements additional caching.
     *
     * @param $roomId
     * @return mixed
     * @throws Exception
     */
    public function getWatchRoomUsers($roomId) {
        return $watchRoomIds = $this->select([
            $this->sqlPrefix . 'watchRooms' => 'userId, roomId'
        ], [
            'roomId' => $this->int($roomId)
        ])->getColumnValues('userId');
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
        $dbLists = $this->getCensorListsActive($roomId);// var_dump($lists); die();

        foreach ($lists AS $listId => $listEnable) {
            if ($listEnable && !isset($dbLists[$listId])) // the list should be enabled but isn't currently
                $this->setCensorList($roomId, $listId, 'block');

            elseif (!$listEnable && isset($dbLists[$listId])) // the list shouldn't be enabled but is currently
                $this->setCensorList($roomId, $listId, 'unblock');
        }
    }



    public function markMessageRead($roomId, $userId)
    {
        if ($this->config['enableUnreadMessages']) {
            return $this->delete($this->sqlPrefix . "unreadMessages", array(
                'roomId'    => $roomId,
                'userId'    => $userId
            ));
        }

        return false;
    }


    /**
     * Changes the user's status.
     *
     * @param string $status
     * @param bool   $typing
     */
    public function setUserStatus($roomId, $status = null, $typing = null) {
        $conditions = array(
            'userId' => $this->user->id,
            'roomId' => $roomId
        );

        $data = array(
            'time' => $this->now(),
        );

        if (!is_null($typing)) $data['typing'] = (bool) $typing;
        if (!is_null($status)) $data['status'] = $status;

        if ($status === 'offline')
            $this->delete($this->sqlPrefix . 'ping', $conditions);
        else
            $this->upsert($this->sqlPrefix . 'ping', $conditions, $data);
    }


    /*
     * Store message does not check for permissions. Make sure that all permissions are cleared before calling storeMessage.
     */
    public function storeMessage(fimMessage $message)
    {
        /**
         * Flood limit check.
         * As this is... pretty important to ensure, we perform this check at the last possible moment, here in storeMessage.
         */
        if ($this->config['floodDetectionGlobal']) {
            $time = time();
            $minute = $this->ts($time - ($time % 60));
            $messageFlood = $this->select([
                $this->sqlPrefix . 'messageFlood' => 'userId, roomId, messages, time'
            ], [
                'userId' => $message->user->id,
                'roomId' => $this->in([$message->room->id, 0]),
                'time' => $minute,
            ])->getAsArray('roomId');

            if ($messageFlood[$message->room->id]['messages'] >= $this->config['floodRoomLimitPerMinute'])
                new fimError('roomFlood', 'Room flood limit breached.');

            if ($messageFlood[0]['messages'] >= $this->config['floodSiteLimitPerMinute'])
                new fimError('siteFlood', 'Site flood limit breached.');
        }



        /* Preemptively resolve all needed $user properties */
        $message->user->resolve(array("messageFormatting"));



        $this->startTransaction();



        /* Insert Message Data */
        // Insert into permanent datastore, unless it's an off-the-record room (since that's the only way it's different from a normal private room), in which case we just try to get an autoincremented messageId, storing nothing else.
        if ($message->room->type === 'otr') {
            $this->insert($this->sqlPrefix . "messages", array(
                'roomId'   => $message->room->id,
            ));
            $message->id = $this->getLastInsertId();
        }
        else {
            $this->insert($this->sqlPrefix . "messages", array(
                'roomId'   => $message->room->id,
                'userId'   => $message->user->id,
                'text'     => $this->blob($message->textEncrypted),
                'textSha1' => sha1($message->text),
                'salt'     => $message->salt,
                'iv'       => $this->blob($message->iv),
                'ip'       => $_SERVER['REMOTE_ADDR'],
                'flag'     => $message->flag,
                'time'     => $this->now(),
            ));
            $message->id = $this->getLastInsertId();
        }


        // Insert into cache/memory datastore.
        if ($message->room->isPrivateRoom()) {
            $this->insert($this->sqlPrefix . "messagesCachedPrivate", array(
                'messageId'         => $message->id,
                'roomId'            => $message->room->id,
                'userId'            => $message->user->id,
                'text'              => $message->text,
                'flag'              => $message->flag,
                'time'              => $this->now(),
            ));
        }
        else {
            $this->insert($this->sqlPrefix . "messagesCached", array(
                'messageId'         => $message->id,
                'roomId'            => $message->room->id,
                'userId'            => $message->user->id,
                'messageFormatting' => $message->user->messageFormatting,
                'text'              => $message->text,
                'flag'              => $message->flag,
                'time'              => $this->now(),
            ));
        }
        $messageCacheId = $this->getLastInsertId();


        // Generate (and Insert) Key Words, Unless an Off-the-Record Room
        if ($message->room->type !== 'otr') {
            $keyWords = $this->getKeyWordsFromText($message->text);
            $this->storeKeyWords($keyWords, $message->id, $message->user->id, $message->room->id);
        }



        /* Update the Various Caches */
        // Update room caches.
        $this->update($this->sqlPrefix . "rooms", array(
            'lastMessageTime' => $this->now(),
            'lastMessageId'   => $message->id,
            'messageCount'    => $this->equation('$messageCount + 1')
        ), array(
            'id' => $message->room->id,
        ));


        // Update the messageIndex if appropriate
        if (!$message->room->isPrivateRoom()) {
            $room = $this->getRoom($message->room->id); // Get the new room data. (TODO: UPDATE ... RETURNING for PostGreSQL)

            if ($message->room->messageCount % $this->config['messageIndexCounter'] === 0) { // If the current messages in the room is divisible by the messageIndexCounter, insert into the messageIndex cache. Note that we are hoping this is because of the very last query which incremented this value, but it is impossible to know for certain (if we tried to re-order things to get the room data first, we still run this risk, so that doesn't matter; either way accuracy isn't critical). Postgres would avoid this issue, once implemented.
                $this->insert($this->sqlPrefix . "messageIndex", array(
                    'roomId'    => $message->room->id,
                    'interval'  => $message->room->messageCount,
                    'messageId' => $message->id
                ));
            }
        }


        // Update the messageDates if appropriate (TODO: should be on a per-room basis)
        /*$lastDayCache = (int) $generalCache->get('fim3_lastDayCache');

        $currentTime = time();
        $lastMidnight = $currentTime - ($currentTime % $this->config['messageTimesCounter']); // Using some cool math (look it up if you're not familiar), we determine the distance from the last even day, then get the time of the last even day itself. This is the midnight reference point.

        if ($lastDayCache < $lastMidnight) { // If the most recent midnight comes after the period at which the time cache was last updated, handle that. Note that, though rare-ish, this query may be executed by a few different messages. It's not a big deal, since the primary key will prevent any duplicate entries, but still.
            $generalCache->set('fim3_lastDayCache', $lastMidnight); // Update the quick cache.

            $this->insert($this->sqlPrefix . "messageDates", array(
                'time'      => $lastMidnight,
                'messageId' => $messageId
            ));
        }*/


        // Update Flood Counter
        $time = time();
        $minute = $this->ts($time - ($time % 60));
        foreach ([$message->room->id, 0] AS $roomId) {
            $this->upsert($this->sqlPrefix . "messageFlood", array(
                'userId' => $message->user->id,
                'roomId' => $roomId,
                'time' => $minute,
            ), array(
                'ip' => $_SERVER['REMOTE_ADDR'],
                'messages' => $this->equation('$messages + 1'),
            ), array(
                'messages' => 1,
            ));
        }


        // Update user caches
        $this->update($this->sqlPrefix . "users", array(
            'messageCount' => $this->equation('$messageCount + 1'),
        ), array(
            'id' => $message->user->id,
        ));


        // Insert or update a user's room stats.
        $this->upsert($this->sqlPrefix . "roomStats", array(
            'userId'   => $message->user->id,
            'roomId'   => $message->room->id,
        ), array(
            'messages' => $this->equation('$messages + 1')
        ), array(
            'messages' => 1
        ));


        // Increment the messages counter.
        $this->incrementCounter('messages');


        // Enter message into stream
        require_once('StreamFactory.php');
        StreamFactory::publish('room_' . $message->room->id, 'newMessage', [
            'id' => $message->id,
            'text' => $message->text,
            'userId' => $message->user->id,
        ]);


        // Delete old messages from the cache, based on the maximum allowed rows. (TODO: test)
        if ($messageCacheId > $this->config['messageCacheTableMaxRows']) {
            $this->partitionAt(['roomId' => $message->room->id])->delete($this->sqlPrefix . "messagesCached" . ($message->room->isPrivateRoom() ? 'Private' : ''), [
                'id' => $this->int($messageCacheId - $this->config['messageCacheTableMaxRows'], 'lte')
            ]);
        }


        // If the contact is a private communication, create an event and add to the message unread table.
        if ($message->room->isPrivateRoom()) {
            foreach (($message->room->getPrivateRoomMemberIds()) AS $sendToUserId) {
                if ($sendToUserId == $message->user->id)
                    continue;
                else
                    $this->createUnreadMessage($sendToUserId, $message->user, $message->room, $message->id);
            }
        }
        else {
            foreach ($message->room->watchedByUsers AS $sendToUserId) {
                $this->createUnreadMessage($sendToUserId, $message->user, $message->room, $message->id);
            }
        }


        $this->endTransaction();


        // Return the ID of the inserted message.
        return $message->id;
    }


    /**
     * Updates the database representation of an object to match its state as an object.
     *
     * @param fimMessage $message The message object, as currently stored.
     */
    public function updateMessage(fimMessage $message) {
        if (!$message->id)
            new fimError('badUpdateMessage', 'Update message must operate on a message with a valid ID.');

        $this->startTransaction();

        // Get the old message, for the edit history log.
        $oldMessage = $this->getMessage($message->room, $message->id);

        if ($oldMessage->text != $message->text) {
            // Create logs of edit
            $this->modLog('editMessage', $message->id);

            $this->insert($this->sqlPrefix . "messageEditHistory", array(
                'messageId' => $message->id,
                'roomId' => $message->room->id,
                'userId' => $message->user->id,
                'oldText' => $oldMessage->textEncrypted,
                'newText' => $message->textEncrypted,
                'iv1' => $oldMessage->iv,
                'iv2' => $message->iv,
                'salt1' => $oldMessage->salt,
                'salt2' => $message->salt,
                'ip' => $_SERVER['REMOTE_ADDR'],
            ));

            // Update keywords for searching
            $this->dropKeyWords($message->id);
            $keyWords = $this->getKeyWordsFromText($message->text);
            $this->storeKeyWords($keyWords, $message->id, $this->user->id, $message->room->id);

            // Create event to prompt update in existing message displays.
            $this->createEvent('editedMessage', $message->user->id, $message->room->id, $message->id, false, false, false);
        }

        // Update message entry itself
        $this->update($this->sqlPrefix . "messages", [
            'text' => $message->textEncrypted,
            'iv' => $message->iv,
            'salt' => $message->salt,
            'flag' => $message->flag,
            'deleted' => $this->bool($message->deleted)
        ], array(
            'roomId' => $message->room->id,
            'id' => $message->id,
        ));

        // Update message caches
        if ($message->deleted) {
            $this->delete($this->sqlPrefix . "messagesCached" . ($message->room->isPrivateRoom() ? "Private" : ""), [
                "roomId" => $message->room->id,
                "messageId" => $message->id
            ]);
        }
        else {
            // Note: this does mean that undeleting a message will not put it back into the message cache.
            $this->update($this->sqlPrefix . "messagesCached" . ($message->room->isPrivateRoom() ? "Private" : ""), [
                'text' => $message->text,
            ], [
                'roomId' => $message->room->id,
                'messageId' => $message->id,
            ]);
        }

        $this->endTransaction();
    }









    public function createUnreadMessage($sendToUserId, fimUser $user, fimRoom $room, $messageId) {
        if ($this->config['enableUnreadMessages']) {
            if ($room->isPrivateRoom()) // If watched rooms created events, our event log may quickly run out of space, causing missed events.
                $this->createUserEvent('missedMessage', $sendToUserId, $room->id, $messageId);

            $this->upsert($this->sqlPrefix . "unreadMessages", array(
                'userId'            => $sendToUserId,
                'roomId'            => $room->id
            ), array(
                'senderId'          => $user->id,
                'senderName'        => $user->name,
                'senderNameFormat'  => $user->nameFormat,
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
        list($contentsEncrypted, $iv, $saltNum) = fim_encrypt($file->contents, FIM_ENCRYPT_FILECONTENT);

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
        $fileId = $this->getLastInsertId();

        $this->insert($this->sqlPrefix . "fileVersions", array(
            'fileId' => $fileId,
            'sha256hash' => $file->sha256hash,
            'salt' => $this->int($saltNum),
            'iv' => $this->blob($iv),
            'size' => $file->size,
            'contents' => $this->blob($contentsEncrypted),
            'time' => time(),
        ));
        $versionId = $this->getLastInsertId();

        $this->update($this->sqlPrefix . "users", array(
            'fileCount' => $this->type('equation', '$fileCount + 1'),
            'fileSize' => $this->type('equation', '$fileSize + ' . (int) $file->size),
        ), array(
            'id' => $user->id,
        ));

        $this->incrementCounter('uploads');
        $this->incrementCounter('uploadSize', $file->size);

        if ($room->id)
            $this->storeMessage(new fimMessage([
                'room'        => $room,
                'user'        => $user,
                'text'        => $file->webLocation,
                'flag'        => $file->container,
            ]));

        if (in_array($file->extension, $this->config['imageTypes'])) {
            list($width, $height) = getimagesizefromstring($file->contents);

            if ($width > $this->config['imageResizeMaxWidth'] || $height > $this->config['imageResizeMaxHeight']) {

            }
            elseif (!$imageOriginal = imagecreatefromstring($file->contents)) {
                throw new fimError('resizeFailed', 'The image could not be thumbnailed. The file was still uploaded.');
            }
            else {
                foreach ($this->config['imageThumbnails'] AS $resizeFactor) {
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

                    list($thumbnailEncrypted, $iv, $keyNum) = fim_encrypt($thumbnail, FIM_ENCRYPT_FILECONTENT);

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
        )->getAsArray('phraseName'); // TODO: what the hell were you thinking, exactly?


        foreach (array_unique($words) AS $piece) {
            if (!isset($phraseData[$piece])) {
                $this->insert($this->sqlPrefix . "searchPhrases", array(
                    'phraseName' => $piece,
                ));
                $phraseId = $this->getLastInsertId();
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
        if (!isset($this->user->id)) throw new Exception('database->modLog requires user->id');

        if ($this->insert($this->sqlPrefix . "modLog", array(
            'userId' => (int) $this->user->id,
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
        if ($this->insert($this->sqlPrefix . "fullLog", array(
            'userId'   => $this->user->id,
            'server' => json_encode(array_intersect_key($_SERVER,array_flip($this->config['fullLogServerDirectives']))),
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

    public function accessLog($action, $data)
    {
        if ($this->config['floodDetectionGlobal']) {
            $time = time();
            $minute = $time - ($time % 60);

            $floodCount = $this->select([
                  $this->sqlPrefix . 'accessFlood' => 'action, ip, period, count'
            ], [
                'action' => $action,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'period' => $this->ts($minute),
            ])->getColumnValue('count');

            if ($floodCount > $this->config['floodDetectionGlobal_' . $action . '_perMinute'] && !$this->user->hasPriv('modPrivs')) {
                new fimError("flood", "Your IP has sent too many $action requests ($floodCount observed).", null, null, "HTTP/1.1 429 Too Many Requests");
            }
            else {
                $this->upsert($this->sqlPrefix . "accessFlood", [
                    'action'  => $action,
                    'ip'      => $_SERVER['REMOTE_ADDR'],
                    'period'  => $this->ts($minute),
                ], [
                    'userId' => $this->user->id,
                    'count'  => $this->equation('$count + 1'),
                    'expires' => $this->ts($minute + 60),
                ], [
                    'count' => 1,
                ]);
            }
        }

        if ($this->config['accessLogEnabled']) {
            if ($this->insert($this->sqlPrefix . "accessLog", array(
                'userId' => $this->user->id,
                'sessionHash' => $this->user->sessionHash,
                'action' => $action,
                'data' => json_encode($data),
                'time' => $_SERVER['REQUEST_TIME_FLOAT'],
                'executionTime' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
                'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'clientCode' => $this->user->clientCode,
                'ip' => $_SERVER['REMOTE_ADDR'],
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
    // TODO: Shouldn't be part of fimDatabase.php.
    public function getKeyWordsFromText($text) {
        $string = $this->makeSearchable($text);

        $stringPieces = array_unique(explode(' ', $string));
        $stringPiecesAdd = array();

        foreach ($stringPieces AS $piece) {
            if (strlen($piece) >= $this->config['searchWordMinimum'] &&
                strlen($piece) <= $this->config['searchWordMaximum'] &&
                !in_array($piece, $this->config['searchWordOmissions'])) $stringPiecesAdd[] = $piece;
        }

        if (count($stringPiecesAdd) > 0) {
            sort($stringPiecesAdd);

            return $stringPiecesAdd;
        }
        else {
            return array();
        }
    }



    /****** TRIGGERS ******/
    public function triggerRoomWatchedByIds($set, $dataChanges) {
        $this->triggerRoomListCache($set, "watchedByUsers", $dataChanges);
    }

    public function triggerRoomListCache($roomId, $cacheColumn, $dataChanges) {
        global $generalCache, $config;

        $room = fimRoomFactory::getFromId((int) $roomId);
        $listEntries = $room->{$cacheColumn};

        /*if (count($listEntries) > $config['databaseCollectionMaxEntries']) {
            $cacheIndex = 'fim_' . $cacheColumn . '_' . $roomId;

            if (!$generalCache->exists($cacheIndex, 'redis')) {
                $generalCache->setAdd($cacheIndex, $listEntries);
            }

            foreach ($dataChanges AS $operation => $values) {
                switch ($operation) {
                    case 'delete':
                        if (is_string($values) && $values === '*')
                            $generalCache->clear($cacheIndex, 'redis');

                        else
                            $generalCache->setRemove($cacheIndex, $listEntries);
                    break;

                    case 'insert':
                        $generalCache->setAdd($cacheIndex, $values);
                    break;
                }
            }
        }

        else { // Use database*/
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
        //}

        $room->setDatabase([
            $cacheColumn => $listEntries
        ]);
    }




    /**
     * Overrides the normal function to use fimDatabaseResult instead.
     * @see Database::databaseResultPipe()
     */
    protected function databaseResultPipe($queryData, $reverseAlias, string $sourceQuery, Database $database, int $paginated = 0) {
        return new fimDatabaseResult($queryData, $reverseAlias, $sourceQuery, $database, $paginated);
    }
}

require('fimDatabaseResult.php');
?>