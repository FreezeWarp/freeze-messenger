<?php
/* FreezeMessenger Copyright © 2017 Joseph Todd Parsons

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
 * @copyright Joseph T. Parsons 2017
 */


if (!defined('API_INFILE'))
    die();



/* Get Request Data */
$requestHead = array_merge($requestHead, fim_sanitizeGPC('g', array(
    'uploadMethod' => array(
        'default' => 'raw',
        'valid' => array(
            'raw', 'put',
        ),
    ),
)));

/* If the upload method is put, we read directly from php://input */
$request = fim_sanitizeGPC(
    ($requestHead['uploadMethod'] === 'put' ? 'g' : 'p'), // If the uploadMethod is put, then we are reading from stdin, and there likely is no POST data available (since stdin is usually used to send POST data)... so use GET instead for everything.
    array(
        'fileName' => array(
            'require' => true,
            'trim' => true,
        ),
    
        'fileSize' => array(
            'cast' => 'int',
        ),
    
        'md5hash' => array(),
    
        'sha256hash' => array(),

        'crc32bhash' => array(),
    
        'roomId' => array(
            'require' => !fimConfig::$allowOrphanFiles,
            'cast' => 'roomId',
        ),
    
        'fileId' => array(
            'default' => 0,
            'cast' => 'int',
        ),
    )
);
\Fim\Database::instance()->accessLog('editFile', $request);



/* Data Predefine */
$xmlData = array(
    'response' => array(),
);



\Fim\Database::instance()->startTransaction();



