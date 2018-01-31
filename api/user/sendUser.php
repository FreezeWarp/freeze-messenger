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
 * @package   fim3
 * @version   3.0
 * @author    Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 *
 * (Want password security? Install on TLS.)
 *
 * TODO: Captcha Support, IP Limits, Email Restricted/Allowed Domains, Censor Names
 * (Everything, really.)
 */


/* Prevent Direct Access of File */

use Fim\Error;
use Fim\User;

if (!defined('API_INUSER'))
    die();



/* Get Request Data */
$request = \Fim\Utilities::sanitizeGPC('p', [
    'name' => [
        'require' => true,
    ],

    'password' => [
        'require' => true,
    ],

    'email' => [
        'require' => \Fim\Config::$emailRequired,
    ],

    'birthDate' => [
        'require' => \Fim\Config::$ageRequired,
        'cast'    => 'int',
    ],
]);

\Fim\Database::instance()->accessLog('sendUser', $request, true);


/* Start Processing */
if (isset($request['birthDate']))
    $age = \Fim\Utilities::dobToAge($request['birthDate']);
else
    $age = \Fim\Config::$parentalAgeDefault;


if ($loginConfig['method'] === 'vanilla' && !\Fim\Config::$registrationEnabled)
    new \Fim\Error('registrationDisabled', 'Registration is disabled on this server.');

elseif ($loginConfig['method'] !== 'vanilla' && !\Fim\Config::$registrationEnabledIgnoreForums)
    new \Fim\Error('registrationDisabled', 'Registration is disabled on this server. Please register on the forum.');

elseif ($user->id && !$user->isAnonymousUser())
    new \Fim\Error('loggedIn', 'You are already logged-in.');

elseif ($request['email'] && (!filter_var($request['email'], FILTER_VALIDATE_EMAIL)))
    new \Fim\Error('emailInvalid', 'The email specified is not allowed.');

elseif (strlen($request['password']) < \Fim\Config::$passwordMinimumLength)
    new \Fim\Error('passwordMinimumLength', 'The password provided is too short.');

elseif (isset($request['birthDate']) && ($age < \Fim\Config::$ageMinimum))
    new \Fim\Error('ageMinimum', 'The age specified is below the minimum age allowed by the server.', [
        'ageDetected' => $age,
        'ageMinimum'  => \Fim\Config::$ageMinimum
    ]);

elseif (\Fim\Database::instance()->getUsers(['userNames' => [$request['name']]])->getCount() > 0)
    new \Fim\Error('nameTaken', 'That user specified already exists.');

else {
    $newUser = new User(0);
    if (!$newUser->setDatabase(array_merge(
        \Fim\Utilities::arrayFilterKeys($request, ['name', 'password', 'birthDate', 'email']),
        ['parentalAge' => \Fim\Utilities::nearestAge($age)]
    ))) {
        new \Fim\Error("userCreationFailed", "Could not create user.");
    }
}


/* Output Data */
echo new Http\ApiData([
    'user' => [
        'id' => $newUser->id,
        'name' => $newUser->name,
    ],
]);
?>