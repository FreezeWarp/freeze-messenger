<?php
require_once('../global.php');
require_once('../functions/parserFunctions.php');
header('Content-type: text/plain');

//$message = utf8_decode(urldecode($_GET['message']));
$message = vrim_urldecode($_GET['message']);

$room = intval($_GET['room']);
$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");
$ip = mysqlEscape($_SERVER['REMOTE_ADDR']); // Get the IP address of the user.

if (!$room) { echo '\'0\',\'invalidroom\''; } 
elseif (strlen($message) == 0 || strlen($message) > 1000) { echo '\'0\',\'badmessage\''; } // Too short/long.
elseif (!hasPermission($room,$user)) { echo '\'0\',\'noperm\''; } // Not allowed to post.
else {
  $message = finalParse($message);

  list($messageRaw,$messageHtml,$messageVBnet,$saltNum,$iv) = $message;

  mysqlQuery("INSERT INTO {$sqlPrefix}messages (user, room, rawText, htmlText, vbText, salt, iv, microtime, ip) VALUES ($user[userid], $room[id], '$messageRaw', '$messageHtml', '$messageVBnet', $saltNum, '$iv', '" . microtime(true) . "', '$ip')");
  echo '\'1\',\'\'';
}

mysqlClose();
?>