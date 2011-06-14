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

$inRoom = true;
$title = 'Chat';
$reqPhrases = true;
$reqHooks = true;

require_once('global.php');


if (!$valid) {
  header('Location: login.php');
  die('You are not authenticated. <a href="login.php">Click here to login.</a>');
}


/* Get the room we're in */
$room = intval($_GET['room'] ?: $user['defaultRoom'] ?: 1); // Get the room we're on. If there is a $_GET variable, use it, otherwise the user's "default", or finally just main.
$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE roomId = '$room'"); // Data on the room.

$bodyHook = ' data-roomId="' . $room['roomId'] . '" data-ding="' . ($user['optionDefs']['audioDing'] ? 1 : 0) . '" data-reverse="' . ($user['optionDefs']['reversePostOrder'] ? 1 : 0) . '" data-complex="' . ($user['optionDefs']['showAvatars'] ? 1 : 0) . '" data-longPolling="' . ($longPolling ? 1 : 0) . '"';


eval(hook('chatStart'));


require_once('templateStart.php');


eval(hook('chatStartOutput'));


if ($banned) { // Check that the user isn't banned.
  eval(hook('chatBanned'));

  echo container($phrases['chatBannedTitle'],$phrases['chatBannedMessage']);
}

elseif (!$room) { // No room data was returned.
  eval(hook('chatRoomDoesNotExist'));

  trigger_error($phrases['chatRoomDoesNotExist'],E_USER_ERROR);
}

else {

  list($fim_hasPermission,$hPC,$hPT) = fim_hasPermission($room,$user,'post',true);

  if (($room['options'] & 2) && (($user['settings'] & 64) == false)) {
    echo template('chatMatureWarning');
  }

  elseif ($fim_hasPermission) { // The user is not banned, and is allowed to view this room.

    if ((($room['options'] & 1) == false) && (($user['settings'] & 64) == false)) {
      if ($room['options'] & 16) {
        $stopMessage = $phrases['chatPrivateRoom'];
      }
      else {
        $stopMessage = $phrases['chatNotModerated'];
      }
    }

    if (($user['settings'] & 16) && ((($room['owner'] == $user['userId'] && $room['owner'] > 0) || (in_array($user['userId'],explode(',',$room['allowedUsers'])) || $room['allowedUsers'] == '*') || (in_array($user['userId'],explode(',',$room['moderators']))) || ((fim_inArray(explode(',',$user['membergroupids']),explode(',',$room['allowedGroups'])) || $room['allowedGroups'] == '*') && ($room['allowedGroups'] != ''))) == false)) {
      $stopMessage = $phrases['chatAdminAccess'];
    }

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

    $chatTemplate = container('Access Denied',$hPM);
  }

  $canModerate = fim_hasPermission($room,$user,'moderate');


  echo template('chatInnerTemplate');
}


eval(hook('chatEnd'));

echo template('templateEnd');
?>