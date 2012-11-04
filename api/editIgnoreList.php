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
 * Edit's a User's Ignore List
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
 * TODO -- Optimise
*/

$apiRequest = true;

require('../global.php');


/* Get Request Data */
$request = fim_sanitizeGPC('p', array(
  'ignoredUserIds' => array(
    'context' => array(
      'type' => 'csv',
      'filter' => 'int',
      'evaltrue' => true,
    ),
  ),

  'method' => array(
    'context' => array(
      'allowedValues' => array('add', 'remove', 'replace'),
    ),
  ),
));


/* Data Predefine */
$xmlData = array(
  'editIgnoreList' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => ($errStr),
    'errDesc' => ($errDesc),
    'response' => array(),
  ),
);


/* Plugin Hook Start */
($hook = hook('editIgnoreList_start') ? eval($hook) : '');


switch ($request['method']) {
  case 'add':
  foreach ($request['ignoredUserId'] AS $ignoredUserId) {
    if ($slaveDatabase->getUser($ignoredUserId)) {
      $database->delete("{$sqlPrefix}ignoredUsers", array(
        'userId' => $user['userId'],
        'ignoredUserId' => $ignoredUserId,
      ));

      $database->insert("{$sqlPrefix}ignoredUsers", array(
        'userId' => $user['userId'],
        'ignoredUserId' => $ignoredUserId,
      ));
    }
    else {
      if ($slaveDatabase->getUser($ignoredUserId)) {
        unset($request['ignoreList'][$key]);
      }
    }
  }
  break;

  case 'remove':
  foreach ($request['ignoredUserId'] AS $ignoredUserId) {
    $database->delete("{$sqlPrefix}ignoredUsers", array(
      'userId' => $user['userId'],
      'ignoredUserId' => $ignoredUserId,
    ));
  }
  break;

  case 'replace':
  $database->delete("{$sqlPrefix}ignoredUsers", array(
    'userId' => $user['userId'],
  ));

  foreach ($request['ignoredUserId'] AS $ignoredUserId) {
    if ($slaveDatabase->getUser($ignoredUserId)) {
      $database->insert("{$sqlPrefix}ignoredUsers", array(
        'userId' => $user['userId'],
        'ignoredUserId' => $ignoredUserId,
      ));
    }
    else {
      if ($slaveDatabase->getUser($ignoredUserId)) {
        unset($request['ignoreList'][$key]);
      }
    }
  }
  break;
}


/* Plugin Hook Start */
($hook = hook('editIgnoreList_end') ? eval($hook) : '');
?>