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
 * Class fimUser
 * Stores user data.
 */
class fimUser
{
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
    private $sessionHash;

    /**
     * @var string The user's client code.
     * @todo: remove
     */
    private $clientCode;


    /**
     * @var int The user's anonymous user ID.
     * @todo: prevent caching?
     */
    private $anonId;


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
    private $name = "MISSINGno.";


    /**
     * @var array The list of social group IDs the user belongs to.
     */
    private $socialGroupIds;

    /**
     * @var int The primary group the user belongs to, mainly for integration purposes.
     */
    private $mainGroupId;

    /**
     * @var array The list of parental flags the user is blocking.
     */
    private $parentalFlags = [];

    /**
     * @var int The age cutoff of content the user wishes to see.
     */
    private $parentalAge = 0;

    /**
     * @var int The priviledges the user has (as bitfield)
     */
    private $privs = 0;

    /**
     * @var int The last time the user's data was synced with an integration service.
     */
    private $lastSync;

    /**
     * @var string The user's avatar; URL.
     */
    private $avatar;

    /**
     * @var string The user's name formatting; CSS.
     */
    private $nameFormat;

    /**
     * @var string The default message formatting applied to the user's messages; CSS.
     */
    private $messageFormatting;

    /**
     * @var int The room the user would like to be loaded by default.
     */
    private $defaultRoomId = 1;

    /**
     * @var string The user's profile page; URL.
     */
    private $profile;

    /**
     * @var int A bitfield of options the user has set.
     */
    private $options;

    /**
     * @var string The user's email address.
     */
    private $email;

    /**
     * @var int The date the user joined the integration service (possibly the date they created a messenger account, if vanilla logins).
     */
    private $joinDate;

    /**
     * @var int The user's birthdate, for content settings.
     */
    private $birthDate = 0;

    /**
     * @var array An integer list of rooms the user has favourited.
     * TODO: evaluate performance of making list of fimRoom objects
     */
    private $favRooms = [];

    /**
     * @var array An integer list of rooms the user is watching (wants to know when new messages are made in).
     * TODO: evaluate performance of making list of fimRoom objects
     */
    private $watchRooms = [];

    /**
     * @var array An integer list of users the user is ignoring (doesn't want private messages from).
     * TODO: evaluate performance of making list of fimUser objects
     */
    private $ignoredUsers = [];

    /**
     * @var array An integer list of users the user is friends with.
     * TODO: evaluate performance of making list of fimUser objects
     */
    private $friendedUsers = [];

    /**
     * @var string The user's password, hashed. Only in vanilla logins.
     */
    private $passwordHash;

    /**
     * @var string The user's password's salt. Only in vanilla logins.
     */
    private $passwordSalt;

    /**
     * @var string The user's password hasing algorithm. Only in vanilla logins.
     */
    private $passwordFormat;

    /**
     * @var string The last time the user's password was changed. Only in vanilla logins.
     */
    private $passwordLastReset;

    /**
     * @var string If the user's password must be changed immediately. Usually only in vanilla logins.
     */
    private $passwordResetNow = false;

    /**
     * @var int The number of files the user has uploaded.
     */
    private $fileCount;

    /**
     * @var int The number of rooms the user has created.
     */
    private $ownedRooms;

    /**
     * @var int The number of messages the user has posted.
     */
    private $messageCount;

    /**
     * @var string The total size of the files the user has uploaded.
     */
    private $fileSize;

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
    private $resolved = array();

