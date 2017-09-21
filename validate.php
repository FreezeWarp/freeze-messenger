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
   along withis program.  If not, see <http://www.gnu.org/licenses/>. */


/**
 * Establishes a Login
 * @internal This script had for the longest time been a fairly large modification of a login script I wrote some five years ago when I first learned PHP. I ended up rewriting it (almost) completely, but in the process have surely added some weird idiosyncracies.
 * Directives Used Specifically for Obtaining a SessionHash via This Script:
 * @param string userName - The username of the user.
 * @param string password - The password of the user.
 * @param string passwordEncrypt - The ecryption used for obtaining a login. "plaintext" and "md5" are both accepted, but the latter can only be used with vBulletin v3. Other forms of encryption will be possible soon.
 * @param string apiVersions - The version of the API being used to login. It can be comma-seperated if multiple versions will work withe client. 3.0.0 is the only currently accepted version.
 * Standard Directives Required for __ALL__ API Calls:
 * @param string fim3_userId
 * @param string fim3_sessionHash
 *
 */


/******
 * Require base files.
 ******/
require_once(dirname(__FILE__) . '/global.php');



/**
 * When set, login is disabled.
 */
$ignoreLogin = $ignoreLogin ?? false;

/**
 * When set, execution continues with validation present. (That is, we're being included from another PHP script.)
 */
$hookLogin = $hookLogin ?? $apiRequest ?? false;

/**
 * When true, the script will create an access token for an integration login (e.g. with Google).
 */
$doIntegrationLogin = false;



/******
 * Parse request information.
 ******/
$request = fim_sanitizeGPC('r', array(
    'userId' => array('cast' => 'int'),
    'userName' => array(),
    'password' => array(),
    'passwordEncrypt' => array(
        'valid' => array('base64', 'plaintext', 'md5'),
    ),
    'apiVersions' => array(
        'cast' => 'list',
        'filter' => 'string',
        'evaltrue' => true,
    ),
));



/* Default user object.
 * Note: As of now, this object should never be used. In all cases the script either quits or the user object is filled with anonymous information or information corresponding with a real user. However, this object is useful for dev purposes, and if a script wants to use $ignoreLogin. */
$user = new fimUser(0);




