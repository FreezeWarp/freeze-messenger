<?php
/* FreezeMessenger Copyright © 2011 Joseph Todd Parsons

 * This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>. */

/* The following file is used to manage all logins within VRIM. At present it is a direct port of VB, but at present this is not ideal.
 * In the future it will be rewritten to better handle logins to other forums/backends, to better support the API, and so-on. */




///* Require Base *///

require_once('global.php');



///* Required Forum-Included Functions *///

switch ($loginMethod) {

  case 'vbulletin':
  /* Set Relevant Column Data */
  $sqlUserTable = $forumPrefix . 'user'; // The user table in the login method used.
  $sqlUserGroupTable = $forumPrefix . 'usergroup'; // The usergroup table in the login method used.
  $sqlSessionTable = $forumPrefix . 'session'; // The sessions table in the login method used.

  $sqlUserTableCols = array(
    'userId' => 'userid', // The user ID column of the user table in the login method used.
    'userName' => 'username', // The userName column of the user table in the login method used.
    'usergroup' => 'displaygroupid', // The usergroup column of the user table in the login method used.
    'allgroups' => 'membergroupids',
    'tzoffset' => 'timezoneoffset',
    'options' => 'options',
  );
  $sqlUserGroupTableCols = array(
    'groupid' => 'usergroupid',
  );

  $parseGroups = true; // This still needed?
  break;

  case 'phpbb':
  $sqlUserTable = $forumPrefix . 'users'; // The user table in the login method used.
  $sqlUserGroupTable = $forumPrefix . 'groups'; // The usergroup table in the login method used.
  $sqlSessionTable = $forumPrefix . 'sessions'; // The sessions table in the login method used.

  $sqlUserTableCols = array(
    'userId' => 'user_id', // The user ID column of the user table in the login method used.
    'userName' => 'username', // The userName column of the user table in the login method used.
    'usergroup' => 'group_id', // The usergroup column of the user table in the login method used.
    'allgroups' => 'group_id',
    'tzoffset' => 'user_timezone',
    'colour' => 'user_colour',
  );
  $sqlUserGroupTableCols = array(
    'groupid' => 'group_id',
  );

  $parseGroups = false;
  break;

  default:
  trigger_error("Login method '$loginMethod' unrecognized.",E_USER_ERROR);
  break;

}


/* The following function is derived from vBulletin code required for properly defining a vBulletin-compatible cookie.
 * It is deemed fair use to use this code for the following reasons. If its authors have any issue, please contact Joseph T. Parsons by email (rehtaew@gmail.com) to sort any possible issues out:
 ** It is brief in nature.
 ** It is required for the function used.
 ** It should not hurt or cause any damage to the vBulletin software nor its authors.
 ** The algorithm is incredibly basic, and mainly performs simplistic tests on PHP $_SERVER variables.
 ** It is used in good nature.
 * To be clear, the code is under the copyright of its vBulletin authors, and used as it is thought to be fair use. */

function realIp() { // vBulletin Function
  $alt_ip = $_SERVER['REMOTE_ADDR'];

  if (isset($_SERVER['HTTP_CLIENT_IP'])) {
    $alt_ip = $_SERVER['HTTP_CLIENT_IP'];
  }
  elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
    foreach ($matches[0] AS $ip) {
      if (!preg_match("#^(10|172\.16|192\.168)\.#", $ip)) {
        $alt_ip = $ip;
        break;
      }
    }
  }
  else if (isset($_SERVER['HTTP_FROM'])) {
    $alt_ip = $_SERVER['HTTP_FROM'];
  }
  return implode('.', array_slice(explode('.', $alt_ip), 0, 3));
}

function phpbb_hash($password) {
  $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

  $random_state = unique_id();
  $random = '';
  $count = 6;

  if (($fh = @fopen('/dev/urandom', 'rb'))) {
    $random = fread($fh, $count);
    fclose($fh);
  }

  if (strlen($random) < $count) {
    $random = '';

    for ($i = 0; $i < $count; $i += 16) {
      $random_state = md5(unique_id() . $random_state);
      $random .= pack('H*', md5($random_state));
    }
    $random = substr($random, 0, $count);
  }

  $hash = _hash_crypt_private($password, _hash_gensalt_private($random, $itoa64), $itoa64);

  if (strlen($hash) == 34) {
    return $hash;
  }

  return md5($password);
}