    /**
     * @var array User data fields that should be resolved together when a resolution is needed.
     */
    private static $userDataPullGroups = array(
        'id,name,privs,lastSync',
        'mainGroupId,socialGroupIds,parentalFlags,parentalAge,birthDate',
        'joinDate,messageFormatting,profile,avatar,nameFormat',
        'options,defaultRoomId',
        'passwordHash,passwordFormat',
        'fileCount,fileSize',
        'favRooms,watchRooms,ignoredUsers,friendedUsers',
        'email'
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
        global $loginConfig, $integrationDatabase;

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


    /*
     * The first time data is loaded,
     */
    public function __set($property, $value)
    {
        global $config, $loginConfig, $database, $generalCache;

        if (property_exists($this, $property))
            $this->{$property} = $value;
        else
            throw new Exception("fimUser does not have property '$property'");

        // If we've already processed the value once, it generally won't need to be reprocessed. Permissions, for instance, may be altered intentionally. We do make an exception for setting integers to what should be arrays -- we will reprocess in this case.
        if (!in_array($property, $this->resolved)) {
            $this->resolved[] = $property;


            // Parental Flags: Convert CSV to Array, or empty if disabled
            if ($property === 'parentalFlags') {
                if ($config['parentalEnabled']) $this->parentalFlags = fim_emptyExplode(',', $value);
                else                            $this->parentalFlags = array();
            }


            // Parental Age: Disable if feature is disabled.
            elseif ($property === 'parentalAge') {
                if ($config['parentalEnabled']) $this->parentalAge = $value;
                else                            $this->parentalAge = 255;
            }


            // Room and user lists
            elseif ($property === 'socialGroupIds' || $property === 'favRooms' || $property === 'watchRooms' || $property === 'friendsList' || $property === 'ignoreList') {
                if (!$config['enableWatchRooms'] && $property === 'watchRooms')
                    $this->watchRooms = [];

                /* The returned value was "incomplete," indicating that it was truncated by the database software.
                 * We check to see if it exists in a database list cache (Redis), and then invoke the database wrapper's relevant method to retrieve it from the full table.
                 * Performance note: I would argue that performing the Redis check first will typically be faster, but that would require a fairly large rearchitecture of the User class. */
                elseif ($value === fimDatabase::decodeError) {
                    $cacheIndex = 'fim_' . $property . '_' . $this->id;

                    if ($generalCache->exists($cacheIndex, 'redis')) {
                        throw new Exception('Redis activated.');

                        $this->{$property} = $generalCache->get($cacheIndex, 'redis');
                    }
                    else {
                        $this->{$property} = call_user_func([$database, 'getUser' . ucfirst($property)], $this->id);

                        throw new Exception('User data corrupted: ' . $cacheIndex . '; fallback refused. (Note: this error is for development purposes. A fallback is available, we\'re just not using it. Recovery data found as: ' + print_r($this->{$property}, true));

                        $generalCache->setAdd($cacheIndex, $this->{$property});
                    }
                }

                elseif (!is_array($value))
                    throw new Exception( "The following list was passed as something other than an array to fimUser:" . $property);
            }


            // Privileges: modify based on global permissions, inconsistencies, and superuser status.
            elseif ($property === 'privs') {
                // If certain features are disabled, remove user priviledges. The bitfields should be maintained, however, for when a feature is reenabled.
                if (!$config['userRoomCreation'])
                    $this->privs &= ~fimUser::USER_PRIV_CREATE_ROOMS;
                if (!$config['userPrivateRoomCreation'])
                    $this->privs &= ~(fimUser::USER_PRIV_PRIVATE_ALL | fimUser::USER_PRIV_PRIVATE_FRIENDS); // Note: does not disable the usage of existing private rooms. Use "privateRoomsEnabled" for this.
                if ($config['disableTopic'])
                    $this->privs &= ~fimUser::USER_PRIV_TOPIC; // Topics are disabled (in fact, this one should also disable the returning of topics; TODO).

                // Certain bits imply other bits. Make sure that these are consistent.
                if ($this->privs & fimUser::USER_PRIV_PRIVATE_ALL)
                    $this->privs |= fimUser::USER_PRIV_PRIVATE_FRIENDS;

                // Superuser override (note that any user with GRANT or in the $config superuser array is automatically given all permissions, and is marked as protected. The only way, normally, to remove a user's GRANT status, because they are automatically protected, is to do so directly in the database.)
                // LoginConfig is not guranteed to be set here (e.g. during installation), which is why we cast.
                if (in_array($this->id, (array) $loginConfig['superUsers']) || ($this->privs & fimUser::ADMIN_GRANT))
                    $this->privs = 0x7FFFFFFF;
                elseif ($this->privs & fimUser::ADMIN_ROOMS)
                    $this->privs |= (fimUser::USER_PRIV_VIEW | fimUser::USER_PRIV_POST | fimUser::USER_PRIV_TOPIC); // Being a super-moderator grants a user the ability to view, post, and make topic changes in all rooms.
            }

            elseif ($property === 'avatar') {
                if (!$this->defaultRoomId) $this->defaultRoomId = $config['avatarDefault'];
            }

            elseif ($property === 'defaultRoomId') {
                if (!$this->defaultRoomId) $this->defaultRoomId = $config['defaultRoomId'];
            }
        }
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
        global $config;
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
            case 'editOwnPosts':   return $config['usersCanEditOwnPosts'] && !$this->isAnonymousUser();   break;
            case 'deleteOwnPosts': return $config['usersCanDeleteOwnPosts'] && !$this->isAnonymousUser(); break;

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
     * @param $password The password to check against.
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

            case 'vbmd5':
                if (!isset($this->passwordHash, $this->passwordSalt))
                    throw new Exception('User object was not generated with password hash information.');
                else
                    return ($this->passwordHash === md5(md5($password) . $this->passwordSalt));
            break;

            case 'plaintext':
                return ($this->__get('passwordHash') === $password);
            break;

            case 'phpbb':
                return strlen($password) > 0 && $this->phpbb_check_hash($password, $this->__get('passwordHash'));
            break;

            default:
                throw new Exception('Invalid password format: ' . $this->passwordFormat);
            break;
        }
    }

