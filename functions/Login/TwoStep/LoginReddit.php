<?php

namespace Login\TwoStep;

use Google\Auth\HttpHandler\Guzzle5HttpHandler;
use Login\LoginFactory;
use Login\LoginTwoStep;
use Rudolf\OAuth2\Client\Provider\Reddit;

class LoginReddit extends LoginTwoStep {
    public $loginFactory;

    public $client;

    public function __construct(LoginFactory $loginFactory, $clientId, $clientSecret) {
        global $installUrl;

        parent::__construct($loginFactory);

        $this->client = new Reddit([
            'clientId'     => $clientId,
            'clientSecret' => $clientSecret,
            'userAgent'    => 'online:flexchat:v0.1, (by /u/freezewarp)',
            'redirectUri'  => $installUrl . 'validate.php?integrationMethod=reddit',
        ]);
    }

    public function hasLoginCredentials(): bool {
        return isset($_REQUEST['code']);
    }

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

    public function setUser() {
        global $database;

        if (!session_id())
            session_start();

        if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
            var_dump($_SESSION); die();
            session_unset();
            new \fimError('redditLoginFailed', 'Invalid state.');
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
            new \fimError('redditLoginFailed', 'Could not get token: ' . $e);
        }


        /* Store User Info */
        $this->loginFactory->user = new \fimUser([
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


        /* Add User Groups Based On Subscriptions */
        $subscriptions = $this->client->getParsedResponse($this->client->getAuthenticatedRequest(
            'GET',
            'https://oauth.reddit.com/api/v1/me/karma',
            $token
        ));

        if (isset($subscriptions['data'])) {
            $subscriptionNames = [];

            foreach ($subscriptions['data'] AS $subscription) {
                $subscriptionNames[] = 'Subscribers of /r/' . $subscription['sr'];
                @$database->createSocialGroup('Subscribers of /r/' . $subscription['sr']);
            }


            $dbGroupIds = $database->select([
                'socialGroups' => 'id, name'
            ], ['name' => $database->in($subscriptionNames)])->getColumnValues('id');

            $database->autoQueue(true);
            foreach ($dbGroupIds AS $groupId) {
                @$database->enterSocialGroup($groupId, $this->loginFactory->user);
            }
            @$database->autoQueue(false);
        }
    }

}