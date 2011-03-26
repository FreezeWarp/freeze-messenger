<?php
require_once('../global.php');
require_once('../functions/parserFunctions.php');
header('Content-type: text/plain');

$message = urldecode($_GET['message']);
$room = intval($_GET['room']);
$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");
$ip = mysqlEscape($_SERVER['REMOTE_ADDR']); // Get the IP address of the user.

if (!$room) { echo '\'0\',\'invalidroom\''; } 
elseif (strlen($message) == 0 || strlen($message) > 1000) { echo '\'0\',\'badmessage\''; } // Too short/long.
elseif (!hasPermission($room,$user)) { echo '\'0\',\'noperm\''; } // Not allowed to post.
else {
  $messageRaw = mysqlEscape(utf8_urldecode(utf8_encode($message))); // Parses the sources for MySQL.
  $messageHtml = mysqlEscape(nl2br(utf8_urldecode(htmlParse(utf8_encode(smilie(censor($message))),$room['bbcode'])))); // Parses for browser or HTML rendering.
  $messageVBnet = mysqlEscape(nl2vb(utf8_urldecode(utf8_encode(smilie(censor($message)))))); // Not yet coded, you see.
  
  mysqlQuery("INSERT INTO {$sqlPrefix}messages (user, room, rawText, htmlText, vbText, microtime, ip) VALUES ($user[userid], $room[id], '$messageRaw', '$messageHtml', '$messageVBnet', '" . microtime(true) . "', '$ip')");
  echo '\'1\',\'\'';
}

mysqlClose();
?>