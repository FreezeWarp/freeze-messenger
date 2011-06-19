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

$apiRequest = true;

require_once('../global.php');
require_once('../functions/parserFunctions.php');

$message = fim_urldecode($_POST['message']);

$roomId = (int) $_POST['roomId'];
$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE roomId = $roomId");
$ip = mysqlEscape($_SERVER['REMOTE_ADDR']); // Get the IP address of the user.


($hook = hook('sendMessage_start') ? eval($hook) : '');


$words = sqlArr("SELECT w.word, w.severity, w.param
FROM {$sqlPrefix}censorLists AS l, {$sqlPrefix}censorWords AS w
WHERE w.listId = l.listId AND (w.severity = 'warn' OR w.severity = 'confirm' OR w.severity = 'block')",'word');

if ($words) {
  ($hook = hook('sendMessage_censor_start') ? eval($hook) : '');

  foreach ($words AS $word) {
    if ($_POST['ignoreBlock'] && $word['severity'] == 'confirm') continue;

    $searchText[] = addcslashes(strtolower($word['word']),'^&|!$?()[]<>\\/.+*');

    ($hook = hook('sendMessage_censor_eachWord') ? eval($hook) : '');
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

  ($hook = hook('sendMessage_censor_end') ? eval($hook) : '');
}


($hook = hook('sendMessage_preGen') ? eval($hook) : '');

if ($continue) {
  if (!$room) { // Bad room.
    $failCode = 'badroom';
    $failMessage = 'That room could not be found.';
  }
  elseif (strlen($message) == 0 || strlen($message) > 1000) { // Too short/long.
    $failCode = 'badmessage';
    $failMessage = 'The message you entered is either too long or too short.';
  }
  elseif (preg_match('/^(\ |\n|\r)*$/',$message)) { // All spaces.
    $failCode = 'spacemessage';
    $failMessage = 'In some countries, you could be arrested for posting only spaces. Now aren\'t you glad we stopped you?';
  }
  elseif (!fim_hasPermission($room,$user)) { // Not allowed to post.
    $failCode = 'noperm';
    $failMessage = 'You are not allowed to post in this room.';
  }
  elseif ($blockedWordSeverity == 'block') {
    $failCode = 'blockcensor';
    $failMessage = 'The word ' . $blockedWordText . ' is not allowed: ' . $blockedWordReason;

    $blockWordApi['severity'] = 'block';
    $blockWordApi['word'] = $blockedWordText;
    $blockWordApi['reason'] = $blockedWordReason;
  }
  elseif ($blockedWordSeverity == 'confirm') {
    $failCode = 'confirmcensor';
    $failMessage = 'Warning: The word ' . $blockedWordtext . ' may not be allowed: ' . $blockedWordReason;

    $blockWordApi['severity'] = 'confirm';
    $blockWordApi['word'] = $blockedWordText;
    $blockWordApi['reason'] = $blockedWordReason;
  }
  elseif (strpos($message, '/topic') === 0) {
    $title = preg_replace('/^\/topic (.+?)$/i','$1',$message);

    $title = mysqlEscape(fimParse_censorParse($title)); // Parses the sources for MySQL and UTF8. We will also censor, but no BBcode.

    fim_sendMessage('/me changed the topic to ' . $title,$user,$room,'topic');
    mysqlQuery("UPDATE {$sqlPrefix}rooms SET topic = '$title' WHERE roomId = $room[id]");
  }
  else {
    if (strpos($message, '/me') === 0) { $flag = 'me'; }

    fim_sendMessage($message,$user,$room,$flag);
  }
}


($hook = hook('sendMessage_postGen') ? eval($hook) : '');



$xmlData = array(
  'sendMessage' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'sentData' => array(
      'roomId' => (int) $_POST['roomId'],
      'message' => ($_POST['message']),
    ),
    'errorcode' => ($failCode),
    'errortext' => ($failMessage),
    'censor' => array(
      'word' => ($blockWordApi['word']),
      'severity' => ($blockWordApi['severity']),
      'reason' => ($blockWordApi['reason']),
    ),
  ),
);


($hook = hook('sendMessage_end') ? eval($hook) : '');


echo fim_outputXml($xmlData);

mysqlClose();
?>