function unique_id($extra = 'c') {
  static $dss_seeded = false;
  global $forumCookieSalt;

  $val = $forumCookieSalt . microtime();
  $val = md5($val);
  $randSeed = md5($forumCookieSalt . $val . $extra);

  return substr($val, 4, 16);
}



function _hash_crypt_private($password, $setting, &$itoa64) {
  $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

  $output = '*';

  // Check for correct hash
  if (substr($setting, 0, 3) != '$H$') {
    return $output;
  }

  $count_log2 = strpos($itoa64, $setting[3]);

  if ($count_log2 < 7 || $count_log2 > 30) {
    return $output;
  }

  $count = 1 << $count_log2;
  $salt = substr($setting, 4, 8);

  if (strlen($salt) != 8) {
    return $output;
  }

        /**
        * We're kind of forced to use MD5 here since it's the only
        * cryptographic primitive available in all versions of PHP
        * currently in use.  To implement our own low-level crypto
        * in PHP would result in much worse performance and
        * consequently in lower iteration counts and hashes that are
        * quicker to crack (by non-PHP code).
        */
  $hash = md5($salt . $password, true);
  do {
    $hash = md5($hash . $password, true);
  } while (--$count);

  $output = substr($setting, 0, 12);
  $output .= _hash_encode64($hash, 16, $itoa64);

  return $output;
}

function _hash_encode64($input, $count, &$itoa64) {
  $output = '';
  $i = 0;

  do {
    $value = ord($input[$i++]);
    $output .= $itoa64[$value & 0x3f];

    if ($i < $count) {
      $value |= ord($input[$i]) << 8;
    }

    $output .= $itoa64[($value >> 6) & 0x3f];

    if ($i++ >= $count) {
      break;
    }

    if ($i < $count) {
      $value |= ord($input[$i]) << 16;
    }

    $output .= $itoa64[($value >> 12) & 0x3f];

    if ($i++ >= $count) {
      break;
    }

    $output .= $itoa64[($value >> 18) & 0x3f];
  } while ($i < $count);

  return $output;
}

function phpbb_check_hash($password, $hash) {
  if (strlen($hash) == 34) {
    return (_hash_crypt_private($password, $hash, $itoa64) === $hash) ? true : false;
  }

  return (md5($password) === $hash) ? true : false;
}


if ($apiRequest) {
  if ($_SERVER['HTTP_REFERER'] && $installUrl) {
    if (strstr($_SERVER['HTTP_REFERER'],$installUrl)) {
      $apiRequestCheck = false;
    }
  }

  if ($apiRequestCheck !== false) {
    if (!$enableForeignApi) {
      die('Foreign API Disabled');
    }
    elseif ($insecureApi) {
      $apiRequestCheck = false;
    }
    else {
      $apiRequestCheck = true;
    }
  }
}
else {
  $apiRequestCheck = false;
}




///* Process Functions for Each Forum  *///

/* User should be array, password md5sum of plaintext. */
function processVBulletin($user,$password) {
  global $forumPrefix;

  $idhash = md5($_SERVER['HTTP_USER_AGENT'] . realIp());

  if (!$user['userId']) return false;

  if ($user['password'] === md5($password . $user['salt'])) { // The password matches.
    global $user; // Make sure accessible elsewhere.
    return true;
  }

  else {
    return false;
  }
}

function processPHPBB($user, $password) {
  global $forumPrefix, $brokenUsers;

  if (!$user['user_id']) return false;
  elseif (in_array($user['user_id'],$brokenUsers)) return false;

  if (phpbb_check_hash($password, $user['user_password'])) {
    return true;
  }
  else {
    return false;
  }
}





///* Obtain Login Data From Different Locations *///

