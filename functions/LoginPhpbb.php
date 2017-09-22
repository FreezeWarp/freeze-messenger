<?php
require_once('LoginDatabase.php');
require_once('PasswordHash.php');
class LoginPhpbb extends LoginDatabase {
    /**
     * @var array A reference to the tables used by PHPBB.
     */
    private $tables = [
        'users' => 'users',
        'adminGroups' => false,
        'socialGroups' => 'groups',
        'socialGroupMembers' => 'user_group'
    ];

    /**
     * @var array A reference to the tables columns used by PHPBB.
     */
    private $fields = [
        'users' => array(
            'user_id' => 'id', 'username' => 'name',
            'user_password' => 'password',
            'group_id' => 'mainGroupId', 'user_email' => 'email',
            'user_regdate' => 'joinDate', 'user_avatar' => 'avatar',
            'user_colour' => 'nameColor'
        ),
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

        $phpbbUser = $this->loginFactory->database->select(array(
            $this->loginFactory->database->sqlPrefix . $this->tables['users'] => $this->fields['users']
        ), [
            "name" => $_REQUEST['username']
        ])->getAsArray(false);

        if (!$phpbbUser) {
            new fimError('usernameInvalid', 'A user by the given name does not exist.');
        }
        elseif (strlen($_REQUEST['password']) <= 0 || !(new PasswordHash(8, FALSE))->CheckPassword($_REQUEST['password'], $phpbbUser['password'])) {
            new fimError('passwordInvalid', 'A user by the given password does not exist.');
        }
        else {
            if (!preg_match('/^[0-9A-Fa-f]+$/', $phpbbUser['nameColor'])) {
                $phpbbUser['nameColor'] = '';
            }

            $this->loginFactory->user = new fimUser([
                'integrationMethod' => 'phpbb',
                'integrationId' => $phpbbUser['id'],
            ]);
            $this->loginFactory->user->resolveAll();
            $this->loginFactory->user->setDatabase([
                'integrationMethod' => 'phpbb',
                'integrationId' => $phpbbUser['id'],
                'profile' => "{$loginConfig['url']}memberlist.php?mode=viewprofile&u={$phpbbUser['id']}",
                'email' => $phpbbUser['email'],
                'name' => $phpbbUser['name'],
                'avatar' => ($phpbbUser['avatar'] ? "{$loginConfig['url']}/download/file.php?avatar={$phpbbUser['avatar']}" : ''),
                'nameFormat' => ($phpbbUser['nameColor'] ? 'color: #' . $phpbbUser['nameColor'] : ''),
            ]);

            $this->oauthGrantType = $this->loginFactory->oauthGetIntegrationLogin();
        }
    }

}