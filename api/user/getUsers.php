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
 * Get Data on One or More Users
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 */


/* Prevent Direct Access of File */
if (!defined('API_INUSER'))
    die();



/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
    'userIds' => array(
        'conflict' => ['id'],
        'cast' => 'list',
        'filter' => 'int',
        'evaltrue' => true,
        'default' => [],
    ),

    'userNames' => array(
        'conflict' => ['id'],
        'cast' => 'list',
        'filter' => 'string',
        'default' => [],
    ),

    'showOnly' => array( // TODO
        'cast' => 'list',
        'valid' => array('banned', '!banned', '!friends', 'friends', '!ignored', 'ignored', ''),
        'default' => [],
    ),

    'sort' => array(
        'valid' => array('id', 'name'),
        'default' => 'id',
    ),

    'info' => array(
        'cast' => 'list',
        'valid' => array('profile', 'groups', 'self'),
        'default' => ["profile", "groups", "self"],
    ),
));

$database->accessLog('getUsers', $request);



/* Data Predefine */
$xmlData = array(
    'users' => array(),
);



/* Get Users from Database */
if (isset($userData)) { // From api/user.php
    $users = $userData;
}
else {
    $users = $slaveDatabase->getUsers(
        fim_arrayFilterKeys($request, ['userIds', 'userNames']),
        [$request['sort'] => 'asc']
    )->getAsUsers();
}



/* Start Processing */
foreach ($users AS $userId => $userData) {
    $returnFields = ['name', 'id'];

    if (in_array('profile', $request['info']))
        $returnFields = array_merge($returnFields, ['info', 'avatar', 'profile', 'nameFormat', 'messageFormatting', 'joinDate']);

    if (in_array('groups', $request['info']))
        $returnFields = array_merge($returnFields, ['mainGroupId', 'socialGroupIds']);

    if ($userData->id === $user->id
        && in_array('self', $request['info']))
        $returnFields = array_merge($returnFields, ['defaultRoomId', 'options', 'parentalAge', 'parentalFlags', 'ignoredUsers', 'friendedUsers', 'favRooms', 'watchRooms']);

    $xmlData['users']['user ' . $userId] = fim_objectArrayFilterKeys($userData, $returnFields);

        // todo
        //if (isset($userDataForums[$userId]['posts'])) // TODO
        //    $xmlData['users']['user ' . $userId]['postCount'] = $userDataForums[$userId]['posts'];

        //if (isset($userDataForums[$userId]['userTitle']))
        //    $xmlData['users']['user ' . $userId]['userTitle'] = $userDataForums[$userId]['userTitle'];
}


/* Output Data */
echo new apiData($xmlData);
?>