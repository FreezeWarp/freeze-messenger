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
 * @param users - A comma-seperated list of user IDs to get. If not specified, all users will be retrieved.
*/

$apiRequest = true;

static $usersArray, $reverseOrder;

require_once('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC(array(
  'get' => array(
    'users' => array(
      'type' => 'string',
      'require' => false,
      'default' => '',
      'context' => array(
         'type' => 'csv',
         'filter' => 'int',
         'evaltrue' => true,
      ),
    ),

    'sort' => array(
      'type' => 'string',
      'valid' => array(
        'userId',
        'userName',
      ),
      'require' => false,
      'default' => 'userId',
    ),
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



/* Plugin Hook Start */
($hook = hook('getUsers_start') ? eval($hook) : '');



/* Get Users from Database */
$users = $slaveDatabase->select(
  array(
    "{$sqlPrefix}users" => array(
      'userId' => 'userId',
      'userName' => 'userName',
      'userFormatStart' => 'userFormatStart',
      'userFormatEnd' => 'userFormatEnd',
      'profile' => 'profile',
      'avatar' => 'avatar',
      'socialGroups' => 'socialGroups',
      'defaultColor' => 'defaultColor',
      'defaultHighlight' => 'defaultHighlight',
      'defaultFontface' => 'defaultFontface',
      'defaultFormatting' => 'defaultFormatting',
      'favRooms' => 'favRooms',
    ),
  ),
  false,
  array(
    'userId' => 'asc',
  )
);
$users = $users->getAsArray();



/* Start Processing */
if ($users) {
  foreach ($users AS $userData) {
    ($hook = hook('getUsers_eachUser_start') ? eval($hook) : '');


    switch ($loginMethod) {
      case 'vbulletin':
      $userDataForums = $integrationDatabase->select(
        array(
          $sqlUserTable => array(
            'joindate' => 'joinDate',
          ),
        ),
        array(
          'both' => array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => $sqlUserTableCols['userId']
            ),
            'right' => array(
              'type' => 'int',
              'value' => (int) $userData['userId'],
            ),
          ),
        ),
      );
      $userDataForums = $userDataForums->getAsArray();
      break;

      case 'phpbb':
      $userDataForums = $integrationDatabase->select(
        array(
          $sqlUserTable => array(
            'user_posts' => 'posts',
            'user_regdate' => 'joinDate',
          ),
        ),
        array(
          'both' => array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => $sqlUserTableCols['userId']
            ),
            'right' => array(
              'type' => 'int',
              'value' => (int) $userData['userId'],
            ),
          ),
        ),
      );
      $userDataForums = $userDataForums->getAsArray();
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
      'ignoreList' => ($userData['ignoreList']),
      'favRooms' => ($userData['favRooms']),
      'postCount' => (int) $userDataForums['posts'],
      'joinDate' => (int) $userDataForums['joinDate'],
      'joinDateFormatted' => (fim_date(false,$userDataForums['joinDate'])),
      'userTitle' => ($userDataForums['usertitle']),
    );


    ($hook = hook('getUsers_eachUser_end') ? eval($hook) : '');
  }
}



/* Update Data for Errors */
$xmlData['getUsers']['errStr'] = ($errStr);
$xmlData['getUsers']['errDesc'] = ($errDesc);



/* Plugin Hook End */
($hook = hook('getUsers_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);



/* Close Database Connection */
dbClose();
?>