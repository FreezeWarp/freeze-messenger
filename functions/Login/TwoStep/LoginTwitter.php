<?php

namespace Login\TwoStep;

use Abraham\TwitterOAuth\TwitterOAuth;
use Login\LoginFactory;
use Login\LoginTwoStep;

/**
 * Twitter Login Provider
 * This will use the Twitter client library to authenticate users using Twitter login credentials.
 */
class LoginTwitter extends LoginTwoStep {
    /**
     * @var TwitterOAuth The TwitterOAuth instance.
     */
    public $client;

    /**
     * LoginTwitter constructor.
     *
     * @param $loginFactory LoginFactory The LoginFactory instance used to create this object.
     * @param $clientId     string The Twitter API client ID.
     * @param $clientSecret string The Twitter API client secret.
     */
    public function __construct(LoginFactory $loginFactory, string $clientId, string $clientSecret) {
        parent::__construct($loginFactory);

        // create our client credentials
        $this->client = new TwitterOAuth($clientId, $clientSecret);
    }


    /**
     * @see LoginRunner::hasLoginCredentials()
     */
    public function hasLoginCredentials(): bool {
        return isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token'];
    }


    /**
     * @see LoginRunner::getLoginCredentials()
     */
    public function getLoginCredentials() {
        global $installUrl;

        $request_token = $this->client->oauth('oauth/request_token', array('oauth_callback' => $installUrl . 'validate.php?integrationMethod=twitter'));

        session_start();
        $_SESSION['oauth_token'] = $request_token['oauth_token'];
        $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
        session_commit();

        header('Location: ' . $this->client->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token'])));
        die();
    }


    /**
     * @see LoginRunner::setUser()
     */
    public function setUser() {
        session_start();
        $request_token = [];
        $request_token['oauth_token'] = $_SESSION['oauth_token'];
        $request_token['oauth_token_secret'] = $_SESSION['oauth_token_secret'];
        session_unset();
        $this->client->setOauthToken($request_token['oauth_token'], $request_token['oauth_token_secret']);

        $access_token = $this->client->oauth("oauth/access_token", ["oauth_verifier" => $_REQUEST['oauth_verifier']]);
        $this->client->setOauthToken($access_token['oauth_token'], $access_token['oauth_token_secret']);

        $userInfo = $this->client->get("account/verify_credentials");

        // store user info...
        $this->loginFactory->user = new \fimUser([
            'integrationMethod' => 'twitter',
            'integrationId' => (int) $userInfo->id,
        ]);
        $this->loginFactory->user->resolveAll(); // This will resolve the ID if the user exists.
        $this->loginFactory->user->setDatabase([
            'integrationMethod' => 'twitter',
            'integrationId' => $userInfo->id,
            'name' => $userInfo->name,
            'avatar' => str_replace('_normal', '', $userInfo->profile_image_url_https),
            'profile' => $userInfo->url
            //bio $userInfo->description
        ]);
    }


    /**
     * Indicates that 'selfChangeAvatar' and 'selfChangeProfile' are disabled profile feature when using Twitter logins.
     * @see LoginRunner::isProfileFeatureDisabled()
     */
    public static function isProfileFeatureDisabled($feature): bool {
        return in_array($feature, ['selfChangeAvatar', 'selfChangeProfile']);
    }

}