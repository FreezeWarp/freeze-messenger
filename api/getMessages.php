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
   along withis program.  If not, see <http://www.gnu.org/licenses/>. */

/**
 * Get Messages from the Server
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011

 * Primary Directives:
 * @param string rooms - A comma seperated list of rooms. Can be a single room integer format. Some predefined constants can also be used.
 * Note: Using more than one room can conflict or even break the script’s execution should the watchRooms or activeUsers flags be seto true.
 * @param int [messageLimit=1000] - The maximum number of posts to receive, defaulting to the internalimit of (in most cases) 1000. Thishould be high, as all other conditions (roomId, deleted, etc.) are applied after this limit.
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

 * Extra Data:
 * @param bool [watchRooms=false] - Get unread messages from a user’s list of watchRooms (also applies to private IMs).
 * @param bool [activeUsers=false] - Returns a list of activeUsers in the room(s) if specified. This identical to calling the getActiveUserscript, except with less data redundancy.
 * @param int [onlineThreshold=15] - If using the activeUsers functionality, this will alter theffective onlineThreshold to be used.

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
 * the use of indexes is a must for any reliable message retrieval. Asuch, a standard "SELECT * WHERE roomId = xxx ORDER BY messageId DESC LIMIT 10" (theasiest way of getting the last 10 messages) isimply impossible. Instead, a few alternatives arecommended:
 ** Specify a "messageIdEnd" as the last message obtained from the room.
 * similarly, the messageLimit and messageHardLimit directives are applied for the sake of scalibility. messageHardLimit is afteresults have been retrieved and filtered by, say, the roomId, and messageLimit is a limit on messages retrieved from all rooms, etc.
 * a message cache is maintained, and it is the default means of obtaining messages. Specifying archive will be far slower, but is required for searching, and generally is recommended at other times as well (e.g. getting initial posts).
*/


$apiRequest = true;

require('../global.php');


/* Get Request Data */
$request = fim_sanitizeGPC(array(
  'get' => array(
    'rooms' => array(
      'default' => '',
      'context' => array(
         'type' => 'csv',
         'filter' => 'int',
         'evaltrue' => true,
      ),
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
        'roomId',
        'roomName',
        'smart',
      ),
      'default' => 'roomId',
    ),

    'showDeleted' => array(
      'default' => false,
      'context' => array(
        'type' => 'bool',
      ),
    ),

    'watchRooms' => array(
      'default' => false,
      'context' => array(
        'type' => 'bool',
      ),
    ),

    'activeUsers' => array(
      'default' => false,
      'context' => array(
        'type' => 'bool',
      ),
    ),

    'archive' => array(
      'default' => false,
      'context' => array(
        'type' => 'bool',
      ),
    ),

    'noping' => array(
      'default' => false,
      'context' => array(
        'type' => 'bool',
      ),
    ),

    'longPolling' => array(
      'default' => false,
      'context' => array(
        'type' => 'bool',
      ),
    ),

    'messageIdMax' => array(
      'default' => false,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'messageIdMin' => array(
      'default' => false,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'messageDateMax' => array(
      'default' => false,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'messageDateMin' => array(
      'default' => false,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'messageIdStart' => array(
      'default' => false,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'messageIdEnd' => array(
      'default' => false,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'onlineThreshold' => array(
      'default' => $config['defaultOnlineThreshold'],
      'context' => array(
        'type' => 'int',
      ),
    ),

    'messageLimit' => array(
      'default' => 10000,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'messageHardLimit' => array(
      'default' => 50,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'search' => array(
      'default' => false,
    ),

    'encode' => array(
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
      'default' => 'both',
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
    'owner' => 'owner',
    'defaultPermissions' => 'defaultPermissions',
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
  if (count($request['rooms']) > 0) { // Dunno if its possible for it not to be...
    $queryParts['searchSelect']['conditions']['both'][] = array(
      'type' => 'in',
      'left' => array(
        'type' => 'column',
        'value' => 'roomId',
      ),
      'right' => array(
        'type' => 'array',
        'value' => $request['rooms'],
      ),
    );
  }

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
      if ($request['messageDateMin']) {
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



      /* Make sure the user has permission to view posts from the room
       * TODO: make work with multiple rooms */
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

          while (!$messages) {
            $longPollingRetries++;
            $messages = $database->select($queryParts['messagesSelect']['columns'],
              $queryParts['messagesSelect']['conditions'],
              $queryParts['messagesSelect']['sort'],
              $request['messageHardLimit']);
            $messages = $messages->getAsArray('messageId');

            ($hook = hook('getMessages_postMessages_longPolling_repeat') ? eval($hook) : '');

            if ($longPollingRetries <= $config['longPollingMaxRetries']) {
              sleep($config['longPollingWait']);
            }
          }

          ($hook = hook('getMessages_postMessages_longPolling') ? eval($hook) : '');

        }
        else {

          $messages = $database->select($queryParts['messagesSelect']['columns'],
            $queryParts['messagesSelect']['conditions'],
            $queryParts['messagesSelect']['sort'],
            $request['messageHardLimit']);
          $messages = $messages->getAsArray('messageId');

          ($hook = hook('getMessages_postMessages_polling') ? eval($hook) : '');

        }


        /* Process Messages */
        if (is_array($messages)) {
          if (count($messages) > 0) {
            foreach ($messages AS $id => $message) {
              $roomData = $database->getRoom($message['roomId']);

              $message = fim_decrypt($message, 'text');
              $messageParse = new messageParse($message['text'], $message['flag'], $user, $roomData);
              $message['text'] = $messageParse->getHtml();

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
                  'messageTimeFormatted' => fim_date(false,$message['time']),
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


        /* Process Active Users */
        if ($request['activeUsers']) {
          $queryParts['activeUsersSelect']['columns'] = array(
            "{$sqlPrefix}ping" => 'status, typing, time ptime, roomId proomId, userId puserId',
            "{$sqlPrefix}rooms" => 'roomId',
            "{$sqlPrefix}users" => 'userId, userName, userFormatStart, userFormatEnd, userGroup, socialGroups',
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
            $queryParts['activeUsersSelect']['sort']);
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