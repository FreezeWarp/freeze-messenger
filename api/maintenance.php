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

die('This feature will be added in FIMv4.');

/**
 * Performs a Maintenance Action
 * The user must be an administrator with maintenance priviledges, or the action will fail.
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2014
 *
 * @param string action - The maintenance action to perform.
 * @param int offset - The ID offset to be used on the task at hand.
 * @param int limit - The maximum number of results to process in this cycle.
*/

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('p', array(
  'action' => array(
    'require' => true,
    'valid' => array(
      'disableSystem',
      'enableSystem',
      'updatePostFormatCache',
      'updatePostCountCache',
    ),
  ),

  'offset' => array(
    'default' => 0,
    'context' => 'int',
  ),

  'limit' => array(
    'default' => 0,
    'context' => 'int',
  ),
));



/* Data Predefine */
$xmlData = array(
  'maintenance' => array(
    'response' => array(),
  ),
);



/* Start Processing */
if ($user['adminDefs']['modPrivs']) {
  switch ($request['action']) {
    case 'disableSystem':
    if (file_exists('.tempStop')) {
      echo container('Error','FIM has already been stopped.');
    }
    else {
      modLog('disable','');

      touch('.tempStop');
      echo container('','FIM has been stopped.');
    }
    break;

    case 'enableSystem':
    if (file_exists('.tempStop')) {
      modLog('enable','');

      unlink('.tempStop');
      echo container('','FIM has been re-enabled.');
    }
    else {
      echo container('Error','FIM is already running.');
    }
    break;

    case 'updatePostFormatCache':
    echo container('Error','Not yet coded.');
    break;

    case 'updatePostCountCache':
    $limit = 20;
    $offset = intval($_GET['page']) * $limit;
    $nextpage = intval($_GET['page']) + 1;

    $records = dbRows("SELECT * FROM {$sqlPrefix}ping LIMIT $limit OFFSET $offset",'id');
    foreach ($records AS $id => $record) {
      $totalPosts = dbRows("SELECT COUNT(m.id) AS count FROM {$sqlPrefix}messages AS m WHERE room = $record[roomId] AND user = $record[userId] AND m.deleted = false GROUP BY m.user");
      $totalPosts = intval($totalPosts['count']);
      dbQuery("UPDATE {$sqlPrefix}ping SET messages = $totalPosts WHERE id = $record[id]");
    }

    if ($records) {
      echo "<script type=\"text/javascript\">window.location = './moderate.php?do=maintenance&do2=postcountcache&page=$nextpage';</script>";
    }
    break;
  }
}
else {
  trigger_error('No permission.',E_USER_ERROR);
}



/* Update Data for Errors */
$xmlData['maintenance']['errStr'] = ($errStr);
$xmlData['maintenance']['errDesc'] = ($errDesc);



/* Output Data */
echo new apiData($xmlData);
?>