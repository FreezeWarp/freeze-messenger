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

if ($longPolling) {
  set_time_limit(0);
  ini_set('max_execution_time',0);
}



///* Variable Setting *///

$rooms = $_GET['rooms'];
$roomsArray = explode(',',$rooms);
foreach ($roomsArray AS &$v) $v = intval($v);

$newestMessage = intval($_GET['messageIdMax']); // INT
$oldestMessage = intval($_GET['messageIdMin']); // INT

$newestDate = intval($_GET['messageDateMax']); // INT
$oldestDate = intval($_GET['messageDateMin']); // INT

$messageStart = intval($_GET['messageIdStart']); // INT

$watchRooms = intval($_GET['watchRooms']); // BOOL
$activeUsers = intval($_GET['activeUsers']); // BOOL
$archive = intval($_GET['archive']); // BOOL
$noPing = intval($_GET['noping']); // BOOL

$encode = ($_GET['encode']); // String - 'base64', 'plaintext'
$fields = ($_GET['messageFields']); // String - 'api', 'html', or 'both'


$onlineThreshold = intval($_GET['onlineThreshold'] ?: $onlineThreshold); // INT - Only if activeUsers = TRUE

if ($_GET['messageLimit'] == '0') {
  $messageLimit = 500; // Sane maximum.
}
else {
  $messageLimit = ($_GET['messageLimit'] ? intval($_GET['messageLimit']) : ($messageLimit ? $messageLimit : 40));
  if ($messageLimit > 500) $messageLimit = 500; // Sane maximum.
}



///* Query Filter Generation *///

if ($newestMessage) {
  $whereClause .= "AND messageid < $newestMessage ";
}
if ($oldestMessage) {
  $whereClause .= "AND messageid > $oldestMessage ";
}
if ($newestDate) {
  $whereClause .= "AND UNIX_TIMESTAMP(m.time) < $newestDate ";
}
if ($oldestDate) {
  $whereClause .= "AND UNIX_TIMESTAMP(m.time) > $oldestDate ";
}
if (!$whereClause && $messageStart) {
  $whereClause .= "AND messageid > $messageStart AND messageid < " . ($messageStart + $messageLimit);
}

if ($loginMethod == 'vbulletin') {
  $tableClause .= "{$sqlUserGroupTable} AS g";
  $whereClause .= "u.{$sqlUserTableCols[usergroup]} = g.{$sqlUserGroupTableCols[groupid]}";
}
elseif ($loginMethod == 'phpbb') {
  $colClause .= ', u.user_colour';
}

