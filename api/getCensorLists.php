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
 * Get All Censor Lists, Optionally With the Active Status in One or More Rooms
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 *
 * @param string rooms - A comma-seperated list of room IDs to query for whether or not the list is active in that room.
 * @param string lists - A comma-seperated list of list IDs to filter by. If not specified all lists will be retrieved.
 *
 * @todo Implement room status.
 */

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
    'listIds' => array(
        'default' => [],
        'cast' => 'list',
        'filter' => 'int',
        'evaltrue' => true,
    ),
    'roomId' => array(
        'cast' => 'roomId',
    ),
    'includeWords' => array(
        'default' => false,
        'filter' => 'bool',
    ),
));
$database->accessLog('getCensorLists', $request);


/* Data Predefine */
$xmlData = array(
    'lists' => array()
);



/* Get Censor Lists from Slave Database */
$censorLists = $slaveDatabase->getCensorLists(array(
    'listIds' => $request['listIds'],
    'includeStatus' => $request['roomId'],
))->getAsArray(array('listId'));

if ($request['includeWords']) {
    $censorWords = $slaveDatabase->getCensorWords(array(
        'listIds' => array_keys($censorLists),
    ))->getAsArray(array('listId', 'wordId'));
}



/* Start Processing */
foreach ($censorLists AS $listId => $list) { // Run through each censor list retrieved.
    if (!isset($xmlData['lists'][$list['listId']])) {
        $xmlData['lists'][$list['listId']] = array(
            'listId' => (int) $list['listId'],
            'listName' => ($list['listName']),
            'listType' => ($list['listType']),
            'listOptions' => (int) $list['options'],
            'words' => array(),
            'status' => $list['status'],
        );

        if ($request['includeWords']) {
            foreach($censorWords[$list['listId']] AS $wordId => $censorListWord) {
                $xmlData['lists'][$list['listId']]['words'][$censorListWord['wordId']] = array(
                    'wordId' => $censorListWord['wordId'],
                    'word' => $censorListWord['word'],
                    'severity' => $censorListWord['severity'],
                    'param' => $censorListWord['param'],
                );
            }
        }
    }
}



/* Output Data */
echo new Http\ApiData($xmlData);
?>