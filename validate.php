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


/* Quick Notes:
 * Magic Session Hash (Cookie Store): hash('sha256',hash('sha256',uniqid('',true)) . salt)
 * Magic Session Hash (DB Check): hash('sha256',hash('sha256',hash('sha256',uniqid('',true)) . salt) . userId)
 * Password Hash: sha1(sha1(password) . user_iv) */


///* Require Base *///

require_once('global.php');


///* Required Forum-Included Functions *///

switch ($loginMethod) {

  case 'vbulletin':
  /* Set Relevant Column Data */
  $sqlUserTable = $forumPrefix . 'user'; // The user table in the login method used.
  $sqlUserGroupTable = $forumPrefix . 'userGroup'; // The userGroup table in the login method used.
  $sqlSessionTable = $forumPrefix . 'session'; // The sessions table in the login method used.

  $sqlUserTableCols = array(
    'userId' => 'userid', // The user ID column of the user table in the login method used.
    'userName' => 'username', // The userName column of the user table in the login method used.
    'userGroup' => 'displaygroupid', // The userGroup column of the user table in the login method used.
    'allGroups' => 'membergroupids',
    'timeZone' => 'timezoneoffset',
    'options' => 'options',
  );
  $sqlUserGroupTableCols = array(
    'groupId' => 'usergroupid',
    'groupName' => 'title',
  );

  $parseGroups = true; // This still needed?
  break;

  case 'phpbb':
  $sqlUserTable = $forumPrefix . 'users'; // The user table in the login method used.
  $sqlUserGroupTable = $forumPrefix . 'groups'; // The userGroup table in the login method used.
  $sqlSessionTable = $forumPrefix . 'sessions'; // The sessions table in the login method used.

  $sqlUserTableCols = array(
    'userId' => 'user_id', // The user ID column of the user table in the login method used.
    'userName' => 'username', // The userName column of the user table in the login method used.
    'userGroup' => 'group_id', // The userGroup column of the user table in the login method used.
    'allGroups' => 'group_id',
    'timeZone' => 'user_timezone',
    'colour' => 'user_colour',
  );
  $sqlUserGroupTableCols = array(
    'groupId' => 'group_id',
    'groupName' => 'group_name',
  );

  $parseGroups = false;
  break;

  case 'vanilla':
  $sqlUserTable = $tablePrefix . 'users'; // The user table in the login method used.
  $sqlUserGroupTable = $tablePrefix . 'groups'; // The userGroup table in the login method used.
  $sqlSessionTable = $tablePrefix . 'sessions'; // The sessions table in the login method used.

  $sqlUserTableCols = array(
    'userId' => 'userId', // The user ID column of the user table in the login method used.
    'userName' => 'userName', // The userName column of the user table in the login method used.
    'userGroup' => 'userGroup', // The userGroup column of the user table in the login method used.
    'allGroups' => 'allGroups',
    'timeZone' => 'timeZone',
  );
  $sqlUserGroupTableCols = array(
    'groupId' => 'groupId',
    'groupName' => 'groupName',
  );

  $parseGroups = false;
  break;

  default:
  trigger_error("Login method '$loginMethod' unrecognized.",E_USER_ERROR);
  break;

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

function fim_generateSession() {
  global $salts;

  $salt = end($salts);

  if (function_exists('mt_rand')) {
    $rand = mt_rand(1,100000000);
  }
  elseif (function_exists('rand')) {
    $rand = rand(1,100000000);
  }

  /* The algorithm below may not be ideal. It is intended to minimize the ability to guess a hash via a bruteforce mechanism. To do so, we both require that the userId is correct later on (though doing so would make little difference if a hacker is attempting to breach a certain user) and that they know the salt used in the system (without knowing this, it is impossible to attempt to attack by guessing the uniqid - something that is possible). The additional rand is most likely redundant, but for the sake of paranoi it doesn't neccissarly hurt. */

  if (function_exists('hash')) {
    return uniqid('',true) . hash('sha256',hash('sha256',$rand) . $salt);
  }
  else {
    return uniqid('',true) . md5(md5($rand) . $salt);
  }
}

function fim_generatePassword($password) {
  global $salts;

  $salt = end($salts);

  /* Similar to generateSession, the algorthim used below is possibly inferrior, but still will withstand most basic methods, including rainbow tables and in many cases bruteforce (though this may not be true if an attacker is able to gain access to the associated config.php file; in this case, it still will be impossible to decipher anything more advanced than dictionary passwords). */

  if (function_exists('hash')) {
    return hash('sha256',hash('sha256',$password) . $salt);
  }
  else {
    return md5(md5($password) . $salt);
  }
}



///* Process Functions for Each Forum  *///

/* User should be array, password md5sum of plaintext. */
function processVBulletin($user,$password) {
  global $forumPrefix, $sqlUserTable, $sqlUserTableCols;

  if (!$user[$sqlUserTableCols['userId']]) {
    return false;
  }

  if ($user['password'] === md5($password . $user['salt'])) { // The password matches.
    global $user; // Make sure accessible elsewhere.
    return true;
  }

  else {
    return false;
  }
}

function processPHPBB($user, $password) {
  global $forumPrefix, $brokenUsers, $sqlUserTable, $sqlUserTableCols;

  if (!$user[$sqlUserTableCols['userId']]) {
    return false;
  }
  elseif (in_array($user['user_id'],$brokenUsers)) {
    return false;
  }

  if (phpbb_check_hash($password, $user['user_password'])) {
    return true;
  }
  else {
    return false;
  }
}

function processVanilla($user, $password) {
  global $tablePrefix, $sqlUserTable, $sqlUserTableCols;

  if (!$user[$sqlUserTableCols['userId']]) {
    return false;
  }
  else {

  }
}

function processLogin($user, $password) {
  global $loginMethod;

  switch ($loginMethod) {
    case 'vbulletin':
    return processVBulletin($user, $password);
    break;

    case 'phpbb':
    return processPHPBB($user, $password);
    break;

    case 'vanilla':
    return processVanilla($user, $password);
    break;
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

  $userName = fim_urldecode($_GET['userName']);
  $password = fim_urldecode($_GET['password']);

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

elseif (isset($_COOKIE['fim_msid'])) { // Magic Session!
  $magicSessionHash = $_COOKIE['fim_msid'];
}

elseif (isset($_GET['sessionhash'])) {
  $magicSessionHash = fim_urldecode($_GET['sessionhash']);
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
else {
  if ($userName && $password) {
    $user = sqlArr("SELECT * FROM {$sqlUserTable} WHERE $sqlUserTableCols[userName] = '" . mysqlEscape($userName) . "' LIMIT 1");

    if (processLogin($user,$password)) {
      $setCookie = true;
      $valid = true;
      $session = 'create';
    }
    else {
      $valid = false;
    }
  }

  elseif ($userId && $password) {
    $user = sqlArr("SELECT * FROM {$sqlUserTable} WHERE $sqlUserTableCols[userId] = " . (int) $userId . '" LIMIT 1');

    if (processLogin($user,$password)) {
      $setCookie = true;
      $valid = true;
      $session = 'create';
    }
    else {
      $valid = false;
    }
  }

  elseif ($magicSessionHash) {

  }

  elseif ($userId && $passwordVBulletin) {
    $user = sqlArr("SELECT * FROM $sqlUserTable WHERE $sqlUserTableCols[userId] = " . (int) $userId . ' AND "' . mysqlEscape($_COOKIE[$forumCookiePrefix . 'password'])  . '" = MD5(CONCAT(password,"' . mysqlEscape($forumCookieSalt) . '"))'); // Query from vBulletin user table.

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
    case 'phpbb':

    /* Set Relevant User Data */
    $user2['userName'] = $userCopy[$sqlUserTableCols['userName']];
    $user2['userId'] = $userCopy[$sqlUserTableCols['userId']];
    $user2['timeZone'] = $userCopy[$sqlUserTableCols['timeZone']];
    $user2['userGroup'] = $userCopy[$sqlUserTableCols['userGroup']];
    $user2['allGroups'] = $userCopy[$sqlUserTableCols['allGroups']];

    break;

    case 'vbulletin':

    if ($userCopy[$sqlUserOptionsCol] & 64) $user2['timezoneoffset']++; // DST is autodetect. We'll just set it by hand.
    elseif ($userCopy[$sqlUserOptionsCol] & 128) $user2['timezoneoffset']++; // DST is on, add an hour
    else $user2['timezoneoffset']; // DST is off

    $group = sqlArr("SELECT * FROM $sqlUserGroupTable WHERE $sqlUserGroupTableCols[groupId] = $user2[userGroup]");

    $user2['userFormatStart'] = $group[$sqlUserGroupTableCols['startTag']];
    $user2['userFormatEnd'] = $group[$sqlUserGroupTableCols['endTag']];

    break;

    case 'phpbb':
    $user2['colour'] = $userCopy[$sqlUserTableCols['colour']];

    $user2['userFormatStart'] = "<span style=\"color: #$user2[colour]\">";
    $user2['userFormatEnd'] = '</span>';
    break;

    default:
    die('Error');
    break;

  }


  $userprefs = sqlArr('SELECT * FROM ' . $sqlPrefix . 'users WHERE userId = ' . (int) $user2['userId']); // Should be merged into the above $user query, but because the two don't automatically sync for now it can't be. A manual sync, plus setting up the userpref row in the first event would fix this.

  if (!$userprefs) {
    mysqlQuery('INSERT INTO ' . $sqlPrefix . 'users
SET userId = ' . (int) $user2['userId'] . ',
  userName = "' . mysqlEscape($user2['userName']) . '",
  userGroup = ' . (int) $user2['userGroup'] . ',
  allGroups = "' . mysqlEscape($user2['allGroups']) . '",
  userFormatStart = "' . mysqlEscape($user2['userFormatStart']) . '",
  userFormatEnd = "' . mysqlEscape($user2['userFormatEnd']) . '"'); // Create the new row

    $userprefs = sqlArr('SELECT * FROM ' . $sqlPrefix . 'users WHERE userId = ' . (int) $user2['userId']); // Should be merged into the above $user query, but because the two don't automatically sync for now it can't be. A manual sync, plus setting up the userpref row in the first event would fix this.
  }


  $user = array_merge($user2,$userprefs); // Merge userprefs into user for future referrence.


  if ($session == 'create') {
  }

  elseif ($session == 'update' && $sessionHash) {
  }

  else {
  }


  if ($setCookie) {
    if ($rememberMe) {
    }

    setcookie($forumCookiePrefix . 'sessionhash',$sessionhash,0,'/',$forumCookieDomain); // Set the cookie for the unique session.
  }

  if ($bannedUserGroups) {
    if (fim_inArray($bannedUserGroups,explode(',',$user['allGroups']))) $banned = true;
  }
  if ($user['settings'] & 2) {
    $banned = true;
  }
}

else { // If the user is not valid, remove all user data. If a user's name is correct but not the password, the user variable could contain sensitive data which should not be seen.
  unset($user);
  $user['settings'] = 45; // Set the user prefs to their defaults.
  $user['allGroups'] = '1';
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
    <apiVersion>" . fim_encodeXml($_GET['apiVersion']) . "</apiVersion>
    <passwordEncrypt>" . fim_encodeXml($_GET['passwordEncrypt']) . "</passwordEncrypt>
    <userName>" . fim_encodeXml($_GET['userName']) . "</userName>
    <password>" . fim_encodeXml($_GET['password']) . "</password>
  </sentData>
  <valid>$valid</valid>
  <errorcode>$flag</errorcode>
  <errortext>$failMessage</errortext>
  <sessionhash>$sessionhash</sessionhash>
  <userdata>
    <userId>$user[userId]</userId>
    <userName>$user[userName]</userName>
    <userGroup>$user[userGroup]</userGroup>
    <allGroups>$user[allGroups]</allGroups>
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

unset($sqlPassword); // Security!
?>