///* Error Checking *///
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

        if (!$noPing) {
          mysqlQuery("INSERT INTO {$sqlPrefix}ping (userid,roomid,time) VALUES ($user[userid],$room[id],CURRENT_TIMESTAMP()) ON DUPLICATE KEY UPDATE time = CURRENT_TIMESTAMP()");
        }

        switch ($fields) {
          case 'both': case '': $messageFields = 'm.apiText AS apiText, m.htmlText AS htmlText,'; break;
          case 'api': $messageFields = 'm.apiText AS apiText,'; break;
          case 'html': $messageFields = 'm.htmlText AS htmlText,'; break;
          default:
            $failCode = 'badFields';
            $failMessage = 'The given message fields are invalid - recognized values are "api", "html", and "both"';
          break;
        }

        if ($archive) {
          $messageQuery = "SELECT m.id AS messageid,
  UNIX_TIMESTAMP(m.time) AS time,
  $messageFields
  m.iv AS iv,
  m.salt AS salt,
  u.{$sqlUserTableCols[userid]} AS userid,
  u.{$sqlUserTableCols[username]} AS username,
  u.{$sqlUserTableCols[usergroup]} AS displaygroupid,
  u2.defaultColour AS defaultColour,
  u2.defaultFontface AS defaultFontface,
  u2.defaultHighlight AS defaultHighlight,
  u2.defaultFormatting AS defaultFormatting
  $colClause
FROM {$sqlPrefix}messages AS m,
  {$sqlUserTable} AS u,
  {$sqlPrefix}users AS u2
WHERE room = $room[id]
  AND m.deleted != true
  AND m.user = u.{$sqlUserTableCols[userid]}
  AND m.user = u2.userid
$whereClause
ORDER BY messageid $order
LIMIT $messageLimit";
        }
        else {
          $messageQuery = "SELECT m.messageid AS messageid,
  UNIX_TIMESTAMP(m.time) AS time,
  $messageFields
  m.userid AS userid,
  m.username AS username,
  m.usergroup AS displaygroupid,
  m.groupFormatStart AS groupFormatStart,
  m.groupFormatEnd AS groupFormatEnd,
  m.flag AS flag,
  u2.settings AS usersettings,
  u2.defaultColour AS defaultColour,
  u2.defaultFontface AS defaultFontface,
  u2.defaultHighlight AS defaultHighlight,
  u2.defaultFormatting AS defaultFormatting
FROM {$sqlPrefix}messagesCached AS m,
  {$sqlPrefix}users AS u2
WHERE m.roomid = $room[id]
  AND m.userid = u2.userid
$whereClause
ORDER BY messageid $order
LIMIT $messageLimit";
        }

        if ($longPolling) {
          while (!$messages) {
            $messages = sqlArr($messageQuery,'messageid');
            sleep($longPollingWait);
          }
        }
        else {
          $messages = sqlArr($messageQuery,'messageid');
        }

        if ($messages) {
          foreach ($messages AS $id => $message) {
            $message = vrim_decrypt($message);

            $message['username'] = addslashes($message['username']);
            $message['apiText'] = vrim_encodeXML($message['apiText']);
            $message['htmlText'] = vrim_encodeXML($message['htmlText']);

            if ($loginMethod == 'phpbb') {
              $message['groupFormatStart'] = "<span style=\"color: #$message[user_colour]\">";
              $message['groupFormatEnd'] = '</span>';
            }

            switch ($encode) {
              case 'base64':
              $message['apiText'] = base64_encode($message['apiText']);
              $message['htmlText'] = base64_encode($message['htmlText']);
              break;
            }

            $messageXML .=  "    <message>
      <roomdata>
        <roomid>$room[id]</roomid>
        <roomname>$room[name]</roomname>
        <roomtopic>" . vrim_encodeXML($room['title']) . "</roomtopic>
      </roomdata>
      <messagedata>
        <messageid>$message[messageid]</messageid>
        <messagetime>$message[time]</messagetime>
        <messagetimeformatted>" . vbdate(false,$message['time']) . "</messagetimeformatted>
        <messagetext>
          <apptext>$message[apiText]</apptext>
          <htmltext>$message[htmlText]</htmltext>
        </messagetext>
        <flags>$message[flag]</flags>
      </messagedata>
      <userdata>
        <username>$message[username]</username>
        <userid>$message[userid]</userid>
        <displaygroupid>$message[displaygroupid]</displaygroupid>
        <startTag>" . vrim_encodeXML($message['groupFormatStart']) . "</startTag>
        <endTag>" . vrim_encodeXML($message['groupFormatEnd']) . "</endTag>
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

        ///* Process Active Users
        if ($activeUsers) {
          switch ($loginMethod) {
            case 'vbulletin':
            $join = "LEFT JOIN {$sqlUserGroupTable} AS g ON displaygroupid = g.{$sqlUserGroupTableCols[groupid]}";
            $cols = ", g.$sqlUserGroupTableCols[startTag] AS opentag, g.$sqlUserGroupTableCols[endTag] AS closetag";
            break;
            case 'phpbb':
            $cols = ", u.user_colour";
            break;
          }

          $ausers = sqlArr("SELECT u.{$sqlUserTableCols[username]} AS username,
  u.{$sqlUserTableCols[userid]} AS userid,
  u.{$sqlUserTableCols[usergroup]} AS displaygroupid,
  p.status,
  p.typing
  $cols
FROM {$sqlPrefix}ping AS p,
  {$sqlUserTable} AS u
{$join}
WHERE p.roomid IN ($room[id])
  AND p.userid = u.{$sqlUserTableCols[userid]}
  AND UNIX_TIMESTAMP(p.time) >= (UNIX_TIMESTAMP(NOW()) - $onlineThreshold)
ORDER BY u.{$sqlUserTableCols[username]}
LIMIT 500",'userid');

  if ($ausers) {
    foreach ($ausers AS $auser) {
        switch ($loginMethod) {
          case 'vbulletin':
          $auser['opentag'] = vrim_encodeXML($auser['opentag']);
          $auser['closetag'] = vrim_encodeXML($auser['closetag']);
          break;
          case 'phpbb':
          $auser['opentag'] = vrim_encodeXML("<span style=\"color: #$auser[user_colour]\">");
          $auser['closetag'] = vrim_encodeXML("</span>");
          break;
        }

        $ausersXML .= "      <user>
        <username>$auser[username]</username>
        <userid>$auser[userid]</userid>
        <displaygroupid>$auser[displaygroupid]</displaygroupid>
        <startTag>$auser[opentag]</startTag>
        <endTag>$auser[closetag]</endTag>
      </user>
";
      }
    }
}
      }
    }
  }
}



///* Process Watch Rooms *///
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




///* Output *///
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

  <watchRooms>
    $watchRoomsXML
  </watchRooms>

  <activeUsers>
    $ausersXML
  </activeUsers>
</getMessages>";


if ($_GET['gz']) {
 echo gzcompress($data);
}
else {
  echo $data;
}

mysqlClose();
?>