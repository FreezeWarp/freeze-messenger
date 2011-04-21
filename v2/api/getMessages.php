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

$apiRequest = true;

require_once('../global.php');
header('Content-type: text/xml');

$rooms = $_GET['rooms'];
$roomsArray = explode(',',$rooms);
foreach ($roomsArray AS &$v) $v = intval($v);

$newestMessage = intval($_GET['messageIdMax']);
$oldestMessage = intval($_GET['messageIdMin']);

$newestDate = intval($_GET['messageDateMax']);
$oldestDate = intval($_GET['messageDateMin']);

$messageStart = intval($_GET['messageIdStart']);

$watchRooms = intval($_GET['watchRooms']);

if ($_GET['maxMessages'] == '0') {
  $messageLimit = 10000000000;
}
else {
  $messageLimit = ($_GET['maxMessages'] ? intval($_GET['maxMessages']) : ($messageLimit ? $messageLimit : 40));
}

if ($newestMessage) $whereClause .= "AND m.id < $newestMessage ";
if ($oldestMessage) $whereClause .= "AND m.id > $oldestMessage ";
if ($newestdate) $whereClause .= "AND m.date < $newestdate ";
if ($oldestdate) $whereClause .= "AND m.date > $oldestdate ";
if (!$whereClause && $messageStart) {
  echo $whereClause .= "AND m.id > $messageStart AND m.id < " . ($messageStart + $messageLimit);
}

if (!$rooms) {
  $failCode = 'badroomsrequest';
  $failMessage = 'The room string was not supplied or evaluated to false.';
}
if (!$roomsArray) {
  $failCode = 'badroomsrequest';
  $failMessage = 'The room string was not formatted properly in Comma-Seperated notation.';
}
else {
  foreach ($roomsArray AS $roomXML) {
    $roomsXML .= "<room>$roomXML</room>";
  }

  foreach ($roomsArray AS $room2) {
    $room2 = intval($room2);
    $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room2");

    if ($room) {
      if (!hasPermission($room,$user)) { } // Gotta make sure the user can view that room.
      else {
        if (!$_GET['noping']) mysqlQuery("INSERT INTO {$sqlPrefix}ping (userid,roomid,time) VALUES ($user[userid],$room[id],CURRENT_TIMESTAMP()) ON DUPLICATE KEY UPDATE time = CURRENT_TIMESTAMP()");

        $messages = sqlArr("SELECT m.id, UNIX_TIMESTAMP(m.time) AS time, m.rawText, m.vbText, m.htmlText, m.iv, m.salt, u.userid, u.username, u.displaygroupid, u2.defaultColour, u2.defaultFontface, u2.defaultHighlight, u2.defaultFormatting FROM {$sqlPrefix}messages AS m, user AS u, {$sqlPrefix}users AS u2 WHERE room = $room[id] AND deleted != true AND m.user = u.userid AND m.user = u2.userid $whereClause ORDER BY m.id DESC LIMIT $messageLimit",'id');

        if ($messages) {
          if ($_GET['order'] == 'reverse') $messages = array_reverse($messages);
          foreach ($messages AS $id => $message) {
            $message = vrim_decrypt($message);

            $message['username'] = addslashes($message['username']);
            $message['appText'] = htmlspecialchars($message['vbText']);
            $message['rawText'] = htmlspecialchars($message['rawText']);
            $message['displaygroupid'] = displayGroupToColour($message['displaygroupid']);
            $messageXML .=  "    <message>
      <roomdata>
        <roomid>$room[id]</roomid>
        <roomname>$room[name]</roomname>
        <roomtopic>$room[title]</roomtopic>
      </roomdata>
      <messagedata>
        <messageid>$message[id]</messageid>
        <messagetime>$message[time]</messagetime>
        <messagetext>
          <raw>$message[rawText]</raw>
          <app>$message[appText]</app>
          <html>$message[htmlText]</html>
        </messagetext>
        <flags>$message[flag]</flags>
      </messagedata>
      <userdata>
        <username>$message[username]</username>
        <userid>$message[userid]</userid>
        <displaygroupid>$message[displaygroupid]</displaygroupid>
        <defaultFormatting>
          <color>$message[defaultColour]</color>
          <highlight>$message[defaultHighlight]</highlight>
          <fontface>$message[defaultFontface]</fontface>
          <general>$message[defaultFormatting]</general>
        </defaultFormatting>
      </userdata>
    </message>";
          }
        }
      }
    }
  }
}

if ($watchRooms) {
  /* Get Missed Messages */
  $missedMessages = sqlArr("SELECT r.*, UNIX_TIMESTAMP(r.lastMessageTime) AS lastMessageTimestamp FROM {$sqlPrefix}rooms AS r LEFT JOIN {$sqlPrefix}ping AS p ON (p.userid = $user[userid] AND p.roomid = r.id) WHERE (r.options & 16 " . ($user['watchRooms'] ? " OR r.id IN ($user[watchRooms])" : '') . ") AND (r.allowedUsers REGEXP '({$user[userid]},)|{$user[userid]}$' OR r.allowedUsers = '*') AND IF(p.time, UNIX_TIMESTAMP(r.lastMessageTime) > (UNIX_TIMESTAMP(p.time) + 10), TRUE)",'id'); // Right now only private IMs are included, but in the future this will be expanded.

  if ($missedMessages) {
    foreach ($missedMessages AS $message) {
      if (!hasPermission($message,$user,'view')) { continue; }

      $roomName = vrim_encodeXML($message['name']);
      $watchRoomsXML .= "    <room>
      <roomid>$message[id]</roomid>
      <roomname>$roomName</roomname>
      <lastMessageTime>$message[lastMessageTimestamp]</lastMessageTime>
    </room>";
    }
  }
}



$data = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<!DOCTYPE html [
  <!ENTITY nbsp \" \">
]>
<getMessages>
  <activeUser>
    <userid>$user[userid]</userid>
    <username>" . vrim_encodeXML($user['username']) . "</username>
  </activeUser>
  <sentData>
    <rooms>$rooms</rooms>
    <roomsList>
    $roomsXML
    </roomsList>
    <newestMessage>$newestMessage</newestMessage>
    <oldestMessage>$oldestMessage</oldestMessage>
    <newestDate>$newestDate</newestDate>
    <oldestDate>$oldestDate</oldestDate>
  </sentData>
  <errorcode>$failCode</errorcode>
  <errortext>$failMessage</errortext>
  <messages>
    $messageXML
  </messages>
  <watchrooms>
    $watchRoomsXML
  </watchrooms>
</getMessages>";

if ($_GET['gz']) {
 echo gzcompress($data);
}
else {
  echo $data;
}

mysqlClose();
?>