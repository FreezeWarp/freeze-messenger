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

/**
 * Get Messages from the Server
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
 *
 * @param string rooms - A comma seperated list of rooms. Can be a single room in integer format. Some predefined constants can also be used.
 * Note: Using more than one room can conflict or even break the script’s execution should the watchRooms or activeUsers flags be set to true.
 * @param messageLimit - The maximum number of posts to receive, defaulting to the internal limit of (in most cases) 40. Specifying 0 removes any limit.
 * Note: A hardcoded maximum of 500 is in place to prevent any potential issues. This will in the future be changable by the administrator.
 * @param timestamp messageDateMin - The earliest a post could have been made. Use of newestDate only makes sense with no messageLimit. Do not specify to prevent checking.
 * @param timestamp messageDateMax - The latest a post could have been made. Use of newestDate only makes sense with no messageLimit. Do not specify to prevent checking.
 * @param int messageIdMin - All posts must be after this ID. Use of newestMessage only makes sense with no messageLimit. Do not specify to prevent checking.
 * @param int messageIdMax - All posts must be before this ID. Use of newestMessage only makes sense with no messageLimit. Do not specify to prevent checking.
 * @param int messageIdStart - When specified WITHOUT the above two directives, messageIdStart will return all posts from this ID to this ID plus the messageLimit directive. This is strongly encouraged for all requests to the cache, e.g. for normal instant messenging sessions.
 * @param bool noping - Disables ping; useful for archive viewing.
 * @param bool watchRooms - Get unread messages from a user’s list of watchRooms (also applies to private IMs).
 * @param bool activeUsers - Returns a list of activeUsers in the room(s) if specified. This is identical to calling the getActiveUsers script, except with less data redundancy.
*/


$apiRequest = true;
require_once('../global.php');


$longPollingWait = .25;

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

$search = $_GET['search']; // STRING

$showDeleted = (bool) $_GET['showDeleted'];

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

if ($longPolling) {
  set_time_limit(0);
  ini_set('max_execution_time',0);
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
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'sentData' => array(
      'rooms' => ($rooms),
      'roomsList' => array(),
      'newestMessage' => (int) $newestMessage,
      'oldestMessage' => (int) $oldestMessage,
      'newestDate' => (int) $newestDate,
      'oldestDate' => (int) $oldestDate,
      'messageLimit' => (int) $messageLimit,
    ),
    'errStr' => $errStr,
    'errDesc' => $errDesc,
    'messages' => array(),
    'watchRooms' => array(),
    'activeUsers' => array(),
  ),
);


($hook = hook('getMessages_start') ? eval($hook) : '');



///* Query Filter Generation *///

if ($newestMessage) {
  $whereClause .= " AND messageId < $newestMessage ";
}
if ($oldestMessage) {
  $whereClause .= " AND messageId > $oldestMessage ";
}
if ($newestDate) {
  $whereClause .= " AND UNIX_TIMESTAMP(m.time) < $newestDate ";
}
if ($oldestDate) {
  $whereClause .= " AND UNIX_TIMESTAMP(m.time) > $oldestDate ";
}
if (!$whereClause && $messageStart) {
  $whereClause .= " AND messageId > $messageStart AND messageId < " . ($messageStart + $messageLimit);
}
if (!$whereClause && $messageEnd) {
  $whereClause .= " AND messageId < $messageEnd AND messageId > " . ($messageEnd - $messageLimit);
}

if (!$showDeleted && $archive) {
  $whereClause .= " AND m.deleted = FALSE";
}