if (isset($_GET['userName'],$_GET['password'])) { // API.
  $apiVersion = intval($_GET['apiVersion']);
  switch($apiVersion) {
    case '1':
    $flag = 'oldversion';
    break;

    case '2':
    // Do nothing
    break;

    default:
    $flag = 'noversion';
    break;
  }

  $userName = vrim_urldecode($_GET['userName']);
  $password = vrim_urldecode($_GET['password']);

  switch ($_GET['passwordEncrypt']) {
    case 'md5':
    // Do nothing
    break;

    case 'plaintext':
    $password = md5($password);
    break;

    case 'base64':
    $password = md5(base64_decode($password));
    break;

    default:
    $flag = 'unrecpassencrpyt';
    break;
  }

  $api = true;
}

elseif (isset($_POST['userName'],$_POST['password'])) { // Data is stored in a just-submitted login form.

  $userName = $_POST['userName'];
  $password = $_POST['password'];

  if ($loginMethod == 'vbulletin') {
    if ($_POST['passwordEncrypt'] == 'md5') {
      // Do nothing
    }
    else {
      $password = md5($password);
    }
  }
  else {

  }

  if ($_POST['rememberme']) {
    $rememberMe = true;
  }
}

elseif (isset($_GET['sessionhash'])) {
  $sessionHash = vrim_urldecode($_GET['sessionhash']);

  $userName = false;
  $password = false;
}

elseif (isset($_COOKIE[$forumCookiePrefix . 'sessionhash']) && !$apiRequestCheck) { // Data is stored in session cookie.
  $sessionHash = vrim_urldecode($_COOKIE[$forumCookiePrefix . 'sessionhash']);

  $userName = false;
  $password = false;
}

elseif (isset($_COOKIE[$forumCookiePrefix . 'sid']) && !$apiRequestCheck) {
  $sessionHash = vrim_urldecode($_COOKIE[$forumCookiePrefix . 'sid']);

  $userName = false;
  $password = false;
}

elseif (isset($_COOKIE[$forumCookiePrefix . 'userId'],$_COOKIE[$forumCookiePrefix . 'password']) && !$apiRequestCheck) { // Data is stored in long-lasting cookies.
  $userId = intval($_COOKIE[$forumCookiePrefix . 'userId']);
  $passwordVBulletin = $_COOKIE[$forumCookiePrefix . 'password'];
}

else { // No login data exists.
  $userName = false;
  $password = false;
  $userId = false;
  $sessionHash = false;
}




///* Process Login Data *///

if ($flag) {
  // Do nothing.
}
elseif ($loginMethod === 'vbulletin') {
  if ($userName && $password) {
    $user = sqlArr('SELECT * FROM ' . $sqlUserTable . ' WHERE userName = "' . mysqlEscape($userName) . '" LIMIT 1');

    if (processVBulletin($user,$password)) {
      $setCookie = true;
      $valid = true;
      $session = 'create';
    }
    else {
      $valid = false;
    }
  }

  elseif ($userId && $password) {
    $user = sqlArr('SELECT * FROM ' . $sqlUserTable . ' WHERE userId = "' . intval($userId) . '" LIMIT 1');

    if (processVBulletin($user,$password)) {
      $setCookie = true;
      $valid = true;
      $session = 'create';
    }
    else {
      $valid = false;
    }
  }

  elseif ($sessionHash) {
    $session = sqlArr('SELECT * FROM ' . $sqlSessionTable . ' WHERE sessionhash = "' . mysqlEscape($sessionHash) . '"');

    if (!$session['userId']) {
      if (isset($_COOKIE[$forumCookiePrefix . 'userId'],$_COOKIE[$forumCookiePrefix . 'password'])) { // Data is stored in long-lasting cookies.
        $userId = intval($_COOKIE[$forumCookiePrefix . 'userId']);
        $passwordVBulletin = $_COOKIE[$forumCookiePrefix . 'password'];

        $user = sqlArr('SELECT * FROM ' . $sqlUserTable . ' WHERE userId = "' . intval($userId) . '" AND "' . mysqlEscape($_COOKIE[$forumCookiePrefix . 'password'])  . '" = MD5(CONCAT(password,"' . $forumCookieSalt . '"))'); // Query from vBulletin user table.

        if ($user) {
          $valid = true;

          $session = 'create';
          $setCookie = true;
        }
        else {
          $valid = false;
        }
      }
    }
    else {
      $user = sqlArr('SELECT * FROM ' . $sqlUserTable . ' WHERE userId = "' . (int) $session['userId'] . '"'); // Query from vBulletin user table.
      $session = 'update';
      $valid = true;
    }
  }

  elseif ($userId && $passwordVBulletin) {
    $user = sqlArr('SELECT * FROM ' . $sqlUserTable . ' WHERE userId = "' . (int) $userId . '" AND "' . mysqlEscape($_COOKIE[$forumCookiePrefix . 'password'])  . '" = MD5(CONCAT(password,"' . $forumCookieSalt . '"))'); // Query from vBulletin user table.

    if ($user) {
      $valid = true;

      $session = 'create';
      $setCookie = true;
    }
    else {
      $valid = false;
    }
  }

  else {
    $valid = false;
  }
}

