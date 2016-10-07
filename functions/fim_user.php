<?php

class fimUser
{
    const ANONYMOUS_USER_ID = -1;

    public $id;
    private $name;
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
    );

    private $userDataPullGroups = array(
        'userId,userName,privs,lastSync',
        'userGroupId,socialGroupIds,userParentalFlags,userParentalAge',
        'joinDate,messageFormatting,profile,avatar,userNameFormat',
        'options,defaultRoomId',
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
        if (!in_array($property, $this->resolved)) {
            if (!$this->id) throw new Exception('Uninitialised user object.');

            // Find selection group
            if (isset(array_flip($this->userDataConversion)[$property])) {
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
            else if ($property = 'parentalAge') {
                if ($config['parentalEnabled']) $this->parentalAge = $value;
                else                              $this->parentalAge = 255;
            }


            // Priviledges: modify based on global permissions, inconsistencies, and superuser status.
            else if ($property = 'privs') {
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

            else if ($property = 'anonId') {
                if ($this->id === $this->generalCache->getConfig('anonymousUserId')) {
                    $this->name .= $this->anonId;
                }
            }
        }
    }


    function checkPassword($password)
    {
        global $database;

        if ($database->lockoutActive()) {

        }
        else {
            $database->lockoutIncrement();
            /* TODO: IP limit to calling this function. */

            switch ($this->userData['passwordFormat']) {
                case 'phpass':
                    if (!isset($this->userData['passwordHash'])) {
                        throw new Exception('User object was not generated with password hash information.');
                    } else {
                        require_once('PasswordHash.php');

                        $h = new PasswordHash(8, FALSE);

                        return $h->CheckPassword($password, $this->userData['passwordHash']);
                    }
                    break;

                case 'vbmd5':
                    if (!isset($this->userData['passwordHash'], $this->userData['passwordSalt'])) {
                        throw new Exception('User object was not generated with password hash information.');
                    } else {
                        return ($this->userData['passwordHash'] === md5(md5($password) . $this->userData['passwordSalt']));
                    }
                    break;

                case 'raw':
                    return ($this->userData['passwordHash'] === $password);
                    break;

                default:
                    throw new Exception('Invalid password format.');
                    break;
            }

            return false;
        }
    }


    function isAnonymousUser() {
        return ($this->id === self::ANONYMOUS_USER_ID);
    }


    private function getColumns($columns) {
        global $database;

        if (count($columns) > 0)
            return $this->populateFromArray($database->where(array('userId' => $this->id))->select(array($database->sqlPrefix . 'users' => array_merge(array('userId'), $columns)))->getAsArray(false));
        else
            return true;
    }


    private function populateFromArray($userData)
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

            $database->insert($database->sqlPrefix . "usersHistory", $database->getUsers(array(
                'userIds' => array($this->id),
                'columns' => fimDatabase::userHistoryColumns,
            ))->getAsArray(false));

            return $database->upsert($database->sqlPrefix . "users", array(
                'userId' => $this->id,
            ), $databaseFields);

            $database->endTransaction();
        } else {
            $databaseFields = array_merge(array(
                'privs' => $this->generalCache->getConfig('defaultUserPrivs')
            ), $databaseFields);

            return $database->insert($database->sqlPrefix . "users", $databaseFields);
        }
    }
}

?>