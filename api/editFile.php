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
 * Edit a file's properties, create a file, delete a file, or undelete a file.
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 *
 * =POST Parameters=
 * @param string action - The action to be performed by the script, either:
 ** 'create' - Creates a new file.
 ** 'edit' - Edits an existing file.
 ** 'delete' - Marks a file as deleted. (File data will remain on the server.)
 ** 'undelete' - Unmarks a file as deleted.
 * @param string uploadMethod='raw' - How the file is being transferred from the server, either:
 ** 'raw' - File data is stored in the "fileData" POST variable.
 ** 'put' - File is being transferred via PUT. [[Unstable.]]
 * @param string fileName - The name of the file. [[Required.]]
 * @param string fileData - The data of the file. If not specified, the file will be stored empty.
 * @param int fileSize - The size of the file (in bytes), used for checks.  [[TODO: Bugtest.]]
 * @param string fileMd5Hash - The MD5 hash of the file, used for checks.
 * @param string fileSha256Hash - The SHA256 hash of the file, used for checks.
 * @param int roomId - If the image is to be directly posted to a room, specify the room ID here. This may be required, depending on server settings.
 * @param string dataEncode - How the data is encoded, either:
 ** 'base64' - Data is encoded as Base64.
 ** 'binary' - Data is not encoded. [[Unstable.]]
 * @param string parentalAge - The parental age corresponding to the file. If the age is not recognised, a server-defined default will be used.
 * @param csv parentalFlags - A comma-separated list of parental flags that apply to the file. If a flag is not recognised, it will be dropped. If omitted, a server-defined default will be used.
 * @param int fileId - If editing, deleting, or undeleting the file, this is the ID of the file.
 *
 * =Errors=
 * @throws tooManyFiles - The user is not allowed to upload files because they have reached the file upload limit, either for themselves or for the entire server.
 * @throws badEncoding - The encoding specified is not recognised.
 * @throws badMd5Hash - The md5 hash of the uploaded file data does not match the md5 hash sent.
 * @throws badSha256Hash - The sha256 hash of the uploaded file data does not match the sha256 hash sent.
 * @throws badSize - The size of the uploaded file data does not match the fileSize parameter sent.
 * @throws badName - No name was specified, or, potentially, the name contained characters that are not allowed but will not be removed.
 * @throws badNameParts - An extension could not be obtained because of the number of '.' characters in the file. If there are zero, or two or more, then this error will thrown. (Thus, for example, ".tar.gz" files can not be processed by the script.)
 * @throws emptyFile - The file sent was empty. This is only thrown if the server does not accept empty files.
 * @throws tooLarge - The file data exceeds the server limit.
 * @throws unrecExt - The extension of the file is not recognised by the server, and thus is not accepted.
 * @throws invalidFile - The 'fileId' parameter sent does not correspond to an existing file.
 * @throws noPerm - The active user does not have permission to perform the action requested.
 * @throws noOrphanFiles - A valid room was not provided, and the server requires that all files are associated with a room.
 *
 * =Reponse=
 * @return APIOBJ:
 ** editFile
 *** activeUser
 **** userId
 **** userName
 *** errStr
 *** errDesc
 *** response [[TODO]]
*/

$apiRequest = true;

require('../global.php');

/* Get Request Data */
$request = fim_sanitizeGPC('p', array(
  'action' => array(
    'require' => true,
    'valid' => array(
      'create', 'edit',
      'delete', 'undelete',
      'flag', // TODO
    ),
  ),


  'uploadMethod' => array(
    'default' => 'raw',
    'valid' => array(
      'raw', 'put',
    ),
  ),

  'fileName' => array(
    'require' => true,
    'trim' => true,
  ),

  'fileData' => array(
    'default' => '',
  ),

  'fileSize' => array(
    'context' => 'int',
  ),

  'fileMd5hash' => array(),

  'fileSha256hash' => array(),

  'roomId' => array(
    'default' => 0,
    'context' => 'int',
  ),

  'dataEncode' => array(
    'require' => true,
    'valid' => array(
      'base64', 'binary',
    ),
  ),

  'parentalAge' => array(
    'context' => 'int',
    'valid' => $config['parentalAges'],
    'default' => $config['parentalAgeDefault'],
  ),

  'parentalFlags' => array(
    'default' => $config['parentalFlagsDefault'],
    'context' => array(
      'type' => 'csv',
      'valid' => $config['parentalFlags'],
    ),
  ),

  'fileId' => array(
    'default' => 0,
    'context' => array(
      'type' => 'int',
    ),
  ),
));



/* Data Predefine */
$xmlData = array(
  'editFile' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => ($errStr),
    'errDesc' => ($errDesc),
    'response' => array(),
  ),
);

