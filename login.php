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
    case 'nouser': $message .= $phrases['loginNoUser']; break; // No user with that username exists.
    case 'nopass': $message .= $phrases['loginNoPass']; break; // The password is wrong.
  }

  eval(hook('loginFlag'));

  if ($message) {
    echo container($phrases['loginBad'],$message);
  }
}

  echo container($phrases['loginTitle'],"<div id=\"normalLogin\">
  <br />

    <form action=\"/login.php\" method=\"post\" style=\"text-align: center; display: block;\">
    <label for=\"username\">$phrases[loginUsername]</label><br />
    <input type=\"text\" name=\"username\" /><br /><br />

      <label for=\"password\">$phrases[loginPassword]</label><br />
    <input type=\"password\" name=\"password\" /><br /><br />

      <label for=\"rememberme\">$phrases[loginRemember]</label>
    <input type=\"checkbox\" name=\"rememberme\" id=\"rememberme\" /><br /><br />

      <button type=\"submit\">$phrases[loginSubmit]</button><button type=\"reset\">$phrases[loginReset]</button><button type=\"button\" onclick=\"$('#normalLogin').slideUp(); $('#secureLogin').slideDown();\">Secure Login</button>
  </form>
</div>" . '

<div id="secureLogin" style="display: none;">
  Below you can login directly to the forums via SSL encyption:<br /><br />

  <span style="middle" onclick="$(this).html(\'<iframe src=&quot;https://www.victoryroad.net/login.php?do=vrimLogin&quot; style=&quot;width: 300px; height: 150px;&quot;>iFrames are disabled.</iframe>\');"><a href="javascript:void(0);">[Display Login]</a></span><br /><br />

  Once you have logged in, <a href="/">click here</a>.
</div>');

  echo container($phrases['loginGuestLinks'],'<ul>
  <li><a href="/index.php?action=online">Who\'s Online</a></li>
  <li><a href="/viewRooms.php">Room List</a></li>
  <li><a href="/archive.php">Archives</a></li>
  <ul>
    <li><a href="/index.php?action=archive&roomid=1">Main</a></li>
  </ul>
</ul>');


eval(hook('loginEnd'));


require_once('templateEnd.php');