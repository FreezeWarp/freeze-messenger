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
require(dirname(__FILE__) . '/functions/fim_uac.php');

if(!isset($ignoreLogin)) $ignoreLogin = false; // pages without login
if(!isset($apiRequest)) $apiRequest = false; // /api/ functions
if(!isset($streamRequest)) $streamRequest = false; // /apiRequest/ functions
if(!isset($hookLogin)) $hookLogin = false; // pages with custom login


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
  'fim3_sessionHash' => array(),
  'fim3_userId' => array('cast' => 'int',),
));


///* Some Pre-Stuff *///
$sessionHash = '';
$loginMethod = false;
$goodVersion = false;

$loginDefs['syncMethods'] = array('phpbb', 'vbulletin3', 'vbulletin4');

/* Default user object.
 * Note: As of now, this object should never be used. In all cases the script either quites or the user object is filled with anonymous information or information corresponding with a real user. However, this object is useful for dev purposes, and if a script wants to use $ignoreLogin. */
$user = array(
  'userId' => 0,
  'userName' => 'MISSINGNO.',
  'adminPrivs' => 0, // Nothing
  'userPrivs' => 0, // Allowed, but nothing else.
);





///* Determine How to Verify the Login in the Next Section *///

if (is_array($hookLogin)) {
  if (isset($hookLogin['userName'], $hookLogin['password'])) {
    $request['userName'] = $hookLogin['userName'];
    $request['password'] = $hookLogin['password'];

    $loginMethod = 'credentials';
  }

  if (isset($hookLogin['sessionHash'], $hookLogin['userId'])) {
    $request['fim3_sessionHash'] = $hookLogin['sessionHash'];
    $request['fim3_userId'] = $hookLogin['userId'];

    $loginMethod = 'session';
  }
}

elseif ($ignoreLogin === true) { // Used for APIs that explicitly.
  // We do nothing.
}

elseif ($apiRequest !== true && $streamRequest !== true) { // Validate.php called directly.
  /* Ensure that the client is compatible with the server. Note that this check is only performed when getting a sessionHash, not when doing any other action. */
  foreach ($request['apiVersions'] AS $version) {
    if ($version == 30000) $goodVersion = true; // This the same as version 3.0.0
  }

  if (!$goodVersion)
    throw new Exception('The server API is incompatible with the client API.');
  elseif (!$config['anonymousUserId'] && !isset($request['userName'], $request['password']) && !isset($request['userId'], $request['password']))
    throw new Exception('Invalid login parameters: validate requires either [userName or userId] together with password.');

  $loginMethod = 'credentials';
}

elseif ($apiRequest === true || $streamRequest === true) { // Validate.php called from API.
  if (!isset($request['fim3_sessionHash'], $request['fim3_userId'])) {
    throw new Exception('A sessionHash and userId are required for all API requests. See the API documentation on obtaining these using validate.php.');
  }

  $loginMethod = 'session';
}




///* Verify the Login *///
if ($loginMethod === 'credentials') {
  if (isset($request['userName']) || isset($request['userId'])) {
    if (!isset($request['password'])) throw new Exception('passwordRequired');
    else {
      if ($request['passwordEncrypt'] === 'base64') $request['password'] = base64_decode($request['password']);

      if ($request['userId']) {
        $userPre = $database->getUsers(array(
          'userIds' => array($request['userId']),
          'includePasswords' => true
        ))->getAsArray(false);
      }
      elseif ($request['userName']) {
        $userPre = $database->getUsers(array(
          'userNames' => array($request['userName']),
          'includePasswords' => true
        ))->getAsArray(false);
      }

      // If this fails, and we support integration, try again using the getUserFromUAC function, which will automatically create vanilla data (and return that data to us).
      if (!count($userPre) && in_array($loginConfig['method'], $loginDefs['syncMethods'])) {
        $userPre = $integrationDatabase->getUserFromUAC(array(
          'userName' => $request['userName'],
          'userId' => $request['userId']
        ))->getAsArray(false);
      }

      if ($userPre['userId'] == $config['anonymousUserId']) throw new Exception('anonAccount');
      elseif (processLogin($userPre, $request['password'], 'plaintext')) $user = $userPre; // Verify that the submitted password matches the stored one.
      else throw new Exception('invalidLogin');

      $user['anonId'] = 0;

      $sessionHash = $database->createSession($user['userId']);
    }
  }
  elseif ($config['anonymousUserId']) {
    $user = $database->getUsers(array(
      'userIds' => array($config['anonymousUserId'])
    ))->getAsArray(false);
    $user['anonId'] = rand(1, 10000);

    $sessionHash = $database->createSession($config['anonymousUserId'], $user['anonId']);
  }
  else throw new Exception('loginRequired');
}

