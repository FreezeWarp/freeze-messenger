<?php
/* FreezeMessenger Copyright © 2014 Joseph Todd Parsons

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
        'cast' => 'jsonList',
        'filter' => 'string',
        'evaltrue' => true,
    ),
));


///* Some Pre-Stuff *///
$loginDefs['syncMethods'] = array('phpbb', 'vbulletin3', 'vbulletin4');

/* Default user object.
 * Note: As of now, this object should never be used. In all cases the script either quits or the user object is filled with anonymous information or information corresponding with a real user. However, this object is useful for dev purposes, and if a script wants to use $ignoreLogin. */
$user = array(
    'userId' => 0,
    'userName' => 'MISSINGNO.',
    'privs' => 0, // Nothing
);

/* If a username and password have been passed to the PHP directly, use them for OAuth authentication. */
if (is_array($hookLogin) && isset($hookLogin['userName'], $hookLogin['password'])) {
    $_POST['grant_type'] = 'client_credentials';
    $_POST['username'] = $hookLogin['userName'];
    $_POST['password'] = $hookLogin['password'];
}


/* Begin OAuth Server
 * We have not yet added any Grant Types. We add these instead in the next section. */
require_once('functions/oauth2-server-php/src/OAuth2/Autoloader.php');
OAuth2\Autoloader::register();

$oauthStorage = new OAuth2\Storage\FIMDatabaseOAuth($database);
$oauthServer = new OAuth2\Server($oauthStorage); // Pass a storage object or array of storage objects to the OAuth2 server class
$oauthRequest = OAuth2\Request::createFromGlobals();

/* If $ignoreLogin is set, we run things without processing any logins. */
if ($ignoreLogin) {

} /* If grant_type is not set, we granting a token, not evaluating. */
else if (isset($_REQUEST['grant_type'])) {
    /* Depending on which grant_type is set, we interact with the OAuth layer a little bit differently. */
    switch ($_REQUEST['grant_type']) {
        case 'password': // User authentication
            $oauthServer->addGrantType($userC = new OAuth2\GrantType\UserCredentials($oauthStorage));
            break;

        case 'client_credentials':
            $oauthServer->addGrantType($userC = new OAuth2\GrantType\ClientCredentials($oauthStorage));
            break;

        case 'anonymous':
            global $anonId; // Because we don't want to significantly rearchitect the OAuth code, we use a global that is set by the Anonymous GrantType, and read by the FIMDatabase Storage, in order to support anonymous users
            $oauthServer->addGrantType($userC = new OAuth2\GrantType\Anonymous($oauthStorage));
            break;
    }

    $oauthResponse = $oauthServer->handleTokenRequest($oauthRequest);
    $user = new fimUser((int) $userC->getUserId());

    if ($oauthResponse->getStatusCode() === 200) {
        /* Send Data to API */
        $apiData = new apiData();
        $apiData->replaceData(array(
            'login' => array(
                'access_token' => $oauthResponse->getParameter('access_token'),
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
        echo $apiData;
    } else {
        $oauthResponse->send();
    }

    die();
} /* If access_token has been passed, then we are evaluating a token. */
elseif (isset($_REQUEST['access_token'])) {
    if (!$attempt = $oauthServer->verifyResourceRequest($oauthRequest)) {
        $oauthServer->getResponse()->send();
        die();
    } else {//var_dump($oauthServer->getResourceController()->getAccessTokenData($oauthRequest, $oauthServer->getResponse())['user_id']); die();
        $user = new fimUser((int)$oauthServer->getResourceController()->getAccessTokenData($oauthRequest, $oauthServer->getResponse())['user_id']);
//      var_dump($user);
    }
}


define('FIM_LOGINRUN', true);
?>