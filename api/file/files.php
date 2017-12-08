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
 * Obtains One or More User's Uploads
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 */


if (!defined('API_INFILE'))
    die();



/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
    'fileIds' => [
        'default'  => [],
        'cast'     => 'list',
        'filter'   => 'roomId',
        'evaltrue' => true,
        'max'      => 50,
    ],

    'userIds' => array(
        'default' => array($user->id),
        'cast' => 'list',
        'filter' => 'int',
        'evaltrue' => true,
        'max' => 10,
    ),

    'page' => [
        'cast' => 'int'
    ]
));

\Fim\Database::instance()->accessLog('getFiles', $request);



/* Data Pre-Define */
$xmlData['files'] = array();



/* Get Uploads from Database */
$files = \Fim\DatabaseSlave::instance()->getFiles([
    'fileIds' => $request['fileIds'],
    'userIds' => $request['userIds']
], ['id' => 'asc'], 10, $request['page'])->getAsObjects('\\Fim\\File');


/* Start Processing */
foreach ($files AS $file) {
    // Only show if the user has permission.
    if ($file->room && $file->user->id != $user->id) { /* TODO: Test */
        if (!(\Fim\Database::instance()->hasPermission($user, $file->room) & fimRoom::ROOM_PERMISSION_VIEW)) continue;
    }

    $xmlData['files']['file ' . $file->id] = fim_objectArrayFilterKeys($file, ['name', 'size', 'container', 'sha256Hash', 'webLocation']);
}



if (fimConfig::$dev) $xmlData['request'] = $request;



/* Output Data */
echo new Http\ApiData($xmlData);
?>