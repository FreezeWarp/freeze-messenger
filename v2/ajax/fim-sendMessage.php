<?php
require_once('../global.php');
require_once('../functions/parserFunctions.php');

$message = vrim_urldecode($_POST['message']); // Get the message from POST.
$room = intval($_POST['room']); // Get the room from POST.

$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");

if (strlen($message) == 0 || strlen($message) > 1000) { echo 'This message is too long or short. Messages must be more than 0 characters and less than or equal to 1000.'; } // Too short/long.
elseif (preg_match('/^(\ |\n|\r)*$/',$message)) { echo 'In some countries, you could be arrested for posting only spaces. Now aren\'t you glad we stopped you?'; } // All spaces.
//elseif (strstr($message,array('4chan.net','Pokefarm.net'))) { echo 'You have made mention to a website which is banned within the chat.'; }
elseif (!hasPermission($room,$user)) { echo 'Whoa buddy. You aren\'t allowed to post here.'; } // Not allowed to post.
elseif (strpos($message, '/topic') === 0) {
  $title = preg_replace('/^\/topic (.+?)$/i','$1',$message);

  $title = mysqlEscape(censor($title)); // Parses the sources for MySQL and UTF8. We will also censor, but no BBcode.

  sendMessage('/me changed the topic to ' . $title,$user,$room,'topic');
  mysqlQuery("UPDATE {$sqlPrefix}rooms SET title = '$title' WHERE id = $room[id]");
}
else {
  if (strpos($message, '/me') === 0) { $flag = 'me'; }

  sendMessage($message,$user,$room,$flag);

  echo 'success';
}

mysqlClose();
?>