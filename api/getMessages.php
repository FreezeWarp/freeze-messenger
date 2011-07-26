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
 * @param int [messageLimit=1000] - The maximum number of posts to receive, defaulting to the internal limit of (in most cases) 1000. This should be high, as all other conditions (roomId, deleted, etc.) are applied after this limit.
 * @param int [messageHardLimit=40] - An alternative, generally lower limit applied once all messages are obtained from the server (or via the LIMIT clause of applicable). In other words, this limits the number of results AFTER roomId, etc. restrictions have been applied.
 * @param timestamp [messageDateMin=null] - The earliest a post could have been made. Use of messageDateMax only makes sense with no messageLimit. Do not specify to prevent checking.
 * @param timestamp [messageDateMax=null] - The latest a post could have been made. Use of messageDateMax only makes sense with no messageLimit. Do not specify to prevent checking.
 * @param int [messageIdMin=null] - All posts must be after this ID. Use of messageDateMax only makes sense with no messageLimit. Do not specify to prevent checking.
 * @param int [messageIdMax=null] - All posts must be before this ID. Use of messageDateMax only makes sense with no messageLimit. Do not specify to prevent checking.
 * @param int [messageIdStart=null] - When specified WITHOUT the above two directives, messageIdStart will return all posts from this ID to this ID plus the messageLimit directive. This is strongly encouraged for all requests to the cache, e.g. for normal instant messenging sessions.
 * @param bool [noping=false] - Disables ping; useful for archive viewing.
 * @param bool [watchRooms=false] - Get unread messages from a user’s list of watchRooms (also applies to private IMs).
 * @param bool [activeUsers=false] - Returns a list of activeUsers in the room(s) if specified. This is identical to calling the getActiveUsers script, except with less data redundancy.
 * @param bool [longPolling=false] - Whether or not to enable experimental longPolling. It will be replaced with "pollMethod=push|poll|longPoll" in version 4 when all three methods will be supported (though will be backwards compatible).
 * @param bool [showDeleted=false] - Whether or not to show deleted messages. You will need to be a room moderator.
 * @param int [onlineThreshold=15] - If using the activeUsers functionality, this will alter the effective onlineThreshold to be used.
 * @param string [search=null] - A keyword that can be used for searching through the archive. It will overwrite messages.
 * @param string [encode=plaintext] - The encoding of messages to be used on retrieval. "plaintext" is the only accepted format currently.
 * @param string [fields=api|html|both] - The message fields to obtain: "api", "html", or "both". The "html" result returns data preformatted using BBcode and other functionality, while the API field is mostly untouched.
 * @param string [messages=null] - A comma seperated list of message IDs that the results will be limited to. It is only intended for use with the archive, and will be over-written with the results of search.
 *
 * @todo Add back unread message retrieval.
 *
 * -- Notes on Scalability --
 * As FreezeMessenger attempts to ecourage broad scalability wherever possible, sacrifices are at times made to prevent badness from happening. getMessages illustrates one of the best examples of this:
 * the use of indexes is a must for any reliable message retrieval. As such, a standard "SELECT * WHERE roomId = xxx ORDER BY messageId DESC LIMIT 10" (the easiest way of getting the last 10 messages) is simply impossible. Instead, a few alternatives are recommended:
 ** Specify a "messageIdEnd" as the last message obtained from the room.
 * similarly, the messageLimit and messageHardLimit directives are applied for the sake of scalibility. messageHardLimit is after results have been retrieved and filtered by, say, the roomId, and messageLimit is a limit on messages retrieved from all rooms, etc.
 * a message cache is maintained, and it is the default means of obtaining messages. Specifying archive will be far slower, but is required for searching, and generally is recommended at other times as well (e.g. getting initial posts).
*/


$apiRequest = true;

require_once('../global.php');


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

    'messages' => array(
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
      'default' => (isset($config['defaultOnlineThreshold']) ? $config['defaultOnlineThreshold'] : 15),
      'context' => array(
        'type' => 'int',
      ),
    ),

    'messageLimit' => array(
      'type' => 'int',
      'require' => false,
      'default' => (isset($config['defaultMessageLimit']) ? $config['defaultMessageLimit'] : 10000),
      'context' => array(
        'type' => 'int',
      ),
    ),

    'messageHardLimit' => array(
      'type' => 'int',
      'require' => false,
      'default' => (isset($config['defaultMessageHardLimit']) ? $config['defaultMessageHardLimit'] : 50),
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
        'api,html',
        'html,api',
      ),
      'require' => false,
      'default' => 'both',
    ),
  ),
));


if ($config['longPolling'] && $request['longPolling'] === true) {
  $config['longPolling'] = true;

  set_time_limit(0);
  ini_set('max_execution_time',0);
}
else {
  $config['longPolling'] = false;
}


