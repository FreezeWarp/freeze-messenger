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

require_once('global.php');
define('COOKIE_SALT', 'pSD68BENJ7q5w7ReEUTBX0H6LWOO5TEmuZakrwXRu4OI2wMqgxIVf');

/* Process ID Hash */
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

$idhash = md5($_SERVER['HTTP_USER_AGENT'] . realIp());



/* Process Login */


if (isset($_GET['username'],$_GET['password']) && $_GET['apiVersion'] != 'xml') { // VRC goodness.
  $vrimVersion = intval($_GET['vrimVersion']);
  switch($vrimVersion) {
    case '10':
    $flag = 'oldversion';
    break;

    case '0.0.1.10':
    break;

    default:
    $flag = 'noversion';
    break;
  }

  $user = sqlArr('SELECT * FROM user WHERE username = "' . mysqlEscape(vrim_urldecode($_GET['username'])) . '" LIMIT 1');

  if ($flag) {}
  elseif (!$user['userid'] > 0) { // No user entry exists, so the username is wrong.
    $flag = 'nouser';
  }
  else { // If the database password is the same as the password in the cookies or post data, then the user is valid.
    switch ($_GET['encrypt']) {
      case 'md5': case '':
      $password = $_GET['password'];
      break;

      case 'plaintext':
      $password = md5(vrim_urldecode($_GET['password']));
      break;
    }

    if ($user['password'] === md5($password . $user['salt'])) { // The password matches.
      $sessionhash = md5(uniqid(microtime(), true)); // Generate the sessionhash, which should be unique to this browsing session.

      mysqlQuery('INSERT INTO session SET sessionhash = "' . $sessionhash . '", idhash="' . $idhash . '", userid = "' . $user['userid'] . '", host = "' . $_SERVER['REMOTE_ADDR'] . '", lastactivity = "' . time()  . '", location="/chat.php", useragent="' . $_SERVER['HTTP_USER_AGENT'] . '", loggedin = 2'); // Add to the vBulletin session table for the who's online.
    }
    else { // Password is wrong.
      $flag = 'nopass';
    }
  }

  if ($flag) {
    header('vrim-status: 0');
    header('vrim-error: ' . $flag);

    $status = 0;
    $error = $flag;
  }
  else {
    header('vrim-status: 1');
    header('vrim-session: ' . $sessionhash);

    $status = 1;
    $error = '';
  }

  $api = true;
  $valid = true;
}

elseif (isset($_GET['username'],$_GET['password']) && $_GET['apiVersion'] == 'xml') { // VRC goodness.
  $application = $_GET['applicationName'];
  $version = $_GET['applicationVersion'];
  $encrypt = $_GET['encrypt'];
  $password = $_GET['password'];
  $username = $_GET['username'];

  switch ($application) {
    case 'vrimWeb':
    case 'unofficial':
    if ($version == '1.0b2') {
      $continue = true;
    }
    else {
      $failCode = 'badversion';
      $failMessage = 'The version of the API used in this program has been outdated, and is no longer supported. Please try to update the version of the program you are using.';
      $continue = false;
    }
    break;

    case 'vrimWin':
    case 'vrimMac':
    case 'vrimLin':
    if ($version == '1.0a1') {
      $continue = true;
    }
    else {
      $failCode = 'badversion';
      $failMessage = 'A new version of the VRIM client program has been released. Please attempt to download it.';
      $continue = false;
    }
    break;

    default:
    $failCode = 'badprogram';
    $failMessage = 'This application is attempting to use an unrecognized program code.';
    $continue = false;
    break;
  }

  if ($continue) {
    $user = sqlArr('SELECT * FROM user WHERE username = "' . mysqlEscape(vrim_urldecode($username)) . '" LIMIT 1');

    if (!$user['userid'] > 0) { // No user entry exists, so the username is wrong.
      $failCode = 'nouser';
      $failMessage = 'No user with this username exists.';
      $continue = false;
    }
    else { // If the database password is the same as the password in the cookies or post data, then the user is valid.
      switch ($encrypt) {
        case 'md5': case '':
        $password = $password;
        break;

        case 'plaintext':
        $password = md5(vrim_urldecode($password));
        break;

        case 'base64':
        $password = md5(base64_decode(vrim_urldecode($password)));
        break;

        default:
        $failCode = 'badpasswordmode';
        $failMessage = 'This application is attempting to an unrecognized password validation mode.';
        $continue = false;
        break;
      }

      if ($continue) { // It was already set to true previously, and it should still be unless the password mode was unrecognized or the username was invalid.
        if ($user['password'] === md5($password . $user['salt'])) { // The password matches.
          $sessionhash = md5(uniqid(microtime(), true)); // Generate the sessionhash, which should be unique to this browsing session.

          mysqlQuery('INSERT INTO session SET sessionhash = "' . $sessionhash . '", idhash="' . $idhash . '", userid = "' . $user['userid'] . '", host = "' . $_SERVER['REMOTE_ADDR'] . '", lastactivity = "' . time()  . '", location="/chat.php", useragent="' . $_SERVER['HTTP_USER_AGENT'] . '", loggedin = 2'); // Add to the vBulletin session table for the who's online.
        }
        else { // Password is wrong.
          $failCode = 'badpassword';
          $failMessage = 'You appear to have entered an incorrect password.';
          $continue = false;
        }
      }
    }
  }

  if ($continue) {
    userprefs2:
    $userprefs = sqlArr("SELECT * FROM {$sqlPrefix}users WHERE userid = $user[userid]"); // Should be merged into the above $user query, but because the two don't automatically sync for now it can't be. A manual sync, plus setting up the userpref row in the first event would fix this.
    if (!$userprefs) {
      mysqlQuery("INSERT INTO {$sqlPrefix}users SET userid = $user[userid]"); // Create the new row.
      goto userprefs2; // Not good practice, but it works well enough.
    }
    $user = array_merge($user,$userprefs); // Merge userprefs into user for future referrence.

    if ($user['settings'] & 2) {
      $failCode = 'userbanned';
      $failMessage = 'You have been banned/';
      $continue = false;
    }
  }

  if ($continue) $valid = 'true';
  else $valid = 'false';

  header('Content-type: text/xml');
  echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<login>
  <sentData>
    <applicationName>$application</applicationName>
    <applicationVersion>$version</applicationVersion>
    <passwordEncrypt>$encrypt</passwordEncrypt>
    <username>$username</username>
    <password>$password</password>  
  </sentData>
  <valid>$valid</valid>
  <errorcode>$failCode</errorcode>
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

  $valid = false;
  die();
}

