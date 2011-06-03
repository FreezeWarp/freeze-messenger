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


$reqPhrases = true;
$reqHooks = true;

require_once('../global.php'); // Used for everything.

if (!$_GET['roomid']) {
  trigger_error('A roomid was not specified',E_USER_ERROR);
}
else {
  $room = intval($_GET['roomid']); // Get the room we're on. If there is a $_GET variable, use it, otherwise the user's "default", or finally just main.
  $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = '$room'"); // Data on the room.

  $phase = $_GET['phase'];
  if (!$phase) $phase = '1'; // Default to phase 1.


  $listsActive = sqlArr("SELECT * FROM {$sqlPrefix}censorBlackWhiteLists WHERE roomid = $room[id]",'id');
  if ($listsActive) {
    foreach ($listsActive AS $active) {
      $listStatus[$active['listid']] = $active['status'];
    }
  }


  $lists = sqlArr("SELECT * FROM {$sqlPrefix}censorLists AS l WHERE options & 2",'id');
  foreach ($lists AS $list) {
    if ($list['type'] == 'black' && $listStatus[$list['id']] == 'block') $checked = true;
    elseif ($list['type'] == 'white' && $listStatus[$list['id']] != 'unblock') $checked = true;
    else $checked = false;

    $censorLists .= "<label><input type=\"checkbox\" name=\"censor[$list[id]]\" " . ($checked ? " checked=\"checked\""  : '') . " /> $list[name]</label><br />";
  }

  if ($phase == '1') {
    if (!$room) trigger_error('This is not a valid room (roomid = ' . $_GET['roomid'] . ').',E_USER_ERROR);
    elseif ($user['userid'] != $room['owner'] && !($user['settings'] & 16)) trigger_error('You must be the owner to edit this room',E_USER_ERROR);
    elseif ($room['settings'] & 4) trigger_error('This room is deleted, and as such may not be edited.',E_USER_ERROR);
    else {
      echo template('editRoomForm');
    }
  }
  elseif ($phase == '2') {s
  }
  else {
    trigger_error($phrases['editRoomUnknownAction'],E_USER_ERROR);
  }
}
?>