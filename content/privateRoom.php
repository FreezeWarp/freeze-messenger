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

require_once('../global.php');
require_once('../functions/container.php');

$phase = $_GET['phase'];
if (!$phase) $phase = '1'; // Default to phase 1.

if ($phase == '1') {
  echo container('Summary','Private rooms are rooms that can only be accessed by you and one other user of your choosing. Similar to private messages, these rooms can not be moderated, allow all BBCode, and lack many other group-based permissions.');

  echo container('Create / Enter a Private Room','<form action="/index.php?action=privateRoom&phase=2" method="post">

  <label for="username">Username</label>: <input type="text" name="username" id="username" /><br />
  <small><span style="margin-left: 10px;">The other user you would like to talk to.</span></small><br /><br />

  <input type="submit" value="Go" />

</form>');
}
elseif ($phase == '2') {
  $username = ($_POST['username'] ?: $_GET['username']);
  $userid = ($_POST['userid'] ?: $_GET['userid']);

  if ($username) {
    $safename = mysqlEscape($_POST['username']); // Escape the username for MySQL.
    $user2 = sqlArr("SELECT * FROM user WHERE username = '$safename'"); // Get the user information.
  }
  elseif ($userid) {
    $userid = intval($_GET['userid']);
    $user2 = sqlArr("SELECT * FROM user WHERE userid = $userid");
  }
  else {
    trigger_error('You did not specify a user.',E_USER_ERROR);
  }

  if (!$user2) { // No user exists.
    trigger_error('That user could not be found.',E_USER_ERROR);
  }
  elseif ($user2['userid'] == $user['userid']) {
    trigger_error('Um... Why exactly do you want to talk to yourself?',E_USER_ERROR);
  }
  else {
    $group = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE (allowedUsers = '$user[userid],$user2[userid]' OR allowedUsers = '$user2[userid],$user[userid]') AND options & 16"); // Query a group that would match the criteria for a private room.
    if ($group) {
      echo "<script type=\"text/javascript\">window.open('/chat.php?room=$group[id]','room$group[id]');</script>";
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
      echo "<script type=\"text/javascript\">window.open('/chat.php?room=$insertId','_BLANK');</script>";
    }
  }
}
else {
  trigger_error('Unknown Action',E_USER_ERROR);
}
?>