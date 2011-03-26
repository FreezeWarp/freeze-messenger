<?php
ini_set('max_execution_time','30');

require_once('../global.php');

$room = intval($_GET['room']);
$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");
$source = $_GET['source'];
$time = time();

mysqlQuery("INSERT INTO {$sqlPrefix}ping (userid,roomid,time) VALUES ($user[userid],$room[id],CURRENT_TIMESTAMP()) ON DUPLICATE KEY UPDATE time = CURRENT_TIMESTAMP()");

$lastid = intval($_GET['lastMessage']);
$reverse = intval($_GET['reverse']);

if (!hasPermission($room,$user)) {} // Gotta make sure the user can view that room.
else {
  $messages = sqlArr("SELECT m.id, UNIX_TIMESTAMP(m.time) AS time, m.rawText, m.vbText, m.htmlText, m.iv, m.salt, u.userid, u.username, u.displaygroupid, u2.settings AS usersettings, u2.defaultColour, u2.defaultFontface, u2.defaultHighlight, u2.defaultFormatting, m.flag FROM {$sqlPrefix}messages AS m, user AS u, {$sqlPrefix}users AS u2 WHERE room = $room[id] AND deleted != true AND m.user = u.userid AND m.user = u2.userid AND m.id > $lastid ORDER BY m.time DESC LIMIT $messageLimit",'id');
  if ($reverse && $messages) $messages = array_reverse($messages);

  if ($messages) {
    if ((($user['settings'] & 16) || in_array($user['userid'],explode(',',$room['moderators'])) || $user['userid'] == $room['owner']) && (($room['options'] & 32) == false)) $canModerate = true; // The user /can/ moderate if they are a mod of the room, the room's owner, or an admin. If the room is disabled from moderation ($room['options'] & 32), then you still can't edit it.

    foreach ($messages AS $id => $message) {
      $message = vrim_decrypt($message);
      $style = messageStyle($message);

      if ($message['flag'] == 'topic') {
        $topic = preg_replace('/^\/me changed the topic to (.+?)$/i','$1',$message['rawText']);
      }

  $messagesText .= "<span id=\"message$message[id]\" class=\"messageLine\">" . userFormat($message, $room) . "
  @ <em>" . vbdate(false,$message['time']) . "</em>: <span style=\"{$style}padding: 2px;\" class=\"messageText\">$message[htmlText]</span><br />
</span>\n";
      if ($message['id'] > $lastmessage) $lastmessage = $message['id'];
    }

    if ($user['settings'] & 1024 || $_GET['disableVideo']) {
      $messagesText = preg_replace('/<object(.+?)>(.*?)<embed src="(.+?)"(.+?)>(.*?)<\/embed>(.*?)<\/object>/','<a href="$3" target="_BLANK">Youtube Video</a>',$messagesText);
    }

    if (strlen($topic) > 0) {
      $topic = htmlentities($topic);
      $messagesText .= "<script type=\"text/javascript\"></script>";
    }
  }
}

$users = sqlArr("SELECT u.username, u.userid, u.displaygroupid, p.id, u2.settings AS usersettings FROM {$sqlPrefix}ping AS p, user AS u, {$sqlPrefix}users AS u2 WHERE p.roomid = $room[id] AND p.userid = u.userid AND u2.userid = u.userid AND UNIX_TIMESTAMP(p.time) >= UNIX_TIMESTAMP(NOW()) - $onlineThreshold ORDER BY u.username",'id');

if ($users) {
  foreach ($users AS $user) {
    $users2[] = userFormat($user, $room, false);
  }
}

$activeUsers = base64_encode(implode(', ',$users2));

$messagesText = base64_encode($messagesText);

if ($lastmessage) $return .= "lastMessage = $lastmessage;";
$return .= "messages = '$messagesText';";
if ($topic) $return .= "$('#title$room[id]').html('$topic');";
$return .= "activeUsers = '$activeUsers';";

echo $return;

mysqlClose();
?>