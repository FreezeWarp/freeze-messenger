<?php
require_once('../global.php');
require_once('../functions/parserFunctions.php');

$title = $_POST['title']; // Get the message from POST.

$room = intval($_POST['room']); // Get the room from POST.
$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");

if (strlen($title) > 100) { } // Too short/long.
elseif (!hasPermission($room,$user,'post')) { } // Not allowed to post.
else {
  $title = mysqlEscape(censor($title)); // Parses the sources for MySQL and UTF8. We will also censor, but no BBcode.
 
  mysqlQuery("UPDATE {$sqlPrefix}rooms SET title = '$title' WHERE id = $room[id]");

  $message = finalParse('/me changed the topic to ' . $title);

  list($messageRaw,$messageHtml,$messageVBnet,$saltNum,$iv) = $message;

  mysqlQuery("INSERT INTO {$sqlPrefix}messages (user, room, rawText, htmlText, vbText, salt, iv, microtime, ip, flag) VALUES ($user[userid], $room[id], '$messageRaw', '$messageHtml', '$messageVBnet', $saltNum, '$iv', '" . microtime(true) . "', '$ip', 'topic')");

  echo mysql_error();
  echo 'Success1';
}

mysqlClose();
?>