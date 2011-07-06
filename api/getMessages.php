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



/* Get Request Data */
$request = fim_sanitizeGPC(array(
  'get' => array(
    'rooms' => array(
      'type' => 'string',
      'require' => false,
      'default' => '',
      'context' => array(
         'type' => 'csv',
         'filter' => 'int',
         'evaltrue' => true,
      ),
    ),

    'sort' => array(
      'type' => 'string',
      'valid' => array(
        'roomId',
        'roomName',
        'smart',
      ),
      'require' => false,
      'default' => 'roomId',
    ),

    'showDeleted' => array(
      'type' => 'string',
      'require' => false,
      'default' => false,
      'context' => array(
        'type' => 'bool',
      ),
    ),

    'watchRooms' => array(
      'type' => 'string',
      'require' => false,
      'default' => false,
      'context' => array(
        'type' => 'bool',
      ),
    ),

    'activeUsers' => array(
      'type' => 'string',
      'require' => false,
      'default' => false,
      'context' => array(
        'type' => 'bool',
      ),
    ),

    'archive' => array(
      'type' => 'string',
      'require' => false,
      'default' => false,
      'context' => array(
        'type' => 'bool',
      ),
    ),

    'noping' => array(
      'type' => 'string',
      'require' => false,
      'default' => false,
      'context' => array(
        'type' => 'bool',
      ),
    ),

    'longPolling' => array(
      'type' => 'string',
      'require' => false,
      'default' => false,
      'context' => array(
        'type' => 'bool',
      ),
    ),

    'messageIdMax' => array(
      'type' => 'string',
      'require' => false,
      'default' => false,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'messageIdMin' => array(
      'type' => 'string',
      'require' => false,
      'default' => false,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'messageDateMax' => array(
      'type' => 'string',
      'require' => false,
      'default' => false,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'messageDateMin' => array(
      'type' => 'string',
      'require' => false,
      'default' => false,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'messageIdStart' => array(
      'type' => 'string',
      'require' => false,
      'default' => false,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'messageIdEnd' => array(
      'type' => 'string',
      'require' => false,
      'default' => false,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'onlineThreshold' => array(
      'type' => 'int',
      'require' => false,
      'default' => ($onlineThreshold ? $onlineThreshold : 15),
      'context' => array(
        'type' => 'int',
      ),
    ),

    'messageLimit' => array(
      'type' => 'int',
      'require' => false,
      'default' => ($messageLimit ? $messageLimit : 50),
      'context' => array(
        'type' => 'int',
      ),
    ),

    'search' => array(
      'type' => 'string',
      'require' => false,
      'default' => false,
    ),

    'encode' => array(
      'type' => 'string',
      'require' => false,
      'default' => 'plaintext',
    ),

    'fields' => array(
      'type' => 'string',
      'require' => false,
      'default' => 'both',
    ),
  ),
));


if ($longPolling && $request['longPolling']) {
  $longPolling = true;

  set_time_limit(0);
  ini_set('max_execution_time',0);
}
else {
  $longPolling = false;
}


if ($request['messageLimit'] > 500) {
  $request['messageLimit'] = 500; // Sane maximum.
}



/* Data Predefine */
$xmlData = array(
  'getMessages' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => $errStr,
    'errDesc' => $errDesc,
    'messages' => array(),
    'watchRooms' => array(),
    'activeUsers' => array(),
  ),
);

if ($request['archive']) {
  $queryParts['messagesSelect']['columns'] = array(
    "{$sqlPrefix}messages" => array(
      'messageId' => 'messageId',
      'time' => array(
        'context' => 'time',
        'name' => 'time',
      ),
      'iv' => 'iv',
      'salt' => 'salt',
    ),
    "{$sqlPrefix}users" => array(
      'userId' => 'userId',
      'userName' => 'userName',
      'userGroup' => 'userGroup',
      'userFormatStart' => 'userFormatStart',
      'userFormatEnd' => 'userFormatEnd',
      'avatar' => 'avatar',
      'defaultColor' => 'defaultColor',
      'defaultFontface' => 'defaultFontface',
      'defaultHighlight' => 'defaultHighlight',
      'defaultFormatting' => 'defaultFormatting',
    ),
  );
  $queryParts['messagesSelect']['conditions'] = array(
    'both' => array(
      array(
        'type' => 'e',
        'left' => array(
          'type' => 'column',
          'value' => 'roomId',
        ),
        'right' => array(
          'type' => 'int',
          'value' => (int) $request['roomId'],
        ),
      ),
      array(
        'type' => 'e',
        'left' => array(
          'type' => 'column',
          'value' => 'muserId',
        ),
        'right' => array(
          'type' => 'column',
          'value' => 'userId',
        ),
      ),
    ),
  );
  $queryParts['messagesSelect']['order'] = array(
    'messageId' => 'asc',
  );
}
else {
  $queryParts['messagesSelect']['columns'] = array(
    "{$sqlPrefix}messages" => array(
      'messageId' => 'messageId',
      'time' => array(
        'context' => 'time',
        'name' => 'time',
      ),
      'iv' => 'iv',
      'salt' => 'salt',
      'flag' => 'flag',
      'userId' => 'userId',
      'userName' => 'userName',
      'userGroup' => 'userGroup',
      'userFormatStart' => 'userFormatStart',
      'userFormatEnd' => 'userFormatEnd',
      'avatar' => 'avatar',
      'defaultColor' => 'defaultColor',
      'defaultFontface' => 'defaultFontface',
      'defaultHighlight' => 'defaultHighlight',
      'defaultFormatting' => 'defaultFormatting',
    ),
  );
  $queryParts['messagesSelect']['conditions'] = array(
    'both' => array(
      array(
        'type' => 'e',
        'left' => array(
          'type' => 'column',
          'value' => 'roomId',
        ),
        'right' => array(
          'type' => 'int',
          'value' => (int) $request['roomId'],
        ),
      ),
    ),
  );
  $queryParts['messagesSelect']['order'] = array(
    'messageId' => 'asc',
  );
}



/* Modify Query Data for Directives */
if ($newestMessage) {
  $queryParts['messagesSelect']['conditions']['both'][] = array(
    'type' => 'lt',
    'left' => array(
      'type' => 'column',
      'value' => 'messageId',
    ),
    'right' => array(
      'type' => 'int',
      'value' => (int) $request['newestMessage'],
    ),
  );
}
if ($oldestMessage) {
  $queryParts['messagesSelect']['conditions']['both'][] = array(
    'type' => 'gt',
    'left' => array(
      'type' => 'column',
      'value' => 'messageId',
    ),
    'right' => array(
      'type' => 'int',
      'value' => (int) $request['oldestMessage'],
    ),
  );
}

if ($newestDate) {
  $queryParts['messagesSelect']['conditions']['both'][] = array(
    'type' => 'lt',
    'left' => array(
      'type' => 'column',
      'context' => 'time'
      'value' => 'time',
    ),
    'right' => array(
      'type' => 'int',
      'value' => (int) $request['newestDate'],
    ),
  );
}
if ($oldestDate) {
  $queryParts['messagesSelect']['conditions']['both'][] = array(
    'type' => 'gt',
    'left' => array(
      'type' => 'column',
      'context' => 'time'
      'value' => 'time',
    ),
    'right' => array(
      'type' => 'int',
      'value' => (int) $request['oldestDate'],
    ),
  );
}

if (!$whereClause && $messageStart) {
  $queryParts['messagesSelect']['conditions']['both'][] = array(
    'type' => 'gt',
    'left' => array(
      'type' => 'column',
      'value' => 'messageId',
    ),
    'right' => array(
      'type' => 'int',
      'value' => (int) $request['messageStart'],
    ),
  );
  $queryParts['messagesSelect']['conditions']['both'][] = array(
    'type' => 'lt',
    'left' => array(
      'type' => 'column',
      'value' => 'messageId',
    ),
    'right' => array(
      'type' => 'int',
      'value' => (int) ($request['messageStart'] + $request['messageLimit']),
    ),
  );
}
if (!$whereClause && $messageEnd) {
  $queryParts['messagesSelect']['conditions']['both'][] = array(
    'type' => 'lt',
    'left' => array(
      'type' => 'column',
      'value' => 'messageId',
    ),
    'right' => array(
      'type' => 'int',
      'value' => (int) $request['messageEnd'],
    ),
  );
  $queryParts['messagesSelect']['conditions']['both'][] = array(
    'type' => 'gt',
    'left' => array(
      'type' => 'column',
      'value' => 'messageId',
    ),
    'right' => array(
      'type' => 'int',
      'value' => (int) ($request['messageEnd'] - $request['messageLimit']),
    ),
  );
}

if (!$showDeleted && $archive) {
  $queryParts['messagesSelect']['conditions']['both'][] = array(
    'type' => 'lt',
    'left' => array(
      'type' => 'column',
      'value' => 'deleted',
    ),
    'right' => array(
      'type' => 'bool',
      'value' => false,
    ),
  );
}



/* Plugin Hook Start */
($hook = hook('getMessages_start') ? eval($hook) : '');







/* Start Crazy Shit */
if (is_array($request['rooms'])) {
  if (count($request['rooms']) > 0) {
    foreach ($request['rooms'] AS $roomId) { // We will run through each room.
      /* Get Room Data
       * TODO: Run through this in a single query. */
      $room = $database->select(
        array(
          "{$sqlPrefix}rooms" => array(
            'roomId' => 'roomId',
            'roomName' => 'roomName',
            'roomTopic' => 'roomTopic',
            'owner' => 'owner',
            'allowedUsers' => 'allowedUsers',
            'allowedGroups' => 'allowedGroups',
            'moderators' => 'moderators',
          ),
        ),
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'roomId',
          ),
          'right' => array(
            'type' => 'int',
            'value' => (int) $roomId,
          ),
        ),
        false,
        false,
        1
      );
      $room->getAsArray();

      if ($room) {
        /* Make sure the user has permission to view posts from the room */
        $permission = fim_hasPermission($room,$user,'view',false);
        if (!$permission[0]) { // No Permission
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
        else { // Has Permission
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
              $searchArray2[] = '"' . dbEscape(str_replace(array_keys($searchWordConverts),array_values($searchWordConverts),$searchVal)) . '"';
            }

            $search2 = implode(',',$searchArray2);

            $searchMessageIds = dbRows("SELECT GROUP_CONCAT(messageId SEPARATOR ',') AS messages
            FROM {$sqlPrefix}searchPhrases AS p,
            {$sqlPrefix}searchMessages AS m
            WHERE p.phraseId = m.phraseId AND p.phraseName IN ($search2)");

            $whereClause .= " AND messageId IN ($searchMessageIds[messages]) ";
          }





          /* Get Messages from Database */
          if ($longPolling) {
            ($hook = hook('getMessages_postMessages_longPolling') ? eval($hook) : '');

            while (!$messages) {
              $messages = $database->select($queryParts['messagesSelect']['columns'],
                $queryParts['messagesSelect']['conditions'],
                $queryParts['messagesSelect']['columns']);
              $messages->getAsArray('messageId');

              sleep($longPollingWait);
            }
          }
          else {
            ($hook = hook('getMessages_postMessages_polling') ? eval($hook) : '');

              $messages = $database->select($queryParts['messagesSelect']['columns'],
                $queryParts['messagesSelect']['conditions'],
                $queryParts['messagesSelect']['columns']);
              $messages->getAsArray('messageId');
          }

          /* Process Messages */
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
}



///* Process Watch Rooms *///
if ($watchRooms) {
  ($hook = hook('getMessages_watchRooms_start') ? eval($hook) : '');

  /* Get Missed Messages */
  $missedMessages = dbRows("SELECT r.*,
  UNIX_TIMESTAMP(r.lastMessageTime) AS lastMessageTimestamp
FROM {$sqlPrefix}rooms AS r
  LEFT JOIN {$sqlPrefix}ping AS p ON (p.userId = $user[userId] AND p.roomId = r.roomId)
WHERE (r.options & 16 " . ($user['watchRooms'] ? " OR r.roomId IN ($user[watchRooms])" : '') . ") AND (r.allowedUsers REGEXP '({$user[userId]},)|{$user[userId]}$' OR r.allowedUsers = '*') AND IF(p.time, UNIX_TIMESTAMP(r.lastMessageTime) > (UNIX_TIMESTAMP(p.time) + 10), TRUE)",'id');


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




/* Update Data for Errors */
$xmlData['getMessages']['errStr'] = (string) $errStr;
$xmlData['getMessages']['errDesc'] = (string) $errDesc;



/* Plugin Hook End */
($hook = hook('getMessages_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);



/* Close Database Connection */
dbClose();
?>