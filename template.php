<?php

$noReqLogin = true;
$reqPhrases = true;
$reqHooks = true;

require_once('global.php');

$template = $_GET['template'];

switch ($template) {
  case 'kickForm': /* The below calculations should be replaced with the API once possible. */
  $userid = intval($_POST['userid'] ?: $_GET['userid']);
  $roomid = intval($_POST['roomid'] ?: $_GET['roomid']);

  $roomSelect = mysqlReadThrough(mysqlQuery("SELECT * FROM {$sqlPrefix}rooms WHERE " . ((($user['settings'] & 16) == false) ? "(owner = '$user[userid]' OR moderators REGEXP '({$user[userid]},)|{$user[userid]}$') AND " : '') . "(options & 16) = false AND (options & 4) = false AND (options & 8) = false"),'<option value="$id"{{' . $roomid . ' == $id}}{{ selected="selected"}{}}>$name</option>
');
  $userSelect = mysqlReadThrough(mysqlQuery("SELECT u2.userid, u2.username FROM {$sqlPrefix}users AS u, user AS u2 WHERE u2.userid = u.userid ORDER BY username"),'<option value="$userid"{{' . $userid . ' == $userid}}{{ selected="selected"}{}}>$username</option>
');
  echo template('kickForm');
  break;

  case 'editRoomForm': /* The below calculations should be replaced with the API once possible. */


//  if (!$room) trigger_error('This is not a valid room (roomid = ' . $_GET['roomid'] . ').',E_USER_ERROR);
//  elseif ($user['userid'] != $room['owner'] && !($user['settings'] & 16)) trigger_error('You must be the owner to edit this room',E_USER_ERROR);
//  elseif ($room['settings'] & 4) trigger_error('This room is deleted, and as such may not be edited.',E_USER_ERROR);

  $room = intval($_GET['roomId']); // Get the room we're on. If there is a $_GET variable, use it, otherwise the user's "default", or finally just main.
  $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = '$room'"); // Data on the room.

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

  echo template('editRoomForm');
  break;

  case 'unkickForm':
  case 'copyright':
  case 'userSettingsForm':
  case 'online':
  case 'createRoomForm':
  case 'help':
  echo template($template);
  break;

  default:
  trigger_error("Unknown Template: '$template'", E_USER_ERROR);
  break;
}
?>