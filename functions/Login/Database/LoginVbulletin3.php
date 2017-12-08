<?php
namespace Login\Database;

use Login\LoginDatabase;
use Login\LoginFactory;

class LoginVbulletin3 extends LoginDatabase {
    /**
     * @var array A reference to the tables used by PHPBB.
     */
    private $tables = [
        'users' => 'user',
        'adminGroups' => 'usergroup',
        'socialGroups' => 'socialgroup',
        'socialGroupMembers' => 'socialgroupmember',
    ];

    /**
     * @var array A reference to the tables columns used by PHPBB.
     */
    private $fields = [
        'users' => array(
            'userid' => 'id', 'username' => 'name',
            'password' => 'password', 'salt' => 'salt',
            'displaygroupid' => 'mainGroupId', 'email' => 'email',
            'joindate' => 'joinDate', 'usertitle' => 'userTitle',
            'posts' => 'posts', 'lastvisit' => 'lastVisit',
        ),
/*        'adminGroups' => array(
            'groupId' => 'usergroupid', 'groupName' => 'title',
            'startTag' => 'opentag', 'endTag' => 'closetag',
        ),
        'socialGroups' => array(
            'groupId' => 'groupid', 'groupName' => 'name',
        ),
        'socialGroupMembers' => array(
            'groupId' => 'groupid', 'userId' => 'userid',
            'type' => 'type', 'validType' => 'member',
        ),*/
    ];


    public function __construct(LoginFactory $loginFactory) {
        parent::__construct($loginFactory);
    }

    public function getLoginFactory(): LoginFactory {
        return $this->loginFactory;
    }

    public function hasLoginCredentials(): bool {
        return isset($_REQUEST['username'], $_REQUEST['password']);
    }

    public function getLoginCredentials() {
        return;
    }

    public function setUser() {
        global $loginConfig;

        $vbUser = $this->loginFactory->database->select([
            "{$this->loginFactory->database->sqlPrefix}user" => 'userid, username, password, salt, displaygroupid, usergroupid, membergroupids, email, joindate, usertitle, posts, lastvisit',
        ], [
            'username' => $_REQUEST['username']
        ])->getAsArray(false);

        if (!$vbUser) {
            new \fimError('usernameInvalid', 'A user by the given name does not exist.');
        }
        elseif (strlen($_REQUEST['password']) <= 0 || $vbUser['password'] !== md5(md5($_REQUEST['password']) . $vbUser['salt'])) {
            new \fimError('passwordInvalid', 'A user by the given password does not exist.');
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
                'usergroupid' => $this->loginFactory->database->in(fim_arrayValidate($vbUserGroupIds, 'int'))
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
            $this->loginFactory->user = new \fimUser([
                'integrationMethod' => 'vb34',
                'integrationId' => $vbUser['userid'],
            ]);
            $this->loginFactory->user->resolveAll();
            $this->loginFactory->user->setDatabase([
                'integrationMethod' => 'vb34',
                'integrationId' => $vbUser['userid'],
                'profile' => "{$loginConfig['url']}member.php?u={$vbUser['userid']}",
                'name' => $vbUser['username'],
                'email' => $vbUser['email'],
                'bio' => $vbUser['usertitle'],
                'nameFormat' => $css,
                'mainGroupId' => $vbUser['usergroupid'],
                'joinDate' => $vbUser['joindate'],
                'avatar' => "{$loginConfig['url']}/image.php?u={$vbUser['userid']}", // TODO, I think
            ]);


            /* Get Social Groups The User Belongs To */
            $vbSocialGroupIds = $this->loginFactory->database->select([
                "{$this->loginFactory->database->sqlPrefix}socialgroupmember" => 'userid, groupid, type'
            ], [
                'userid' => $vbUser['userid'],
                'type' => 'member'
            ])->getColumnValues('groupid');

            $vbSocialGroups = $this->loginFactory->database->select([
                "{$this->loginFactory->database->sqlPrefix}socialgroup" => 'groupid, name'
            ], [
                'groupid' => $this->loginFactory->database->in($vbSocialGroupIds)
            ])->getAsArray(true);


            /* Create User Groups */
            $groupNames = [];

            \Fim\Database::instance()->autoQueue(true);
            foreach ($vbUserGroups AS $userGroup) {
                $groupNames[] = $userGroup['title'];
                @\Fim\Database::instance()->createSocialGroup($userGroup['title']);
            }

            foreach ($vbSocialGroups AS $socialGroup) {
                $groupNames[] = 'Social Group: ' . $socialGroup['name'];
                @\Fim\Database::instance()->createSocialGroup('Social Group: ' . $socialGroup['name'], $loginConfig['url'] . '/image.php?groupid=' . $socialGroup['groupid']);
            }
            @\Fim\Database::instance()->autoQueue(false);


            /* Join User Groups */
            $dbGroupIds = \Fim\Database::instance()->select([
                'socialGroups' => 'id, name'
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

    public function syncInstall() {
        global $database, $loginConfig;

        $smilies = $this->loginFactory->database->select(array(
            "{$this->loginFactory->database->sqlPrefix}smilie" => 'smilietext emoticonText, smiliepath emoticonFile'
        ))->getAsArray(true);
        //var_dump($smilies); die();

        \Fim\Database::instance()->autoQueue(true);
        foreach ($smilies AS $smilie) {
            @\Fim\Database::instance()->insert("{\Fim\Database::instance()->sqlPrefix}emoticons", [
                'emoticonText' => $smilie['emoticonText'],
                'emoticonFile' => "{$loginConfig['url']}/{$smilie['emoticonFile']}"
            ]);
        }
        @\Fim\Database::instance()->autoQueue(false);
    }

}