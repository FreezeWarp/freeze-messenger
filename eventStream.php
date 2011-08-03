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



    $queryParts['missedMessages']['columns'] = array(
      "{$sqlPrefix}rooms" => array(
        'roomId' => 'roomId',
        'options' => 'options',
        'lastMessageTime' => array(
          'context' => 'time',
          'name' => 'lastMessageTime',
        ),
      ),
      "{$sqlPrefix}ping" => array(
        'time' => array(
          'context' => 'time',
          'name' => 'pingTime',
        ),
        'userId' => 'puserId',
        'roomId' => 'proomId',
      ),
    );

    $queryParts['missedMessages']['conditions'] = array(
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
            'value' => fim_arrayValidate(explode(',',$user['watchRooms']),'int',false),
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



    $messages = $database->select($queryParts['messagesSelect']['columns'],
      $queryParts['messagesSelect']['conditions'],
      $queryParts['messagesSelect']['sort']);
    $messages = $messages->getAsArray('messageId');
    $messagesOutput = array();

    $missedMessages = $database->select(
      $queryParts['missedMessages']['columns'],
      $queryParts['missedMessages']['conditions']);
    $missedMessages = $missedMessages->getAsArray();// AND (r.allowedUsers REGEXP  OR r.allowedUsers = '*') AND IF(p.time, UNIX_TIMESTAMP(r.lastMessageTime) > (UNIX_TIMESTAMP(p.time) + 10), TRUE)


    if (is_array($missedMessages)) {
      if (count($missedMessages) > 0) {
        foreach ($missedMessages AS $message) {
          if (!fim_hasPermission($message,$user,'view',true)) {
            ($hook = hook('getMessages_watchRooms_noPerm') ? eval($hook) : '');

            continue;
          }

          $missedMessagesOutput = array(
            'roomId' => (int) $message['roomId'],
            'roomName' => ($message['roomName']),
            'lastMessageTime' => (int) $message['lastMessageTimestamp'],
          );

          echo "event: missedMessage\n";
          echo "data: " . json_encode($missedMessagesOutput) . "\n\n";
          flush();

          if (ob_get_level()) {
            ob_flush();
          }

          ($hook = hook('getMessages_watchRooms_eachRoom') ? eval($hook) : '');
        }
      }
    }


    if (is_array($messages)) {
      if (count($messages) > 0) {
        foreach ($messages AS $message) {
          $messagesOutput[] = array(
            'messageData' => array(
              'roomId' => (int) $message['roomId'],
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
    }


    ($hook = hook('getMessages_postMessages_serverSentEvents_repeat') ? eval($hook) : '');

    sleep(isset($config['serverSentEventsWait']) ? $config['serverSentEventsWait'] : 2);
  }
}
?>