elseif ($loginMethod == 'phpbb') {
  if ($userName && $password) {
    $user = sqlArr('SELECT * FROM ' . $sqlUserTable . ' WHERE userName = "' . mysqlEscape($userName) . '" LIMIT 1');

    if (processPHPBB($user,$password)) {
      $setCookie = true;
      $valid = true;
      $session = 'create';
    }
    else {
      $valid = false;
    }
  }

  elseif ($userId && $password) {
    $user = sqlArr('SELECT * FROM ' . $sqlUserTable . ' WHERE userId = "' . (int) $userId . '" LIMIT 1');

    if (processPHPBB($user,$password)) {
      $setCookie = true;
      $valid = true;
      $session = 'create';
    }
    else {
      $valid = false;
    }
  }

  elseif ($sessionHash) {
    $session = sqlArr('SELECT * FROM ' . $sqlSessionTable . ' WHERE session_id = "' . mysqlEscape($sessionHash) . '"');

    if (!$session['session_user_id'] || in_array($session['session_user_id'],$brokenUsers)) {
      $valid = false;
    }
    elseif ($session['session_user_id'] != $_COOKIE[$forumCookiePrefix . 'u']) {
      trigger_error('Session Mismatch',E_USER_ERROR);
    }
    else {
      $user = sqlArr('SELECT * FROM ' . $sqlUserTable . ' WHERE user_id = "' . (int) $session['session_user_id'] . '"'); // Query from user table.
      $session = 'update';
      $valid = true;
    }
  }
}





///* Final Forum-Specific Processing *///

