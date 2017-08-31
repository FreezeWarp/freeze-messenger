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
        'cast' => 'list',
        'filter' => 'int',
        'evaltrue' => true,
        'default' => [],
    ),

    'userNames' => array(
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
        'valid' => array('userId', 'userName'),
        'default' => 'userId',
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
    if (count($request['userIds']) || count($request['userNames']))
        new fimError('idUserIdsUserNamesConflict', 'id can not be used with userIds and userNames.');

    $users = $userData;
}
else {
    $users = $slaveDatabase->getUsers(array(
        'userIds' => $request['userIds'],
        'userNames' => $request['userNames']
    ), array($request['sort'] => 'asc'))->getAsUsers();
}



/* Start Processing */
foreach ($users AS $userId => $userData) {
    $xmlData['users']['user ' . $userId] = array(
        'userName' => $userData->name,
        'userId' => $userData->id,
    );

    if (in_array('profile', $request['info'])) {
        $xmlData['users']['user ' . $userId]['avatar'] = $userData->avatar;
        $xmlData['users']['user ' . $userId]['profile'] = $userData->profile;
        $xmlData['users']['user ' . $userId]['userNameFormat'] = $userData->userNameFormat;
        $xmlData['users']['user ' . $userId]['messageFormatting'] = $userData->messageFormatting;

        if (isset($userDataForums[$userId]['posts'])) // TODO
            $xmlData['users']['user ' . $userId]['postCount'] = $userDataForums[$userId]['posts'];

        if (isset($userDataForums[$userId]['userTitle']))
            $xmlData['users']['user ' . $userId]['userTitle'] = $userDataForums[$userId]['userTitle'];

        $xmlData['users']['user ' . $userId]['joinDate'] = (int) (isset($userDataForums[$userId]['joinDate']) ? $userDataForums[$userId]['joinDate'] : $user->joinDate);
    }

    if (in_array('groups', $request['info'])) {
        $xmlData['users']['user ' . $userId]['mainGroupId'] = $userData->mainGroupId;
        $xmlData['users']['user ' . $userId]['socialGroupIds'] = new apiOutputList($userData->socialGroupIds);
    }

    if ((int) $userId === (int) $user->id
        && in_array('self', $request['info'])) {
        $xmlData['users']['user ' . $userId]['defaultRoomId'] = $userData->defaultRoomId;
        $xmlData['users']['user ' . $userId]['options'] = $userData->options;
        $xmlData['users']['user ' . $userId]['parentalAge'] = $userData->parentalAge;
        $xmlData['users']['user ' . $userId]['parentalFlags'] = new apiOutputList($userData->parentalFlags);
        $xmlData['users']['user ' . $userId]['ignoreList'] = $userData->ignoreList;
        $xmlData['users']['user ' . $userId]['favRooms'] = $userData->favRooms;
        $xmlData['users']['user ' . $userId]['watchRooms'] = $userData->watchRooms;
    }
}


/* Output Data */
echo new apiData($xmlData);
?>