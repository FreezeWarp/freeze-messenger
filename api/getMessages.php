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
$messageEnd = (int) $_GET['messageIdEnd']; // INT

$watchRooms = (bool) $_GET['watchRooms']; // BOOL
$activeUsers = (bool) $_GET['activeUsers']; // BOOL
$archive = (bool) $_GET['archive']; // BOOL
$noPing = (bool) $_GET['noping']; // BOOL

$encode = ($_GET['encode']); // String - 'base64', 'plaintext'
$fields = ($_GET['messageFields']); // String - 'api', 'html', or 'both'

if ($longPolling && $_GET['longPolling']) {
  $longPolling = true;
}
else {
  $longPolling = false;
}


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
if (!$whereClause && $messageEnd) {
  $whereClause .= "AND messageId < $messageEnd AND messageId > " . ($messageEnd - $messageLimit);
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
    $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE roomId = $room2");

    if ($room) {
      if (!fim_hasPermission($room,$user)) { // Gotta make sure the user can view that room.
        // Do nothing
      }
      else {

        if (!$noPing) {
          mysqlQuery("INSERT INTO {$sqlPrefix}ping
            (userId,
            roomId,
            time)
          VALUES
            ($user[userId],
            $room[roomId],
            CURRENT_TIMESTAMP())
          ON DUPLICATE KEY
            UPDATE time = CURRENT_TIMESTAMP()");
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
          $messageQuery = "SELECT m.messageId,
            UNIX_TIMESTAMP(m.time) AS time,
            $messageFields
            m.iv AS iv,
            m.salt AS salt,
            u.userId AS userId,
            u.userName AS userName,
            u.userGroup AS userGroup,
            u.userFormatStart,
            u.userFormatEnd,
            u.defaultColour AS defaultColour,
            u.defaultFontface AS defaultFontface,
            u.defaultHighlight AS defaultHighlight,
            u.defaultFormatting AS defaultFormatting
          FROM {$sqlPrefix}messages AS m,
            {$sqlPrefix}users AS u
          WHERE m.roomId = $room[roomId]
            AND m.deleted != true
            AND m.userId = u.userId
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
            m.userGroup AS userGroup,
            m.userFormatStart AS userFormatStart,
            m.userFormatEnd AS userFormatEnd,
            m.flag AS flag,
            m.defaultColour AS defaultColour,
            m.defaultFontface AS defaultFontface,
            m.defaultHighlight AS defaultHighlight,
            m.defaultFormatting AS defaultFormatting
          FROM {$sqlPrefix}messagesCached AS m
          WHERE m.roomId = $room[roomId]
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

            switch ($encode) {
              case 'base64':
              $message['apiText'] = base64_encode($message['apiText']);
              $message['htmlText'] = base64_encode($message['htmlText']);
              break;
            }

            $xmlData['getMessages']['messages']['message ' . $message['messageId']] = array(
              'roomData' => array(
                'roomId' => (int) $room['roomId'],
                'roomName' => fim_encodeXml($room['name']),
                'roomTopic' => fim_encodeXml($room['title']),
              ),
              'messageData' => array(
                'messageId' => (int) $message['messageId'],
                'messageTime' => (int) $message['time'],
                'messageTimeFormatted' => fim_date(false,$message['time']),
                'messageText' => array(
                  'apiText' => fim_encodeXml($message['apiText']),
                  'htmlText' => fim_encodeXml($message['htmlText']),
                ),
                'flags' => fim_encodeXml($message['flag']),
              ),
              'userData' => array(
                'userName' => fim_encodeXml($message['userName']),
                'userId' => (int) $message['userId'],
                'userGroup' => (int) $message['userGroup'],
                'socialGroups' => fim_encodeXml($message['socialGroups']),
                'startTag' => fim_encodeXml($message['userFormatStart']),
                'endTag' => fim_encodeXml($message['userFormatEnd']),
                'defaultFormatting' => array(
                  'color' => fim_encodeXml($message['defaultColour']),
                  'highlight' => fim_encodeXml($message['defaultHighlight']),
                  'fontface' => fim_encodeXml($message['defaultFontface']),
                  'general' => (int) $message['defaultGeneral']
                 ),
              ),
            );
          }
        }

        ///* Process Active Users
        if ($activeUsers) {
          $ausers = sqlArr("SELECT u.{$sqlUserTableCols[userName]} AS userName,
            u.userId AS userId,
            u.userGroup AS userGroup,
            u.userFormatStart AS userFormatStart,
            u.userFormatEnd AS userFormatEnd,
            p.status,
            p.typing
            $cols
          FROM {$sqlPrefix}ping AS p,
            {$sqlPrefix}users AS u
          {$join}
          WHERE p.roomId IN ($room[roomId])
            AND p.userId = u.userId
            AND UNIX_TIMESTAMP(p.time) >= (UNIX_TIMESTAMP(NOW()) - $onlineThreshold)
          ORDER BY u.userName
          LIMIT 500",'userId');

          if ($ausers) {
            foreach ($ausers AS $auser) {
              $xmlData['getMessages']['activeUsers']['user ' . $auser['userId']] = array(
                'userId' => (int) $auser['userId'],
                'userName' => fim_encodeXml($auser['userName']),
                'userGroup' => (int) $auser['userGroup'],
                'socialGroups' => fim_encodeXml($auser['socialGroups']),
                'startTag' => fim_encodeXml($auser['userFormatStart']),
                'endTag' => fim_encodeXml($auser['userFormatEnd']),
              );
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
  $missedMessages = sqlArr("SELECT r.*,
  UNIX_TIMESTAMP(r.lastMessageTime) AS lastMessageTimestamp
FROM {$sqlPrefix}rooms AS r
  LEFT JOIN {$sqlPrefix}ping AS p ON (p.userId = $user[userId] AND p.roomId = r.roomId)
WHERE (r.options & 16 " . ($user['watchRooms'] ? " OR r.roomId IN ($user[watchRooms])" : '') . ") AND (r.allowedUsers REGEXP '({$user[userId]},)|{$user[userId]}$' OR r.allowedUsers = '*') AND IF(p.time, UNIX_TIMESTAMP(r.lastMessageTime) > (UNIX_TIMESTAMP(p.time) + 10), TRUE)",'id'); // Right now only private IMs are included, but in the future this will be expanded.

  if ($missedMessages) {
    foreach ($missedMessages AS $message) {
      if (!fim_hasPermission($message,$user,'view')) {
        continue;
      }

      $xmlData['getMessages']['watchRooms']['room ' . $message['roomId']] = array(
        'roomId' => (int) $message['roomId'],
        'roomName' => fim_encodeXml($message['roomName']),
        'lastMessageTime' => (int) $message['lastMessageTimestamp'],
      );
    }
  }
}




///* Output *///

echo fim_outputXml($xmlData);

mysqlClose();
?>