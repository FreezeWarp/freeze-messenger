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
use Database\DatabaseTypeType;

/**
 * Class fimUser
 * Stores user data.
 */
class fimUser extends fimDynamicObject
{
    /**
     * The user does not wish to enable private messages.
     */
    const USER_PRIVACY_BLOCKALL = "block";

    /**
     * The user only allows private messages from friends.
     */
    const USER_PRIVACY_FRIENDSONLY = "friends";

    /**
     * The user only allows private messages from all users.
     */
    const USER_PRIVACY_ALLOWALL = "allow";


    /**
     * The user may view rooms.
     */
    const USER_PRIV_VIEW = 0x1;

    /**
     * The user may post in rooms.
     */
    const USER_PRIV_POST = 0x2;

    /**
     * The user may change the topic in rooms.
     */
    const USER_PRIV_TOPIC = 0x4;

    /**
     * The user may create rooms.
     */
    const USER_PRIV_CREATE_ROOMS = 0x20;

    /*
     * The user may make private messages to friends.
     */
    const USER_PRIV_PRIVATE_FRIENDS = 0x40;

    /**
     * The user may make private messages to everybody.
     */
    const USER_PRIV_PRIVATE_ALL = 0x80;

    /**
     * The user may view active users.
     */
    const USER_PRIV_ACTIVE_USERS = 0x400;

    /**
     * The user may view post counts.
     */
    const USER_PRIV_POST_COUNTS = 0x800;


    /**
     * The user has administrative grant priviledges, such that they can make other users administrators.
     */
    const ADMIN_GRANT = 0x10000;

    /**
     * The user is protected, and cannot lose permissions through normal means.
     */
    const ADMIN_PROTECTED = 0x20000;

    /**
     * The user may administer rooms.
     */
    const ADMIN_ROOMS = 0x40000;

    /**
     * The user may view private rooms.
     * @todo: remove?
     */
    const ADMIN_VIEW_PRIVATE = 0x80000;

    /**
     * The user may administer users.
     */
    const ADMIN_USERS = 0x100000;

    /**
     * The user may administer files.
     */
    const ADMIN_FILES = 0x400000;

    /**
     * The user may administer the censor.
     */
    const ADMIN_CENSOR = 0x1000000;


    /**
     * The id reserved for anonymous users.
     */
    const ANONYMOUS_USER_ID = -1;

    /**
     * @var string The user's session hash.
     * @todo: remove, shouldn't be cached
     */
    protected $sessionHash;

    /**
     * @var string The user's client code.
     * @todo: remove
     */
    protected $clientCode;


    /**
     * @var int The user's anonymous user ID.
     * @todo: prevent caching?
     */
    protected $anonId;


    /**
     * @var int The user's ID.
     */
    public $id = 0;

    /**
     * @var int The user's integration ID.
     */
    public $integrationId = 0;

    /**
     * @var int The user's integration method.
     */
    public $integrationMethod = false;


    /**
     * @var string The user's name.
     */
    protected $name = "MISSINGno.";


    /**
     * @var array The list of social group IDs the user belongs to.
     */
    protected $socialGroupIds;

    /**
     * @var int The primary group the user belongs to, mainly for integration purposes.
     */
    protected $mainGroupId;

    /**
     * @var array The list of parental flags the user is blocking.
     */
    protected $parentalFlags = [];

    /**
     * @var int The age cutoff of content the user wishes to see.
     */
    protected $parentalAge = 255;

    /**
     * @var int The priviledges the user has (as bitfield)
     */
    protected $privs = 0;

    /**
     * @var int The last time the user's data was synced with an integration service.
     * todo: remove, probably
     */
    protected $lastSync;

    /**
     * @var string The user's avatar; URL.
     */
    protected $avatar;

    /**
     * @var string The user's name formatting; CSS.
     */
    protected $nameFormat;

    /**
     * @var string The default message formatting applied to the user's messages; CSS.
     */
    protected $messageFormatting;

    /**
     * @var int The room the user would like to be loaded by default.
     */
    protected $defaultRoomId = 1;

    /**
     * @var string The user's profile page; URL.
     */
    protected $profile;

    /**
     * @var int A bitfield of options the user has set.
     */
    protected $options;

