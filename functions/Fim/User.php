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

namespace Fim;

use Database\Type\Type;
use Exception;
use Fim\Error;

/**
 * Class Fim\fimUser
 * Stores user data.
 */
class User extends DynamicObject
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

    /**
     * The user may make private messages.
     */
    const USER_PRIV_PRIVATE_ROOMS = 0x80;



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
     * The user may administer emoticons.
     */
    const ADMIN_EMOTICONS = 0x2000000;

    /**
     * @var array A map of string permissions to their bits in a bitfield.
     */
    public static $permArray = [
        'view'           => User::USER_PRIV_VIEW,
        'post'           => User::USER_PRIV_POST,
        'changeTopic'    => User::USER_PRIV_TOPIC,
        'createRooms'    => User::USER_PRIV_CREATE_ROOMS,
        'privateRooms'   => User::USER_PRIV_PRIVATE_ROOMS,
        'modPrivs'       => User::ADMIN_GRANT,
        'protected'      => User::ADMIN_PROTECTED,
        'modRooms'       => User::ADMIN_ROOMS,
        'modUsers'       => User::ADMIN_USERS,
        'modFiles'       => User::ADMIN_FILES,
        'modCensor'      => User::ADMIN_CENSOR,
        'modEmoticons'   => User::ADMIN_EMOTICONS,
    ];


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
     * @var string The user's name, transformed for searchability.
     */
    protected $nameSearchable = "";


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
     * @var string The user's biography line.
     */
    protected $bio;

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
     */
    protected $favRooms = null;

    /**
     * @var array An integer list of users the user is ignoring (doesn't want protected messages from).
     */
    protected $ignoredUsers = null;

    /**
     * @var array An integer list of users the user is friends with.
     */
    protected $friendedUsers = null;

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
     * @var array The list of fields that have been resolved on this user object.
     */
    protected $resolved = [];

    /**
     * @var array User data fields that should be resolved together when a resolution is needed.
     */
    public static $pullGroups = [
        ['id', 'integrationId', 'integrationMethod', 'name', 'privs'],
        ['mainGroupId', 'parentalFlags', 'parentalAge', 'birthDate'], // Permission flags.
        ['email', 'joinDate', 'messageFormatting', 'profile', 'avatar', 'nameFormat', 'bio'],
        ['options', 'defaultRoomId', 'privacyLevel'],
        ['passwordHash', 'passwordFormat'],
        ['fileCount', 'fileSize'],
    ];


    /**
     * @param $userData mixed Should either be an array or an integer (other values will simply fail to populate the object's data). If an array, should correspond with a row obtained from the `rooms` database, if an integer should correspond with the room ID.
     *
     * @return $this
     */
    public function __construct($userData)
    {
        if (is_int($userData))
            $this->id = $userData;

        elseif (is_array($userData)) // TODO: remove/replace with instanceof databaseResult
            $this->populateFromArray($userData); // TODO: test contents

        elseif ($userData === false || $userData === null)
            $this->id = false;

        else
            throw new Exception('Invalid user data specified -- must either be an associative array corresponding to a table row, a user ID, or false (to create a user, etc.)');
    }


    public function __get($property)
    {
        return $this->get($property);
    }


    /**
     * We don't want to resolve this property, so we define a getter for it.
     * @return {@see $anonId}
     */
    public function getAnonId()
    {
        return $this->anonId;
    }

    /**
     * @return array The list of groups this user belongs to.
     */
    public function getSocialGroupIds(): array
    {
        return $this->socialGroupIds =
            ($this->socialGroupIds === null
                ? \Fim\Database::instance()->getUserSocialGroupIds($this->id)
                : $this->socialGroupIds
            );
    }

    /**
     * @return array The list of rooms favourited by this user.
     */
    public function getFavRooms(): array
    {
        return $this->favRooms =
            ($this->favRooms === null
                ? \Fim\Database::instance()->getUserFavRooms($this->id)
                : $this->favRooms
            );
    }

    /**
     * @return array The list of users friended by this user.
     */
    public function getFriendedUsers(): array
    {
        return $this->friendedUsers =
            ($this->friendedUsers === null
                ? \Fim\Database::instance()->getUserFriendsList($this->id)
                : $this->friendedUsers
            );
    }

    /**
     * @return array The list of users ignored by this user.
     */
    public function getIgnoredUsers(): array
    {
        return $this->ignoredUsers =
            ($this->ignoredUsers === null
                ? \Fim\Database::instance()->getUserIgnoreList($this->id)
                : $this->ignoredUsers
            );
    }

    public function editList($listName, $ids, $action)
    {
        /* Flag the Object for Recaching */
        $this->doCache = true;


        /* Define Tables */
        $tableNames = [
            'favRooms'      => 'userFavRooms',
            'ignoredUsers'  => 'userIgnoreList',
            'friendedUsers' => 'userFriendsList'
        ];

        $table = \Fim\Database::$sqlPrefix . $tableNames[$listName];


        /* Get Valid Entries from the Database */
        if ($listName === 'favRooms') {
            $items = (count($ids) > 0
                ? \Fim\Database::instance()->getRooms([
                    'roomIds' => $ids
                ])->getAsRooms()
                : []);

            $columnName = 'roomId';
        }

        elseif ($listName === 'ignoredUsers' || $listName === 'friendedUsers') {
            $items = (count($ids) > 0
                ? \Fim\Database::instance()->getUsers([
                    'userIds' => $ids
                ])->getAsUsers()
                : []);

            $columnName = 'subjectId';
        }

        else {
            throw new Exception('Unknown list.');
        }


        /* Perform Action */
        // Delete Elements for a Delete
        if ($action === 'delete') {
            \Fim\Database::instance()->delete($table, [
                'userId'    => $this->id,
                $columnName => \Fim\Database::instance()->in($ids),
            ]);

            $this->{$listName} = array_diff($this->{$listName}, $ids);
        }

        // Empty the List Before an Edit
        if ($action === 'edit') {
            \Fim\Database::instance()->delete($table, [
                'userId' => $this->id,
            ]);

            $this->{$listName} = [];
        }

        // Add Items for a Create or Edit
        if ($action === 'create' || $action === 'edit') {
            foreach ($items AS $item) {
                // Skip Rooms That The User Doesn't Have Permission To
                if ($listName === 'favRooms' && !(\Fim\Database::instance()->hasPermission($this, $item) & Room::ROOM_PERMISSION_VIEW)) {
                    continue;
                }

                // Update the Database List
                \Fim\Database::instance()->insert($table, [
                    'userId'    => $this->id,
                    $columnName => $item->id,
                ]);

                // Update Our Local List
                if (!in_array($item->id, $this->{$listName}))
                    $this->{$listName}[] = $item->id;
            }
        }


        /* Sort Our Local List */
        if ($this->{$listName})
            sort($this->{$listName});
    }


    /**
     * Set the user priviledges bitfield, disabling and enabling certain bits based on other user data.
     * (For instance, if the user is an admin, they get all priviledges regardless of the bitfield.)
     *
     * @param $privs int The database-stored bitfield.
     */
    protected function setPrivs($privs)
    {
        global $loginConfig;

        $this->privs = $privs;

        // If certain features are disabled, remove user privileges. The bitfields should be maintained, however, for when a feature is reenabled.
        if (!\Fim\Config::$userRoomCreation)
            $this->privs &= ~User::USER_PRIV_CREATE_ROOMS;


        // Superuser override (note that any user with GRANT or in the $config superuser array is automatically given all permissions, and is marked as protected. The only way, normally, to remove a user's GRANT status, because they are automatically protected, is to do so directly in the database.)
        // LoginConfig is not guranteed to be set here (e.g. during installation), which is why we cast.
        if (in_array($this->id, (array)$loginConfig['superUsers'])
            || in_array($this->mainGroupId, (array)$loginConfig['adminGroups'])
            || ($this->privs & User::ADMIN_GRANT))
            $this->privs = 0x7FFFFFFF;

        elseif ($this->privs & User::ADMIN_ROOMS)
            $this->privs |= (User::USER_PRIV_VIEW | User::USER_PRIV_POST | User::USER_PRIV_TOPIC); // Being a super-moderator grants a user the ability to view, post, and make topic changes in all rooms.


        // Note that we set these after setting admin privs, becuase we don't want admins using these functionalities when they are disabled.
        if (!\Fim\Config::$userPrivateRoomCreation)
            $this->privs &= ~(User::USER_PRIV_PRIVATE_ROOMS);

        if (\Fim\Config::$disableTopic)
            $this->privs &= ~User::USER_PRIV_TOPIC; // Topics are disabled (in fact, this one should also disable the returning of topics; TODO).


        // Disable bits based on login-provider disabled features
        $loginRunner = \Login\LoginFactory::getLoginRunnerFromName($loginConfig['method']);
        if (!$loginRunner::isSiteFeatureDisabled('emoticons')) {
            $this->privs &= ~User::ADMIN_EMOTICONS;
        }

    }

    /**
     * Set {@see $anonId}
     */
    public function setAnonId($anonId)
    {
        if (!$this->isAnonymousUser())
            throw new Exception('Can\'t set anonymous user ID on non-anonymous users.');

        $this->anonId = $anonId;
    }

    /**
     * Set {@see $sessionHash}
     */
    public function setSessionHash($hash)
    {
        $this->sessionHash = $hash;

        $this->resolved[] = 'sessionHash';
    }

    /**
     * Set {@see $clientCode}
     */
    public function setClientCode($code)
    {
        $this->clientCode = $code;

        $this->resolved[] = 'clientCode';
    }

    /**
     * Set {@see $defaultRoomId}
     */
    public function setDefaultRoomId($defaultRoomId)
    {
        $this->defaultRoomId = $defaultRoomId ?: \Fim\Config::$defaultRoomId;
    }

    /**
     * Set {@see $parentalAge}
     */
    protected function setParentalAge($age)
    {
        if (\Fim\Config::$parentalEnabled)
            $this->parentalAge = $age;
    }

    /**
     * Set {@see $parentalFlags}
     */
    protected function setParentalFlags($flags)
    {
        if (\Fim\Config::$parentalEnabled)
            $this->parentalFlags = fim_emptyExplode(',', $flags);
    }

    /**
     * Check if this Fim\fimUser object theoretically corresponds with a valid user; use {@see exists()} to determine if a user actually exists.
     *
     * @return bool True if the user is a valid user, false otherwise.
     */
    public function isValid()
    {
        return $this->id != 0;
    }

    /**
     * @link fimDynamicObject::exists()
     */
    public function exists(): bool
    {
        return $this->exists = ($this->exists || (count(\Fim\Database::instance()->getUsers([
                    'userIds' => $this->id,
                ])->getAsArray(false)) > 0));
    }

    /**
     * Checks to see if the user has permission to do the specified thing.
     *
     * @param $priv string The priviledge to check, one of ['protected', 'modPrivs', 'modRooms', 'modUsers', 'modFiles', 'modCensor', 'view', 'post', 'changeTopic', 'createRooms', 'privateRooms']
     *
     * @return bool True if user has permission, false if not.
     * @throws Exception for unrecognised priviledges
     */
    public function hasPriv(string $priv): bool
    {
        $privs = $this->__get('privs');

        switch ($priv) {
            /* Config Aliases
             * (These may become full priviledges in the future.) */
            case 'editOwnPosts':
                return \Fim\Config::$usersCanEditOwnPosts && !$this->isAnonymousUser();
            break;

            case 'deleteOwnPosts':
                return \Fim\Config::$usersCanDeleteOwnPosts && !$this->isAnonymousUser();
            break;

            /* Login Features */
            case 'selfChangeProfile':
            case 'selfChangeAvatar':
            case 'selfChangeParentalAge':
            case 'selfChangeParentalFlags':
            case 'selfChangeFriends':
            case 'selfChangeIgnore':
                if ($this->isAnonymousUser())
                    return false;
                elseif ($loginRunner = \Login\LoginFactory::getLoginRunnerFromName($this->__get('integrationMethod')))
                    return !$loginRunner::isProfileFeatureDisabled($priv);
                else
                    return true;
            break;

            /* Normal Priviledges */
            default:
                if (isset(self::$permArray[$priv]))
                    return ($privs & self::$permArray[$priv]) == self::$permArray[$priv];
                else
                    throw new Exception("Invalid priv; $priv");
            break;
        }
    }


    /**
     * Gets a displayable array of permsisions based on the current user's {@see $privs} field.
     *
     * @return array An associative array corresponding to the permissions user has based on their bitfield. Keys are the keys of {@see Fim\fimUser::$permArray}.
     */
    public function getPermissionsArray(): array
    {
        $returnArray = [];

        foreach (array_merge(array_keys(User::$permArray), ['editOwnPosts', 'deleteOwnPosts', 'selfChangeProfile', 'selfChangeAvatar', 'selfChangeParentalAge', 'selfChangeParentalFlags', 'selfChangeFriends', 'selfChangeIgnore']) AS $perm) {
            $returnArray[$perm] = $this->hasPriv($perm);
        }

        return $returnArray;
    }

    /**
     * Checks if the plaintext password matches the user's password (generally after some hashing).
     *
     * @param $password string The password to check against.
     *
     * @return bool True if the password match, false otherwise.
     * @throws Exception If the user's passwordFormat is not understood.
     */
    public function checkPasswordAndLockout($password): bool
    {
        if (\Fim\Database::instance()->lockoutActive()) {
            new \Fim\Error('lockoutActive', 'You have attempted to login too many times. Please wait a while and then try again.');

            return false;
        }
        else {
            if ($this->checkPassword($password)) {
                return true;
            }
            else {
                \Fim\Database::instance()->lockoutIncrement();

                return false;
            }
        }
    }

    public function checkPassword($password): bool
    {
        switch ($this->__get('passwordFormat')) {
            case 'phpass':
                $h = new \Login\PasswordHash(8, false);

                return $h->CheckPassword($password, $this->__get('passwordHash'));
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
     * @return bool True if the user is an anonymous user, false otherwise.
     */
    public function isAnonymousUser(): bool
    {
        return ((int)$this->id === self::ANONYMOUS_USER_ID);
    }


    /**
     * Makes sure the user has obtained the specified data columns, obtaining them from cache or database, if not already retrieved
     *
     * @param array $columns
     *
     * @return bool Returns true on success, false on failure.
     * @throws Exception
     */
    protected function getColumns(array $columns): bool
    {
        if (count($columns) == 0)
            return true;

        elseif ($this->id) // We can fetch data given the user's unique ID.
            return $this->populateFromArray(\Fim\Database::instance()->where(['id' => $this->id])->select([\Fim\Database::$sqlPrefix . 'users' => array_merge(['id'], $columns)])->getAsArray(false));

        elseif ($this->integrationId && $this->integrationMethod) // We can fetch data given the user's unique pair of integration ID and integration method.
            return $this->populateFromArray(\Fim\Database::instance()->where(['integrationMethod' => $this->integrationMethod, 'integrationId' => $this->integrationId])->select([\Fim\Database::$sqlPrefix . 'users' => array_merge(['integrationId', 'integrationMethod'], $columns)])->getAsArray(false));

        else
            throw new Exception('Fim\fimUser does not have uniquely identifying information required to perform database retrieval.');
    }


    /**
     * Resolves all user data from the database.
     */
    public function resolveAll(): bool
    {
        return $this->resolve(['id', 'name', 'mainGroupId', 'options', 'joinDate', 'birthDate', 'email', 'lastSync', 'passwordHash', 'passwordFormat', 'passwordResetNow', 'passwordLastReset', 'avatar', 'profile', 'nameFormat', 'defaultRoomId', 'messageFormatting', 'privs', 'fileCount', 'fileSize', 'ownedRooms', 'messageCount', 'parentalFlags', 'parentalAge']);
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
        if (isset($databaseFields['password'])) {
            $h = new \Login\PasswordHash(8, false);
            $databaseFields['passwordHash'] = $h->HashPassword($databaseFields['password']);
            $databaseFields['passwordFormat'] = 'phpass';

            unset($databaseFields['password']);
        }

        $this->populateFromArray($databaseFields, true);
        $databaseFields = fim_dbCastArrayEntry($databaseFields, 'privs', Type::bitfield);

        if ($this->id) {
            \Fim\Database::instance()->startTransaction();

            if (fim_inArray(array_keys($databaseFields), explode(', ', \Fim\DatabaseInstance::userHistoryColumns))) {
                if ($existingUserData = \Fim\Database::instance()->getUsers([
                    'userIds' => [$this->id],
                    'columns' => \Fim\DatabaseInstance::userHistoryColumns,
                ])->getAsArray(false)) {
                    \Fim\Database::instance()->insert(\Fim\Database::$sqlPrefix . "userHistory", fim_arrayFilterKeys($existingUserData, ['userId', 'name', 'nameFormat', 'profile', 'avatar', 'mainGroupId', 'defaultMessageFormatting', 'options', 'parentalAge', 'parentalFlags', 'privs']));
                }
            }

            $return = \Fim\Database::instance()->upsert(\Fim\Database::$sqlPrefix . "users", [
                'id' => $this->id,
            ], $databaseFields);

            \Fim\Database::instance()->endTransaction();

            $this->doCache = true;

            return $return;
        }

        else {
            $databaseFields = array_merge([
                'privs' => \Fim\Config::$defaultUserPrivs
            ], $databaseFields);

            $return = \Fim\Database::instance()->insert(\Fim\Database::$sqlPrefix . "users", $databaseFields);

            $this->id = \Fim\Database::instance()->getLastInsertId();

            return $return;
        }
    }
}

?>