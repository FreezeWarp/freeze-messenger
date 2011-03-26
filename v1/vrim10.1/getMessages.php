<?php
require_once('../global.php');
header('Content-type: text/xml');

$rooms = $_GET['rooms'];
$roomsArray = explode(',',$rooms);
foreach ($roomsArray AS &$v) $v = intval($v);

$newestMessage = intval($_GET['newestMessage']);
$oldestMessage = intval($_GET['oldestMessage']);

$newestDate = intval($_GET['newestDate']);
$oldestDate = intval($_GET['oldestDate']);

if ($newestMessage) $whereClause .= "AND m.id < $newestMessage ";
if ($oldestMessage) $whereClause .= "AND m.id > $oldestMessage ";
if ($newestdate) $whereClause .= "AND m.date < $newestdate ";
if ($oldestdate) $whereClause .= "AND m.date > $oldestdate ";

if (!$rooms) {
  $failCode = 'badroomsrequest';
  $failMessage = 'The room string was not supplied or evaluated to false.';
}
if (!$roomsArray) {
  $failCode = 'badroomsrequest';
  $failMessage = 'The room string was not formatted properly in Comma-Seperated notation.';
}
else {
  foreach ($roomsArray AS $roomXML) $roomsXML .= "<room>$roomXML</room>";

  foreach ($roomsArray AS $room2) {
    $room2 = intval($room2);
    $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room2");

    if ($room) {
      if (!hasPermission($room,$user)) { } // Gotta make sure the user can view that room.
      else {
        $messages = sqlArr("SELECT m.id, UNIX_TIMESTAMP(m.time) AS time, m.rawText, m.vbText, m.htmlText, m.iv, m.salt, u.userid, u.username, u.displaygroupid, u2.defaultColour, u2.defaultFontface, u2.defaultHighlight, u2.defaultFormatting FROM {$sqlPrefix}messages AS m, user AS u, {$sqlPrefix}users AS u2 WHERE room = $room[id] AND deleted != true AND m.user = u.userid AND m.user = u2.userid $whereClause ORDER BY m.id DESC LIMIT $messageLimit",'id');

        if ($messages) {
          if ($_GET['order'] == 'reverse') $messages = array_reverse($messages);
          foreach ($messages AS $id => $message) {
            $message = vrim_decrypt($message);

            $message['username'] = addslashes($message['username']);
            $message['vbText'] = htmlspecialchars($message['vbText']);
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
        <rawText>$message[rawText]</rawText>
        <vbText>$message[vbText]</vbText>
        <flags>$message[flag]</flags>
      </messagedata>
      <userdata>
        <username>$message[username]</username>
        <userid>$message[userid]</userid>
        <displaygroupid>$message[displaygroupid]</displaygroupid>
        <defaultColour>$message[defaultColour]</defaultColour>
        <defaultHighlight>$message[defaultHighlight]</defaultHighlight>
        <defaultFontface>$message[defaultFontface]</defaultFontface>
        <defaultFormatting>$message[defaultFormatting]</defaultFormatting>
      </userdata>
    </message>";
          }
        }
      }
    }
  }
}



$data = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<getMessages>
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
</getMessages>";

if ($_GET['gz']) {
 echo gzcompress($data);
}
else {
  echo $data;
}

mysqlClose();
?>