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
 * @global DatabaseSQL $database
 * @global DatabaseSQL $integrationDatabase
 */


/******
 * Require base files.
 ******/
require_once(__DIR__ . '/global.php');



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
    $loginFactory = new \Login\LoginFactory($oauthRequest, $oauthStorage, $oauthServer, $integrationDatabase);



    if ($loginFactory->hasLogin()) {
        $loginFactory->getLogin();
        $loginFactory->apiResponse();
        die();
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