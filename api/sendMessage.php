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

/**
 * Get Messages from the Server
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
 *
 * @param int roomId - The room ID.
 * @param string message - The message text, properly URLencoded.
*/

$apiRequest = true;

require_once('../global.php');
require_once('../functions/parserFunctions.php');

$message = fim_urldecode($_POST['message']);

$roomId = (int) $_POST['roomId'];
$room = dbRows("SELECT * FROM {$sqlPrefix}rooms WHERE roomId = $roomId");
$ip = dbEscape($_SERVER['REMOTE_ADDR']); // Get the IP address of the user.

$flag = $_POST['flag'];

($hook = hook('sendMessage_start') ? eval($hook) : '');


$words = dbRows("SELECT w.word, w.severity, w.param
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
    $errStr = 'badroom';
    $errDesc = 'That room could not be found.';
  }
  elseif (strlen($message) == 0 || strlen($message) > 1000) { // Too short/long.
    $errStr = 'badmessage';
    $errDesc = 'The message you entered is either too long or too short.';
  }
  elseif (preg_match('/^(\ |\n|\r)*$/',$message)) { // All spaces.
    $errStr = 'spacemessage';
    $errDesc = 'In some countries, you could be arrested for posting only spaces. Now aren\'t you glad we stopped you?';
  }
  elseif (!fim_hasPermission($room,$user,'post',true)) { // Not allowed to post.
    $errStr = 'noperm';
    $errDesc = 'You are not allowed to post in this room.';
  }
  elseif ($blockedWordSeverity == 'block') {
    $errStr = 'blockcensor';
    $errDesc = 'The word ' . $blockedWordText . ' is not allowed: ' . $blockedWordReason;

    $blockWordApi['severity'] = 'block';
    $blockWordApi['word'] = $blockedWordText;
    $blockWordApi['reason'] = $blockedWordReason;
  }
  elseif ($blockedWordSeverity == 'confirm') {
    $errStr = 'confirmcensor';
    $errDesc = 'Warning: The word ' . $blockedWordtext . ' may not be allowed: ' . $blockedWordReason;

    $blockWordApi['severity'] = 'confirm';
    $blockWordApi['word'] = $blockedWordText;
    $blockWordApi['reason'] = $blockedWordReason;
  }
  elseif (strpos($message, '/topic') === 0 && !$disableTopic) {
    $topic = preg_replace('/^\/topic (.+?)$/i','$1',$message);

    $topic = dbEscape(fimParse_censorParse($topic)); // Parses the sources for MySQL and UTF8. We will also censor, but no BBcode.

    fim_sendMessage('/me changed the topic to ' . $topic,$user,$room,'topic');
    dbQuery("UPDATE {$sqlPrefix}rooms SET roomTopic = '$topic' WHERE roomId = $room[roomId]");
  }/*
  elseif (strpos($message, '/kick') === 0) {
    $kickData = preg_replace('/^\/kick (.+?)(| ([0-9]+?))$/i','$1,$2',$message);
    $kickData = explode(',',$kickData);

    $userData =

    $ch = curl_init('./');
    $fp = fopen("moderate.php", "w");

    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($agent, CURLOPT_POST, true);
    curl_setopt($agent, CURLOPT_POSTFIELDS, 'action=kickUser&userId=&roomId=' . $roomId . '&fim3_userId=&fim3_sessionHash=');

    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
  }*/
  else {
    if (strpos($message, '/me') === 0) {
      $flag = 'me';
    }

    fim_sendMessage($message,$user,$room,$flag);
  }
}


($hook = hook('sendMessage_postGen') ? eval($hook) : '');



/* Data Define */
$xmlData = array(
  'sendMessage' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => ($errStr),
    'errDesc' => ($errDesc),
    'censor' => array(
      'word' => ($blockWordApi['word']),
      'severity' => ($blockWordApi['severity']),
      'reason' => ($blockWordApi['reason']),
    ),
  ),
);



/* Plugin Hook End */
($hook = hook('sendMessage_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);



/* Close Database Connection */
dbClose();
?>