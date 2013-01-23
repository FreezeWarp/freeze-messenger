<?php
/* FreezeMessenger Copyright © 2012 Joseph Todd Parsons

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
 * Works with both private and normal rooms.
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 *
 * @param int roomId - The room ID.
 * @param string message - The message text, properly URLencoded.
 * @param string flag - A message content-type/context flag, used for sending images, urls, etc.
 * @param bool ignoreBlock - If true, the system will ignore censor warnings. You must pass this to resend a message that was denied because of a censor warning.
*/

$apiRequest = true;

require('../global.php');


/* Get Request Data */
$request = fim_sanitizeGPC('p', array(
  'roomId' => array(
    'require' => true,
  ),

  'message' => array(),

  'flag' => array(
    'valid' => array('image', 'video', 'url', 'email', 'html', 'audio', 'text', 'source', ''),
  ),

  'ignoreBlock' => array(
    'default' => false,
    'cast' => 'bool',
  ),
));
$ip = $_SERVER['REMOTE_ADDR']; // Get the IP address of the user.


/* Plugin Hook */
($hook = hook('sendMessage_start') ? eval($hook) : '');


/* Get Room for DB */
$roomData = $database->getRoom($request['roomId']);


/* Censor Fun */
$blockedWord = false;
$blockedWordText = false;
$blockedWordReason = false;
$blockedWordSeverity = false;
$blockWordApi = array(
  'word' => '',
  'severity' => '',
  'reason' => '',
);

if ($censorWordsCache['byWord']) {
  ($hook = hook('sendMessage_censor_start') ? eval($hook) : '');

  foreach ($censorWordsCache['byWord'] AS $word) {
    if ($request['ignoreBlock'] && $word['severity'] === 'confirm') continue;

    $searchText[] = addcslashes(strtolower($word['word']), '^&|!$?()[]<>\\/.+*');

    ($hook = hook('sendMessage_censor_eachWord') ? eval($hook) : '');
  }


  if ($searchText) {
    preg_match('/(' . implode('|',$searchText) . ')/i', $request['message'], $matches);
    if ($matches[1]) {
      $blockedWord = strtolower($matches[1]);
      $blockedWordText = $censorWordsCache['byWord'][$blockedWord]['word'];
      $blockedWordReason = $censorWordsCache['byWord'][$blockedWord]['param'];
      $blockedWordSeverity = $censorWordsCache['byWord'][$blockedWord]['severity'];
    }
  }

  ($hook = hook('sendMessage_censor_end') ? eval($hook) : '');
}


/* Plugin Hook */
($hook = hook('sendMessage_preGen') ? eval($hook) : '');


/* Start Processing */
if ($continue) {
  if (!$roomData['roomId']) { // Bad room.
    $errStr = 'badRoom';
    $errDesc = 'That room could not be found.';
  }
  elseif (strlen($request['message']) == 0 || strlen($request['message']) > $config['maxMessageLength']) { // Too short/long.
    $errStr = 'badMessage';
    $errDesc = 'The message you entered is either too long or too short.';
  }
  elseif (preg_match('/^(\ |\n|\r)*$/', $request['message'])) { // All spaces.
    $errStr = 'spaceMessage';
    $errDesc = 'In some countries, you could be arrested for posting only spaces. Now aren\'t you glad we stopped you?';
  }
  elseif (!fim_hasPermission($roomData, $user, 'post', true)) { // Not allowed to post.
    $errStr = 'noPerm';
    $errDesc = 'You are not allowed to post in this room.';
  }
  // TODO: MB Support
  elseif (in_array($request['flag'], array('image', 'video', 'url', 'html', 'audio')) && !filter_var($request['message'], FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) { // If the message is suppoed to be a URI, make sure it is. (We do this here and not at the function level to allow for plugins to override such a check).
    $errStr = 'badUrl';
    $errDesc = 'The URL specified is not valid.';
  }
  elseif (($request['flag'] === 'email') && !filter_var($request['message'], FILTER_VALIDATE_EMAIL)) { // If the message is suppoed to be an email, make sure it is. (We do this here and not at the function level to allow for plugins to override such a check).
    $errStr = 'badEmail';
    $errDesc = 'The email specified is not valid.';
  }
  elseif ($blockedWordSeverity == 'block') {
    $errStr = 'blockCensor';
    $errDesc = 'The word ' . $blockedWordText . ' is not allowed: ' . $blockedWordReason;

    $blockWordApi['severity'] = 'block';
    $blockWordApi['word'] = $blockedWordText;
    $blockWordApi['reason'] = $blockedWordReason;
  }
  elseif ($blockedWordSeverity == 'confirm') {
    $errStr = 'confirmCensor';
    $errDesc = 'Warning: The word ' . $blockedWordtext . ' may not be allowed: ' . $blockedWordReason;

    $blockWordApi['severity'] = 'confirm';
    $blockWordApi['word'] = $blockedWordText;
    $blockWordApi['reason'] = $blockedWordReason;
  }
  elseif (strpos($request['message'], '/topic') === 0 && !$config['disableTopic']) {
    $topicNew = preg_replace('/^\/topic (.+?)$/i', '$1', $request['message']); // Strip the "/topic" from the message.

    ($hook = hook('sendMessage_topic') ? eval($hook) : '');

    if ($continue) {
      fim_sendMessage($request['message'], '', $user, $roomData);

      $database->createEvent('topicChange', false, $roomData['roomId'], false, $topic, false, false); // name, user, room, message, p1, p2, p3

      $database->update("{$sqlPrefix}rooms", array(
        'roomTopic' => $topic,
      ), array(
      'roomId' => $roomData['roomId'],
      ));
    }
  }/*
  elseif (strpos($request['message'], '/kick') === 0) {
    $kickData = preg_replace('/^\/kick (.+?)(| ([0-9]+?))$/i','$1,$2',$request['message']);
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
    if ($blockedWordSeverity == 'warn') {
      $blockWordApi['severity'] = 'warn';
      $blockWordApi['word'] = $blockedWordText;
      $blockWordApi['reason'] = $blockedWordReason;
    }

    ($hook = hook('sendMessage_send') ? eval($hook) : '');

    if ($continue) {
      fim_sendMessage($request['message'], $request['flag'], $user, $roomData);
    }
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
?>