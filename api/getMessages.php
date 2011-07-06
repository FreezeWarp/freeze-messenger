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
 * @param timestamp messageDateMin - The earliest a post could have been made. Use of messageDateMax only makes sense with no messageLimit. Do not specify to prevent checking.
 * @param timestamp messageDateMax - The latest a post could have been made. Use of messageDateMax only makes sense with no messageLimit. Do not specify to prevent checking.
 * @param int messageIdMin - All posts must be after this ID. Use of messageDateMax only makes sense with no messageLimit. Do not specify to prevent checking.
 * @param int messageIdMax - All posts must be before this ID. Use of messageDateMax only makes sense with no messageLimit. Do not specify to prevent checking.
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
      'default' => (isset($onlineThreshold) ? $onlineThreshold : 15),
      'context' => array(
        'type' => 'int',
      ),
    ),

    'messageLimit' => array(
      'type' => 'int',
      'require' => false,
      'default' => (isset($messageLimit) ? $messageLimit : 50),
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
      'valid' => array(
        'both',
        'api',
        'html',
      ),
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

$queryParts['roomsSelect']['columns'] = array(
  "{$sqlPrefix}rooms" => array(
    'roomId' => 'roomId',
    'roomName' => 'roomName',
    'roomTopic' => 'roomTopic',
    'owner' => 'owner',
    'allowedUsers' => 'allowedUsers',
    'allowedGroups' => 'allowedGroups',
    'moderators' => 'moderators',
    'options' => 'options',
  ),
);
$queryParts['roomsSelect']['conditions'] = array(
  'both' => array(
    array(
      'type' => 'in',
      'left' => array(
        'type' => 'column',
        'value' => 'roomId'
      ),
      'right' => array(
        'type' => 'array',
        'value' => $request['rooms'],
      ),
    ),
  ),
);
$queryParts['roomsSelect']['sort'] = array(
  'roomId' => 'asc',
);



if ((strlen($request['search']) > 0) && $request['archive']) {
  $searchArray = explode(',',$search);

  foreach ($searchArray AS $searchVal) {
    $searchArray2[] = str_replace(
      array_keys($searchWordConverts),
      array_values($searchWordConverts),
      $searchVal
    );
  }

/*  $searchMessageIds = dbRows("SELECT GROUP_CONCAT(messageId SEPARATOR ',') AS messages
  FROM {$sqlPrefix}searchPhrases AS p,
  {$sqlPrefix}searchMessages AS m
  WHERE p.phraseId = m.phraseId AND p.phraseName IN ($search2)");*/

  $searchMessageIds = $database->select(
    array(
      "{$sqlPrefix}searchPhrases" => array(
        'phraseName' => 'phraseName',
        'phraseId' => 'pphraseId',
      ),
      "{$sqlPrefix}searchMessages" => array(
        'phraseId' => 'mphraseId',
        'messageId' => 'messageId',
      ),
    ),
    array(
      'both' => array(
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'mphraseId',
          ),
          'right' => array(
            'type' => 'column',
            'value' => 'pphraseId',
          ),
        ),
        array(
          'type' => 'in',
          'left' => array(
            'type' => 'column',
            'value' => 'phraseName',
          ),
          'right' => array(
            'type' => 'array',
            'value' => $searchArray2,
          ),
        ),
      ),
    ),
    false
  );

  $whereClause .= " AND messageId IN ($searchMessageIds[messages]) ";
}



/* Plugin Hook Start */
($hook = hook('getMessages_start') ? eval($hook) : '');