if ($valid) { // If the user is valid, process their preferrences.
  $userCopy = $user;
  unset($user);

  switch ($loginMethod) {

    case 'vbulletin':
    /* Set Relevant User Data */
    $user2['userName'] = $userCopy[$sqlUserTableCols['userName']];
    $user2['userId'] = $userCopy[$sqlUserTableCols['userId']];
    $user2['timezoneoffset'] = $userCopy[$sqlUserTableCols['tzoffset']];
    $user2['displaygroupid'] = $userCopy[$sqlUserTableCols['usergroup']];
    $user2['membergroupids'] = $userCopy[$sqlUserTableCols['allgroups']];

    if ($userCopy[$sqlUserOptionsCol] & 64) $user2['timezoneoffset']++; // DST is autodetect. We'll just set it by hand.
    elseif ($userCopy[$sqlUserOptionsCol] & 128) $user2['timezoneoffset']++; // DST is on, add an hour
    else $user2['timezoneoffset']; // DST is off

    break;



    case 'phpbb':
    $parseGroups = false;

    /* Set Relevant User Data */
    $user2['userName'] = $userCopy[$sqlUserTableCols['userName']];
    $user2['userId'] = $userCopy[$sqlUserTableCols['userId']];
    $user2['timezoneoffset'] = $userCopy[$sqlUserTableCols['tzoffset']];
    $user2['displaygroupid'] = $userCopy[$sqlUserTableCols['usergroup']];
    $user2['membergroupids'] = $userCopy[$sqlUserTableCols['allgroups']];
    $user2['colour'] = $userCopy[$sqlUserTableCols['colour']];
    break;


    default:
    die('Error');
    break;

  }


  $userprefs = sqlArr('SELECT * FROM ' . $sqlPrefix . 'users WHERE userId = ' . (int) $user2['userId']); // Should be merged into the above $user query, but because the two don't automatically sync for now it can't be. A manual sync, plus setting up the userpref row in the first event would fix this.

  if (!$userprefs) {
    mysqlQuery('INSERT INTO ' . $sqlPrefix . 'users SET userId = ' . (int) $user2['userId']); // Create the new row

    $userprefs = sqlArr('SELECT * FROM ' . $sqlPrefix . 'users WHERE userId = ' . (int) $user2['userId']); // Should be merged into the above $user query, but because the two don't automatically sync for now it can't be. A manual sync, plus setting up the userpref row in the first event would fix this.
  }


  $user = array_merge($user2,$userprefs); // Merge userprefs into user for future referrence.


  if ($session == 'create') {
    switch ($loginMethod) {
      case 'vbulletin':
      $sessionhash = md5(uniqid(microtime(), true)); // Generate the sessionhash, which should be unique to this browsing session.

      mysqlQuery('INSERT INTO ' . $sqlSessionTable . ' SET sessionhash = "' . mysqlEscape($sessionhash) . '", idhash="' . mysqlEscape($idhash) . '", userId = "' . (int) $user['userId'] . '", host = "' . mysqlEscape($_SERVER['REMOTE_ADDR']) . '", lastactivity = "' . time()  . '", location="/chat.php", useragent="' . mysqlEscape($_SERVER['HTTP_USER_AGENT']) . '", loggedin = 2'); // Add to the vBulletin session table for the who's online.
      break;

      case 'phpbb':
      $sessionhash = md5(unique_id()); // Works slightly different compared to vB, you'll see.

      mysqlQuery('INSERT INTO ' . $sqlSessionTable . ' SET session_id = "' . mysqlEscape($sessionhash) . '", session_user_id = "' . (int) $user['userId'] . '", session_ip = "' . mysqlEscape($_SERVER['REMOTE_ADDR']) . '", session_time = "' . time()  . '", session_page="chat.php", session_browser="' . mysqlEscape($_SERVER['HTTP_USER_AGENT']) . '"'); // Add to the vBulletin session table for the who's online.
      break;
    }
  }
  elseif ($session == 'update' && $sessionHash) {
    switch ($loginMethod) {
      case 'vbulletin':
      mysqlQuery('UPDATE ' . $sqlSessionTable . ' SET lastactivity = "' . time() . '" WHERE sessionhash = "' . $session['sessionhash'] . '"');
      break;

      case 'phpbb':
      mysqlQuery('UPDATE ' . $sqlSessionTable . ' SET session_time = "' . time() . '" WHERE session_id = "' . $session['session_id'] . '"');
      break;
    }
  }
  else {

  }


  if ($setCookie) {
    switch($loginMethod) {
      case 'vbulletin':
      if ($rememberMe) { // This will store the user's login information in the browser's cookies for one week.
        setcookie($forumCookiePrefix . 'userId',$userCopy[$sqlUserTableCols['userId']],time() + 60 * 60 * 24 * 365,'/',$forumCookieDomain); // Set the cookie for userId.
        setcookie($forumCookiePrefix . 'password',md5($userCopy[$sqlUserTableCols['password']] . $forumCookieSalt),time() + 60 * 60 * 24 * 365,'/',$forumCookieDomain); // Set the cookie for password.
      }

      setcookie($forumCookiePrefix . 'sessionhash',$sessionhash,0,'/',$forumCookieDomain); // Set the cookie for the unique session.
      break;

      case 'phpbb':
      setcookie($forumCookiePrefix . 'u',$userCopy[$sqlUserTableCols['userId']],0,'/',$forumCookieDomain); // Set the cookie for the unique session.
      setcookie($forumCookiePrefix . 'sid',$sessionhash,0,'/',$forumCookieDomain); // Set the cookie for the unique session.
      break;
    }
  }

  if ($bannedUserGroups) {
    if (inArray($bannedUserGroups,explode(',',$user['membergroupids']))) $banned = true;
  }
  if ($user['settings'] & 2) {
    $banned = true;
  }
}

