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
   along with this program.  If not, see <http://www.gnu.org/licenses/>. */

/** Creates a New User
 * Only valid with certain login backends.
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2014
 *
 * @param userName - The user's name.
 * @param password - The user's password.
 * @param passwordEncrypt - The method of encryption used to send the password. 'sha256' indicates sha256(password), 'sha256-salt' indicates sha256(sha256(password) . passwordSalt). 'sha256-salt' is discouraged, since it prevents the system from using an unstored salt that prevents against bruteforcing if the database is hacked.
 * @param passwordSalt - The salt used for encrypting the password, if it is encrypted using 'sha256-salt' or 'sha256-salt'. Any salt can be used, though long ones will be truncated to 50 characters, and only certain characters are allowed.
 * @param email - The email of the user.
 * @param birthdate - The date-of-birth of the user (unix timestamp).
 *
 * TODO: Captcha Support, IP Limits, Email Restricted/Allowed Domains, Birthdate Filter, Censor Names
 */



$apiRequest = true;
$ignoreLogin = true;

require('../global.php');
require('../functions/fim_uac_vanilla.php');

/* Get Request Data */
$request = fim_sanitizeGPC('p', array(
  'userName' => array(
    'default' => '',
  ),

  'password' => array(
    'default' => '',
  ),

  'passwordEncrypt' => array(
    'valid' => array('plaintext', 'sha256', 'sha256-salt'),
    'default' => '',
  ),

  'passwordSalt' => array(
    'cast' => 'ascii128',
  ),

  'email' => array(
    'default' => '',
  ),

  'birthdate' => array(
    'cast' => 'int',
  ),
));


$userAge = fim_dobToAge($request['birthdate']); // Generate the age in years of the user.

/* Start Processing */
if ($continue) {
  if ($loginConfig['method'] != 'vanilla') {
    $errStr = 'notSupported';
    $errDesc = 'This script only works for servers using vanilla logins.';
  }
  elseif ($user['userId'] && (($config['anonymousUserId'] && $user['userId'] != $config['anonymousUserId']) || !$config['anonymousUserId'])) {
    $errStr = 'loggedIn';
    $errDesc = 'You are already logged-in.';
  }
  elseif (count($slaveDatabase->getUsers(array(
    'userNames' => $request['userName'],
  ))->getAsArray(true)) > 0) {
      $errStr = 'userExists';
    $errDesc = 'That user specified already exists.';
  }
  elseif (!$request['userName']) {
    $errStr = 'noUserName';
    $errDesc = 'No user name was specified.';
  }
  elseif ($config['requireEmail'] && !$request['email']) {
    $errStr = 'noEmail';
    $errDesc = 'No email was specified.';
  }
  elseif ($request['email'] && (!filter_var($request['email'], FILTER_VALIDATE_EMAIL))) {
    $errStr = 'badEmail';
    $errDesc = 'The email specified is not allowed.';
  }
  elseif (!$request['password']) {
    $errStr = 'noPassword';
    $errDesc = 'No password was specified.';
  }
  elseif (!$request['passwordEncrypt']) {
    $errStr = 'noPasswordEncrypt ';
    $errDesc = 'A valid password encryption was not specified.';
  }
  elseif ($config['ageRequired'] && !isset($request['birthdate'])) {
    $errStr = 'ageRequired';
    $errDesc = 'An age must be specified to continue.';
  }
  elseif (isset($request['birthdate']) && ($userAge > $config['ageMaximum'])) {
    $errStr = 'ageMaximum';
    $errDesc = 'The age specified exceeds the maximum age allowed by the server.';
  }
  elseif (isset($request['birthdate']) && ($userAge < $config['ageMinimum'])) {
    $errStr = 'ageMinimum';
    $errDesc = 'The age specified is below the minimum age allowed by the server.';
  }
  else {
    // Get Salts Used For Encryption
    if ($salts) {
      $encryptSalt = end($salts); // Move the file pointer to the last entry in the array (and return its value)
      $encryptSaltNum = key($salts) + 1; // Get the key/id of the corrosponding salt.
    }
    else {
      $encryptSalt = '';
      $encryptSaltNum = 0;
    }

    $passwordSalt = fim_generateSalt(); // Generate a random salt.


    // Encrypt Sent Password
    switch ($request['passwordEncrypt']) {
      case 'plaintext':
      $password = fim_generatePassword($request['password'], $passwordSalt, $encryptSaltNum, 0);
      break;

      case 'sha256':
      $password = fim_generatePassword($request['password'], $passwordSalt, $encryptSaltNum, 1);
      break;

      case 'sha256-salt':
      $password = fim_generatePassword($request['password'], $passwordSalt, $encryptSaltNum, 2);
      break;

      default:
      $errStr = 'badEncryption';
      $errDesc = 'The password encryption specified is not supported.';

      $continue = false;
      break;
    }

    // Generate Value for User Privs
    $userPrivs = 16;
    if ($config['userRoomCreation']) $userPrivs += 32;
    if ($config['userPrivateRoomCreation']) $userPrivs += 64;


    // Create Userdata Array
    $userData = array(
      'userName' => $request['userName'],
      'password' => $password,
      'passwordSalt' => $passwordSalt,
      'passwordSaltNum' => $encryptSaltNum,
      'birthDate' => $request['birthdate'],
      'joinDate' => time(),
      'email' => $request['email'],
      'userPrivs' => $userPrivs,
    );


    // Insert Data
    if ($continue) {
      $database->startTransaction();

      $database->insert("{$sqlPrefix}users", $userData);
      $userId = $database->insertId;

      $database->endTransaction();
    }
  }
}



/* Data Define */
$xmlData = array(
  'sendUser' => array(
    'activeUser' => array(
      'userId' => (int) $userId,
      'userName' => ($userData['userName']),
    ),
    'errStr' => ($errStr),
    'errDesc' => ($errDesc),
    'censor' => array(
      'word' => ($blockWordApi['word']),
      'severity' => ($blockWordApi['severity']),
      'reason' => ($blockWordApi['reason']),
    ),
  ),
);



/* Output Data */
echo new apiData($xmlData);
?>