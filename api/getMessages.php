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

$newestMessage = (int) $_GET['messageIdMax']; // INT
$oldestMessage = (int) $_GET['messageIdMin']; // INT

$newestDate = (int) $_GET['messageDateMax']; // INT
$oldestDate = (int) $_GET['messageDateMin']; // INT

$messageStart = (int) $_GET['messageIdStart']; // INT

$watchRooms = (bool) $_GET['watchRooms']; // BOOL
$activeUsers = (bool) $_GET['activeUsers']; // BOOL
$archive = (bool) $_GET['archive']; // BOOL
$noPing = (bool) $_GET['noping']; // BOOL

$encode = ($_GET['encode']); // String - 'base64', 'plaintext'
$fields = ($_GET['messageFields']); // String - 'api', 'html', or 'both'


$onlineThreshold = (int) ($_GET['onlineThreshold'] ? $_GET['onlineThreshold'] : $onlineThreshold); // INT - Only if activeUsers = TRUE


if ($_GET['messageLimit'] == '0') {
  $messageLimit = 500; // Sane maximum.
}
else {
  $messageLimit = (int) ($_GET['messageLimit'] ? $_GET['messageLimit'] : ($messageLimit ? $messageLimit : 40));
  if ($messageLimit > 500) $messageLimit = 500; // Sane maximum.
}

$xmlData = array(
  'getMessages' => array(
    'activeUser' => array(
      'userId' => $user['userId'],
      'userName' => fim_encodeXml($user['userName']),
    ),
    'sentData' => array(
      'rooms' => $rooms,
      'roomsList' => $roomsXML,
      'newestMessage' => $newestMessage,
      'oldestMessage' => $oldestMessage,
      'newestDate' => $newestDate,
      'oldestDate' => $oldestDate,
      'messageLimit' => $messageLimit,
    ),
    'errorcode' => $failCode,
    'errormessage' => $failMessage,
    'messages' => array(),
    'watchRooms' => array(),
    'activeUsers' => array(),
  ),
);



///* Query Filter Generation *///

if ($newestMessage) {
  $whereClause .= "AND messageId < $newestMessage ";
}
if ($oldestMessage) {
  $whereClause .= "AND messageId > $oldestMessage ";
}
if ($newestDate) {
  $whereClause .= "AND UNIX_TIMESTAMP(m.time) < $newestDate ";
}
if ($oldestDate) {
  $whereClause .= "AND UNIX_TIMESTAMP(m.time) > $oldestDate ";
}
if (!$whereClause && $messageStart) {
  $whereClause .= "AND messageId > $messageStart AND messageId < " . ($messageStart + $messageLimit);
}

