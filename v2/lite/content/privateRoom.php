<?php
$phase = $_GET['phase'];
if (!$phase) $phase = '1'; // Default to phase 1.

if ($phase == '1') {
  echo container('Summary','Private rooms are rooms that can only be accessed by you and one other user of your choosing. Similar to private messages, these rooms can not be moderated, allow all BBCode, and lack many other group-based permissions.');

  echo container('Create / Enter a Private Room','<form action="./index.php?action=privateRoom&phase=2" method="post">

  <label for="username">Username</label>: <input type="text" name="username" id="username" /><br />
  <small><span style="margin-left: 10px;">The other user you would like to talk to.</span></small><br /><br />

  <input type="submit" value="Go" />

</form>');
}
elseif ($phase == '2') {
  if ($_POST['username']) {
    $safename = mysqlEscape($_POST['username']); // Escape the username for MySQL.
    $user2 = sqlArr("SELECT * FROM user WHERE username = '$safename'"); // Get the user information.
  }
  elseif ($_GET['userid']) {
    $userid = intval($_GET['userid']);
    $user2 = sqlArr("SELECT * FROM user WHERE userid = $userid");
  }
  else {
    echo container('Error','You did not specify a user');
  }

  if (!$user2) { // No user exists.
    echo container('Error','That user could not be found.');
  }
  elseif ($user2['userid'] == $user['userid']) {
    echo container('Error','Um... Why exactly do you want to talk to yourself?');
  }
  else {
    $group = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE (allowedUsers = '$user[userid],$user2[userid]' OR allowedUsers = '$user2[userid],$user[userid]') AND options & 16"); // Query a group that would match the criteria for a private room.
    if ($group) {
      echo "<script type=\"text/javascript\">window.location.href = './index.php?room=$group[id]';</script>";
    }
    else {
      $allowedGroups = '';
      $allowedUsers = "$user[userid],$user2[userid]";
      $moderators = '';
      $options = 48;
      $bbcode = 1;
      $name = mysqlEscape("Private IM ($user[username] and $user2[username])");

      mysqlQuery("INSERT INTO {$sqlPrefix}rooms (name,allowedGroups,allowedUsers,moderators,owner,options,bbcode) VALUES ('$name','$allowedGroups','$allowedUsers','$moderators',$user[userid],$options,$bbcode)");
      $insertId = mysql_insert_id();
      echo "<script type=\"text/javascript\">window.location.href = './index.php?room=$insertId';</script>";
    }
  }
}
else {
  trigger_error('Unknown Action',E_USER_ERROR);
}
?>