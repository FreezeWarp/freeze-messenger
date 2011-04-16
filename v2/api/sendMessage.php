<?php
$apiRequest = true;

require_once('../global.php');
require_once('../functions/parserFunctions.php');
header('Content-type: text/xml');

$message = vrim_urldecode($_GET['message']);

$room = intval($_GET['room']);
$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");
$ip = mysqlEscape($_SERVER['REMOTE_ADDR']); // Get the IP address of the user.



$words = sqlArr("SELECT w.word, w.severity, w.param
FROM {$sqlPrefix}censorLists AS l, {$sqlPrefix}censorWords AS w
WHERE w.listid = l.id AND (w.severity = 'warn' OR w.severity = 'confirm' OR w.severity = 'block')",'word');

if (!$words) {}
else {
  foreach ($words AS $word) {
    if ($_GET['ignoreBlock'] && $word['severity'] == 'confirm') continue;

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
elseif (!hasPermission($room,$user)) { // Not allowed to post.
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

  $title = mysqlEscape(censor($title)); // Parses the sources for MySQL and UTF8. We will also censor, but no BBcode.

  sendMessage('/me changed the topic to ' . $title,$user,$room,'topic');
  mysqlQuery("UPDATE {$sqlPrefix}rooms SET title = '$title' WHERE id = $room[id]");
}
else {
  if (strpos($message, '/me') === 0) { $flag = 'me'; }

  sendMessage($message,$user,$room,$flag);
}



  echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<sendMessage>
  <activeUser>
    <userid>$user[userid]</userid>
    <username>" . vrim_encodeXML($user['username']) . "</username>
  </activeUser>
  <sentData>
    <room>" . vrim_encodeXML($_GET['room']) . "</room>
    <message>" . vrim_encodeXML($_GET['message']) . "</message>
  </sentData>
  <errorcode>$failCode</errorcode>
  <errortext>$failMessage</errortext>
  <censor>
    <word>$blockWordApi[word]</word>
    <severity>$blockWordApi[severity]</severity>
    <reason>$blockWordApi[reason]</reason>
  </censor>
</sendMessage>";

mysqlClose();
?>