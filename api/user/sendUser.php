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

/**
 * Creates a New User. Only valid with certain login backends.
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 *
 * (Want password security? Install on TLS.)
 *
 * TODO: Captcha Support, IP Limits, Email Restricted/Allowed Domains, Censor Names
 * (Everything, really.)
 */


/* Prevent Direct Access of File */
if (!defined('API_INUSER'))
    die();



/* Get Request Data */
$request = fim_sanitizeGPC('p', [
    'userName' => [
        'require' => true,
    ],

    'password' => [
        'require' => true,
    ],

    'email' => [
        'require' => $config['emailRequired'],
    ],

    'birthdate' => [
        'require' => $config['ageRequired'],
        'cast' => 'int',
    ],
]);

$database->accessLog('sendUser', $request);


/* Start Processing */
if ($loginConfig['method'] != 'vanilla')
    new fimError('notSupported', 'This script only works for servers using vanilla logins.');

elseif ($user->id && !$user->isAnonymousUser())
    new fimError('loggedIn', 'You are already logged-in.');

elseif ($request['email'] && (!filter_var($request['email'], FILTER_VALIDATE_EMAIL)))
    new fimError('emailInvalid', 'The email specified is not allowed.');

elseif (isset($request['birthdate']) && (fim_dobToAge($request['birthdate']) < $config['ageMinimum']))
    new fimError('ageMinimum', 'The age specified is below the minimum age allowed by the server.', [
        'ageDetected' => fim_dobToAge($request['birthdate']),
        'ageMinimum' => $config['ageMinimum']
    ]);

elseif ($database->getUsers(['userNames' => [$request['userName']]])->count() > 0)
    new fimError('userNameTaken', 'That user specified already exists.');

else {
    // Create Userdata Array
    if (!(new fimUser(0))->setDatabase([
          'userName'  => $request['userName'],
          'password'  => $request['password'],
          'birthDate' => $request['birthdate'] ?? 0,
          'email'     => $request['email'] ?? ''
      ])) {
        new fimError("userCreationFailed", "Could not create user.");
    }
}


/* Output Data */
echo new apiData([
    'sendUser' => [
        'userName' => $request['userName']
    ],
]);
?>