if ($archive) {
  if ($loginMethod == 'vbulletin') {
    $tableClause .= "{$sqlUserGroupTable} AS g";
    $whereClause .= "u.{$sqlUserTableCols[userGroup]} = g.{$sqlUserGroupTableCols[groupid]}";
  }
  elseif ($loginMethod == 'phpbb') {
    $colClause .= ', u.user_colour';
  }
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
      if (!fim_hasPermission($room,$user)) { } // Gotta make sure the user can view that room.
      else {

        if (!$noPing) {
          mysqlQuery("INSERT INTO {$sqlPrefix}ping (userId,roomId,time) VALUES ($user[userId],$room[id],CURRENT_TIMESTAMP()) ON DUPLICATE KEY UPDATE time = CURRENT_TIMESTAMP()");
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
          $messageQuery = "SELECT m.id AS messageId,
  UNIX_TIMESTAMP(m.time) AS time,
  $messageFields
  m.iv AS iv,
  m.salt AS salt,
  u.{$sqlUserTableCols[userId]} AS userId,
  u.{$sqlUserTableCols[userName]} AS userName,
  u.{$sqlUserTableCols[userGroup]} AS displaygroupid,
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
  AND m.user = u.{$sqlUserTableCols[userId]}
  AND m.user = u2.userId
$whereClause
ORDER BY messageId $order
LIMIT $messageLimit";
        }
        else {
          $messageQuery = "SELECT m.messageId AS messageId,
  UNIX_TIMESTAMP(m.time) AS time,
  $messageFields
  m.userId AS userId,
  m.userName AS userName,
  m.userGroup AS displaygroupid,
  m.groupFormatStart AS groupFormatStart,
  m.groupFormatEnd AS groupFormatEnd,
  m.flag AS flag,
  u2.settings AS usersettings,
  u2.defaultColour AS defaultColour,
  u2.defaultFontface AS defaultFontface,
  u2.defaultHighlight AS defaultHighlight,
  u2.defaultFormatting AS defaultFormatting
  $colClause
FROM {$sqlPrefix}messagesCached AS m,
  {$sqlPrefix}users AS u2
WHERE m.roomId = $room[id]
  AND m.userId = u2.userId
$whereClause
ORDER BY messageId $order
LIMIT $messageLimit";
        }

        if ($longPolling) {
          while (!$messages) {
            $messages = sqlArr($messageQuery,'messageId');
            sleep($longPollingWait);
          }
        }
        else {
          $messages = sqlArr($messageQuery,'messageId');
        }

        if ($messages) {
          foreach ($messages AS $id => $message) {
            $message = fim_decrypt($message);

            $message['userName'] = addslashes($message['userName']);
            $message['apiText'] = fim_encodeXml($message['apiText']);
            $message['htmlText'] = fim_encodeXml($message['htmlText']);

            if ($loginMethod == 'phpbb' && $archive) {
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
      <roomData>
        <roomId>$room[id]</roomId>
        <roomName>$room[name]</roomName>
        <roomTopic>" . fim_encodeXml($room['title']) . "</roomTopic>
      </roomData>
      <messageData>
        <messageId>$message[messageId]</messageId>
        <messageTime>$message[time]</messageTime>
        <messageTimeFormatted>" . fim_date(false,$message['time']) . "</messageTimeFormatted>
        <messageText>
          <appText>$message[apiText]</appText>
          <htmlText>$message[htmlText]</htmlText>
        </messageText>
        <flags>$message[flag]</flags>
      </messageData>
      <userData>
        <userName>$message[userName]</userName>
        <userId>$message[userId]</userId>
        <userGroup>$message[displaygroupid]</userGroup>
        <startTag>" . fim_encodeXml($message['groupFormatStart']) . "</startTag>
        <endTag>" . fim_encodeXml($message['groupFormatEnd']) . "</endTag>
        <defaultFormatting>
          <color>$message[defaultColour]</color>
          <highlight>$message[defaultHighlight]</highlight>
          <fontface>$message[defaultFontface]</fontface>
          <general>$message[defaultFormatting]</general>
        </defaultFormatting>
      </userData>
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

          $ausers = sqlArr("SELECT u.{$sqlUserTableCols[userName]} AS userName,
  u.{$sqlUserTableCols[userId]} AS userId,
  u.{$sqlUserTableCols[userGroup]} AS displaygroupid,
  p.status,
  p.typing
  $cols
FROM {$sqlPrefix}ping AS p,
  {$sqlUserTable} AS u
{$join}
WHERE p.roomId IN ($room[id])
  AND p.userId = u.{$sqlUserTableCols[userId]}
  AND UNIX_TIMESTAMP(p.time) >= (UNIX_TIMESTAMP(NOW()) - $onlineThreshold)
ORDER BY u.{$sqlUserTableCols[userName]}
LIMIT 500",'userId');

  if ($ausers) {
    foreach ($ausers AS $auser) {
        switch ($loginMethod) {
          case 'vbulletin':
          $auser['opentag'] = fim_encodeXml($auser['opentag']);
          $auser['closetag'] = fim_encodeXml($auser['closetag']);
          break;
          case 'phpbb':
          $auser['opentag'] = fim_encodeXml("<span style=\"color: #$auser[user_colour]\">");
          $auser['closetag'] = fim_encodeXml("</span>");
          break;
        }

        $ausersXML .= "      <user>
        <userName>$auser[userName]</userName>
        <userId>$auser[userId]</userId>
        <userGroup>$auser[displaygroupid]</userGroup>
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
  $missedMessages = sqlArr("SELECT r.*, UNIX_TIMESTAMP(r.lastMessageTime) AS lastMessageTimestamp FROM {$sqlPrefix}rooms AS r LEFT JOIN {$sqlPrefix}ping AS p ON (p.userId = $user[userId] AND p.roomId = r.id) WHERE (r.options & 16 " . ($user['watchRooms'] ? " OR r.id IN ($user[watchRooms])" : '') . ") AND (r.allowedUsers REGEXP '({$user[userId]},)|{$user[userId]}$' OR r.allowedUsers = '*') AND IF(p.time, UNIX_TIMESTAMP(r.lastMessageTime) > (UNIX_TIMESTAMP(p.time) + 10), TRUE)",'id'); // Right now only private IMs are included, but in the future this will be expanded.

  if ($missedMessages) {
    foreach ($missedMessages AS $message) {
      if (!fim_hasPermission($message,$user,'view')) { continue; }

      $roomName = fim_encodeXml($message['name']);
      $watchRoomsXML .= "    <room>
      <roomId>$message[id]</roomId>
      <roomName>$roomName</roomName>
      <lastMessageTime>$message[lastMessageTimestamp]</lastMessageTime>
    </room>";
    }
  }
}




///* Output *///
echo "
<getMessages>
  <activeUser>
    <userId>$user[userId]</userId>
    <userName>" . fim_encodeXml($user['userName']) . "</userName>
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


//fim_outputXml($xmlData);

mysqlClose();
?>