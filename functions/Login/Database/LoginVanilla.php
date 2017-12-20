<?php

namespace Login\Database;

use Login\LoginDatabase;
use Login\LoginFactory;
use Login\LoginRunner;

/**
 * The LoginRunner used for vanilla FreezeMessenger database logins.
 */
class LoginVanilla extends LoginDatabase
{
    /**
     * LoginVanilla constructor.
     *
     * @param $loginFactory LoginFactory The LoginFactory instance used to create this object.
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
        $this->oauthGrantType = new \OAuth2\GrantType\UserCredentials($this->loginFactory->oauthStorage);
    }

}