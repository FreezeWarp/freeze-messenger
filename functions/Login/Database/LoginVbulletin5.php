<?php

namespace Login\Database;

use Login\LoginDatabase;
use Login\LoginFactory;

/**
 * vBulletin 5 Login Provider
 * This uses `password_verify(md5(password), hash)` to validate vBulletin-stored logins, and then fetches user group information and smilies.
 */
class LoginVbulletin5 extends LoginDatabase
{
    /**
     * LoginVbulletin3 constructor.
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

        $vbUser = $this->loginFactory->database->select([
            "{$this->loginFactory->database->sqlPrefix}user" => 'userid, username, token, scheme, displaygroupid, usergroupid, membergroupids, email, joindate, usertitle, posts, lastvisit',
        ], [
            'username' => $_REQUEST['username']
        ])->getAsArray(false);

        if (!$vbUser || strlen($vbUser['token']) <= 0) {
            new \Fim\Error('usernameInvalid', 'A user by the given name does not exist.');
        }
        elseif ($vbUser['scheme'] === 'legacy') {
            new \Fim\Error('passwordUnsupported', 'Users imported from vBulletin 3/4 systems are not supported. Please change your password in vBulletin 5 and then login again.');
        }
        // This isn't quite one-to-one (we could technically be allowing users that vB5 would reject), but is close enough
        elseif (!password_verify(md5($_REQUEST['password']), $vbUser['token'])) {
            new \Fim\Error('passwordInvalid', 'A user by the given password does not exist.');
        }
        else {
            /* TODO: all of the fancy stuff here (querying the group tables) should be put on a timer, and only done once every so often. */

            /* Get All User Groups */
            $vbUser['displaygroupid'] = $vbUser['displaygroupid'] ?: $vbUser['usergroupid'];

            $vbUserGroupIds = explode(',', $vbUser['membergroupids']);
            if (!in_array($vbUser['usergroupid'], $vbUserGroupIds))
                $vbUserGroupIds[] = $vbUser['usergroupid'];
            if (!in_array($vbUser['displaygroupid'], $vbUserGroupIds))
                $vbUserGroupIds[] = $vbUser['displaygroupid'];

            $vbUserGroups = $this->loginFactory->database->select([
                "{$this->loginFactory->database->sqlPrefix}usergroup" => 'usergroupid, title, opentag'
            ], [
                'usergroupid' => $this->loginFactory->database->in(\Fim\Utilities::arrayValidate($vbUserGroupIds, 'int'))
            ])->getAsArray('usergroupid');


            /* Get Display Group */
            if (isset($vbUserGroups[$vbUser['displaygroupid']])) {
                /*
                 * This isn't perfect, since it will not correctly detect escaped quotes inside of the style tag. However, it will detect pretttty much everything else.
                 * (Obviously, the inherent style of the tags used will be ignored. It's a compromise.)
                 */
                preg_match_all("/style=('|\")(.+?)\\1/", $vbUserGroups[$vbUser['displaygroupid']]['opentag'], $matches);
                $css = implode(';', $matches[2]);
            }
            else {
                $css = '';
            }


            /* Create User */
            $this->loginFactory->user = new \Fim\User([
                'integrationMethod' => 'vb5',
                'integrationId'     => $vbUser['userid'],
            ]);
            $this->loginFactory->user->resolveAll();
            $this->loginFactory->user->setDatabase([
                'integrationMethod' => 'vb5',
                'integrationId'     => $vbUser['userid'],
                'profile'           => "{$loginConfig['url']}member/{$vbUser['userid']}",
                'name'              => $vbUser['username'],
                'email'             => $vbUser['email'],
                'bio'               => $vbUser['usertitle'],
                'nameFormat'        => $css,
                'mainGroupId'       => $vbUser['usergroupid'],
                'joinDate'          => $vbUser['joindate'],
                'avatar'            => "{$loginConfig['url']}core/image.php?userid={$vbUser['userid']}",
            ]);


            /* Get Social Groups The User Belongs To */
            $vbSocialGroupIds = $this->loginFactory->database->select([
                "{$this->loginFactory->database->sqlPrefix}groupintopic" => 'userid, nodeid'
            ], [
                'userid' => $vbUser['userid'],
            ])->getColumnValues('nodeid');

            $vbSocialGroups = $this->loginFactory->database->select([
                "{$this->loginFactory->database->sqlPrefix}node" => 'nodeid, title'
            ], [
                'nodeid' => $this->loginFactory->database->in($vbSocialGroupIds)
            ])->getAsArray(true);


            /* Create User Groups */
            $groups = [];

            foreach ($vbUserGroups AS $userGroup) {
                $groups[] = [
                    'name' => $userGroup['title'],
                    'avatar' => '',
                ];
            }

            foreach ($vbSocialGroups AS $socialGroup) {
                $groups[] = [
                    'name' => 'Social Group: ' . $socialGroup['title'],
                    'avatar' => "{$loginConfig['url']}filedata/fetch?channelid={$socialGroup['nodeid']}&type=medium"
                ];
            }

            \Fim\Database::instance()->enterSocialGroups($this->loginFactory->user->id, $groups);



            /*
             * TODO: on timer
             */
            $this->syncInstall();

            $this->oauthGrantType = $this->loginFactory->oauthGetIntegrationLogin();
        }
    }


    /**
     * Sync the remote vBulletin installation information with the locally-available FreezeMessenger installation information.
     */
    public function syncInstall()
    {
        $this->syncEmoticons($this->loginFactory->database->select([
            "{$this->loginFactory->database->sqlPrefix}smilie" => 'smilietext emoticonText, smiliepath emoticonFile'
        ])->getAsArray('emoticonText'), 'core/');
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