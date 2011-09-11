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
 * Edit a File's Status
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
 *
 * @param string action
 * @param integer fileId
*/

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('p', array(
  'action' => array(
    'type' => 'string',
    'require' => true,
    'valid' => array(
      'create',
      'edit',
      'delete',
      'undelete',
      'flag',
    ),
  ),


  'uploadMethod' => array(
    'type' => 'string',
    'default' => 'raw',
    'valid' => array(
      'raw',
      'put',
    ),
  ),

  'fileName' => array(
    'type' => 'string',
    'require' => true,
  ),

  'fileData' => array(
    'type' => 'string',
  ),

  'fileSize' => array(
    'type' => 'string',
  ),

  'fileMd5hash' => array(
    'type' => 'string',
  ),

  'fileSha256hash' => array(
    'type' => 'string',
  ),

  'roomId' => array(
    'type' => 'string',
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
      'binary',
    ),
  ),

  'parentalAge' => array(
    'type' => 'string',
    'context' => array(
      'type' => 'int',
      'valid' => array(
        6, 10, 13, 16, 18,
      ),
    ),
    'default' => 6,
  ),

  'parentalFlags' => array(
    'type' => 'array',
    'require' => true,
    'valid' => array(
      'violence', 'suggestive', 'nudity',
      'pnudity', 'language', 'violence',
      'gore', 'weapons', 'drugs',
    ),
  ),

  'fileId' => array(
    'type' => 'string',
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
if (!$fileData) {
  $errStr = 'invalidFile';
  $errDesc = 'The file specified is invalid.';
}
elseif ($continue) {
  switch ($request['action']) {
    case 'edit':
    case 'create':
    if ($request['action'] === 'create') {
      /* Get Mime Types from the Database */
      $mimes = $slaveDatabase->select(
        $queryParts['getMimes']['columns'],
        $queryParts['getMimes']['conditions'],
        $queryParts['getMimes']['sort'],
        $queryParts['getMimes']['limit']
      );
      $mimes = $mimes->getAsArray('extension');


      if ($request['uploadMethod'] === 'put') { // This is an unsupported alternate upload method. It will not be documented until it is known to work.
        $putResource = fopen("php://input", "r"); // file data is from stdin
        $request['fileData'] = ''; // The only real change is that we're getting things from stdin as opposed to from the headers. Thus, we'll just translate the two here.

        while ($fileContents = fread($putResource, $config['fileUploadChunkSize'])) { // Read the resource using 1KB chunks. This is slower than a higher chunk, but also avoids issues for now. It can be overridden with the config directive fileUploadChunkSize.
          $request['fileData'] = $fileContents; // We're not sure if this will work, since there are indications you have to write to a file instead.
        }

        fclose($putResource);
      }


      if (($config['uploadMaxFiles'] !== -1 && $database->getCounter('uploads') > $config['uploadMaxFiles']) || ($config['uploadMaxUserFiles'] !== -1 && $user['fileCount'] > $config['uploadMaxUserFiles'])) {
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
            if (hash('sha256', $rawData) != $request['sha256hash']) {
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


        ($hook = hook('sendFile_method') ? eval($hook) : '');



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


                if (!$rawData) {
                  $errStr = 'emptyFile';
                  $errDesc = $phrases['uploadErrorFileContents'];
                }
                elseif (strlen($rawData) == 0) {
                  $errStr = 'emptyFile';
                  $errDesc = $phrases['uploadErrorFileContents'];
                }
                elseif (strlen($rawData) > $maxSize) { // Note: Data is stored as base64 because its easier to handle; thus, the data will be about 33% larger than the normal (thus, if a limit is normally 400KB the file must be smaller than 300KB).
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

                    if ($request['roomId']) {
                      $room = $slaveDatabase->getRoom($request['roomId']);

                      fim_sendMessage($webLocation, $container, $user, $room);
                    }
                  }
                  else {
                    ($hook = hook('sendFile_preInsert') ? eval($hook) : '');

                    if ($continue) {
                      $database->insert("{$sqlPrefix}files", array(
                        'userId' => $user['userId'],
                        'fileName' => $request['fileName'],
                        'fileType' => $mime,
                        'parentalFlags' =>
                        'creationTime' => time(),
                      ));

                      $fileId = $database->insertId;
                      $parentalFileId = $fileId;

                      $database->insert("{$sqlPrefix}fileVersions", array(
                        'fileId' => $fileId,
                        'sha256hash' => $sha256hash,
                        'md5hash' => $md5hash,
                        'salt' => $saltNum,
                        'iv' => $iv,
                        'size' => strlen($rawData),
                        'contents' => $contentsEncrypted,
                        'time' => time(),
                      ));

                      $database->insert("{$sqlPrefix}fileVersions", array(
                        'fileId' => $fileId,
                        'sha256hash' => $sha256hash,
                        'md5hash' => $md5hash,
                        'salt' => $saltNum,
                        'iv' => $iv,
                        'size' => strlen($rawData),
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
                          'value' => '$fileSize + ' . (int) strlen($rawData),
                        ),
                      ), array(
                        'userId' => $user['userId'],
                      ));

                      $database->incrementCounter('uploads');
                      $database->incrementCounter('uploadSize', strlen($rawData));

                      $webLocation = "{$installUrl}file.php?sha256hash={$sha256hash}";

                      ($hook = hook('sendFile_postInsert') ? eval($hook) : '');

                      if ($continue) {
                        if ($request['roomId']) {
                          $room = $slaveDatabase->getRoom($request['roomId']);

                          fim_sendMessage($webLocation, $container, $user, $room);
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
      $parentalFileId = $request['fileId'];
    }

    if ($parentalFileId) {
      $database->update("{$sqlPrefix}files", array(
        'parentalAge' => (int) $request['parentalAge'],
        'parentalFlags' => implode(',', $request['parentalFlags']),
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

    case 'flag':

    break;

    default:
    $errStr = 'badAction';
    $errDesc = 'The action specified does not exist.';
    break;
  }
}



/* Update Data for Errors */
$xmlData['moderate']['errStr'] = ($errStr);
$xmlData['moderate']['errDesc'] = ($errDesc);



/* Plugin Hook End */
($hook = hook('editFile_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);



/* Close Database Connection */
dbClose();
?>