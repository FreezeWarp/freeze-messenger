<?php

namespace Cache;

/**
 *
 * @package FileCache - A simple file based cache
 * @author Erik Giberti
 * @copyright 2010 Erik Giberti, all rights reserved
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 * Class to implement a file based cache. This is useful for caching large objects such as
 * API/Curl responses or HTML results that aren't well suited to storing in small memory caches
 * or are infrequently accessed but are still expensive to generate.
 *
 * For security reasons, it's *strongly* recommended you set your cache directory to be outside
 * of your web root and on a drive independent of your operating system.
 *
 * Uses JSON to serialize the data object.
 *
 * Sample usage:
 *
 * $cache = new FileCache('/var/www/cache/');
 * $data = $cache->get('sampledata');
 * if(!$data){
 *      $data = array('a'=>1,'b'=>2,'c'=>3);
 *      $cache->set('sampledata', $data, 3600);
 * }
 * print $data['a'];
 *
 * Changes by Joseph T. Parsons:
 ** FIM syntax style
 ** exists function
 ** add function
 ** dump function
 ** serialize instead of json
 */

class FileCache {

    /**
     * Value is pre-pended to the cache, should be the full path to the directory
     */
    protected $root = null;

    /**
     * Prefix to append to all files created
     */
    protected $prefix = null;

    /**
     * @param string $root The root of the file cache.
     */
    function __construct ($root = '/tmp/', $prefix = 'fileCache_') {
        $this->root = $root;
        $this->prefix = $prefix;
    }

    /**
     * Saves data to the cache. Anything that evaluates to false, null, '', boolean false, 0 will
     * not be saved.
     * @param string $key An identifier for the data
     * @param mixed $data The data to save
     * @param int $ttl Seconds to store the data
     * @returns boolean True if the save was successful, false if it failed
     */
    public function set($key, $data = false, $ttl = 3600) {
        $key = $this->_make_file_key($key);

        $store = array(
            'data' => $data,
            'ttl'  => time() + $ttl,
        );

        $fh = fopen($key, "w+"); // Open file named with the key..

        flock($fh, LOCK_EX); // Lock the file.
        ftruncate($fh, 0); // Empty the file.
        if (!fwrite($fh, serialize($store))) { // Rewrite the file with the new contents.
            return false;
        }
        flock($fh, LOCK_UN); // Remove the lock on the file.
        fclose($fh); // Close the file from memory.

        return true;
    }

    /**
     * Increment existing cache entry.
     */
    public function inc($key, $amt = 1) {
        $key = $this->_make_file_key($key);

        // Get the data from the file
        $fh = @fopen($key, "a+");
        flock($fh, LOCK_EX); // Lock the file for reading.
        $file_content = @fread($fh, filesize($key)); // Get contents.

        // Assuming we got something back...
        if ($file_content) {
            $store = unserialize($file_content);
            $store['data'] += $amt;

            ftruncate($fh, 0); // Empty the file.
            if (!fwrite($fh, serialize($store))) { // Rewrite the file with the new contents.
                throw new \Exception('Could not write cache.');
            }
            flock($fh, LOCK_UN); // Remove the lock on the file.
            fclose($fh); // Close the file from memory.

            return true;
        }

        return false;
    }


    /**
     * Reads the data from the cache
     * @param string $key An identifier for the data
     * @returns mixed Data that was stored
     */
    public function get($key) {
        $key = $this->_make_file_key($key);
        $file_content = null;


        // Get the data from the file
        $file_content = @file_get_contents($key);

        if ($file_content !== false) {
            $store = @unserialize($file_content);

            if(!isset($store['ttl']) || $store['ttl'] < time()) { // If the cache has expired.
                @unlink($key); // remove the file

                return false;
            }

            return $store['data'];
        }

        return false;
    }


    /**
     * Remove a key, regardless of it's expire time
     * @param string $key An identifier for the data
     */
    public function delete($key) {
        return @unlink($this->_make_file_key($key));
    }

    /**
     * Checks if cache exists
     * @param string $key An identifier for the data
     * @returns bool
     */
    public function exists($key) {
        $key = $this->_make_file_key($key);
        $file_content = null;


        if (file_exists($key)) {
            return true;
        }
        else {
            return false;
        }
    }


    /**
     * Deletes all data
     */
    public function deleteAll() {
        $files = glob($this->root . $this->prefix . '*');

        foreach ($files AS $file) {
            if (!unlink($file)) { // Remove the file.
                throw new \Exception('Could not delete cache file ' . $file);
            }
        }

        return true;
    }


    /**
     * Dumps all data
     */
    public function dumpAll() {
        $files = glob($this->root . $this->prefix . '*');
        $data = [];

        foreach ($files AS $file) {
            $data[$file] = unserialize(@file_get_contents($file));
        }

        return $data;
    }


    /**
     * Create a key for the cache
     * @todo Beef up the cleansing of the file.
     * @param string $key The key to create
     * @returns string The full path and filename to access
     */
    private function _make_file_key($key) {
        $safe_key = str_replace(array('.','/',':','\''), array('_','-','-','-'), trim($key));
        return $this->root . $this->prefix . $safe_key;
    }
}