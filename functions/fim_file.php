<?php

/**
 * Created by PhpStorm.
 * User: joseph
 * Date: 15/04/17
 * Time: 19:56
 */
class fimFile {
    private $contents;

    // Dervied from $contents
    private $sha256hash;
    private $md5hash;
    private $crc32bhash;
    private $size;
    private $webLocation;

    // Derived from name
    private $name;
    private $extension;
    private $mime;
    private $container;

    // other
    private $parentalAge = 0;
    private $parentalFlags = [];

    private $resolved = array();

    public function __construct($filename, $contents) {
        $this->name = $filename;
        $this->contents = $contents;
    }

    public function __get($property) {
        if (in_array($property, $this->resolved)) {
            return $this->{$property};
        }

        elseif ($this->contents && in_array($property, ['sha256hash', 'md5hash', 'crc32bhash', 'size', 'webLocation'])) {
            $this->resolved[] = $property;

            switch ($property) {
                case 'sha256hash':
                    $this->sha256hash = fim_sha256($this->contents);
                break;
                case 'md5hash':
                    $this->sha256hash = md5($this->contents);
                break;
                case 'crc32bhash':
                    $this->sha256hash = hash('crc32b', $this->contents);
                break;

                case 'size':
                    $this->size = strlen($this->contents);
                break;

                case 'webLocation':
                    global $installUrl;
                    $this->webLocation = "{$installUrl}file.php?sha256hash=" . $this->__get('sha256hash');
                break;
            }
        }

        elseif (in_array($property, ['extension', 'mime', 'container']) && $this->__get('name')) {
            $this->resolved[] = $property;

            switch ($property) {
                case 'extension':
                    $this->extension = pathinfo($this->name, PATHINFO_EXTENSION);
                break;

                case 'mime':
                    global $config;
                    $this->mime = $config['uploadMimes'][$this->__get('extension')] ? $config['uploadMimes'][$this->__get('extension')] : 'application/octet-stream';
                    break;

                case 'container':
                    global $config;
                    $this->container = $config['fileContainers'][$this->__get('extension')] ? $config['fileContainers'][$this->__get('extension')] : 'other';
                break;
            }
        }

        elseif ($property !== 'sha256hash' && $this->__get('sha256hash')) {
            // database
        }

        elseif (!property_exists($this, $property)) {
            throw new fimError("fimFileNoProperty", "fimFile does not have the requested property, '$property'");
        }

        else {
            throw new fimError("fimFileNotEnoughInfo", "fimFile does not have enough file state data to resolve the request property, '$property'");
        }

        return $this->{$property};
    }


    public function __set($property, $value) {
        if (property_exists($this, $property) && in_array($property, ["name", "parentalAge", "parentalFlags"])) {
            $this->{$property} = $value;

            if (!in_array($property, $this->resolved))
                $this->resolved[] = $property;
        }
        else
            throw new fimError("fimFileBadProperty", "fimFile does not have property '$property', or does not allow it to be written.");
    }
}