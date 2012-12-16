<?php
/* FreezeMessenger Copyright © 2012 Joseph Todd Parsons

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
 * @copyright Joseph T. Parsons 2012
 *
 * @param string users - A comma-seperated list of user IDs to get.
*/

$apiRequest = true;

require('../global.php');


/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
  'users' => array(
    'default' => '',
    'context' => array(
      'type' => 'csv',
      'filter' => 'int',
      'evaltrue' => true,
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

$queryParts['fileSelect']['columns'] = array(
  "{$sqlPrefix}files" => 'fileId, fileName, fileType, creationTime, userId, parentalAge, parentalFlags',
  "{$sqlPrefix}fileVersions" => 'fileId vfileId, md5hash, sha256hash, size',
);
$queryParts['fileSelect']['conditions'] = array(
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
);
$queryParts['fileSelect']['sort'] = 'fileId';
$queryParts['fileSelect']['limit'] = false;



/* Plugin Hook Start */
($hook = hook('getFiles_start') ? eval($hook) : '');



/* Get Uploads from Database */
if ($continue) {
  $files = $database->select($queryParts['fileSelect']['columns'],
    $queryParts['fileSelect']['conditions'],
    $queryParts['fileSelect']['sort'],
    $queryParts['fileSelect']['limit']);
  $files = $files->getAsArray('fileId');
}



/* Start Processing */
if ($continue) {
  if (is_array($files)) {
    if (count($files) > 0) {
      foreach ($files AS $file) {
        $xmlData['getFiles']['files']['file ' . $file['fileId']] = array(
          'fileSize' => (int) $file['size'],
          'fileSizeFormatted' => fim_formatSize($file['size']),
          'fileName' => $file['fileName'],
          'mime' => $file['mime'],
          'parentalAge' => $file['parentalAge'],
          'parentalFlags' => explode(',', $file['parentalFlags']),
          'md5hash' => $file['md5hash'],
          'sha256hash' => $file['sha256hash'],
        );

        ($hook = hook('getFiles_eachFile') ? eval($hook) : '');
      }
    }
  }
}



/* Plugin Hook End */
($hook = hook('getFiles_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);
?>