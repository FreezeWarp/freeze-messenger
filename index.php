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

if (!file_exists('config.php')) {
  if (file_exists('install.php')) {
    header('Location: install.php');
    die('FreezeMessenger must first be installed. <a href="install.php">Click here</a> to do so.');
  }
  else {
    die('FreezeMessenger must first be installed. Please modify config-base.php and save as config.php.');
  }
}


$inRoom = true;
$title = 'Chat';
$reqPhrases = true;
$reqHooks = true;



require_once('global.php');



/* Get the room we're in */
$room = (int) ($_GET['room'] ? $_GET['room'] :
  ($user['defaultRoom'] ? $user['defaultRoom'] :
    ($defaultRoom ? $defaultRoom : 1))); // Get the room we're on. If there is a $_GET variable, use it, otherwise the user's "default", or finally just main.
$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE roomId = '$room'"); // Data on the room.



require_once('templateStart.php');


if ($valid) {


  ($hook = hook('$$ajaxOfficial_chat_end') ? eval($hook) : '');




  if ($banned) { // Check that the user isn't banned.
    ($hook = hook('$$ajaxOfficial_chat_banned') ? eval($hook) : '');

    echo container($phrases['chatBannedTitle'],$phrases['chatBannedMessage']);
  }

  elseif (!$room) { // No room data was returned.
    ($hook = hook('$$ajaxOfficial_chat_roomNull') ? eval($hook) : '');

    trigger_error($phrases['chatRoomDoesNotExist'],E_USER_ERROR);
  }

  else {

    list($fim_hasPermission,$hPC,$hPT) = fim_hasPermission($room,$user,'post',true);

    if (($room['options'] & 2) && (($user['settings'] & 64) == false)) {
      echo template('chatMatureWarning');
    }

    elseif ($fim_hasPermission) { // The user is not banned, and is allowed to view this room.
      ($hook = hook('$$ajaxOfficial_chat_preTemplate') ? eval($hook) : '');

      if ($stopMessage) {
        $chatTemplate = template('chatStopMessage');
      }

      $chatTemplate .= template('chatTemplate');
    }

    else {
      switch ($hPC) {
        case 'general':
        default:
        $hPM = '[snobs and stuff]';
        break;
        case 'banned':
        $hPM = '[banned and stuff]';
        break;
        case 'kicked':
        $hPM = 'You have been kicked from this room. Your kick will expire on ' . fim_date('m/d/Y g:i:sa',$hPT) . '.';
        break;
      }

      ($hook = hook('$$ajaxOfficial_chat_accessDenied') ? eval($hook) : '');

      $chatTemplate = container('Access Denied',$hPM);
    }


    echo template('chatInnerTemplate');
  }

  ($hook = hook('$$ajaxOfficial_chat_end') ? eval($hook) : '');
}
else {
  ($hook = hook('$$ajaxOfficial_login_start') ? eval($hook) : '');

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

  ($hook = hook('$$ajaxOfficial_login_end') ? eval($hook) : '');
}

echo template('templateEnd');
?>