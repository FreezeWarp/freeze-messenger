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
require_once(dirname(__FILE__) . '/functions/LoginFactory.php');



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

    /**
     * A factory for performing integration logins.
     */
    $loginFactory = new LoginFactory($oauthRequest, $oauthStorage, $oauthServer);



    if ($loginFactory->hasLogin()) {
        $loginFactory->getLogin();
        $loginFactory->apiResponse();
        die();
    }


    /**
     * grant_type is set, so issue a new access token.
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

            case 'access_token':
                $oauthServer->addGrantType($userC = new OAuth2\GrantType\AccessToken($oauthStorage));
                break;

            case 'refresh_token':
                $oauthServer->addGrantType($userC = new OAuth2\GrantType\RefreshToken($oauthStorage, [
                    'always_issue_new_refresh_token' => true
                ]));
                break;
        }


        // Process the token request from $_REQUEST variables.
        $oauthResponse = $oauthServer->handleTokenRequest($oauthRequest);

        // Error, if needed
        if ($oauthResponse->getStatusCode() !== 200) {
            new fimError($oauthResponse->getParameters()['error'], $oauthResponse->getParameters()['error_description']);
        }

        // Get the user object from a user ID
        $user = fimUserFactory::getFromId((int) $userC->getUserId());
        $user->setSessionHash($oauthResponse->getParameter('access_token')); // Mainly for logging.
        $user->setClientCode($oauthResponse->getParameter('client_id')); // Mainly for logging.
        $tokenExpires = $oauthResponse->getParameter('expires_in');
        $refreshToken = $oauthResponse->getParameter('refresh_token');

        // Set the anonymous user ID, if applicable
        if ($_REQUEST['grant_type'] === 'anonymous') {
            $user->setAnonId($userC->getAnonymousUserId());
        }

        // Send the User and Session Data to the Client
        $user->resolveAll();
        die(new ApiData(array(
            'login' => array(
                'access_token' => $user->sessionHash,
                'refresh_token' => $refreshToken ?? '',
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
        )));
    }


    /*
     * Verify an access token. Typically used for API logins.
     */
    elseif (isset($_REQUEST['access_token'])) {
        // Verify the Access Token
        if ($attempt = $oauthServer->verifyResourceRequest($oauthRequest)) {
            // Get the Token
            $token = $oauthServer->getResourceController()->getToken();

            // Get the User Object
            $user = fimUserFactory::getFromId((int) $token['user_id']);
            $user->setSessionHash($token['access_token']);
            $user->setClientCode($token['client_id']);
            if ($token['anon_id'] ?? false)
                $user->setAnonId($token['anon_id']);
        }

        // Error on Failure
        else {
            new fimError($oauthServer->getResponse()->getParameter('error'), $oauthServer->getResponse()->getParameter('error_description'));
            die();
        }
    }


    /*
     * No verification method found.
     */
    else {
        new fimError('noLogin', 'Please specify login credentials.');
    }



    if ($hookLogin) {
        $database->registerUser($user);
        define('FIM_LOGINRUN', true);
    }
    else {
        die('nocontinue');
    }
}
?>