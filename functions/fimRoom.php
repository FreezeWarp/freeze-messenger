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


require_once('fimDynamicObject.php');

/**
 * Class fimRoom
 * The data for a room object.
 */
class fimRoom extends fimDynamicObject {

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

    /*
     * The room is a general room.
     */
    const ROOM_TYPE_GENERAL = 'general';



    /**
     * When a private room behaves normally.
     */
    const PRIVATE_ROOM_STATE_NORMAL = 'normal';

    /**
     * When a private room has members who are not allowed to private message eachother.
     */
    const PRIVATE_ROOM_STATE_READONLY = 'readOnly';

    /**
     * When a private room is outright disabled, and should not be readable even by its member users.
     * This is typically the state of the room when one member does not exist, or private rooms are disabled entirely.
     */
    const PRIVATE_ROOM_STATE_DISABLED = 'disabled';


    
    /**
     * @var mixed The ID of the room.
     */
    public $id = 0;

    /**
     * @var string The name of the room.
     */
    protected $name = "Missingname.";

        /**
     * @var int A bitfield containing options for delete, official, hidden, and archive.
     */
    protected $options;

    /**
     * @var bool Whether or not the room is deleted.
     */
    protected $deleted;

    /**
     * @var bool Whether or not the room is "official" and should be displayed with special prominence (typically, they are stickied).
     */
    protected $official;

    /**
     * @var bool Whether or not the room is hidden and won't be shown in a room search except to admins (but will otherwise be accessible).
     */
    protected $hidden;

    /**
     * @var bool Whether or not the room is archived and can't be posted in.
     */
    protected $archived;

    /**
     * @var bool The ID of the owner of the room. TODO: make user object?
     */
    protected $ownerId = 0;

    /**
     * @var string The topic of the room.
     */
    protected $topic;

    /**
     * @var string The room's type, e.g. "private"
     */
    protected $type = 'unknown';

    /**
     * @var bool The state of the private room, based on its members and global configuration. (Basically, we're caching it, since it's an O(n^3) operation to check, and requires finding all allowed users.)
     */
    protected $privateRoomState = null;

    /**
     * @var array An array of parental flags applied to the room.
     */
    protected $parentalFlags = [];

    /**
     * @var int The room's parental age (that is, the age a user should be to participate in a room).
     */
    protected $parentalAge = 0;

    /**
     * @var int The default permission bitfield for the room.
     */
    protected $defaultPermissions;

    /**
     * @var int The ID of the last message that was posted in this room.
     */
    protected $lastMessageId;

    /**
     * @var int The time of the last message that was posted in this room.
     */
    protected $lastMessageTime;

    /**
     * @var int The number of messages that have been posted in this room.
     */
    protected $messageCount;

    /**
     * @var array A list of flags for this room. TODO: what are they for?
     */
    protected $flags;

    /**
     * @var string The room's ID encoded for binary storage.
     */
    protected $encodedId;

    /**
     * @var array A list of users watching this room. TODO: user instances?
     */
    protected $watchedByUsers = [];

    /**
     * @var array An array of censor words applied to this room, to aid in caching.
     */
    protected $censorWordsArray;

    /**
     * @var mixed The room data the room was initialised with.
     */
    protected $roomData;


