<?php

namespace Login\TwoStep;

use Login\LoginFactory;
use Login\LoginTwoStep;
use Rudolf\OAuth2\Client\Provider\Reddit;

/**
 * Reddit Login Provider
 * This will use the Reddit client library to authenticate users using Reddit login credentials.
 */
class LoginReddit extends LoginTwoStep {
    /**
     * @var Reddit The Reddit instance.
     */
    public $client;


    /**
     * LoginReddit constructor.
     *
     * @param $loginFactory LoginFactory The LoginFactory instance used to create this object.
     * @param $clientId     string The Reddit API client ID.
     * @param $clientSecret string The Reddit API client secret.
     */
    public function __construct(LoginFactory $loginFactory, string $clientId, string $clientSecret) {
        global $installUrl;

        parent::__construct($loginFactory);

        $this->client = new Reddit([
            'clientId'     => $clientId,
            'clientSecret' => $clientSecret,
            'userAgent'    => 'online:flexchat:v0.4, (by /u/freezewarp)',
            'redirectUri'  => $installUrl . 'validate.php?integrationMethod=reddit',
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
            'scope'       => ['identity', 'mysubreddits']
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
            new \Fim\Error('redditLoginFailed', 'Invalid state.');
        }
        session_unset();

        // Try to get an access token (using the authorization code grant)
        $token = $this->client->getAccessToken('authorization_code', [
            'code' => $_GET['code'],
            'state' => $_GET['state']
        ]);


        /* Get User Info */
        try {
            // We got an access token, let's now get the user's details
            $userInfo = $this->client->getResourceOwner($token);
        } catch (\Exception $e) {
            new \Fim\Error('redditLoginFailed', 'Could not get token: ' . $e);
        }


        /* Store User Info */
        $this->loginFactory->user = new \Fim\User([
            'integrationMethod' => 'reddit',
            'integrationId' => $userInfo['id'],
        ]);
        $this->loginFactory->user->resolveAll(); // This will resolve the ID if the user exists.
        $this->loginFactory->user->setDatabase([
            'integrationMethod' => 'reddit',
            'integrationId' => $userInfo['id'],
            'name' => $userInfo['name'],
        ]);
        //todo: $userInfo['over_18']


        header('content-type: text/plain');

        /* Add User Groups Based On Subscriptions */
        $groups = [];

        do {
            $subscriptions = $this->client->getParsedResponse($this->client->getAuthenticatedRequest(
                'GET',
                'https://oauth.reddit.com/subreddits/mine/subscriber?limit=100&sr_detail=false' . (!empty($after) ? '&after=' . $after : ''),
                $token
            ));

            $after = $subscriptions['data']['after'];

            if (!empty($subscriptions['data']['children'])) {
                foreach ($subscriptions['data']['children'] AS $subscription) {
                    $groups[] = [
                        'name'   => 'Reddit Subscribers of /r/' . $subscription['data']['display_name'],
                        'avatar' => $subscription['data']['icon_img']
                    ];
                }
            }
        } while (!empty($after));

        if (!empty($groups)) {
            \Fim\Database::instance()->enterSocialGroups($this->loginFactory->user->id, $groups);
        }
    }
}