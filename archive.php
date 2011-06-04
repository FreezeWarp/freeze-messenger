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

if (!$_GET['roomId']) { // If no room ID is provided, then give the search form.
  $rooms = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE options & 8 = false ORDER BY options & 1 DESC, options & 16 ASC, id",'id');
  foreach ($rooms AS $room2) {
    if (hasPermission($room2,$user,'view')) {
      $roomSelect .= template('archiveChooseSettingsRoomOption');
    }
  }

  exec(hook('archiveForm'));

  echo container($phrases['archiveChooseSettings'],$phrases['archiveMessage'] . '<br /><br />' . template('archiveChooseSettings'));
}

else {
  $order = ($_GET['oldfirst'] ? 'ASC' : 'DESC'); // The order, either ASC (oldest to newest) or DESC (newest to oldest).
  $ordero = $order;

  $limit = intval($_GET['numresults']) ?: 50; // The limit for results on each page.
  if ($limit > 500) $limit = 2;

  $userIDs = mysqlEscape(urldecode($_GET['userIds'])); // Searching only specific users.

  $roomId = intval($_GET['roomId'] ?: 1);
  $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $roomId");

  $messageStart = intval($_GET['messageStart'] ?: $_GET['message']);
  $messageEnd = intval($_GET['messageEnd']);

  if ($messageEnd) {
    $messageBase = $messageEnd;
    $flopSymbol = true;
  }
  elseif ($messageStart) {
    $messageBase = $messageStart;
  }

  exec(hook('archiveResultsStart'));

  if (!$room) {
    trigger_error($phrase['chatRoomDoesNotExist'],E_USER_ERROR);
  }

  elseif (!hasPermission($room,$user,'view')) { // Gotta mEndake sure the user can view that room.
    echo container($phrase['chatAccessDenied'],E_USER_ERROR);
  }

  else {
    if ((($user['settings'] & 16) || in_array($user['userId'],explode(',',$room['moderators'])) || $user['userId'] == $room['owner']) && (($room['options'] & 32) == false)) {
      $canModerate = true; // The user /can/ moderate if they are a mod of the room, the room's owner, or an admin. If the room is disabled from moderation ($room['options'] & 32), then you still can't edit it.
    }

    if ($messageBase) {
      if ($order == "DESC") {
        if ($flopSymbol) {
          $sym = '>';
          $order = "ASC";
          $flopResults = true;
        }
        else {
          $sym = '<';
        }

        $whereHook = " AND m.id $sym= $messageBase";
      }
      else {
        if ($flopSymbol) {
          $sym = '<';
          $order = "DESC";
          $flopResults = true;
        }
        else {
          $sym = '>';
        }

        $whereHook = " AND m.id $sym= $messageBase";
      }
    }

    exec(hook('archiveResultsPreprocess'));

  $messages = sqlArr("SELECT m.id,
  UNIX_TIMESTAMP(m.time) AS time,
  m.htmlText,
  m.deleted,
  m.salt,
  m.iv,
  u.userId,
  u.userName,
  vu.settings,
  vu.defaultColour,
  vu.defaultFontface,
  vu.defaultHighlight,
  vu.defaultFormatting,
  u.displaygroupid
FROM vrc_messages AS m
  INNER JOIN vrc_users AS vu ON vu.userId = m.user
  INNER JOIN user AS u ON u.userId = m.user
WHERE m.room = $room[id]
  " . ($user['settings'] & 16 == false ? "AND deleted != true" : '') . "
  " . ($userIDs ? " AND user IN ($userIDs)" : '') . "
  $whereHook
ORDER BY m.id $order
LIMIT $limit",'id');

    if ($flopResults) {
      $messages = array_reverse($messages);
    }

    exec(hook('archiveResultsProcess'));

    if ($messages) {
      foreach ($messages AS $id => $message) {
        if ($ordero == "DESC") {
          if (!$messagePrev || $messagePrev < $message['id']) $messagePrev = $message['id'];
          if (!$messageNext || $messageNext > $message['id']) $messageNext = $message['id'];
        }
        else {
          if (!$messagePrev || $messagePrev > $message['id']) $messagePrev = $message['id'];
          if (!$messageNext || $messageNext < $message['id']) $messageNext = $message['id'];
        }

        exec(hook('archiveResultsProcessEachStart'));

        $message = vrim_decrypt($message);
        $style = messageStyle($message);

        $opacitya = ($user['settings'] & 1 ? '.5' : '1.0'); // Grey-out if deleted.
        $opacitya2 = ($user['settings'] & 1 ? '1.0' : '.5'); // Grey-out if deleted.
        $opacityb = ($message['deleted'] ? '.5' : '1.0'); // Grey-out if deleted.
        $opacityb2 = ($message['deleted'] ? '1.0' : '.5'); // Grey-out if deleted.

        switch ($_GET['format']) {
          case 'bbcode':
          $output .= '[url=http://www.victoryroad.net/member.php?u=' . $message['userId'] . '][div=color:rgb(' . displayGroupToColour($message['displaygroupid']) . ');font-weight:bold;display:inline;]' . $message['userName'] . '[/div][/url]|' . vbdate('m/d/y g:i:sa',$message['time']) . '|' . "[div=display:inline;color:rgb($message[defaultColour]);font-family:$message[defaultFontface];background-color:rgb($message[defaultHighlight]);]$message[htmlText][/div]\n";
          break;

          case '':
          case 'normal':
        $output .= "<tr style=\"opacity: $opacityb\" id=\"message$message[id]\">
  <td>
    $hooks[0]
    " . userFormat($message, $room) . "
  </td>
  <td>
    " . vbdate(false,$message['time']) . "
  </td>
  <td style=\"$style\">
    " . ($canModerate ? "
    <a href=\"javascript:void(0);\" onclick=\"$.ajax({url: '/ajax/modAction.php?action=deletepost&postid=$message[id]', type: 'GET', cache: false, success: function() { $('#message$message[id]').animate({'opacity':'$opacityb2'}); } });\">
      <img src=\"images/edit-delete.png\" style=\"opacity: $opacitya; height: 16px; width: 16px;\" />
    </a>": '') . "
    <span>$message[htmlText]</span>
  </td>
</tr>";
          break;
        }

        exec(hook('archiveResultsProcessEachEnd'));
      }

      switch ($_GET['format']) {
        case 'bbcode':
        $output2 = "<textarea style=\"width: 80%; height: 400px;\">[table]{$output}[/table]</textarea>";
        break;

        case '':
        case 'normal':
        $output2 = '<table class="page ui-widget">
  <thead>
    <tr class="hrow ui-widget-header">
      <td width="20%">' . $phrase['archiveHeaderUser'] . '</td>
      <td width="20%">' . $phrase['archiveHeaderTime'] . '</td>
      <td width="60%">' . $phrase['archiveHeaderMessage'] . '</td>
    </tr>
  </thead>
  <tbody class="ui-widget-content">
    ' . $output . '
  </tbody>
</table><br /><br />';
        break;
      }

      exec(hook('archiveResultsOutputStart'));

echo container("$phrases[archiveTitle]: $room[name]","
<form method=\"get\" action=\"/archive.php\" style=\"text-align: center\">
  <input type=\"hidden\" name=\"numresults\" value=\"$_GET[numresults]\" />
  <input type=\"hidden\" name=\"roomId\" value=\"$_GET[roomId]\" />
  <input type=\"hidden\" name=\"oldfirst\" value=\"$_GET[oldfirst]\" />
  <input type=\"hidden\" name=\"userIds\" value=\"$_GET[userIds]\" />
  <label for=\"format\">$phrases[archiveViewAs]</label>
  <select name=\"format\" id=\"format\">
    <option value=\"normal\">$phrases[archiveFormatHTML]</option>
    <option value=\"bbcode\">$phrases[archiveFormatBBCode]</option>$phrase[archiveFormatSelectHook]
  </select><br /><button type=\"submit\" name=\"messageEnd\" value=\"$messagePrev\"><< Previous</button>  <button type=\"submit\" name=\"messageStart\" value=\"$messageNext\">Next >></button>
</form><br /><br />");

      echo $output2;
    }
    else {
      trigger_error($phrases['archiveNoMessages']);
    }
  }
}
?>
