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
    private $alias;
    private $options;
    private $deleted;
    private $official;
    private $hidden;
    private $archived;
    private $ownerId;
    private $topic;
    private $type;
    private $parentalFlags;
    private $parentalAge;
    private $defaultPermissions;
    private $lastMessageId;
    private $lastMessageTime;
    private $messageCount;
    private $flags;

    protected $roomData;

    private $resolved = array();
    private $roomDataConversion = array( // Eventually, we'll hopefully rename everything in the DB itself, but that'd be too time-consuming right now.
        'roomId' => 'id',
        'roomName' => 'name',
        'roomAlias' => 'alias',
        'options' => 'options',
        'ownerId' => 'ownerId',
        'roomTopic' => 'topic',
        'roomType' => 'type',
        'roomParentalFlags' => 'parentalFlags',
        'roomParentalAge' => 'parentalAge',
        'defaultPermissions' => 'defaultPermissions',
        'lastMessageTime' => 'lastMessageTime',
        'lastMessageId' => 'lastMessageTime',
        'messageCount' => 'messageCount',
        'flags' => 'flags'
    );

    private $roomDataPullGroups = array(
        'roomId, roomName',
        'roomType, defaultPermissions, options',
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


        if (is_int($roomData))
            $this->id = $roomData;
        elseif (is_array($roomData))
            $this->populateFromArray($roomData); // TODO: test contents
        elseif ($roomData === false)
            $this->id = false;
        else throw new Exception('Invalid room data specified -- must either be an associative array corresponding to a table row, a room ID, or false (to create a room, etc.) Passed: ' . print_r($roomData, true));

        $this->roomData = $roomData;
    }


    public function __get($property) {
        if ($this->id && !in_array($property, $this->resolved)) {
            // Find selection group
            if (isset(array_flip($this->roomDataConversion)[$property])) {
                $needle = array_flip($this->roomDataConversion)[$property];
                $selectionGroup = array_values(array_filter($this->roomDataPullGroups, function ($var) use ($needle) {
                    return strpos($var, $needle) !== false;
                }))[0];

                if ($selectionGroup)
                    $this->getColumns(explode(',', $selectionGroup));
                else
                    throw new Exception("Selection group not found for '$property'");
            }
        }

        return $this->$property;
    }


    public function __set($property, $value)
    {
        global $config;

        if (property_exists($this, $property))
            $this->{$property} = $value;
        else
            throw new fimError("fimRoomBadProperty", "fimRoom does not have property '$property'");
        // If we've already processed the value once, it generally won't need to be reprocessed. Permissions, for instance, may be altered intentionally. We do make an exception for setting integers to what should be arrays -- we will reprocess in this case.
        if (!in_array($property, $this->resolved) ||
            (($property === 'parentalFlags' || $property === 'socialGroupIds')
                && gettype($value) === 'integer')
        ) {
            $this->resolved[] = $property;


            // Parental Flags: Convert CSV to Array, or empty if disabled
            if ($property === 'parentalFlags') {
                if ($config['parentalEnabled']) $this->parentalFlags = (is_array($value) ? $value : explode(',', $value));
                else                            $this->parentalFlags = array();
            }


            // Parental Age: Disable if feature is disabled.
            else if ($property === 'parentalAge') {
                if ($config['parentalEnabled']) $this->parentalAge = $value;
                else                            $this->parentalAge = 255;
            }


            else if ($property === 'topic' && $config['disableTopic']) {
                $this->topic = "";
            }


            else if ($property === 'options') {
                $this->deleted  = ($this->options & ROOM_DELETED);
                $this->archived = ($this->options & ROOM_ARCHIVED);
                $this->official = ($this->options & ROOM_OFFICIAL) && $config['officialRooms'];
                $this->hidden   = ($this->options & ROOM_HIDDEN) && $config['hiddenRooms'];
            }
        }
    }

    private function getColumns(array $columns) : bool
    {
        global $database;

        if (count($columns) > 0)
            return $this->populateFromArray($database->where(array('roomId' => $this->id))->select(array($database->sqlPrefix . 'rooms' => array_merge(array('roomId'), $columns)))->getAsArray(false));
        else
            return true;
    }

    private function mapDatabaseProperty($property) {
        if (!isset(array_flip($this->roomDataConversion)[$property]))
            throw new Exception("Unable to map database property '$property'");
        else
            return array_flip($this->roomDataConversion)[$property];
    }


    public function resolve($properties) {
        return $this->getColumns(array_map(array($this, 'mapDatabaseProperty'), array_diff($properties, $this->resolved)));
    }

    public function resolveAll() {
        return $this->getColumns(array_map(array($this, 'mapDatabaseProperty'), array_diff(array_values($this->roomDataConversion), $this->resolved)));
    }


    private function populateFromArray(array $roomData, $dbNameMapping = true): bool {
        if ($roomData) {
//            $this->resolved = array_diff($this->resolved, array_values($roomData)); // The resolution process in __set modifies the data based from an array in several ways. As a result, if we're importing from an array a second time, we either need to ignore the new value or, as in this case, uncheck the resolve[] entries to have them reparsed when __set fires.

            foreach ($roomData AS $attribute => $value) {
                if (!$dbNameMapping) {
                    $this->__set($attribute, $value);
                }
                elseif (!isset($this->roomDataConversion[$attribute]))
                    trigger_error("fimRoom was passed a roomData array containing '$attribute', which is unsupported.", E_USER_ERROR);
                else {
                    $this->__set($this->roomDataConversion[$attribute], $value);
                }
            }

            return true;
        }
        else {
            return false;
        }
    }


    /* TODO: move to DB */
    public function changeTopic($topic) {
        global $config, $database;
        if ($config['disableTopic']) {
            throw new fimError('topicsDisabled', 'Topics are disabled on this server.');
        }
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