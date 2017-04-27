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

///* Require Base *///

require_once(dirname(__FILE__) . '/global.php');

if (!isset($ignoreLogin)) $ignoreLogin = false; // pages without login
if (!isset($apiRequest)) $apiRequest = false; // /api/ functions
if (!isset($streamRequest)) $streamRequest = false; // /apiRequest/ functions
if (!isset($hookLogin)) $hookLogin = false; // pages with custom login


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


///* Some Pre-Stuff *///
$loginDefs['syncMethods'] = array('phpbb', 'vbulletin3', 'vbulletin4');

/* Default user object.
 * Note: As of now, this object should never be used. In all cases the script either quits or the user object is filled with anonymous information or information corresponding with a real user. However, this object is useful for dev purposes, and if a script wants to use $ignoreLogin. */
$user = new fimUser(0);

/* If a username and password have been passed to the PHP directly, use them for OAuth authentication. */
if (is_array($hookLogin) && isset($hookLogin['accessToken'])) {
    $_REQUEST['access_token'] = $hookLogin['accessToken'];
    $_GET['access_token'] = $hookLogin['accessToken'];
}


/* Begin OAuth Server
 * We have not yet added any Grant Types. We add these instead in the next section. */
require_once('functions/oauth2-server-php/src/OAuth2/Autoloader.php');
OAuth2\Autoloader::register();

$oauthStorage = new OAuth2\Storage\FIMDatabaseOAuth($database, fimError);
$oauthServer = new OAuth2\Server($oauthStorage); // Pass a storage object or array of storage objects to the OAuth2 server class
$oauthRequest = OAuth2\Request::createFromGlobals();

/* If $ignoreLogin is set, we run things without processing any logins. */
if ($ignoreLogin) {

}

/* If grant_type is not set, we granting a token, not evaluating. */
else if (isset($_REQUEST['grant_type']) && $_REQUEST['grant_type'] !== 'access_token') {
    $database->cleanSessions();
    /* Depending on which grant_type is set, we interact with the OAuth layer a little bit differently. */
    switch ($_REQUEST['grant_type']) {
        case 'password': // User authentication
            $oauthServer->addGrantType($userC = new OAuth2\GrantType\UserCredentials($oauthStorage));
            break;

        case 'anonymous':
            global $anonId; // Because we don't want to significantly rearchitect the OAuth code, we use a global that is set by the Anonymous GrantType, and read by the FIMDatabase Storage, in order to support anonymous users
            $oauthServer->addGrantType($userC = new OAuth2\GrantType\Anonymous($oauthStorage));
            break;
    }

    $oauthResponse = $oauthServer->handleTokenRequest($oauthRequest);
    $user = fimUserFactory::getFromId((int) $userC->getUserId());
    $user->sessionHash = $oauthResponse->getParameter('access_token');
    $user->clientCode = $oauthResponse->getParameter('client_id');

    if ($oauthResponse->getStatusCode() === 200) {
        /* Send Data to API */
        $user->resolveAll();

        $apiData = new apiData();
        $apiData->replaceData(array(
            'login' => array(
                'access_token' => $user->sessionHash,
                'anonId' => $user->anonId,
                'defaultRoomId' => $user->defaultRoomId,
                'userData' => array(
                    'userId' => $user->id,
                    'userName' => $user->name,
                    'userNameFormat' => $user->userNameFormat,
                    'userGroupId' => $user->mainGroupId,
                    'socialGroupIds' => new apiOutputList($user->socialGroupIds),
                    'avatar' => $user->avatar,
                    'profile' => $user->profile,
                    'messageFormatting' => $user->messageFormatting,
                    'parentalFlags' => new apiOutputList($user->parentalFlags),
                    'parentalAge' => $user->parentalAge,
                ),
                'permissions' => $user->getPermissionsArray()
            ),
        ));

        if (!$hookLogin)
            echo $apiData;
    }

    else {
        throw new fimError($oauthResponse->getParameters()['error'], $oauthResponse->getParameters()['error_description']);
    }

    if (!$hookLogin)
        die();
}

/* If access_token has been passed, then we are evaluating a token. */
elseif (isset($_REQUEST['access_token'])) {
    if (!$attempt = $oauthServer->verifyResourceRequest($oauthRequest)) {
        $oauthServer->getResponse()->send();
        die();
    }

    else {
        $user = new fimUser((int) $oauthServer->getResourceController()->getToken()['user_id']);
    }
}

else {
    throw new fimError('noLogin', 'Please specify login credentials.');
}


$database->registerUser($user);
define('FIM_LOGINRUN', true);
?>