elseif ($loginMethod === 'session') {
  $session = $database->getSessions(array(
    'sessionHashes' => array($request['fim3_sessionHash'])
  ))->getAsArray(false);

  if (!count($session)) throw new Exception('invalidSession');
  elseif ((int) $session['userId'] !== (int) $request['fim3_userId']) throw new Exception('sessionMismatchUserId'); // The userid sent has to be the same one in the DB. In theory we could just not require a userId be specified, but there are benefits to this alternative. For instance, this eliminates some forms of injection-based session fixation.
  elseif ($session['sessionBrowser'] !== $_SERVER['HTTP_USER_AGENT']) throw new Exception('sessionMismatchBrowser'); // Require the UA match that of the one used to establish the session. Smart clients are encouraged to specify their own with their client name and version.
  elseif ($session['sessionIp'] !== $_SERVER['REMOTE_ADDR']) throw new Exception('sessionMismatchIp'); // This is a tricky one, but generally the most certain to block any attempted forgeries. That said, IPs can, /theoretically/ be spoofed.
  else {
    $user = $session; // Mostly identical, though a few additional properties do exist.

    if ($session['sessionTime'] < time() - 300) $database->refreshSession($session['sessionId']); // If five minutes have passed since the session has been generated, update it.
  }
}




///* Process User Information *///
define('FIM_ACTIVEUSERID', $user['userId']); // For certain critical functions, this constant should be used instead of the $user object to prevent plugins from modifying the userID. A minor issue when plugins can basically access everything else, but a small precaution regardless.

if ($user['anonId']) $user['userName'] .= $user['anonId']; // If anonymous, make the username distinct (TODO: add special randomised colouring, maybe)

if ($user['userId'] < 0 || ($user['userId'] === 0 && !$ignoreLogin)) throw new Exception('badUserData'); // Internally, user IDs less than 0 may be used as placeholders. To avoid issues, prevent anything lower than 0. Additionally, "0" itself should only be used when ignoreLogin is active.

// If certain features are disabled, remove user priviledges. The bitfields should be maintained, however, for when a feature is reenabled.
if (!$config['userRoomCreation']) $user['userPrivs'] &= ~USER_PRIV_CREATE_ROOMS;
if (!$config['userPrivateRoomCreation']) $user['userPrivs'] &= ~(USER_PRIV_PRIVATE_ALL + USER_PRIV_PRIVATE_FRIENDS); // Note: does not disable the usage of existing private rooms. Use "disablePrivateRooms" for this.

if (fim_isSuper()) { // Super admins should get all permissions. The bitfields up to 32768 (2^15) are set to true.
  $user['adminPrivs'] = 65535;
  $user['userPrivs'] = 65535;
}


/* The following defines each individual user's options via an associative array. It is highly recommended this be used to reference settings.
 * TODO-ish: Long term, a user class should be created, and this will be applied to every user. Right now, functions like fim_hasPermission, which operate on data directly form the database, must still check against the adminPriv and userPriv columns. */
