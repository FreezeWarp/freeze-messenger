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

namespace Login\TwoStep;

use Login\LoginFactory;
use Login\LoginTwoStep;
use \Facebook;

/**
 * Facebook Login Provider
 * This will use the Facebook client library to authenticate users using Facebook login credentials.
 */
class LoginFacebook extends LoginTwoStep
{
    /**
     * @var Facebook\Facebook The Facebook client instance.
     */
    public $client;

    /**
     * LoginFacebook constructor.
     *
     * @param $loginFactory LoginFactory The LoginFactory instance used to create this object.
     * @param $clientId     string The Facebook API client ID.
     * @param $clientSecret string The Facebook API client secret.
     */
    public function __construct(LoginFactory $loginFactory, string $clientId, string $clientSecret)
    {
        parent::__construct($loginFactory);

        // Session Data for Facebook
        if (!session_id()) {
            session_start();
        }

        // create our client credentials
        $this->client = new Facebook\Facebook([
            'app_id'                => $clientId, // Replace {app-id} with your app id
            'app_secret'            => $clientSecret,
            'default_graph_version' => 'v2.2',
        ]);
    }


    /**
     * @see LoginRunner::hasLoginCredentials()
     */
    public function hasLoginCredentials(): bool
    {
        return isset($_REQUEST['code']) || isset($_REQUEST['state']);
    }


    /**
     * @see LoginRunner::getLoginCredentials()
     */
    public function getLoginCredentials()
    {
        global $installUrl;
        header('Location: ' . filter_var($this->client->getRedirectLoginHelper()->getLoginUrl($installUrl . 'validate.php?integrationMethod=facebook'), FILTER_SANITIZE_URL));
        die();
    }


    /**
     * @see LoginRunner::setUser()
     */
    public function setUser()
    {
        try {
            $accessToken = $this->client->getRedirectLoginHelper()->getAccessToken();
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            new \Fim\Error('facebookError', 'Graph returned an error: ' . $e->getMessage());
            die();
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            new \Fim\Error('facebookError', 'Facebook SDK returned an error: ' . $e->getMessage());
            die();
        }

        if (!isset($accessToken)) {
            $helper = $this->client->getRedirectLoginHelper();

            if ($this->client->getRedirectLoginHelper()->getError()) {
                new \Fim\Error($helper->getErrorCode(), $helper->getError(), [
                    'reason'      => $helper->getErrorReason(),
                    'description' => $helper->getErrorDescription()
                ]);
            }
            else {
                new \Fim\Error('facebookError', 'Unknown Facebook Error.');
            }
            exit;
        }

        try {
            $user = $this->client->get('/me?fields=id,name,about', $accessToken)->getDecodedBody();
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            new \Fim\Error('facebookError', 'Graph returned an error: ' . $e->getMessage());
            die();
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            new \Fim\Error('facebookError', 'Facebook SDK returned an error: ' . $e->getMessage());
            die();
        }

        try {
            $picture = $this->client->get('/me/picture?redirect=false&type=large', $accessToken)->getDecodedBody();
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            new \Fim\Error('facebookError', 'Graph returned an error: ' . $e->getMessage());
            die();
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            new \Fim\Error('facebookError', 'Facebook SDK returned an error: ' . $e->getMessage());
            die();
        }
        session_unset();

        if (isset($user) && $user['id']) {
            // store user info...
            $this->loginFactory->user = new \Fim\User([
                'integrationMethod' => 'facebook',
                'integrationId'     => $user['id'],
            ]);
            $this->loginFactory->user->resolveAll(); // This will resolve the ID if the user exists.
            $this->loginFactory->user->setDatabase([
                'integrationMethod' => 'facebook',
                'integrationId'     => $user['id'],
                'name'              => $user['name'],
                'avatar'            => $picture['data']['url']
            ]);
        }
        else {
            \fimError('invalidFacebookLogin', 'Facebook Login returned bad data.');
        }
    }

    /**
     * Indicates that 'selfChangeAvatar' is a disabled profile feature when using Facebook logins.
     * @see LoginRunner::isProfileFeatureDisabled()
     */
    public static function isProfileFeatureDisabled($feature): bool
    {
        return in_array($feature, ['selfChangeAvatar']);
    }
}