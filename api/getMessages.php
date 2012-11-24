<?php
/* FreezeMessenger Copyright © 2012 Joseph Todd Parsons

 * This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
   along withis program.  If not, see <http://www.gnu.org/licenses/>. */

/**
 * Get Messages from the Server
 * Works with both private and normal rooms.
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2012

 * Primary Directives:
 * @param string roomId - The ID of the room to fetch messages from.
 * @param int [messageLimit=1000] - The maximum number of posts to receive, defaulting to the internal limit of (in most cases) 1000. This should be high, as all other conditions (roomId, deleted, etc.) are applied after this limit.
 * @param int [messageHardLimit=40] - An alternative, generally lower limit applied once all messages are obtained from the server (or via the LIMIT clause of applicable). In other words, this limits the number of results AFTER roomId, etc. restrictions have been applied.
 * @param timestamp [messageDateMin=null] - Thearliest a post could have been made. Use of messageDateMax only makesense with no messageLimit. Do not specify to prevent checking.
 * @param timestamp [messageDateMax=null] - The latest a post could have been made. Use of messageDateMax only makesense with no messageLimit. Do not specify to prevent checking.
 * @param int [messageIdMin=null] - All posts must be after this ID. Use of messageDateMax only makesense with no messageLimit. Do not specify to prevent checking.
 * @param int [messageIdMax=null] - All posts must before this ID. Use of messageDateMax only makesense with no messageLimit. Do not specify to prevent checking.
 * @param int [messageIdStart=null] - When specified WITHOUT the above two directives, messageIdStart will return all posts from this ID to this ID plus the messageLimit directive. This strongly encouraged for all requests to the cache, e.g. for normal instant messenging sessions.

 * Misc Directives:
 * @param bool [noping=false] - Disables ping; useful for archive viewing.
 * @param bool [longPolling=false] - Whether or noto enablexperimentalongPolling. It will be replaced with "pollMethod=push|poll|longPoll" in version 4 when all three methods will be supported (though will be backwards compatible).
 * @param string [encode=plaintext] - Thencoding of messages to be used on retrieval. "plaintext" is the only accepted format currently.

 * Filters:
 * @param bool [showDeleted=false] - Whether or noto show deleted messages. You will need to be a roomoderator. This directive only has an effect on the archive, as the cache does not retain deleted messages.
 * @param string [search=null] - A keyword that can be used for searching through the archive. It will overwrite messages.
 * @param string [messages=null] - A comma seperated list of message IDs thathe results will be limited to. It is only intended for use withe archive, and will be over-written withe results of the search directive if specified.
 * @param string users - A comma seperated list of users to restrict message retrieval to. This most useful for archive scanning, though can in theory be used withe message cache as well.
 *
 * @todo Add back unread message retrieval.
 *
 * -- Notes on Scalability --
 * As FreezeMessenger attempts to ecourage broad scalability wherever possible, sacrifices are atimes made to prevent badness from happening. getMessages illustrates one of the best examples of this:
 * the use of indexes is a must for any reliable message retrieval. As such, a standard "SELECT * WHERE roomId = xxx ORDER BY messageId DESC LIMIT 10" (the easiest way of getting the last 10 messages) is simply impossible. Instead, a few alternatives are recommended:
 ** Specify a "messageIdEnd" as the last message obtained from the room.
 * similarly, the messageLimit and messageHardLimit directives are applied for the sake of scalibility. messageHardLimit is after results have been retrieved and filtered by, say, the roomId, and messageLimit is a limit on messages retrieved from all rooms, etc.
 * a message cache is maintained, and it is the default means of obtaining messages. Specifying archive will be far slower, but is required for searching, and generally is recommended at other times as well (e.g. getting initial posts).
 *
 * -- TODO --
 * We need to use internal message boundaries via the messageIndex and messageDates table. Using these, we can approximate message dates for individual rooms. Here is how that will work:
 ** Step 1: Identify Crtiteria. If a criteria is date based (e.g. what was said in this room on this date?), we will rely on messageDates. If it is ID-based, we will rely on messageIndex.
 ** Step 2: If using date-based criteria, we lookup the approximate post ID that corresponds to the room and date. At this point, we are basically done. Simply set the messageIdStart to the date that occured before and mesageIdEnd to the date that occured after.
 ** If, however, we are using ID-based criteria, we will instead look into messageIndex. Here, we correlate room and ID, and try to find an approprimate messageIdEnd and messageIdStart.
 ** Step 3: Use a more narrow range if neccessary. The indexes we used may be too large. In this case, we need to do our best to approximate.
*/


$apiRequest = true;

require('../global.php');


