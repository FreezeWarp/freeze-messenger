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


use Fim\Error;

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
            'require' => !\Fim\Config::$allowOrphanFiles,
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
        if ($request['roomId']) $roomData = \Fim\RoomFactory::getFromId($request['roomId']);
        else $roomData = false;


        if (!\Fim\Config::$enableUploads)
            new \Fim\Error('uploadsDisabled', 'Uploads are disabled on this FreezeMessenger server.');
        if (!$roomData && !\Fim\Config::$allowOrphanFiles)
            new \Fim\Error('noOrphanFiles', 'Files cannot be orphaned on this FreezeMessenger server. You must post them to a room.');
        if (\Fim\Config::$uploadMaxFiles !== -1 && \Fim\Database::instance()->getCounterValue('uploads') > \Fim\Config::$uploadMaxFiles)
            new \Fim\Error('tooManyFilesServer', 'The server has reached its upload limit. No more uploads can be made.');
        if (\Fim\Config::$uploadMaxUserFiles !== -1 && $user->fileCount > \Fim\Config::$uploadMaxUserFiles)
            new \Fim\Error('tooManyFilesUser', 'You have reached your upload limit. No more uploads can be made.');
        if (\Fim\Config::$uploadMaxSpace !== -1 && \Fim\Database::instance()->getCounterValue('uploadSize') > \Fim\Config::$uploadMaxSpace)
            new \Fim\Error('tooManyFilesServer', 'The server has reached its upload limit. No more uploads can be made.');
        if (\Fim\Config::$uploadMaxUserSpace !== -1 && $user->fileSize > \Fim\Config::$uploadMaxUserSpace)
            new \Fim\Error('tooManyFilesUser', 'You have reached your upload limit. No more uploads can be made.');


        /* PUT Support */
        if ($requestHead['uploadMethod'] === 'put') {
            $fileMime = mime_content_type("php://input"); // TODO?
            $putResource = fopen("php://input", "r"); // file data is from stdin
            $fileData = ''; // The only real change is that we're getting things from stdin as opposed to from the headers. Thus, we'll just translate the two here.

            while ($fileContents = fread($putResource, \Fim\Config::$fileUploadChunkSize)) { // Read the resource using 1KB chunks. This is slower than a higher chunk, but also avoids issues for now. It can be overridden with the config directive fileUploadChunkSize.
                $fileData .= $fileContents;
            }

            fclose($putResource);
        }
        else {
            if (!$_FILES['file']['tmp_name']) {
                new \Fim\Error('uploadFailed', 'The upload was not successful. This is most likely because the server is not configured to support large file uploads. (Using the PUT method may be more successful.)');
            }

            $fileMime = mime_content_type($_FILES['file']['tmp_name']);
            $fileData = file_get_contents($_FILES['file']['tmp_name']);
        }


        /* Verify Against Empty Data */
        if (!strlen($request['fileName']))
            new \Fim\Error('badName', 'The filename is not valid.'); // This error may be expanded in the future.
        if (!strlen($fileData))
            new \Fim\Error('emptyData', 'No file contents were received.');


        /* Create the File Object */
        $file = new \Fim\File([
            'name' => $request['fileName'],
            'contents' => $fileData,
            'roomIdLink' => $request['roomId'],
            'userId' => $user->id
        ]);


        /* Verify Basic File Object Parameters */
        if (!$file->extension)
            new \Fim\Error('noFileExtension', 'The uploaded file did not have a file extension.');


        /* Verify the file's contents against any sent hashes. */
        if (isset($request['md5hash']) && $file->md5hash != $request['md5hash'])
            new \Fim\Error('badMd5Hash', 'The uploaded file\'s contents did not match those of its sent MD5 hash.');

        if (isset($request['sha256hash']) && $file->sha256hash != $request['sha256hash'])
            new \Fim\Error('badSha256Hash', 'The uploaded file\'s contents did not match those of its sent SHA256 hash.');

        if (isset($request['crc32bhash']) && $file->crc32bhash != $request['crc32bhash'])
            new \Fim\Error('badCrc32BHash', 'The uploaded file\'s contents did not match those of its sent CRC32B hash.');

        if (isset($request['fileSize']) && $file->size != $request['fileSize'])
            new \Fim\Error('badSize', 'The uploaded file\'s contents did not match those of its sent filesize.');


        /* File Type Restrictions */
        if (isset(\Fim\Config::$extensionChanges[$file->extension])) // Certain extensions are considered to be equivalent, so we only keep records for the primary one. For instance, "html" is considered to be the same as "htm" usually.
            $file->extension = \Fim\Config::$extensionChanges[$fileNameParts[1]];

        if (!in_array($file->extension, \Fim\Config::$allowedExtensions))
            new \Fim\Error('badExt', 'The filetype is forbidden, and thus the file cannot be uploaded.'); // Not allowed...

        // All files theoretically need to have a mime (at any rate, we will require one). This is different from simply not being allowed, wherein we understand what file you are trying to upload, but aren't going to accept it. (Small diff, I know.)
        if (!isset(\Fim\Config::$uploadMimes[$file->extension]))
            new \Fim\Error('unrecExt', 'The filetype is unrecognised, and thus the file cannot be uploaded.');
        // We "check" the contents of a file to see if they match the extension-determined mimetype if the extension is in the list of those to check.
        else if (in_array($file->extension, \Fim\Config::$uploadMimeProof)
            && \Fim\Config::$uploadMimes[$file->extension] !== $fileMime)
            new \Fim\Error('invalidFileContent', 'The upload file does not appear to be a valid file of its type.');


        /* File Size Restrictions */
        // Derived from File Type
        $maxSize = (\Fim\Config::$uploadSizeLimits[$file->extension] ? \Fim\Config::$uploadSizeLimits[$file->extension] : 0);

        if ($file->size > $maxSize)
            new \Fim\Error('tooLarge', 'The file is too large to upload; the maximum size is ' . $maxSize . 'B, and the file you uploaded was ' . $file->size . '.');


        /* Get Files with Existing, Matching Sha256 */
        $prefile = \Fim\Database::instance()->getFiles(array(
            'sha256hashes' => array($file->sha256hash)
        ))->getAsArray(false);


        /* Upload or Redirect, if Sha256 Match Found */
        if (count($prefile) > 0) { // The odds of a collision are astronomically low unless the server is handling an absolutely massive number of files. ...We could make the effort to detect the collision by actually comparing file contents, but it hardly seems worth the processing power.
            if ($roomData) \Fim\Database::instance()->storeMessage(new \Fim\Message([
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

            \Fim\Database::instance()->update(\Fim\Database::$sqlPrefix . "files", array(
                'deleted' => ($requestHead['_action'] == 'delete' ? 1 : 0)
            ), array(
                'fileId' => $request['fileId'],
            ));
        }
        else new \Fim\Error('noPerm');
    break;

    case 'flag': // TODO: Allows users to flag images that are not appropriate for a room.

    break;
}


\Fim\Database::instance()->endTransaction();



/* Update Data for Errors */
if (\Fim\Config::$dev) $xmlData['request'] = $request;



/* Output Data */
echo new Http\ApiData($xmlData);
?>