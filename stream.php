<?php
/* FreezeMessenger Copyright © 2017 Joseph Todd Parsons

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

use Fim\Error;
use Fim\Room;

$apiRequest = true;
require('global.php');

/* Disable output buffering and compression */
ini_set('output_buffering', 'off'); // This one probably won't work, but eh.
ini_set('zlib.output_compression', false);
while (@ob_end_flush());

/* Send Proper Headers */
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache'); // recommended to prevent caching of event data.

@set_time_limit(\Fim\Config::$serverSentTimeLimit);

$serverSentRetries = 0;


/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
    'queryId' => array(
        'cast' => 'roomId',
        'evaltrue' => true,
    ),
    'streamType' => array(
        'require' => true,
        'valid' => array('user', 'room'),
    ),
    'lastEvent' => array(
        'default' => 0,
        'cast' => 'int',
    ),
    'lastMessage' => array(
        'default' => 0,
        'cast' => 'int',
    ),
    'fallback' => array(
        'default' => false,
        'cast' => 'bool'
    )
));

if (!\Fim\Config::$serverSentEvents && !$request['fallback']) {
    new \Fim\Error('fallbackMandatory', 'Fallback mode is required on this server.');
}

if ($request['streamType'] === 'room') {
    $room = new Room($request['queryId']);
    $request['queryId'] = $room->id;

    if (!(\Fim\Database::instance()->hasPermission($user, $room) & Room::ROOM_PERMISSION_VIEW))
        new \Fim\Error('noPerm', 'You are not allowed to view this room.'); // Don't have permission.

    \Fim\Database::instance()->markMessageRead($request['queryId'], $user->id);
}
elseif ($request['streamType'] === 'user') {
    $request['queryId'] = $user->id;
}


if (!$request['queryId']) {
    new \Fim\Error('queryIdRequired', 'You must specify a query ID.');
}


if (isset($_SERVER['HTTP_LAST_EVENT_ID'])) {
    $request['lastEvent'] = $_SERVER['HTTP_LAST_EVENT_ID']; // Get the message ID used for keeping state data; e.g. 1-2-3
}


if ($request['fallback']) {
    $messageResults = \Stream\StreamFactory::getDatabaseInstance()->subscribeOnce($request['streamType'] . '_' . $request['queryId'], $request['lastEvent']);

    echo new Http\ApiData(["events" => $messageResults]);
}
else {
    \Stream\StreamFactory::subscribe($request['streamType'] . '_' . $request['queryId'], $request['lastEvent'], function($message) use ($request) {
        if ($request['streamType'] === 'room') {
            if (isset($message['data']['id']) && $message['eventName'] === 'newMessage' && $message['data']['id'] <= $request['lastMessage']) return;
        }

        echo "\nid: " . (int)$message['id'] . "\n";
        echo "event: " . $message['eventName'] . "\n";
        echo "data: " . json_encode($message['data']) . "\n\n";
        fim_flush();
    });
}

if (!$request['fallback']) {
    echo "retry: 0\n";
}
?>
