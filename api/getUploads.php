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

require_once('../global.php');


/* Get Request Data */
$request = fim_sanitizeGPC(array(
  'get' => array(
    'users' => array(
      'type' => 'string',
      'require' => true,
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
  'getUploads' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => $errStr,
    'errDesc' => $errDesc,
    'uploads' => array(),
  ),
);



/* Plugin Hook Start */
($hook = hook('getUploads_eachUpload_start') ? eval($hook) : '');



/* Get Uploads from Database */
/*$uploads = dbRows("SELECT v.fileId, f.mime, f.name, f.rating, v.md5hash
  FROM {$sqlPrefix}files AS f, {$sqlPrefix}fileVersions AS v
  WHERE f.userId = $user[userId] AND f.fileId = v.fileId",'fileId');*/
$uploads = $database->select(
  array(
    "{$sqlPrefix}files" => array(
      'fileId' => 'fileId',
      'fileName' => 'fileName',
      'fileType' => 'fileType',
      'creationTime' => 'creationTime',
      'userId' => 'userId',
      'rating' => 'rating',
      'flags' => 'flags',
      'sha256hash' => 'sha256hash',
    ),
    "{$sqlPrefix}fileVersions" => array(
      'vfileId' => 'vfileId',
    ),
  ),
  array(
    'both' => array(
      array(
        'type' => 'e'
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
$uploads = $uploads->getAsArray('fileId');



/* Start Processing */
if (is_array($uploads)) {
  if (count($uploads) > 0) {
    foreach ($uploads AS $file) {
      $xmlData['getUploads']['uploads']['upload ' . $file['fileId']] = array(
        'size' => (int) $file['size'],
        'sizeFormatted' => formatSize($file['size']),
        'name' => $file['name'],
        'mime' => $file['mime'],
        'rating' => $file['rating'],
        'md5hash' => $file['md5hash'],
      );

      ($hook = hook('getUploads_eachUpload') ? eval($hook) : '');
    }
  }
}



/* Plugin Hook End */
($hook = hook('getUploads_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);



/* Close Database Connection */
dbClose();
?>