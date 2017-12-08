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

namespace Fim;

use \fimUser;
use \fimRoom;
use \fimError;
use \Exception;

class File extends MagicGettersSetters {
    /**
     * @var int The ID of the file.
     */
    protected $id;

    /**
     * @var int The version ID of the file.
     */
    protected $versionId;

    /**
     * @var string The contents of the file.
     */
    protected $contents;

    /**
     * @var string The name of the file.
     */
    protected $name;

    /**
     * @var int When the file was created.
     */
    protected $creationTime;

    /**
     * @var string The Sha256Hash of {@see $contents}. May be generated on-demand.
     */
    protected $sha256Hash;

    /**
     * @var string The Crc32bHash of {@see $contents}. May be generated on-demand.
     */
    protected $crc32bHash;

    /**
     * @var int The length of {@see $contents}. May be generated on-demand.
     */
    protected $size;

    /**
     * @var int The number of times this image has been reported.
     */
    protected $flags;

    /**
     * @var int The user who posted this image.
     */
    protected $user;

    /**
     * @var fimRoom The room this image was posted in (if any).
     */
    protected $room;


    public function __construct($fileData) {
        // When working with an existing file row, we require that all (almost) indexes be present.
        if ($fileData instanceof \Database\DatabaseResult) {
            $fileData = $fileData->getAsArray(false);
        }

        // When creating a new file.
        else if (!is_array($fileData)) {
            throw new Exception('Invalid message data specified -- must be an associative array corresponding to a table row. Passed: ' . print_r($fileData, true));
        }


        // Required Fields
        $this->name = $fileData['name'] ?? new fimError('badfimFile', 'fimFile must be invoked with a name.');

        // Not Entirely Required Fields
        $this->id = $fileData['id'] ?? 0;
        $this->versionId = $fileData['versionId'] ?? 0;
        $this->size = (int) ($fileData['size'] ?? 0);
        $this->contents = $fileData['contents'] ?? '';
        $this->sha256Hash = $fileData['sha256Hash'] ?? '';
        $this->creationTime = $fileData['creationTime'] ?? time();

        // Unimplemented
        $this->flags = $fileData['flags'] ?? 0;

        // Users and Rooms
        $userId = $fileData['userId'] ?? new fimError('badfimFile', 'fimFile must be invoked with userId.');
        $roomIdLink = $fileData['roomIdLink'] ?? new fimError('badfimFile', 'fimFile must be invoked with roomIdLink.');

        $this->user = UserFactory::getFromId($userId);
        $this->room = $roomIdLink
            ? new fimRoom($fileData['roomIdLink'])
            : null;
    }



    public function __get($property) {
        return parent::get($property);
    }

    public function __set($property, $value) {
        parent::set($property, $value);
    }



    /**
     * @return string The file extension of {@see $name}. {@see Config::$extensionChanges} will be used to convert common extensions to a single extension.
     */
    public function getExtension() {
        $extension = strtolower(pathinfo($this->name, PATHINFO_EXTENSION));

        return Config::$extensionChanges[$extension] ?? $extension;
    }

    /**
     * @return string {@see $sha256Hash}
     */
    public function getSha256Hash() {
        return $this->sha256Hash = $this->sha256Hash ?: fim_sha256($this->contents);
    }

    /**
     * @return string {@see $crc32bHash}
     */
    public function getCrc32bHash() {
        return $this->crc32bHash = $this->crc32bHash ?: (in_array('crc32b', hash_algos())
            ? hash('crc32b', $this->contents)
            : '');
    }

    /**
     * @return string The mime type of the file, based on the database of mime extensions, {@see Config::$uploadMimes}, and using {@see getExtension()} to lookup a mimetype. If none is detected, will default to "application/octet-stream".
     */
    public function getMime() {
        return Config::$uploadMimes[$this->__get('extension')]
            ? Config::$uploadMimes[$this->__get('extension')]
            : 'application/octet-stream';
    }

    /**
     * @return string The container that should be used to display this file, based on the database of file containers, {@see Config::$fileContainers}, and using {@see getExtension()} to lookup a container. If none is detected, will default to "other".
     */
    public function getContainer() {
        return Config::$fileContainers[$this->__get('extension')] ?? 'other';
    }

    /**
     * @return int {@see $size}
     */
    public function getSize() {
        return $this->size = $this->size ?: strlen($this->contents);
    }

    /**
     * @return string The fully-qualified web URI for this file.
     */
    public function getWebLocation() {
        global $installUrl;
        return "{$installUrl}file.php?sha256hash=" . $this->get('sha256hash');
    }
}