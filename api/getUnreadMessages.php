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

/**
 * Get the Active User's Unread Messages
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
 * @todo Add user ID requirement
*/

$apiRequest = true;

require_once('../global.php');





/* Data Predefine */
$xmlData = array(
  'getUnreadMessages' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => ($errStr),
    'errDesc' => ($errDesc),
    'unreadMessages' => array(),
  ),
);

$queryParts['unreadMessages']['columns'] = array(
  "{$sqlPrefix}unreadMessages" => array(
    'userId' => 'userId',
    'senderId' => 'senderId',
    'roomId' => 'roomId',
    'messageId' => 'messageId',
  ),
);
$queryParts['unreadMessages']['conditions'] = array(
  'both' => array(
    array(
      'type' => 'e',
      'left' => array(
        'type' => 'column',
        'value' => 'userId',
      ),
      'right' => array(
        'type' => 'int',
        'value' => $user['userId'],
      )
    )
  )
);
$queryParts['unreadMessages']['sort'] = array(
  'messageId' => 'asc',
);



/* Plugin Hook Start */
($hook = hook('getUnreadMessages_start') ? eval($hook) : '');



/* Get Unread Messages from Database */
$unreadMessages = $database->select($queryParts['unreadMessages']['columns'],
  $queryParts['unreadMessages']['conditions'],
  $queryParts['unreadMessages']['sort']);
$unreadMessages = $unreadMessages->getAsArray('messageId');



/* Start Processing */
if (is_array($unreadMessages)) {
  if (count($unreadMessages) > 0) {
    foreach ($unreadMessages AS $unreadMessage) {
      $xmlData['getUnreadMessages']['unreadMessages']['unreadMessage ' . $unreadMessage['messageId']] = array(
        'messageId' => (int) $unreadMessage['messageId'],
        'senderId' => (int) $unreadMessage['senderId'],
        'roomId' => (int) $unreadMessage['roomId'],
      );

      ($hook = hook('getUnreadMessages_eachMessage') ? eval($hook) : '');
    }
  }
}



/* Update Data for Errors */
$xmlData['getMessages']['errStr'] = (string) $errStr;
$xmlData['getMessages']['errDesc'] = (string) $errDesc;



/* Plugin Hook End */
($hook = hook('getFonts_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);



/* Close Database Connection */
dbClose();
?>