elseif (isset($_POST['username'],$_POST['password'])) { // Data is stored in a just-submitted login form.
  $user = sqlArr('SELECT * FROM user WHERE username = "' . mysqlEscape($_POST['username']) . '" LIMIT 1');

  if (!$user['userid'] > 0) { // No user entry exists, so the username is wrong.
    $valid = false;
    $flag = 'nouser';
  }
  else { // If the database password is the same as the password in the cookies or post data, then the user is valid.
    if ($user['password'] === md5(md5($_POST['password']) . $user['salt'])) { // The password matches.
      $valid = true;
      $sessionhash = md5(uniqid(microtime(), true)); // Generate the sessionhash, which should be unique to this browsing session.

      if ($_POST['rememberme']) { // This will store the user's login information in the browser's cookies for one week.
        setcookie('bbuserid',$user['userid'],time() + 60 * 60 * 24 * 365,'/','.victoryroad.net'); // Set the cookie for userid.
        setcookie('bbpassword',md5($user['password'] . COOKIE_SALT),time() + 60 * 60 * 24 * 365,'/','.victoryroad.net'); // Set the cookie for password.
      }

      setcookie('bbsessionhash',$sessionhash,0,'/','.victoryroad.net'); // Set the cookie for the unique session.

      mysqlQuery('INSERT INTO session SET sessionhash = "' . $sessionhash . '", idhash="' . $idhash . '", userid = "' . $user['userid'] . '", host = "' . $_SERVER['REMOTE_ADDR'] . '", lastactivity = "' . time()  . '", location="chat.php", useragent="' . $_SERVER['HTTP_USER_AGENT'] . '", loggedin = 2'); // Add to the vBulletin session table for the who's online.
    }
    else { // Password is wrong.
      $valid = false;
      $flag = 'nopass';
    }
  }
}

elseif (isset($_GET['sessionhash'])) {
  $session = sqlArr('SELECT * FROM session WHERE sessionhash = "' . mysqlEscape($_GET['sessionhash']) . '"');

  $user = sqlArr('SELECT * FROM user WHERE userid = "' . $session['userid'] . '"'); // Query from vBulletin user table.

  if ($user['userid'] > 0) { // Just in case a 0 user id pops up somehow. Also weeds out the userid being false.
    $valid = true;

    if ($_GET['stalesession'] == false) {
      mysqlQuery('UPDATE session SET lastactivity = "' . time() . '", useragent = "' . $_SERVER['HTTP_USER_AGENT'] . '" WHERE sessionhash = "' . $session['sessionhash'] . '"');
    }
  }
  else {
    $valid = false;
  }
}

