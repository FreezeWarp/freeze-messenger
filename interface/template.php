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
$reqPhrases = true;
$reqHooks = true;

require_once('../global.php');

$template = $_GET['template'];

switch ($template) {
   /* The below calculations should be replaced with the API once possible. */
//  if (!$room) trigger_error('This is not a valid room (roomId = ' . $_GET['roomId'] . ').',E_USER_ERROR);
//  elseif ($user['userId'] != $room['owner'] && !($user['settings'] & 16)) trigger_error('You must be the owner to edit this room',E_USER_ERROR);
//  elseif ($room['settings'] & 4) trigger_error('This room is deleted, and as such may not be edited.',E_USER_ERROR);

/*  $room = intval($_GET['roomId']); // Get the room we're on. If there is a $_GET variable, use it, otherwise the user's "default", or finally just main.
  if ($room) $room = dbRows("SELECT * FROM {$sqlPrefix}rooms WHERE id = '$room'"); // Data on the room.

  if ($room) $listsActive = dbRows("SELECT * FROM {$sqlPrefix}censorBlackWhiteLists WHERE roomId = $room[id]",'id');
  if ($listsActive) {
    foreach ($listsActive AS $active) {
      $listStatus[$active['listid']] = $active['status'];
    }
  }

  if ($room) $lists = dbRows("SELECT * FROM {$sqlPrefix}censorLists AS l WHERE options & 2",'id');
  if ($lists) {
    foreach ($lists AS $list) {
      if ($list['type'] == 'black' && $listStatus[$list['id']] == 'block') $checked = true;
      elseif ($list['type'] == 'white' && $listStatus[$list['id']] != 'unblock') $checked = true;
      else $checked = false;

      $censorLists .= "<label><input type=\"checkbox\" name=\"censor[$list[id]]\" " . ($checked ? " checked=\"checked\""  : '') . " /> $list[name]</label><br />";
    }
  }

  echo template('editRoomForm');
  break;*/

  case 'kickForm':
  case 'unkickForm':
  case 'copyright':
  case 'userSettingsForm':
  case 'online':
  case 'help':
  case 'privateRoomForm':
  case 'createRoomForm':
  case 'contextMenu':
  case 'login':
  case 'register':
  case 'editRoomForm':
  case 'insertDoc':
  echo template($template);
  break;

  default:
  trigger_error("Unknown Template: '$template'", E_USER_ERROR);
  break;
}
?>