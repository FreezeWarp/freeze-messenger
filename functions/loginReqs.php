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





/**
* Database auth plug-in for phpBB3
*
* Authentication plug-ins is largely down to Sergey Kanareykin, our thanks to him.
*
* This is for authentication via the integrated user table
*
* @package login
* @version $Id$
* @copyright (c) 2005 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

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





///* FIM *///

function fim_generateSession() {
  global $salts;

  /* The algorithm below may not be ideal (or moreover redundant). It is intended to minimize the ability to guess a hash via a bruteforce mechanism (and does so using the 256-bit SHA256 hash, a random value using the relatively good mt_rand generator that varies between 1 and a billion, and the salt specified by the site operator). */

  if (count($salts) > 0) {
    $salt = end($salts);
  }
  else {
    $salt = rand(1,100000000);
  }


  if (function_exists('mt_rand')) {
    $rand = mt_rand(1,1000000000);
  }
  elseif (function_exists('rand')) {
    $rand = rand(1,1000000000);
  }

  if (function_exists('hash')) {
    return str_replace('.','',uniqid('',true)) . hash('sha256',hash('sha256',$rand) . $salt);
  }
  else {
    return str_replace('.',uniqid('',true)) . md5(md5($rand) . $salt);
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
  global $forumTablePrefix;

  if (!$user['userId']) { // The user does not exists
    define('LOGIN_FLAG','BAD_USERNAME');

    return false;
  }

  elseif ($user['password'] === md5($password . $user['salt'])) { // The password matches.
    return true;
  }

  else {
    define('LOGIN_FLAG','BAD_PASSWORD');

    return false;
  }
}

function processPHPBB($user, $password) {
  global $forumTablePrefix, $brokenUsers;

  if (!$user['userId']) { // The user does not exist

    define('LOGIN_FLAG','BAD_USERNAME');

    return false;
  }

  elseif (in_array($user['userId'],$brokenUsers)) { // The user is flagged as a PHPBB auto user.
    define('LOGIN_FLAG','BROKEN_USER');

    return false;
  }

  elseif (phpbb_check_hash($password, $user['password'])) { // The password matches
    return true;
  }

  else {
    define('LOGIN_FLAG','BAD_PASSWORD');

    return false;
  }
}

function processVanilla($user, $password) {
  global $tablePrefix, $sqlUserTable, $sqlUserTableCols;

  if (!$user['userId']) { // The user does not exist
    define('LOGIN_FLAG','BAD_USERNAME');

    return false;
  }

  else if ($something) { // TODO

  }

  else {

  }
}

function processLogin($user, $password, $encrypt) {
  global $loginConfig;

  switch ($loginConfig['method']) {
    case 'vbulletin3':
    if ($encrypt == 'plaintext') {
      $password = md5($password);
    }

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
?>