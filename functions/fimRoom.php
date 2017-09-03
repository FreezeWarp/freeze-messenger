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


/**
 * Class fimRoom
 * The data for a room object.
 */
class fimRoom {

    /**
     * The room is official, and will receive special prominence in room searches.
     */
    const ROOM_OFFICIAL = 1;

    /**
     * The room is deleted, and cannot be viewed (except by admins) or posted in.
     */
    const ROOM_DELETED = 4;

    /**
     * The room is hidden, and will not be shown in room searches.
     */
    const ROOM_HIDDEN = 8;

    /**
     * The room is archived, and cannot be posted in, but can still be viewed.
     */
    const ROOM_ARCHIVED = 16;


    /**
     * The room can be viewed by the user.
     */
    const ROOM_PERMISSION_VIEW = 1;

    /**
     * The room can be posted in by the user.
     */
    const ROOM_PERMISSION_POST = 2;

    /**
     * The room's topic can be changed by the user.
     */
    const ROOM_PERMISSION_TOPIC = 4;

    /**
     * The room can be moderated by the user, e.g. posts can be deleted.
     */
    const ROOM_PERMISSION_MODERATE = 8;

    /**
     * The room's properties can be altered by the user, e.g. the room's censors can be changed.
     */
    const ROOM_PERMISSION_PROPERTIES = 16;

    /**
     * The room's permissions for users and groups can be changed by the user. This includes, for instance, making other users moderators.
     */
    const ROOM_PERMISSION_GRANT = 128;


    /**
     * The room is private between two or more people.
     */
    const ROOM_TYPE_PRIVATE = 'private';

    /**
     * The room is off-the-record between two or more people.
     */
    const ROOM_TYPE_OTR = 'otr';

    
    /**
     * @var mixed The ID of the room.
     */
    public $id = 0;

    /**
     * @var string The name of the room.
     */
    private $name = "Missingname.";

    /**
     * @var int A bitfield containing options for delete, official, hidden, and archive.
     */
    private $options;

    /**
     * @var bool Whether or not the room is deleted.
     */
    private $deleted;

    /**
     * @var bool Whether or not the room is "official" and should be displayed with special prominence (typically, they are stickied).
     */
    private $official;

    /**
     * @var bool Whether or not the room is hidden and won't be shown in a room search except to admins (but will otherwise be accessible).
     */
    private $hidden;

    /**
     * @var bool Whether or not the room is archived and can't be posted in.
     */
    private $archived;

    /**
     * @var bool The ID of the owner of the room. TODO: make user object.
     */
    private $ownerId;

    /**
     * @var string The topic of the room.
     */
    private $topic;

    /**
     * @var string The room's type, e.g. "private"
     */
    private $type = 'unknown';

    /**
     * @var array An array of parental flags applied to the room.
     */
    private $parentalFlags;

    /**
     * @var int The room's parental age (that is, the age a user should be to participate in a room).
     */
    private $parentalAge;

    /**
     * @var int The default permission bitfield for the room.
     */
    private $defaultPermissions;

    /**
     * @var int The ID of the last message that was posted in this room.
     */
    private $lastMessageId;

    /**
     * @var int The time of the last message that was posted in this room.
     */
    private $lastMessageTime;

    /**
     * @var int The number of messages that have been posted in this room.
     */
    private $messageCount;

    /**
     * @var array A list of flags for this room. TODO: what are they for?
     */
    private $flags;

    /**
     * @var string The room's ID encoded for binary storage.
     */
    private $encodedId;

    /**
     * @var array A list of users watching this room. TODO: user instances?
     */
    private $watchedByUsers;

    /**
     * @var mixed The room data the room was initialised with.
     */
    protected $roomData;

    /**
     * @var array The parameters that have been resolved for this instance of fimRoom. If an unresolved parameter is accessed, it will be resolved.
     */
    private $resolved = array();


    /**
     * @var array A map of string permissions to their bits in a bitfield.
     */
    public static $permArray = [
        'post' => fimRoom::ROOM_PERMISSION_POST,
        'view' => fimRoom::ROOM_PERMISSION_VIEW,
        'topic' => fimRoom::ROOM_PERMISSION_TOPIC,
        'moderate' => fimRoom::ROOM_PERMISSION_MODERATE,
        'properties' => fimRoom::ROOM_PERMISSION_PROPERTIES,
        'grant' => fimRoom::ROOM_PERMISSION_GRANT,
        'own' => 255,
    ];

