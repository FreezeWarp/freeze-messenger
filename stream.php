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
$apiRequest = true;
require('global.php');

if (!$config['serverSentEvents']) {
    die('Not Supported');
}
else {
    require_once(__DIR__ . '/functions/Stream/StreamFactory.php');

    /* Possibly Helpful:
	ini_set('output_buffering', 'off');
	ini_set('zlib.output_compression', false);
    while (@ob_end_flush()); */

    /* Send Proper Headers */
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache'); // recommended to prevent caching of event data.

    @set_time_limit($config['serverSentTimeLimit']);

    $serverSentRetries = 0;


    /* Get Request Data */
    $request = fim_sanitizeGPC('g', array(
        'queryId' => array(
            'require' => true,
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
        )
    ));

    if ($request['streamType'] === 'room') {
        if (!($database->hasPermission($user, new fimRoom($request['queryId'])) & fimRoom::ROOM_PERMISSION_VIEW))
            new fimError('noPerm', 'You are not allowed to view this room.'); // Don't have permission.

        $database->markMessageRead($request['queryId'], $user->id);
    }


    if (isset($_SERVER['HTTP_LAST_EVENT_ID'])) {
        $request['lastEvent'] = $_SERVER['HTTP_LAST_EVENT_ID']; // Get the message ID used for keeping state data; e.g. 1-2-3
    }


    StreamFactory::subscribe($request['streamType'] . '_' . $request['queryId'], $request['lastEvent'], function($message) use ($request) {
        if ($request['streamType'] === 'room') {
            if (isset($message['data']['id']) && $message['data']['id'] <= $request['lastMessage']) return;
        }

        echo "\nid: " . (int)$message['id'] . "\n";
        echo "event: " . $message['eventName'] . "\n";
        echo "data: " . json_encode($message['data']) . "\n\n";
        fim_flush();
    });
}

echo "retry: 0\n";
?>