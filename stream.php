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
    require_once('functions/StreamFactory.php');

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
            'valid' => array('messages', 'user', 'room'),
        ),
        'lastEvent' => array(
            'require' => false,
            'default' => 0,
            'cast' => 'int',
            'evaltrue' => false,
        )
    ));

    if ($request['streamType'] === 'messages') {
        if (!($database->hasPermission($user, new fimRoom($roomId)) & fimRoom::ROOM_PERMISSION_VIEW))
            new fimError('noPerm', 'You are not allowed to view this room.'); // Don't have permission.
    }


    if (isset($_SERVER['HTTP_LAST_EVENT_ID'])) {
        $request['lastEvent'] = $_SERVER['HTTP_LAST_EVENT_ID']; // Get the message ID used for keeping state data; e.g. 1-2-3
    }

    while ($messages = StreamFactory::subscribe($request['streamType'] . '_' . $request['queryId'], $request['lastEvent'])) {
        foreach ($messages AS $message) {
            //$database->markMessageRead($roomId, $user->id);
            echo "\nid: " . (int)$message['id'] . "\n";
            echo "event: " . $message['eventName'] . "\n";
            echo "data: " . json_encode($message['data']) . "\n\n";
            fim_flush();

            if ($message['id'] > $request['lastEvent']) $request['lastEvent'] = $message['id'];
        }
    }
}

echo "retry: 0\n";
?>