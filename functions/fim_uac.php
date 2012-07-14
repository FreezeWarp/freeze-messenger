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
 * Creates a new session identifier that is highly random (so high that bruteforce would be virtually impossible), encrypted based on config salts (to reduce any chance of being able to guess the randomizer).
 *
 * @global array $salts - Key-value pairs used for encryption.
 * @return string - The generated session hash
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
*/
function fim_generateSession() {
  global $salts;

  if (count($salts) > 0) { // Check to see if any salts exist.
    $salt = end($salts); // If so, get the last entry.
  }
  else {
    $salt = mt_rand(1,1000000000); // If not, we will generate a second random integer, hopefully reducing any chance of guessing.
  }

  $rand = mt_rand(1,1000000000); // Generate a random integer that will be used to prevent guessing.

  $hash = str_replace('.', '', // Remove the period from the generated unique id
    uniqid('', true) // Generate a unique ID based on the current time, using extra entropy (see http://php.net/manual/en/function.uniqid.php)
  ) . fim_sha256(fim_sha256($rand) . $salt); // Create a sha256 hash of the random value, then hash that with the added user salt. Append this to $hash and the possibility of strategically guessing the sessionhash (which is, in truth, by far the lowest risk from the get-go) becomes nonexistent.

  return $hash; // Return the generated session hash.
}



/**
 * Verify a login using a vBulletin database. vBulletin3 and vBulletin4 appear to be identical.
 *
 * @param array user - The userdata array.
 * @param string password - The submitted password (usually via a form).

 * @return bool - Whether or not the user is valid.
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
*/
function processVBulletin($user, $password) {
  if (!$user['userId']) { // The user does not exists
    define('LOGIN_FLAG', 'BAD_USERNAME');

    return false;
  }

  elseif ($user['password'] === md5($password . $user['salt'])) { // The password matches.

    return true;
  }

  else { // The password does not match.
    define('LOGIN_FLAG', 'BAD_PASSWORD');

    return false;
  }
}



/**
 * Verify a login using a PHPBB3 database.
 *
 * @param array user - The userdata array.
 * @param string password - The submitted password (usually via a form).

 * @return bool - Whether or not the user is valid.
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
*/
function processPHPBB($user, $password) {
  if (!$user['userId']) { // The user does not exist
    define('LOGIN_FLAG', 'BAD_USERNAME');

    return false;
  }

  elseif (strlen($user['password']) === 0) { // PHPBB often stores passwords empty when the user shouldn't be able to login.'
    return false;
  }

  elseif (phpbb_check_hash($password, $user['password'])) { // The password matches.
    return true;
  }

  else { // The pasword does not match.
    define('LOGIN_FLAG', 'BAD_PASSWORD');

    return false;
  }
}



/**
 * Verify a login using a FIM's database.
 * @todo Make work
 *
 * @param array user - The userdata array.
 * @param string password - The submitted password (usually via a form).

 * @return bool - Whether or not the user is valid.
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
*/
function processVanilla($user, $password) {
  if (!$user['userId']) { // The user does not exist.
    define('LOGIN_FLAG', 'BAD_USERNAME');

    return false;
  }

  else if (fim_generatePassword($password, $user['passwordSalt'], $user['passwordSaltNum'], 2) === $user['password']) { // The password is correct.
    return true;
  }

  else { // The password is not correct.
    define('LOGIN_FLAG', 'BAD_PASSWORD');

    return false;
  }
}



/**
 * Generic validation wrapper.
 *
 * @param array user - The userdata array.
 * @param string password - The submitted password (usually via a form).

 * @return bool - Whether or not the user is valid.
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
*/
function processLogin($user, $password, $encrypt) {
  global $loginConfig;

  switch ($loginConfig['method']) {
    case 'vbulletin3':
    case 'vbulletin4':
    if ($encrypt == 'plaintext') {
      $password = md5($password);
    }

    return processVBulletin($user, $password);
    break;


    case 'phpbb':
    require(dirname(__FILE__) . '/fim_uac_phpbb3.php'); // Require PHPBB3 Library

    return processPHPBB($user, $password);
    break;


    case 'vanilla': // TODO
    require(dirname(__FILE__) . '/fim_uac_vanilla.php'); // Require Vanilla Library

    return processVanilla($user, $password);
    break;
  }
}
?>