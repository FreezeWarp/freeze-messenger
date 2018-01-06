<?php

namespace Login\TwoStep;

use Login\LoginFactory;
use Login\LoginRunner;
use Login\LoginTwoStep;
use \Stevenmaguire\OAuth2\Client\Provider\Microsoft;

/**
 * Microsoft Login Provider
 * This will use the Microsoft client library to authenticate users using Microsoft login credentials.
 * It requires that a connection be made using HTTPS.
 */
class LoginMicrosoft extends LoginTwoStep {
    /**
     * @var Microsoft The Microsoft client instance.
     */
    public $client;


    /**
     * LoginMicrosoft constructor.
     *
     * @param $loginFactory LoginFactory The LoginFactory instance used to create this object.
     * @param $clientId     string The Microsoft API client ID.
     * @param $clientSecret string The Microsoft API client secret.
     */
    public function __construct(LoginFactory $loginFactory, $clientId, $clientSecret) {
        global $installUrl;

        parent::__construct($loginFactory);

        // create our client credentials
        $this->client = new Microsoft([
            // Required
            'clientId'                  => $clientId,
            'clientSecret'              => $clientSecret,
            'redirectUri'               => $installUrl . 'validate.php?integrationMethod=microsoft'
        ]);
    }


    /**
     * @see LoginRunner::hasLoginCredentials()
     */
    public function hasLoginCredentials(): bool {
        return isset($_REQUEST['code']);
    }


    /**
     * @see LoginRunner::getLoginCredentials()
     */
    public function getLoginCredentials() {
        $url = $this->client->getAuthorizationUrl([
            'scope' => ['wl.basic']
        ]);

        if (!session_id())
            session_start();

        $_SESSION['oauth2state'] = $this->client->getState();

        session_commit();

        // If we don't have an authorization code then get one;
        header('Location: '. $url);
        die();
    }


    /**
     * @see LoginRunner::setUser()
     */
    public function setUser() {
        if (!session_id())
            session_start();

        if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
            session_unset();
            new \fimError('microsoftLoginFailed', 'Invalid state.');
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

        $picture = new \Http\CurlRequest('https://apis.live.net/v5.0/' . $userInfo['id'] . '/picture');
        $picture->executeGET();

        if ($picture->redirectLocation === 'https://js.live.net/static/img/DefaultUserPicture.png') {
            $pictureUrl = '';
        }
        else {
            $pictureUrl = $picture->redirectLocation;
        }

        // store user info...
        $this->loginFactory->user = new \Fim\User([
            'integrationMethod' => 'microsoft',
            'integrationId' => (int) $userInfo['id'],
        ]);
        $this->loginFactory->user->resolveAll(); // This will resolve the ID if the user exists.
        $this->loginFactory->user->setDatabase([
            'integrationMethod' => 'microsoft',
            'integrationId' => $userInfo['id'],
            'name' => $userInfo['name'],
            'email' => $userInfo['emails']['preferred'] ?: $userInfo['emails']['account'],
            'avatar' => $pictureUrl,
            //language (e.g. en_US) $userInfo['locale']
        ]);
    }


    /**
     * Indicates that 'selfChangeAvatar' is a disabled profile feature when using Microsoft logins.
     * @see LoginRunner::isProfileFeatureDisabled()
     */
    public static function isProfileFeatureDisabled($feature): bool {
        return in_array($feature, ['selfChangeAvatar']);
    }

}