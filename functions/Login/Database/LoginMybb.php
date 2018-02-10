<?php

namespace Login\Database;

use Login\LoginDatabase;
use Login\LoginFactory;

/**
 * MyBB 1.8 Login Provider
 */
class LoginMybb extends LoginDatabase
{

    /**
     * @var array A reference to the tables used by PHPBB.
     */
    private $tables = [
        'users'              => 'users',
        'adminGroups'        => 'usergroups',
        'socialGroups'       => false,
        'socialGroupMembers' => false
    ];

    /**
     * @var array A reference to the tables columns used by PHPBB.
     */
    private $fields = [
        'users' => [
            'uid'              => 'id',
            'username'         => 'name',
            'password'         => 'password',
            'salt'             => 'salt',
            'usergroup'        => 'mainGroupId',
            'displaygroup'     => 'displayGroupId',
            'additionalgroups' => 'allGroupIds',
            'email'            => 'email',
            'regdate'          => 'joinDate',
            'avatar'           => 'avatar',
            'avatartype'       => 'avatarType',
            'postnum'          => 'posts'
        ],

        'adminGroups' => [
            'gid'       => 'id',
            'title'     => 'name',
            'namestyle' => 'userNameFormat',
            'isbannedgroup' => 'bannedGroup',
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

        $mybbUser = $this->loginFactory->database->select([
            $this->loginFactory->database->sqlPrefix . $this->tables['users'] => $this->fields['users']
        ], [
            "name" => $_REQUEST['username']
        ])->getAsArray(false);

        if (!$mybbUser) {
            new \Fim\Error('usernameInvalid', 'A user by the given name does not exist.');
        }
        elseif (strlen($mybbUser['password']) <= 0
            || md5(md5($mybbUser['salt']) . md5($_REQUEST['password'])) != $mybbUser['password']) {
            new \Fim\Error('passwordInvalid', 'A user by the given password does not exist.');
        }
        else {
            $mybbUser['displayGroupId'] = $mybbUser['displayGroupId'] ?: $mybbUser['mainGroupId'];

            $mybbUserGroupIds = explode(',', $mybbUser['allGroupIds']);
            if ($mybbUser['displayGroupId'] && !in_array($mybbUser['displayGroupId'], $mybbUserGroupIds))
                $mybbUserGroupIds[] = $mybbUser['displayGroupId'];
            if ($mybbUser['mainGroupId'] && !in_array($mybbUser['mainGroupId'], $mybbUserGroupIds))
                $mybbUserGroupIds[] = $mybbUser['mainGroupId'];

            $mybbUserGroups = $this->loginFactory->database->select([
                $this->loginFactory->database->sqlPrefix . $this->tables['adminGroups'] => $this->fields['adminGroups']
            ], [
                'id' => $this->loginFactory->database->in(\Fim\Utilities::arrayValidate($mybbUserGroupIds, 'int'))
            ])->getAsArray('id');

            /* Get Display Group */
            if (isset($mybbUserGroups[$mybbUser['displayGroupId']])) {
                /*
                 * This isn't perfect, since it will not correctly detect escaped quotes inside of the style tag. However, it will detect pretttty much everything else.
                 * (Obviously, the inherent style of the tags used will be ignored. It's a compromise.)
                 */
                preg_match_all("/style=('|\")(.+?)\\1/", $mybbUserGroups[$mybbUser['displayGroupId']]['userNameFormat'], $matches);
                $css = implode(';', $matches[2]);
            }
            else {
                $css = '';
            }

            $this->loginFactory->user = new \Fim\User([
                'integrationMethod' => 'mybb',
                'integrationId'     => $mybbUser['id'],
            ]);
            $this->loginFactory->user->resolveAll();
            $this->loginFactory->user->setDatabase([
                'integrationMethod' => 'mybb',
                'integrationId'     => $mybbUser['id'],
                'profile'           => "{$loginConfig['url']}member.php?action=profile&uid={$mybbUser['id']}",
                'email'             => $mybbUser['email'],
                'mainGroupId'       => $mybbUser['mainGroupId'],
                'joinDate'          => $mybbUser['joinDate'],
                'name'              => $mybbUser['name'],
                'avatar'            => $mybbUser['avatarType'] === 'upload' ? "{$loginConfig['url']}{$mybbUser['avatar']}" : '',
                'nameFormat'        => $css,
            ]);


            /* Create User Groups  */
            if (count($mybbUserGroups) > 0) {
                $groups = [];

                foreach ($mybbUserGroups AS $group) {
                    $groups[] = [
                        'name'   => $group['name'],
                    ];
                }

                \Fim\Database::instance()->enterSocialGroups($this->loginFactory->user->id, $groups);
            }

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
            \Fim\DatabaseLogin::$sqlPrefix . "smilies" => 'find emoticonText, image emoticonFile'
        ])->getAsArray('emoticonText'), '/');
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