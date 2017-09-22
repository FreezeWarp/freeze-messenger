<?php
/* FreezeMessenger Copyright © 2017 Joseph Todd Parsons

 * This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>. */

class LoginFactory {
    public $oauthRequest;
    public $oauthStorage;
    public $oauthServer;

    public $loginRunner = null;

    public $user;

    public function __construct(OAuth2\Request $oauthRequest, OAuth2\Storage\FIMDatabaseOAuth $oauthStorage, OAuth2\Server $oauthServer) {
        global $loginConfig;

        $this->oauthRequest = $oauthRequest;
        $this->oauthStorage = $oauthStorage;
        $this->oauthServer = $oauthServer;

        if (isset($_REQUEST['googleLogin'])
            && isset($loginConfig['extraMethods']['google']['clientId'])
            && isset($loginConfig['extraMethods']['google']['clientSecret'])) {
            require('LoginGoogle.php');
            $this->loginRunner = new LoginGoogle(
                $this,
                $loginConfig['extraMethods']['google']['clientId'],
                $loginConfig['extraMethods']['google']['clientSecret']
            );
        }

        else if (isset($_REQUEST['twitterLogin'])) {
            require('LoginTwitter.php');
            $this->loginRunner = new LoginTwitter(
                $this,
                $loginConfig['extraMethods']['twitter']['clientId'],
                $loginConfig['extraMethods']['twitter']['clientSecret']
            );
        }
    }

    /**
     * Whether or not an integration login is available.
     * @return bool
     */
    public function hasLogin() {
        return $this->loginRunner != null;
    }


    /**
     * Perform a login.
     */
    public function getLogin() {
        if ($this->loginRunner->hasLoginCredentials()) {
            $this->loginRunner->setUser();
        }
        else {
            $this->loginRunner->getLoginCredentials();
        }
    }

    /**
     * Get the API response, following a login.
     */
    public function apiResponse() {
        global $installUrl;

        $this->oauthRequest->request['client_id'] = 'IntegrationLogin'; // Pretend we have this.
        $this->oauthRequest->request['grant_type'] = 'integrationLogin'; // Pretend we have this. It isn't used for verification.
        $this->oauthRequest->server['REQUEST_METHOD'] =  'POST'; // Pretend we're a POST request for the OAuth library. A better solution would be to forward, but honestly, it's hard to see the point.
        $this->oauthServer->addGrantType($userC = new OAuth2\GrantType\IntegrationLogin($this->oauthStorage, $this->user));

        $oauthResponse = $this->oauthServer->handleTokenRequest($this->oauthRequest);

        if ($oauthResponse->getStatusCode() !== 200) {
            new fimError($oauthResponse->getParameters()['error'], $oauthResponse->getParameters()['error_description']);
        }
        else {
            header('Location: ' . $installUrl . '?sessionHash=' . $oauthResponse->getParameter('access_token'));
        }

        die();
    }
}