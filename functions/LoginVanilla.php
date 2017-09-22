<?php
require_once('LoginDatabase.php');
class LoginVanilla extends LoginDatabase {
    /**
     * @var LoginFactory
     */
    public $loginFactory;

    /**
     * @var fimDatabase
     */
    public $database;


    public function __construct(LoginFactory $loginFactory, fimDatabase $database) {
        $this->loginFactory = $loginFactory;
        $this->database = $database;
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
        $this->oauthGrantType = new OAuth2\GrantType\UserCredentials($this->loginFactory->oauthStorage);
    }

}