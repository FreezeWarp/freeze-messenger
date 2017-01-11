<?php
/**
 *
 *
 * TODO:
 * Private Room Auto Name Generation
 * High-Entropy Room Names
 */

define("ROOM_OFFICIAL", 1);
define("ROOM_DELETED", 4);
define("ROOM_HIDDEN", 8);
define("ROOM_ARCHIVED", 16);
define("ROOM_R9000", 256);

define("ROOM_PERMISSION_VIEW", 1);
define("ROOM_PERMISSION_POST", 2);
define("ROOM_PERMISSION_TOPIC", 4);
define("ROOM_PERMISSION_MODERATE", 8);
define("ROOM_PERMISSION_PROPERTIES", 16);
define("ROOM_PERMISSION_GRANT", 128);

class fimRoom {
    public $id = 0;
    private $name = "Missingname.";
    private $alias; // TODO: remove
    private $options;
    private $deleted;
    private $official;
    private $hidden;
    private $archived;
    private $ownerId;
    private $topic;
    private $type = 'unknown';
    private $parentalFlags;
    private $parentalAge;
    private $defaultPermissions;
    private $lastMessageId;
    private $lastMessageTime;
    private $messageCount;
    private $flags;
    private $encodedId;
    private $watchedBy;

    protected $roomData;

    private $resolved = array();
    private static $roomDataConversion = array( // Eventually, we'll hopefully rename everything in the DB itself, but that'd be too time-consuming right now.
        'roomId' => 'id',
        'roomName' => 'name',
        'roomAlias' => 'alias', // TODO: remove
        'options' => 'options',
        'ownerId' => 'ownerId',
        'roomTopic' => 'topic',
        'roomParentalFlags' => 'parentalFlags',
        'roomParentalAge' => 'parentalAge',
        'defaultPermissions' => 'defaultPermissions',
        'lastMessageTime' => 'lastMessageTime',
        'lastMessageId' => 'lastMessageTime',
        'messageCount' => 'messageCount',
        'watchedBy' => 'watchedBy',
        'flags' => 'flags'
    );

    private static $roomDataPullGroups = array(
        'roomId, roomName',
        'defaultPermissions, options',
        'roomParentalFlags,roomParentalAge,roomTopic',
        'lastMessageTime,lastMessageId,messageCount,flags'
    );

    private $generalCache;

    /**
     * @param $roomData mixed Should either be an array or an integer (other values will simply fail to populate the object's data). If an array, should correspond with a row obtained from the `rooms` database, if an integer should correspond with the room ID.
     */
    function __construct($roomData) {
        global $generalCache;
        $this->generalCache = $generalCache;


        if (is_int($roomData)) {
            $this->__set('type', 'general');
            $this->id = $roomData;
        }
        elseif (is_string($roomData) && $this->isPrivateRoomId($roomData)) {
            /* Set room type */
            if ($roomData[0] === 'p')     $this->__set('type', 'private');
            elseif ($roomData[0] === 'o') $this->__set('type', 'otr');
            else throw new Exception('Sanity check failed.');

            /* Set other room defaults */
            $this->__set('options', 0);
            $this->__set('parentalAge', 0);
            $this->__set('parentalFlags', []);
            $this->__set('topic', '');
            $this->__set('ownerId', 0);
            $this->__set('defaultPermissions', 0);

            /* Set the ID */
            $this->id = $roomData;
        }
        elseif (is_array($roomData))
            $this->populateFromArray($roomData); // TODO: test contents
        elseif ($roomData === false)
            $this->id = false;
        else
            throw new Exception('Invalid room data specified -- must either be an associative array corresponding to a table row, a room ID, or false (to create a room, etc.) Passed: ' . print_r($roomData, true));

        $this->roomData = $roomData;
    }


    /* Returns true if and only if $id is a private or off-the-record room ID string, e.g. "o1,2" or "p5,60". The user IDs must be greater than 0 for this to return true. Duplicates are not checked for, though they will be flagged by hasPermission _and_ will be removed by fimRoom->getPrivateRoomMembers(). */
    public static function isPrivateRoomId(string $id) {
        if ($id[0] === 'p' || $id[0] === 'o') {
            $idList = explode(',', substr($id, 1));

            if (count($idList) < 2)
                return false;

            foreach ($idList AS $id) {
                if ((int) $id < 1)
                    return false;

                if (!ctype_digit($id))
                    return false;
            }

            return true;
        }
        else {
            return false;
        }
    }