$user['adminDefs'] = array(
  'protected' => (bool) ($user['adminPrivs'] & ADMIN_PROTECTED), // This the "untouchable" flag, but that's more or less all it means.

  'modPrivs' => (bool) ($user['adminPrivs'] & ADMIN_GRANT), // This effectively allows a user to give himself everything else below. It is also used for admin functions that can not be delegated effectively -- such as modifying the site configuration.
  'modRooms' => (bool) ($user['adminPrivs'] & ADMIN_GRANT + ADMIN_ROOMS), // Alter rooms -- kicking users, delete posts, and change hidden/official status
  'modPrivate' => (bool) ($user['adminPrivs'] & ADMIN_GRANT + ADMIN_VIEW_PRIVATE), // View private communications.
  'modUsers' => (bool) ($user['adminPrivs'] & ADMIN_GRANT + ADMIN_USERS), // Site-wide bans, mostly.
  'modFiles' => (bool) ($user['adminPrivs'] & ADMIN_GRANT + ADMIN_FILES), // File Uploads
  'modCensor' => (bool) ($user['adminPrivs'] & ADMIN_GRANT + ADMIN_CENSOR), // Censor

  /* Should Generally Go Together */
  'modPlugins' => (bool) ($user['adminPrivs'] & ADMIN_GRANT + ADMIN_PLUGINS), // Plugins
  'modTemplates' => (bool) ($user['adminPrivs'] & ADMIN_GRANT + ADMIN_INTERFACES), // Templates
);

$user['userDefs'] = array(
  'view' => (bool) ($user['userPrivs'] & USER_PRIV_VIEW), // Is not banned
  'post' => (bool) ($user['userPrivs'] & USER_PRIV_POST),
  'changeTopic' => (bool) ($user['userPrivs'] & USER_PRIV_TOPIC),
  'createRooms' => (bool) ($user['userPrivs'] & USER_PRIV_CREATE_ROOMS), // May create rooms
  'privateRoomsFriends' => (bool) ($user['userPrivs'] & USER_PRIV_PRIVATE_FRIENDS), // May create private rooms (friends only)
  'privateRoomsAll' => (bool) ($user['userPrivs'] & USER_PRIV_PRIVATE_FRIENDS && $user['userPrivs'] & USER_PRIV_PRIVATE_ALL), // May create private rooms (anybody)
  'roomsOnline' => (bool) ($user['userPrivs'] & USER_PRIV_ACTIVE_USERS), // May see rooms online.
  'postCounts' => (bool) ($user['userPrivs'] & USER_PRIV_POST_COUNTS), // May see post counts.
);





/* API Output */

if ($apiRequest !== true && $streamRequest !== true && $ignoreLogin !== true && $hookLogin === false) {
  $xmlData = array(
    'login' => array(
      'sessionHash' => $sessionHash,
      'anonId' => $user['anonId'],
      'defaultRoomId' => (int) (isset($_GET['room']) ? $_GET['room'] :
        (isset($user['defaultRoom']) ? $user['defaultRoom'] :
          ($config['defaultRoom']))), // Get the room we're on. If there is a $_GET variable, use it, otherwise the user's "default", or finally just main.

      'userData' => array(
        'userName' => ($user['userName']),
        'userId' => (int) $user['userId'],
        'userGroup' => (int) $user['userGroup'],
        'avatar' => (isset($user['avatar']) ? $user['avatar'] :
          (isset($avatarBase) ? $avatarBase : '')),
        'profile' => (isset($user['profile']) ? $user['profile'] : ''),
        'socialGroups' => ($user['socialGroups']),
        'startTag' => ($user['userFormatStart']),
        'endTag' => ($user['userFormatEnd']),
        'defaultFormatting' => array(
          'color' => ($user['defaultColor']),
          'highlight' => ($user['defaultHighlight']),
          'fontface' => ($user['defaultFontface']),
          'general' => (int) $user['defaultFormatting']
        ),
        'parentalFlags' => $user['parentalFlags'],
        'parentalAge' => $user['parentalAge'],
      ),
      'userPermissions' => $user['userDefs'],
      'adminPermissions' => $user['adminDefs'],
    ),
  );

  die(fim_outputApi($xmlData));
}


define('FIM_LOGINRUN', true);
?>