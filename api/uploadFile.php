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
require_once('../functions/parserFunctions.php');


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
    'errStr' => $errStr,
    'errDesc' => $errDesc,
    'upload' => array(),
  ),
);


switch ($uploadMethod) {
  case 'file':
  if (!in_array($_FILES['fileUpload']['type'],$validTypes)) {
    $errStr = 'badFileType';
    $errDesc = $phrases['uploadErrorBadType'];
  }
  elseif (!in_array($ext,$validExts) && $_FILES['fileUpload']['type'] == 'application/octet-stream') {
    $errStr = 'badFileType';
    $errDesc = $phrases['uploadErrorBadType'];
  }
  elseif ($_FILES['fileUpload']['size'] > 4 * 1000 * 1000) {
    $errStr = 'badFileSize';
    $errDesc = $phrases['uploadErrorSize'];
  }
  elseif ($_FILES['fileUpload']['error'] > 0) {
    $errStr = 'badFileUnknown';
    $errDesc = $phrases['uploadErrorOther'] . $_FILES['fileUpload']['error'];
  }
  else {
    $contents = file_get_contents($_FILES['fileUpload']['tmp_name']);
    $md5hash = md5($contents);

    $name = $_FILES['fileUpload']['name'];
    $size = intval(strlen($contents));
    $mime = $_FILES['fileUpload']['type'];

    $extParts = explode('.',$_FILES['fileUpload']['name']);
    $ext = $extParts[count($extParts) - 1];
  }
  break;

  case 'raw':
  $name = fim_urldecode($_POST['file_name']);
  $data = fim_urldecode($_POST['file_data']);
  $size = (int) $_POST['file_size'];
  $md5hashComp = $_POST['file_md5hash'];
  $dataEncode = $_POST['dataEncode'];

  switch($dataEncode) {
    case 'base64':
    $rawData = base64_decode($data);
    break;

    default:
    $errStr = 'badRawEncoding';
    $errDesc = 'The specified file content encoding was not recognized.';

    $continue = false;
    break;
  }

  if ($md5hash) {
    if (md5($rawData) != $md5hash) {
      $errStr = 'badRawHash';
      $errDesc = 'The included MD5 hash did not match the file content.';

      $continue = false;
    }
  }

  if ($size) {
    if (strlen($rawData) != $size) {
      $errStr = 'badRawSize';
      $errDesc = 'The specified content length did not match the file content.';

      $continue = false;
    }
  }

  break;
}

if ($continue) {
  $md5hash = md5($rawData);

  if ($encryptUploads) {
    list($contentsEncrypted,$iv,$saltNum) = fim_encrypt($rawData);
    $iv = dbEscape($iv);
    $saltNum = intval($saltNum);
  }
  else {
    $contentsEncrypted = base64_encode($rawData);
    $iv = '';
    $saltNum = '';
  }

  if (!$rawData) {
    $errDesc = $phrases['uploadErrorFileContents'];
  }
  else {
    $prefile = dbRows("SELECT v.versionId, v.fileId, v.md5hash FROM {$sqlPrefix}fileVersions AS v, {$sqlPrefix}files AS f WHERE v.md5hash = '$md5hash' AND v.fileId = f.fileId AND f.userId = $user[userId]");

    if ($prefile) {
      $webLocation = "{$installUrl}file.php?hash={$prefile[md5hash]}";

      if (isset($_POST['autoInsert'])) {
        $room = dbRows("SELECT * FROM {$sqlPrefix}rooms WHERE roomId = " . (int) $_POST['roomId']);

        fim_sendMessage($webLocation,$user,$room,'image');
      }
    }
    else {
      dbInsert(array(
        'userId' => $user['userId'],
        'name' => $name,
        'mime' => $mime,
      ),"{$sqlPrefix}files");

      $fileId = dbInsertId();

      dbInsert(array(
        'fileId' => $fileId,
        'md5hash' => $md5hash,
        'salt' => $saltNum,
        'iv' => $iv,
        'contents' => $contentsEncrypted,
      ),"{$sqlPrefix}fileVersions");

      $webLocation = "{$installUrl}file.php?hash={$md5hash}";

      if (isset($_POST['autoInsert'])) {
        $room = dbRows("SELECT * FROM {$sqlPrefix}rooms WHERE roomId = " . (int) $_POST['roomId']);

        fim_sendMessage($webLocation,$user,$room,'image');
      }
    }
  }
}


echo fim_outputApi($xmlData);

dbClose();
?>