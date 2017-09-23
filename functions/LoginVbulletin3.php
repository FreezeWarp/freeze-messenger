<?php
require_once('LoginDatabase.php');

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

        $vbUser = $this->loginFactory->database->select(array(
            $this->loginFactory->database->sqlPrefix . $this->tables['users'] => $this->fields['users']
        ), [
            "name" => $_REQUEST['username']
        ])->getAsArray(false);

        if (!$vbUser) {
            new fimError('usernameInvalid', 'A user by the given name does not exist.');
        }
        elseif (strlen($_REQUEST['password']) <= 0 || $vbUser['password'] !== md5(md5($_REQUEST['password']) . $vbUser['salt'])) {
            new fimError('passwordInvalid', 'A user by the given password does not exist.');
        }
        else {
            $this->loginFactory->user = new fimUser([
                'integrationMethod' => 'vb34',
                'integrationId' => $vbUser['id'],
            ]);
            $this->loginFactory->user->resolveAll();
            $this->loginFactory->user->setDatabase([
                'integrationMethod' => 'vb34',
                'integrationId' => $vbUser['id'],
                'profile' => "{$loginConfig['url']}member.php?u={$vbUser['id']}",
                'name' => $vbUser['name'],
                'email' => $vbUser['email'],
                'mainGroupId' => $vbUser['mainGroupId'],
                'joinDate' => $vbUser['joinDate'],
                'avatar' => "{$loginConfig['url']}/image.php?u={$vbUser['id']}",
            ]);

            /**
             * TODO:
             * nameFormat from groupId
             * check avatar
             *
             */

            $this->oauthGrantType = $this->loginFactory->oauthGetIntegrationLogin();
        }
    }

}