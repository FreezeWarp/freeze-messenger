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

/**
 * Get Messages from the Server
 * Works with both private and normal rooms.
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2014
 *
 * @param int roomId - The room ID.
 * @param string message - The message text, properly URLencoded.
 * @param string flag - A message content-type/context flag, used for sending images, urls, etc.
 * @param bool ignoreBlock - If true, the system will ignore censor warnings. You must pass this to resend a message that was denied because of a censor warning.
 */

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('p', array(
    'roomId' => array(
        'require' => true,
        'cast' => 'roomId',
    ),

    'message' => array(),

    'flag' => array(
        'valid' => array('image', 'video', 'url', 'email', 'html', 'audio', 'text', 'source', ''),
    ),

    'ignoreBlock' => array(
        'default' => false,
        'cast' => 'bool',
    ),
));
$database->accessLog('sendMessage', $request);


/* Get Room for DB */
$room = new fimRoom($request['roomId']);


/* Start Processing */
if (!$room->id)
    new fimError('badRoom'); // Room doesn't exist.

elseif (strlen($request['message']) < $config['messageMinLength'] || strlen($request['message']) > $config['messageMaxLength'])
    new fimError('messageLength', 'Minimum: ' . $config['messageMinLength'] . ', Maximum: ' . $config['messageMaxLength']); // Too short/long.

elseif (preg_match('/^(\ |\n|\r)*$/', $request['message']))
    new fimError('spaceMessage'); // All spaces. TODO: MB Support

elseif (!($database->hasPermission($user, $room) & ROOM_PERMISSION_POST))
    new fimError('noPerm');

elseif (in_array($request['flag'], array('image', 'video', 'url', 'html', 'audio'))
    && !filter_var($request['message'], FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED))
    new fimError('badUrl'); // If the message is supposed to be a URI, make sure it is. (We do this here and not at the function level to allow for plugins to override such a check).

elseif ($request['flag'] === 'email'
    && !filter_var($request['message'], FILTER_VALIDATE_EMAIL))
    new fimError('badUrl'); // If the message is suppoed to be an email, make sure it is. (We do this here and not at the function level to allow for plugins to override such a check).

elseif (strpos($request['message'], '/kick') === 0) { // TODO
    $kickData = preg_replace('/^\/kick (.+?)(| ([0-9]+?))$/i','$1,$2',$request['message']);
    $kickData = explode(',',$kickData);

    $userData = $database->getUsers(array(
        'userNames' => array($kickData[0])
    ))->getAsUser();

    $userData->kick($kickData[1]);
}

else {
    if (strpos($request['message'], '/topic') === 0 && ($database->hasPermission($user, $room) & ROOM_PERMISSION_TOPIC)) {
        $room->changeTopic(preg_replace('/^\/topic( |)(.+?)$/i', '$2', $request['message']));
    }

    $database->storeMessage($request['message'], $request['flag'], $user, $room, $request['ignoreBlock'], $censorMatches);
}





/* Data Define */
$xmlData = array(
    'sendMessage' => array(
        'censor' => $censorMatches
    ),
);



/* Output Data */
echo new apiData($xmlData);
?>