if ($request['messageLimit'] > 10000) {
  $request['messageLimit'] = 10000; // Sane maximum.
}
if ($request['messageHardLimit'] > 500) {
  $request['messageHardLimit'] = 500; // Sane maximum.
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
  $searchArray = explode(',',$request['search']);

  foreach ($searchArray AS $searchVal) {
    $searchArray2[] = str_replace(
      array_keys($config['searchWordConverts']),
      array_values($config['searchWordConverts']),
      $searchVal
    );
  }

/*  $searchMessageIds = dbRows("SELECT GROUP_CONCAT(messageId SEPARATOR ',') AS messages
  FROM {$sqlPrefix}searchPhrases AS p,
  {$sqlPrefix}searchMessages AS m
  WHERE p.phraseId = m.phraseId AND p.phraseName IN ($search2)");*/

  $queryParts['searchSelect']['columns'] = array(
    "{$sqlPrefix}searchPhrases" => array(
      'phraseName' => 'phraseName',
      'phraseId' => 'pphraseId',
    ),
    "{$sqlPrefix}searchMessages" => array(
      'phraseId' => 'mphraseId',
      'messageId' => 'messageId',
    ),
  );
  $queryParts['searchSelect']['conditions'] = array(
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
    ),
  );
  $queryParts['searchSelect']['join'] = false;

  if (!$config['fullTextArchive']) { // Original, Fastest Algorithm
    $queryParts['searchSelect']['columns']["{$sqlPrefix}searchMessages"]['messageId 2'] = array(
      'context' => 'join',
      'separator' => ',',
      'name' => 'messageIds',
    );
    $queryParts['searchSelect']['conditions']['both'][] = array(
      'type' => 'in',
      'left' => array(
        'type' => 'column',
        'value' => 'phraseName',
      ),
      'right' => array(
        'type' => 'array',
        'value' => $searchArray2,
      ),
    );

    $queryParts['searchSelect']['join'] = 'messageId';
  }
  else { // Slower Algorithm
    $queryParts['searchSelect']['columns']["{$sqlPrefix}searchMessages"]['messageId 2'] = array(
      'context' => 'join',
      'separator' => ',',
      'name' => 'messageIds',
    );

    $queryParts['searchSelect']['join'] = 'mphraseId';
  }

  $searchMessageIds = $database->select(
    $queryParts['searchSelect']['columns'],
    $queryParts['searchSelect']['conditions'],
    false,
    $queryParts['searchSelect']['join']
  );

  if (!$config['fullTextArchive']) {
    $searchMessageIds = $searchMessageIds->getAsArray(false);
    $searchMessages = explode(',',$searchMessageIds['messageIds']);
  }
  else {
    $searchMessageIds = $searchMessageIds->getAsArray('mphraseId');

    foreach ($searchMessageIds AS &$phrase) {
      foreach($searchArray2 AS $arrayPiece) {
        if (strpos($phrase['phraseName'],$arrayPiece) !== false) {
          $searchMessageIds2[] = $phrase;
        }
      }
    }

    foreach ($searchMessageIds2 AS $phrase) {
      foreach (explode(',',$phrase['messageIds']) AS $message) {
        $searchMessageIds3[] = $message;
      }
    }

    $searchMessages = $searchMessageIds3;
  }

  $request['messages'] = fim_arrayValidate($searchMessages,'int',true);
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

    foreach ($rooms AS $roomId => $room) { // We will run through each room.
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
            'roomId' => 'roomId',
            'userId' => 'userId',
            'deleted' => 'deleted',
          ),
          "{$sqlPrefix}users" => array(
            'userId' => 'muserId',
            'userName' => 'userName',
            'userGroup' => 'userGroup',
            'socialGroups' => 'socialGroups',
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
            'roomId' => 'roomId',
            'time' => array(
              'context' => 'time',
              'name' => 'time',
            ),
            'flag' => 'flag',
            'userId' => 'userId',
            'userName' => 'userName',
            'userGroup' => 'userGroup',
            'socialGroups' => 'socialGroups',
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
      if ($request['messageIdMax']) {
        $queryParts['messagesSelect']['conditions']['both'][] = array(
          'type' => 'lte',
          'left' => array(
            'type' => 'column',
            'value' => 'messageId',
          ),
          'right' => array(
            'type' => 'int',
            'value' => (int) $request['messageIdMax'],
          ),
        );
      }
      if ($request['messageIdMin']) {
        $queryParts['messagesSelect']['conditions']['both'][] = array(
          'type' => 'gte',
          'left' => array(
            'type' => 'column',
            'value' => 'messageId',
          ),
          'right' => array(
            'type' => 'int',
            'value' => (int) $request['messageIdMin'],
          ),
        );
      }

      if ($request['messageDateMax']) {
        $queryParts['messagesSelect']['conditions']['both'][] = array(
          'type' => 'lte',
          'left' => array( // Quick Note: Context: time is redunant and will cause issues if defined.
            'type' => 'column',
            'value' => 'time',
          ),
          'right' => array(
            'type' => 'int',
            'value' => (int) $request['messageDateMax'],
          ),
        );
      }
      if ($request['messageDateMin']) {
        $queryParts['messagesSelect']['conditions']['both'][] = array(
          'type' => 'gte',
          'left' => array( // Quick Note: Context: time is redunant and will cause issues if defined.
            'type' => 'column',
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
          'type' => 'gte',
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
          'type' => 'lte',
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

      if (!$request['showDeleted'] === true && $request['archive'] === true) {
        $queryParts['messagesSelect']['conditions']['both'][] = array(
          'type' => 'e',
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

      if (count($request['messages']) > 0) {
        $queryParts['messagesSelect']['conditions']['both'][] = array(
          'type' => 'in',
          'left' => array(
            'type' => 'column',
            'value' => 'messageId',
          ),
          'right' => array(
            'type' => 'array',
            'value' => $request['messages'],
          ),
        );
      }


      switch ($request['fields']) {
        case 'both':
        case 'api,html': // In the future we may introduce more fields that will require using comma-values.
        case 'html,api': // Same thing.
        $queryParts['messagesSelect']['columns']["{$sqlPrefix}messages" . (!$request['archive'] ? 'Cached' : '')]['apiText'] = 'apiText';
        $queryParts['messagesSelect']['columns']["{$sqlPrefix}messages" . (!$request['archive'] ? 'Cached' : '')]['htmlText'] = 'htmlText';
        break;

        case 'api':
        $queryParts['messagesSelect']['columns']["{$sqlPrefix}messages" . (!$request['archive'] ? 'Cached' : '')]['apiText'] = 'apiText';
        break;

        case 'html':
        $queryParts['messagesSelect']['columns']["{$sqlPrefix}messages" . (!$request['archive'] ? 'Cached' : '')]['htmlText'] = 'htmlText';
        break;

        default:
        $errStr = 'badFields';
        $errDesc = 'The given message fields are invalid - recognized values are "api", "html", and "both"';
        break;
      }



      /* Make sure the user has permission to view posts from the room
       * TODO: make work with multiple rooms */
      $permission = fim_hasPermission($room,$user,'view',false);

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
        if (!$request['noping']) {
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

        if ($config['longPolling']) {

          while (!$messages) {
            $messages = $database->select($queryParts['messagesSelect']['columns'],
              $queryParts['messagesSelect']['conditions'],
              $queryParts['messagesSelect']['sort'],
              false,
              $request['messageHardLimit']);
            $messages = $messages->getAsArray('messageId');

            ($hook = hook('getMessages_postMessages_longPolling_repeat') ? eval($hook) : '');

            sleep(isset($config['longPollingWait']) ? $config['longPollingWait'] : 2);
          }

          ($hook = hook('getMessages_postMessages_longPolling') ? eval($hook) : '');

        }
        else {

          $messages = $database->select($queryParts['messagesSelect']['columns'],
            $queryParts['messagesSelect']['conditions'],
            $queryParts['messagesSelect']['sort'],
            false,
            $request['messageHardLimit']);// echo $messages->sourceQuery;
          $messages = $messages->getAsArray('messageId');

          ($hook = hook('getMessages_postMessages_polling') ? eval($hook) : '');

        }


        /* Process Messages */
        if (is_array($messages)) {
          if (count($messages) > 0) {
            foreach ($messages AS $id => $message) {
              $message = fim_decrypt($message);


              switch ($request['encode']) {
                case 'plaintext':
                // All Good
                break;

                case 'base64':
                $message['apiText'] = base64_encode($message['apiText']);
                $message['htmlText'] = base64_encode($message['htmlText']);
                break;
              }


              $xmlData['getMessages']['messages']['message ' . (int) $message['messageId']] = array(
                'messageData' => array(
                  'roomId' => (int) $room['roomId'],
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
              'time' => array(
                'name' => 'ptime',
                'context' => 'time',
              ),
              'roomId' => 'proomId',
              'userId' => 'puserId',
            ),
            "{$sqlPrefix}rooms" => array(
              'roomId' => 'roomId',
            ),
            "{$sqlPrefix}users" => array(
              'userId' => 'userId',
              'userName' => 'userName',
              'userFormatStart' => 'userFormatStart',
              'userFormatEnd' => 'userFormatEnd',
              'userGroup' => 'userGroup',
              'socialGroups' => 'socialGroups',
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
//                  'context' => 'time',
                ),
                'right' => array(
                  'type' => 'int',
                  'value' => (int) (time() - $request['onlineThreshold']),
                ),
              ),
            ),
          );
          $queryParts['activeUsersSelect']['sort'] = array(
            'userName' => 'asc',
          );


          $activeUsers = $database->select($queryParts['activeUsersSelect']['columns'],
            $queryParts['activeUsersSelect']['conditions'],
            $queryParts['activeUsersSelect']['sort']);// echo $activeUsers->sourceQuery;
          $activeUsers = $activeUsers->getAsArray(true);


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
          'type' => 'gt',
          'left' => array( // Quick Note: Context: time is redunant and will cause issues if defined.
            'type' => 'column',
            'value' => 'time',
          ),
          'right' => array(
            'type' => 'equation',
            'value' => '$time + 10',
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