    /**
     * @var array A map of string permissions to their bits in a bitfield.
     */
    public static $permArray = [
        'post' => fimRoom::ROOM_PERMISSION_POST,
        'view' => fimRoom::ROOM_PERMISSION_VIEW,
        'changeTopic' => fimRoom::ROOM_PERMISSION_TOPIC,
        'moderate' => fimRoom::ROOM_PERMISSION_MODERATE,
        'properties' => fimRoom::ROOM_PERMISSION_PROPERTIES,
        'grant' => fimRoom::ROOM_PERMISSION_GRANT,
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
     * @param $roomData mixed Should either be an array or an integer (other values will simply fail to populate the object's data). If an array, should correspond with a row obtained from the `rooms` database, if an integer should correspond with the room ID.
     */
    function __construct($roomData) {
        if (is_int($roomData))
            $this->set('id', $roomData);

        elseif (is_string($roomData) && $this->isPrivateRoomId($roomData))
            $this->set('id', $roomData);

        elseif (is_array($roomData))
            $this->populateFromArray($roomData); // TODO: test contents

        elseif ($roomData === false)
            $this->id = false;

        else
            throw new Exception('Invalid room data specified -- must either be an associative array corresponding to a table row, a room ID, or false (to create a room, etc.) Passed: ' . print_r($roomData, true));

        $this->roomData = $roomData;
    }


    /**
     * Returns true iif $id is a private or off-the-record room ID string, e.g. "o1,2" or "p5,60". The user IDs must be greater than 0 for this to return true. Duplicates are not checked for, though they will be flagged by hasPermission _and_ will be removed by fimRoom->getPrivateRoomMembers(). (Well, almost: the first two entries are checked for duplication. If they are duplicates, the string is rejected. This ensures that at least two non-duplicate IDs exist.)
     *
     * @param string $id The ID string to check.
     *
     * @return bool True if the ID string can be evaluated as a private room ID string, false otherwise.
     */
    public static function isPrivateRoomId(string $id) {
        if (strlen($id) == 0) {
            return false;
        }

        elseif ($id[0] === 'p' || $id[0] === 'o') {
            $idList = explode(',', substr($id, 1));

            if (count($idList) < 2)
                return false;

            elseif ($idList[0] == $idList[1]) // This checks to see if the first two entries are duplicates. This does not check all duplicates, merely ensuring that there are at least two non-duplicate entries (and rejecting the string if the very first two entries are duplicates).
                return false;

            else {
                foreach ($idList AS $id) {
                    if ((int) $id < 1)
                        return false;
                    elseif (!ctype_digit($id))
                        return false;
                }
            }

            return true;
        }

        else {
            return false;
        }
    }


    /**
     * Packs the ID into hexadecimal, using the following scheme:
     * - if multiple "userIds" in the ID, convert them all into a series of base-15 numbers. If only a single roomId, convert it into base-15.
     * - if private room, prepend "f". If off-the-record room, prepend "ff".
     * - append each of our ids in turn, with 'f' appended at the end.
     *
     * For instance, "p1,2,100" is encoded at 0xF1F2F6AF. "o1,2,100" is encoded as 0xFF1F2F6AF. "100" is encoded as 0x6AF.
     *
     * @param $id string The roomId to encode.
     * @return string The roomId encoded.
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


    /**
     * Decodes a packed roomId into its string representation by {@link fimRoom::encodeId()}.
     *
     * @param $id string The roomId encoded.
     * @return string The roomId decoded, e.g. "12" or "p1,3"
     */
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
        return $this->type === fimRoom::ROOM_TYPE_GENERAL;
    }

    /**
     * Returns true if the room type is a private or OTR room, or false otherwise.
     * @return bool
     */
    public function isPrivateRoom() {
        return $this->type === fimRoom::ROOM_TYPE_PRIVATE || $this->type === fimRoom::ROOM_TYPE_OTR;
    }


    /**
     * @link fimDynamicObject::exists()
     */
    public function exists() : bool {
        global $database;

        return $this->exists = ($this->exists || ($this->isPrivateRoom() ? $this->arePrivateRoomMembersValid() : (count($database->getRooms([
            'roomIds' => $this->id,
        ])->getAsArray(false)) > 0)));
    }


    /**
     * Returns an array of the user IDs who are part of the private/otr room. It does not check to see if they exist.
     * @return array
     * @throws Exception
     * TODO: move into fimRoomPrivate class
     */
    public function getPrivateRoomMemberIds() {
        if (!$this->isPrivateRoom())
            throw new Exception('Call to fimRoom->getPrivateRoomMemberIds only supported on instances of a private room.');

        return array_unique(explode(',', substr($this->id, 1)));
    }

    /**
     * Returns an array of fimUser objects corresponding with those returned by getPrivateRoomMemberIds(), though only valid members (those that exist in the database) will be returned. Thus, the count of this may differ from the count of getPrivateRoomMemberIds(), and this fact can be used to check if the room exists solely of valid members.
     *
     * @return fimUser[]
     * @throws Exception
     * TODO: move into fimRoomPrivate class
     */
    public function getPrivateRoomMembers() : array {
        if (!$this->isPrivateRoom())
            throw new Exception('Call to fimRoom->getPrivateRoomMembersNames only supported on instances of a private room.');

        $users = [];
        foreach ($this->getPrivateRoomMemberIds() AS $userId) {
            $user = fimUserFactory::getFromId($userId);

            if ($user->exists()) {
                $users[] = $user;
            }
        }

        return $users;
    }

    /**
     * @return bool
     * @throws Exception If the room is not a private room.
     * TODO: move into fimRoomPrivate class
     */
    public function arePrivateRoomMembersValid() : bool {
        if (!$this->isPrivateRoom())
            throw new Exception('Call to fimRoom->getPrivateRoomMembersNames only supported on instances of a private room.');

        return (count($this->getPrivateRoomMembers()) === count($this->getPrivateRoomMemberIds()));
    }

    /**
     * Determines whether all private room members are allowed to private message all other members, based on ignore/friends lists and each user's privacy settings.
     *
     * Performance wise, this is typically O(n^3), although:
     ** the first two ns will be fairly small (10 is max) -- this is the two foreach loops.
     ** the third n may be bigger -- this is the in_array(, friended/ignoredUsers)
     *
     * @return bool True if all private room members are allowed to private message all other private room members, false otherwise.
     * @throws Exception If the room is not a private room.
     */
    public function getPrivateRoomState() {
        if (!$this->isPrivateRoom())
            throw new Exception('Call to fimRoom->getPrivateRoomMembersNames only supported on instances of a private room.');


        if ($this->privateRoomState !== null)
            return $this->privateRoomState;

        else if (!$this->arePrivateRoomMembersValid())
            return $this->privateRoomState = fimRoom::PRIVATE_ROOM_STATE_DISABLED;

        else {
            // Disallow OTR rooms if disabled.
            if ($this->type === fimRoom::ROOM_TYPE_OTR && !fimConfig::$otrRoomsEnabled)
                return $this->privateRoomState = fimRoom::PRIVATE_ROOM_STATE_DISABLED;

            // Disallow private rooms if disabled.
            elseif ($this->type === fimRoom::ROOM_TYPE_PRIVATE && !fimConfig::$privateRoomsEnabled)
                return $this->privateRoomState = fimRoom::PRIVATE_ROOM_STATE_DISABLED;

            // Disallow private rooms with too many members.
            elseif (count($this->getPrivateRoomMemberIds()) > fimConfig::$privateRoomMaxUsers)
                return $this->privateRoomState = fimRoom::PRIVATE_ROOM_STATE_DISABLED;

            else {
                $users = $this->getPrivateRoomMembers();

                // This checks for invalid users, as getPrivateRoomMembers() will only return members who exist in the database, while getPrivateRoomMemberIds() returns all ids who were specified when the fimRoom object was created.
                if (count($this->getPrivateRoomMemberIds()) !== count($users))
                    return $this->privateRoomState = fimRoom::PRIVATE_ROOM_STATE_DISABLED;

                else {
                    $roomAllowed = true;

                    /*
                     * Performance wise, this is typically O(n^3), although:
                     ** the first two ns will be fairly small (10 is max) -- this is the two foreach loops.
                     ** the third n may be bigger -- this is the in_array(, friended/ignoredUsers)
                     */
                    foreach ($users AS $user) {
                        if ($user->privacyLevel == fimUser::USER_PRIVACY_BLOCKALL) {
                            $roomAllowed = false;
                            break;
                        }
                        else {
                            foreach ($users AS $otherUser) {
                                if ($otherUser === $user)
                                    continue;

                                // If a user is only allowing friends, block if a non-friended user is present.
                                elseif ($user->privacyLevel == fimUser::USER_PRIVACY_FRIENDSONLY
                                    && !in_array($otherUser->id, $user->friendedUsers)) {
                                    $roomAllowed = false;
                                    break;
                                }

                                // If a user is allowing by default, block only if an ignored user is present.
                                elseif ($user->privacyLevel == fimUser::USER_PRIVACY_ALLOWALL
                                    && in_array($otherUser->id, $user->ignoredUsers)) {
                                    $roomAllowed = false;
                                    break;
                                }
                            }
                        }
                    }

                    if ($roomAllowed)
                        return $this->privateRoomState = fimRoom::PRIVATE_ROOM_STATE_NORMAL;
                    else
                        return $this->privateRoomState = fimRoom::PRIVATE_ROOM_STATE_READONLY;
                }
            }
        }
    }

    public function getEncodedId() {
        return fimRoom::encodeId($this->id);
    }

    /**
     * TODO
     * @return array
     */
    public function getCensorWords() {
        /**
         * @var fimDatabase
         */
        global $slaveDatabase;

        if ($this->censorWordsArray !== null)
            return $this->censorWordsArray;

        return $this->censorWordsArray = $slaveDatabase->getCensorWordsActive($this->id)->getAsArray(true);
    }

    /**
     *
     * @param $text - The text to censor
     * @param null $roomId - The roomID whose rules should be applied. If not specified, the global rules (for, e.g., usernames) will be used
     * @param bool $dontAsk - If true, we won't stop for words that merely trigger confirms
     * @param $matches - This array will fill with all matched words.
     *
     * @return string text with substitutions made.
     */
    public function censorScan($text, $dontAsk = false, &$matches) {
        foreach ($this->getCensorWords() AS $word) {
            if ($dontAsk && $word['severity'] === 'confirm') continue;

            if (stripos($text, $word['word']) !== FALSE) {
                switch ($word['severity']) {
                    // Automatically replaces text
                    case 'replace':
                        $text = str_ireplace($word['word'], $word['param'], $text);
                        break;

                    // Passes the word to $matches, to advise the user to be careful
                    case 'warn':
                        $matches[$word['word']] = $word['param'];
                        break;

                    // Blocks the word, throwing an exception
                    case 'block':
                        new fimError('blockCensor', "The message can not be sent: '{$word['word']}' is not allowed.");
                        break;

                    // Blocks the word, throwing an exception, but can be overwridden with $dontAsk
                    case 'confirm':
                        new fimError('confirmCensor', "The message must be resent because a word may not be allowed: {$word['word']} is discouraged: {$word['param']}.");
                        break;
                }
            }
        }

        return $text;
    }


    /**
     * This is a common getter for all fimRoom parameters.
     * It returns the object's property, fetching from the database or cache if needed.
     * Notably, it exposes all private variables to be publically read.
     *
     * @param $property
     *
     * @return mixed
     * @throws Exception
     */
    public function __get($property) {
        if (!property_exists($this, $property))
            throw new Exception("Invalid property accessed in fimRoom: $property");

        if ($this->id && !in_array($property, $this->resolved)) {
            if ($property === 'deleted' || $property === 'archived' || $property === 'hidden' || $property === 'official') {
                $this->resolveFromPullGroup("options");
            }

            else {
                if ($this->isPrivateRoom()) {
                    switch ($property) {
                        case 'name':
                            $userNames = [];
                            foreach($this->getPrivateRoomMembers() AS $user)
                                $userNames[] = $user->name;

                            $name = ($this->type === fimRoom::ROOM_TYPE_PRIVATE ? 'Private' : 'Off-the-Record') . ' Room Between ' . fim_naturalLanguageJoin(', ', $userNames);
                            $this->set('name', $name);
                            return $name;
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


    protected function setWatchedByUsers($users) {
        if (!fimConfig::$enableWatchRooms)
            return;

        // TODO
        elseif ($users === fimDatabase::decodeError) {
            global $database;
            $this->watchedByUsers = $database->getWatchRoomUsers($this->id);

            $database->update($database->sqlPrefix . "rooms", [
                "watchedByUsers" => $this->watchedByUsers
            ], [
                "id" => $this->id,
            ]);
        }

        elseif ($users === fimDatabase::decodeExpired) {
            global $database;
            $this->watchedByUsers = $database->getWatchRoomUsers($this->id);

            $database->update($database->sqlPrefix . "rooms", [
                "watchedByUsers" => $this->watchedByUsers
            ], [
                "id" => $this->id,
            ]);
        }

        else {
            $this->watchedByUsers = $users;
        }
    }

    protected function setId($id) {
        $this->id = $id;

        if (fimRoom::isPrivateRoomId($id)) {
            if ($id[0] === 'p')
                $this->set('type', fimRoom::ROOM_TYPE_PRIVATE);
            elseif ($id[0] === 'o')
                $this->set('type', fimRoom::ROOM_TYPE_OTR);

            $this->set('options', 0);
            $this->set('parentalAge', 0);
            $this->set('parentalFlags', []);
            $this->set('topic', '');
            $this->set('ownerId', 0);
            $this->set('defaultPermissions', 0);
        }
        else {
            $this->set('type', fimRoom::ROOM_TYPE_GENERAL);
        }
    }


    protected function setOptions($options) {
        $this->options = $options;

        $this->deleted  = ($this->options & fimRoom::ROOM_DELETED);
        $this->archived = ($this->options & fimRoom::ROOM_ARCHIVED);
        $this->official = ($this->options & fimRoom::ROOM_OFFICIAL) && fimConfig::$officialRooms;
        $this->hidden   = ($this->options & fimRoom::ROOM_HIDDEN) && fimConfig::$hiddenRooms;
    }

    protected function setTopic($topic) {
        $this->topic = fimConfig::$disableTopic ? '' : $topic;
    }

    protected function setParentalAge($age) {
        $this->parentalAge = fimConfig::$parentalEnabled ? (int) $age : 0;
    }

    protected function setParentalFlags($parentalFlags) {
        if (fimConfig::$parentalEnabled && is_string($parentalFlags))
            $this->parentalFlags = fim_emptyExplode(',', $parentalFlags);
    }


    /**
     * Resolves an array of database columns. This can be called preemptively to prevent fimRoom from making multiple database calls to resolve itself.
     *
     * @param array $columns
     * @return bool
     * @throws Exception
     */
    protected function getColumns(array $columns) : bool
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
     * @link fimDynamicObject::resolve
     */
    public function resolve(array $properties) {
        if ($this->isPrivateRoom())
            return;

        return $this->getColumns(array_diff($properties, $this->resolved));
    }

    /**
     * Resolves the entirety of the fimRoom object. It will not resolve already-resolved properties.
     *
     * @return bool
     * @throws Exception
     */
    public function resolveAll() {
        return $this->resolve(['id', 'name', 'topic', 'options', 'ownerId', 'defaultPermissions', 'parentalFlags', 'parentalAge', 'lastMessageTime', 'lastMessageId', 'messageCount', 'flags', 'watchedByUsers']);
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


    /* TODO: move to DB, I think? */
    public function changeTopic($topic) {
        global $database;

        if ($this->isPrivateRoom())
            throw new Exception('Can\'t call fimRoom->changeTopic on private room.');
        elseif (fimConfig::$disableTopic)
            throw new fimError('topicsDisabled', 'Topics are disabled on this server.');
        else {
            \Stream\StreamFactory::publish('room_' . $this->id, 'topicChange', [
                'topic' => $topic,
                'time' => time(),
            ]);
        }
    }


    /**
     * Gets a displayable array of permsisions based on a permissions bitfield.
     *
     * @param $field int The bitfield storing the permissions.
     *
     * @return array An associative array corresponding to the permissions user has based on their bitfield. Keys are "view", "post", "moderate", etc., and values are true if the user has the given permission, false otherwise.
     */
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
     * @param $roomParameters array Data to set. These values will be set both in the fimRoom object and in the database.
     *
     * @return mixed The room's ID if inserted, otherwise true if success/false if failure.
     */
    public function setDatabase(array $roomParameters)
    {
        global $database;

        if ($this->isPrivateRoom())
            throw new Exception('Can\'t call fimRoom->setDatabase on private rooms.');

        if (!count($roomParameters))
            return;

        fim_removeNullValues($roomParameters);
        $this->populateFromArray($roomParameters);

        if ($this->id) {
            $database->startTransaction();

            if ($existingRoomData = $database->getRooms([
                'roomIds' => [$this->id],
                'columns' => explode(', ', $database->roomHistoryColumns), // TODO: uh... shouldn't roomHistoryColumns be array?
            ])->getAsArray(false)) {
                $database->insert($database->sqlPrefix . "roomHistory", fim_arrayFilterKeys(fim_removeNullValues($existingRoomData), ['id', 'name', 'topic', 'options', 'ownerId', 'defaultPermissions', 'parentalFlags', 'parentalAge']));

                $return = $database->update($database->sqlPrefix . "rooms", array_merge(fim_arrayFilterKeys((array)$this, ['name', 'topic', 'options', 'defaultPermissions', 'parentalFlags', 'parentalAge']), $roomParameters), array(
                    'id' => $this->id,
                ));

                if (isset($roomParameters['defaultPermissions'])) {
                    $database->deletePermissionsCache($this->id);
                }
            }

            $database->endTransaction();

            return $return;
        }

        else {
            $database->insert($database->sqlPrefix . "rooms", $roomParameters);
            return $this->id = $database->getLastInsertId();
        }
    }
}

require_once('fimRoomFactory.php');
?>