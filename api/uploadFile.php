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


$validTypes = ($uploadMimes ? $uploadMimes :
  array('image/gif','image/jpeg','image/png','image/pjpeg','application/octet-stream'));

$validExts = ($uploadExtensions ? $uploadExtensions :
  array('gif','jpg','jpeg','png'));

$uploadMethod = $_POST['uploadMethod'];


$xmlData = array(
  'uploadFile' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'sentData' => array(),
    'errorcode' => $failCode,
    'errortext' => $failMessage,
    'upload' => array(),
  ),
);

switch ($uploadMethod) {
  case 'file':
  if (!in_array($_FILES['fileUpload']['type'],$validTypes)) {
    $failCode = 'badFileType';
    $failMessage = $phrases['uploadErrorBadType'];
  }
  elseif (!in_array($ext,$validExts) && $_FILES['fileUpload']['type'] == 'application/octet-stream') {
    $failCode = 'badFileType';
    $failMessage = $phrases['uploadErrorBadType'];
  }
  elseif ($_FILES['fileUpload']['size'] > 4 * 1000 * 1000) {
    $failCode = 'badFileSize';
    $failMessage = $phrases['uploadErrorSize'];
  }
  elseif ($_FILES['fileUpload']['error'] > 0) {
    $failCode = 'badFileUnknown';
    $failMessage = $phrases['uploadErrorOther'] . $_FILES['fileUpload']['error'];
  }
  else {
    $contents = file_get_contents($_FILES['fileUpload']['tmp_name']);
    $md5hash = md5($contents);

    $name = mysqlEscape($_FILES['fileUpload']['name']);
    $size = intval(strlen($contents));
    $mime = mysqlEscape($_FILES['fileUpload']['type']);

    $extParts = explode('.',$_FILES['fileUpload']['name']);
    $ext = $extParts[count($extParts) - 1];
  }
  break;

  case 'raw':
  $name = $_POST['file_name'];
  $data = $_POST['file_data'];
  $size = (int) $_POST['file_size'];
  $md5hash = $_POST['file_md5hash'];

  $dataEncode = $_POST['dataEncode'];

  switch($dataEncode) {
    case 'base64':
    $rawData = base64decode($data);
    break;

    default:
    $failCode = 'badRawEncoding';
    $failMessage = 'The specified file content encoding was not recognized.';

    $continue = false;
    break;
  }

  if ($md5hash) {
    if (md5($rawData) != $md5hash) {
      $failCode = 'badRawHash';
      $failMessage = 'The included MD5 hash did not match the file content.';

      $continue = false;
    }
  }

  if ($size) {
    if (strlen(md5($rawData)) != $size) {
      $failCode = 'badRawSize';
      $failMessage = 'The specified content length did not match the file content.';

      $continue = false;
    }
  }

  break;
}

if ($continue) {

  if ($encryptUploads) {
    list($contentsEncrypted,$iv,$saltNum) = fim_encrypt($contents);
    $contentsEncrypted = mysqlEscape($contentsEncrypted);
    $iv = mysqlEscape($iv);
    $saltNum = intval($saltNum);
  }
  else {
    $contentsEncrypted = mysqlEscape(base64_encode($contents));
    $iv = '';
    $saltNum = '';
  }

  if (!$contents) {
    $failMessage = $phrases['uploadErrorFileContents'];
  }
  else {
    $prefile = sqlArr("SELECT v.id, v.fileId FROM {$sqlPrefix}fileVersions AS v, {$sqlPrefix}files AS f WHERE v.md5hash = '$md5hash' AND v.fileId = f.id AND f.userId = $user[userId]");

    if ($prefile) {
      $webLocation = "{$installUrl}file.php?hash={$prefile[md5hash]}";

      $message = "[img]{$webLocation}[/img]";
    }
    else {
      mysqlQuery("INSERT INTO {$sqlPrefix}files (userId, name, size, mime) VALUES ($user[userId], '$name', '$size', '$mime')");
      $fileId = mysql_insert_id();

      mysqlQuery("INSERT INTO {$sqlPrefix}fileVersions (fileId, md5hash, salt, iv, contents) VALUES ($fileId, '$md5hash', '$saltNum', '$iv', '$contentsEncrypted')");

      $webLocation = "{$installUrl}file.php?hash={$md5hash}";

      if ($parseFlags) {
        $message = $webLocation;
      }
    }
  }
}


echo fim_outputXml($xmlData);

mysqlClose();
?>