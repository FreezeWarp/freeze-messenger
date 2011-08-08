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


require('global.php');

if (!$config['serverSentEvents']) {
  die('Not Supported');
}
else {
  /* Send Proper Headers */
  header('Content-Type: text/event-stream');
  header('Cache-Control: no-cache'); // recommended to prevent caching of event data.

  $serverSentRetries = 0;


  /* Get Request Data */
  $request = fim_sanitizeGPC(array(
    'get' => array(
      'roomId' => array(
        'type' => 'string',
        'require' => true,
        'default' => 0,
        'context' => array(
          'type' => 'int',
          'evaltrue' => true,
        ),
      ),
      'lastMessage' => array(
        'type' => 'string',
        'require' => false,
        'default' => 0,
        'context' => array(
          'type' => 'int',
          'evaltrue' => false,
        ),
      ),
      'lastUnreadMessage' => array(
        'type' => 'string',
        'require' => false,
        'default' => 0,
        'context' => array(
          'type' => 'int',
          'evaltrue' => false,
        ),
      ),
      'lastEvent' => array(
        'type' => 'string',
        'require' => false,
        'default' => 0,
        'context' => array(
          'type' => 'int',
          'evaltrue' => false,
        ),
      ),
    ),
  ));

  while (true) {
    $serverSentRetries++;


    $queryParts['messagesSelect']['columns'] = array(
      "{$sqlPrefix}messagesCached" => array(
        'messageId' => 'messageId',
        'roomId' => 'roomId',
        'time' => 'time',
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
        'apiText' => 'apiText',
        'htmlText' => 'htmlText',
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
          'type' => 'gt',
          'left' => array(
            'type' => 'column',
            'value' => 'messageId',
          ),
          'right' => array(
            'type' => 'int',
            'value' => (int) $request['lastMessage'],
          ),
        ),
      ),
    );
    $queryParts['messagesSelect']['sort'] = array(
      'messageId' => 'asc',
    );



    $queryParts['missedSelect']['columns'] = array(
      "{$sqlPrefix}rooms" => array(
        'roomId' => 'roomId',
        'options' => 'options',
        'lastMessageTime' => 'lastMessageTime',
      ),
      "{$sqlPrefix}ping" => array(
        'time' => 'pingTime',
        'userId' => 'puserId',
        'roomId' => 'proomId',
      ),
    );
    $queryParts['missedSelect']['conditions'] = array(
      'both' => array(
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'puserId',
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
        array(
          'type' => 'in',
          'left' => array(
            'type' => 'column',
            'value' => 'roomId',
          ),
          'right' => array(
            'type' => 'array',
            'value' => (isset($user['watchRooms']) ? fim_arrayValidate(explode(',', $user['watchRooms']), 'int', false) : array()),
          ),
        ),
        array(
          'type' => 'gt',
          'left' => array( // Quick Note: Context: time is redunant and will cause issues if defined.
            'type' => 'column',
            'value' => 'lastMessageTime',
          ),
          'right' => array(
            'type' => 'equation',
            'value' => '$pingTime + 10',
          ),
        ),
      )
    );



    $queryParts['eventsSelect']['columns'] = array(
      "{$sqlPrefix}events" => 'eventId, eventName, roomId, userId, messageId, param1, param2, param3',
    );
    $queryParts['eventsSelect']['conditions'] = array(
      'both' => array(
        'either' => array(
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
              'value' => 'userId',
            ),
            'right' => array(
              'type' => 'int',
              'value' => (int) $user['userId'],
            ),
          ),
        ),
        'both' => array(
          array(
            'type' => 'gt',
            'left' => array(
              'type' => 'column',
              'value' => 'eventId',
            ),
            'right' => array(
              'type' => 'int',
              'value' => (int) $request['lastEvent'],
            ),
          ),
        ),
      ),
    );



    $queryParts['unreadSelect']['columns'] = array(
      "{$sqlPrefix}unreadMessages" => 'userId, senderId, senderName, senderFormatStart, senderFormatEnd, roomId, roomName, messageId',
    );
    $queryParts['unreadSelect']['conditions'] = array(
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
          'type' => 'gt',
          'left' => array(
            'type' => 'column',
            'value' => 'messageId',
          ),
          'right' => array(
            'type' => 'int',
            'value' => (int) $request['lastUnreadMessage'],
          ),
        ),
      ),
    );



    /* Get Messages */
    $messages = $database->select($queryParts['messagesSelect']['columns'],
      $queryParts['messagesSelect']['conditions'],
      $queryParts['messagesSelect']['sort']);
    $messages = $messages->getAsArray('messageId');

    $messagesOutput = array();

    if (is_array($messages)) {
      if (count($messages) > 0) {
        foreach ($messages AS $message) {
          $messagesOutput = array(
            'messageData' => array(
              'roomId' => (int) $message['roomId'],
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

            )
          );

          if ($message['messageId'] > $request['lastMessage']) {
            $request['lastMessage'] = $message['messageId'];
          }

          echo "event: message\n";
          echo "data: " . json_encode($messagesOutput) . "\n\n";


          flush();

          if (ob_get_level()) {
            ob_flush();
          }
        }
      }
    }




    /* Get New Message Alerts from Watched Rooms */
    if ($config['enableWatchRooms'] && isset($user['watchRooms'])) {
      if (count(fim_arrayValidate(explode(',', $user['watchRooms']), 'int', false)) > 0) {
        $missedMessages = $database->select(
          $queryParts['missedSelect']['columns'],
          $queryParts['missedSelect']['conditions']);
        $missedMessages = $missedMessages->getAsArray();

        if (is_array($missedMessages)) {
          if (count($missedMessages) > 0) {
            foreach ($missedMessages AS $message) {
              if (!fim_hasPermission($message, $user, 'view', true)) {
                continue;
              }

              echo "event: missedMessage\n";
              echo "data: " . json_encode($message) . "\n\n";


              flush();

              if (ob_get_level()) {
                ob_flush();
              }
            }
          }
        }
      }
    }




    /* Get Unread Private Messages */
    if ($config['enableUnreadMessages'] && $user['userId'] > 0) {
      $unreadMessages = $database->select(
        $queryParts['unreadSelect']['columns'],
        $queryParts['unreadSelect']['conditions']);
      $unreadMessages = $unreadMessages->getAsArray();

      if (is_array($unreadMessages)) {
        if (count($unreadMessages) > 0) {
          foreach ($unreadMessages AS $message) {
            echo "event: privateMessage\n";
            echo "data: " . json_encode($message) . "\n\n";

            $request['lastUnreadMessage'] = $message['messageId'];


            flush();

            if (ob_get_level()) {
              ob_flush();
            }

            ($hook = hook('getMessages_watchRooms_eachRoom') ? eval($hook) : '');
          }
        }
      }
    }




    /* Get Events */
    if ($config['enableEvents']) {
      $events = $database->select($queryParts['eventsSelect']['columns'],
        $queryParts['eventsSelect']['conditions']);
      $events = $events->getAsArray('eventId');

      $eventsOutput = array();

      if (is_array($events)) {
        if (count($events) > 0) {
          foreach ($events AS $event) {
            echo "event: " . $event['eventName'] . "\n";
            echo "data: " . json_encode($event) . "\n\n";

            $request['lastEvent'] = $event['eventId'];

            flush();

            if (ob_get_level()) {
              ob_flush();
            }

            ($hook = hook('getMessages_watchRooms_eachRoom') ? eval($hook) : '');
          }
        }
      }
    }




    if ($serverSentRetries <= $config['serverSentMaxRetries']) {
      ($hook = hook('getMessages_postMessages_serverSentEvents_repeat') ? eval($hook) : '');

      sleep($config['serverSentEventsWait']); // Wait before re-requesting.
    }
  }
}
?>