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
 * @global $slaveDatabase fimDatabase
 */

$apiRequest = true;

require('../global.php');

/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
  'list' => array(
    'valid' => array(
      'users', 'rooms',
    ),
    'require' => true,
  ),

  'search' => array(
    'cast' => 'string',
    'require' => true,
  ),
));
$database->accessLog('acHelper', $request);


switch ($request['list']) {

  case 'users':
    $entries = new Http\ApiOutputDict($slaveDatabase->getUsers(array(
      'userNameSearch' => $request['search'],
    ), null, 10)->getColumnValues('name', 'id'));
    break;

  case 'rooms':
    $entries = new Http\ApiOutputDict($slaveDatabase->getRooms(array(
      'roomNameSearch' => $request['search'],
    ), null, 10)->getColumnValues('name', 'id'));
     break;

}



/* Data Predefine */
$xmlData = array(
    'entries' => $entries,
);


/* Output Data */
echo new Http\ApiData($xmlData);
?>