/* Start Crazy Shit */
if (is_array($request['rooms'])) {
  if (count($request['rooms']) > 0) {
    $rooms = $database->select($queryParts['roomsSelect']['columns'],
      $queryParts['roomsSelect']['conditions'],
      $queryParts['roomsSelect']['sort']);
    $rooms = $rooms->getAsArray('roomId');

    foreach ($rooms AS $roomId => $roomData) { // We will run through each room.
      /* Date Predefine */
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
        $queryParts['messagesSelect']['sort'] = array(
          'messageId' => 'asc',
        );
      }
      else {
        $queryParts['messagesSelect']['columns'] = array(
          "{$sqlPrefix}messagesCached" => array(
            'messageId' => 'messageId',
            'time' => array(
              'context' => 'time',
              'name' => 'time',
            ),
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
                'value' => (int) $room['roomId'],
              ),
            ),
          ),
        );
        $queryParts['messagesSelect']['sort'] = array(
          'messageId' => 'asc',
        );
      }



      /* Modify Query Data for Directives */
      if ($request['messageDateMax']) {
        $queryParts['messagesSelect']['conditions']['both'][] = array(
          'type' => 'lt',
          'left' => array(
            'type' => 'column',
            'value' => 'messageId',
          ),
          'right' => array(
            'type' => 'int',
            'value' => (int) $request['messageDateMax'],
          ),
        );
      }
      if ($request['messageDateMin']) {
        $queryParts['messagesSelect']['conditions']['both'][] = array(
          'type' => 'gt',
          'left' => array(
            'type' => 'column',
            'value' => 'messageId',
          ),
          'right' => array(
            'type' => 'int',
            'value' => (int) $request['messageDateMin'],
          ),
        );
      }

      if ($request['messageDateMax']) {
        $queryParts['messagesSelect']['conditions']['both'][] = array(
          'type' => 'lt',
          'left' => array(
            'type' => 'column',
            'context' => 'time',
            'value' => 'time',
          ),
          'right' => array(
            'type' => 'int',
            'value' => (int) $request['messageDateMax'],'CURRENT_TIMESTAMP()',
          ),
        );
      }
      if ($request['messageDateMin']) {
        $queryParts['messagesSelect']['conditions']['both'][] = array(
          'type' => 'gt',
          'left' => array(
            'type' => 'column',
            'context' => 'time',
            'value' => 'time',
          ),
          'right' => array(
            'type' => 'int',
            'value' => (int) $request['messageDateMin'],
          ),
        );
      }

      if ($request['messageIdStart']) {
        $queryParts['messagesSelect']['conditions']['both'][] = array(
          'type' => 'gt',
          'left' => array(
            'type' => 'column',
            'value' => 'messageId',
          ),
          'right' => array(
            'type' => 'int',
            'value' => (int) $request['messageIdStart'],
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
            'value' => (int) ($request['messageIdStart'] + $request['messageLimit']),
          ),
        );
      }
      if ($request['messageIdEnd']) {
        $queryParts['messagesSelect']['conditions']['both'][] = array(
          'type' => 'lt',
          'left' => array(
            'type' => 'column',
            'value' => 'messageId',
          ),
          'right' => array(
            'type' => 'int',
            'value' => (int) $request['messageIdEnd'],
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
            'value' => (int) ($request['messageIdEnd'] - $request['messageLimit']),
          ),
        );
      }

      if (!$request['showDeleted'] && $request['archive']) {
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

      switch ($request['fields']) {
        case 'both':
        $queryParts['messagesSelect']['columns']["{$sqlPrefix}messages"]['apiText'] = 'apiText';
        $queryParts['messagesSelect']['columns']["{$sqlPrefix}messages"]['htmlText'] = 'htmlText';
        break;

        case 'api':
        $queryParts['messagesSelect']['columns']["{$sqlPrefix}messages"]['apiText'] = 'apiText';
        break;

        case 'html':
        $queryParts['messagesSelect']['columns']["{$sqlPrefix}messages"]['htmlText'] = 'htmlText';
        break;

        default:
        $errStr = 'badFields';
        $errDesc = 'The given message fields are invalid - recognized values are "api", "html", and "both"';
        break;
      }



      /* Make sure the user has permission to view posts from the room
       * TODO: make work with multiple rooms */
      $permission = fim_hasPermission($roomData,$user,'view',false);

      if (!$permission[0]) { // No Permission

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

        ($hook = hook('getMessages_noPerm') ? eval($hook) : '');

      }
      else { // Has Permission

        /* Process Ping */
        if (!$noPing) {
          $database->insert(array(
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

          ($hook = hook('getMessages_ping') ? eval($hook) : '');
        }


        /* Get Messages from Database */
        if ($longPolling) {

          while (!$messages) {
            $messages = $database->select($queryParts['messagesSelect']['columns'],
              $queryParts['messagesSelect']['conditions'],
              $queryParts['messagesSelect']['sort']);
            $messages->getAsArray('messageId');

            ($hook = hook('getMessages_postMessages_longPolling_repeat') ? eval($hook) : '');

            sleep($longPollingWait);
          }

          ($hook = hook('getMessages_postMessages_longPolling') ? eval($hook) : '');

        }
        else {

          $messages = $database->select($queryParts['messagesSelect']['columns'],
            $queryParts['messagesSelect']['conditions'],
            $queryParts['messagesSelect']['sort']);
          $messages->getAsArray('messageId');

          ($hook = hook('getMessages_postMessages_polling') ? eval($hook) : '');

        }


        /* Process Messages */
        if (is_array($messages)) {
          if (count($messages) > 0) {
            foreach ($messages AS $id => $message) {

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
                  'socialGroups' => ($message['socialGroups']),
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


              ($hook = hook('getMessages_eachMessage') ? eval($hook) : ''); // Useful for running code that requires the specific message array to still be present, or otherwise for convience sake.

            }
          }
        }


        /* Process Active Users */
        if ($request['activeUsers']) {
          $queryParts['activeUsersSelect']['columns'] = array(
            "{$sqlPrefix}ping" => array(
              'status' => 'status',
              'typing' => 'typing',
              'time' => 'ptime',
              'roomId' => 'proomId',
              'userId' => 'puserId',
            ),
            "{$sqlPrefix}rooms" => array(
              'roomId' => 'roomId',
            ),
            "{$sqlPrefix}users" => array(
              'userId' => 'userId',
              'userName' => 'userName',
            ),
          );
          $queryParts['activeUsersSelect']['conditions'] = array(
            'both' => array(
              array(
                'type' => 'e',
                'left' => array(
                  'type' => 'column',
                  'value' => 'roomId',
                ),
                'right' => array(
                  'type' => 'int',
                  'value' => (int) $room['roomId'],
                ),
              ),
              array(
                'type' => 'e',
                'left' => array(
                  'type' => 'column',
                  'value' => 'proomId',
                ),
                'right' => array(
                  'type' => 'column',
                  'value' => 'roomId',
                ),
              ),
              array(
                'type' => 'e',
                'left' => array(
                  'type' => 'column',
                  'value' => 'puserId',
                ),
                'right' => array(
                  'type' => 'column',
                  'value' => 'userId',
                ),
              ),
              array(
                'type' => 'gte',
                'left' => array(
                  'type' => 'column',
                  'value' => 'ptime',
                  'context' => 'time',
                ),
                'right' => array(
                  'type' => 'int',
                  'value' => (int) ($request['time'] - $request['onlineThreshold']),
                ),
              ),
            ),
          );
          $queryParts['activeUsersSelect']['sort'] = array(
            'userName' => 'asc',
          );

/*          $activeUsers = dbRows("SELECT u.{$sqlUserTableCols[userName]} AS userName,
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
          {$activeUser_end}",'userId');*//*
          $activeUsers = $database->select($queryParts['activeUsersSelect']['columns'],
            $queryParts['activeUsersSelect']['conditions'],
            $queryParts['activeUsersSelect']['sort']);
          $activeUsers = $activeUsers->getAsArray();*/


          if (is_array($activeUsers)) {
            if (count($activeUsers) > 0) {
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
          }


          ($hook = hook('getMessages_activeUsers') ? eval($hook) : '');
        }
      }
    }
  }
}

$request['watchRooms'] = false;
if ($request['watchRooms']) {
  ($hook = hook('getMessages_watchRooms_start') ? eval($hook) : '');

  /* Get Missed Messages */
  $missedMessages = $database->select(
    array(
      "{$sqlPrefix}rooms" => array(
        'roomId' => 'roomId',
        'options' => 'options',
        'allowedUsers' => 'allowedUsers',
        'lastMessageTime' => array(
          'context' => 'time',
          'name' => 'lastMessageTime',
        ),
      ),
      "{$sqlPrefix}ping" => array(
        'time' => array(
          'context' => 'time',
          'name' => 'pingTime',
        ),
        'userId' => 'puserId',
        'roomId' => 'proomId',
      ),
    ),
    array(
      'both' => array(
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'userId',
          ),
          'right' => array(
            'type' => 'int',
            'value' => (int) $user['userId'],
          ),
        ),
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'roomId',
          ),
          'right' => array(
            'type' => 'column',
            'value' => 'proomId',
          ),
        ),
        'either' => array(
          array(
            'type' => 'bitwise',
            'left' => array(
              'type' => 'column',
              'value' => 'options',
            ),
            'right' => array(
              'type' => 'int',
              'value' => 16,
            ),
          ),
          array(
            'type' => 'in',
            'left' => array(
              'type' => 'column',
              'value' => 'roomId',
            ),
            'right' => array(
              'type' => 'array',
              'value' => fim_arrayValidate(explode(',',$user['watchRooms']),'int',false),
            ),
          ),
        ),
        'either' => array(
          array(
            'type' => 'regexp',
            'left' => array(
              'type' => 'column',
              'value' => 'allowedUsers',
            ),
            'right' => array(
              'type' => 'regexp',
              'value' => '(' . (int) $user['userId'] . ',|' . (int) $user['userId'] . ')$',
            ),
          ),
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'allowedUsers',
            ),
            'right' => array(
              'type' => 'string',
              'value' => '*',
            ),
          ),
        ),
        array(
          'type' => 'if',
          'condition' => 'p.time',
          'true' => array(
            'type' => 'gt',
            'left' => array(
              'type' => 'column',
              'value' => 'time',
            ),
            'right' => array(
              'type' => 'equation',
              'value' => '$time + 10',
            ),
          ),
          'false' => array(
            'type' => 'true',
          ),
        ),
      ),
    )
  );
  $missedMessages = $missedMessages->getAsArray();// AND (r.allowedUsers REGEXP  OR r.allowedUsers = '*') AND IF(p.time, UNIX_TIMESTAMP(r.lastMessageTime) > (UNIX_TIMESTAMP(p.time) + 10), TRUE)

  if (is_array($missedMessages)) {
    if (count($missedMessages) > 0) {
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
  }

  ($hook = hook('getMessages_watchRooms') ? eval($hook) : '');
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