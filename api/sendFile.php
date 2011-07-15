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



/* Get Request Data */
$request = fim_sanitizeGPC(array(
  'post' => array(
    'uploadMethod' => array(
      'type' => 'string',
      'default' => 'raw',
      'valid' => array(
        'raw',
      ),
      'require' => false,
    ),

    'file_name' => array(
      'type' => 'string',
      'require' => true,
    ),

    'file_data' => array(
      'type' => 'string',
      'require' => true,
    ),

    'file_size' => array(
      'type' => 'string',
      'require' => false,
    ),

    'file_md5hash' => array(
      'type' => 'string',
      'require' => false,
    ),

    'file_sha256hash' => array(
      'type' => 'string',
      'require' => false,
    ),

    'dataEncode' => array(
      'type' => 'string',
      'require' => true,
      'valid' => array(
        'base64',
      ),
    ),
  ),
));



/* Plugin Hook End */
($hook = hook('uploadFile_end') ? eval($hook) : '');



/* Data Predefine */
$xmlData = array(
  'uploadFile' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => $errStr,
    'errDesc' => $errDesc,
    'upload' => array(),
  ),
);



/* Get Mime Types from the Database */
$mimes = $slaveDatabase->select(
  array(
    "{$sqlPrefix}uploadTypes" => array(
      'typeId' => 'typeId',
      'extesion' => 'extesion',
      'mime' => 'mime',
      'maxSize' => 'maxSize',
    ),
  )
);
$mimes = $mimes->getAsArray('extension');




if ($request['uploadMethod'] == 'put') { // This is an unsupported alternate upload method. It will not be documented until it is known to work.
  $putResource = fopen("php://input", "r"); // file data is from stdin
  $request['file_data'] = ''; // The only real change is that we're getting things from stdin as opposed to from the headers. Thus, we'll just translate the two here.

  while ($fileContents = fread($putResource, (isset($config['fileUploadChunkSize']) ? $config['fileUploadChunkSize'] : 1024))) { // Read the resource using 1KB chunks. This is slower than a higher chunk, but also avoids issues for now. It can be overridden with the config directive fileUploadChunkSize.
    $request['file_data'] = $fileContents; // We're not sure if this will work, since there are indications you have to write to a file instead.
  }

  fclose($putResource);
}



/* Verify the Data, Preprocess */
switch ($request['uploadMethod']) {
  case 'raw':
  case 'put':
  $fileName = ($request['file_name']);
  $fileContents = ($request['file_data']);
  $fileSize = (int) $request['file_size'];

  $md5hashComp = $request['file_md5hash'];
  $sha256hashComp = $request['file_sha256hash'];

  $fileContentsEncode = $request['dataEncode'];


  switch($fileContentsEncode) {
    case 'base64':
    $rawData = base64_decode($fileContents);
    break;

    case 'binary': // Binary is buggy and far from confirmed to work. That said... if you're lucky? MDN has some useful information on this type of thing: https://developer.mozilla.org/En/Using_XMLHttpRequest
    $rawData = $fileContents;
    break;

    default:
    $errStr = 'badRawEncoding';
    $errDesc = 'The specified file content encoding was not recognized.';

    $continue = false;
    break;
  }


  if ($md5hash) { // This will allow us to verify that the upload worked.
    if (md5($rawData) != $md5hash) {
      $errStr = 'badRawHash';
      $errDesc = 'The included MD5 hash did not match the file content.';

      $continue = false;
    }
  }

  if ($sha256hash) { // This will allow us to verify that the upload worked.
    if (hash('sha256',$rawData) != $sha256hash) {
      $errStr = 'badRawHash';
      $errDesc = 'The included MD5 hash did not match the file content.';

      $continue = false;
    }
  }

  if ($fileSize) { // This will allow us to verify that the upload worked as well, can be easier to implement, but doesn't serve the primary purpose of making sure the file upload wasn't intercepted.
    if (strlen($rawData) != $fileSize) {
      $errStr = 'badRawSize';
      $errDesc = 'The specified content length did not match the file content.';

      $continue = false;
    }
  }
  break;
}



/* Start Processing */
if ($continue) {
  if (!$fileName) {
    $errStr = 'badName';
    $errDesc = 'A name was not specified for the file.';
  }
  else {
    $fileNameParts = explode($fileName);

    if (count($fileNameParts) == 2) {
      $errStr = 'badNameParts';
      $errDesc = 'There was an improper number of "periods" in the file name - the extension could not be obtained.';
    }
    else {
      if (isset($mimes[$fileNameParts[1]])) {
        $mime = $mimes[$fileNameParts[1]]['mime'];

        $sha256hash = hash('sha256',$rawData);

        if ($encryptUploads) {
          list($contentsEncrypted,$iv,$saltNum) = fim_encrypt($rawData);
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
          $prefile = $database->select(
            array(
              "{$sqlPrefix}fileVersions" => array(
                'versionId',
                'fileId',
                'sha256hash',
              ),
              "{$sqlPrefix}files" => array(
                'fileId' => 'vfileId',
              ),
            ),
            array(
              'both' => array(
                array(
                  'type' => 'e',
                  'left' => array(
                    'type' => 'column',
                    'value' => 'sha256hash',
                  ),
                  'right' => array(
                    'type' => 'string',
                    'value' => $sha256hash,
                  ),
                ),
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

          if ($prefile) {
            $webLocation = "{$installUrl}file.php?hash={$prefile[sha256hash]}";

            if ($request['autoInsert']) {
              $room = $slaveDatabase->getRoom($request['roomId']);

              fim_sendMessage($webLocation,$user,$room,'image');
            }
          }
          else {
            $fileContentsbase->insert(array(
              'userId' => $user['userId'],
              'name' => $fileName,
              'mime' => $mime,
            ),"{$sqlPrefix}files");

            $fileId = dbInsertId();

            $fileContentsbase->insert(array(
              'fileId' => $fileId,
              'sha256hash' => $sha256hash,
              'salt' => $saltNum,
              'iv' => $iv,
              'contents' => $contentsEncrypted,
            ),"{$sqlPrefix}fileVersions");

            $webLocation = "{$installUrl}file.php?hash={$md5hash}";

            if ($request['autoInsert']) {
              $room = $slaveDatabase->getRoom($request['roomId']);

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



/* Update Data for Errors */
$xmlData['uploadFile']['errStr'] = ($errStr);
$xmlData['uploadFile']['errDesc'] = ($errDesc);



/* Plugin Hook End */
($hook = hook('uploadFile_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);



/* Close Database Connection */
dbClose();
?>