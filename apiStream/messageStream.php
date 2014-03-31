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

if (defined('FIM_EVENTSOURCE')) {
  $messages = $database->getMessages(array(
      'roomIds' => array($request['roomId']),
      'messagesSince' => $request['lastEvent'],
    ), array('messageId' => 'asc'))->getAsArray('messageId');


  foreach ($messages AS $messageId => $message) {
    if ($messageId > $request['lastEvent']) $request['lastEvent'] = $messageId;

    echo "id: " . (int) $message['messageId'] . "\n";
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
        'startTag' => ($message['userFormatStart']),
        'endTag' => ($message['userFormatEnd']),
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
}
?>