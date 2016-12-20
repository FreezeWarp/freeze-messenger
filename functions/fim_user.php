<?php

class fimUser
{
    const ANONYMOUS_USER_ID = -1;

    public $id = 0;
    private $name = "MISSINGno.";
    private $socialGroupIds;
    private $mainGroupId;
    private $parentalFlags;
    private $parentalAge;
    private $privs = 0;
    private $lastSync;
    private $avatar;
    private $userNameFormat;
    private $messageFormatting;
    private $defaultRoomId;
    private $profile;
    private $options;

    private $passwordHash;
    private $passwordSalt;
    private $passwordFormat;

    private $anonId;

    protected $generalCache;
    protected $userData;

    private $resolved = array();
    private $userDataConversion = array(
        'userId' => 'id',
        'userName' => 'name',
        'userNameFormat' => 'userNameFormat',
        'userGroupId' => 'mainGroupId',
        'socialGroupIds' => 'socialGroupIds',
        'userParentalFlags' => 'parentalFlags',
        'userParentalAge' => 'parentalAge',
        'privs' => 'privs',
        'lastSync' => 'lastSync',
        'messageFormatting' => 'messageFormatting',
        'defaultRoomId' => 'defaultRoomId',
        'profile' => 'profile',
        'avatar' => 'avatar',
        'options' => 'options',
        'passwordHash' => 'passwordHash',
        'passwordFormat' => 'passwordFormat',
    );

    private $userDataPullGroups = array(
        'userId,userName,privs,lastSync',
        'userGroupId,socialGroupIds,userParentalFlags,userParentalAge',
        'joinDate,messageFormatting,profile,avatar,userNameFormat',
        'options,defaultRoomId',
        'passwordHash,passwordFormat'
    );


    /**
     * @param $userData mixed Should either be an array or an integer (other values will simply fail to populate the object's data). If an array, should correspond with a row obtained from the `rooms` database, if an integer should correspond with the room ID.
     * @return $this
     */
    public function __construct($userData)
    {
        global $generalCache;
        $this->generalCache = $generalCache;

        $this->avatar = $this->generalCache->getConfig('avatarDefault');
//        $this->userNameFormat = '';
        $this->defaultRoomId = $this->generalCache->getConfig('defaultRoomId');



        if (is_int($userData))
            $this->id = $userData;

        elseif (is_array($userData))
            $this->populateFromArray($userData); // TODO: test contents

        elseif ($userData === false)
            $this->id = false;

        else
            throw new fimError('fimUserInvalidConstruct', 'Invalid user data specified -- must either be an associative array corresponding to a table row, a user ID, or false (to create a user, etc.)');

        $this->userData = $userData;

        return $this;
    }


