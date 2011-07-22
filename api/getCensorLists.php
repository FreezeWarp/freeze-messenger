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
 * Get All Censor Lists, Optionally With the Active Status in One or More Rooms
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
 *
 * @param string rooms - A comma-seperated list of room IDs to query for whether or not the list is active in that room.
 * @param string lists - A comma-seperated list of list IDs to filter by. If not specified all lists will be retrieved.
 *
 * @todo Implement room status.
*/

$apiRequest = true;

require_once('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC(array(
  'get' => array(
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
    'lists' => array(
      'type' => 'string',
      'require' => false,
      'default' => '',
      'context' => array(
         'type' => 'csv',
         'filter' => 'int',
         'evaltrue' => true,
      ),
    ),
  ),
));



/* Data Predefine */
$xmlData = array(
  'getCensorLists' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => ($errStr),
    'errDesc' => ($errDesc),
    'lists' => array(),
  ),
);

$queryParts['censorListsSelect']['columns'] = array(
  "{$sqlPrefix}censorLists" => array(
    'listId' => 'listId',
    'listName' => 'listName',
    'listType' => 'listType',
    'options' => 'listOptions',
  ),
);
$queryParts['censorListsSelect']['conditions'] = array();
$queryParts['censorListsSelect']['sort'] = array(
  'listName' => 'asc',
);



/* Modify Query Data for Directives */
if (count($request['lists']) > 0) {
  $queryParts['censorListsSelect']['conditions']['both'][] = array(
    'type' => 'in',
    'left' => array(
      'type' => 'column',
      'value' => 'listId',
    ),
    'right' => array(
       'type' => 'array',
       'value' => (array) $request['lists'],
    ),
  );
}



/* Plugin Hook Start */
($hook = hook('getCensorLists_start') ? eval($hook) : '');



/* Get Censor Lists from Slave Database */
$censorLists = $slaveDatabase->select($queryParts['censorListsSelect']['columns'],
  $queryParts['censorListsSelect']['conditions'],
  $queryParts['censorListsSelect']['sort']);
$censorLists = $censorLists->getAsArray('listId');



/* Start Processing */
if (count($censorLists) > 0) {
  foreach ($censorLists AS $list) { // Run through each censor list retrieved.
    $xmlData['getCensorLists']['lists']['list ' . $list['listId']] = array(
      'listId' => (int) $list['listId'],
      'listName' => ($list['listName']),
      'listType' => ($list['listType']),
      'listOptions' => (int) $list['listOptions'],
    );

    ($hook = hook('getCensorLists_eachList') ? eval($hook) : '');
  }
}



/* Update Data for Errors */
$xmlData['getCensorLists']['errStr'] = ($errStr);
$xmlData['getCensorLists']['errDesc'] = ($errDesc);



/* Plugin Hook End */
($hook = hook('getCensorLists_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);



/* Close Database */
dbClose();
?>