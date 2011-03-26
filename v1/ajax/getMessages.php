<?php
ini_set('max_execution_time','30');

require_once('../global.php');

$room = intval($_GET['room']);
$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");
$source = $_GET['source'];

$lastid = intval(($_GET['lastMessage'] ?: $_COOKIE['lastmessage-room' . $room['id']]) ?: 0);

$reverse = intval($_GET['reverse']);

$light = ($_GET['light'] ? true : false);

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

  $messagesText .= "<span id=\"message$message[id]\" class=\"messageLine\">" . ($canModerate && !$light ? "
  <a href=\"javascript:void(0);\" onclick=\"if (confirm('Are you sure you want to delete this message')) { $.ajax({url: '/ajax/modAction.php?action=deletepost&amp;postid=$message[id]', type: 'GET', cache: false, success: function() { $('#message$message[id]').fadeOut(); } }); }\">
    <img src=\"images/edit-delete.png\" style=\"height: 12px; width: 12px;\" alt=\"Del\" />
   </a>" : '') . userFormat($message, $room) . "
  @ <em>" . vbdate(false,$message['time']) . "</em>: <span style=\"{$style}padding: 2px;\">$message[htmlText]</span><br />
</span>\n";
      if ($message['id'] > $lastmessage) $lastmessage = $message['id'];
    }

    if ($user['settings'] & 1024 || $_GET['disableVideo']) {
      $messagesText = preg_replace('/<object(.+?)>(.*?)<embed src="(.+?)"(.+?)>(.*?)<\/embed>(.*?)<\/object>/','<a href="$3" target="_BLANK">Youtube Video</a>',$messagesText);
    }

    if (strlen($topic) > 0) {
      $topic = htmlentities($topic);
      $messagesText .= "<script type=\"text/javascript\">$('#title$room[id]').html('$topic');</script>";
    }
  }
}


if ($lastmessage) {
  $messagesText .= "<script type=\"text/javascript\">var lastMessage = $lastmessage;</script>";
}

//setcookie('lastmessage-room' . $room['id'],$lastmessage ?: $lastid,0,'/','.victoryroad.net');

switch ($_GET['encrypt']) {
  case 'base64':
  if ($messagesText) {
    echo base64_encode($messagesText);
  }
  break;

  default:
  echo $messagesText;
  break;
}

mysqlClose();
?>