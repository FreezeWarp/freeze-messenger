<?php

namespace Login\Database;

use Login\LoginDatabase;
use Login\LoginFactory;

/**
 * PHPBB 3 Login Provider
 * This uses PHPass to validate PHPBB-stored logins, and then fetches user group information and smilies.
 */
class LoginPhpbb extends LoginDatabase
{
    /**
     * @var array A reference to the tables used by PHPBB.
     */
    private $tables = [
        'users'              => 'users',
        'adminGroups'        => false,
        'socialGroups'       => 'groups',
        'socialGroupMembers' => 'user_group'
    ];

    /**
     * @var array A reference to the tables columns used by PHPBB.
     */
    private $fields = [
        'users' => [
            'user_id'       => 'id', 'username' => 'name',
            'user_password' => 'password',
            'group_id'      => 'mainGroupId', 'user_email' => 'email',
            'user_regdate'  => 'joinDate', 'user_avatar' => 'avatar',
            'user_colour'   => 'nameColor', 'user_posts' => 'posts'
        ],
    ];


    /**
     * LoginPhpbb constructor.
     *
     * @param $loginFactory \Login\LoginFactory The LoginFactory instance used to create this object.
     */
    public function __construct(LoginFactory $loginFactory)
    {
        parent::__construct($loginFactory);
    }


    /**
     * @see LoginRunner::hasLoginCredentials()
     */
    public function hasLoginCredentials(): bool
    {
        return isset($_REQUEST['username'], $_REQUEST['password']);
    }


    /**
     * @see LoginRunner::getLoginCredentials()
     */
    public function getLoginCredentials()
    {
        return;
    }


    /**
     * @see LoginRunner::setUser()
     */
    public function setUser()
    {
        global $loginConfig;

        $phpbbUser = $this->loginFactory->database->select([
            $this->loginFactory->database->sqlPrefix . $this->tables['users'] => $this->fields['users']
        ], [
            "name" => $_REQUEST['username']
        ])->getAsArray(false);

        if (!$phpbbUser) {
            new \Fim\Error('usernameInvalid', 'A user by the given name does not exist.');
        }
        elseif (strlen($phpbbUser['password']) <= 0 || !(new \Login\PasswordHash(8, false))->CheckPassword($_REQUEST['password'], $phpbbUser['password'])) {
            new \Fim\Error('passwordInvalid', 'A user by the given password does not exist.');
        }
        else {
            if (!preg_match('/^[0-9A-Fa-f]+$/', $phpbbUser['nameColor'])) {
                $phpbbUser['nameColor'] = '';
            }

            $this->loginFactory->user = new \Fim\User([
                'integrationMethod' => 'phpbb',
                'integrationId'     => $phpbbUser['id'],
            ]);
            $this->loginFactory->user->resolveAll();
            $this->loginFactory->user->setDatabase([
                'integrationMethod' => 'phpbb',
                'integrationId'     => $phpbbUser['id'],
                'profile'           => "{$loginConfig['url']}memberlist.php?mode=viewprofile&u={$phpbbUser['id']}",
                'email'             => $phpbbUser['email'],
                'mainGroupId'       => $phpbbUser['mainGroupId'],
                'joinDate'          => $phpbbUser['joinDate'],
                'name'              => $phpbbUser['name'],
                'avatar'            => ($phpbbUser['avatar'] ? "{$loginConfig['url']}download/file.php?avatar={$phpbbUser['avatar']}" : ''),
                'nameFormat'        => ($phpbbUser['nameColor'] ? 'color: #' . $phpbbUser['nameColor'] : ''),
            ]);



            /* Get Social Groups The User Belongs To */
            $phpbbGroupIds = $this->loginFactory->database->select([
                "{$this->loginFactory->database->sqlPrefix}user_group" => 'user_id, group_id, user_pending'
            ], [
                'user_id' => $phpbbUser['id'],
                'user_pending' => 0,
            ])->getColumnValues('group_id');

            $phpbbGroups = $this->loginFactory->database->select([
                "{$this->loginFactory->database->sqlPrefix}groups" => 'group_id, group_name, group_avatar'
            ], [
                'group_id' => $this->loginFactory->database->in($phpbbGroupIds)
            ])->getAsArray(true);


            /* Create User Groups
             * TODO: detect group moves/renames */
            $groupNames = [];

            \Fim\Database::instance()->autoQueue(true);
            foreach ($phpbbGroups AS $group) {
                $groupNames[] = $group['group_name'];
                @\Fim\Database::instance()->createSocialGroup(
                    $group['group_name'],
                    $group['group_avatar']
                        ? "{$loginConfig['url']}download/file.php?avatar={$group['group_avatar']}"
                        : ''
                );
            }
            @\Fim\Database::instance()->autoQueue(false);


            /* Join User Groups */
            $dbGroupIds = \Fim\Database::instance()->select([
                \Fim\Database::$sqlPrefix . 'socialGroups' => 'id, name'
            ], ['name' => \Fim\Database::instance()->in($groupNames)])->getColumnValues('id');

            \Fim\Database::instance()->autoQueue(true);
            foreach ($dbGroupIds AS $groupId) {
                @\Fim\Database::instance()->enterSocialGroup($groupId, $this->loginFactory->user);
            }
            @\Fim\Database::instance()->autoQueue(false);

            /*
             * TODO: on timer
             */
            $this->syncInstall();

            $this->oauthGrantType = $this->loginFactory->oauthGetIntegrationLogin();
        }
    }


    /**
     * Sync the remote PHPBB installation information with the locally-available FreezeMessenger installation information.
     */
    public function syncInstall()
    {
        $this->syncEmoticons(\Fim\DatabaseLogin::instance()->select([
            \Fim\DatabaseLogin::$sqlPrefix . "smilies" => 'code emoticonText, smiley_url emoticonFile'
        ])->getAsArray('emoticonText'), 'images/smilies/');
    }


    /**
     * Indicates that 'selfChangeAvatar' and 'selfChangeProfile' is a disabled profile feature when using PHPBB logins.
     * @see LoginRunner::isProfileFeatureDisabled()
     */
    public static function isProfileFeatureDisabled($feature): bool
    {
        return in_array($feature, ['selfChangeAvatar', 'selfChangeProfile']);
    }

    /**
     * Indicates that 'emoticons' is a disabled site feature when using PHPBB logins.
     * @see LoginRunner::isSiteFeatureDisabled()
     */
    public static function isSiteFeatureDisabled($feature): bool
    {
        return in_array($feature, ['emoticons']);
    }

}