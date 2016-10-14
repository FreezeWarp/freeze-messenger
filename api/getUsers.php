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
 * Get Data on One or More Users
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2014
 *
 * @param string [users] - A comma-seperated list of user IDs to get. If not specified, all users will be retrieved.
 * @param string [sort=userId] - How to sort the users, either by userId or userName.
 * @param string [showOnly] - A specific filter to apply to users that may be used for certain special tasks. "banned" specifies to show only users who have been banned. Prepending a bang ("!") to any value will reverse the filter - thus, "!banned" will only show users who have not been banned. It is possible to apply multiple filters by comma-seperating values.
*/

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
  'users' => array(
    'cast' => 'jsonList',
    'filter' => 'int',
    'evaltrue' => true,
    'default' => '',
  ),

  'userNames' => array(
    'cast' => 'jsonList',
    'filter' => 'string',
    'default' => '',
  ),

  'showOnly' => array( // TODO
    'cast' => 'jsonList',
    'valid' => array('banned', '!banned', '!friends', 'friends', '!ignored', 'ignored', ''),
    'default' => '',
  ),

  'sort' => array(
    'valid' => array('userId', 'userName'),
    'default' => 'userId',
  ),

  'info' => array(
    'cast' => 'jsonList',
    'valid' => array('profile', 'groups', 'self'),
    'default' => '["profile", "groups", "self"]',
  ),
));



/* Data Predefine */
$xmlData = array(
  'getUsers' => array(
    'users' => array(),
  ),
);



/* Get Users from Database */
$users = $slaveDatabase->getUsers(array(
  'userIds' => $request['users'],
  'userNames' => $request['userNames']
), array($request['sort'] => 'asc'))->getAsUsers();



/* Run Seperate Queries for Integration Methods
 * TODO: These should, long term, probably be plugins.
 * TODO: vB and PHPBB both broken. */
/*switch ($loginConfig['method']) {
  case 'vbulletin3': case 'vbulletin4':
  $userDataForums = $integrationDatabase->select(
    array(
      $sqlUserTable => array(
        'joindate' => 'joinDate',
        'posts' => 'posts',
        'usertitle' => 'userTitle',
        'lastvisit' => 'lastVisit',
        $sqlUserTableCols['userId'] => 'userId',
      ),
    ),
    array('both' => array('userId' => $this->in(array_keys($users))))
  )->getAsArray('userId');
  break;

  case 'phpbb':
  $userDataForums = $integrationDatabase->select(
    array(
      $sqlUserTable => array(
        'user_posts' => 'posts',
        'user_regdate' => 'joinDate',
        $sqlUserTableCols['userId'] => 'userId',
      ),
    ),
    array(
      array('both' => array('userId' => $this->in(array_keys($users))))
    )
  )->getAsArray('userId');
  break;

  case 'vanilla':
    $userDataForums = array(
      'joinDate' => $user['joinDate'],
      'posts' => false,
    );
  break;

  default:
  $userDataForums = array();
  break;
}*/


/* Start Processing */
foreach ($users AS $userId => $userData) {
  $xmlData['getUsers']['users']['user ' . $userId] = array(
    'userName' => $userData->name,
    'userId' => $userData->id,
  );

  if (in_array('profile', $request['info'])) {
    $xmlData['getUsers']['users']['user ' . $userId]['avatar'] = $userData->avatar;
    $xmlData['getUsers']['users']['user ' . $userId]['profile'] = $userData->profile;
    $xmlData['getUsers']['users']['user ' . $userId]['userNameFormat'] = $userData->userNameFormat;
    $xmlData['getUsers']['users']['user ' . $userId]['messageFormatting'] = $userData->messageFormatting;
//    $xmlData['getUsers']['users']['user ' . $userId]['postCount'] = (int) (isset($userDataForums[$userId]['posts']) ? $userDataForums[$userId]['posts'] : 0); TODO
//    $xmlData['getUsers']['users']['user ' . $userId]['joinDate'] = (int) (isset($userDataForums[$userId]['joinDate']) ? $userDataForums[$userId]['joinDate'] : 0);
//    $xmlData['getUsers']['users']['user ' . $userId]['userTitle'] = (isset($userDataForums[$userId]['userTitle']) ? $userDataForums[$userId]['userTitle'] : (isset($config['defaultUserTitle']) ? $config['defaultUserTitle'] :  ''));
  }

  if (in_array('groups', $request['info'])) {
    $xmlData['getUsers']['users']['user ' . $userId]['mainGroupId'] = $userData->mainGroupId;
    $xmlData['getUsers']['users']['user ' . $userId]['socialGroupIds'] = new apiOutputList($userData->socialGroupIds);
  }

  if ($userId === $user->id
    && in_array('self', $request['info'])) {
    $xmlData['getUsers']['users']['user ' . $userId]['defaultRoom'] = $userData->defaultRoom;
    $xmlData['getUsers']['users']['user ' . $userId]['options'] = $userData->options;
    $xmlData['getUsers']['users']['user ' . $userId]['parentalAge'] = $userData->parentalAge;
    $xmlData['getUsers']['users']['user ' . $userId]['parentalFlags'] = new apiOutputList($userData->parentalFlags);
//TODO        $xmlData['getUsers']['users']['user ' . $userId]['ignoreList'] = $userData['ignoreList'];
//TODO        $xmlData['getUsers']['users']['user ' . $userId]['favRooms'] = $userData['favRooms'];
//TODO        $xmlData['getUsers']['users']['user ' . $userId]['watchRooms'] = $userData['watchRooms'];
  }
}



/* Output Data */
echo new apiData($xmlData);
?>