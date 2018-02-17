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
 * Represents a "file" object in some context, performing file enumerations and file posts:
 ** When used with a GET request, this will retrieve a list of files. If a file ID is provided, it will retrieve a single file's information, otherwise it will retrieve files who match certain parameters.
 ** When used with a PUT request, this will create a new file.
 ** (TODO) When used with a delete request, the file will be marked as deleted, though the file data may remain on the server.
 ** To view a specific file, use the /file.php API.
 *
 *
 * = Create File Directives =
 * @param string uploadMethod='raw' - How the file is being transferred from the server, either:
 ** 'raw' - File is being uploaded as an HTTP upload.
 ** 'put' - File is being transferred via PUT.
 * @param string fileName - The name of the file. [[Required.]]
 * @param int fileSize - The size of the file (in bytes), used for checks.
 * @param string md5hash - The MD5 hash of the file, used for checks.
 * @param string sha256hash - The SHA256 hash of the file, used for checks.
 * @param string crc32bhash - The CRC32b hash of the file, used for checks.
 * @param int roomId - If the image is to be directly posted to a room, specify the room ID here. This may be required, depending on server settings.
 *
 * = Errors =
 * == Creating Files ==
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
 */

/* Common Resources */

use Fim\Error;

class file
{
    static $xmlData;

    static $requestHead;

    /**
     * @var \Fim\File
     */
    static $file;

    static function init()
    {
        self::$requestHead = \Fim\Utilities::sanitizeGPC('g', [
            '_action' => [],
        ]);

        self::$requestHead = array_merge(self::$requestHead, (array)\Fim\Utilities::sanitizeGPC('g', [
            'id' => [
                'cast' => 'int',
                'require' => in_array(self::$requestHead['_action'], ['edit', 'delete'])
            ],
        ]));

        if (isset(self::$requestHead['id'])) {
            if (self::$requestHead['_action'] === 'create') // ID shouldn't be used here.
                new \Fim\Error('idExtra', 'Parameter ID should not be used with PUT requests.');

            try {
                self::$file = \Fim\Database::instance()->getFiles(['fileIds' => self::$requestHead['id']])->getAsObject('\\Fim\\File');
            } catch (Exception $ex) {
                new \Fim\Error('idNoExist', 'The given "id" parameter does not correspond with a real room.');
            }
        }

        self::{self::$requestHead['_action']}();
    }


    static function get()
    {
        /* Get Request Data */
        $request = \Fim\Utilities::sanitizeGPC('g', array(
            'fileIds' => [
                'default'  => [],
                'cast'     => 'list',
                'filter'   => 'roomId',
                'evaltrue' => true,
                'max'      => 50,
            ],

            'userIds' => array(
                'default' => array(\Fim\LoggedInUser::instance()->id),
                'cast' => 'list',
                'filter' => 'int',
                'evaltrue' => true,
                'max' => 10,
            ),

            'page' => [
                'cast' => 'int'
            ]
        ));

        \Fim\Database::instance()->accessLog('getFiles', $request);



        /* Data Pre-Define */
        self::$xmlData = ['files' => []];



        /* Start Processing */
        do {
            $filesQuery = \Fim\DatabaseSlave::instance()->getFiles([
                'fileIds' => $request['fileIds'],
                'userIds' => $request['userIds']
            ], ['id' => 'asc'], 10, $request['page']);

            foreach ($filesQuery->getAsObjects('\\Fim\\File') AS $file) {
                // Files can only be viewed by admins and the user themselves, and users with permission to view the room the file was posted in.
                if (!\Fim\LoggedInUser::instance()->hasPriv('modFiles') && $file->user->id != \Fim\LoggedInUser::instance()->id) {
                    if (!$file->room || (!(\Fim\Database::instance()->hasPermission(\Fim\LoggedInUser::instance(), $file->room) & \Fim\Room::ROOM_PERMISSION_VIEW)))
                        continue;
                }

                self::$xmlData['files'][] = array_merge([
                    'userId' => $file->user->id,
                    'roomId' => $file->room->id
                ], \Fim\Utilities::objectArrayFilterKeys($file, ['id', 'name', 'size', 'container', 'sha256Hash', 'webLocation']));
            }

            $request['page']++;

            // We relog so that the next query counts as part of the flood detection. (If we go over the flood limit, catch the exception and return with where to continue searching from.)
            try {
                \Fim\Database::instance()->accessLog('getFiles', $request);
            } catch (\Fim\ErrorThrown $ex) {
                // TODO: test
                self::$xmlData['metadata']['nextPage'] = $request['page'];
                return;
            }
        } while ($filesQuery->paginated && count(self::$xmlData['files']) === 0);
    }


