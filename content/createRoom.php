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
require_once('../functions/container.php'); // Used for /some/ formatting, though perhaps too sparcely right now.

$phase = $_GET['phase'];
if (!$phase) $phase = '1'; // Default to phase 1.

if (!$allowRoomCreation) {
  trigger_error($phrases['createRoomDisabled'],E_USER_ERROR);
}
elseif ($user['settings'] & 2) {
  trigger_error($phrases['createRoomBanned'],E_USER_ERROR);
}
elseif ($phase == '1') {
  echo '<script type="text/javascript">
$(document).ready(function(){
  $("#createRoomForm").submit(function(){
    data = $("#createRoomForm").serialize(); // Serialize the form data for AJAX.
    $.post("content/createRoom.php?phase=2",data,function(html) {
      quickDialogue(html,\'\',\'createRoomResultDialogue\');
    }); // Send the form data via AJAX.

    $("#createRoomDialogue").dialog(\'close\');

    return false; // Don\'t submit the form.
  });
});
</script>' . "
<form action=\"#\" method=\"post\" id=\"createRoomForm\">
  <label for=\"name\">$phrases[editRoomNameLabel]</label>: <input type=\"text\" name=\"name\" id=\"name\" /><br />
  <small><span style=\"margin-left: 10px;\">$phrases[editRoomNameBlurb]</span></small><br /><br />

  <label for=\"allowedUsers\">$phrases[editRoomAllowedUsersLabel]</label>: <input type=\"text\" name=\"allowedUsers\" id=\"allowedUsers\" /><br />
  <small><span style=\"margin-left: 10px;\">$phrases[editRoomAllowedUsersBlurb]</span></small><br /><br />

  <label for=\"allowedGroups\">$phrases[editRoomAllowedGroupsLabel]</label>: <input type=\"text\" name=\"allowedGroups\" id=\"allowedGroups\" /><br />
  <small><span style=\"margin-left: 10px;\">$phrases[editRoomAllowedGroupsBlurb]</span></small><br /><br />

  <label for=\"moderators\">$phrases[editRoomModeratorsLabel]</label>: <input type=\"text\" name=\"moderators\" id=\"moderators\" /><br />
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

  <button type=\"submit\">$phrases[createRoomSubmit]</buttin><button type=\"reset\">$phrases[createRoomReset]</button>
</form>";
}
elseif ($phase == '2') {
  $name = substr(mysqlEscape($_POST['name']),0,20); // Limits to 20 characters.

  if (!$name) {
    trigger_error($phrases['editRoomNoName'],E_USER_ERROR);
  }
  else {
    if (sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE name = '$name'")) {
      trigger_error($phrases['editRoomNameTaken'],E_USER_ERROR);
    }
    else {
      $allowedGroups = mysqlEscape($_POST['allowedGroups']);
      $allowedUsers = mysqlEscape($_POST['allowedUsers']);
      $moderators = mysqlEscape($_POST['moderators']);
      $options = ($_POST['mature'] ? 2 : 0);
      $bbcode = intval($_POST['bbcode']);

      mysqlQuery("INSERT INTO {$sqlPrefix}rooms (name,allowedGroups,allowedUsers,moderators,owner,options,bbcode) VALUES ('$name','$allowedGroups','$allowedUsers','$moderators',$user[userid],$options,$bbcode)");
      $insertId = mysql_insert_id();

      if ($insertId) {
        echo "$phrases[createRoomCreatedAt]<br /><br /><form action=\"{$installUrl}index.php?room={$insertId}\" method=\"post\"><input type=\"text\" style=\"width: 300px;\" value=\"http://vrim.victoryroad.net/index.php?room={$insertId}\" name=\"url\" /><input type=\"submit\" value=\"$phrases[editRoomCreatedGo]\" /></form>";
      }
      else {
        trigger_error($phrases['createRoomFail'],E_USER_ERROR);
      }
    }
  }
}
else {
  trigger_error($phrases['createRoomUnknownAction'],E_USER_ERROR);
}
?>