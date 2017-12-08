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
 * Send or update a message.
 *
 * @global    $message \Fim\Message
 * @global    $room    fimRoom
 * @package   fim3
 * @version   3.0
 * @author    Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 */


/* Prevent Direct Access of File */
if (!defined('API_INMESSAGE'))
    die();


/* Get Request Data */
$request = fim_sanitizeGPC('p', [
    'message' => [
        'require' => true,
    ],

    'flag' => [
        'default' => '',
        'valid'   => ['image', 'video', 'url', 'email', 'html', 'audio', 'text', 'source', ''],
    ],

    'ignoreBlock' => [
        'default' => false,
        'cast'    => 'bool',
    ],
]);


/* Logging */
\Fim\Database::instance()->accessLog('sendMessage', $request);


/* Start Processing */
if (strlen($request['message']) < \Fim\Config::$messageMinLength || strlen($request['message']) > \Fim\Config::$messageMaxLength)
    new fimError('messageLength', "The message is too long/too short.", [
        "minLength" => \Fim\Config::$messageMinLength,
        "maxLength" => \Fim\Config::$messageMaxLength
    ]); // Too short/long.

elseif (preg_match('/^(\ |\n|\r)*$/', $request['message']))
    new fimError('spaceMessage', 'The sent message is all whitespace.'); // All spaces. TODO: MB Support

elseif (!(\Fim\Database::instance()->hasPermission($user, $room) & fimRoom::ROOM_PERMISSION_POST))
    new fimError('noPerm', 'You may not post in this room.');

elseif (in_array($request['flag'], ['image', 'video', 'url', 'html', 'audio'])
    && !filter_var($request['message'], FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED))
    new fimError('badUrl', 'The sent URL is invalid.'); // If the message is supposed to be a URI, make sure it is. (We do this here and not at the function level to allow for plugins to override such a check).

elseif ($request['flag'] === 'email'
    && !filter_var($request['message'], FILTER_VALIDATE_EMAIL))
    new fimError('badEmail', 'The sent email is invalid.'); // If the message is suppoed to be an email, make sure it is. (We do this here and not at the function level to allow for plugins to override such a check).

else {
    \Fim\Database::instance()->setUserStatus($room->id); // The user seems active to me...

    switch ($requestHead['_action']) {
        case 'edit':
            if ($message->text == $request['message'] && $message->flag == $request['flag'])
                new fimError('noChange', 'Your edited message is unchanged.');

            elseif ($message->user->id != $user->id || !$user->hasPriv('editOwnPosts'))
                new fimError('noPerm', 'You are not allowed to edit this message.');

            else {
                $message->setText($request['message'], $request['ignoreBlock']);
                $message->setFlag($request['flag']);
                \Fim\Database::instance()->updateMessage($message);
            }
        break;

        case 'create':
            // if /kick starts the message, the user is using a shorthand to kick a user. We don't actually create a new message, but we do attempt to kick the user given.
            if (strpos($request['message'], '/kick') === 0
                && (\Fim\Database::instance()->hasPermission($user, $room) & fimRoom::ROOM_PERMISSION_MODERATE)) {
                $kickData = preg_replace('/^\/kick (.+?)(| ([0-9]+?))$/i', '$1,$2', $request['message']);
                $kickData = explode(',', $kickData);

                $userData = \Fim\Database::instance()->getUsers([
                    'userNames' => [$kickData[0]]
                ])->getAsUser();

                if ($userData)
                    new fimError('kickUserNameInvalid', 'That username does not exist.');
                else
                    \Fim\Database::instance()->kickUser($userData->id, $room->id, $kickData[1] ?: 600);
            }

            else {
                $message = new \Fim\Message([
                    'room'     => $room,
                    'user'     => $user,
                    'text'        => $request['message'],
                    'flag'        => $request['flag'],
                    'ignoreBlock' => $request['ignoreBlock']
                ]);


                // if /topic starts the message, the user is trying to change the topic.
                if (strpos($message->text, '/topic') === 0) {
                    if (\Fim\Database::instance()->hasPermission($user, $room) & fimRoom::ROOM_PERMISSION_TOPIC)
                        $room->changeTopic(preg_replace('/^\/topic( |)(.+?)$/i', '$2', $message->text));
                    else
                        new fimError('noPerm', 'You do not have permission to change the topic.');
                }
                else {
                    \Fim\Database::instance()->storeMessage($message);
                }
            }
        break;
    }
}





/* Data Define */
$xmlData = [
    'message' => [
        'id'     => $message->id,
        'censor' => $message->censorMatches
    ],
];



/* Output Data */
echo new Http\ApiData($xmlData);
?>