else { // If the user is not valid, remove all user data. If a user's name is correct but not the password, the user variable could contain sensitive data which should not be seen.
  unset($user);
  $user['settings'] = 45; // Set the user prefs to their defaults.
  $user['membergroupids'] = '1';
  $user['userId'] = 0;
}







if ($api) {
  switch ($flag) {
    case 'unrecpassencrpyt':
    $failMessage = 'The password encryption used was not recognized and could not be decoded.';
    break;
    case 'nouser':
    $failMessage = 'No user was given.';
    break;
    case 'nopass':
    $failMessage = 'No password was given.';
    break;
    case 'noversion':
    $failMessage = 'No API version string was given. The software only supports version 2.';
    break;
    case 'oldversion':
    $failMessage = 'An old API version string was given. The software only supports version 2.';
    break;
  }

  if (!$valid && !$flag) {
    $flag = 'invalid';
    $failMessage = 'The login was incorrect.';
  }

  header('Content-type: text/xml');
  echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<login>
  <sentData>
    <apiVersion>" . vrim_encodeXML($_GET['apiVersion']) . "</apiVersion>
    <passwordEncrypt>" . vrim_encodeXML($_GET['passwordEncrypt']) . "</passwordEncrypt>
    <userName>" . vrim_encodeXML($_GET['userName']) . "</userName>
    <password>" . vrim_encodeXML($_GET['password']) . "</password>
  </sentData>
  <valid>$valid</valid>
  <errorcode>$flag</errorcode>
  <errortext>$failMessage</errortext>
  <sessionhash>$sessionhash</sessionhash>
  <userdata>
    <userId>$user[userId]</userId>
    <userName>$user[userName]</userName>
    <membergroupids>$user[membergroupids]</membergroupids>
    <messageFormatting>
      <standard>$user[defaultFormatting]</standard>
      <highlight>$user[defaultHighlight]</highlight>
      <colour>$user[defaultColour]</colour>
      <font>$user[defaultFont]</font>
    </messageFormatting>
  </userdata>
</login>
";
  die();
}
elseif (!$valid && !$noReqLogin && !$apiRequest) {

}
elseif ($valid) {
  /* The following defines each individual user's options via an associative array. It is highly recommended this be used to referrence settings. */
  $user['optionDefs'] = array(
    'disableFormatting' => ($user['settingsOfficialAjax'] & 16),
    'disableVideos' => ($user['settingsOfficialAjax'] & 32),
    'disableImages' => ($user['settingsOfficialAjax'] & 64),
    'reversePostOrder' => ($user['settingsOfficialAjax'] & 1024),
    'showAvatars' => ($user['settingsOfficialAjax'] & 2048),
    'audioDing' => ($user['settingsOfficialAjax'] & 8192),
  );

  $user['adminDefs'] = array(
    'modPrivs' => ($user['adminPrivs'] & 1),
    'modUsers' => ($user['adminPrivs'] & 16),
    'modImages' => ($user['adminPrivs'] & 64),
    'modCensorWords' => ($user['adminPrivs'] & 256),
    'modCensorLists' => ($user['adminPrivs'] & 512),
    'modPlugins' => ($user['adminPrivs'] & 4096),
    'modTemplates' => ($user['adminPrivs'] & 8192),
    'modHooks' => ($user['adminPrivs'] & 16384),
    'modTranslations' => ($user['adminPrivs'] & 32768),
  );

  $user['userDefs'] = array(
    'allowed' => ($user['userPrivs'] & 16),
    'createRooms' => ($user['userPrivs'] & 32),
  );
}
?>