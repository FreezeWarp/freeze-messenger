<?php
require_once('../global.php');
require_once('../functions/parserFunctions.php');

$message = vrim_urldecode($_POST['message']); // Get the message from POST.
$room = intval($_POST['room']); // Get the room from POST.

$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");
$ip = mysqlEscape($_SERVER['REMOTE_ADDR']); // Get the IP address of the user.

if (strlen($message) == 0 || strlen($message) > 1000) { echo 'This message is too long or short. Messages must be more than 0 characters and less than or equal to 1000.'; } // Too short/long.
elseif (preg_match('/^(\ |\n|\r)*$/',$message)) { echo 'In some countries, you could be arrested for posting only spaces. Now aren\'t you glad we stopped you?'; } // All spaces.
//elseif (strstr($message,array('4chan.net','Pokefarm.net'))) { echo 'You have made mention to a website which is banned within the chat.'; }
elseif (!hasPermission($room,$user)) { echo 'Whoa buddy. You aren\'t allowed to post here.'; } // Not allowed to post.
elseif (strpos($message, '/topic') === 0) {
  $title = preg_replace('/^\/topic (.+?)$/i','$1',$message);

  $title = mysqlEscape(censor($title)); // Parses the sources for MySQL and UTF8. We will also censor, but no BBcode.
 
  mysqlQuery("UPDATE {$sqlPrefix}rooms SET title = '$title' WHERE id = $room[id]");

  $message = finalParse('/me changed the topic to ' . $title);

  list($messageRaw,$messageHtml,$messageVBnet,$saltNum,$iv) = $message;

  mysqlQuery("INSERT INTO {$sqlPrefix}messages (user, room, rawText, htmlText, vbText, salt, iv, microtime, ip, flag) VALUES ($user[userid], $room[id], '$messageRaw', '$messageHtml', '$messageVBnet', $saltNum, '$iv', '" . microtime(true) . "', '$ip', 'topic')");

  echo 'success';
}
else {
  if (strpos($message, '/me') === 0) { $flag = 'me'; }
  $message = finalParse($message);


  list($messageRaw,$messageHtml,$messageVBnet,$saltNum,$iv) = $message;

  mysqlQuery("UPDATE {$sqlPrefix}rooms SET lastMessageTime = NOW() WHERE id = $room[id]");
  mysqlQuery("INSERT INTO {$sqlPrefix}messages (user, room, rawText, htmlText, vbText, salt, iv, microtime, ip, flag) VALUES ($user[userid], $room[id], '$messageRaw', '$messageHtml', '$messageVBnet', $saltNum, '$iv', '" . microtime(true) . "', '$ip', '$flag')");

  echo mysql_error();

  echo 'success';
}

mysqlClose();
?>