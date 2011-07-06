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
 * Get Rooms from the Server
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
 * @param bool showDeleted - Will attempt to show deleted rooms, assuming the user has access to them (that is, is an administrator). Defaults to false.
 * @param bool showPrivate - Will show any private rooms of the user. Defaults to true.
 * @param string order - How the rooms should be ordered. Options include:
 * <ul>
 * <li>roomId - ID (Default)</li>
 * <li>roomName - Name</li>
 * <li>smart - Smart Sort (Official -> Nonofficial -> Private)</li>
 * </ul>
 * @param bool orderReverse - If true the above search will be descending, not ascending.
 * @param string rooms - If specified, only specific rooms are listed. By default, all rooms are listed.
*/

$apiRequest = true;

require_once('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC(array(
  'get' => array(
    'permLevel' => array(
      'type' => 'string',
      'default' => 'view',
      'valid' => array(
        'post',
        'view',
        'moderate',
        'know',
        'admin',
      ),
      'require' => false,
    ),

    'rooms' => array(
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
        'roomId',
        'roomName',
        'smart',
      ),
      'require' => false,
      'default' => 'roomId',
    ),

    'showDeleted' => array(
      'type' => 'string',
      'require' => false,
      'default' => false,
      'context' => array(
        'type' => 'bool',
      ),
    ),

    'reverseOrder' => array(
      'type' => 'string',
      'require' => false,
      'default' => false,
      'context' => array(
        'type' => 'bool',
      ),
    ),
  ),
));



/* Get User's Favourite Rooms as Array */
if (isset($user['favRooms'])) {
  $favRooms = fim_arrayValidate(explode(',',$user['favRooms']),'int',false); // All entries cast as integers, will not preserve entries of zero.
}
else {
  $favRooms = array();
}



/* Filter */
/*$whereClause = ($request['showDeleted'] ? '' : '(options & 4 = FALSE) AND ');
if ($rooms) {
  $whereClause .= ' roomId IN (' . implode(',',$roomsArray) . ') AND ';
}*/



/* Query Results Order
 * roomId*, roomName, smart */
/*switch ($request['sort']) {
  case 'roomId':
  $order = 'roomId ' . ($request['reverseOrder'] ? 'DESC' : 'ASC');
  break;

  case 'roomName':
  $order = 'roomName ' . ($request['reverseOrder'] ? 'DESC' : 'ASC');
  break;

  case 'smart':
  $order = '(options & 1) DESC, (options & 16) ASC';
  break;

  default:
  $order = 'roomId ' . ($request['reverseOrder'] ? 'DESC' : 'ASC');
  break;
}*/


/* Data Predefine */
$xmlData = array(
  'getRooms' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => $errStr,
    'errDesc' => $errDesc,
    'rooms' => array(),
  ),
);


/* Plugin Hook Start */
($hook = hook('getRooms_start') ? eval($hook) : '');



/* Get Rooms From Database */
$rooms = $database->select(
  array( // Columns
    "{$sqlPrefix}rooms" => array(
      'roomId' => 'roomId',
      'roomName' => 'roomName',
      'options' => 'options',
      'allowedUsers' => 'allowedUsers',
      'allowedGroups' => 'allowedGroups',
      'moderators' => 'moderators',
      'owner' => 'owner',
      'bbcode' => 'bbcode',
      'roomTopic' => 'roomTopic',
    )
  )
);
$rooms = $rooms->getAsArray();



/* Process Rooms Obtained from Database */
if (is_array($rooms)) {
  if (count($rooms) > 0) {
    foreach ($rooms AS $room) {
      $permissions = fim_hasPermission($room,$user,'all',false);

      if ($permissions[0][$request['permLevel']] === false) {
        continue;
      }
      else {
        $xmlData['getRooms']['rooms']['room ' . $room['roomId']] = array(
          'roomId' => (int)$room['roomId'],
          'roomName' => ($room['roomName']),
          'roomTopic' => ($room['roomTopic']),
          'roomOwner' => (int) $room['owner'],
          'allowedUsers' => ($room['allowedUsers']),
          'allowedGroups' => ($room['allowedGroups']),
          'moderators' => ($room['moderators']),
          'favorite' => (bool) (in_array($room['roomId'],$favRooms) ? true : false),
          'options' => (int) $room['options'],
          'optionDefinitions' => array(
            'official' => (bool) ($room['options'] & 1),
            'mature' => (bool) ($room['options'] & 2),
            'deleted' => (bool) ($room['options'] & 4),
            'hidden' => (bool) ($room['options'] & 8),
            'privateIm' => (bool) ($room['options'] & 16),
          ),
          'bbcode' => (int) $room['bbcode'],
          'permissions' => array(
            'canModerate' => (bool) $permissions[0]['moderate'],
            'canAdmin' => (bool) $permissions[0]['admin'],
            'canPost' => (bool) $permissions[0]['post'],
            'canView' => (bool) $permissions[0]['view'],
            'canKnow' => (bool) $permissions[0]['know'],
          ),
        );

        ($hook = hook('getRooms_eachRoom') ? eval($hook) : '');
      }
    }
  }
}



/* Errors */
$xmlData['getRooms']['errStr'] = ($errStr);
$xmlData['getRooms']['errDesc'] = ($errDesc);



/* Plugin Hook End */
($hook = hook('getRooms_end') ? eval($hook) : '');



/* Output Data Structure */
echo fim_outputApi($xmlData);



/* Close Database (Should be Automatic) */
dbClose();
?>