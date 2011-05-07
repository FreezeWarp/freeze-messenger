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

$noReqLogin = true;
$title = 'Message Archive';
$reqPhrases = true;
$reqHooks = true;

require_once('global.php'); // Used for everything.
require_once('functions/container.php'); // Used for /some/ formatting, though perhaps too sparcely right now.


exec(hook('archiveStart'));


require_once('templateStart.php');

if (!$_GET['roomid']) { // If no room ID is provided, then give the search form.
  $rooms = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE options & 8 = false ORDER BY options & 1 DESC, options & 16 ASC, id",'id');
  foreach ($rooms AS $room2) {
    if (hasPermission($room2,$user,'view')) {
      $roomSelect .= "<option value=\"$room2[id]\">$room2[name]</option>";
    }
  }

  exec(hook('archiveForm'));

  echo container($phrases['archiveChooseSettings'],$phrases['archiveMessage'] . '<br /><br />

<form action="/archive.php" method="get">
<table class="leftright">
  <tr>
    <td colspan="2">
      <select name="roomid" id="roomid">
        ' . $roomSelect . '
      </select>
    </td>
  </tr>
  <tr>
    <td>
      <label for="numresults">' . $phrases['archiveNumResultsLabel'] . '</label>
    <td>
    <td>
      <select name="numresults" id="numresults">
        <option value="10">10</option><option value="20">20</option>
        <option value="50" selected="selected">50</option>
        <option value="100">100</option>
        <option value="500">500</option>
      </select>
    </td>
  </tr>
  <tr>
    <td>
      <label for="oldfirst">' . $phrases['archiveReversePostOrderLabel'] . '</label>
    </td>
    <td>
      <input type="checkbox" name="oldfirst" id="oldfirst" value="true" />
    <td>
  </tr>
  <tr>
    <td><label for="userids">' . $phrases['archiveUserIdsLabel'] . '</label></td>
    <td><input type="text" name="userids" id="userids"  /></td>
  </tr>
  <tr>
    <td colspan="2">
      <button type="submit">' . $phrases['archiveSubmit'] . '</button>
    </td>
  </tr>
</table>

</form>');
}

else {
  $page = intval($_GET['pagen']) ?: 1; // The page of the results. This should be greater than or equal to one.
  $order = ($_GET['oldfirst'] ? 'ASC' : 'DESC'); // The order, either ASC (oldest to newest) or DESC (newest to oldest).
  $limit = intval($_GET['numresults']) ?: 50; // The limit for results on each page.
  $userIDs = mysqlEscape(urldecode($_GET['userids'])); // Searching only specific users.
  $roomid = intval($_GET['roomid'] ?: 1);
  $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $roomid");
  $messageStart = intval($_GET['message']);

  $offset = ($page - 1) * $limit; // This is calculated for the MySQL query based on page and limit.

  exec(hook('archiveResultsStart'));

  if (!$room) {
    trigger_error($phrase['chatRoomDoesNotExist'],E_USER_ERROR);
  }

  elseif (!hasPermission($room,$user,'view')) { // Gotta make sure the user can view that room.
    echo container($phrase['chatAccessDenied'],E_USER_ERROR);
  }

  else {
    if ((($user['settings'] & 16) || in_array($user['userid'],explode(',',$room['moderators'])) || $user['userid'] == $room['owner']) && (($room['options'] & 32) == false)) $canModerate = true; // The user /can/ moderate if they are a mod of the room, the room's owner, or an admin. If the room is disabled from moderation ($room['options'] & 32), then you still can't edit it.

    $total = sqlArr("SELECT COUNT(id) AS total FROM {$sqlPrefix}messages WHERE user IN (SELECT userid FROM user) AND room = $roomid" . ($user['settings'] & 16 == false ? "AND deleted != true" : '') . ($userIDs ? " AND user IN ($userIDs)" : '')); // Get the total number of messages that exist for the query coniditons.
    $totalPages = ceil($total['total'] / $limit); // Determine how many pages this should be divided into.
    for ($a = 0; $a <= $totalPages; $a++) { // Create the jump list for the pages.
      $b = ($a+1);
      $jumpList .= "<option value=\"$b\"" . ($b == $page ? ' selected="selected"':'') . ">$b</option>
";
    }

    if ($messageStart) {
      if ($order == "DESC") $whereHook = " AND m.id <= $messageStart";
      else $whereHook = " AND m.id >= $messageStart";

      $limitHook = "LIMIT $limit";
    }
    else {
      $limitHook = "LIMIT $limit OFFSET $offset";
    }


    exec(hook('archiveResultsPreprocess'));

    $messages = sqlArr("SELECT m.id, UNIX_TIMESTAMP(m.time) AS time, m.rawText, m.htmlText, m.deleted, m.salt, m.iv, u.userid, u.username, vu.settings, vu.defaultColour, vu.defaultFontface, vu.defaultHighlight, vu.defaultFormatting, u.displaygroupid FROM {$sqlPrefix}messages AS m, {$sqlPrefix}users AS vu, user AS u WHERE room = $roomid " . ($user['settings'] & 16 == false ? "AND deleted != true" : '') . " AND m.user = u.userid AND u.userid = vu.userid " . ($userIDs ? " AND user IN ($userIDs)" : '') . " $whereHook ORDER BY m.time $order $limitHook",'id'); // get the messages that should display.

    exec(hook('archiveResultsProcess'));

    if ($messages) {
      foreach ($messages AS $id => $message) {
        exec(hook('archiveResultsProcessEachStart'));

        $message = vrim_decrypt($message);
        $style = messageStyle($message);

        $opacitya = ($user['settings'] & 1 ? '.5' : '1.0'); // Grey-out if deleted.
        $opacitya2 = ($user['settings'] & 1 ? '1.0' : '.5'); // Grey-out if deleted.
        $opacityb = ($message['deleted'] ? '.5' : '1.0'); // Grey-out if deleted.
        $opacityb2 = ($message['deleted'] ? '1.0' : '.5'); // Grey-out if deleted.

        switch ($_GET['format']) {
          case 'bbcode':
          $output .= '[url=http://www.victoryroad.net/member.php?u=' . $message['userid'] . '][div=color:rgb(' . displayGroupToColour($message['displaygroupid']) . ');font-weight:bold;display:inline;]' . $message['username'] . '[/div][/url]|' . vbdate('m/d/y g:i:sa',$message['time']) . '|' . "[div=display:inline;color:rgb($message[defaultColour]);font-family:$message[defaultFontface];background-color:rgb($message[defaultHighlight]);]$message[htmlText][/div]\n";
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
  <input type=\"hidden\" name=\"roomid\" value=\"$_GET[roomid]\" />
  <input type=\"hidden\" name=\"oldfirst\" value=\"$_GET[oldfirst]\" />
  <input type=\"hidden\" name=\"userids\" value=\"$_GET[userids]\" />
  <input type=\"hidden\" name=\"search\" value=\"$_GET[search]\" />
  <label for=\"pagen\">$phrases[archivePageSelect]</label>
  <select name=\"pagen\" id=\"pagen\">
    $jumpList
  </select><br />
  <button type=\"submit\">$phrases[archiveSubmit]</button>
</form><br /><br />
<form method=\"get\" action=\"/archive.php\" style=\"text-align: center\">
  <input type=\"hidden\" name=\"numresults\" value=\"$_GET[numresults]\" />
  <input type=\"hidden\" name=\"roomid\" value=\"$_GET[roomid]\" />
  <input type=\"hidden\" name=\"oldfirst\" value=\"$_GET[oldfirst]\" />
  <input type=\"hidden\" name=\"userids\" value=\"$_GET[userids]\" />
  <input type=\"hidden\" name=\"search\" value=\"$_GET[search]\" />
  <input type=\"hidden\" name=\"pagen\" value=\"$_GET[pagen]\" />
  <label for=\"pagen\">$phrases[archiveViewAs]</label>
  <select name=\"format\" id=\"format\">
    <option value=\"normal\">$phrases[archiveFormatHTML]</option>
    <option value=\"bbcode\">$phrases[archiveFormatBBCode]</option>$phrase[archiveFormatSelectHook]
  </select><br />
  <button type=\"submit\">$phrases[archiveSubmit]</button>
</form>");

      echo $output2;
    }
    else {
      trigger_error($phrases['archiveNoMessages']);
    }
  }
}


exec(hook('archiveEnd'));

require_once('templateEnd.php');
?>