    /**
     * @var string The user's email address.
     */
    protected $email;

    /**
     * @var int The date the user joined the integration service (possibly the date they created a messenger account, if vanilla logins).
     */
    protected $joinDate;

    /**
     * @var int The user's birthdate, for content settings.
     */
    protected $birthDate = 0;

    /**
     * @var array An integer list of rooms the user has favourited.
     * TODO: evaluate performance of making list of fimRoom objects
     */
    protected $favRooms = [];

    /**
     * @var array An integer list of rooms the user is watching (wants to know when new messages are made in).
     * TODO: evaluate performance of making list of fimRoom objects
     */
    protected $watchRooms = [];

    /**
     * @var array An integer list of users the user is ignoring (doesn't want protected messages from).
     * TODO: evaluate performance of making list of fimUser objects
     */
    protected $ignoredUsers = [];

    /**
     * @var array An integer list of users the user is friends with.
     * TODO: evaluate performance of making list of fimUser objects
     */
    protected $friendedUsers = [];

    /**
     * @var string the user's privacy setting.
     */
    protected $privacyLevel;

    /**
     * @var string The user's password, hashed. Only in vanilla logins.
     */
    protected $passwordHash;

    /**
     * @var string The user's password's salt. Only in vanilla logins.
     */
    protected $passwordSalt;

    /**
     * @var string The user's password hasing algorithm. Only in vanilla logins.
     */
    protected $passwordFormat;

    /**
     * @var string The last time the user's password was changed. Only in vanilla logins.
     */
    protected $passwordLastReset;

    /**
     * @var string If the user's password must be changed immediately. Usually only in vanilla logins.
     */
    protected $passwordResetNow = false;

    /**
     * @var int The number of files the user has uploaded.
     */
    protected $fileCount;

    /**
     * @var int The number of rooms the user has created.
     */
    protected $ownedRooms;

    /**
     * @var int The number of messages the user has posted.
     */
    protected $messageCount;

    /**
     * @var string The total size of the files the user has uploaded.
     */
    protected $fileSize;

    /**
     * @var fimCache The caching object.
     */
    protected $generalCache;

    /**
     * @var array The source userdata.
     * @todo remove?
     */
    protected $userData;

    /**
     * @var array The list of fields that have been resolved on this user object.
     */
    protected $resolved = array();

    /**
     * @var array User data fields that should be resolved together when a resolution is needed.
     */
    private static $userDataPullGroups = array(
        'id,name,privs,lastSync',
        'mainGroupId,socialGroupIds,parentalFlags,parentalAge,birthDate', // Permission flags.
        'joinDate,messageFormatting,profile,avatar,nameFormat',
        'options,defaultRoomId',
        'passwordHash,passwordFormat',
        'fileCount,fileSize',
        'favRooms,watchRooms',
        'privacyLevel,ignoredUsers,friendedUsers',
        'email',
    );


    /**
     * @param $userData mixed Should either be an array or an integer (other values will simply fail to populate the object's data). If an array, should correspond with a row obtained from the `rooms` database, if an integer should correspond with the room ID.
     * @return $this
     */
    public function __construct($userData)
    {
        if (is_int($userData))
            $this->id = $userData;

        elseif (is_array($userData))
            $this->populateFromArray($userData); // TODO: test contents

        elseif ($userData === false || $userData === null)
            $this->id = false;

        else
            throw new Exception('Invalid user data specified -- must either be an associative array corresponding to a table row, a user ID, or false (to create a user, etc.)');

        $this->userData = $userData;
    }


