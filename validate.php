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
 * Note: As of now, this object should never be used. In all cases the script either quits or the user object is filled with anonymous information or information corresponding with a real user. However, this object is useful for dev purposes, and if a script wants to use $ignoreLogin. */
$user = array(
  'userId' => 0,
  'userName' => 'MISSINGNO.',
  'privs' => 0, // Nothing
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
    if ($version == 10000) $goodVersion = true; // This the same as version 1.0.0
  }

  if (!$goodVersion)
    trigger_error('The server API is incompatible with the client API.', E_USER_ERROR);
  elseif (!$config['anonymousUserId'] && !isset($request['userName'], $request['password']) && !isset($request['userId'], $request['password']))
    trigger_error('Invalid login parameters: validate requires either [userName or userId] together with password.', E_USER_ERROR);

  $loginMethod = 'credentials';
}

elseif ($apiRequest === true || $streamRequest === true) { // Validate.php called from API.
  if (!isset($request['fim3_sessionHash'])) {
    trigger_error('A sessionHash is required for all API requests. See the API documentation on obtaining these using validate.php.', E_USER_ERROR);
  }

  $loginMethod = 'session';
}



///* Session Lockout *///
if ($loginMethod === 'credentials' || $loginMethod === 'session') {
  if ($database->lockoutActive()) trigger_error('lockoutActive', E_USER_ERROR);
}




///* Verify the Login *///
if ($loginMethod === 'credentials') {
  if (isset($request['userName']) || isset($request['userId'])) {
    if (!isset($request['password'])) {
      trigger_error('passwordRequired', E_USER_ERROR);
    }
    else {
      if ($request['passwordEncrypt'] === 'base64') $request['password'] = base64_decode($request['password']);


      // If non-vanilla, we must use the integration database to authorise a user.
      if (in_array($loginConfig['method'], $loginDefs['syncMethods'])) {
        $userPre = $integrationDatabase->getUserFromUAC(array(
          'userName' => $request['userName'],
          'userId' => $request['userId']
        ))->getAsUser(); // TODO
      }
      else {
        if ($request['userId']) {
          $userPre = $database->getUsers(array(
            'userIds' => array($request['userId']),
            'includePasswords' => true
          ))->getAsUser();
        }
        elseif ($request['userName']) {
          $userPre = $database->getUsers(array(
            'userNames' => array($request['userName']),
            'includePasswords' => true
          ))->getAsUser();
        }
      }

      if ($userPre->checkPassword($request['password'])) {
        $user = $userPre;
      }
      else {
        $database->lockoutIncrement();
        trigger_error('invalidLogin', E_USER_ERROR);
      }
    }
  }
  elseif ($config['anonymousUserId']) {
    $user = new fimUser($config['anonymousUserId']);
  }
  else {
    trigger_error('loginRequired', E_USER_ERROR);
  }


  $sessionHash = $database->createSession($user);
}

elseif ($loginMethod === 'session') {
  $session = $database->getSessions(array(
    'sessionHashes' => array($request['fim3_sessionHash'])
  ))->getAsArray(false);


  if (!count($session)) {
    $database->lockoutIncrement();
    trigger_error('invalidSession', E_USER_ERROR);
  }
  elseif ($session['userAgent'] !== $_SERVER['HTTP_USER_AGENT']) { // Require the UA match that of the one used to establish the session. Smart clients are encouraged to specify their own with their client name and version.
    $database->lockoutIncrement();
    trigger_error('sessionMismatchBrowser', E_USER_ERROR);
  }
  elseif ($session['sessionIp'] !== $_SERVER['REMOTE_ADDR']) { // This is a tricky one (in some instances, a user's IP may change throughout their session, especially over mobile), but generally the most certain to block any attempted forgeries. That said, IPs can, /theoretically/ be spoofed.
    $database->lockoutIncrement();
    trigger_error('sessionMismatchIp', E_USER_ERROR);
  }
  else {
    $user = new fimUser($session); // Mostly identical, though a few additional properties do exist.

    if ($session['sessionTime'] < time() - $config['sessionRefresh']) $database->refreshSession($session['sessionId']); // If five minutes have passed since the session has been generated, update it.
  }
}



/* API Output */
if ($apiRequest !== true && $streamRequest !== true && $ignoreLogin !== true && $hookLogin === false) {
  $apiData = new apiData();
  $apiData->replaceData(array(
    'login' => array(
      'sessionHash' => $sessionHash,
      'anonId' => $user->anonId,
      'defaultRoomId' => $user->defaultRoomId,
      'userData' => array(
        'userId' => $user->id,
        'userName' => $user->name,
        'userNameFormat' => $user->nameFormat,
        'userGroupId' => $user->mainGroupId,
        'socialGroupIds' => new apiOutputList($user->socialGroupIds),
        'avatar' => $user->avatar,
        'profile' => $user->profile,
        'messageFormatting' => $user->messageFormatting,
        'parentalFlags' => new apiOutputList($user->parentalFlags),
        'parentalAge' => $user->parentalAge,
      ),
      'permissions' => array(
        'protected' => (bool) ($user->privs & ADMIN_PROTECTED), // This the "untouchable" flag, but that's more or less all it means.
        'modPrivs' => (bool) ($user->privs & ADMIN_GRANT), // This effectively allows a user to give himself everything else below. It is also used for admin functions that can not be delegated effectively -- such as modifying the site configuration.
        'modRooms' => (bool) ($user->privs & ADMIN_ROOMS), // Alter rooms -- kicking users, delete posts, and change hidden/official status
        'modPrivate' => (bool) ($user->privs & ADMIN_VIEW_PRIVATE), // View private communications.
        'modUsers' => (bool) ($user->privs & ADMIN_USERS), // Site-wide bans, mostly.
        'modFiles' => (bool) ($user->privs & ADMIN_FILES), // File Uploads
        'modCensor' => (bool) ($user->privs & ADMIN_CENSOR), // Censor

        /* User Privs */
        'view' => (bool) ($user->privs & USER_PRIV_VIEW), // Is not banned
        'post' => (bool) ($user->privs & USER_PRIV_POST),
        'changeTopic' => (bool) ($user->privs & USER_PRIV_TOPIC),
        'createRooms' => (bool) ($user->privs & USER_PRIV_CREATE_ROOMS), // May create rooms
        'privateRoomsFriends' => (bool) ($user->privs & USER_PRIV_PRIVATE_FRIENDS), // May create private rooms (friends only)
        'privateRoomsAll' => (bool) ($user->privs & USER_PRIV_PRIVATE_ALL), // May create private rooms (anybody)
        'roomsOnline' => (bool) ($user->privs & USER_PRIV_ACTIVE_USERS), // May see rooms online.
        'postCounts' => (bool) ($user->privs & USER_PRIV_POST_COUNTS), // May see post counts.
      )
    ),
  ));
  die($apiData->output());
}


define('FIM_LOGINRUN', true);
?>