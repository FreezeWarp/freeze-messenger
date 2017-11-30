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
 *
 * @param string users - A comma-seperated list of user IDs to get.
 */

$apiRequest = true;

require('../global.php');


/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
    'users' => array(
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
$database->accessLog('getFiles', $request);



/* Data Pre-Define */
$xmlData['files'] = array();



/* Get Uploads from Database */
$files = $database->getFiles(array(
    'userIds' => $request['users']
), ['fileId' => 'asc'], 10, $request['page'])->getAsArray('fileId');



/* Start Processing */
foreach ($files AS $file) {
    // Only show if the user has permission.
    if ($file['roomIdLink'] && $file['userId'] != $user->id) { /* TODO: Test */
        if (!($database->hasPermission($user, $database->getRoom($file['roomIdLink'])) & fimRoom::ROOM_PERMISSION_VIEW)) continue;
    }

    $xmlData['files']['file ' . $file['fileId']] = array(
        'fileSize' => (int) $file['size'],
        'fileSizeFormatted' => fim_formatSize($file['size']),
        'fileName' => $file['fileName'],
        'parentalAge' => $file['parentalAge'],
        'parentalFlags' => explode(',', $file['parentalFlags']),
        'sha256hash' => $file['sha256hash'],
    );
}



if (fimConfig::$dev) $xmlData['request'] = $request;



/* Output Data */
echo new Http\ApiData($xmlData);
?>