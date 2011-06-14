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

$apiRequest = true;
require_once('../global.php');


$users = $_GET['users'];
$usersArray = explode(',',$users);
foreach ($usersArray AS &$v) {
  $v = intval($v);
}
$userList = implode(',',$usersArray);


$xmlData = array(
  'getUploads' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'sentData' => array(
      'rooms' => $userList,
      'resultLimit' => $resultLimit,
    ),
    'errorcode' => $failCode,
    'errortext' => $failMessage,
    'uploads' => array(),
  ),
);


($hook = hook('getUploads_eachUpload_start') ? eval($hook) : '');


$uploads = sqlArr("SELECT v.fileId, f.mime, f.size, f.name, f.rating, v.md5hash
  FROM {$sqlPrefix}files AS f, {$sqlPrefix}fileVersions AS v
  WHERE f.userId = $user[userId] AND f.id = v.fileId",'fileId');


if ($uploads) {
  foreach ($uploads AS $file) {
    $xmlData['getUploads']['uploads']['upload ' + $file['fileId']] = array(
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


($hook = hook('getUploads_end') ? eval($hook) : '');


echo fim_outputXml($xmlData);

mysqlClose();