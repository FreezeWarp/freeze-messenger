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
  echo template('privateRoomForm');
}
elseif ($phase == '2') {
  $userName = ($_POST['userName'] ?: $_GET['userName']);
  $userId = ($_POST['userId'] ?: $_GET['userId']);

  if ($userName) {
    $safename = mysqlEscape($_POST['userName']); // Escape the userName for MySQL.
    $user2 = sqlArr("SELECT * FROM user WHERE userName = '$safename'"); // Get the user information.
  }
  elseif ($userId) {
    $userId = intval($_GET['userId']);
    $user2 = sqlArr("SELECT * FROM user WHERE userId = $userId");
  }
  else {
    trigger_error('You did not specify a user.',E_USER_ERROR);
  }

  if (!$user2) { // No user exists.
    trigger_error('That user could not be found.',E_USER_ERROR);
  }
  elseif ($user2['userId'] == $user['userId']) {
    trigger_error('Um... Why exactly do you want to talk to yourself?',E_USER_ERROR);
  }
  else {
    $group = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE (allowedUsers = '$user[userId],$user2[userId]' OR allowedUsers = '$user2[userId],$user[userId]') AND options & 16"); // Query a group that would match the criteria for a private room.
    if ($group) {
      echo "<script type=\"text/javascript\">window.open('/chat.php?room=$group[id]','room$group[id]');</script>";
    }
    else {
      $allowedGroups = '';
      $allowedUsers = "$user[userId],$user2[userId]";
      $moderators = '';
      $options = 48;
      $bbcode = 1;
      $name = mysqlEscape("Private IM ($user[userName] and $user2[userName])");

      mysqlQuery("INSERT INTO {$sqlPrefix}rooms (name,allowedGroups,allowedUsers,moderators,owner,options,bbcode) VALUES ('$name','$allowedGroups','$allowedUsers','$moderators',$user[userId],$options,$bbcode)");
      $insertId = mysql_insert_id();
      echo "<script type=\"text/javascript\">window.open('/chat.php?room=$insertId','_BLANK');</script>";
    }
  }
}
else {
  trigger_error('Unknown Action',E_USER_ERROR);
}
?>