/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
  'roomId' => array(
    'default' => '',
  ),

  'users' => array(
    'default' => '',
    'context' => array(
      'type' => 'csv',
      'filter' => 'int',
      'evaltrue' => true,
    ),
  ),

  'messages' => array(
    'default' => '',
    'context' => array(
      'type' => 'csv',
      'filter' => 'int',
      'evaltrue' => true,
    ),
  ),

  'sort' => array(
    'valid' => array(
      'roomId', 'roomName', 'smart',
    ),
    'default' => 'roomId',
  ),

  'showDeleted' => array(
    'default' => false,
    'context' => 'bool',
  ),

  'archive' => array(
    'default' => false,
    'context' => 'bool',
  ),

  'noping' => array(
    'default' => false,
    'context' => 'bool',
  ),

  'longPolling' => array(
    'default' => false,
    'context' => 'bool',
  ),

  'messageDateMax' => array(
    'context' => 'int',
  ),

  'messageDateMin' => array(
    'context' => 'int',
  ),

  'messageIdStart' => array(
    'context' => 'int',
  ),

  'messageIdEnd' => array(
    'context' => 'int',
  ),

  'onlineThreshold' => array(
    'default' => $config['defaultOnlineThreshold'],
    'context' => 'int',
  ),

  'messageLimit' => array(
    'default' => $config['defaultMessageLimit'],
    'max' => $config['maxMessageLimit'],
    'min' => 1,
    'context' => 'int',
  ),

  'messageHardLimit' => array(
    'default' => $config['defaultMessageHardLimit'],
    'max' => $config['maxMessageHardLimit'],
    'min' => 1,
    'context' => 'int',
  ),

  'search' => array(
    'default' => false,
  ),

  'encode' => array(
    'default' => 'plaintext',
    'valid' => array(
      'plaintext', 'base64',
    ),
  ),
));


if ($config['longPolling'] && $request['longPolling'] === true) {
  $config['longPolling'] = true;
  $longPollingRetries = 0;

  set_time_limit(0);
  ini_set('max_execution_time',0);
}
else {
  $config['longPolling'] = false;
}


$room = $database->getRoom($request['roomId']); // Get the roomdata.


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
  ),
);


