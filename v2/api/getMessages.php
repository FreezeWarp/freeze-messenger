<?php
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


$messageLimit = ($_GET['maxMessages'] ? intval($_GET['maxMessages']) : ($messageLimit ? $messageLimit : 40));

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
</getMessages>";

if ($_GET['gz']) {
 echo gzcompress($data);
}
else {
  echo $data;
}

mysqlClose();
?>