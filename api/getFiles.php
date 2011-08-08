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
 * Obtains One or More User's Uploads
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
 *
 * @param string users - A comma-seperated list of user IDs to get.
*/

$apiRequest = true;

require('../global.php');


/* Get Request Data */
$request = fim_sanitizeGPC(array(
  'get' => array(
    'users' => array(
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



/* Data Pre-Define */
$xmlData = array(
  'getFiles' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => $errStr,
    'errDesc' => $errDesc,
    'files' => array(),
  ),
);



/* Plugin Hook Start */
($hook = hook('getFiles_start') ? eval($hook) : '');



/* Get Uploads from Database */
/*$files = dbRows("SELECT v.fileId, f.mime, f.name, f.rating, v.md5hash
  FROM {$sqlPrefix}files AS f, {$sqlPrefix}fileVersions AS v
  WHERE f.userId = $user[userId] AND f.fileId = v.fileId",'fileId');*/
$files = $database->select(
  array(
    "{$sqlPrefix}files" => array(
      'fileId' => 'fileId',
      'fileName' => 'fileName',
      'fileType' => 'fileType',
      'creationTime' => 'creationTime',
      'userId' => 'userId',
      'rating' => 'rating',
      'flags' => 'flags',
    ),
    "{$sqlPrefix}fileVersions" => array(
      'fileId' => 'vfileId',
      'md5hash' => 'md5hash',
      'sha256hash' => 'sha256hash',
    ),
  ),
  array(
    'both' => array(
      array(
        'type' => 'e',
        'left' => array(
          'type' => 'column',
          'value' => 'fileId',
        ),
        'right' => array(
          'type' => 'column',
          'value' => 'vfileId',
        ),
      ),
    ),
  )
);
$files = $files->getAsArray('fileId');



/* Start Processing */
if (is_array($files)) {
  if (count($files) > 0) {
    foreach ($files AS $file) {
      $xmlData['getFiles']['files']['file ' . $file['fileId']] = array(
        'fileSize' => (int) $file['fileSize'],
        'fileSizeFormatted' => formatSize($file['fileSize']),
        'fileName' => $file['fileName'],
        'mime' => $file['mime'],
        'rating' => $file['rating'],
        'md5hash' => $file['md5hash'],
        'sha256hash' => $file['sha256hash'],
      );

      ($hook = hook('getFiles_eachUpload') ? eval($hook) : '');
    }
  }
}



/* Plugin Hook End */
($hook = hook('getFiles_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);



/* Close Database Connection */
dbClose();
?>