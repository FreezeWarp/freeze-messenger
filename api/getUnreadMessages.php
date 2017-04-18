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

/**
 * Get the Active User's Unread Messages.
 * An "unread message" is created whenever a message is inserted into a room watched by a user, or a private room that that user is a part of, and the user does not appear to be online (according to the ping table/database->getActiveUsers()).
 * If a user is online, new messages in *private* rooms will instead be sent to the user's event stream. Due to possible backend limitations, messages in *watched* rooms that the user is not in (but is online) will still be recorded here. As a result, having a poll of getUnreadMessages.php, and a stream from events.php?user=x is recommended, the two attempt to be mutually exclusive. If the client does not support event streaming, there will be no way to query new messages to private rooms; they are not logged here, to avoid concerns with managing duplication.
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2014
 */

$apiRequest = true;

require('../global.php');





/* Data Predefine */
$xmlData = array(
    'getUnreadMessages' => array(
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



/* Get Unread Messages from Database */
if (!$user['userId']) {
    $errStr = 'loginRequired';
    $errDesc = 'You must be logged in to get your unread messages.';
}
elseif ($continue) {
    $unreadMessages = $database->select($queryParts['unreadMessages']['columns'],
                                        $queryParts['unreadMessages']['conditions'],
                                        $queryParts['unreadMessages']['sort']);
    $unreadMessages = $unreadMessages->getAsArray('messageId');
}



/* Start Processing */
if ($continue) {
    if (is_array($unreadMessages)) {
        if (count($unreadMessages) > 0) {
            foreach ($unreadMessages AS $unreadMessage) {
                $xmlData['getUnreadMessages']['unreadMessages']['unreadMessage ' . $unreadMessage['messageId']] = array(
                    'messageId' => (int) $unreadMessage['messageId'],
                    'senderId' => (int) $unreadMessage['senderId'],
                    'roomId' => (int) $unreadMessage['roomId'],
                );
            }
        }
    }
}



/* Update Data for Errors */
$xmlData['getMessages']['errStr'] = (string) $errStr;
$xmlData['getMessages']['errDesc'] = (string) $errDesc;



/* Output Data */
echo new apiData($xmlData);
?>