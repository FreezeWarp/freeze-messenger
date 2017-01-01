<?php
/* FreezeMessenger Copyright © 2014 Joseph Todd Parsons

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


function stream_event($streamSource, $queryId, $lastEvent) {
  global $database;

  if ($streamSource === 'user') $events = $database->getUserEventsForId($queryId, $lastEvent)->getAsArray('eventId');
  elseif ($streamSource === 'room') $events = $database->getRoomEventsForId($queryId, $lastEvent)->getAsArray('eventId');

  if (count($events) > 0) {
    foreach ($events AS $eventId => $event) {
      if ($eventId > $lastEvent) $lastEvent = $eventId;

      echo "id: " . (int) $eventId . "\n";
      echo "event: " . $event['eventName'] . "\n";
      echo "data: " . json_encode($event) . "\n\n";

      fim_flush();
      $outputStarted = true;
    }

    fim_flush(); // Force the server to flush.
  }

  unset($events); // Free memory.

  return $lastEvent;
}


function stream_messages($roomId, $lastEvent) {
  global $database;

  $messages = $database->getMessages(array(
    'room' => new fimRoom($roomId),
    'messageIdStart' => $lastEvent + 1,
  ), array('messageId' => 'asc'))->getAsArray('messageId');


  foreach ($messages AS $messageId => $message) {
    if ($messageId > $lastEvent) $lastEvent = $messageId;

    echo "\nid: " . (int) $message['messageId'] . "\n";
    echo "event: message\n";
    echo "data: " . json_encode(array(
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
          'userNameFormat' => ($message['userNameFormat']),
          'defaultFormatting' => array(
            'color' => ($message['defaultColor']),
            'highlight' => ($message['defaultHighlight']),
            'fontface' => ($message['defaultFontface']),
            'general' => (int) $message['defaultFormatting']
          ),
        )
      )) . "\n\n";

    fim_flush(); // Force the server to flush.
  }

  unset($messages); // Free memory.

  return $lastEvent;
}



$streamRequest = true;
define('FIM_EVENTSOURCE', true);
require('global.php');

if (!$config['serverSentEvents']) {
  die('Not Supported');
}
else {
  /* Send Proper Headers */
  header('Content-Type: text/event-stream');
//  header('Content-Type: text/plain');
  header('Cache-Control: no-cache'); // recommended to prevent caching of event data.

  set_time_limit($config['serverSentTimeLimit']);

  $serverSentRetries = 0;


  /* Get Request Data */
  $request = fim_sanitizeGPC('g', array(
    'queryId' => array(
      'require' => true,
      'cast' => 'int',
      'evaltrue' => true,
    ),
    'streamType' => array(
      'require' => true,
      'valid' => array('messages', 'user', 'room'),
    ),
    'lastEvent' => array(
      'require' => false,
      'default' => 0,
      'cast' => 'int',
      'evaltrue' => false,
    )
  ));


  if (isset($_SERVER['HTTP_LAST_EVENT_ID'])) {
    $request['lastEvent'] = $_SERVER['HTTP_LAST_EVENT_ID']; // Get the message ID used for keeping state data; e.g. 1-2-3
  }


  while ($serverSentRetries < $config['serverSentMaxRetries']) {
    $serverSentRetries++;

    switch ($request['streamType']) {
      case 'messages': $request['lastEvent'] = stream_messages($request['queryId'], $request['lastEvent']);      break;
      case 'user':     $request['lastEvent'] = stream_event('user', $request['queryId'], $request['lastEvent']); break;
      case 'room':     $request['lastEvent'] = stream_event('room', $request['queryId'], $request['lastEvent']); break;
    }

    if ($config['dev']) {
      $time = date('r');
      echo "event: time\n";
      echo "data: {$time}\n\n";
      fim_flush();
    }

    usleep($config['serverSentEventsWait'] * 1000000); // Wait before re-requesting. usleep delays in microseconds (millionths of seconds).
  }
}

echo "retry: 0\n";
?>