if (!$room) {
  $errStr = 'badRoom';
  $errDesc = 'That room could not be found.';
}
else {
  if ((strlen($request['search']) > 0) && $request['archive']) {
    $searchArray = explode(',',$request['search']);

    foreach ($searchArray AS $searchVal) {
      $searchArray2[] = str_replace(
        array_keys($config['searchWordConverts']),
        array_values($config['searchWordConverts']),
        $searchVal
      );
    }

    /* Establish Base Data */
    $queryParts['searchSelect']['columns'] = array(
      "{$sqlPrefix}searchPhrases" => array(
        'phraseName' => 'phraseName',
        'phraseId' => 'pphraseId',
      ),
      "{$sqlPrefix}searchMessages" => array(
        'phraseId' => 'mphraseId',
        'messageId' => 'messageId',
        'userId' => 'userId',
        'roomId' => 'roomId',
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


    /* Apply User and Room Filters */
    $queryParts['searchSelect']['conditions']['both'][] = array(
      'type' => 'e',
      'left' => array(
        'type' => 'column',
        'value' => 'roomId',
      ),
      'right' => array(
        'type' => 'string',
        'value' => $request['roomId'],
      ),
    );

    if (count($request['users']) > 0) {
      $queryParts['searchSelect']['conditions']['both'][] = array(
        'type' => 'in',
        'left' => array(
          'type' => 'column',
          'value' => 'userId',
        ),
        'right' => array(
          'type' => 'array',
          'value' => $request['users'],
        ),
      );
    }


    /* Determine Whether to Use the Fast or Slow Algorithms */
    if (!$config['fullTextArchive']) { // Original, Fastest Algorithm
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
    }
    else { // Slower Algorithm
      foreach ($searchArray2 AS $phrase) {
        $queryParts['searchSelect']['conditions']['both']['either'][] = array(
          'type' => 'glob',
          'left' => array(
            'type' => 'column',
            'value' => 'phraseName',
          ),
          'right' => array(
            'type' => 'glob',
            'value' => '*' . $phrase . '*',
          ),
        );
      }
    }


    /* Run the Query */
    $searchMessageIds = $database->select(
      $queryParts['searchSelect']['columns'],
      $queryParts['searchSelect']['conditions']);

    $searchMessageIds = $searchMessageIds->getAsArray('messageId');
    $searchMessages = array_keys($searchMessageIds);


    /* Modify the Request Filter for Messages */
    if ($searchMessages) {
      $request['messages'] = fim_arrayValidate($searchMessages, 'int', true);
    }
    else {
      $request['messages'] = array(0); // This is a fairly dirty approach, but it does work for now.
    }
  }

  if ($request['archive']) {
    $queryParts['messagesSelect']['columns'] = array(
      "{$sqlPrefix}messages" => 'messageId, time, iv, salt, roomId, userId, deleted, flag, text',
      "{$sqlPrefix}users" => 'userId muserId, userName, userGroup, socialGroups, userFormatStart, userFormatEnd, avatar, defaultColor, defaultFontface, defaultHighlight, defaultFormatting'
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
      "{$sqlPrefix}messagesCached" => "messageId, roomId, time, flag, userId, userName, userGroup, socialGroups, userFormatStart, userFormatEnd, avatar, defaultColor, defaultFontface, defaultHighlight, defaultFormatting, text",
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
  if (isset($request['messageIdMax'])) {
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
  if (isset($request['messageIdMin'])) {
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

  if (isset($request['messageDateMax'])) {
    $queryParts['messagesSelect']['conditions']['both'][] = array(
      'type' => 'lte',
      'left' => array(
        'type' => 'column',
        'value' => 'time',
      ),
      'right' => array(
        'type' => 'int',
        'value' => (int) $request['messageDateMax'],
      ),
    );
  }
  if (isset($request['messageDateMin'])) {
    $queryParts['messagesSelect']['conditions']['both'][] = array(
      'type' => 'gte',
      'left' => array(
        'type' => 'column',
        'value' => 'time',
      ),
      'right' => array(
        'type' => 'int',
        'value' => (int) $request['messageDateMin'],
      ),
    );
  }

  if (isset($request['messageIdStart'])) {
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
  elseif (isset($request['messageIdEnd'])) {
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

  if (count($request['users']) > 0) {
    $queryParts['messagesSelect']['conditions']['both'][] = array(
      'type' => 'in',
      'left' => array(
        'type' => 'column',
        'value' => 'userId',
      ),
      'right' => array(
        'type' => 'array',
        'value' => $request['users'],
      ),
    );
  }



  /* Plugin Hook Start */
  ($hook = hook('getMessages_start') ? eval($hook) : '');



  /* Start Crazy Stuff */

  /* Make sure the user has permission to view posts from the room. */
  $permission = fim_hasPermission($room, $user, 'view', false);

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
      $database->insert("{$sqlPrefix}ping", array(
        'userId' => $user['userId'],
        'roomId' => $room['roomId'],
        'time' => $database->now(),
      ), array(
        'time' => $database->now(),
      ));

      ($hook = hook('getMessages_ping') ? eval($hook) : '');
    }


    /* Get Messages from Database */

    if ($config['longPolling']) {
      $messages = false;
      while (!$messages) {
        $longPollingRetries++;

        $messages = $database->select($queryParts['messagesSelect']['columns'],
          $queryParts['messagesSelect']['conditions'],
          $queryParts['messagesSelect']['sort'],
          $request['messageLimit']);
        $messages = $messages->getAsArray('messageId');

        ($hook = hook('getMessages_postMessages_longPolling_repeat') ? eval($hook) : '');

        if ($longPollingRetries <= $config['longPollingMaxRetries']) { sleep($config['longPollingWait']); }
        else break;
      }

      ($hook = hook('getMessages_postMessages_longPolling') ? eval($hook) : '');

    }
    else {
      $messages = $database->select($queryParts['messagesSelect']['columns'],
        $queryParts['messagesSelect']['conditions'],
        $queryParts['messagesSelect']['sort'],
        $request['messageLimit']);
      $messages = $messages->getAsArray('messageId');

      ($hook = hook('getMessages_postMessages_polling') ? eval($hook) : '');

    }


    /* Process Messages */
    if (is_array($messages)) {
      if (count($messages) > 0) {
        if (count($messages) > $request['messageHardLimit']) {
          if (isset($request['messageIdEnd'])) array_splice($messages, 0, -1 * $request['messageHardLimit']);
          else array_splice($messages, $request['messageHardLimit']);
        }

        foreach ($messages AS $id => $message) {
          $roomData = $database->getRoom($message['roomId']);

          $message = fim_decrypt($message, 'text');

          switch ($request['encode']) {
            case 'plaintext':
            // All Good
            break;

            case 'base64':
            $message['text'] = base64_encode($message['text']);
            break;
          }


          $xmlData['getMessages']['messages']['message ' . (int) $message['messageId']] = array(
            'messageData' => array(
              'roomId' => (int) $room['roomId'],
              'messageId' => (int) $message['messageId'],
              'messageTime' => (int) $message['time'],
              'messageText' => $message['text'],
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


          ($hook = hook('getMessages_eachMessage') ? eval($hook) : ''); // Useful forunning code that requires the specific message array to still be present, or otherwise for convience sake.

        }
      }
    }
  }
}



/* Update Data for Errors */
$xmlData['getMessages']['errStr'] = (string) $errStr;
$xmlData['getMessages']['errDesc'] = (string) $errDesc;



/* Plugin Hook End */
($hook = hook('getMessages_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);
?>