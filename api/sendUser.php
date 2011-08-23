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

/** Creates a New User
 * Only valid with certain login backends.
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
 *
 * @param userName - The user's name.
 * @param password - The user's password.
 * @param passwordEncrypt - The method of encryption used to send the password.
 * @param email - The email of the user.
 * @param dob - The date-of-birth of the user (unix timestamp).
*/

$apiRequest = true;

require('../global.php');
require('../functions/fim_uac_vanilla.php');



/* Get Request Data */
$request = fim_sanitizeGPC(array(
  'post' => array(
    'userName' => array(
      'type' => 'string',
    ),
    'password' => array(
      'type' => 'string',
    ),
    'passwordEncrypt' => array(
      'type' => 'string',
    ),
    'email' => array(
      'type' => 'string',
    ),
    'dob' => array(
      'type' => 'string',
    ),
  ),
));


switch ($request['passwordEncrypt']) {
  case 'plaintext':
  $passwordDecrypted = $request['passwordEncrypt'];
  break;

  default:
  $errStr = 'badEncryption';
  $errDesc = 'The message encryption specified is not supported.';

  $continue = false;
  break;
}


($hook = hook('sendUser_start') ? eval($hook) : '');


if ($continue) {
  if ($loginConfig['method'] != 'vanilla') {
    $errStr = 'notSupported';
    $errDesc = 'This script only works for servers using vanilla logins.';
  }
  elseif ($user['userId'] && (($config['anonymousUserId'] && $user['userId'] != $config['anonymousUserId']) || !$config['anonymousUserId'])) {
    $errStr = 'logginIn';
    $errDesc = 'You are already logged-in.';
  }
  elseif ($database->getUser(false, $request['userName']) {
    $errStr = 'userExists';
    $errDesc = 'That user specified already exists.';
  }
  elseif (!$request['userName']) {
    $errStr = 'noUserName';
    $errDesc = 'No user name was specified.';
  }
  elseif (!$request['email']) {
    $errStr = 'noEmail';
    $errDesc = 'No email was specified.';
  }
  elseif (!$passwordDecrypted) {
    $errStr = 'noPassword';
    $errDesc = 'No password was specified.';
  }
  else {
    $password = fim_generatePassword($passwordDecrypted, fim_generateSalt());
    $userData = array(
      'userName' => $request['userName'],
      'password' => $password,
      'dob' => $request['dob'],
      'email' => $request['email'],
    );

    ($hook = hook('sendUser_preAdd') ? eval($hook) : '');

    if ($continue) {
      $database->insert("{$sqlPrefix}users", $userData);
    }
  }
}



/* Data Define */
$xmlData = array(
  'sendUser' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
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



/* Plugin Hook End */
($hook = hook('sendUser_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);



/* Close Database Connection */
dbClose();
?>