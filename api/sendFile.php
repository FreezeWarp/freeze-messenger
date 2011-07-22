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
 *
 * @param string uploadMethod -
 * @param string fileName -
 * @param string fileData -
 * @param string fileSize -
 * @param string fileMd5hash -
 * @param string fileSha256hash -
 * @param string roomId -
 * @param string dataEncode -
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

    'fileName' => array(
      'type' => 'string',
      'require' => true,
    ),

    'fileData' => array(
      'type' => 'string',
      'require' => true,
    ),

    'fileSize' => array(
      'type' => 'string',
      'require' => false,
    ),

    'fileMd5hash' => array(
      'type' => 'string',
      'require' => false,
    ),

    'fileSha256hash' => array(
      'type' => 'string',
      'require' => false,
    ),

    'roomId' => array(
      'type' => 'string',
      'require' => false,
      'default' => 0,
      'context' => array(
        'type' => 'int',
      ),
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
      'extension' => 'extension',
      'mime' => 'mime',
      'maxSize' => 'maxSize',
    ),
  )
);
$mimes = $mimes->getAsArray('extension');



if ($request['uploadMethod'] == 'put') { // This is an unsupported alternate upload method. It will not be documented until it is known to work.
  $putResource = fopen("php://input", "r"); // file data is from stdin
  $request['fileData'] = ''; // The only real change is that we're getting things from stdin as opposed to from the headers. Thus, we'll just translate the two here.

  while ($fileContents = fread($putResource, (isset($config['fileUploadChunkSize']) ? $config['fileUploadChunkSize'] : 1024))) { // Read the resource using 1KB chunks. This is slower than a higher chunk, but also avoids issues for now. It can be overridden with the config directive fileUploadChunkSize.
    $request['fileData'] = $fileContents; // We're not sure if this will work, since there are indications you have to write to a file instead.
  }

  fclose($putResource);
}

$continue = true;

/* Verify the Data, Preprocess */
switch ($request['uploadMethod']) {
  case 'raw':
  case 'put':
  switch($request['dataEncode']) {
    case 'base64':
    $rawData = base64_decode($request['fileData']);
    break;

    case 'binary': // Binary is buggy and far from confirmed to work. That said... if you're lucky? MDN has some useful information on this type of thing: https://developer.mozilla.org/En/Using_XMLHttpRequest
    $rawData = $request['fileData'];
    break;

    default:
    $errStr = 'badRawEncoding';
    $errDesc = 'The specified file content encoding was not recognized.';

    $continue = false;
    break;
  }


  if ($request['md5hash']) { // This will allow us to verify that the upload worked.
    if (md5($rawData) != $request['md5hash']) {
      $errStr = 'badRawHash';
      $errDesc = 'The included MD5 hash did not match the file content.';

      $continue = false;
    }
  }

  if ($request['sha256hash']) { // This will allow us to verify that the upload worked.
    if (hash('sha256',$rawData) != $request['sha256hash']) {
      $errStr = 'badRawHash';
      $errDesc = 'The included MD5 hash did not match the file content.';

      $continue = false;
    }
  }

  if ($request['fileSize']) { // This will allow us to verify that the upload worked as well, can be easier to implement, but doesn't serve the primary purpose of making sure the file upload wasn't intercepted.
    if (strlen($rawData) != $request['fileSize']) {
      $errStr = 'badRawSize';
      $errDesc = 'The specified content length did not match the file content.';

      $continue = false;
    }
  }
  break;
}



/* Start Processing */
if ($continue) {
  if (!$request['fileData']) {
    $errStr = 'badName';
    $errDesc = 'A name was not specified for the file.';
  }
  else {
    $fileNameParts = explode('.',$request['fileName']);

    if (count($fileNameParts) != 2) {
      $errStr = 'badNameParts';
      $errDesc = 'There was an improper number of "periods" in the file name - the extension could not be obtained.';
    }
    else {
      if (isset($mimes[$fileNameParts[1]])) {
        $mime = $mimes[$fileNameParts[1]]['mime'];

        $sha256hash = hash('sha256',$rawData);
        $md5hash = hash('md5',$rawData);

/*        if ($encryptUploads) {
          list($contentsEncrypted,$iv,$saltNum) = fim_encrypt($rawData);
          $saltNum = intval($saltNum);
        }
        else {*/
          $contentsEncrypted = base64_encode($rawData);
          $iv = '';
          $saltNum = '';
//        }

        if (!$rawData) {
          $errStr = 'emptyFile';
          $errDesc = $phrases['uploadErrorFileContents'];
        }
        elseif (strlen($rawData) == 0) { // Note: Data is stored as base64 because its easier to handle; thus, the data will be about 33% larger than the normal (thus, if a limit is normally 400KB the file must be smaller than 300KB).
          $errStr = 'empty';
          $errDesc = 'The file uploaded is too large.';
        }
        else {
          $prefile = $database->select(
            array(
              "{$sqlPrefix}fileVersions" => array(
                'versionId' => 'versionId',
                'fileId' => 'fileId',
                'sha256hash' => 'sha256hash',
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
          )->getAsArray(false);

          if ($prefile) {
            $webLocation = "{$installUrl}file.php?sha256hash={$prefile['sha256hash']}";

            if ($request['roomId']) {
              $room = $slaveDatabase->getRoom($request['roomId']);

              fim_sendMessage($webLocation,$user,$room,'image');
            }
          }
          else {
            $database->insert(array(
              'userId' => $user['userId'],
              'fileName' => $request['fileName'],
              'fileType' => $mime,
            ),"{$sqlPrefix}files");

            $fileId = dbInsertId();

            $database->insert(array(
              'fileId' => $fileId,
              'sha256hash' => $sha256hash,
              'md5hash' => $md5hash,
              'salt' => $saltNum,
              'iv' => $iv,
              'contents' => $contentsEncrypted,
            ),"{$sqlPrefix}fileVersions");

            $webLocation = "{$installUrl}file.php?sha256hash={$sha256hash}";

            if ($request['roomId']) {
              $room = $slaveDatabase->getRoom($request['roomId']);

              fim_sendMessage($webLocation,$user,$room,'image');
            }
          }
        }
      }
      else {
        $errStr = 'unrecExt';
        $errDesc = 'The extension .' . $fileNameParts[1] . ' was not recognized.';
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