if (!$ignoreLogin) {
    /******
     * Process special login information.
     ******/

    /*
     * If an access token has been passed to the PHP directly (through hook login), use them for OAuth authentication.
     */
    if (is_array($hookLogin) && isset($hookLogin['accessToken'])) {
        $_REQUEST['access_token'] = $hookLogin['accessToken'];
        $_GET['access_token'] = $hookLogin['accessToken'];
    }



    /*
     * Process Google/etc. logins.
     * We'll create a new user if needed, and then defer to the grant token code below.
     */
    else if (isset($_REQUEST['googleLogin'])) {
        if (!isset($loginConfig['extraMethods']['google']['clientId'], $loginConfig['extraMethods']['google']['clientSecret'])) {
            new fimError('googleLoginDisabled', 'Google logins are not currently enabled.');
        }
        else {
            require_once('vendor/autoload.php');

            // create our client credentials
            $client = new Google_Client();

            $client->setApplicationName("FlexMessenger Login");
            $client->setDeveloperKey("AIzaSyDxK4wHgx7NAy6NU3CcSsQ2D3JX3K6FwVs");
            $client->setClientId($loginConfig['extraMethods']['google']['clientId']);
            $client->setClientSecret($loginConfig['extraMethods']['google']['clientSecret']);
            $client->setRedirectUri($installUrl . 'validate.php?googleLogin');
            $client->addScope([
                Google_Service_Oauth2::USERINFO_EMAIL,
                Google_Service_Oauth2::USERINFO_PROFILE,
            ]);

            if (isset($_GET['code'])) {
                $client->fetchAccessTokenWithAuthCode($_GET['code']); // verify returned code

                $access_token = $client->getAccessToken();
                if (!$access_token)
                    new fimError('failedLogin', 'We were unable to login to the Google server.');

                // get user info
                $googleUser = new Google_Service_Oauth2($client);
                $userInfo = $googleUser->userinfo->get();
                //var_dump($userInfo);

                if (!$userInfo->getId())
                    new fimError('invalidIntegrationId', 'The Google server did not respond with a valid user ID. Login cannot continue.');

                elseif (!$userInfo->getName())
                    new fimError('invalidIntegrationName', 'The Google server did not respond with a valid user name. Login cannot continue.');

                // store user info...
                $integrationUser = new fimUser([
                    'integrationMethod' => 'google',
                    'integrationId' => $userInfo->getId(),
                ]);
                $integrationUser->resolveAll(); // This will resolve the ID if the user exists.
                $integrationUser->setDatabase([
                    'integrationMethod' => 'google',
                    'integrationId' => $userInfo->getId(),
                    'email' => $userInfo->getEmail(),
                    'name' => $userInfo->getName(),
                    'avatar' => $userInfo->getPicture()
                ]); // If the ID wasn't resolved above, a new user will be created.

                $doIntegrationLogin = true;
            }

            else {
                // redirect to the login URL
                $auth_url = $client->createAuthUrl();
                header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
                die();
            }
        }
    }



    /*
     * Begin OAuth Server
     * We have not yet added any Grant Types. We add these instead in the next section.
     */
    require_once('functions/oauth2-server-php/src/OAuth2/Autoloader.php');
    OAuth2\Autoloader::register();

    /**
     * How our OAuth data is stored.
     */
    $oauthStorage = new OAuth2\Storage\FIMDatabaseOAuth($database, 'fimError');

    /**
     * How our OAuth processes requests.
     */
    $oauthServer = new OAuth2\Server($oauthStorage); // Pass a storage object or array of storage objects to the OAuth2 server class

    /**
     * Our OAuth request, created from the globals passed to this page.
     */
    $oauthRequest = OAuth2\Request::createFromGlobals();



    /*
     * Process login information previously set for Google, etc.
     */
    if ($doIntegrationLogin) {
        $oauthRequest->request['client_id'] = 'IntegrationLogin'; // Pretend we have this.
        $oauthRequest->request['grant_type'] = 'integrationLogin'; // Pretend we have this. It isn't used for verification.
        $oauthRequest->server['REQUEST_METHOD'] =  'POST'; // Pretend we're a POST request for the OAuth library. A better solution would be to forward, but honestly, it's hard to see the point.
        $oauthServer->addGrantType($userC = new OAuth2\GrantType\IntegrationLogin($oauthStorage, $integrationUser));

        $oauthResponse = $oauthServer->handleTokenRequest($oauthRequest);

        if ($oauthResponse->getStatusCode() !== 200) {
            new fimError($oauthResponse->getParameters()['error'], $oauthResponse->getParameters()['error_description']);
        }
        else {
            header('Location: ' . $installUrl . '?sessionHash=' . $oauthResponse->getParameter('access_token'));
            die();
        }

    }


    elseif (isset($_REQUEST['refresh_token'])) {
        $oauthServer->addGrantType($userC = new OAuth2\GrantType\RefreshToken($oauthStorage, [
            'always_issue_new_refresh_token' => true
        ]));
        $oauthResponse = $oauthServer->handleTokenRequest($oauthRequest);

        die(new ApiData([
            'login' => [
                'access_token' => $oauthResponse->getParameter('access_token'),
                'refresh_token' => $oauthResponse->getParameter('refresh_token'),
                'expires' => $oauthResponse->getParameter('expires_in'),
            ]
        ]));
    }


    /*
     * Verify an access token. Typically used for API logins.
     */
    elseif (isset($_REQUEST['access_token'])) {
        if (!$attempt = $oauthServer->verifyResourceRequest($oauthRequest)) {
            new fimError($oauthServer->getResponse()->getParameter('error'), $oauthServer->getResponse()->getParameter('error_description'));
            die();
        }

        else {
            $token = $oauthServer->getResourceController()->getToken();

            $user = fimUserFactory::getFromId((int) $token['user_id']);

            if ($token['anon_id'] ?? false)
                $user->setAnonId($token['anon_id']);

            $user->setSessionHash($token['access_token']);
            $user->setClientCode($token['client_id']);
            $tokenExpires = $token['expires'];
        }
    }


    /**
     * grant_type is set (and not "access_token"), so issue a new access token.
     */
    elseif (isset($_REQUEST['grant_type'])) {
        $database->cleanSessions();

         /* Depending on which grant_type is set, we interact with the OAuth layer a little bit differently. */
        switch ($_REQUEST['grant_type']) {
            case 'password': // User authentication
                $oauthServer->addGrantType($userC = new OAuth2\GrantType\UserCredentials($oauthStorage));
                break;

            case 'anonymous':
                $oauthServer->addGrantType($userC = new OAuth2\GrantType\Anonymous($oauthStorage));
                break;
        }


        $oauthResponse = $oauthServer->handleTokenRequest($oauthRequest);
        $user = fimUserFactory::getFromId((int) $userC->getUserId());
        $user->setSessionHash($oauthResponse->getParameter('access_token'));
        $user->setClientCode($oauthResponse->getParameter('client_id'));
        $tokenExpires = $oauthResponse->getParameter('expires_in');
        $refreshToken = $oauthResponse->getParameter('refresh_token');

        if ($_REQUEST['grant_type'] === 'anonymous') {
            $user->setAnonId($userC->getAnonymousUserId());
        }


        if ($oauthResponse->getStatusCode() !== 200) {
            new fimError($oauthResponse->getParameters()['error'], $oauthResponse->getParameters()['error_description']);
        }
    }


    /*
     * No verification method found.
     */
    else {
        new fimError('noLogin', 'Please specify login credentials.');
    }



    /*
     * Send Data to API unless we are continuing the script.
     */
    if (!$hookLogin) {
        $user->resolveAll();

        $apiData = new ApiData();
        $apiData->replaceData(array(
            'login' => array(
                'access_token' => $user->sessionHash,
                'refresh_token' => $refreshToken ?? 0,
                'expires' => $tokenExpires ?? 0,
                'userData' => array_merge([
                    'permissions' => $user->getPermissionsArray()
                ], fim_castArrayEntry(
                    fim_objectArrayFilterKeys(
                        $user,
                        ['id', 'anonId', 'name', 'nameFormat', 'mainGroupId', 'socialGroupIds', 'avatar', 'profile', 'parentalAge', 'parentalFlags', 'messageFormatting', 'defaultRoomId', 'options', 'ignoredUsers', 'friendedUsers', 'favRooms', 'watchRooms']
                    ), ['socialGroupIds', 'parentalFlags', 'ignoredUsers', 'friendedUsers', 'favRooms', 'watchRooms'], 'ApiOutputList'
                ))
            ),
        ));

        die($apiData);
    }
}


$database->registerUser($user);
define('FIM_LOGINRUN', true);
?>