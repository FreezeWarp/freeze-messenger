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
      echo '<script type="text/javascript">
$(document).ready(function(){
  $("#editRoomForm").submit(function(){
    data = $("#editRoomForm").serialize(); // Serialize the form data for AJAX.
    $.post("content/editRoom.php?phase=2&roomid=' . $room['id'] . '",data,function(html) {
      quickDialogue(html,\'\',\'editRoomResultDialogue\');
    }); // Send the form data via AJAX.

    $("#editRoomDialogue").dialog(\'close\');

    return false; // Don\'t submit the form.
  });
});
</script>' . "
<form action=\"#\" method=\"post\" id=\"editRoomForm\">
  <label for=\"name\">$phrases[editRoomNameLabel]</label>: <input type=\"text\" name=\"name\" id=\"name\" value=\"$room[name]\" /><br />
  <small><span style=\"margin-left: 10px;\">$phrases[editRoomNameBlurb]</span></small><br /><br />

  <label for=\"allowedUsers\">$phrases[editRoomAllowedUsersLabel]</label>: <input type=\"text\" name=\"allowedUsers\" id=\"allowedUsers\" value=\"$room[allowedUsers]\" /><br />
  <small><span style=\"margin-left: 10px;\">$phrases[editRoomAllowedUsersBlurb]</span></small><br /><br />

  <label for=\"allowedGroups\">$phrases[editRoomAllowedGroupsLabel]</label>: <input type=\"text\" name=\"allowedGroups\" id=\"allowedGroups\" value=\"$room[allowedGroups]\" /><br />
  <small><span style=\"margin-left: 10px;\">$phrases[editRoomAllowedGroupsBlurb]</span></small><br /><br />

  <label for=\"moderators\">$phrases[editRoomModeratorsLabel]</label>: <input type=\"text\" name=\"moderators\" id=\"moderators\" value=\"$room[moderators]\" /><br />
  <small><span style=\"margin-left: 10px;\">$phrases[editRoomModeratorsBlurb]</span></small><br /><br />

  <label for=\"mature\">$phrases[editRoomMatureLabel]</label>: <input type=\"checkbox\" name=\"mature\" id=\"mature\" /><br />
  <small><span style=\"margin-left: 10px;\">$phrases[editRoomMatureBlurb]</strong></small><br /><br />

  <label for=\"bbcode\">$phrases[editRoomBBCode]</label>: <select name=\"bbcode\">
    <option value=\"1\" selected=\"selected\">$phrases[editRoomBBCodeAll]</option>
    <option value=\"5\">$phrases[editRoomBBCodeMulti]</option>
    <option value=\"9\">$phrases[editRoomBBCodeImg]</option>
    <option value=\"13\">$phrases[editRoomBBCodeLink]</option>
    <option value=\"16\">$phrases[editRoomBBCodeBasic]</option>
    <option>$phrases[editRoomBBCodeNothing]</option>
  </select><br />

  <small style=\"margin-left: 10px;\">$phrases[editRoomBBCodeBlurb]</small><br /><br />

  <label>$phrases[editRoomCensorLabel]</label>:<br /><div style=\"margin-left: 10px;\">{$censorLists}</div><br />

  <button type=\"submit\">$phrases[editRoomSubmit]</button><button type=\"reset\">$phrases[editRoomReset]</button>
</form>";
    }
  }
  elseif ($phase == '2') {
    $name = substr(mysqlEscape($_POST['name']),0,20); // Limits to 20 characters.

    if (!$name) {
      trigger_error($phrases['editRoomNoName'],E_USER_ERROR); // ...It has to have a name /still/.
    }
    elseif ($user['userid'] != $room['owner'] && !($user['settings'] & 16)) {
      trigger_error($phrases['editRoomNotOwner'],E_USER_ERROR); // Again, check to make sure the user is the group's owner or an admin.
    }
    elseif ($room['settings'] & 4) {
      trigger_error($phrases['editRoomDeleted'],E_USER_ERROR); // Make sure the room hasn't been deleted.
    }
    else {
      $data = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE name = '$name'");

      if ($data && $data['id'] != $room['id']) {
        trigger_error($phrases['editRoomNameTaken'],E_USER_ERROR);
      }
      else {
        $listsActive = sqlArr("SELECT * FROM {$sqlPrefix}censorBlackWhiteLists WHERE roomid = $room[id]",'id');
        if ($listsActive) {
          foreach ($listsActive AS $active) {
            $listStatus[$active['listid']] = $active['status'];
          }
        }

       $censorLists = $_POST['censor'];
       foreach($censorLists AS $id => $list) {
         $listsNew[$id] = $list;
       }

       $lists = sqlArr("SELECT * FROM {$sqlPrefix}censorLists AS l WHERE options & 2",'id');
       foreach ($lists AS $list) {
          if ($list['type'] == 'black' && $listStatus[$list['id']] == 'block') $checked = true;
          elseif ($list['type'] == 'white' && $listStatus[$list['id']] != 'unblock') $checked = true;
          else $checked = false;

          if ($checked == true && !$listsNew[$list['id']]) {
            mysqlQuery("INSERT INTO ${sqlPrefix}censorBlackWhiteLists (roomid, listid, status) VALUES ($room[id], $id, 'unblock') ON DUPLICATE KEY UPDATE status = 'unblock'");
          }
          elseif ($checked == false && $listsNew[$list['id']]) {
            mysqlQuery("INSERT INTO ${sqlPrefix}censorBlackWhiteLists (roomid, listid, status) VALUES ($room[id], $id, 'block') ON DUPLICATE KEY UPDATE status = 'block'");
          }
        }

        $allowedGroups = mysqlEscape($_POST['allowedGroups']);
        $allowedUsers = mysqlEscape($_POST['allowedUsers']);
        $moderators = mysqlEscape($_POST['moderators']);
        $options = ($room['options'] & 1) + ($_POST['mature'] ? 2 : 0) + ($room['options'] & 4) + ($room['options'] & 8) + ($_POST['disableModeration'] ? 32 + 0 : 0);
        $bbcode = intval($_POST['bbcode']);
        mysqlQuery("UPDATE {$sqlPrefix}rooms SET name = '$name', allowedGroups = '$allowedGroups', allowedUsers = '$allowedUsers', moderators = '$moderators', options = '$options', bbcode = '$bbcode' WHERE id = $room[id]");
        echo 'Your group was successfully edited.<br /><br />' . button('Go To It','index.php?room=' . $room['id']);
      }
    }
  }
  else {
    trigger_error($phrases['editRoomUnknownAction'],E_USER_ERROR);
  }
}
?>