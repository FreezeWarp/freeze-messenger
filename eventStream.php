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


require_once('global.php');

if (!$config['serverSentEvents']) {
  die('Not Supported');
}
else {
  /* Send Proper Headers */
  header('Content-Type: text/event-stream');
  header('Cache-Control: no-cache'); // recommended to prevent caching of event data.


  $lastMessage = 0;


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
    ),
  ));

  while (true) {
    $queryParts['messagesSelect']['columns'] = array(
      "{$sqlPrefix}messagesCached" => array(
        'messageId' => 'messageId',
        'roomId' => 'roomId',
        'time' => array(
          'context' => 'time',
          'name' => 'time',
        ),
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
            'value' => (int) $lastMessage,
          ),
        ),
      ),
    );
    $queryParts['messagesSelect']['sort'] = array(
      'messageId' => 'asc',
    );

    $messages = $database->select($queryParts['messagesSelect']['columns'],
      $queryParts['messagesSelect']['conditions'],
      $queryParts['messagesSelect']['sort']);
    $messages = $messages->getAsArray('messageId');
    $messagesOutput = array();

    if (count($messages) > 0) {
      foreach ($messages AS $message) {
        $messagesOutput[] = array(
          'messageData' => array(
            'roomId' => (int) $room['roomId'],
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

        if ($message['messageId'] > $lastMessage) {
          $lastMessage = $message['messageId'];
        }

        echo "event: message\n";
        echo "data: " . json_encode($messagesOutput) . "\n\n";
        flush();

        if (ob_get_level()) {
          ob_flush();
        }
      }
    }


    ($hook = hook('getMessages_postMessages_serverSentEvents_repeat') ? eval($hook) : '');

    sleep(isset($config['serverSentEventsWait']) ? $config['serverSentEventsWait'] : 2);
  }
}
?>