/* Start Processing */
switch ($requestHead['_action']) {
    case 'edit': case 'create':
    if ($requestHead['_action'] === 'create') {
        /* Get Room Data, if Applicable */
        if ($request['roomId']) $roomData = fimRoomFactory::getFromId($request['roomId']);
        else $roomData = false;


        if (!fimConfig::$enableUploads)
            throw new fimError('uploadsDisabled', 'Uploads are disabled on this FreezeMessenger server.');
        if (!$roomData && !fimConfig::$allowOrphanFiles)
            throw new fimError('noOrphanFiles', 'Files cannot be orphaned on this FreezeMessenger server. You must post them to a room.');
        if (fimConfig::$uploadMaxFiles !== -1 && \Fim\Database::instance()->getCounterValue('uploads') > fimConfig::$uploadMaxFiles)
            throw new fimError('tooManyFilesServer', 'The server has reached its upload limit. No more uploads can be made.');
        if (fimConfig::$uploadMaxUserFiles !== -1 && $user->fileCount > fimConfig::$uploadMaxUserFiles)
            throw new fimError('tooManyFilesUser', 'You have reached your upload limit. No more uploads can be made.');
        if (fimConfig::$uploadMaxSpace !== -1 && \Fim\Database::instance()->getCounterValue('uploadSize') > fimConfig::$uploadMaxSpace)
            throw new fimError('tooManyFilesServer', 'The server has reached its upload limit. No more uploads can be made.');
        if (fimConfig::$uploadMaxUserSpace !== -1 && $user->fileSize > fimConfig::$uploadMaxUserSpace)
            throw new fimError('tooManyFilesUser', 'You have reached your upload limit. No more uploads can be made.');


        /* PUT Support */
        if ($requestHead['uploadMethod'] === 'put') {
            $fileMime = mime_content_type("php://input"); // TODO?
            $putResource = fopen("php://input", "r"); // file data is from stdin
            $fileData = ''; // The only real change is that we're getting things from stdin as opposed to from the headers. Thus, we'll just translate the two here.

            while ($fileContents = fread($putResource, fimConfig::$fileUploadChunkSize)) { // Read the resource using 1KB chunks. This is slower than a higher chunk, but also avoids issues for now. It can be overridden with the config directive fileUploadChunkSize.
                $fileData .= $fileContents;
            }

            fclose($putResource);
        }
        else {
            $fileMime = mime_content_type($_FILES['file']['tmp_name']);
            $fileData = file_get_contents($_FILES['file']['tmp_name']);
        }


        /* Verify Against Empty Data */
        if (!strlen($request['fileName']))
            throw new fimError('badName', 'The filename is not valid.'); // This error may be expanded in the future.
        if (!strlen($fileData))
            throw new fimError('emptyData', 'No file contents were received.');


        /* Create the File Object */
        $file = new \Fim\File([
            'name' => $request['fileName'],
            'contents' => $fileData,
            'roomIdLink' => $request['roomId'],
            'userId' => $user->id
        ]);


        /* Verify Basic File Object Parameters */
        if (!$file->extension)
            throw new fimError('noFileExtension', 'The uploaded file did not have a file extension.');


        /* Verify the file's contents against any sent hashes. */
        if (isset($request['md5hash']) && $file->md5hash != $request['md5hash'])
            throw new fimError('badMd5Hash', 'The uploaded file\'s contents did not match those of its sent MD5 hash.');

        if (isset($request['sha256hash']) && $file->sha256hash != $request['sha256hash'])
            throw new fimError('badSha256Hash', 'The uploaded file\'s contents did not match those of its sent SHA256 hash.');

        if (isset($request['crc32bhash']) && $file->crc32bhash != $request['crc32bhash'])
            throw new fimError('badCrc32BHash', 'The uploaded file\'s contents did not match those of its sent CRC32B hash.');

        if (isset($request['fileSize']) && $file->size != $request['fileSize'])
            throw new fimError('badSize', 'The uploaded file\'s contents did not match those of its sent filesize.');


        /* File Type Restrictions */
        if (isset(fimConfig::$extensionChanges[$file->extension])) // Certain extensions are considered to be equivalent, so we only keep records for the primary one. For instance, "html" is considered to be the same as "htm" usually.
            $file->extension = fimConfig::$extensionChanges[$fileNameParts[1]];

        if (!in_array($file->extension, fimConfig::$allowedExtensions))
            throw new fimError('badExt', 'The filetype is forbidden, and thus the file cannot be uploaded.'); // Not allowed...

        if (!isset(fimConfig::$uploadMimes[$file->extension]))
            throw new fimError('unrecExt', 'The filetype is unrecognised, and thus the file cannot be uploaded.'); // All files theoretically need to have a mime (at any rate, we will require one). This is different from simply not being allowed, wherein we understand what file you are trying to upload, but aren't going to accept it. (Small diff, I know.)
        else if (fimConfig::$uploadMimes[$file->extension] !== $fileMime)
            throw new fimError('invalidFileContent', 'The upload file does not appear to be a valid file of its type.');


        /* File Size Restrictions */
        // Derived from File Type
        $maxSize = (fimConfig::$uploadSizeLimits[$file->extension] ? fimConfig::$uploadSizeLimits[$file->extension] : 0);

        if ($file->size > $maxSize)
            throw new Exception('tooLarge', 'The file is too large to upload; the maximum size is ' . $maxSize . 'B, and the file you uploaded was ' . $file->size . '.');


        /* Get Files with Existing, Matching Sha256 */
        $prefile = \Fim\Database::instance()->getFiles(array(
            'sha256hashes' => array($file->sha256hash)
        ))->getAsArray(false);


        /* Upload or Redirect, if Sha256 Match Found */
        if (count($prefile) > 0) { // The odds of a collision are astronomically low unless the server is handling an absolutely massive number of files. ...We could make the effort to detect the collision by actually comparing file contents, but it hardly seems worth the processing power.
            if ($roomData) \Fim\Database::instance()->storeMessage(new fimMessage([
                'room' => $roomData,
                'user' => $user,
                'text'    => $file->webLocation,
                'flag'    => $file->container,
                ]));
        }
        else {
            \Fim\Database::instance()->storeFile($file, $user, $roomData);
        }

        $xmlData['response']['webLocation'] = $file->webLocation;
    }
    elseif ($requestHead['_action'] === 'edit') {
        /*      $fileData = \Fim\Database::instance()->getFile($request['fileId']);

            if (!$fileData) {
              $errStr = 'invalidFile';
              $errDesc = 'The file specified is invalid.';
            }
            else {
              $parentalFileId = $request['fileId'];
            }
          }

          if ($parentalFileId > 0) {
            \Fim\Database::instance()->update("{$sqlPrefix}files", array(
              'parentalAge' => (int) $request['parentalAge'],
              'parentalFlags' => implode(',', $request['parentalFlags']),
            ), array(
              'fileId' => $request['fileId'],
            )); TODO */
    }
    break;

    // TODO
    case 'delete': case 'undelete':
        $fileData = \Fim\DatabaseSlave::instance()->getFiles(['fileIds' => $request['fileId']]);

        if ($user->hasPriv('modFiles') || $user->id == $fileData['userId']) {
            \Fim\Database::instance()->modLog('deleteImage', $request['fileId']);

            \Fim\Database::instance()->update(\Fim\Database::instance()->sqlPrefix . "files", array(
                'deleted' => ($requestHead['_action'] == 'delete' ? 1 : 0)
            ), array(
                'fileId' => $request['fileId'],
            ));
        }
        else new fimError('noPerm');
    break;

    case 'flag': // TODO: Allows users to flag images that are not appropriate for a room.

    break;
}


\Fim\Database::instance()->endTransaction();



/* Update Data for Errors */
if (fimConfig::$dev) $xmlData['request'] = $request;



/* Output Data */
echo new Http\ApiData($xmlData);
?>