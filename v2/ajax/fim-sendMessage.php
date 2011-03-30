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
require_once('../functions/parserFunctions.php');

$message = vrim_urldecode($_POST['message']); // Get the message from POST.
$room = intval($_POST['room']); // Get the room from POST.

$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");




$words = sqlArr("SELECT w.word, w.severity, w.param
FROM {$sqlPrefix}censorLists AS l, {$sqlPrefix}censorWords AS w
WHERE w.listid = l.id AND (w.severity = 'warn' OR w.severity = 'confirm' OR w.severity = 'block')",'word');

if (!$words) {}
else {
  foreach ($words AS $word) {
    if ($_POST['confirmed'] && $word['severity'] == 'confirm') continue;
    $searchText[] = addcslashes(strtolower($word['word']),'^&|!$?()[]<>\\/.+*');
  }

  if ($searchText) {
    preg_match('/(' . implode('|',$searchText) . ')/i',$message,$matches);
    if ($matches[1]) {
      $blockedWord = strtolower($matches[1]);
      $blockedWordText = $words[$blockedWord]['word'];
      $blockedWordReason = $words[$blockedWord]['param'];
      $blockedWordSeverity = $words[$blockedWord]['severity'];
    }
  }
}


if (strlen($message) == 0 || strlen($message) > 1000) { // Too short/long.
  echo 'This message is too long or short. Messages must be more than 0 characters and less than or equal to 1000.';
}
elseif (preg_match('/^(\ |\n|\r)*$/',$message)) { // All spaces.
  echo 'In some countries, you could be arrested for posting only spaces. Now aren\'t you glad we stopped you?';
}
elseif (!hasPermission($room,$user)) { // Not allowed to post.
  echo 'Whoa buddy. You aren\'t allowed to post here.';
}
elseif ($blockedWordSeverity == 'block') {
  echo 'The word ' . $blockedWordText . ' is not allowed: ' . $blockedWordReason;
}
elseif ($blockedWordSeverity == 'confirm') {
  echo 'Warning: The word ' . $blockedWordtext . ' may not be allowed: ' . $blockedWordReason . '. Would you still like to send it?:<br /><br /><button type="button" onclick="(this).parent().dialog(\'close\');">No</button><button type="button" onclick="sendMessage(\'' . addslashes($message) . '\',1); (this).parent().dialog(\'close\');">Yes</button>';
}
elseif (strpos($message, '/topic') === 0) {
  $title = preg_replace('/^\/topic (.+?)$/i','$1',$message);

  $title = mysqlEscape(censor($title)); // Parses the sources for MySQL and UTF8. We will also censor, but no BBcode.

  sendMessage('/me changed the topic to ' . $title,$user,$room,'topic');
  mysqlQuery("UPDATE {$sqlPrefix}rooms SET title = '$title' WHERE id = $room[id]");
}
else {
  if (strpos($message, '/me') === 0) { $flag = 'me'; }

  sendMessage($message,$user,$room,$flag);

  if ($blockedWordSeverity == 'warn') {
    echo $blockedWordReason;
  }
  else {
    echo 'success';
  }
}

mysqlClose();
?>