$queryParts['getMimes']['columns'] = array(
  "{$sqlPrefix}uploadTypes" => 'typeId, extension, mime, maxSize, container',
);
$queryParts['getMimes']['conditions'] = false;
$queryParts['getMimes']['sort'] = false;
$queryParts['getMimes']['limit'] = false;





/* Plugin Hook End */
($hook = hook('editFile_start') ? eval($hook) : '');



/* Start Processing */
if ($continue) {
  switch ($request['action']) {
    case 'edit':
    case 'create':
    $parentalFileId = 0;

    if ($request['action'] === 'create') {
      /* Get Mime Types from the Database */
      $mimes = $slaveDatabase->select(
        $queryParts['getMimes']['columns'],
        $queryParts['getMimes']['conditions'],
        $queryParts['getMimes']['sort'],
        $queryParts['getMimes']['limit']
      );
      $mimes = $mimes->getAsArray('extension');


      /* Get Room Data, if Applicable */
      if ($request['roomId']) $roomData = $slaveDatabase->getRoom($request['roomId']);
      else $roomData = false;


      /* PUT Support (TODO) */
      if ($request['uploadMethod'] === 'put') { // This is an unsupported alternate upload method. It will not be documented until it is known to work.
        $putResource = fopen("php://input", "r"); // file data is from stdin
        $request['fileData'] = ''; // The only real change is that we're getting things from stdin as opposed to from the headers. Thus, we'll just translate the two here.

        while ($fileContents = fread($putResource, $config['fileUploadChunkSize'])) { // Read the resource using 1KB chunks. This is slower than a higher chunk, but also avoids issues for now. It can be overridden with the config directive fileUploadChunkSize.
          $request['fileData'] = $fileContents; // We're not sure if this will work, since there are indications you have to write to a file instead.
        }

        fclose($putResource);
      }


      if (!$roomData && !$config['allowOrphanFiles']) {
        $errStr = 'noOrphanFiles';
        $errDesc = 'The server will not accept orphan files.';
      }
      elseif (($config['uploadMaxFiles'] !== -1 && $database->getCounter('uploads') > $config['uploadMaxFiles']) || ($config['uploadMaxUserFiles'] !== -1 && $user['fileCount'] > $config['uploadMaxUserFiles'])) {
        $errStr = 'tooManyFiles';
        $errDesc = 'The server has reached its file capacity.';
      }
      elseif ($continue) {
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
            $errStr = 'badEncoding';
            $errDesc = 'The specified file content encoding was not recognized.';

            $continue = false;
            break;
          }

          $rawSize = strlen($rawData);


          if ($request['md5hash']) { // This will allow us to verify that the upload worked.
            if (md5($rawData) != $request['md5hash']) {
              $errStr = 'badMd5Hash';
              $errDesc = 'The included MD5 hash did not match the file content.';

              $continue = false;
            }
          }

          if ($request['sha256hash']) { // This will allow us to verify that the upload worked.
            if (hash('sha256', $rawData) != $request['sha256hash']) {
              $errStr = 'badSha256Hash';
              $errDesc = 'The included MD5 hash did not match the file content.';

              $continue = false;
            }
          }

          if ($request['fileSize']) { // This will allow us to verify that the upload worked as well, can be easier to implement, but doesn't serve the primary purpose of making sure the file upload wasn't intercepted.
            if ($rawSize != $request['fileSize']) {
              $errStr = 'badSize';
              $errDesc = 'The specified content length did not match the file content.';

              $continue = false;
            }
          }
          break;
        }


        ($hook = hook('sendFile_method') ? eval($hook) : '');



        /* Start Processing */
        if ($continue) {
          if (!$request['fileName']) {
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
                $container = $mimes[$fileNameParts[1]]['container'];
                $maxSize = $mimes[$fileNameParts[1]]['maxSize'];

                $sha256hash = hash('sha256',$rawData);
                $md5hash = hash('md5',$rawData);

                if ($encryptUploads) {
                  list($contentsEncrypted,$iv,$saltNum) = fim_encrypt($rawData);
                  $saltNum = intval($saltNum);
                }
                else {
                  $contentsEncrypted = base64_encode($rawData);
                  $iv = '';
                  $saltNum = '';
                }


                ($hook = hook('sendFile_postReq') ? eval($hook) : '');


                if (!$rawData && !$config['allowEmptyFiles']) {
                  $errStr = 'emptyFile';
                  $errDesc = $phrases['uploadErrorFileContents'];
                }
                elseif (($rawSize == 0) && !$config['allowEmptyFiles']) {
                  $errStr = 'emptyFile';
                  $errDesc = $phrases['uploadErrorFileContents'];
                }
                elseif ($rawSize > $maxSize) { // Note: Data is stored as base64 because its easier to handle; thus, the data will be about 33% larger than the normal (thus, if a limit is normally 400KB the file must be smaller than 300KB).
                  $errStr = 'tooLarge';
                  $errDesc = $phrases['uploadErrorFileSize'];
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

                  ($hook = hook('sendFile_prefile') ? eval($hook) : '');

                  if ($prefile) {
                    $webLocation = "{$installUrl}file.php?sha256hash={$prefile['sha256hash']}";

                    if ($roomData) {
                      fim_sendMessage($webLocation, $container, $user, $roomData);
                    }
                  }
                  else {
                    ($hook = hook('sendFile_preInsert') ? eval($hook) : '');

                    if ($continue) {
                      $database->insert("{$sqlPrefix}files", array(
                        'userId' => $user['userId'],
                        'fileName' => $request['fileName'],
                        'fileType' => $mime,
                        'parentalAge' => $request['parentalAge'],
                        'parentalFlags' => implode(',', $request['parentalFlags']),
                        'creationTime' => time(),
                        'fileSize' => $rawSize,
                      ));

                      $fileId = $database->insertId;
                      $parentalFileId = $fileId;

                      $database->insert("{$sqlPrefix}fileVersions", array(
                        'fileId' => $fileId,
                        'sha256hash' => $sha256hash,
                        'md5hash' => $md5hash,
                        'salt' => $saltNum,
                        'iv' => $iv,
                        'size' => $rawSize,
                        'contents' => $contentsEncrypted,
                        'time' => time(),
                      ));

                      $database->insert("{$sqlPrefix}fileVersions", array(
                        'fileId' => $fileId,
                        'sha256hash' => $sha256hash,
                        'md5hash' => $md5hash,
                        'salt' => $saltNum,
                        'iv' => $iv,
                        'size' => $rawSize,
                        'contents' => $contentsEncrypted,
                        'time' => time(),
                      ));

                      $database->update("{$sqlPrefix}users", array(
                        'fileCount' => array(
                          'type' => 'equation',
                          'value' => '$fileCount + 1',
                        ),
                        'fileSize' => array(
                          'type' => 'equation',
                          'value' => '$fileSize + ' . (int) $rawSize,
                        ),
                      ), array(
                        'userId' => $user['userId'],
                      ));

                      $database->incrementCounter('uploads');
                      $database->incrementCounter('uploadSize', $rawSize);

                      $webLocation = "{$installUrl}file.php?sha256hash={$sha256hash}";

                      ($hook = hook('sendFile_postInsert') ? eval($hook) : '');

                      if ($continue) {
                        if ($roomData) {
                          fim_sendMessage($webLocation, $container, $user, $roomData);
                        }
                      }
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
      }
    }
    elseif ($request['action'] === 'edit') {
      $fileData = $database->getFile($request['fileId']);

      if (!$fileData) {
        $errStr = 'invalidFile';
        $errDesc = 'The file specified is invalid.';
      }
      else {
        $parentalFileId = $request['fileId'];
      }
    }

    if ($parentalFileId > 0) {
      $database->update("{$sqlPrefix}files", array(
        'parentalAge' => (int) $request['parentalAge'],
        'parentalFlags' => implode(',', $request['parentalFlags']),
      ), array(
        'fileId' => $request['fileId'],
      ));
    }
    break;

    case 'delete':
    $fileData = $database->getFile($request['fileId']);

    if ($user['adminDefs']['modImages'] || $user['userId'] == $fileData['userId']) {
      $database->modLog('deleteImage', $request['fileId']);

      $database->update("{$sqlPrefix}files", array(
        'deleted' => 1,
      ), array(
        'fileId' => $request['fileId'],
      ));
    }
    else {
      $errStr = 'noPerm';
      $errDesc = 'You do not have permission to delete and undelete images.';
    }
    break;

    case 'undelete':
    $fileData = $database->getFile($request['fileId']);

    if ($user['adminDefs']['modImages']) {
      modLog('undeleteImage', $request['fileId']);

      $database->update("{$sqlPrefix}files", array(
        'deleted' => 0,
      ), array(
        'fileId' => $request['fileId'],
      ));
    }
    else {
      $errStr = 'noPerm';
      $errDesc = 'You do not have permission to delete and undelete images.';
    }
    break;

    case 'flag': // TODO: Allows users to flag images that are not appropriate for a room.

    break;
  }
}



/* Update Data for Errors */
$xmlData['editFile']['errStr'] = ($errStr);
$xmlData['editFile']['errDesc'] = ($errDesc);



/* Plugin Hook End */
($hook = hook('editFile_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);
?>