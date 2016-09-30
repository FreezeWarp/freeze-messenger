<?php

class fimUser
{
    public $id;
    private $name;
    private $socialGroupIds;
    private $mainGroupId;
    private $parentalFlags;
    private $parentalAge;
    private $privs;
    private $lastSync;
    private $avatar;
    private $userNameFormat;
    private $messageFormatting;
    private $defaultRoomId;
    private $profile;

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
        'userId, userName, privs, lastSync',
        'userGroupId, socialGroupIds, userParentalFlags, userParentalAge',
        'messageFormatting, profile, avatar',
        'options, defaultRoomId',
    );


    /**
     * @param $roomData mixed Should either be an array or an integer (other values will simply fail to populate the object's data). If an array, should correspond with a row obtained from the `rooms` database, if an integer should correspond with the room ID.
     */
    public function __construct($userData)
    {
        global $generalCache;
        $this->generalCache = $generalCache;

        $this->avatar = $this->generalCache->getConfig('avatarDefault');
        $this->userNameFormat = $this->generalCache->getConfig('userNameFormat');
        $this->defaultRoomId = $this->generalCache->getConfig('defaultRoomId');



        if (is_int($userData))
            $this->id = $userData;

        elseif (is_array($userData))
            $this->populateFromArray($userData); // TODO: test contents

        elseif ($userData === false)
            $this->id = false;

        else
            throw new Exception('Invalid user data specified -- must either be an associative array corresponding to a table row, a user ID, or false (to create a user, etc.)');

        $this->userData = $userData;
    }


    public function __get($property) {
        global $database;

        if (!in_array($property, $this->resolved)) {
            if (!$this->id) throw new Exception('Uninitialised user object.');

            // Find selection group

            if (isset(array_flip($this->userDataConversion)[$property])) {
                echo $needle = array_flip($this->userDataConversion)[$property];
                $selectionGroup = array_values(array_filter($this->userDataPullGroups, function ($var) use ($needle) {
                    return strpos($var, $needle) !== false;
                }))[0];

//                var_dump($selectionGroup);
//                var_dump($needle); var_dump($property); die();

                $this->populateFromArray($database->where(array('userId' => $this->id))->select(array($database->sqlPrefix . 'users' => $selectionGroup . ', userId'))->getAsArray(false));
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
        $this->{$property} = $value;

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


    private function populateFromArray($userData)
    {
        $this->resolved = array_diff($this->resolved, array_values($userData)); // The resolution process in __set modifies the data based from an array in several ways. As a result, if we're importing from an array a second time, we either need to ignore the new value or, as in this case, uncheck the resolve[] entries to have them reparsed when __set fires.

        foreach ($userData AS $attribute => $value)
            $this->__set($this->userDataConversion[$attribute], $value);
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
     * @param $options - Corresponds mostly with room columns, though the options tag is seperted.
     *
     * @return bool|resource
     */
    public function set($options, $create = false)
    {
        global $database;

        if (isset($options['password'])) {
            require 'PasswordHash.php';
            $h = new PasswordHash(8, FALSE);
            $options['passwordHash'] = $h->HashPassword($options['password']);
            $options['passwordFormat'] = 'phpass';

            unset($options['password']);
        }

        if ($this->id) {
            return $database->upsert($database->sqlPrefix . "users", array(
                'userId' => $this->id,
            ), $options);
        } else {
            return $database->insert($database->sqlPrefix . "users", $options);
        }
    }
}

?>