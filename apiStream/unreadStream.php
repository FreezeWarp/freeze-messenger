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


require('../global.php');


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
      'require' => true,
      'cast' => 'int',
      'evaltrue' => true,
    ),
    'lastMessage' => array(
      'require' => false,
      'default' => 0,
      'cast' => 'int',
      'evaltrue' => false,
    ),
    'lastUnreadMessage' => array(
      'require' => false,
      'default' => 0,
      'cast' => 'int',
      'evaltrue' => false,
    ),
    'lastEvent' => array(
      'require' => false,
      'default' => 0,
      'cast' => 'int',
      'evaltrue' => false,
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
    $queryParts['unreadSelect']['limit'] = false;




    /* Get Unread Private Messages */
    if ($config['enableUnreadMessages'] && $user['userId'] > 0) {
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
    }


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