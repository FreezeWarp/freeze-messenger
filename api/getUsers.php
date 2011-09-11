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
 * Get Data on One or More Users
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
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
    'context' => array(
      'type' => 'csv',
      'filter' => 'int',
      'evaltrue' => true,
    ),
  ),

  'showOnly' => array(
    'valid' => array(
      'banned', 'unbanned', 'friends', 'ignored', ''
    ),
    'default' => '',
  ),

  'sort' => array(
    'valid' => array(
      'userId', 'userName'
    ),
    'default' => 'userId',
  ),
));



/* Data Predefine */
$xmlData = array(
  'getUsers' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => ($errStr),
    'errDesc' => ($errDesc),
    'users' => array(),
  ),
);

$queryParts['userSelect']['columns'] = array(
  "{$sqlPrefix}users" => 'userId, userName, userFormatStart, userFormatEnd, profile, avatar, socialGroups, defaultColor, defaultHighlight, defaultFontface, defaultFormatting, favRooms, ignoreList, userGroup, options, defaultRoom, watchRooms',
);
$queryParts['userSelect']['conditions'] = false;
$queryParts['userSelect']['sort'] = array(
  'userId' => 'asc',
);
$queryParts['userSelect']['limit'] = false;




/* Modify Query Data for Directives */
switch ($request['showOnly']) {
  case 'banned':
  $queryParts['userSelect']['conditions']['both'][] = array(
    'type' => 'xor',
    'left' => array(
      'type' => 'column',
      'value' => 'options',
    ),
    'right' => array(
      'type' => 'int',
      'value' => 1,
    ),
  );
  break;

  case 'unbanned':
  $queryParts['userSelect']['conditions']['both'][] = array(
    'type' => 'and',
    'left' => array(
      'type' => 'column',
      'value' => 'options',
    ),
    'right' => array(
      'type' => 'int',
      'value' => 1,
    ),
  );
  break;
}
if (count($request['users']) > 0) {
  $queryParts['userSelect']['conditions']['both'][] = array(
    'type' => 'in',
    'left' => array(
      'type' => 'column',
      'value' => 'userId',
    ),
    'right' => array(
      'type' => 'array',
      'value' => $request['users'],
    ),
  );
}



/* Query Results Order
 * userId*, userName */
switch ($request['sort']) {
  case 'userName':
  $queryParts['userSelect']['sort'] = array(
    'userName' => 'asc',
  );
  break;

  case 'userId':
  default:
  $queryParts['userSelect']['sort'] = array(
    'userId' => 'asc',
  );
  break;
}



/* Plugin Hook Start */
($hook = hook('getUsers_start') ? eval($hook) : '');



/* Get Users from Database */
$users = $slaveDatabase->select(
  $queryParts['userSelect']['columns'],
  $queryParts['userSelect']['conditions'],
  $queryParts['userSelect']['sort'],
  $queryParts['userSelect']['limit']
);
$users = $users->getAsArray();



/* Start Processing */
if (is_array($users)) {
  if (count($users) > 0) {
    foreach ($users AS $userData) {
      ($hook = hook('getUsers_eachUser_start') ? eval($hook) : '');


      switch ($loginConfig['method']) {
        case 'vbulletin3':
        case 'vbulletin4':
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
          array(
            'both' => array(
              array(
                'type' => 'e',
                'left' => array(
                  'type' => 'column',
                  'value' => 'userId',
                ),
                'right' => array(
                  'type' => 'int',
                  'value' => (int) $userData['userId'],
                ),
              ),
            ),
          )
        );
        $userDataForums = $userDataForums->getAsArray(false);
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
            'both' => array(
              array(
                'type' => 'e',
                'left' => array(
                  'type' => 'column',
                  'value' => 'userId',
                ),
                'right' => array(
                  'type' => 'int',
                  'value' => (int) $userData['userId'],
                ),
              ),
            ),
          )
        );
        $userDataForums = $userDataForums->getAsArray(false);
        break;

        case 'vanilla':
        $userDataForums = array(
          'joinDate' => false,
          'posts' => false,
        );
        break;

        default:
        $userDataForums = array();
        break;
      }


      ($hook = hook('getUsers_eachUser_postForums') ? eval($hook) : '');


      $xmlData['getUsers']['users']['user ' . $userData['userId']] = array(
        'userName' => ($userData['userName']),
        'userId' => (int) $userData['userId'],
        'userGroup' => (int) $userData['userGroup'],
        'avatar' => ($userData['avatar']),
        'profile' => ($userData['profile']),
        'socialGroups' => ($userData['socialGroups']),
        'startTag' => ($userData['userFormatStart']),
        'endTag' => ($userData['userFormatEnd']),
        'defaultFormatting' => array(
          'color' => ($userData['defaultColor']),
          'highlight' => ($userData['defaultHighlight']),
          'fontface' => ($userData['defaultFontface']),
          'general' => (int) $userData['defaultFormatting']
        ),
        'postCount' => (int) (isset($userDataForums['posts']) ? $userDataForums['posts'] : 0),
        'joinDate' => (int) (isset($userDataForums['joinDate']) ? $userDataForums['joinDate'] : 0),
        'userTitle' => (isset($userDataForums['userTitle']) ? $userDataForums['userTitle'] :
          (isset($config['defaultUserTitle']) ? $config['defaultUserTitle'] :  '')),
      );

      if ($userData['userId'] === $user['userId']) {
        $xmlData['getUsers']['users']['user ' . $userData['userId']]['defaultRoom'] = $userData['defaultRoom'];
        $xmlData['getUsers']['users']['user ' . $userData['userId']]['options'] = $userData['options'];
        $xmlData['getUsers']['users']['user ' . $userData['userId']]['ignoreList'] = $userData['ignoreList'];
        $xmlData['getUsers']['users']['user ' . $userData['userId']]['favRooms'] = $userData['favRooms'];
        $xmlData['getUsers']['users']['user ' . $userData['userId']]['watchRooms'] = $userData['watchRooms'];
      }


      ($hook = hook('getUsers_eachUser_end') ? eval($hook) : '');
    }
  }
}



/* Update Data for Errors */
$xmlData['getUsers']['errStr'] = ($errStr);
$xmlData['getUsers']['errDesc'] = ($errDesc);



/* Plugin Hook End */
($hook = hook('getUsers_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);
?>