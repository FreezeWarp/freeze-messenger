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

$apiRequest = true;

require('../global.php');

/* Get Request Data */
$request = \Fim\Utilities::sanitizeGPC('g', [
    'list' => [
        'valid'   => [
            'users', 'rooms', 'groups'
        ],
        'require' => true,
    ],

    'search' => [
        'cast'    => 'string',
        'require' => true,
    ],
]);
\Fim\Database::instance()->accessLog('acHelper', $request);


switch ($request['list']) {

    case 'users':
        $entries = new Http\ApiOutputList(\Fim\DatabaseSlave::instance()->getUsers([
            'userNameSearch' => $request['search'],
        ], null, 10)->getAsSlicedArray(['id', 'name', 'avatar'], null));
        break;

    case 'rooms':
        $entries = new Http\ApiOutputList(\Fim\DatabaseSlave::instance()->getRooms([
            'roomNameSearch' => $request['search'],
        ], null, 10)->getAsSlicedArray(['id', 'name']));
        break;

    case 'groups':
        $entries = new Http\ApiOutputList(\Fim\DatabaseSlave::instance()->getGroups([
            'groupNameSearch' => $request['search'],
        ], null, 10)->getAsSlicedArray(['id', 'name', 'avatar']));
        break;

}



/* Data Predefine */
$xmlData = [
    'entries' => $entries,
];


/* Output Data */
echo new Http\ApiData($xmlData);