<?php
if (!$_GET['roomid']) { // If no room ID is provided, then give the search form.
  $rooms = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE options & 8 = false ORDER BY options & 1 DESC, options & 16 ASC, id",'id');
  foreach ($rooms AS $room2) {
    if (hasPermission($room2,$user,'view')) {
      $roomSelect .= "<option value=\"$room2[id]\">$room2[name]</option>";
    }
  }

  echo container('<h3>The Archives: Select a Room</h3>','Here you can find and search through every post made on VRIM. Simply enter a room, time frame, and the number of results to show and we can get started:<br /><br />

<form action="/index.php" method="get">
  <label for="roomid">Room:</label>
  <select name="roomid" id="roomid">
  ' . $roomSelect . '
  </select><br /><br />

  <label for="numresults">Number of Results Per Page:</label>
  <select name="numresults" id="numresults">
    <option value="10">10</option><option value="20">20</option>
    <option value="50" selected="selected">50</option>
    <option value="100">100</option>
    <option value="500">500</option>
  </select><br /><br />
  
  <label for="oldfirst">Oldest First</label> <input type="checkbox" name="oldfirst" id="oldfirst" value="true" /><br /><br />

  <label for="userids">User IDs (Optional)</label> <input type="text" name="userids" id="userids"  /><br /><br />

<!--  <label for="search">Search Phrase (Optional)</label> <input type="text" name="search" id="search"  /><br /><br />-->

  <input type="submit" value="View Archive" /><input type="hidden" name="action" value="archive" /></form>');
}

else {
  $page = intval($_GET['pagen']) ?: 1; // The page of the results. This should be greater than or equal to one.
  $order = ($_GET['oldfirst'] ? 'ASC' : 'DESC'); // The order, either ASC (oldest to newest) or DESC (newest to oldest).
  $limit = intval($_GET['numresults']) ?: 50; // The limit for results on each page.
  $userIDs = mysqlEscape(urldecode($_GET['userids'])); // Searching only specific users.
  $roomid = intval($_GET['roomid'] ?: 1);
  $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $roomid");

  $offset = ($page - 1) * $limit; // This is calculated for the MySQL query based on page and limit.

  if (!$room) {
    echo container('Error','That room doesn\'t exist');
  }

  elseif (!hasPermission($room,$user,'view')) { // Gotta make sure the user can view that room.
    echo container('Archive','You are not allowed to view this room.');
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

    $messages = sqlArr("SELECT m.id, UNIX_TIMESTAMP(m.time) AS time, m.rawText, m.htmlText, m.deleted, m.salt, m.iv, u.userid, u.username, vu.settings, vu.defaultColour, vu.defaultFontface, vu.defaultHighlight, vu.defaultFormatting, u.displaygroupid FROM {$sqlPrefix}messages AS m, {$sqlPrefix}users AS vu, user AS u WHERE room = $roomid " . ($user['settings'] & 16 == false ? "AND deleted != true" : '') . " AND m.user = u.userid AND u.userid = vu.userid " . ($userIDs ? " AND user IN ($userIDs)" : '') . " ORDER BY m.time $order LIMIT $limit OFFSET $offset",'id'); // get the messages that should display.

    if ($messages) {
      foreach ($messages AS $id => $message) {
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

          default:
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
      }

      switch ($_GET['format']) {
        case 'bbcode':
        $output2 = "<textarea style=\"width: 80%; height: 400px;\">[table]{$output}[/table]</textarea>";
        break;

        default:
        $output2 = '<table border="1" class="page"><thead><tr class="hrow"><td>User</td><td>Time</td><td>Message</td></tr></thead><tbody>' . $output . '</tbody></table><br /><br />';
        break;
      }

echo container("<h3>The Archives: $room[name]</h3>","$output2
<form method=\"get\" action=\"/index.php\">
  <input type=\"hidden\" name=\"action\" value=\"archive\" />
  <input type=\"hidden\" name=\"numresults\" value=\"$_GET[numresults]\" />
  <input type=\"hidden\" name=\"roomid\" value=\"$_GET[roomid]\" />
  <input type=\"hidden\" name=\"oldfirst\" value=\"$_GET[oldfirst]\" />
  <input type=\"hidden\" name=\"userids\" value=\"$_GET[userids]\" />
  <input type=\"hidden\" name=\"search\" value=\"$_GET[search]\" />
  <label for=\"pagen\">Page: </label>
  <select name=\"pagen\" id=\"pagen\">
    $jumpList
  </select>
  <input type=\"submit\" value=\"Go\" />
</form><br />
<form method=\"get\" action=\"/index.php\">
  <input type=\"hidden\" name=\"action\" value=\"archive\" />
  <input type=\"hidden\" name=\"numresults\" value=\"$_GET[numresults]\" />
  <input type=\"hidden\" name=\"roomid\" value=\"$_GET[roomid]\" />
  <input type=\"hidden\" name=\"oldfirst\" value=\"$_GET[oldfirst]\" />
  <input type=\"hidden\" name=\"userids\" value=\"$_GET[userids]\" />
  <input type=\"hidden\" name=\"search\" value=\"$_GET[search]\" />
  <input type=\"hidden\" name=\"pagen\" value=\"$_GET[pagen]\" />
  <label for=\"pagen\">View As: </label>
  <select name=\"format\" id=\"format\">
    <option value=\"normal\">Normal</option>
    <option value=\"bbcode\">Forum BBCode</option>
  </select>
  <input type=\"submit\" value=\"Go\" />
</form><br />");
    }
    else {
      echo container('Error','This room has no messages.');
    }
  }
}
?>