    public function __get($property) {
        global $loginConfig;

        // Return a unique username for every anonymous user.
        if ($this->isAnonymousUser() && $property === 'name')
            return $this->name . $this->anonId;

        if (!property_exists($this, $property))
            throw new Exception("Invalid property accessed in fimUser: $property");

        if ($this->id && !in_array($property, $this->resolved) && $property !== 'anonId') {
            if ($property === 'passwordSalt') {
                throw new Exception('Not yet implemented: fetching passwordSalt from integration database.');
            }

            elseif ($property === 'passwordHash') {
                if ($loginConfig['method'] !== 'vanilla') {
                    throw new Exception('Not yet implemented: fetching passwordHash from integration database.');
                }

                else {
                    $this->getColumns('passwordHash');
                }
            }

            // Find selection group
            else {
                $needle = $property;
                $selectionGroup = array_values(array_filter(fimUser::$userDataPullGroups, function ($var) use ($needle) {
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

    protected function setParentalAge($age) {
        if (fimConfig::$parentalEnabled)
            $this->parentalAge = $age;
    }

    protected function setParentalFlags($flags) {
        if (fimConfig::$parentalEnabled)
            $this->parentalFlags = fim_emptyExplode(',', $flags);
    }

    protected function setSocialGroupIds($socialGroupIds) {
        $this->setList('socialGroupIds', $socialGroupIds);
    }
    protected function setFavRooms($favRooms) {
        $this->setList('favRooms', $favRooms);
    }
    protected function setWatchRooms($watchRooms) {
        if (fimConfig::$enableWatchRooms) {
            $this->setList('watchRooms', $watchRooms);
        }
    }
    protected function setFriendsList($friendsList) {
        $this->setList('friendsList', $friendsList);
    }
    protected function setIgnoreList($ignoreList) {
        $this->setList('ignoredUsers', $ignoreList);
    }

    private function setList($listName, $value) {
        global $database, $generalCache;

        /* The returned value was "incomplete," indicating that it was truncated by the database software.
         * We check to see if it exists in a database list cache (Redis), and then invoke the database wrapper's relevant method to retrieve it from the full table.
         * Performance note: I would argue that performing the Redis check first will typically be faster, but that would require a fairly large rearchitecture of the User class. */
        if ($value === fimDatabase::decodeError) {
            $cacheIndex = 'fim_' . $listName . '_' . $this->id;

            if ($generalCache->exists($cacheIndex, 'redis')) {
                throw new Exception('Redis activated.');

                $this->{$listName} = $generalCache->get($cacheIndex, 'redis');
            }
            else {
                $this->{$listName} = call_user_func([$database, 'getUser' . ucfirst($listName)], $this->id);

                throw new Exception('User data corrupted: ' . $cacheIndex . '; fallback refused. (Note: this error is for development purposes. A fallback is available, we\'re just not using it. Recovery data found as: ' + print_r($this->{$property}, true));

                $generalCache->setAdd($cacheIndex, $this->{$property});
            }
        }

        elseif (!is_array($value))
            throw new Exception( "The following list was passed as something other than an array to fimUser:" . $listName);

        else
            $this->{$listName} = $value;

        sort($this->{$listName});
    }

    public function editList($listName, $ids, $action) {
        global $database;

        // todo: room/user factories that use cached data if available, database otherwise

        $this->resolve([$listName]);

        $tableNames = [
            'favRooms' => 'userFavRooms',
            'watchRooms' => 'watchRooms',
            'ignoredUsers' => 'userIgnoreList',
            'friendedUsers' => 'userFriendsList'
        ];

        if ($listName === 'favRooms' || $listName === 'watchRooms') {
            $items = (count($ids) > 0
                ? $database->getRooms(array(
                    'roomIds' => $ids
                ))->getAsRooms()
                : []);

            $columnName = 'roomId';
        }
        elseif ($listName === 'ignoredUsers' || $listName === 'friendedUsers') {
            $items = (count($ids) > 0
                ? $database->getUsers(array(
                    'userIds' => $ids
                ))->getAsUsers()
                : []);

            $columnName = 'subjectId';
        }
        else {
            throw new Exception('Unknown list.');
        }


        $table = $database->sqlPrefix . $tableNames[$listName];


        if ($action === 'delete') {
            foreach ($items AS $item) {
                $database->delete($table, array(
                    'userId' => $this->id,
                    $columnName => $item->id,
                ));

                if(($key = array_search($item->id, $this->{$listName})) !== false) {
                    unset($this->{$listName}[$key]);
                }
            }
        }

        if ($action === 'edit') {
            foreach ($this->{$listName} AS $id) {
                $database->delete($table, array(
                    'userId' => $this->id,
                    $columnName => $id,
                ));
            }

            $this->{$listName} = [];
        }

        if ($action === 'create' || $action === 'edit') {
            foreach ($items AS $item) {
                // Skip Rooms That The User Doesn't Have Permission To
                if ($listName === 'favRooms' || $listName === 'watchRooms') {
                    if (!($database->hasPermission($this, $item) & fimRoom::ROOM_PERMISSION_VIEW)) {
                        continue;
                    }
                }

                // Update the Database List
                $database->insert($table, array(
                    'userId' => $this->id,
                    $columnName => $item->id,
                ));

                // Update Our Local List
                if (!in_array($item->id, $this->{$listName}))
                    $this->{$listName}[] = $item->id;
            }
        }


        // Sort Our Local List
        sort($this->{$listName});


        // Update the Database List Cache
        $this->setDatabase([
            $listName => $this->{$listName}
        ]);
    }


    protected function setDefaultRoomId($roomId) {
        $this->defaultRoomId = $roomId ?: fimConfig::$defaultRoomId;
    }


    protected function setPrivs($privs) {
        global $loginConfig;

        $this->privs = $privs;

        // If certain features are disabled, remove user privileges. The bitfields should be maintained, however, for when a feature is reenabled.
        if (!fimConfig::$userRoomCreation)
            $this->privs &= ~fimUser::USER_PRIV_CREATE_ROOMS;

        // Superuser override (note that any user with GRANT or in the $config superuser array is automatically given all permissions, and is marked as protected. The only way, normally, to remove a user's GRANT status, because they are automatically protected, is to do so directly in the database.)
        // LoginConfig is not guranteed to be set here (e.g. during installation), which is why we cast.
        if (in_array($this->id, (array) $loginConfig['superUsers']) || ($this->privs & fimUser::ADMIN_GRANT))
            $this->privs = 0x7FFFFFFF;
        elseif ($this->privs & fimUser::ADMIN_ROOMS)
            $this->privs |= (fimUser::USER_PRIV_VIEW | fimUser::USER_PRIV_POST | fimUser::USER_PRIV_TOPIC); // Being a super-moderator grants a user the ability to view, post, and make topic changes in all rooms.

        // Note that we set these after setting admin privs, becuase we don't want admins using these functionalities when they are disabled.
        if (!fimConfig::$userPrivateRoomCreation)
            $this->privs &= ~(fimUser::USER_PRIV_PRIVATE_ALL | fimUser::USER_PRIV_PRIVATE_FRIENDS); // Note: does not disable the usage of existing private rooms. Use "privateRoomsEnabled" for this.
        if (fimConfig::$disableTopic)
            $this->privs &= ~fimUser::USER_PRIV_TOPIC; // Topics are disabled (in fact, this one should also disable the returning of topics; TODO).

        // Certain bits imply other bits. Make sure that these are consistent.
        if ($this->privs & fimUser::USER_PRIV_PRIVATE_ALL)
            $this->privs |= fimUser::USER_PRIV_PRIVATE_FRIENDS;

    }

    public function setAnonId($anonId) {
        if (!$this->isAnonymousUser())
            throw new Exception('Can\'t set anonymous user ID on non-anonymous users.');

        $this->anonId = $anonId;
    }

    public function setSessionHash($hash) {
        $this->sessionHash = $hash;

        $this->resolved[] = 'sessionHash';
    }

    public function setClientCode($code) {
        $this->clientCode = $code;

        $this->resolved[] = 'clientCode';
    }


    public function isValid() {
        return $this->id != 0;
    }

    /**
     * Checks to see if the user has permission to do the specified thing.
     *
     * @param $priv The priviledge to check, one of ['protected', 'modPrivs', 'modRooms', 'modPrivate', 'modUsers', 'modFiles', 'modCensor', 'view', 'post', 'changeTopic', 'createRooms', 'privateRoomsFriends', 'privateRoomsAll', 'roomsOnline', 'postCounts']
     * @return bool True if user has permission, false if not.
     * @throws Exception for unrecognised priviledges
     */
    public function hasPriv(string $priv) : bool
    {
        $privs = $this->__get('privs');

        switch ($priv) {
            /* Admin Privs */
            case 'protected' :  return (bool)($privs & fimUser::ADMIN_PROTECTED);     break; // This the "untouchable" flag; break; but that's more or less all it means.
            case 'modPrivs' :   return (bool)($privs & fimUser::ADMIN_GRANT);         break; // This effectively allows a user to give himself everything else below. It is also used for admin functions that can not be delegated effectively -- such as modifying the site configuration.
            case 'modRooms' :   return (bool)($privs & fimUser::ADMIN_ROOMS);         break; // Alter rooms -- kicking users; break; delete posts; break; and change hidden/official status
            case 'modPrivate' : return (bool)($privs & fimUser::ADMIN_VIEW_PRIVATE);  break; // View private communications.
            case 'modUsers' :   return (bool)($privs & fimUser::ADMIN_USERS);         break; // Site-wide bans; break; mostly.
            case 'modFiles' :   return (bool)($privs & fimUser::ADMIN_FILES);         break; // File Uploads
            case 'modCensor' :  return (bool)($privs & fimUser::ADMIN_CENSOR);        break; // Censor

            /* User Privs */
            case 'view' :                 return (bool)($privs & fimUser::USER_PRIV_VIEW);            break; // Is not banned
            case 'post' :                 return (bool)($privs & fimUser::USER_PRIV_POST);            break;
            case 'changeTopic':           return (bool)($privs & fimUser::USER_PRIV_TOPIC);           break;
            case 'createRooms':           return (bool)($privs & fimUser::USER_PRIV_CREATE_ROOMS);    break; // May create rooms
            case 'privateRoomsFriends':   return (bool)($privs & fimUser::USER_PRIV_PRIVATE_FRIENDS); break; // May create private rooms (friends only)
            case 'privateRoomsAll':       return (bool)($privs & fimUser::USER_PRIV_PRIVATE_ALL);     break; // May create private rooms (anybody)
            case 'roomsOnline':           return (bool)($privs & fimUser::USER_PRIV_ACTIVE_USERS);    break; // May see rooms online.
            case 'postCounts':            return (bool)($privs & fimUser::USER_PRIV_POST_COUNTS);     break; // May see post counts.

            /* Config Aliases
             * (These may become full priviledges in the future.) */
            case 'editOwnPosts':   return fimConfig::$usersCanEditOwnPosts && !$this->isAnonymousUser();   break;
            case 'deleteOwnPosts': return fimConfig::$usersCanDeleteOwnPosts && !$this->isAnonymousUser(); break;

            default: throw new Exception("Invalid priv; $priv"); break;
        }
    }


    public function getPermissionsArray() {
        $privs = array();

        foreach (array('protected', 'modPrivs', 'modRooms', 'modPrivate', 'modUsers', 'modFiles', 'modCensor', 'view', 'post', 'changeTopic', 'createRooms', 'privateRoomsFriends', 'privateRoomsAll', 'roomsOnline', 'postCounts', 'editOwnPosts', 'deleteOwnPosts') AS $priv)
            $privs[$priv] = $this->hasPriv($priv);

        return $privs;
    }

    /**
     * Checks if the plaintext password matches the user's password (generally after some hashing).
     *
     * @param $password string The password to check against.
     * @return bool True if the password match, false otherwise.
     * @throws Exception If the user's passwordFormat is not understood.
     */
    public function checkPasswordAndLockout($password)
    {
        global $database;

        if ($database->lockoutActive()) {
            new fimError('lockoutActive', 'You have attempted to login too many times. Please wait a while and then try again.');
            return false;
        }
        else {
            if ($this->checkPassword($password)) {
                return true;
            }
            else {
                $database->lockoutIncrement();
                return false;
            }
        }
    }

    public function checkPassword($password) {
        switch ($this->passwordFormat) {
            case 'phpass':
                if (!isset($this->passwordHash)) {
                    throw new Exception('User object was not generated with password hash information.');
                }

                else {
                    require_once('PasswordHash.php');
                    $h = new PasswordHash(8, FALSE);
                    return $h->CheckPassword($password, $this->passwordHash);
                }
                break;

            /* Obviously, this method is almost never used. It primarily exists to make testing a little bit easier. */
            case 'plaintext':
                return ($this->__get('passwordHash') === $password);
                break;

            default:
                throw new Exception('Invalid password format: ' . $this->passwordFormat);
                break;
        }
    }


    /**
     * Returns true if the user is an anonymous user, false otherwise.
     *
     * @return bool
     */
    public function isAnonymousUser() : bool
    {
        return ($this->id === self::ANONYMOUS_USER_ID);
    }


    /**
     * Makes sure the user has obtained the specified data columns, obtaining them from cache or database, if not already retrieved
     *
     * @param array $columns
     * @return bool Returns true on success, false on failure.
     * @throws Exception
     */
    protected function getColumns(array $columns) : bool
    {
        global $database;

        if (count($columns) == 0)
            return true;

        elseif ($this->id) // We can fetch data given the user's unique ID.
            return $this->populateFromArray($database->where(['id' => $this->id])->select([$database->sqlPrefix . 'users' => array_merge(['id'], $columns)])->getAsArray(false));

        elseif ($this->integrationId && $this->integrationMethod) // We can fetch data given the user's unique pair of integration ID and integration method.
            return $this->populateFromArray($database->where(['integrationMethod' => $this->integrationMethod, 'integrationId' => $this->integrationId])->select([$database->sqlPrefix . 'users' => array_merge(['integrationId', 'integrationMethod'], $columns)])->getAsArray(false));

        else
            throw new Exception('fimUser does not have uniquely identifying information required to perform database retrieval.');
    }


    /**
     * Populates the user object's parameters based on an associative array obtained from the DB.
     *
     * @param array $userData An array of user data obtained from the database users table.
     * @return bool Returns false if userData is empty, true otherwise.
     */
    public function populateFromArray(array $userData, bool $overwrite = false) : bool
    {
        if ($userData) {
            // The resolution process in set modifies the data based from an array in several ways. As a result, if we're importing from an array a second time, we either need to ignore the new value or, as in this case, uncheck the resolve[] entries to have them reparsed when set fires.
            if ($overwrite) $this->resolved = array_diff($this->resolved, array_keys($userData));

            foreach ($userData AS $attribute => $value) {
                $this->set($attribute, $value);
            }

            return true;
        }
        else {
            return false;
        }
    }


    /**
     * Resolves all user data from the database.
     */
    public function resolveAll() {
        return $this->resolve(['id', 'name', 'options']);
    }


    /**
     * @link fimDynamicObject::exists()
     */
    public function exists() : bool {
        global $database;

        return $this->exists = ($this->exists || (count($database->getSocialGroups([
                    'groupIds' => $this->id,
                ])->getAsArray(false)) > 0));
    }


    /**
     * Modify or create a user.
     * @internal The row will be set to a merge of roomDefaults->existingRow->specifiedOptions.
     *
     * @param $options - Corresponds mostly with user columns
     *
     * @return bool|resource
     */
    public function setDatabase($databaseFields)
    {
        global $database;

        if (isset($databaseFields['password'])) {
            require 'PasswordHash.php';
            $h = new PasswordHash(8, FALSE);
            $databaseFields['passwordHash'] = $h->HashPassword($databaseFields['password']);
            $databaseFields['passwordFormat'] = 'phpass';

            unset($databaseFields['password']);
        }

        $this->populateFromArray($databaseFields, true);
        $databaseFields = fim_dbCastArrayEntry($databaseFields, 'privs', DatabaseTypeType::bitfield);

        if ($this->id) {
            $database->startTransaction();

            if (fim_inArray(array_keys($databaseFields), explode(', ', $database->userHistoryColumns))) {
                if ($existingUserData = $database->getUsers(array(
                    'userIds' => array($this->id),
                    'columns' => $database->userHistoryColumns,
                ))->getAsArray(false)) {
                    $database->insert($database->sqlPrefix . "userHistory", fim_arrayFilterKeys($existingUserData, ['id', 'name', 'nameFormat', 'profile', 'avatar', 'mainGroupId', 'defaultMessageFormatting', 'options', 'parentalAge', 'parentalFlags', 'privs']));
                }
            }

            $return = $database->upsert($database->sqlPrefix . "users", array(
                'id' => $this->id,
            ), $databaseFields);

            $database->endTransaction();

            return $return;
        }

        else {
            $databaseFields = array_merge(array(
                'privs' => fimConfig::$defaultUserPrivs
            ), $databaseFields);

            $return = $database->insert($database->sqlPrefix . "users", $databaseFields);

            $this->id = $database->getLastInsertId();

            return $return;
        }
    }
}
?>