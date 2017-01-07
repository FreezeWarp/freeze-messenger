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
if ($loginConfig['method'] != 'vanilla') {
  throw new Exception('notSupported', 'This script only works for servers using vanilla logins.');
}
elseif ($user->id && !$user->isAnonymousUser()) {
  throw new Exception('loggedIn', 'You are already logged-in.');
}
elseif (count($slaveDatabase->getUsers(array(
  'userNames' => $request['userName'],
))->getAsArray(true)) > 0) {
  throw new Exception('userExists', 'That user specified already exists.');
}
elseif (!$request['userName']) {
  throw new Exception('noUserName', 'No user name was specified.');
}
elseif (['requireEmail'] && !$request['email']) {
  throw new Exception('noEmail', 'No email was specified.');
}
elseif ($request['email'] && (!filter_var($request['email'], FILTER_VALIDATE_EMAIL))) {
  throw new Exception('badEmail', 'The email specified is not allowed.');
}
elseif (!$request['password']) {
  throw new Exception('noPassword', 'No password was specified.');
}
elseif (!$request['passwordEncrypt']) {
  throw new Exception('noPasswordEncrypt ', 'A valid password encryption was not specified.');
}
elseif ($config['ageRequired'] && !isset($request['birthdate'])) {
  throw new Exception('ageRequired', 'An age must be specified to continue.');
}
elseif (isset($request['birthdate']) && ($userAge > $config['ageMaximum'])) {
  throw new Exception('ageMaximum', 'The age specified exceeds the maximum age allowed by the server.');
}
elseif (isset($request['birthdate']) && ($userAge < $config['ageMinimum'])) {
  throw new Exception('ageMinimum', 'The age specified is below the minimum age allowed by the server.');
}
else{
  // Create Userdata Array
  if ((new fimUser(0))->setDatabase(array(
      'userName' => $request['userName'],
      'password' => $request['password'],
      'birthDate' => $request['birthdate'],
      'email' => $request['email']
  ))) {
    throw new fimError("userCreationFailed", "Could not create user.");
  }

  var_dump($database->queryLog); die('3');
}



/* Data Define */
$xmlData = array(
  'sendUser' => array(

  ),
);



/* Output Data */
echo new apiData($xmlData);
?>