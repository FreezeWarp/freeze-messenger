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
  $sqlUserGroupTable = $forumPrefix . 'socialgroup'; // The userGroup table in the login method used.
  $sqlMemberGroupTable = $forumPrefix . 'socialgroupmember'; // The userGroup table in the login method used.
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
    'groupId' => 'groupid',
    'groupName' => 'name',
  );
  $sqlMemberGroupTableCols = array(
    'groupId' => 'groupid',
    'userId' => 'userid',
    'type' => 'type',
    'validType' => 'member',
  );

  $parseGroups = true; // This still needed?
  break;

  case 'phpbb':
  require_once('phpbbReqs.php');

  $sqlUserTable = $forumPrefix . 'users'; // The user table in the login method used.
  $sqlUserGroupTable = $forumPrefix . 'groups'; // The userGroup table in the login method used.
  $sqlMemberGroupTable = $forumPrefix . 'user_group'; // The userGroup table in the login method used.
  $sqlSessionTable = $forumPrefix . 'sessions'; // The sessions table in the login method used.

  $sqlUserTableCols = array(
    'userId' => 'user_id', // The user ID column of the user table in the login method used.
    'userName' => 'username', // The userName column of the user table in the login method used.
    'userGroup' => 'group_id', // The userGroup column of the user table in the login method used.
    'allGroups' => 'group_id',
    'timeZone' => 'user_timezone',
    'color' => 'user_colour',
    'avatar' => 'user_avatar',
  );
  $sqlUserGroupTableCols = array(
    'groupId' => 'group_id',
    'groupName' => 'group_name',
  );
  $sqlMemberGroupTableCols = array(
    'groupId' => 'group_id',
    'userId' => 'user_id',
    'type' => 'user_pending',
    'validType' => '0',
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
    'avatar' => 'avatar',
  );
  $sqlUserGroupTableCols = array(
    'groupId' => 'groupId',
    'groupName' => 'groupName',
  );
  $sqlMemberGroupTableCols = array(
    'groupId' => 'group_id',
    'userId' => 'user_id',
    'type' => 'user_pending',
    'validType' => '0',
  );

  $parseGroups = false;
  break;

  default:
  trigger_error("Login method '$loginMethod' unrecognized.",E_USER_ERROR);
  break;

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
    $user = sqlArr("SELECT u.* FROM {$sqlPrefix}sessions AS s, {$sqlPrefix}users AS u WHERE magicHash = '" . mysqlEscape($magicSessionHash) . "'");

    if ($user['userId'] == $_COOKIE['fim_uid']) {
      $valid = true;
      $noSync = true;
    }
    else {
      $valid = false;
    }

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

  if ($noSync || $loginMethod == 'vanilla') {

  }
  else {
    if ($loginMethod == 'vbulletin' || $loginMethod == 'phpbb') {

      $userCopy = $user;
      unset($user);

      /* Set Relevant User Data */
      $user2['userName'] = $userCopy[$sqlUserTableCols['userName']];
      $user2['userId'] = $userCopy[$sqlUserTableCols['userId']];
      $user2['timeZone'] = $userCopy[$sqlUserTableCols['timeZone']];
      $user2['userGroup'] = $userCopy[$sqlUserTableCols['userGroup']];
      $user2['allGroups'] = $userCopy[$sqlUserTableCols['allGroups']];
      $user2['color'] = $userCopy[$sqlUserTableCols['allGroups']];
      $user2['avatar'] = $userCopy[$sqlUserTableCols['avatar']];
    }

    switch ($loginMethod) {
      case 'vbulletin':

      if ($userCopy[$sqlUserOptionsCol] & 64) $user2['timezoneoffset']++; // DST is autodetect. We'll just set it by hand.
      elseif ($userCopy[$sqlUserOptionsCol] & 128) $user2['timezoneoffset']++; // DST is on, add an hour
      else $user2['timezoneoffset']; // DST is off

      $group = sqlArr("SELECT * FROM $sqlUserGroupTable WHERE $sqlUserGroupTableCols[groupId] = $user2[userGroup]");

      $user2['userFormatStart'] = $group[$sqlUserGroupTableCols['startTag']];
      $user2['userFormatEnd'] = $group[$sqlUserGroupTableCols['endTag']];
      $user2['avatar'] = $forumUrl . '/image.php?u=' . $user2['userId'];
      break;

      case 'phpbb':
      $user2['color'] = $userCopy[$sqlUserTableCols['color']];

      $user2['userFormatStart'] = "<span style=\"color: #$user2[color]\">";
      $user2['userFormatEnd'] = '</span>';
      if ($user2['avatar']) {
        $user2['avatar'] = $forumUrl . 'download/file.php?avatar=' . $user2['avatar'];
      }
      break;

      default:
      die('Error');
      break;

    }

    $userprefs = sqlArr("SELECT * FROM {$sqlPrefix}users WHERE userId = " . (int) $user2['userId']); // Should be merged into the above $user query, but because the two don't automatically sync for now it can't be. A manual sync, plus setting up the userpref row in the first event would fix this.

//    $socialGroups = sqlArr("SELECT * FROM {$sqlMemberGroupTable} WHERE {$sqlMemberGroupTableCols[userId]} = $user[userId] AND $sqlMemberGroupTableCols[type] = $sqlMemberGroupTableCols[validType]");

    if (!$userprefs) {
      mysqlQuery('INSERT INTO ' . $sqlPrefix . 'users
SET userId = ' . (int) $user2['userId'] . ',
  userName = "' . mysqlEscape($user2['userName']) . '",
  userGroup = ' . (int) $user2['userGroup'] . ',
  allGroups = "' . mysqlEscape($user2['allGroups']) . '",
  userFormatStart = "' . mysqlEscape($user2['userFormatStart']) . '",
  userFormatEnd = "' . mysqlEscape($user2['userFormatEnd']) . '",
  avatar = "' . mysqlEscape($user2['avatar']) . '",
  socialGroups = "' . mysqlEscape($socialGroups['groups']) . '",
  lastSync = NOW()'); // Create the new row

      $userprefs = sqlArr('SELECT * FROM ' . $sqlPrefix . 'users WHERE userId = ' . (int) $user2['userId']); // Should be merged into the above $user query, but because the two don't automatically sync for now it can't be. A manual sync, plus setting up the userpref row in the first event would fix this.
    }
    elseif ($userprefs['lastSync'] <= (time() - ($sync ? $sync : (60 * 60 * 2))) || true) {

    $socialGroups = sqlArr("SELECT GROUP_CONCAT($sqlMemberGroupTableCols[groupId] SEPARATOR ',') AS groups FROM {$sqlMemberGroupTable} WHERE {$sqlMemberGroupTableCols[userId]} = $user2[userId] AND $sqlMemberGroupTableCols[type] = '$sqlMemberGroupTableCols[validType]'");

      mysqlQuery('UPDATE ' . $sqlPrefix . 'users
SET userName = "' . mysqlEscape($user2['userName']) . '",
  userGroup = ' . (int) $user2['userGroup'] . ',
  allGroups = "' . mysqlEscape($user2['allGroups']) . '",
  userFormatStart = "' . mysqlEscape($user2['userFormatStart']) . '",
  userFormatEnd = "' . mysqlEscape($user2['userFormatEnd']) . '",
  avatar = "' . mysqlEscape($user2['avatar']) . '",
  socialGroups = "' . mysqlEscape($socialGroups['groups']) . '",
  lastSync = NOW()
WHERE userId = ' . (int) $user2['userId']); // Create the new row

      $userprefs = sqlArr('SELECT * FROM ' . $sqlPrefix . 'users WHERE userId = ' . (int) $user2['userId']); // Should be merged into the above $user query, but because the two don't automatically sync for now it can't be. A manual sync, plus setting up the userpref row in the first event would fix this.
    }

    $user = array_merge($user2,$userprefs); // Merge userprefs into user for future referrence.
  }


  if ($session == 'create') {
    $magicSessionHash = fim_generateSession();

    mysqlQuery("INSERT INTO {$sqlPrefix}sessions (userId,
    time,
    magicHash)
    VALUES ($user[userId],
    NOW(),
    '" . mysqlEscape($magicSessionHash) . "'
    )");
  }

  elseif ($session == 'update' && $magicSessionHash) {
    die('1');
  }

  else {
  }


  if ($setCookie) {
    if ($rememberMe) {
      setcookie('fim_password','',0,'/');
    }

    setcookie('fim_msid',$magicSessionHash,0,'/'); // Set the cookie for the unique session.
    setcookie('fim_uid',$user['userId'],0,'/'); // Set the cookie for the unique session.
  }


  if ($bannedUserGroups) {
    if (fim_inArray($bannedUserGroups,explode(',',$user['allGroups']))) {
      $banned = true;
    }
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
      <color>$user[defaultColor]</color>
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