    function phpbb_hash_crypt_private($password, $setting, &$itoa64) {
        $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $output = '*';

        // Check for correct hash
        if (substr($setting, 0, 3) != '$H$') {
            return $output;
        }

        $count_log2 = strpos($itoa64, $setting[3]);

        if ($count_log2 < 7 || $count_log2 > 30) {
            return $output;
        }

        $count = 1 << $count_log2;
        $salt = substr($setting, 4, 8);

        if (strlen($salt) != 8) {
            return $output;
        }

        $hash = md5($salt . $password, true);
        do {
            $hash = md5($hash . $password, true);
        } while (--$count);

        $output = substr($setting, 0, 12);
        $output .= phpbb_hash_encode64($hash, 16, $itoa64);

        return $output;
    }

    /**
     * Database auth plug-in for phpBB3
     *
     * Authentication plug-ins is largely down to Sergey Kanareykin, our thanks to him.
     *
     * This is for authentication via the integrated user table
     *
     * @package login
     * @version $Id$
     * @copyright (c) 2005 phpBB Group
     * @license http://opensource.org/licenses/gpl-license.php GNU Public License
     *
     */
    function phpbb_hash_encode64($input, $count, &$itoa64) {
        $output = '';
        $i = 0;

        do {
            $value = ord($input[$i++]);
            $output .= $itoa64[$value & 0x3f];

            if ($i < $count) {
                $value |= ord($input[$i]) << 8;
            }

            $output .= $itoa64[($value >> 6) & 0x3f];

            if ($i++ >= $count) {
                break;
            }

            if ($i < $count) {
                $value |= ord($input[$i]) << 16;
            }

            $output .= $itoa64[($value >> 12) & 0x3f];

            if ($i++ >= $count) {
                break;
            }

            $output .= $itoa64[($value >> 18) & 0x3f];
        } while ($i < $count);

        return $output;
    }


