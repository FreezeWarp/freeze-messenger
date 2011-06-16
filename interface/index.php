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



require_once('../global.php');



/* Get the room we're in */
$room = (int) ($_GET['room'] ? $_GET['room'] :
  ($user['defaultRoom'] ? $user['defaultRoom'] :
    ($defaultRoom ? $defaultRoom : 1))); // Get the room we're on. If there is a $_GET variable, use it, otherwise the user's "default", or finally just main.
$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE roomId = '$room'"); // Data on the room.



require_once('templateStart.php');

if ($valid) {

  ($hook = hook('$$ajaxOfficial_chat_start') ? eval($hook) : '');


  /* Favourite Room Cleanup
   * Remove all favourite groups a user is no longer a part of.
   * TODO: Move into Javascript (already possible via API) */

  if ($user['favRooms']) {
    $stop = false;

    $favRooms = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE options & 4 = FALSE AND roomId IN ($user[favRooms])",'id');

    foreach ($favRooms AS $id => $room2) {
      eval(hook('templateFavRoomsEachStart'));

      if (!fim_hasPermission($room2,$user,'post') && !$stop) {
        $currentRooms = explode(',',$user['favRooms']);
        foreach ($currentRooms as $room3) if ($room3 != $room2['roomId'] && $room3 != '') {
          $currentRooms2[] = $room3; // Rebuild the array without the room ID.
        }

        $newRoomString = mysqlEscape(implode(',',$currentRooms2));

        mysqlQuery("UPDATE {$sqlPrefix}users SET favRooms = '$newRoomString' WHERE userId = $user[userId]");

        $stop = false;

        continue;
      }

      $room2['name'] = fim_encodeXml($room2['name']);

      $roomMs .= template('templateRoomMs');
      $roomHtml .= template('templateRoomHtml');

      $template['roomHtml'] = $roomHtml;
      $template['roomMs'] = $roomMs;
    }
  }




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
  if ($_POST['action'] == 'register') {
    if ($loginMethod == 'vanilla') {
      // TODO
    }

    else {
      trigger_error('Registration disabled for this login backend.');
    }
  }
}

echo template('templateEnd');
?>