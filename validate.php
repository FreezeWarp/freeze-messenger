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






///* Required Forum-Included Functions *///


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







///* Require Base *///

require_once('global.php');


if ($apiRequest) {
  if (strstr($_SERVER['HTTP_REFERER'],$installUrl)) {
    $apiRequestCheck = false;
  }
  else {
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

  if (!$user['userid']) return false;

  if ($user['password'] === md5($password . $user['salt'])) { // The password matches.
    global $user; // Make sure accessible elsewhere.
    return true;
  }

  else {
    return false;
  }
}






///* Obtain Login Data From Different Locations *///

if (isset($_GET['username'],$_GET['password'])) { // API.
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

  $username = vrim_urldecode($_GET['username']);
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

elseif (isset($_POST['username'],$_POST['password'])) { // Data is stored in a just-submitted login form.

  $username = $_POST['username'];
  $password = $_POST['password'];

  if ($_POST['passwordEncrypt'] == 'md5') {
    // Do nothing
  }
  else {
    $password = md5($password);
  }

  if ($_POST['rememberme']) {
    $rememberMe = true;
  }
}

elseif (isset($_GET['sessionhash'])) {
  $sessionHash = vrim_urldecode($_GET['sessionhash']);

  $username = false;
  $password = false;
}

elseif (isset($_COOKIE[$forumCookiePrefix . 'sessionhash']) && !$apiRequestCheck) { // Data is stored in session cookie.
  $sessionHash = vrim_urldecode($_COOKIE[$forumCookiePrefix . 'sessionhash']);

  $username = false;
  $password = false;
}

elseif (isset($_COOKIE[$forumCookiePrefix . 'userid'],$_COOKIE[$forumCookiePrefix . 'password']) && !$apiRequestCheck) { // Data is stored in long-lasting cookies.
  $userid = intval($_COOKIE[$forumCookiePrefix . 'userid']);
  $passwordVBulletin = $_COOKIE[$forumCookiePrefix . 'password'];
}

else { // No login data exists.
  $username = false;
  $password = false;
  $userid = false;
  $sessionHash = false;
}




///* Process Login Data *///

if ($flag) {
  // Do nothing.
}
elseif ($loginMethod === 'vbulletin') {
  if ($username && $password) {
    $user = sqlArr('SELECT * FROM ' . $forumPrefix . 'user WHERE username = "' . mysqlEscape($username) . '" LIMIT 1');

    if (processVBulletin($user,$password)) {
      $setCookie = true;
      $valid = true;
      $session = 'create';
    }
    else {
      $valid = false;
    }
  }

  elseif ($userid && $password) {
    $user = sqlArr('SELECT * FROM ' . $forumPrefix . 'user WHERE userid = "' . intval($userid) . '" LIMIT 1');

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
    $session = sqlArr('SELECT * FROM session WHERE sessionhash = "' . mysqlEscape($sessionHash) . '"');

    if (!$session['userid']) {
      if (isset($_COOKIE[$forumCookiePrefix . 'userid'],$_COOKIE[$forumCookiePrefix . 'password'])) { // Data is stored in long-lasting cookies.
        $userid = intval($_COOKIE[$forumCookiePrefix . 'userid']);
        $passwordVBulletin = $_COOKIE[$forumCookiePrefix . 'password'];

        $user = sqlArr('SELECT * FROM user WHERE userid = "' . intval($userid) . '" AND "' . mysqlEscape($_COOKIE['bbpassword'])  . '" = MD5(CONCAT(password,"' . $forumCookieSalt . '"))'); // Query from vBulletin user table.

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
      $user = sqlArr('SELECT * FROM user WHERE userid = "' . intval($session['userid']) . '"'); // Query from vBulletin user table.
      $session = 'update';
      $valid = true;
    }
  }

  elseif ($userid && $passwordVBulletin) {
    $user = sqlArr('SELECT * FROM user WHERE userid = "' . intval($userid) . '" AND "' . mysqlEscape($_COOKIE['bbpassword'])  . '" = MD5(CONCAT(password,"' . $forumCookieSalt . '"))'); // Query from vBulletin user table.

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





///* Final Forum-Specific Processing *///

if ($valid) { // If the user is valid, process their preferrences.

  $userCopy = $user;
  unset($user);

  switch ($loginMethod) {

    case 'vbulletin':

    /* Set Relevant Column Data */
    $sqlUserTable = 'user'; // The user table in the login method used.
    $sqlUserGroupTable = 'usergroup'; // The usergroup table in the login method used.

    $sqlUserTableCols = array(
      'userid' => 'userid', // The user ID column of the user table in the login method used.
      'username' => 'username', // The username column of the user table in the login method used.
      'usergroup' => 'displaygroupid', // The usergroup column of the user table in the login method used.
      'allgroups' => 'membergroupids',
      'tzoffset' => 'timezoneoffset',
      'options' => 'options',
    );
    $sqlUserGroupTableCols = array(
      'groupid' => 'usergroupid',
      'startTag' => 'opentag',
      'endTag' => 'closetag',
    );
    $parseGroups = true;

    /* Set Relevant User Data */
    $user2['username'] = $userCopy[$sqlUserTableCols['username']];
    $user2['userid'] = $userCopy[$sqlUserTableCols['userid']];
    $user2['timezoneoffset'] = $userCopy[$sqlUserTableCols['tzoffset']];
    $user2['displaygroupid'] = $userCopy[$sqlUserTableCols['usergroup']];
    $user2['membergroupids'] = $userCopy[$sqlUserTableCols['allgroups']];

    if ($userCopy[$sqlUserOptionsCol] & 64) $user2['timezoneoffset']++; // DST is autodetect. We'll just set it by hand.
    elseif ($userCopy[$sqlUserOptionsCol] & 128) $user2['timezoneoffset']++; // DST is on, add an hour
    else $user2['timezoneoffset']; // DST is off

    break;



    default:
    die('Error');
    break;

  }

  $userprefs = sqlArr('SELECT * FROM ' . $sqlPrefix . 'users WHERE userid = ' . $userCopy['userid']); // Should be merged into the above $user query, but because the two don't automatically sync for now it can't be. A manual sync, plus setting up the userpref row in the first event would fix this.

  if (!$userprefs) {
    mysqlQuery('INSERT INTO ' . $sqlPrefix . 'users SET userid = ' . $userCopy['userid']); // Create the new row

    $userprefs = sqlArr('SELECT * FROM ' . $sqlPrefix . 'users WHERE userid = ' . $userCopy['userid']); // Should be merged into the above $user query, but because the two don't automatically sync for now it can't be. A manual sync, plus setting up the userpref row in the first event would fix this.
  }

  $user = array_merge($user2,$userprefs); // Merge userprefs into user for future referrence.

  if ($session == 'create') {
    if ($loginMethod == 'vbulletin') {
      $sessionhash = md5(uniqid(microtime(), true)); // Generate the sessionhash, which should be unique to this browsing session.

      mysqlQuery('INSERT INTO session SET sessionhash = "' . $sessionhash . '", idhash="' . $idhash . '", userid = "' . $user['userid'] . '", host = "' . $_SERVER['REMOTE_ADDR'] . '", lastactivity = "' . time()  . '", location="/chat.php", useragent="' . $_SERVER['HTTP_USER_AGENT'] . '", loggedin = 2'); // Add to the vBulletin session table for the who's online.
    }
  }
  elseif ($session == 'update' && $sessionHash) {
    if ($loginMethod == 'vbulletin') {
      mysqlQuery('UPDATE session SET lastactivity = "' . time() . '", useragent = "' . $_SERVER['HTTP_USER_AGENT'] . '" WHERE sessionhash = "' . $session['sessionhash'] . '"');
    }
  }
  else {

  }

  if ($setCookie) {
    if ($loginMethod == 'vbulletin') {
      if ($rememberMe) { // This will store the user's login information in the browser's cookies for one week.
        setcookie($forumCookiePrefix . 'userid',$userCopy['userid'],time() + 60 * 60 * 24 * 365,'/','.victoryroad.net'); // Set the cookie for userid.
        setcookie($forumCookiePrefix . 'password',md5($userCopy['password'] . $forumCookieSalt),time() + 60 * 60 * 24 * 365,'/','.victoryroad.net'); // Set the cookie for password.
      }

      setcookie($forumCookiePrefix . 'sessionhash',$sessionhash,0,'/','.victoryroad.net'); // Set the cookie for the unique session.
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
  $user['userid'] = 0;
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
    <username>" . vrim_encodeXML($_GET['username']) . "</username>
    <password>" . vrim_encodeXML($_GET['password']) . "</password>
  </sentData>
  <valid>$valid</valid>
  <errorcode>$flag</errorcode>
  <errortext>$failMessage</errortext>
  <sessionhash>$sessionhash</sessionhash>
  <userdata>
    <userid>$user[userid]</userid>
    <username>$user[username]</username>
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
  header('Location: login.php');
}
elseif ($valid) {
  /* The following defines each individual user's options via an associative array. It is highly recommended this be used to referrence settings. */
  $user['optionDefs'] = array(
    'disableFormatting' => ($user['settingsOfficialAjax'] & 16),
    'disableVideo' => ($user['settingsOfficialAjax'] & 32),
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