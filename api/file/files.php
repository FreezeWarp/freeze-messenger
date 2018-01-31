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
 * Obtains One or More User's Uploads.
 * Uploads posted in a specific room can only be viewed by users with permission to view that room, and admins with modFiles.
 * Uploads not posted in a specific room can only be viewed by the users themselves, and admins with modFiles.
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 */


use Fim\ErrorThrown;
use Fim\Room;

if (!defined('API_INFILE'))
    die();



/* Get Request Data */
$request = \Fim\Utilities::sanitizeGPC('g', array(
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



/* Start Processing */
do {
    $filesQuery = \Fim\DatabaseSlave::instance()->getFiles([
        'fileIds' => $request['fileIds'],
        'userIds' => $request['userIds']
    ], ['id' => 'asc'], 10, $request['page']);

    foreach ($filesQuery->getAsObjects('\\Fim\\File') AS $file) {
        // Files can only be viewed by admins and the user themselves, and users with permission to view the room the file was posted in.
        if (!$user->hasPriv('modFiles') && $file->user->id != $user->id) {
            if (!$file->room || (!(\Fim\Database::instance()->hasPermission($user, $file->room) & Room::ROOM_PERMISSION_VIEW)))
                continue;
        }

        $xmlData['files']['file ' . $file->id] = array_merge([
            'userId' => $file->user->id,
            'roomId' => $file->room->id
        ], \Fim\Utilities::objectArrayFilterKeys($file, ['id', 'name', 'size', 'container', 'sha256Hash', 'webLocation']));
    }

    $request['page']++;

    // We relog so that the next query counts as part of the flood detection. (If we go over the flood limit, catch the exception and return with where to continue searching from.)
    try {
        \Fim\Database::instance()->accessLog('getFiles', $request);
    } catch (ErrorThrown $ex) {
        // TODO: test
        $xmlData['metadata']['nextPage'] = $request['page'];
        echo new Http\ApiData($xmlData);
    }
} while ($filesQuery->paginated && count($xmlData['rooms']) == 0);



if (\Fim\Config::$dev) $xmlData['request'] = $request;



/* Output Data */
echo new Http\ApiData($xmlData);
?>