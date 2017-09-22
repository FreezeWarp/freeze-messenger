<?php

require_once('LoginRunner.php');
abstract class LoginTwoStep implements LoginRunner {

    public $loginFactory;

    public function __construct($loginFactory) {
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

        $this->loginFactory->oauthRequest->request['client_id'] = 'IntegrationLogin'; // Pretend we have this.
        $this->loginFactory->oauthRequest->request['grant_type'] = 'integrationLogin'; // Pretend we have this. It isn't used for verification.
        $this->loginFactory->oauthRequest->server['REQUEST_METHOD'] =  'POST'; // Pretend we're a POST request for the OAuth library. A better solution would be to forward, but honestly, it's hard to see the point.
        $this->loginFactory->oauthServer->addGrantType($userC = new OAuth2\GrantType\IntegrationLogin($this->loginFactory->oauthStorage, $this->loginFactory->user));

        $oauthResponse = $this->loginFactory->oauthServer->handleTokenRequest($this->loginFactory->oauthRequest);

        if ($oauthResponse->getStatusCode() !== 200) {
            new fimError($oauthResponse->getParameters()['error'], $oauthResponse->getParameters()['error_description']);
        }
        else {
            header('Location: ' . $installUrl . '?sessionHash=' . $oauthResponse->getParameter('access_token'));
        }

        die();
    }

}