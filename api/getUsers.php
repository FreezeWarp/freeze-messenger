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
      'ignoreList' => 'ignoreList',
      'userGroup' => 'userGroup',
    ),
  ),
  false,
  array(
    'userId' => 'asc',
  )
);
$users = $users->getAsArray();



/* Start Processing */
if (is_array($users)) {
  if (count($users) > 0) {
    foreach ($users AS $userData) {
      ($hook = hook('getUsers_eachUser_start') ? eval($hook) : '');


      switch ($loginConfig['method']) {
        case 'vbulletin3':
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
        'ignoreList' => ($userData['ignoreList']),
        'favRooms' => ($userData['favRooms']),
        'postCount' => (int) $userDataForums['posts'],
        'joinDate' => (int) $userDataForums['joinDate'],
        'joinDateFormatted' => (fim_date(false,$userDataForums['joinDate'])),
        'userTitle' => (isset($userDataForums['userTitle']) ? $userDataForums['userTitle'] :
          (isset($config['defaultUserTitle']) ? $config['defaultUserTitle'] :  '')),
      );


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



/* Close Database Connection */
dbClose();
?>