    public function __get($property) {
        global $loginConfig, $integrationDatabase;

        if ($this->id && !in_array($property, $this->resolved)) {
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
            elseif (isset(array_flip($this->userDataConversion)[$property])) {
                $needle = array_flip($this->userDataConversion)[$property];
                $selectionGroup = array_values(array_filter($this->userDataPullGroups, function ($var) use ($needle) {
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
        global $config, $loginConfig;

        if (property_exists($this, $property))
            $this->{$property} = $value;
        else
            throw new fimError("fimUserBadProperty", "fimUser does not have property '$property'");

        // If we've already processed the value once, it generally won't need to be reprocessed. Permissions, for instance, may be altered intentionally. We do make an exception for setting integers to what should be arrays -- we will reprocess in this case.
        if (!in_array($property, $this->resolved) ||
            (($property === 'parentalFlags' || $property === 'socialGroupIds')
            && gettype($value) === 'integer')
        ) {
            $this->resolved[] = $property;


            // Social Group IDs: Convert CSV to Array
            if ($property = 'socialGroupIds')
                $this->socialGroupIds = explode(',', $value);


            // Parental Flags: Convert CSV to Array, or empty if disabled
            else if ($property === 'parentalFlags') {
                if ($config['parentalEnabled']) $this->parentalFlags = explode(',', $value);
                else                              $this->parentalFlags = array();
            }


            // Parental Age: Disable if feature is disabled.
            else if ($property === 'parentalAge') {
                if ($config['parentalEnabled']) $this->parentalAge = $value;
                else                              $this->parentalAge = 255;
            }


            // Priviledges: modify based on global permissions, inconsistencies, and superuser status.
            else if ($property === 'privs') {
                // If certain features are disabled, remove user priviledges. The bitfields should be maintained, however, for when a feature is reenabled.
                if (!$this->generalCache->getConfig('userRoomCreation'))
                    $this->privs &= ~USER_PRIV_CREATE_ROOMS;
                if (!$this->generalCache->getConfig('userPrivateRoomCreation'))
                    $this->privs &= ~(USER_PRIV_PRIVATE_ALL | USER_PRIV_PRIVATE_FRIENDS); // Note: does not disable the usage of existing private rooms. Use "privateRoomsEnabled" for this.
                if ($this->generalCache->getConfig('disableTopic'))
                    $this->privs &= ~USER_PRIV_TOPIC; // Topics are disabled (in fact, this one should also disable the returning of topics; TODO).

                // Certain bits imply other bits. Make sure that these are consistent.
                if ($this->privs & USER_PRIV_PRIVATE_ALL)
                    $this->privs |= USER_PRIV_PRIVATE_FRIENDS;

                // Superuser override (note that any user with GRANT or in the $config superuser array is automatically given all permissions, and is marked as protected. The only way, normally, to remove a user's GRANT status, because they are automatically protected, is to do so directly in the database.)
                if (in_array($this->id, $loginConfig['superUsers']) || ($this->privs & ADMIN_GRANT))
                    $this->privs = 0x7FFFFFFF;
                elseif ($this->privs & ADMIN_ROOMS)
                    $this->privs |= (USER_PRIV_VIEW | USER_PRIV_POST | USER_PRIV_TOPIC); // Being a super-moderator grants a user the ability to view, post, and make topic changes in all rooms.
            }

            else if ($property === 'anonId') {
                if ($this->id === $this->generalCache->getConfig('anonymousUserId')) {
                    $this->name .= $this->anonId;
                }
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
        global $generalCache;
        $privs = $this->__get('privs');

        switch ($priv) {
            /* Admin Privs */
            case 'protected' :  return (bool)($privs & ADMIN_PROTECTED);     break; // This the "untouchable" flag; break; but that's more or less all it means.
            case 'modPrivs' :   return (bool)($privs & ADMIN_GRANT);         break; // This effectively allows a user to give himself everything else below. It is also used for admin functions that can not be delegated effectively -- such as modifying the site configuration.
            case 'modRooms' :   return (bool)($privs & ADMIN_ROOMS);         break; // Alter rooms -- kicking users; break; delete posts; break; and change hidden/official status
            case 'modPrivate' : return (bool)($privs & ADMIN_VIEW_PRIVATE);  break; // View private communications.
            case 'modUsers' :   return (bool)($privs & ADMIN_USERS);         break; // Site-wide bans; break; mostly.
            case 'modFiles' :   return (bool)($privs & ADMIN_FILES);         break; // File Uploads
            case 'modCensor' :  return (bool)($privs & ADMIN_CENSOR);        break; // Censor

            /* User Privs */
            case 'view' :                 return (bool)($privs & USER_PRIV_VIEW);            break; // Is not banned
            case 'post' :                 return (bool)($privs & USER_PRIV_POST);            break;
            case 'changeTopic':           return (bool)($privs & USER_PRIV_TOPIC);           break;
            case 'createRooms':           return (bool)($privs & USER_PRIV_CREATE_ROOMS);    break; // May create rooms
            case 'privateRoomsFriends':   return (bool)($privs & USER_PRIV_PRIVATE_FRIENDS); break; // May create private rooms (friends only)
            case 'privateRoomsAll':       return (bool)($privs & USER_PRIV_PRIVATE_ALL);     break; // May create private rooms (anybody)
            case 'roomsOnline':           return (bool)($privs & USER_PRIV_ACTIVE_USERS);    break; // May see rooms online.
            case 'postCounts':            return (bool)($privs & USER_PRIV_POST_COUNTS);     break; // May see post counts.

            /* Config Aliases
             * (These may become full priviledges in the future.) */
            case 'editOwnPosts':   return $generalCache->getConfig('usersCanEditOwnPosts') && !$this->isAnonymousUser();   break;
            case 'deleteOwnPosts': return $generalCache->getConfig('usersCanDeleteOwnPosts') && !$this->isAnonymousUser(); break;

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
    public function checkPassword($password)
    {
        global $database;

        if ($database->lockoutActive()) {

        }
        else {
            $database->lockoutIncrement();
            /* TODO: IP limit to calling this function. */

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
                    return ($this->passwordHash === $password);
                    break;

                case 'phpbb':
                    return strlen($password) > 0 && $this->phpbb_check_hash($password, $this->passwordHash);
                    break;

                default:
                    throw new Exception('Invalid password format: ' . $this->passwordFormat);
                    break;
            }

            return false;
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
            return (phpbb_hash_crypt_private($password, $hash, $itoa64) === $hash) ? true : false;
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

        if (count($columns) > 0)
            return $this->populateFromArray($database->where(array('userId' => $this->id))->select(array($database->sqlPrefix . 'users' => array_merge(array('userId'), $columns)))->getAsArray(false));
        else
            return true;
    }


    /**
     * Populates the user object's parameters based on an associative array obtained from the DB.
     *
     * @param array $userData An array of user data obtained from the database users table.
     * @return bool Returns false if userData is empty, true otherwise.
     * @throws fimError
     */
    private function populateFromArray(array $userData) : bool
    {
        if ($userData) {
            $this->resolved = array_diff($this->resolved, array_values($userData)); // The resolution process in __set modifies the data based from an array in several ways. As a result, if we're importing from an array a second time, we either need to ignore the new value or, as in this case, uncheck the resolve[] entries to have them reparsed when __set fires.

            foreach ($userData AS $attribute => $value) {
                if (!isset($this->userDataConversion[$attribute]))
                    trigger_error("fimUser was passed a userData array containing '$attribute', which is unsupported.", E_USER_NOTICE);
                else
                    $this->__set($this->userDataConversion[$attribute], $value);
            }

            return true;
        }
        else {
            return false;
        }
    }


    private function mapDatabaseProperty($property) {
        if (!isset(array_flip($this->userDataConversion)[$property]))
            throw new Exception("Unable to map database property '$property'");
        else
            return array_flip($this->userDataConversion)[$property];
    }


    public function resolve($properties) {
        return $this->getColumns(array_map(array($this, 'mapDatabaseProperty'), array_diff($properties, $this->resolved)));
    }

    public function resolveAll() {
        return $this->getColumns(array_map(array($this, 'mapDatabaseProperty'), array_diff(array_values($this->userDataConversion), $this->resolved)));
    }

    /* Do I even remember what this was going to be for? Not really. */
    public function syncUser()
    {
        global $database;

        if ($this->lastSync >= (time() - $this->generalCache->getConfig('userSyncThreshold'))) { // This updates various caches every so often. In general, it is a rather slow process, and as such does tend to take a rather long time (that is, compared to normal - it won't exceed 500 miliseconds, really).
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
        global $database;

        if (isset($databaseFields['password'])) {
            require 'PasswordHash.php';
            $h = new PasswordHash(8, FALSE);
            $databaseFields['passwordHash'] = $h->HashPassword($databaseFields['password']);
            $databaseFields['passwordFormat'] = 'phpass';

            unset($databaseFields['password']);
        }

        if ($this->id) {
            $database->startTransaction();

            if ($existingUserData = $database->getUsers(array(
                'userIds' => array($this->id),
                'columns' => $database->userHistoryColumns,
            ))->getAsArray(false)) {
                $database->insert($database->sqlPrefix . "usersHistory", $existingUserData);
            }

            $return = $database->upsert($database->sqlPrefix . "users", array(
                'userId' => $this->id,
            ), $databaseFields);

            $database->endTransaction();

            return $return;
        } else {
            $databaseFields = array_merge(array(
                'privs' => $this->generalCache->getConfig('defaultUserPrivs')
            ), $databaseFields);

            return $database->insert($database->sqlPrefix . "users", $databaseFields);
        }
    }
}

?>