    /**
     * Packs the ID into hexadecimal, with the ID's number being unchanged, but either 'A' or 'B' being prepended to signify a private/off-the-record room, and with an 'A' character delimiting userIds for a private/otr room.
     * @param $id
     * @return string
     */
    public static function encodeId($id) {
        $packString = '';

        switch ($id[0]) {
            case 'p': $packString .= 'a'; break;
            case 'o': $packString .= 'b'; break;
        }

        if ($packString) {
            $ids = explode(',', substr($id, 1));
            sort($ids, SORT_NUMERIC);

            foreach ($ids AS $id)
                $packString .= "a$id";
        }
        else {
            $packString = $id;
        }

        return pack("H*", $packString);
    }


    public static function decodeId($id) {
        $unpackString = '';

        $decoded = unpack("H*", $id);

        switch ($decoded[0]) {
            case 'a': $unpackString .= 'p'; break;
            case 'b': $unpackString .= 'o'; break;
        }

        if ($unpackString) {
            return $unpackString . str_replace(',', 'a', substr($decoded, 1));
        }
        else
            return $decoded;
    }


    /**
     * Returns true if the room type is a general room, or false otherwise.
     * @return bool
     */
    public function isGeneralRoom() {
        return $this->type === 'general';
    }

    /**
     * Returns true if the room type is a private or OTR room, or false otherwise.
     * @return bool
     */
    public function isPrivateRoom() {
        return $this->type === 'private' || $this->type === 'otr';
    }


    /**
     * Returns true if the room has valid data in the database, or should otherwise be treated as "existing" (e.g. is a private room).
     * TODO: caching
     */
    public function roomExists() {
        global $database;

        return (count($database->getRooms([
            'roomIds' => $this->id,
        ])->getAsArray(false)) > 0);
    }


    /**
     * Returns an array of the user IDs who are part of the private/otr room. It does not check to see if they exist.
     * @return array
     * @throws Exception
     */
    public function getPrivateRoomMemberIds() {
        if ($this->type !== 'private' && $this->type !== 'otr')
            throw new Exception('Call to fimRoom->getPrivateRoomMemberIds only supported on instances of a private room.');

        return array_unique(explode(',', substr($this->id, 1)));
    }

    /**
     * Returns an array of fimUser objects corresponding with those returned by getPrivateRoomMemberIds(), though only valid members (those that exist in the database) will be returned. Thus, the count of this may differ from the count of getPrivateRoomMemberIds(), and this fact can be used to check if the room exists solely of valid members.
     *
     * @return fimUser[]
     * @throws Exception
     */
    public function getPrivateRoomMembers() : array {
        global $database;

        if ($this->type !== 'private' && $this->type !== 'otr')
            throw new Exception('Call to fimRoom->getPrivateRoomMembersNames only supported on instances of a private room.');

        return $database->getUsers(array(
            'userIds' => $this->getPrivateRoomMemberIds(),
        ))->getAsUsers();
    }


    /**
     * Returns the object's property, fetching from the database or cache if needed.
     *
     * @param $property
     * @return mixed
     * @throws Exception
     */
    public function __get($property) {
        if ($this->id && !in_array($property, $this->resolved)) {
            if ($property === 'encodeId') {
                $this->__set('encodedId', $this->encodeId($this->id));
            }

            else {
                if ($this->isPrivateRoom()) {
                    switch ($property) {
                    case 'name':
                        $userNames = [];
                        foreach($this->getPrivateRoomMembers() AS $user)
                            $userNames[] = $user->name;

                        $name = ($this->type === 'private' ? 'Private' : 'Off-the-Record') . ' Room Between ' . fim_naturalLanguageJoin(', ', $userNames);
                        $this->__set('name', $name);
                        return $name;
                        break;

                    default:
                        throw new Exception("Unhandled property '$property' in fimRoom.");
                        break;
                    }
                }

                else {
                    // Find selection group
                    if (isset(array_flip(fimRoom::$roomDataConversion)[$property])) {
                        $needle = array_flip(fimRoom::$roomDataConversion)[$property];
                        $selectionGroup = array_values(array_filter($this->roomDataPullGroups, function ($var) use ($needle) {
                            return strpos($var, $needle) !== false;
                        }))[0];

                        if ($selectionGroup)
                            $this->getColumns(explode(',', $selectionGroup));
                        else
                            throw new Exception("Selection group not found for '$property'");
                    }
                }
            }
        }

        return $this->$property;
    }


