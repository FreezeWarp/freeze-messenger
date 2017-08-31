<?php
/* FreezeMessenger Copyright © 2017 Joseph Todd Parsons

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
 * @copyright Joseph T. Parsons 2017
 *
 * (Want password security? Install on TLS.)
 *
 * @param userName - The user's name.
 * @param password - The user's password.
 * @param email - The email of the user.
 * @param birthdate - The date-of-birth of the user (unix timestamp).
 *
 * TODO: Captcha Support, IP Limits, Email Restricted/Allowed Domains, Censor Names
 * (Everything, really.)
 */



$apiRequest = true;
$ignoreLogin = true;

require('../global.php');

/* Get Request Data */
$request = fim_sanitizeGPC('p', [
    'userName' => [
        'default' => '',
    ],

    'password' => [
        'default' => '',
    ],

    'email' => [
        'default' => '',
    ],

    'birthdate' => [
        'cast' => 'int',
    ],
]);
$database->accessLog('sendUser', $request);


$userAge = fim_dobToAge($request['birthdate']); // Generate the age in years of the user.

/* Start Processing */
if ($loginConfig['method'] != 'vanilla')
    throw new fimError('notSupported', 'This script only works for servers using vanilla logins.');

elseif ($user->id && !$user->isAnonymousUser())
    throw new fimError('loggedIn', 'You are already logged-in.');

elseif (count($database->getUsers(['userNames' => $request['userName']])->getAsArray(true)) > 0)
    throw new fimError('userExists', 'That user specified already exists.');

elseif (!$request['userName'])
    throw new fimError('noUserName', 'No user name was specified.');

elseif (['requireEmail'] && !$request['email'])
    throw new fimError('noEmail', 'No email was specified.');

elseif ($request['email'] && (!filter_var($request['email'], FILTER_VALIDATE_EMAIL)))
    throw new fimError('badEmail', 'The email specified is not allowed.');

elseif (!$request['password'])
    throw new fimError('noPassword', 'No password was specified.');

elseif ($config['ageRequired'] && !isset($request['birthdate']))
    throw new fimError('ageRequired', 'An age must be specified to continue.');

elseif (isset($request['birthdate']) && ($userAge < $config['ageMinimum']))
    throw new fimError('ageMinimum', 'The age specified is below the minimum age allowed by the server.');

else {
    // Create Userdata Array
    if (!(new fimUser(0))->setDatabase([
          'userName'  => $request['userName'],
          'password'  => $request['password'],
          'birthDate' => $request['birthdate'],
          'email'     => $request['email']
      ])) {
        throw new fimError("userCreationFailed", "Could not create user.");
    }
}



/* Data Define */
$xmlData = [
    'sendUser' => [
        'userName' => $request['userName']
    ],
];



/* Output Data */
echo new apiData($xmlData);
?>