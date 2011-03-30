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

require_once('../global.php');
require_once('../functions/container.php');

if (!$_GET['roomid']) {
  $roomSelect = mysqlReadThrough(mysqlQuery("SELECT * FROM {$sqlPrefix}rooms WHERE " . ((($user['settings'] & 16) == false) ? "(owner = '$user[userid]' OR moderators REGEXP '({$user[userid]},)|{$user[userid]}$') AND " : '') . "(options & 16) = false AND (options & 4) = false AND (options & 8) = false"),'<option value="$id">$name</option>
');
  if ($roomSelect) {
    echo container('Manage Kicked Users','<form action="/index.php" method="GET">
      <label for="roomid">Room: </label>
      <select name="roomid" id="roomid">
        ' . $roomSelect . '
      </select><br /><br />

    <input type="submit" value="Go" />
    <input type="hidden" name="action" value="manageKick" />
    </form>');
  }
  else {
    trigger_error('You are not a moderator of any rooms.',E_USER_ERROR);
  }
}
else {
  $roomid = intval($_GET['roomid']);
  $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $roomid");

  if (hasPermission($room,$user,'moderate')) {
    $users = mysqlQuery("SELECT UNIX_TIMESTAMP(k.time) AS kickedOn, UNIX_TIMESTAMP(k.time) + k.length AS expiresOn, u1.username AS username, u1.userid AS userid, u2.username AS kickername FROM {$sqlPrefix}kick AS k, user AS u1, user AS u2 WHERE k.room = $room[id] AND k.userid = u1.userid AND k.kickerid = u2.userid AND UNIX_TIMESTAMP(NOW()) <= (UNIX_TIMESTAMP(time) + length)");
    while ($kickedUser = mysqlArray($users)) {
      $userRow .= "<tr><td>$kickedUser[username]</td><td>$kickedUser[kickername]</td><td>" . vbdate('m/d/Y g:i:sa',$kickedUser['kickedOn']) . "</td><td>" . vbdate('m/d/Y g:i:sa',$kickedUser['expiresOn']) . "</td><td><form action=\"/index.php?action=unkick&phase=2\" method=\"post\"><input type=\"submit\" value=\"Unkick\" /><input type=\"hidden\" name=\"userid\" value=\"$kickedUser[userid]\" /><input type=\"hidden\" name=\"roomid\" value=\"$room[id]\" /></form></td></tr>";
    }

    echo container('<h3>View Kicked Users</h3>','<table class="page">
  <thead>
    <tr class="hrow"><td>User</td><td>Kicked By</td><td>Kicked On</td><td>Expires On</td><td>Actions</td></tr>
  </thead>
  <tbody>
' . $userRow . '
  </tbody>
</table>"');
  }
  else {
    trigger_error('You do not have permission to moderate this room.',E_USER_ERROR);
  }
}
?>