///* Error Checking *///
if (!$rooms) {
  $errStr = 'badroomsrequest';
  $errDesc = 'The room string was not supplied or evaluated to false.';
}
if (!$roomsArray) {
  $errStr = 'badroomsrequest';
  $errDesc = 'The room string was not formatted properly in Comma-Seperated notation.';
}
else {
  foreach ($roomsArray AS $room2) {
    $room2 = intval($room2);
    $room = dbRows("SELECT * FROM {$sqlPrefix}rooms WHERE roomId = $room2");

    if ($room) {
      $permission = fim_hasPermission($room,$user,'view',false);
      if (!$permission[0]) { // Gotta make sure the user can view that room.
        ($hook = hook('getMessages_noPerm') ? eval($hook) : '');

        switch($permission[1]) {
          case 'kick':
          $errStr = 'kicked';
          $errDesc = 'You have been kicked untl ' . fim_date($permission[3]) . '.';
          break;

          default:
          $errStr = 'noperm';
          $errDesc = 'You do not have permission to view the room you are trying to view.';
          break;
        }
      }
      else {

        if (!$noPing) {
          ($hook = hook('getMessages_ping_start') ? eval($hook) : '');

          dbInsert(array(
            'userId' => $user['userId'],
            'roomId' => $room['roomId'],
            'time' => array(
              'type' => 'raw',
              'value' => 'CURRENT_TIMESTAMP()',
            ),
          ),"{$sqlPrefix}ping",array(
            'time' => array(
              'type' => 'raw',
              'value' => 'CURRENT_TIMESTAMP()',
            )
          ));

          ($hook = hook('getMessages_ping_end') ? eval($hook) : '');
        }

        switch ($fields) {
          case 'both': case '': $messageFields = 'm.apiText AS apiText, m.htmlText AS htmlText,'; break;
          case 'api': $messageFields = 'm.apiText AS apiText,'; break;
          case 'html': $messageFields = 'm.htmlText AS htmlText,'; break;
          default:
            $errStr = 'badFields';
            $errDesc = 'The given message fields are invalid - recognized values are "api", "html", and "both"';
          break;
        }

        ($hook = hook('getMessages_preMessages') ? eval($hook) : '');

        if ($search) {
          $searchArray = explode(',',$search);

          foreach ($searchArray AS $searchVal) {
            $searchArray2[] = '"' . dbEscape( str_replace(array_keys($searchWordConverts),array_values($searchWordConverts),$searchVal)) . '"';
          }

          $search2 = implode(',',$searchArray2);

          $searchMessageIds = dbRows("SELECT GROUP_CONCAT(messageId SEPARATOR ',') AS messages
          FROM {$sqlPrefix}searchPhrases AS p,
          {$sqlPrefix}searchMessages AS m
          WHERE p.phraseId = m.phraseId AND p.phraseName IN ($search2)");

          $whereClause .= " AND messageId IN ($searchMessageIds[messages]) ";
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
            u.avatar,
            u.defaultColor AS defaultColor,
            u.defaultFontface AS defaultFontface,
            u.defaultHighlight AS defaultHighlight,
            u.defaultFormatting AS defaultFormatting
            {$messagesArchive_columns}
          FROM {$sqlPrefix}messages AS m,
            {$sqlPrefix}users AS u
            {$messagesArchive_tables}
          WHERE m.roomId = $room[roomId]
            AND m.userId = u.userId
          $whereClause
          {$messagesArchive_where}
          ORDER BY messageId
            {$messagesArchive_order}
          LIMIT $messageLimit
          {$messagesArchive_end}";
        }
        else {
          $messageQuery = "SELECT m.messageId AS messageId,
            UNIX_TIMESTAMP(m.time) AS time,
            $messageFields
            m.userId AS userId,
            m.avatar AS avatar,
            m.userName AS userName,
            m.userGroup AS userGroup,
            m.userFormatStart AS userFormatStart,
            m.userFormatEnd AS userFormatEnd,
            m.flag AS flag,
            m.defaultColor AS defaultColor,
            m.defaultFontface AS defaultFontface,
            m.defaultHighlight AS defaultHighlight,
            m.defaultFormatting AS defaultFormatting
            {$messagesCached_columns}
          FROM {$sqlPrefix}messagesCached AS m
            {$messagesCached_tables}
          WHERE m.roomId = $room[roomId]
            $whereClause
            {$messagesCached_where}
          ORDER BY messageId
            {$messagesCached_order}
          LIMIT $messageLimit
          {$messagesCached_end}";
        }

        if ($longPolling) {
          ($hook = hook('getMessages_postMessages_longPolling') ? eval($hook) : '');

          while (!$messages) {
            $messages = dbRows($messageQuery,'messageId');
            sleep($longPollingWait);
          }
        }
        else {
          ($hook = hook('getMessages_postMessages_polling') ? eval($hook) : '');

          $messages = dbRows($messageQuery,'messageId');
        }

        if ($messages) {
          foreach ($messages AS $id => $message) {
            ($hook = hook('getMessages_eachMessage_start') ? eval($hook) : '');

            $message = fim_decrypt($message);

            $message['userName'] = addslashes($message['userName']);
            $message['apiText'] = ($message['apiText']);
            $message['htmlText'] = ($message['htmlText']);

            switch ($encode) {
              case 'base64':
              $message['apiText'] = base64_encode($message['apiText']);
              $message['htmlText'] = base64_encode($message['htmlText']);
              break;
            }

            $xmlData['getMessages']['messages']['message ' . (int) $message['messageId']] = array(
              'roomData' => array(
                'roomId' => (int) $room['roomId'],
                'roomName' => ($room['roomName']),
                'roomTopic' => ($room['roomTopic']),
              ),
              'messageData' => array(
                'messageId' => (int) $message['messageId'],
                'messageTime' => (int) $message['time'],
                'messageTimeFormatted' => fim_date(false,$message['time']),
                'messageText' => array(
                  'apiText' => ($message['apiText']),
                  'htmlText' => ($message['htmlText']),
                ),
                'flags' => ($message['flag']),
              ),
              'userData' => array(
                'userName' => ($message['userName']),
                'userId' => (int) $message['userId'],
                'userGroup' => (int) $message['userGroup'],
                'avatar' => ($message['avatar']),
                'socialGroups' => ($message['socialI forget if I watched Groups']),
                'startTag' => ($message['userFormatStart']),
                'endTag' => ($message['userFormatEnd']),
                'defaultFormatting' => array(
                  'color' => ($message['defaultColor']),
                  'highlight' => ($message['defaultHighlight']),
                  'fontface' => ($message['defaultFontface']),
                  'general' => (int) $message['defaultFormatting']
                 ),
              ),
            );

            ($hook = hook('getMessages_eachMessage_end') ? eval($hook) : '');
          }
        }

        ///* Process Active Users
        if ($activeUsers) {
          ($hook = hook('getMessages_activeUsers_start') ? eval($hook) : '');

          $activeUsers = dbRows("SELECT u.{$sqlUserTableCols[userName]} AS userName,
            u.userId AS userId,
            u.userGroup AS userGroup,
            u.userFormatStart AS userFormatStart,
            u.userFormatEnd AS userFormatEnd,
            p.status,
            p.typing
            {$activeUser_columns}
          FROM {$sqlPrefix}ping AS p,
            {$sqlPrefix}users AS u
            {$activeUser_tables}
          WHERE p.roomId IN ($room[roomId])
            AND p.userId = u.userId
            AND UNIX_TIMESTAMP(p.time) >= (UNIX_TIMESTAMP(NOW()) - $onlineThreshold)
            {$activeUser_where}
          ORDER BY u.userName
            {$activeUser_order}
          {$activeUser_end}",'userId');

          if ($activeUsers) {
            foreach ($activeUsers AS $activeUser) {
              $xmlData['getMessages']['activeUsers']['user ' . $activeUser['userId']] = array(
                'userId' => (int) $activeUser['userId'],
                'userName' => ($activeUser['userName']),
                'userGroup' => (int) $activeUser['userGroup'],
                'socialGroups' => ($activeUser['socialGroups']),
                'startTag' => ($activeUser['userFormatStart']),
                'endTag' => ($activeUser['userFormatEnd']),
              );

              ($hook = hook('getMessages_activeUsers_eachUser') ? eval($hook) : '');
            }
          }

          ($hook = hook('getMessages_activeUsers_end') ? eval($hook) : '');
        }
      }
    }
  }
}



///* Process Watch Rooms *///
if ($watchRooms) {
  ($hook = hook('getMessages_watchRooms_start') ? eval($hook) : '');

  /* Get Missed Messages */
  $missedMessages = dbRows("SELECT r.*,
  UNIX_TIMESTAMP(r.lastMessageTime) AS lastMessageTimestamp
FROM {$sqlPrefix}rooms AS r
  LEFT JOIN {$sqlPrefix}ping AS p ON (p.userId = $user[userId] AND p.roomId = r.roomId)
WHERE (r.options & 16 " . ($user['watchRooms'] ? " OR r.roomId IN ($user[watchRooms])" : '') . ") AND (r.allowedUsers REGEXP '({$user[userId]},)|{$user[userId]}$' OR r.allowedUsers = '*') AND IF(p.time, UNIX_TIMESTAMP(r.lastMessageTime) > (UNIX_TIMESTAMP(p.time) + 10), TRUE)",'id'); // Right now only private IMs are included, but in the future this will be expanded.

  if ($missedMessages) {
    foreach ($missedMessages AS $message) {
      if (!fim_hasPermission($message,$user,'view',true)) {
        ($hook = hook('getMessages_watchRooms_noPerm') ? eval($hook) : '');

        continue;
      }

      $xmlData['getMessages']['watchRooms']['room ' . (int) $message['roomId']] = array(
        'roomId' => (int) $message['roomId'],
        'roomName' => ($message['roomName']),
        'lastMessageTime' => (int) $message['lastMessageTimestamp'],
      );

      ($hook = hook('getMessages_watchRooms_eachRoom') ? eval($hook) : '');
    }
  }

  ($hook = hook('getMessages_watchRooms_end') ? eval($hook) : '');
}




///* Output *///
$xmlData['getMessages']['errStr'] = ($errStr);
$xmlData['getMessages']['errDesc'] = ($errDesc);


($hook = hook('getMessages_end') ? eval($hook) : '');


echo fim_outputApi($xmlData);

dbClose();
?>