elseif (isset($_COOKIE['bbsessionhash'])) { // Data is stored in session cookie.
  $session = sqlArr('SELECT * FROM session WHERE sessionhash = "' . mysqlEscape($_COOKIE['bbsessionhash']) . '"');

  if (!$session) {
    if ($_COOKIE['bbuserid']) goto cookieuserid;
    else $valid = false;
  }
  else {
    $user = sqlArr('SELECT * FROM user WHERE userid = "' . $session['userid'] . '"'); // Query from vBulletin user table.

    if ($user['userid'] > 0) { // Just in case a 0 user id pops up somehow. Also weeds out the userid being false.
      $valid = true;

      mysqlQuery('UPDATE session SET location="/chat.php", lastactivity = "' . time() . '", useragent = "' . $_SERVER['HTTP_USER_AGENT'] . '" WHERE userid="' . mysqlEscape($user['userid']) . '"');

      setcookie('bbsessionhash',$_COOKIE['bbsessionhash'],0,'/','.victoryroad.net'); // Keep the session cookie fresh.

      if ($_COOKIE['bbuserid']) { // More freshness!
        setcookie('bbuserid',$_COOKIE['bbuserid'],time() + 60 * 60 * 24 * 365,'/','.victoryroad.net'); // Set the cookie for userid.
        setcookie('bbpassword',$_COOKIE['bbpassword'],time() + 60 * 60 * 24 * 365,'/','.victoryroad.net'); // Set the cookie for password.
      }
    }
    else {
      $valid = false;
    }
  }
}

elseif (isset($_COOKIE['bbuserid'],$_COOKIE['bbpassword'])) { // Data is stored in long-lasting cookies.
  cookieuserid:
  $user = sqlArr('SELECT * FROM user WHERE userid = "' . mysqlEscape($_COOKIE['bbuserid']) . '" AND "' . mysqlEscape($_COOKIE['bbpassword'])  . '" = MD5(CONCAT(password,"' . COOKIE_SALT . '"))'); // Query from vBulletin user table.

  if ($user['userid'] > 0) { // Just in case a 0 user id pops up sommysqlEscape(mysqlEscape(ehow. Also weeds out the userid being false.
    $valid = true;
 
    setcookie('bbuserid',$_COOKIE['bbuserid'],time() + 60 * 60 * 24 * 365,'/','.victoryroad.net'); // Set the cookie for userid.
    setcookie('bbpassword',$_COOKIE['bbpassword'],time() + 60 * 60 * 24 * 365,'/','.victoryroad.net'); // Set the cookie for password.

    mysqlQuery('UPDATE session SET location="/chat.php", lastactivity = "' . time() . '", useragent = "' . $_SERVER['HTTP_USER_AGENT'] . '" WHERE userid="' . mysqlEscape($user['userid']) . '"');
  }
  else {
    $valid = false;
  }
}

else { // No login data exists.
  $valid = false;
}


if ($valid) { // If the user is valid, process their preferrences.
  userprefs:
  $userprefs = sqlArr('SELECT * FROM ' . $sqlPrefix . 'users WHERE userid = ' . $user['userid']); // Should be merged into the above $user query, but because the two don't automatically sync for now it can't be. A manual sync, plus setting up the userpref row in the first event would fix this.
  if (!$userprefs) {
    mysqlQuery('INSERT INTO ' . $sqlPrefix . 'users SET userid = ' . $user['userid']); // Create the new row.
    goto userprefs; // Not good practice, but it works well enough.
  }
  $user = array_merge($user,$userprefs); // Merge userprefs into user for future referrence.

/*  if (($_COOKIE['bbstyleid'] != $user['styleid']) && $_COOKIE['bbstyleid']) {
    mysqlQuery('UPDATE user SET styleid = ' . intval($_COOKIE['bbstyleid']) . ' WHERE userid = ' . $user['userid']);
  }*/

  if ($bannedUserGroups) {
    if (inArray($bannedUserGroups,explode(',',$user['membergroupids']))) $banned = true;
  }

  if (($user['settings'] & 1) || $banned) {
    $banned = true;
  }
  elseif ($api) {
    header('Content-Type: text/plain');

    $error = ($error ?: $upgradeFlag);

    $dieString = "'$user[userid]','$status','$error','$sessionhash'";
    if ($_GET['extraInfo']) $dieString .= ",'$user[username]','$user[settings]','$user[favRooms]'";

    die($dieString); // Nothing should actually be done further, as we're just getting a proper login set up.
  }
}

else { // If the user is not valid, remove all user data. If a user's name is correct but not the password, the user variable could contain sensitive data which should not be seen.
  unset($user);
  $user['settings'] = 45; // Set the user prefs to their defaults.
  $user['membergroupids'] = '1';
  $user['userid'] = 0;
}


/* Process Daylight Savings Time */
if ($user['options'] & 64) $user['timezoneoffset']++; // DST is autodetect. We'll just set it by hand.
elseif ($user['options'] & 128) $user['timezoneoffset']++; // DST is on, add an hour
else $user['timezoneoffset']; // DST is off


/* Process Style Settings */
if (isset($_GET['styleid'])) { // Use any style setting in the URL if possible.
  $user['styleid'] = $_GET['styleid'];
}
elseif (isset($_COOKIE['vrim-styleid'])) {
  $user['styleid'] = $_COOKIE['vrim-styleid']; // Use any style in a user's cookies if possible.
}
elseif (!$user['styleid']) {
  $user['styleid'] = 20;
}

if (!in_array($user['userid'],array(1,179,1476,1948))) {
  header('HTTP/1.1 403 Forbidden');
  die();
}
?>