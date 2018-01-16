<?php

namespace Login;

/**
 * This is the broad functionality used by any Two-Step login provider: one that first launches a remote application to obtain a login instance, and then uses that login instance to obtain user information.
 */
abstract class LoginTwoStep implements LoginRunner
{
    /**
     * @var LoginFactory The LoginFactory instance used to create the TwoStep provider.
     */
    public $loginFactory;

    /**
     * LoginTwoStep constructor.
     */
    public function __construct(LoginFactory $loginFactory)
    {
        $this->loginFactory = $loginFactory;
    }

    /**
     * @see LoginRunner::hasLoginCredentials()
     */
    abstract public function hasLoginCredentials(): bool;

    /**
     * @see LoginRunner::getLoginCredentials()
     */
    abstract public function getLoginCredentials();

    /**
     * @see LoginRunner::setUser()
     */
    abstract public function setUser();

    /**
     * @see LoginRunner::getLoginFactory()
     */
    public function getLoginFactory(): LoginFactory
    {
        return $this->loginFactory;
    }

    /**
     * @see LoginRunner::apiResponse()
     */
    public function apiResponse()
    {
        global $installUrl;

        $this->loginFactory->oauthServer->addGrantType($this->loginFactory->oauthGetIntegrationLogin());

        $oauthResponse = $this->loginFactory->oauthServer->handleTokenRequest($this->loginFactory->oauthRequest);

        if ($oauthResponse->getStatusCode() !== 200) {
            new \Fim\Error($oauthResponse->getParameters()['error'], $oauthResponse->getParameters()['error_description']);
        }
        else {
            header('Location: ' . $installUrl . '?sessionHash=' . $oauthResponse->getParameter('access_token'));
        }

        die();
    }

    /**
     * @see LoginRunner::isProfileFeatureDisabled()
     */
    public static function isProfileFeatureDisabled($feature): bool
    {
        return false;
    }

    /**
     * @see LoginRunner::isSiteFeatureDisabled()
     */
    public static function isSiteFeatureDisabled($feature): bool
    {
        return false;
    }

}