    static function create()
    {
        self::$requestHead = array_merge(self::$requestHead, \Fim\Utilities::sanitizeGPC('g', array(
            'uploadMethod' => array(
                'default' => 'raw',
                'valid' => array(
                    'raw', 'put',
                ),
            ),
        )));

        /* If the upload method is put, we read directly from php://input */
        $request = \Fim\Utilities::sanitizeGPC(
            (self::$requestHead['uploadMethod'] === 'put' ? 'g' : 'p'), // If the uploadMethod is put, then we are reading from stdin, and there likely is no POST data available (since stdin is usually used to send POST data)... so use GET instead for everything.
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
        self::$xmlData = array(
            'response' => array(),
        );



        \Fim\Database::instance()->startTransaction();


        /* Get Room Data, if Applicable */
        $roomData = $request['roomId']
            ? \Fim\RoomFactory::getFromId($request['roomId'])
            : false;


        if (!\Fim\Config::$enableUploads)
            new \Fim\Error('uploadsDisabled', 'Uploads are disabled on this FreezeMessenger server.');
        if (!$roomData && !\Fim\Config::$allowOrphanFiles)
            new \Fim\Error('noOrphanFiles', 'Files cannot be orphaned on this FreezeMessenger server. You must post them to a room.');
        if (\Fim\Config::$uploadMaxFiles !== -1 && \Fim\Database::instance()->getCounterValue('uploads') > \Fim\Config::$uploadMaxFiles)
            new \Fim\Error('tooManyFilesServer', 'The server has reached its upload limit. No more uploads can be made.');
        if (\Fim\Config::$uploadMaxUserFiles !== -1 && \Fim\LoggedInUser::instance()->fileCount > \Fim\Config::$uploadMaxUserFiles)
            new \Fim\Error('tooManyFilesUser', 'You have reached your upload limit. No more uploads can be made.');
        if (\Fim\Config::$uploadMaxSpace !== -1 && \Fim\Database::instance()->getCounterValue('uploadSize') > \Fim\Config::$uploadMaxSpace)
            new \Fim\Error('tooManyFilesServer', 'The server has reached its upload limit. No more uploads can be made.');
        if (\Fim\Config::$uploadMaxUserSpace !== -1 && \Fim\LoggedInUser::instance()->fileSize > \Fim\Config::$uploadMaxUserSpace)
            new \Fim\Error('tooManyFilesUser', 'You have reached your upload limit. No more uploads can be made.');


        /* PUT Support */
        if (self::$requestHead['uploadMethod'] === 'put') {
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
            'userId' => \Fim\LoggedInUser::instance()->id
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
                'user' => \Fim\LoggedInUser::instance(),
                'text'    => $file->webLocation,
                'flag'    => $file->container,
            ]));
        }
        else {
            \Fim\Database::instance()->storeFile($file, \Fim\LoggedInUser::instance(), $roomData);
        }

        $xmlData['response']['webLocation'] = $file->webLocation;


        \Fim\Database::instance()->endTransaction();



        /* Update Data for Errors */
        if (\Fim\Config::$dev) $xmlData['request'] = $request;
    }


    static function edit()
    {
        new \Fim\Error('unimplemented');
    }


    static function delete()
    {
        if (self::$file->user->id != \Fim\LoggedInUser::instance()->id
            && !\Fim\LoggedInUser::instance()->hasPriv('modFiles')) {
            new \Fim\Error('noPerm', 'You do not have permission to delete that file.');
        }

        \Fim\Database::instance()->modLog('deleteFile', self::$file->id);
        \Fim\Database::instance()->deleteFile(self::$file);
        self::$xmlData = [];
    }
}

/* Entry Point Code */
$apiRequest = true;
require('../global.php');
file::init();
echo new Http\ApiData(file::$xmlData);