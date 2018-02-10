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
 * Get Data on One or More Users
 *
 * @package   fim3
 * @version   3.0
 * @author    Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 */


/* Prevent Direct Access of File */
if (!defined('API_INGROUP'))
    die();



/* Get Request Data */
$request = \Fim\Utilities::sanitizeGPC('g', [
    'groupIds' => [
        'cast'     => 'list',
        'filter'   => 'int',
        'evaltrue' => true,
        'default'  => [],
        'max'      => 50,
    ],

    'groupNames' => [
        'cast'     => 'list',
        'filter'   => 'string',
        'default'  => [],
        'max'      => 50,
    ],

    'sort' => [
        'valid'   => ['id', 'name'],
        'default' => 'id',
    ],
]);

\Fim\Database::instance()->accessLog('getGroups', $request);


/* Data Predefine */
$xmlData = [
    'groups' => [],
];



/* Get Users from Database */
if (isset($groupData)) { // From api/user.php
    $groups = [$groupData];
}
else {
    $groups = \Fim\DatabaseSlave::instance()->getGroups(
        \Fim\Utilities::arrayFilterKeys($request, ['groupIds', 'groupNames']),
        [$request['sort'] => 'asc']
    )->getAsArray(true);
}



/* Start Processing */
foreach ($groups AS $groupData) {
    $xmlData['groups'][$groupData['id']] = \Fim\Utilities::arrayFilterKeys($groupData, ['id', 'name']);
}


/* Output Data */
echo new Http\ApiData($xmlData);