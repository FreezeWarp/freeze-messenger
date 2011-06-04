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

$noReqLogin = true;
$reqHooks = true;
$reqPhrases = true;
$title = 'Login';

require_once('global.php');


eval(hook('loginStart'));


if ($valid) {
  header('Location: chat.php');
}

require_once('functions/container.php');
require_once('templateStart.php');

if ($flag) {
  switch($flag) {
    case 'nouser': $message .= $phrases['loginNoUser']; break; // No user with that userName exists.
    case 'nopass': $message .= $phrases['loginNoPass']; break; // The password is wrong.
  }

  eval(hook('loginFlag'));

  if ($message) {
    echo container($phrases['loginBad'],$message);
  }
}

echo container($phrases['loginTitle'],template('login'));

echo container($phrases['loginGuestLinks'],template('guestLinks'));


eval(hook('loginEnd'));


require_once('templateEnd.php');