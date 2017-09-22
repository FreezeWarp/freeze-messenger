<?php
require_once('LoginDatabase.php');
class LoginVanilla extends LoginDatabase {
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
        $this->oauthGrantType = new OAuth2\GrantType\UserCredentials($this->loginFactory->oauthStorage);
    }

}