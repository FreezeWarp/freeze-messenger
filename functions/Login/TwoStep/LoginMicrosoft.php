<?php

namespace Login\TwoStep;

use Login\LoginFactory;
use Login\LoginTwoStep;

class LoginMicrosoft extends LoginTwoStep {
    public $loginFactory;

    public $client;

    public function __construct(LoginFactory $loginFactory, $clientId, $clientSecret) {
        global $installUrl;

        parent::__construct($loginFactory);

        // create our client credentials
        $this->client = new \Stevenmaguire\OAuth2\Client\Provider\Microsoft([
            // Required
            'clientId'                  => $clientId,
            'clientSecret'              => $clientSecret,
            'redirectUri'               => $installUrl . 'validate.php?integrationMethod=microsoft'
        ]);
    }

    public function hasLoginCredentials(): bool {
        return isset($_REQUEST['code']);
    }

    public function getLoginCredentials() {
        $url = $this->client->getAuthorizationUrl([
            'scope' => ['wl.basic', 'wl.signin', 'wl.offline_access']
        ]);

        if (!session_id())
            session_start();

        $_SESSION['oauth2state'] = $this->client->getState();

        session_commit();

        // If we don't have an authorization code then get one;
        header('Location: '. $url);
        die();
    }

    public function setUser() {
        if (!session_id())
            session_start();

        if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
            var_dump($_SESSION); die();
            session_unset();
            new fimError('microsoftLoginFailed', 'Invalid state.');
        }
        session_unset();

        // Try to get an access token (using the authorization code grant)
        $token = $this->client->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        try {
            // We got an access token, let's now get the user's details
            $userInfo = $this->client->getResourceOwner($token)->toArray();
        } catch (\Exception $e) {
            new \fimError('microsoftLoginFailed', 'Could not get token: ' . $e);
        }

        /*$picture = new Http\CurlRequest('https://apis.live.net/v5.0/' . $userInfo['id'] . '/picture');
        $picture->executeGET();

        if ($picture->redirectLocation === 'https://js.live.net/static/img/DefaultUserPicture.png') {
            $pictureUrl = '';
        }
        else {
            $pictureUrl = $picture->redirectLocation;
        }*/

        // store user info...
        $this->loginFactory->user = new \fimUser([
            'integrationMethod' => 'microsoft',
            'integrationId' => (int) $userInfo['id'],
        ]);
        $this->loginFactory->user->resolveAll(); // This will resolve the ID if the user exists.
        $this->loginFactory->user->setDatabase([
            'integrationMethod' => 'twitter',
            'integrationId' => $userInfo['id'],
            'name' => $userInfo['name'],
            'email' => $userInfo['emails']['preferred'] ?: $userInfo['emails']['account'],
            //'avatar' => $pictureUrl,
            //language (e.g. en_US) $userInfo['locale']
        ]);
    }

    public static function isProfileFeatureDisabled($feature): bool {
        return in_array($feature, ['selfChangeAvatar']);
    }

}