    /**
     * Sets the object's property, applying filters and config preferences.
     *
     * @param $property
     * @param $value
     * @throws fimError
     */
    public function __set($property, $value)
    {
        global $config;


        if (property_exists($this, $property))
            $this->{$property} = $value;
        else
            throw new fimError("fimRoomBadProperty", "fimRoom does not have property '$property'");


        // If we've already processed the value once, it generally won't need to be reprocessed. Permissions, for instance, may be altered intentionally. We do make an exception for setting integers to what should be arrays -- we will reprocess in this case.
        if (!in_array($property, $this->resolved) ||
            (($property === 'parentalFlags' || $property === 'watchedBy')
                && gettype($value) === 'integer')
        ) {
            $this->resolved[] = $property;


            // Parental Flags: Convert CSV to Array, or empty if disabled
            if ($property === 'parentalFlags') {
                if ($config['parentalEnabled']) $this->parentalFlags = fim_emptyExplode(',', $value);
                else                            $this->parentalFlags = array();
            }


            // Parental Age: Disable if feature is disabled.
            else if ($property === 'parentalAge' && !$config['parentalEnabled']) {
                $this->parentalAge = 0;
            }

            elseif ($property === 'watchedBy') {
                if (!$config['enableWatchRooms'])
                    $this->watchedBy = [];

                elseif ($value === fimDatabase::decodeError) {
                    // TODO: regenerate and cache to APC
                    global $database;
                    $this->watchedBy = $database->getWatchRoomUsers($this->id);

                    $database->update($database->sqlPrefix . "rooms", [
                        "watchedBy" => $this->watchedBy
                    ], [
                        "roomId" => $this->id,
                    ]);
                }

                elseif ($value === fimDatabase::decodeExpired) {
                    global $database;
                    $this->watchedBy = $database->getWatchRoomUsers($this->id);

                    $database->update($database->sqlPrefix . "rooms", [
                        "watchedBy" => $this->watchedBy
                    ], [
                        "roomId" => $this->id,
                    ]);
                }
            }


            else if ($property === 'topic' && $config['disableTopic']) {
                $this->topic = '';
            }


            else if ($property === 'options') {
                $this->deleted  = ($this->options & ROOM_DELETED);
                $this->archived = ($this->options & ROOM_ARCHIVED);
                $this->official = ($this->options & ROOM_OFFICIAL) && $config['officialRooms'];
                $this->hidden   = ($this->options & ROOM_HIDDEN) && $config['hiddenRooms'];
            }

            else if ($property === 'id' && $this->isPrivateRoom()) {

            }
        }
    }


    /**
     * Resolves an array of database columns. This can be called preemptively to prevent fimRoom from making multiple database calls to resolve itself.
     *
     * @param array $columns
     * @return bool
     * @throws Exception
     */
    private function getColumns(array $columns) : bool
    {
        global $database;

        if ($this->isPrivateRoom())
            throw new Exception('Can\'t call fimRoom->getColumns on private room.');
        elseif (count($columns) > 0)
            return $this->populateFromArray($database->where(array('roomId' => $this->id))->select(array($database->sqlPrefix . 'rooms' => array_merge(array('roomId'), $columns)))->getAsArray(false));
        else
            return true;
    }

    private function mapDatabaseProperty($property) {
        if (!isset(array_flip(fimRoom::$roomDataConversion)[$property]))
            throw new Exception("Unable to map database property '$property'");
        else
            return array_flip(fimRoom::$roomDataConversion)[$property];
    }


    /**
     * Resolves an array of database properties. It will not resolve already-resolved properties.
     *
     * @param array $columns
     * @return bool
     * @throws Exception
     */
    public function resolve($properties) {
        return $this->getColumns(array_map(array($this, 'mapDatabaseProperty'), array_diff($properties, $this->resolved)));
    }

    /**
     * Resolves the entirety of the fimRoom object. It will not resolve already-resolved properties.
     *
     * @return bool
     * @throws Exception
     */
    public function resolveAll() {
        return $this->getColumns(array_map(array($this, 'mapDatabaseProperty'), array_diff(array_values(fimRoom::$roomDataConversion), $this->resolved)));
    }


