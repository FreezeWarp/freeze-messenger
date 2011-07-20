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
 * Edit a File's Status
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
 *
 * @param string action
 * @param integer fileId
*/

$apiRequest = true;

require_once('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC(array(
  'post' => array(
    'action' => array(
      'type' => 'string',
      'require' => true,
      'valid' => array(
        'delete',
        'undelete',
        'addFlag', // FIMv4
        'removeFlag', // FIMv4
        'requestAction', // FIMv4
        'parentalRating', // FIMv4
      ),
    ),

    'fileId' => array(
      'type' => 'string',
      'require' => false,
      'default' => 0,
      'context' => array(
        'type' => 'int',
      ),
    ),
  ),
));



/* Data Predefine */
$xmlData = array(
  'moderate' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => ($errStr),
    'errDesc' => ($errDesc),
    'response' => array(),
  ),
);



/* Start Processing */
switch ($request['action']) {
  case 'delete':
  if ($user['adminDefs']['modImages']) {
    $id = intval($_GET['imageId']);

    modLog('deleteImage',$id);

    dbQuery("UPDATE {$sqlPrefix}files SET deleted = 1 WHERE id = $id");

    echo container('Deleted','The image has been deleted.');
  }
  break;

  case 'undelete':
  if ($user['adminDefs']['modImages']) {
    $userId = intval($_GET['userId']);

    modLog('unbanuser',$userId);

    dbQuery("UPDATE {$sqlPrefix}users SET settings = IF(settings & 1,settings - 1,settings) WHERE userId = $userId");

    echo container('User Unbanned','The user has been unbanned.');
  }
  break;

  default:

  break;
}



/* Update Data for Errors */
$xmlData['moderate']['errStr'] = ($errStr);
$xmlData['moderate']['errDesc'] = ($errDesc);



/* Output Data */
echo fim_outputApi($xmlData);



/* Close Database Connection */
dbClose();
?>