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



    /* Get Messages */
    $messages = $database->select($queryParts['messagesSelect']['columns'],
      $queryParts['messagesSelect']['conditions'],
      $queryParts['messagesSelect']['sort'],
      $queryParts['messagesSelect']['limit']);
    $messages = $messages->getAsArray('messageId');

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