    /**
     * Resolves properties based on the passed data, presumably from either the database or a cache.
     *
     * @param array $roomData
     * @param bool $dbNameMapping
     * @return bool
     * @throws fimError
     */
    private function populateFromArray(array $roomData, $dbNameMapping = true): bool {
        if ($roomData) {
//            $this->resolved = array_diff($this->resolved, array_values($roomData)); // The resolution process in __set modifies the data based from an array in several ways. As a result, if we're importing from an array a second time, we either need to ignore the new value or, as in this case, uncheck the resolve[] entries to have them reparsed when __set fires.

            foreach ($roomData AS $attribute => $value) {
                if (!$dbNameMapping) {
                    $this->__set($attribute, $value);
                }
                elseif (!isset(fimRoom::$roomDataConversion[$attribute]))
                    trigger_error("fimRoom was passed a roomData array containing '$attribute', which is unsupported.", E_USER_ERROR);
                else {
                    $this->__set(fimRoom::$roomDataConversion[$attribute], $value);
                }
            }

            return true;
        }
        else {
            return false;
        }
    }


    /* TODO: move to DB, I think? */
    public function changeTopic($topic) {
        global $config, $database;

        if ($this->isPrivateRoom())
            throw new Exception('Can\'t call fimRoom->changeTopic on private room.');
        elseif ($config['disableTopic'])
            throw new fimError('topicsDisabled', 'Topics are disabled on this server.');
        else {
            $database->createRoomEvent('topicChange', $this->id, $topic); // name, roomId, message
            $database->update($database->sqlPrefix . "rooms", array(
                'roomTopic' => $topic,
            ), array(
                'roomId' => $this->id,
            ));
        }
    }


    public function getPermissionsArray($field) {
        $permArray = [
          'post' => ROOM_PERMISSION_POST,
          'view' => ROOM_PERMISSION_VIEW,
          'topic' => ROOM_PERMISSION_TOPIC,
          'moderate' => ROOM_PERMISSION_MODERATE,
          'properties' => ROOM_PERMISSION_PROPERTIES,
          'grant' => ROOM_PERMISSION_GRANT,
          'own' => ROOM_PERMISSION_VIEW
        ];

        $returnArray = [];

        foreach($permArray AS $perm => $bit) {
            $returnArray[$perm] = (bool) ($field & $bit);
        }

        return $returnArray;
    }


    /**
     * Modify or create a room.
     *
     * @param $roomParameters - The room's data.
     * @param $dbNameMapping - Set this to true if $databaseFields is using column names (e.g. roomParentalAge) instead of class property names (e.g. parentalAge)
     *
     * @return bool|resource
     */
    public function setDatabase(array $roomParameters)
    {
        global $database;

        if ($this->isPrivateRoom())
            throw new Exception('Can\'t call fimRoom->setDatabase on private rooms.');

        fim_removeNullValues($roomParameters);
        $this->populateFromArray($roomParameters, true);

        if ($this->id) {
            $database->startTransaction();

            if ($existingRoomData = $database->getRooms([
                'roomIds' => [$this->id],
                'columns' => explode(', ', $database->roomHistoryColumns), // TODO: uh... shouldn't roomHistoryColumns be array?
            ])->getAsArray(false)) {
                $database->insert($database->sqlPrefix . "roomHistory", [
                    'roomId' => $this->id,
                    'roomName' => $existingRoomData['roomName'],
                    'roomTopic' => $existingRoomData['roomTopic'],
                    'options' => (int) $existingRoomData['options'],
                    'ownerId' => (int) $existingRoomData['ownerId'],
                    'defaultPermissions' => (int) $existingRoomData['defaultPermissions'],
                    'roomParentalFlags' => $existingRoomData['roomParentalFlags'],
                    'roomParentalAge' => (int) $existingRoomData['roomParentalAge'],
                ]);

                $return = $database->update($database->sqlPrefix . "rooms", $roomParameters, array(
                    'roomId' => $this->id,
                ));
            }

            $database->endTransaction();

            return $return;
        }

        else {
            $database->insert($database->sqlPrefix . "rooms", $roomParameters);
            return $this->id = $database->insertId;
        }
    }
}
?>