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
   along with this program.  If not, see <http://www.gnu.org/licenses/>. */


require('global.php');


if (!$config['serverSentEvents']) {
  die('Not Supported');
}
else {
  /* Send Proper Headers */
  header('Content-Type: text/event-stream');
  header('Cache-Control: no-cache'); // recommended to prevent caching of event data.

  set_time_limit($config['serverSentTimeLimit']);

  $serverSentRetries = 0;
  $outputStarted = false; // used for fastCGI


  /* Get Request Data */
  $request = fim_sanitizeGPC('g', array(
    'roomId' => array(
      'type' => 'int',
      'require' => true,
      'context' => array('type' => 'int', 'evaltrue' => true),
    ),
    'lastMessage' => array(
      'type' => 'int',
      'require' => false,
      'default' => 0,
      'context' => array('type' => 'int', 'evaltrue' => false),
    ),
    'lastUnreadMessage' => array(
      'type' => 'int',
      'require' => false,
      'default' => 0,
      'context' => array('type' => 'int', 'evaltrue' => false),
    ),
    'lastEvent' => array(
      'type' => 'int',
      'require' => false,
      'default' => 0,
      'context' => array('type' => 'int', 'evaltrue' => false),
    ),
  ));

  if (isset($_SERVER['HTTP_LAST_EVENT_ID'])) {
    $lastMessageId = $_SERVER['HTTP_LAST_EVENT_ID']; // Get the message ID used for keeping state data; e.g. 1-2-3
    $lastMessageIdParts = explode('-', $lastMessageId); // Get each state part; e.g. array(1, 2, 3)

    if (count($lastMessageIdParts) === 3) { // There must be three parts
      $request['lastMessage'] = (int) substr($lastMessageIdParts[0], 1);
      $request['lastUnreadMessage'] = (int) substr($lastMessageIdParts[1], 1);
      $request['lastEvent'] = (int) substr($lastMessageIdParts[2], 1);
    }
  }


  while (true) {
    $serverSentRetries++;

    $queryParts['messagesSelect']['columns'] = array(
      "{$sqlPrefix}messagesCached" => 'messageId, roomId, time, flag, userId, userName, userGroup, socialGroups, userFormatStart, userFormatEnd, avatar, defaultColor, defaultFontface, defaultHighlight, defaultFormatting, text',
    );
    $queryParts['messagesSelect']['conditions'] = array(
      'both' => array(
        array(
          'type' => 'e',
          'left' => array('type' => 'column', 'value' => 'roomId'),
          'right' => array('type' => 'int', 'value' => (int) $request['roomId']),
        ),
        array(
          'type' => 'gt',
          'left' => array('type' => 'column', 'value' => 'messageId'),
          'right' => array('type' => 'int', 'value' => (int) $request['lastMessage']),
        ),
      ),
    );
    $queryParts['messagesSelect']['sort'] = array(
      'messageId' => 'asc',
    );
    $queryParts['messagesSelect']['limit'] = false;



/*    $queryParts['missedSelect']['columns'] = array(
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
    $queryParts['missedSelect']['sort'] = false;
    $queryParts['missedSelect']['limit'] = false;



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
    $queryParts['eventsSelect']['sort'] = false;
    $queryParts['eventsSelect']['limit'] = false;



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
    $queryParts['unreadSelect']['sort'] = false;
    $queryParts['unreadSelect']['limit'] = false; */



    /* Get Messages */
    $messages = $database->select($queryParts['messagesSelect']['columns'],
      $queryParts['messagesSelect']['conditions'],
      $queryParts['messagesSelect']['sort'],
      $queryParts['messagesSelect']['limit']);
    $messages = $messages->getAsArray('messageId');
//    error_log(print_r($queryParts['messagesSelect']['conditions'],true));
//    error_log('EventStream Log: ' . print_r($request, true) . '          ' . print_r($messages->sourceQuery, true) . '          ' . print_r($messages, true));
//    error_log(print_r($messages->sourceQuery, true));

    if (is_array($messages)) {
      if (count($messages) > 0) {
        foreach ($messages AS $message) {
          $messagesOutput = array(
            'messageData' => array(
              'roomId' => (int) $message['roomId'],
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
            )
          );

          if ($message['messageId'] > $request['lastMessage']) $request['lastMessage'] = $message['messageId'];

          echo "event: message\n";
          echo "data: " . json_encode($messagesOutput) . "\n\n";

          fim_flush(); // This /should/ not be neccessary. I don't know why it is -- TODO.

          error_log('eventStream message: ' . json_encode($messagesOutput));
        }

        echo "id: m" . (int) $request['lastMessage'] . "-u" . (int) $request['lastUnreadMessage'] . "-e" . (int) $request['lastEvent'] . "\n\n";

        fim_flush();
        $outputStarted = true;

        unset($messages);
      }
    }




    /* Get New Message Alerts from Watched Rooms */
/*    if ($config['enableWatchRooms'] && isset($user['watchRooms'])) {
      if (count(fim_arrayValidate(explode(',', $user['watchRooms']), 'int', false)) > 0) {
        $missedMessages = $database->select(
          $queryParts['missedSelect']['columns'],
          $queryParts['missedSelect']['conditions'],
          $queryParts['missedSelect']['sort'],
          $queryParts['missedSelect']['limit']);
        $missedMessages = $missedMessages->getAsArray();

        if (is_array($missedMessages)) {
          if (count($missedMessages) > 0) {
            foreach ($missedMessages AS $message) {
              if (!fim_hasPermission($message, $user, 'view', true)) {
                continue;
              }

              echo "event: missedMessage\n";
              echo "data: " . json_encode($message) . "\n";
              echo "id: m" . (int) $request['lastMessage'] . "-u" . (int) $request['lastUnreadMessage'] . "-e" . (int) $request['lastEvent'] . ";\n\n";

              fim_flush();
              $outputStarted = true;
            }
          }
        }

        unset($missedMessages);
      }
    }*/




    /* Get Unread Private Messages */
/*    if ($config['enableUnreadMessages'] && $user['userId'] > 0) {
      $unreadMessages = $database->select(
        $queryParts['unreadSelect']['columns'],
        $queryParts['unreadSelect']['conditions'],
        $queryParts['unreadSelect']['sort'],
        $queryParts['unreadSelect']['limit']);
      $unreadMessages = $unreadMessages->getAsArray();

      if (is_array($unreadMessages)) {
        if (count($unreadMessages) > 0) {
          foreach ($unreadMessages AS $message) {
            echo "event: privateMessage\n";
            echo "data: " . json_encode($message) . "\n";
            echo "id: m" . (int) $request['lastMessage'] . "-u" . (int) $request['lastUnreadMessage'] . "-e" . (int) $request['lastEvent'] . ";\n\n";

            $request['lastUnreadMessage'] = $message['messageId'];

            fim_flush();
            $outputStarted = true;

            ($hook = hook('getMessages_watchRooms_eachRoom') ? eval($hook) : '');
          }
        }
      }

      unset($unreadMessages);
    }*/




    /* Get Events */
/*    if ($config['enableEvents']) {
      $events = $database->select($queryParts['eventsSelect']['columns'],
        $queryParts['eventsSelect']['conditions'],
        $queryParts['eventsSelect']['sort'],
        $queryParts['eventsSelect']['limit']);
      $events = $events->getAsArray('eventId');

      $eventsOutput = array();

      if (is_array($events)) {
        if (count($events) > 0) {
          foreach ($events AS $event) {
            echo "event: " . $event['eventName'] . "\n";
            echo "data: " . json_encode($event) . "\n";
            echo "id: m" . (int) $request['lastMessage'] . "-u" . (int) $request['lastUnreadMessage'] . "-e" . (int) $request['lastEvent'] . ";\n\n";

            $request['lastEvent'] = $event['eventId'];

            fim_flush();
            $outputStarted = true;

            ($hook = hook('getMessages_watchRooms_eachRoom') ? eval($hook) : '');
          }
        }
      }

      unset($events);
    }*/


    if ($config['dev']) {
      $time = date('r');
      echo "event: time\n";
      echo "data: {$time}\n\n";
      fim_flush();
    }




    if (($serverSentRetries > $config['serverSentMaxRetries'])
      || ($config['serverSentFastCGI'] && $outputStarted)) {
      echo "id: m" . (int) $request['lastMessage'] . "-u" . (int) $request['lastUnreadMessage'] . "-e" . (int) $request['lastEvent'] . "\n";
      echo "retry: 0\n";

      exit;
    }
    else {
      ($hook = hook('getMessages_postMessages_serverSentEvents_repeat') ? eval($hook) : '');

      usleep($config['serverSentEventsWait'] * 1000000); // Wait before re-requesting. usleep delays in microseconds (millionths of seconds).
    }
  }
}
?>