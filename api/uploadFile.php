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
 * Sends and Store's a File on the Server
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
*/

$apiRequest = true;
require_once('../global.php');
require_once('../functions/parserFunctions.php');


$uploadMethod = $_POST['uploadMethod'];
die(); // Unimplemented

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

$mimes = dbRows("SELECT typeId, extension, mime, maxSize
  FROM {$sqlPrefix}uploadTypes",'extension');


switch ($uploadMethod) {
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
  if (!$name) {
    $errStr = 'badName';
    $errDesc = 'A name was not specified for the file.';
  }
  else {
    $nameParts = explode($name);

    if (count($nameParts) == 2) {
      $errStr = 'badNameParts';
      $errDesc = 'There was an improper number of "periods" in the file name - the extension could not be obtained.';
    }
    else {
      if (isset($mimes[$nameParts[1]])) {
        $mime = $mimes[$nameParts[1]]['mime'];

        $sha256hash = hash('sha256',$rawData);

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
          $errStr = 'emptyFile';
          $errDesc = $phrases['uploadErrorFileContents'];
        }
        elseif (strlen($rawData) > ) { // Note: Data is stored as base64 because its easier to handle; thus, the data will be about 33% larger than the normal (thus, if a limit is normally 400KB the file must be smaller than 300KB).
          $errStr = 'empty';
          $errDesc = 'The file uploaded is too large.';
        }
        else {
          $prefile = dbRows("SELECT v.versionId, v.fileId, v.sha256hash FROM {$sqlPrefix}fileVersions AS v, {$sqlPrefix}files AS f WHERE v.sha256hash = '" . dbEscape($sha256hash) . "' AND v.fileId = f.fileId AND f.userId = $user[userId]");

          if ($prefile) {
            $webLocation = "{$installUrl}file.php?hash={$prefile[sha256hash]}";

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
              'sha256hash' => $sha256hash,
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
      else {
        $errStr = 'unrecExt';
        $errDesc = 'The extension was not recognized.';
      }
    }
  }
}


echo fim_outputApi($xmlData);

dbClose();
?>