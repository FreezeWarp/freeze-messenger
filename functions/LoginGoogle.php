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

require_once('vendor/autoload.php');

require_once('LoginTwoStep.php');

class LoginGoogle extends LoginTwoStep {
    public $client;
    public $loginFactory;

    public function __construct($loginFactory, $clientId, $clientSecret) {
        global $installUrl;

        parent::__construct($loginFactory);

        // create our client credentials
        $this->client = new Google_Client();

        $this->client->setApplicationName("FlexMessenger Login");
        $this->client->setDeveloperKey("AIzaSyDxK4wHgx7NAy6NU3CcSsQ2D3JX3K6FwVs");
        $this->client->setClientId($clientId);
        $this->client->setClientSecret($clientSecret);
        $this->client->setRedirectUri($installUrl . 'validate.php?integrationMethod=google');
        $this->client->addScope([
            Google_Service_Oauth2::USERINFO_EMAIL,
            Google_Service_Oauth2::USERINFO_PROFILE,
        ]);
    }

    public function hasLoginCredentials() : bool {
        return isset($_REQUEST['code']);
    }

    public function getLoginCredentials() {
        header('Location: ' . filter_var($this->client->createAuthUrl(), FILTER_SANITIZE_URL));
        die();
    }

    public function setUser() {
        $this->client->fetchAccessTokenWithAuthCode($_GET['code']); // verify returned code

        $access_token = $this->client->getAccessToken();
        if (!$access_token)
            new fimError('failedLogin', 'We were unable to login to the Google server.');

        // get user info
        $googleUser = new Google_Service_Oauth2($this->client);
        $userInfo = $googleUser->userinfo->get();

        if (!$userInfo->getId())
            new fimError('invalidIntegrationId', 'The Google server did not respond with a valid user ID. Login cannot continue.');

        elseif (!$userInfo->getName())
            new fimError('invalidIntegrationName', 'The Google server did not respond with a valid user name. Login cannot continue.');

        // store user info...
        $this->loginFactory->user = new fimUser([
            'integrationMethod' => 'google',
            'integrationId' => $userInfo->getId(),
        ]);
        $this->loginFactory->user->resolveAll(); // This will resolve the ID if the user exists.
        $this->loginFactory->user->setDatabase([
            'integrationMethod' => 'google',
            'integrationId' => $userInfo->getId(),
            'email' => $userInfo->getEmail(),
            'name' => $userInfo->getName(),
            'avatar' => $userInfo->getPicture()
        ]); // If the ID wasn't resolved above, a new user will be created.
    }
}