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

if (!$_GET['roomid']) {
  $roomSelect = mysqlReadThrough(mysqlQuery("SELECT * FROM {$sqlPrefix}rooms WHERE " . ((($user['settings'] & 16) == false) ? "(owner = '$user[userId]' OR moderators REGEXP '({$user[userId]},)|{$user[userId]}$') AND " : '') . "(options & 16) = false AND (options & 4) = false AND (options & 8) = false"),'<option value="$id">$name</option>
');
  if ($roomSelect) {
    echo container('Manage Kicked Users','');
  }
  else {
    trigger_error('You are not a moderator of any rooms.',E_USER_ERROR);
  }
}
else {
echo '<script type="text/javascript">
$(document).ready(function() {
  $("form[data-formid=unkick]").submit(function(){
    data = $(this).serialize(); // Serialize the form data for AJAX.
    $.post("content/unkick.php?phase=2",data,function(html) {
      quickDialogue(html,\'\',\'unkickDialogue\');
    }); // Send the form data via AJAX.

    $("#manageKickDialogue").dialog(\'destroy\');

    return false; // Don\'t submit the form.
  });
});
</script>';

  $roomid = intval($_GET['roomid']);
  $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $roomid");

  if (hasPermission($room,$user,'moderate')) {
    $users = mysqlQuery("SELECT UNIX_TIMESTAMP(k.time) AS kickedOn, UNIX_TIMESTAMP(k.time) + k.length AS expiresOn, u1.userName AS userName, u1.userId AS userId, u2.userName AS kickername FROM {$sqlPrefix}kick AS k, user AS u1, user AS u2 WHERE k.room = $room[id] AND k.userId = u1.userId AND k.kickerid = u2.userId AND UNIX_TIMESTAMP(NOW()) <= (UNIX_TIMESTAMP(time) + length)");

    while ($kickedUser = mysqlArray($users)) {
      $kickedUser['kickedOn'] = vbdate('m/d/Y g:i:sa',$kickedUser['kickedOn']);
      $kickedUser['expiresOn'] = vbdate('m/d/Y g:i:sa',$kickedUser['expiresOn']);

      $userRow .= template('manageKickTableRow');
    }

    echo container('View Kicked Users',template('manageKickTable'));
  }
  else {
    trigger_error('You do not have permission to moderate this room.',E_USER_ERROR);
  }
}
?>