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
 * @copyright Joseph T. Parsons 2014
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
 *** response [[TODO]]
 */

$apiRequest = true;

require('../global.php');
require('../functions/fim_file.php');

/* Get Request Data */
$requestHead = fim_sanitizeGPC('g', array(
    '_action' => array(
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
    'dataEncode' => array(
        'require' => true,
        'valid' => array(
            'base64', 'binary',
        ),
    ),
));

/* If the upload method is put, we read directly from php://input */
$request = fim_sanitizeGPC(
    ($requestHead['uploadMethod'] === 'put' ? 'g' : 'p'), // If the uploadMethod is put, then we are reading from stdin, and there likely is no POST data available (since stdin is usually used to send POST data)... so use GET instead for everything.
    array(
        'fileName' => array(
            'require' => true,
            'trim' => true,
        ),
    
        'fileData' => array(
            'default' => '',
        ),
    
        'fileSize' => array(
            'cast' => 'int',
        ),
    
        'md5hash' => array(),
    
        'sha256hash' => array(),

        'crc32bhash' => array(),
    
        'roomId' => array(
            'default' => false,
            'cast' => 'roomId',
        ),
    
        'parentalAge' => array(
            'cast' => 'int',
            'valid' => $config['parentalAges'],
            'default' => $config['parentalAgeDefault'],
        ),
    
        'parentalFlags' => array(
            'default' => $config['parentalFlagsDefault'],
            'cast' => 'list',
            'valid' => $config['parentalFlags'],
        ),
    
        'fileId' => array(
            'default' => 0,
            'cast' => 'int',
        ),
    )
);



/* Data Predefine */
$xmlData = array(
    'response' => array(),
);



$database->startTransaction();



/* Start Processing */
switch ($requestHead['_action']) {
case 'edit': case 'create':
    $parentalFileId = 0;

    if ($requestHead['_action'] === 'create') {
        /* Get Room Data, if Applicable */
        if ($request['roomId']) $roomData = new fimRoom($request['roomId']);
        else $roomData = false;


        if (!$config['enableUploads'])
            throw new fimError('uploadsDisabled', 'Uploads are disabled on this FreezeMessenger server.');
        if (!$roomData && !$config['allowOrphanFiles'])
            throw new fimError('noOrphanFiles', 'Files cannot be orphaned on this FreezeMessenger server. You must post them to a room.');
        if ($config['uploadMaxFiles'] !== -1 && $database->getCounter('uploads') > $config['uploadMaxFiles'])
            throw new fimError('tooManyFilesServer', 'The server has reached its upload limit. No more uploads can be made.');
        if ($config['uploadMaxUserFiles'] !== -1 && $user->fileCount > $config['uploadMaxUserFiles'])
            throw new fimError('tooManyFilesUser', 'You have reached your upload limit. No more uploads can be made.');
        if ($config['uploadMaxSpace'] !== -1 && $database->getCounter('uploadSize') > $config['uploadMaxSpace'])
            throw new fimError('tooManyFilesServer', 'The server has reached its upload limit. No more uploads can be made.');
        if ($config['uploadMaxUserSpace'] !== -1 && $user->fileSize > $config['uploadMaxUserSpace'])
            throw new fimError('tooManyFilesUser', 'You have reached your upload limit. No more uploads can be made.');


        /* PUT Support (TODO) */
        if ($requestHead['uploadMethod'] === 'put') {
            $putResource = fopen("php://input", "r"); // file data is from stdin
            $request['fileData'] = ''; // The only real change is that we're getting things from stdin as opposed to from the headers. Thus, we'll just translate the two here.

            while ($fileContents = fread($putResource, $config['fileUploadChunkSize'])) { // Read the resource using 1KB chunks. This is slower than a higher chunk, but also avoids issues for now. It can be overridden with the config directive fileUploadChunkSize.
                $request['fileData'] .= $fileContents;
            }

            fclose($putResource);
        }


        /* Verify the Data, Preprocess */
        switch($requestHead['dataEncode']) {
            case 'base64': $rawData = base64_decode($request['fileData']); break;
            case 'binary': $rawData = $request['fileData'];                break; // Binary is unlikely to work unless using the PUT method.
            default:      throw new Exception('badEncoding');      break;
        }


        /* Verify Against Empty Data */
        if (!strlen($request['fileName']))
            throw new fimError('badName', 'The filename is not valid.'); // This error may be expanded in the future.
        if (!strlen($request['fileData']))
            throw new fimError('emptyData', 'No file contents were received.');


        /* Create the File Object */
        $file = new fimFile($request['fileName'], $rawData);
        $file->parentalAge = $request['parentalAge'];
        $file->parentalFlags = $request['parentalFlags'];


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
        if (isset($config['extensionChanges'][$file->extension])) // Certain extensions are considered to be equivalent, so we only keep records for the primary one. For instance, "html" is considered to be the same as "htm" usually.
            $file->extension = $config['extensionChanges'][$fileNameParts[1]];

        if (!isset($config['uploadMimes'][$file->extension]))
            throw new fimError('unrecExt', 'The filetype is unrecognised, and thus the file cannot be uploaded.'); // All files theoretically need to have a mime (at any rate, we will require one). This is different from simply not being allowed, wherein we understand what file you are trying to upload, but aren't going to accept it. (Small diff, I know.)

        if (!in_array($file->extension, $config['allowedExtensions']))
            throw new fimError('badExt', 'The filetype is forbidden, and thus the file cannot be uploaded.'); // Not allowed...


        /* Derived from File Type */
        $maxSize = ($config['uploadSizeLimits'][$file->extension] ? $config['uploadSizeLimits'][$file->extension] : 0);


        /* File Size Restrictions */
        if ($file->size > $maxSize)
            throw new Exception('tooLarge', 'The file is too large to upload; the maximum size is ' . $maxSize . 'B, and the file you uploaded was ' . $file->size . '.');


        /* Get Files with Existing, Matching Sha256 */
        $prefile = $database->getFiles(array(
            'sha256hashes' => array($file->sha256hash)
        ))->getAsArray(false);


        /* Upload or Redirect, if Sha256 Match Found */
        if (count($prefile) > 0) { // The odds of a collision are astronomically low unless the server is handling an absolutely massive number of files. ...We could make the effort to detect the collision by actually comparing file contents, but it hardly seems worth the processing power.
            if ($roomData) $database->storeMessage($file->webLocation, $file->container, $user, $roomData);
        }
        else {
            $database->storeFile($file, $user, $roomData);
        }

        $xmlData['response']['webLocation'] = $file->webLocation;
    }
    elseif ($requestHead['_action'] === 'edit') {
        /*      $fileData = $database->getFile($request['fileId']);

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
            )); TODO */
    }
break;

case 'delete':
    $fileData = $database->getFile($request['fileId']);

    if ($user->hasPriv('modFiles') || $user->id == $fileData['userId']) {
        $database->modLog('deleteImage', $request['fileId']);

        $database->update("{$sqlPrefix}files", array(
            'deleted' => 1,
        ), array(
            'fileId' => $request['fileId'],
        ));
    }
    else throw new Exception('noPerm');
break;

case 'undelete':
    $fileData = $database->getFile($request['fileId']);

    if ($user->hasPriv('modFiles')) {
        modLog('undeleteImage', $request['fileId']);

        $database->update("{$sqlPrefix}files", array(
            'deleted' => 0,
        ), array(
            'fileId' => $request['fileId'],
        ));
    }
    else throw new Exception('noPerm');
break;

case 'flag': // TODO: Allows users to flag images that are not appropriate for a room.

break;
}


$database->endTransaction();



/* Update Data for Errors */
if ($config['dev']) $xmlData['request'] = $request;



/* Output Data */
echo new apiData($xmlData);
?>