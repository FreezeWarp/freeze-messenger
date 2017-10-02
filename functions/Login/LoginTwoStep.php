<?php
namespace Login;

abstract class LoginTwoStep implements LoginRunner {

    /**
     * @var LoginFactory
     */
    public $loginFactory;

    public function __construct(LoginFactory $loginFactory) {
        $this->loginFactory = $loginFactory;
    }

    abstract public function hasLoginCredentials(): bool;
    abstract public function getLoginCredentials();
    abstract public function setUser();

    public function getLoginFactory(): LoginFactory {
        return $this->loginFactory;
    }

    public function apiResponse() {
        global $installUrl;

        $this->loginFactory->oauthServer->addGrantType($this->loginFactory->oauthGetIntegrationLogin());

        $oauthResponse = $this->loginFactory->oauthServer->handleTokenRequest($this->loginFactory->oauthRequest);

        if ($oauthResponse->getStatusCode() !== 200) {
            new \fimError($oauthResponse->getParameters()['error'], $oauthResponse->getParameters()['error_description']);
        }
        else {
            header('Location: ' . $installUrl . '?sessionHash=' . $oauthResponse->getParameter('access_token'));
        }

        die();
    }

}