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

ini_set('max_execution_time','30');

require_once('../global.php');

/* Initialize Variables */
$room = intval($_GET['room']);
$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");
$lastid = intval($_GET['lastMessage']);
$lastmessage = $lastid;
$reverse = intval($_GET['reverse']);
$time = time();

if (!hasPermission($room,$user,'view')) {} // Gotta make sure the user can view that room.
else {
  /* Update Ping */
  mysqlQuery("INSERT INTO {$sqlPrefix}ping (userid,roomid,time) VALUES ($user[userid],$room[id],CURRENT_TIMESTAMP()) ON DUPLICATE KEY UPDATE time = CURRENT_TIMESTAMP()");

  /* Get Messages */
  $messages = sqlArr("
SELECT m.id,
  UNIX_TIMESTAMP(m.time) AS time,
  m.htmlText,
  m.iv,
  m.salt,
  m.flag,
  u.{$sqlUserIdCol} AS userid,
  u.{$sqlUsernameCol} AS username,
  u.{$sqlUsergroupCol} AS displaygroupid,
  u2.settings AS usersettings,
  u2.defaultColour,
  u2.defaultFontface,
  u2.defaultHighlight,
  u2.defaultFormatting
FROM {$sqlPrefix}messages AS m,
  user AS u,
  {$sqlPrefix}users AS u2
WHERE room = $room[id]
  AND m.deleted != true
  AND m.user = u.userid
  AND m.user = u2.userid
  AND m.id > $lastid
ORDER BY m.time DESC
LIMIT $messageLimit",'id');
  if ($reverse && $messages) $messages = array_reverse($messages);

  if ($messages) {
    if ((($user['settings'] & 16) || in_array($user['userid'],explode(',',$room['moderators'])) || $user['userid'] == $room['owner']) && (($room['options'] & 32) == false)) $canModerate = true; // The user /can/ moderate if they are a mod of the room, the room's owner, or an admin. If the room is disabled from moderation ($room['options'] & 32), then you still can't edit it.

    foreach ($messages AS $id => $message) {
      $message = vrim_decrypt($message);
      $style = messageStyle($message);

      if ($message['flag'] == 'topic' && !$stopTopic) {
        $topic = preg_replace('/^\/me changed the topic to (.+?)$/i','$1',$message['rawText']);
        if (!$reverse) $stopTopic = true;
      }

      switch ($_GET['mode']) {
        case 'complex':
        case '':
       $messagesText .= "<span id=\"message$message[id]\" class=\"messageLine\" style=\"padding-bottom: 3px; padding-top: 3px; vertical-align: middle;\"><img alt=\"\" src=\"{$forumUrl}image.php?u=$message[userid]\" style=\"max-width: 32px; max-height: 32px; padding-right: 3px;\" class=\"username usernameTable\" data-userid=\"$message[userid]\" time=\"" . vbdate(false,$message['time']) .  "\" /><span style=\"{$style}padding: 2px;\" class=\"messageText\" data-messageid=\"$message[id]\">$message[htmlText]</span><br />
</span>\n";
        break;

        case 'simple':
$messagesText .= "<span id=\"message$message[id]\" class=\"messageLine\">" . userFormat($message, $room) . "
  @ <em>" . vbdate(false,$message['time']) . "</em>: <span style=\"{$style}padding: 2px;\" class=\"messageText\" data-messageid=\"$message[id]\">$message[htmlText]</span><br />
</span>\n";
        break;
      }

      if ($message['id'] > $lastmessage) $lastmessage = $message['id'];
    }

    if ($user['settings'] & 1024 || $_GET['disableVideo']) {
      $messagesText = preg_replace('/<object(.+?)>(.*?)<embed src="(.+?)"(.+?)>(.*?)<\/embed>(.*?)<\/object>/','<a href="$3" target="_BLANK">Youtube Video</a>',$messagesText);
    }
  }

  /* Get Active Users */
  $users = sqlArr("
SELECT u.{$sqlUsernameCol} AS username,
  u.{$sqlUserIdCol} AS userid,
  u.{$sqlUsergroupCol} AS displaygroupid,
  p.id,
  u2.settings AS usersettings
FROM {$sqlPrefix}ping AS p,
  {$sqlUserTable} AS u,
  {$sqlPrefix}users AS u2
WHERE p.roomid = $room[id]
  AND p.userid = u.userid
  AND u2.userid = u.userid
  AND UNIX_TIMESTAMP(p.time) >= (UNIX_TIMESTAMP(NOW()) - $onlineThreshold)
ORDER BY u.username",'id');

  if ($users) {
    foreach ($users AS $user2) { $users2[] = userFormat($user2, $room, false); }
  }
  $activeUsers = implode(', ',$users2);

  /* Get Missed Messages */
  $missedMessages = sqlArr("SELECT r.*
FROM {$sqlPrefix}rooms AS r
  LEFT JOIN {$sqlPrefix}ping AS p ON (p.userid = $user[userid] AND p.roomid = r.id)
WHERE (r.options & 16 " . ($user['watchRooms'] ? " OR r.id IN ($user[watchRooms])" : '') . ")
  AND (r.allowedUsers REGEXP '({$user[userid]},)|{$user[userid]}$' OR r.allowedUsers = '*')
  AND IF(p.time, UNIX_TIMESTAMP(r.lastMessageTime) > (UNIX_TIMESTAMP(p.time) + 10), TRUE)",'id'); // Right now only private IMs are included, but in the future this will be expanded.

  if ($missedMessages) {
    foreach ($missedMessages AS $message) {
      if (!hasPermission($message,$user,'view')) { continue; }

      $roomName = htmlspecialchars(addslashes($message['name']));
      $return .= "notify('<a href=\"./index.php?room=$message[id]\" target=\"_BLANK\">$roomName</a>','New Messages','newMessageNotification',$message[id]);";
    }
  }

  /* Present Data */
  if ($_GET['encrypt'] == 'plaintext') {
    if ($lastmessage) $return .= "lastMessage = $lastmessage;";
    if ($topic) $return .= 'topic = "' . str_replace(array("\n",'\\','"'),array('','\\\\','\"'),$topic) . '";';
    if ($messagesText) $return .= 'messages = "' . str_replace(array("\n",'\\','"'),array('','\\\\','\"'),$messagesText) . '";';
    if ($activeUsers) $return .= 'activeUsers = "' . str_replace(array("\n",'\\','"'),array('','\\\\','\"'),$activeUsers) . '";';
  }
  else {
    if ($lastmessage) $return .= "lastMessage = $lastmessage;";
    if ($topic) $return .= 'topic = "' . base64_encode($topic) . '";';
    if ($messagesText) $return .= 'messages = "' . base64_encode($messagesText) . '";';
    if ($activeUsers) $return .= 'activeUsers = "' . base64_encode($activeUsers) . '";';
  }

  echo $return;
}

mysqlClose();
?>