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
 * Obtains All File Types Configured on the Server
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
*/

$apiRequest = true;

require('../global.php');



/* Data Pre-Define */
$xmlData = array(
  'getFileTypes' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => $errStr,
    'errDesc' => $errDesc,
    'fileTypes' => array(),
  ),
);

$queryParts['fileTypeSelect']['columns'] = array(
  "{$sqlPrefix}uploadTypes" => 'typeId, extension, mime, maxSize, container',
);
$queryParts['fileTypeSelect']['conditions'] = false;
$queryParts['fileTypeSelect']['sort'] = 'typeId';
$queryParts['fileTypeSelect']['limit'] = false;



/* Plugin Hook Start */
($hook = hook('getFileTypes_start') ? eval($hook) : '');



/* Get Uploads from Database */
if ($continue) {
  $fileTypes = $database->select(
    $queryParts['fileTypeSelect']['columns'],
    $queryParts['fileTypeSelect']['conditions'],
    $queryParts['fileTypeSelect']['sort'],
    $queryParts['fileTypeSelect']['limit']);
  $fileTypes = $fileTypes->getAsArray('typeId');
}



/* Start Processing */
if ($continue) {
  if (is_array($fileTypes)) {
    if (count($fileTypes) > 0) {
      foreach ($fileTypes AS $fileType) {
        $xmlData['getFileTypes']['fileTypes']['fileType ' . $fileType['typeId']] = array(
          'typeId' => (int) $fileType['typeId'],
          'extension' => $fileType['extension'],
          'mime' => $fileType['mime'],
          'maxSize' => (int) $fileType['maxSize'],
          'container' => $fileType['container'],
        );

        ($hook = hook('getFileTypes_eachFileType') ? eval($hook) : '');
      }
    }
  }
}



/* Plugin Hook End */
($hook = hook('getFileTypes_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);



/* Close Database Connection */
dbClose();
?>