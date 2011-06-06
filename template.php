<?php

$noReqLogin = true;
$reqPhrases = true;
$reqHooks = true;

require_once('global.php');

$template = $_GET['template'];

switch ($template) {
  case 'editRoomForm': /* The below calculations should be replaced with the API once possible. */
//  if (!$room) trigger_error('This is not a valid room (roomId = ' . $_GET['roomId'] . ').',E_USER_ERROR);
//  elseif ($user['userId'] != $room['owner'] && !($user['settings'] & 16)) trigger_error('You must be the owner to edit this room',E_USER_ERROR);
//  elseif ($room['settings'] & 4) trigger_error('This room is deleted, and as such may not be edited.',E_USER_ERROR);

  $room = intval($_GET['roomId']); // Get the room we're on. If there is a $_GET variable, use it, otherwise the user's "default", or finally just main.
  if ($room) $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = '$room'"); // Data on the room.

  if ($room) $listsActive = sqlArr("SELECT * FROM {$sqlPrefix}censorBlackWhiteLists WHERE roomId = $room[id]",'id');
  if ($listsActive) {
    foreach ($listsActive AS $active) {
      $listStatus[$active['listid']] = $active['status'];
    }
  }

  if ($room) $lists = sqlArr("SELECT * FROM {$sqlPrefix}censorLists AS l WHERE options & 2",'id');
  if ($lists) {
    foreach ($lists AS $list) {
      if ($list['type'] == 'black' && $listStatus[$list['id']] == 'block') $checked = true;
      elseif ($list['type'] == 'white' && $listStatus[$list['id']] != 'unblock') $checked = true;
      else $checked = false;

      $censorLists .= "<label><input type=\"checkbox\" name=\"censor[$list[id]]\" " . ($checked ? " checked=\"checked\""  : '') . " /> $list[name]</label><br />";
    }
  }

  echo template('editRoomForm');
  break;

  case 'createRoomSuccess':
  $insertId = (int) $_GET['insertId'];

  echo template('createRoomSuccess');
  break;

  case 'editRoomSuccess':
  $roomId = (int) $_GET['roomId'];

  echo template('createRoomSuccess');
  break;

  case 'kickForm':
  case 'unkickForm':
  case 'copyright':
  case 'userSettingsForm':
  case 'online':
  case 'help':
  case 'privateRoomForm':
  case 'createRoomForm':
  echo template($template);
  break;

  default:
  trigger_error("Unknown Template: '$template'", E_USER_ERROR);
  break;
}
?>