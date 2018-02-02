<?php

namespace Login\TwoStep;

use League\OAuth2\Client\Provider\AbstractProvider;
use Login\LoginFactory;
use Login\LoginRunner;
use Login\LoginTwoStep;
use \Depotwarehouse\OAuth2\Client\Twitch\Provider\Twitch;

/**
 * Twitch Login Provider
 * This will use the twitch client library to authenticate users using Twitch login credentials.
 */
class LoginTwitch extends LoginTwoStep {
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
        $this->client = new Twitch([
            // Required
            'clientId'                  => $clientId,
            'clientSecret'              => $clientSecret,
            'redirectUri'               => $installUrl . 'validate.php?integrationMethod=twitch'
        ]);
    }


    /**
     * @see LoginRunner::hasLoginCredentials()
     */
    public function hasLoginCredentials(): bool {
        return !empty($_REQUEST['code']);
    }


    /**
     * @see LoginRunner::getLoginCredentials()
     */
    public function getLoginCredentials() {
        $url = $this->client->getAuthorizationUrl([
            'scope' => ['user_read', 'user_subscriptions']
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
            new \Fim\Error('twitchLoginFailed', 'Invalid state.');
        }

        session_unset();

        // Try to get an access token (using the authorization code grant)
        $token = $this->client->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        try {
            // We got an access token, let's now get the user's details
            $userInfo = $this->client->getResourceOwner($token)->toArray();


            // store user info...
            $this->loginFactory->user = new \Fim\User([
                'integrationMethod' => 'twitch',
                'integrationId' => $userInfo['id'],
            ]);
            $this->loginFactory->user->resolveAll(); // This will resolve the ID if the user exists.
            $this->loginFactory->user->setDatabase([
                'integrationMethod' => 'twitch',
                'integrationId' => $userInfo['id'],
                'name' => $userInfo['display_name'],
                'email' => $userInfo['email'],
                'avatar' => $userInfo['logo'],
                'profile' => "https://www.twitch.tv/{$userInfo['username']}"
            ]);


            // create groups
            $groups = [];

            // TODO: pagination
            $follows = $this->client->getParsedResponse(
                $this->client->getAuthenticatedRequest(AbstractProvider::METHOD_GET, 'https://api.twitch.tv/helix/users/follows?from_id=' . $userInfo['id'], $token)
            );

            if (isset($follows['data'])) {
                $followedUserIds = [];
                foreach ($follows['data'] AS $user) {
                    $followedUserIds[] = $user['to_id'];
                }

                $followedNamesRequest = $this->client->getParsedResponse(
                    $this->client->getAuthenticatedRequest(AbstractProvider::METHOD_GET, 'https://api.twitch.tv/helix/users?id=' . implode($followedUserIds, '&id='), $token)
                );

                if (isset($followedNamesRequest['data'])) {
                    foreach ($followedNamesRequest['data'] AS $followName) {
                        $groups[] = [
                            'name' => 'Twitch.tv Followers of ' . $followName['display_name'],
                            'avatar' => $followName['profile_image_url']
                        ];
                    }
                }
            }

            \Fim\Database::instance()->enterSocialGroups($this->loginFactory->user->id, $groups);
        } catch (\Exception $e) {
            new \Fim\Error('twitchLoginFailed', 'Could not get token: ' . $e);
        }
    }


    /**
     * Indicates that 'selfChangeAvatar' is a disabled profile feature when using Microsoft logins.
     * @see LoginRunner::isProfileFeatureDisabled()
     */
    public static function isProfileFeatureDisabled($feature): bool {
        return in_array($feature, ['selfChangeAvatar', 'selfChangeProfile']);
    }

}