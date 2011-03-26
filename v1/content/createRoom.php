<?php
$phase = $_GET['phase'];
if (!$phase) $phase = '1'; // Default to phase 1.

if (!$allowRoomCreation) {
 echo container('Error','Room Creation Has Been Disabled');
}
elseif ($user['settings'] & 2) {
  echo container('Error','You have been banned from room creation.');
}
elseif ($phase == '1') {
  echo container('<h3>Create a Room</h3>','<form action="/index.php?action=createRoom&phase=2" method="post">
  <label for="name">Name</label>: <input type="text" name="name" id="name" /><br />
  <small><span style="margin-left: 10px;">Your group\'s name. Note: This should not container anything vulgar or it will be deleted.</span></small><br /><br />

  <label for="allowedUsers">Allowed Users</label>: <input type="text" name="allowedUsers" id="allowedUsers" /><br />
  <small><span style="margin-left: 10px;">A comma-seperated list of User IDs who can view this chat. Moderators can see your conversation regardless of this setting. Use "*" for everybody.</span></small><br /><br />

  <label for="allowedGroups">Allowed Groups</label>: <input type="text" name="allowedGroups" id="allowedGroups" /><br />
  <small><span style="margin-left: 10px;">A comma-seperated list of Group IDs who can view this chat. Moderators can see your conversation regardless of this setting. Use "*" for everybody.</span></small><br /><br />

  <label for="moderators">Moderators</label>: <input type="text" name="moderators" id="moderators" /><br />
  <small><span style="margin-left: 10px;">A comma-seperated list of moderators who can delete posts from your group.</span></small><br /><br />

  <label for="mature">Mature</label>: <input type="checkbox" name="mature" id="mature" /><br />
  <small><span style="margin-left: 10px;">Mature rooms allow certain content that is otherwise not allowed in that users are required to enable access to these rooms first. In addition, the censor is disabled for all such rooms. <strong>Hatespeech, illegal content, and similar is disallowed regardless.</strong></small><br /><br />

  <label for="bbcode">BB Code Settings</label>: <select name="bbcode"><option value="1" selected="selected">Allow All Content</option><option value="5">Disallow Multimedia (Youtube, etc.)</option><option value="9">Disallow Images and Multimedia</option><option value="13">Only Allow Basic Formatting and Links</option><option value="16">Only Allow Basic Formatting</option><option>Allow No Formatting</option></select><br />
  <small style="margin-left: 10px;">To prevent certain kinds of spam, different levels of BB code can be disallowed. Generally, this doesn\'t really come at much benefit anybody, save for the nitpicky (like us).</small><br /><br />

  <input type="submit" value="Create Group" /><input type="reset" value="Reset" />
</form>');
}
elseif ($phase == '2') {
  $name = substr(mysqlEscape($_POST['name']),0,20); // Limits to 20 characters.
  if (!$name) echo container('Error','Please fill in a name for your group.<br /><br /><button type="button" onclick="window.history.back();">Go Back</buton>');
  else {
    if (sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE name = '$name'")) echo container('Error','The name for your group is already taken.<br /><br /><button type="button" onclick="window.history.back();">Go Back</buton>');
    else {
      $allowedGroups = mysqlEscape($_POST['allowedGroups']);
      $allowedUsers = mysqlEscape($_POST['allowedUsers']);
      $moderators = mysqlEscape($_POST['moderators']);
      $options = ($_POST['mature'] ? 2 : 0);
      $bbcode = intval($_POST['bbcode']);

      mysqlQuery("INSERT INTO {$sqlPrefix}rooms (name,allowedGroups,allowedUsers,moderators,owner,options,bbcode) VALUES ('$name','$allowedGroups','$allowedUsers','$moderators',$user[userid],$options,$bbcode)");
      $insertId = mysql_insert_id();
      if ($insertId) echo container('Create a Room','Your group was successfully created at:<br /><br /><form action="index.php?room=' . $insertId . '" method="post"><input type="text" style="width: 300px;" value="http://vrc.victoryroad.net/index.php?room=' . $insertId . '" name="url" /><input type="submit" value="Go There!" /></form>');
      else echo container('Creation Failed','Your group could not be created.');
    }
  }
}
else {
  trigger_error('Unknown Action',E_USER_ERROR);
}
?>