    /**
     * @var array A mapping between fimRoom's parameters and their column names in the database.
     * TODO: Eventually, we'll hopefully rename everything in the DB itself, but that'd be too time-consuming right now.
     */

    /**
     * @var array When one column in one of these arrays is resolved, the rest will be as well.
     */
    private static $roomDataPullGroups = array(
        ['id','name'],
        ['defaultPermissions','options','ownerId'],
        ['parentalFlags','parentalAge','topic'],
        ['lastMessageTime','lastMessageId','messageCount','flags'],
        ['watchedByUsers']
    );

    /**
     * @var fimCache Our cache instance.
     */
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
            case 'p': $packString .= 'f'; break;
            case 'o': $packString .= 'ff'; break;
        }

        if ($packString) {
            $ids = explode(',', substr($id, 1));
            sort($ids, SORT_NUMERIC);

            foreach ($ids AS $id)
                $packString .= base_convert($id, 10, 15) . 'f';
        }
        else {
            $packString = base_convert($id, 10, 15) . 'f';
        }

        return pack("H*", $packString);
    }


    public static function decodeId($id) {
        $unpackString = '';

        $decoded = rtrim(unpack("H*", $id)[1], '0');

        if (isset($decoded[0])) {
            if ($decoded[0] === 'f') {
                if ($decoded[1] === 'f')
                    $unpackString .= 'o';
                else
                    $unpackString .= 'p';

                $array = array_map(function($value) {
                    return base_convert($value, 15, 10);
                }, explode('f', trim($decoded, 'f')));

                return $unpackString . implode(',', $array);
            }
            else {
                return base_convert(rtrim($decoded, 'f'), 15, 10);
            }
        }

        return "";
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

        return $this->isPrivateRoom() || (count($database->getRooms([
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
        global $config;

        if (!property_exists($this, $property))
            throw new Exception("Invalid property accessed in fimRoom: $property");

        if ($this->id && !in_array($property, $this->resolved)) {
            if ($property === 'encodeId') {
                $this->__set('encodedId', $this->encodeId($this->id));
            }

            elseif ($property === 'watchedByUsers' && !$config['enableWatchRooms']) {
                $this->__set('watchedByUsers', []);
            }

            elseif ($property === 'deleted' || $property === 'archived' || $property === 'hidden' || $property === 'official') {
                $this->resolveFromPullGroup("options");
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
                    $this->resolveFromPullGroup($property);
                }
            }
        }

        return $this->{$property};
    }


    /**
     * Sets the object's property, applying filters and config preferences.
     *
     * @param $property
     * @param $value
     */
    public function __set($property, $value)
    {
        global $config;


        if (property_exists($this, $property))
            $this->{$property} = $value;
        else
            throw new Exception("fimRoom does not have property '$property'");


        // If we've already processed the value once, it generally won't need to be reprocessed. Permissions, for instance, may be altered intentionally. We do make an exception for setting integers to what should be arrays -- we will reprocess in this case.
        if (!in_array($property, $this->resolved) ||
            (($property === 'parentalFlags' || $property === 'watchedByUsers')
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

            elseif ($property === 'watchedByUsers') {
                if (!$config['enableWatchRooms'])
                    $this->watchedByUsers = [];

                elseif ($value === fimDatabase::decodeError) {
                    // TODO: regenerate and cache to APC
                    global $database;
                    $this->watchedByUsers = $database->getWatchRoomUsers($this->id);

                    $database->update($database->sqlPrefix . "rooms", [
                        "watchedByUsers" => $this->watchedByUsers
                    ], [
                        "id" => $this->id,
                    ]);
                }

                elseif ($value === fimDatabase::decodeExpired) {
                    global $database;
                    $this->watchedByUsers = $database->getWatchRoomUsers($this->id);

                    $database->update($database->sqlPrefix . "rooms", [
                        "watchedByUsers" => $this->watchedByUsers
                    ], [
                        "id" => $this->id,
                    ]);
                }
            }


            else if ($property === 'topic' && $config['disableTopic']) {
                $this->topic = '';
            }


            else if ($property === 'options') {
                $this->deleted  = ($this->options & fimRoom::ROOM_DELETED);
                $this->archived = ($this->options & fimRoom::ROOM_ARCHIVED);
                $this->official = ($this->options & fimRoom::ROOM_OFFICIAL) && $config['officialRooms'];
                $this->hidden   = ($this->options & fimRoom::ROOM_HIDDEN) && $config['hiddenRooms'];
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
            return $this->populateFromArray($database->where(array('id' => $this->id))->select(array($database->sqlPrefix . 'rooms' => array_merge(array('id'), $columns)))->getAsArray(false));
        else
            return true;
    }


    /**
     * Resolves an array of database properties. It will not resolve already-resolved properties.
     *
     * @param array $columns
     * @return bool
     * @throws Exception
     */
    public function resolve($properties) {
        return $this->getColumns(array_diff($properties, $this->resolved));
    }

    /**
     * Resolves the entirety of the fimRoom object. It will not resolve already-resolved properties.
     *
     * @return bool
     * @throws Exception
     */
    public function resolveAll() {
        return $this->resolve('id', 'name', 'topic', 'options', 'ownerId', 'defaultPermissions', 'parentalFlags', 'parentalAge', 'lastMessageTime', 'lastMessageId', 'messageCount', 'flags', 'watchedByUsers');
    }


    /**
     * Resolves the needle property and all similar properties.
     *
     * @param $needle
     *
     * @throws Exception If matching pullgroup not found.
     */
    public function resolveFromPullGroup($needle) {
        $groupPointer = false;

        foreach (fimRoom::$roomDataPullGroups AS $group) {
            //var_dump(["get-1", $needle, $group, in_array($needle, $group)]);
            if (in_array($needle, $group)) {
                $groupPointer =& $group;
                break;
            }
        }

        if ($groupPointer) {// var_dump(["get", $groupPointer]);
            $this->resolve($groupPointer);
        }
        else
            throw new Exception("Selection group not found for '$needle'");
    }


    /**
     * Resolves properties based on the passed data, presumably from either the database or a cache.
     *
     * @param array $roomData
     * @param bool $dbNameMapping
     * @return bool
     * @throws fimError
     */
    private function populateFromArray(array $roomData, bool $overwrite = false): bool {
        if ($roomData) {
             // The resolution process in __set modifies the data based from an array in several ways. As a result, if we're importing from an array a second time, we either need to ignore the new value or, as in this case, uncheck the resolve[] entries to have them reparsed when __set fires.
            if ($overwrite) $this->resolved = array_diff($this->resolved, array_keys($roomData));

            foreach ($roomData AS $attribute => $value) {
                $this->__set($attribute, $value);
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
                'topic' => $topic,
            ), array(
                'id' => $this->id,
            ));
        }
    }


    public function getPermissionsArray($field) {
        $returnArray = [];

        foreach(fimRoom::$permArray AS $perm => $bit) {
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
        $this->populateFromArray($roomParameters);

        if ($this->id) {
            $database->startTransaction();

            if ($existingRoomData = $database->getRooms([
                'roomIds' => [$this->id],
                'columns' => explode(', ', $database->roomHistoryColumns), // TODO: uh... shouldn't roomHistoryColumns be array?
            ])->getAsArray(false)) {
                $database->insert($database->sqlPrefix . "roomHistory", fim_arrayFilterKeys($existingRoomData, ['id', 'name', 'topic', 'options', 'ownerId', 'defaultPermissions', 'parentalFlags', 'parentalAge']));

                $return = $database->update($database->sqlPrefix . "rooms", $roomParameters, array(
                    'id' => $this->id,
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


    public function __destruct() {
        if ($this->id !== 0) {
            if (function_exists('apc_store'))
                apc_store('fim_fimRoom_' . $this->id, $this, 500);
            else if (function_exists('apcu_store'))
                apcu_store('fim_fimRoom_' . $this->id, $this, 500);
        }
    }
}
?>