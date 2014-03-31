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
    'roomId' => array(
      'require' => true,
      'cast' => 'int',
      'evaltrue' => true,
    ),
    'streamType' => array(
      'require' => true,
      'valid' => array('messages', 'unreadMessages', 'events'),
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
      case 'messages': require('apiStream/messageStream.php'); break;
      case 'unreadMessages': require('apiStream/unreadStream.php'); break;
      case 'events': require('apiStream/eventStream.php'); break;
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