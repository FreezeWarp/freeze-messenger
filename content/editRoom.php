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

require_once('../global.php'); // Used for everything.

if (!$_GET['roomid']) {
  trigger_error('A roomid was not specified',E_USER_ERROR);
}
else {
  $room = intval($_GET['roomid']); // Get the room we're on. If there is a $_GET variable, use it, otherwise the user's "default", or finally just main.
  $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = '$room'"); // Data on the room.

  $phase = $_GET['phase'];
  if (!$phase) $phase = '1'; // Default to phase 1.

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
</script>

<form action="#" method="post" id="editRoomForm">
  <label for="name">Name</label>: <input type="text" name="name" id="name" value="' . $room['name'] . '" /><br />
  <small><span style="margin-left: 10px;">Your group\'s name. Note: This should not container anything vulgar or it will be deleted.</span></small><br /><br />

  <label for="allowedUsers">Allowed Users</label>: <input type="text" name="allowedUsers" id="allowedUsers" value="' . $room['allowedUsers'] . '" /><br />
  <small><span style="margin-left: 10px;">A comma-seperated list of User IDs who can view this chat. Moderators can see your conversation regardless of this setting. Use "*" for everybody.</span></small><br /><br />

  <label for="allowedGroups">Allowed Groups</label>: <input type="text" name="allowedGroups" id="allowedGroups" value="' . $room['allowedGroups'] . '" /><br />
  <small><span style="margin-left: 10px;">A comma-seperated list of Group IDs who can view this chat. Moderators can see your conversation regardless of this setting. Use "*" for everybody.</span></small><br /><br />

  <label for="moderators">Moderators</label>: <input type="text" name="moderators" id="moderators" value="' . $room['moderators'] . '" /><br />
  <small><span style="margin-left: 10px;">A comma-seperated list of moderator <strong>IDs</strong> who can delete posts from your group.</span></small><br /><br />

  <label for="mature">Mature</label>: <input type="checkbox" name="mature" id="mature"' . ($room['options'] & 2 ? ' checked="checked"' : '') . ' /><br />
  <small><span style="margin-left: 10px;">Mature rooms allow certain content that is otherwise not allowed in that users are required to enable access to these rooms first. In addition, the censor is disabled for all such rooms. <strong>Hatespeech, illegal content, and similar is disallowed regardless.</strong></small><br /><br />

  <label for="disableModeration">Disable Moderation</label>: <input type="checkbox" name="disableModeration" id="disableModeration"' . ($room['options'] & 32 ? ' checked="checked"' : '') . ' /><br />
  <small><span style="margin-left: 10px;">Disable all moderation from this room.</strong></small><br /><br />

  <label for="bbcode">BB Code Settings</label>: <select name="bbcode"><option value="1" selected="selected">Allow All Content</option><option value="5">Disallow Multimedia (Youtube, etc.)</option><option value="9">Disallow Images and Multimedia</option><option value="13">Only Allow Basic Formatting and Links</option><option value="16">Only Allow Basic Formatting</option><option>Allow No Formatting</option></select><br />
  <small style="margin-left: 10px;">To prevent certain kinds of spam, different levels of BB code can be disallowed. Generally, this doesn\'t really come at much benefit anybody, save for the nitpicky (like us).</small><br /><br />

  <button type="submit">Modify Group</button><button type="reset">Reset</button></form>';
    }
  }
  elseif ($phase == '2') {
    $name = substr(mysqlEscape($_POST['name']),0,20); // Limits to 20 characters.

    if (!$name) trigger_error('Please fill in a name for your group.<br /><br /><button type="button" onclick="window.history.back();">Go Back</buton>',E_USER_ERROR); // ...It has to have a name /still/.
    elseif ($user['userid'] != $room['owner'] && !($user['settings'] & 16)) trigger_error('You must be the owner to edit a room',E_USER_ERROR); // Again, check to make sure the user is the group's owner or an admin.
    elseif ($room['settings'] & 4) trigger_error('This room is deleted, and as such may not be edited.',E_USER_ERROR); // Make sure the room hasn't been deleted.
    else {
      $data = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE name = '$name'");

      if ($data && $data['id'] != $room['id']) echo 'The name for your group is already taken.'; // Here we query the database for the same name. If anything is found, data is true, but since our own room could be the same name, we have to make sure our result isn't the same as the room we're dealing with.
      else {
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
    trigger_error('Unknown Action',E_USER_ERROR);
  }
}
?>