    /**
     * Database auth plug-in for phpBB3
     *
     * Authentication plug-ins is largely down to Sergey Kanareykin, our thanks to him.
     *
     * This is for authentication via the integrated user table
     *
     * @package login
     * @version $Id$
     * @copyright (c) 2005 phpBB Group
     * @license http://opensource.org/licenses/gpl-license.php GNU Public License
     *
     */
    function phpbb_check_hash($password, $hash) {
        if (strlen($hash) == 34) {
            return ($this->phpbb_hash_crypt_private($password, $hash, $itoa64) === $hash) ? true : false;
        }

        return (md5($password) === $hash) ? true : false;
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
    private function getColumns(array $columns) : bool
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
            // The resolution process in __set modifies the data based from an array in several ways. As a result, if we're importing from an array a second time, we either need to ignore the new value or, as in this case, uncheck the resolve[] entries to have them reparsed when __set fires.
            if ($overwrite) $this->resolved = array_diff($this->resolved, array_keys($userData));

            foreach ($userData AS $attribute => $value) {
                $this->__set($attribute, $value);
            }

            return true;
        }
        else {
            return false;
        }
    }


    /**
     * Resolves the list of properties from the database.
     *
     * @param $properties list of properties ot resolve
     */
    public function resolve(array $properties) {
        return $this->getColumns(array_diff($properties, $this->resolved));
    }


    /**
     * Resolves all user data from the database.
     */
    public function resolveAll() {
        return $this->resolve(['id', 'name', 'mainGroupId', 'options', 'joinDate', 'birthDate', 'email', 'lastSync', 'passwordHash', 'passwordFormat', 'passwordResetNow', 'passwordLastReset', 'avatar', 'profile', 'nameFormat', 'defaultRoomId', 'messageFormatting', 'privs', 'fileCount', 'fileSize', 'ownedRooms', 'messageCount', 'favRooms', 'watchRooms', 'ignoredUsers', 'friendedUsers', 'socialGroupIds', 'parentalFlags', 'parentalAge']);
    }


    /* Do I even remember what this was going to be for? Not really. */
    public function syncUser()
    {
        global $database, $config;

        if ($this->lastSync >= (time() - $config['userSyncThreshold'])) { // This updates various caches every so often. In general, it is a rather slow process, and as such does tend to take a rather long time (that is, compared to normal - it won't exceed 500 miliseconds, really).
            $database->updateUserCaches($this); // TODO
        }
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
        global $database, $config;

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
                'privs' => $config['defaultUserPrivs']
            ), $databaseFields);

            $return = $database->insert($database->sqlPrefix . "users", $databaseFields);

            $this->id = $database->getLastInsertId();

            return $return;
        }
    }

    public function __destruct() {
        if ($this->id !== 0) {
            if (function_exists('apc_store'))
                apc_store('fim_fimUser_' . $this->id, $this, 500);
            elseif (function_exists('apcu_store'))
                apcu_store('fim_fimUser_' . $this->id, $this, 500);
        }
    }
}

require('fimUserFactory.php');


/* Run Seperate Queries for Integration Methods
 * TODO: These should, long term, probably be plugins.
 * TODO: vB and PHPBB both broken. */
/*switch ($loginConfig['method']) {
  case 'vbulletin3': case 'vbulletin4':
  $userDataForums = $integrationDatabase->select(
    array(
      $sqlUserTable => array(
        'joindate' => 'joinDate',
        'posts' => 'posts',
        'usertitle' => 'userTitle',
        'lastvisit' => 'lastVisit',
        $sqlUserTableCols['userId'] => 'userId',
      ),
    ),
    array('both' => array('userId' => $this->in(array_keys($users))))
  )->getAsArray('userId');
  break;

  case 'phpbb':
  $userDataForums = $integrationDatabase->select(
    array(
      $sqlUserTable => array(
        'user_posts' => 'posts',
        'user_regdate' => 'joinDate',
        $sqlUserTableCols['userId'] => 'userId',
      ),
    ),
    array(
      array('both' => array('userId' => $this->in(array_keys($users))))
    )
  )->getAsArray('userId');
  break;

  case 'vanilla':
    $userDataForums = array(
      'joinDate' => $user['joinDate'],
      'posts' => false,
    );
  break;

  default:
  $userDataForums = array();
  break;
}*/
?>