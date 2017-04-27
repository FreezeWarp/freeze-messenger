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


/* Note: "none" is used solely for speed testing. NEVER EVER EVER use it. (at least with FIM) */

/**
 * Cache layer to support file caches, APC, and (eventually) memcached.
 * An explanation of the support cache methods:
 ** 'none' - If specified, all cache variables will be stored as standard variables in the class. Thus, no cache will actually be used, but all functionality should still work.
 ** 'disk' - If specified, all cache variables will be written to the disk (usually as temporary files).
 ** 'apc' - If specified, all cache variables will be stored using APC.
 ** 'memcached' - If spceified, all cache variables wil be stored using memcached. (Not yet supported.)
 *
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
class generalCache {
    /**
     * Initialises class.
     *
     * @param string method - Cache method to use (will guess if not provided).
     * @param array servers - Servers used for Memcached. (not yet supported).
     * @return void
     *
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function __construct($method = '', $servers) {
        global $config, $tmpDir;

        $this->data = array();
        $this->method = $method;

        // Basically, use APC if we can, unless told not to. If we can't, use disk.
        if ($method !== 'apcu' && $method !== 'apc' && $method !== 'disk') {
            if (extension_loaded('apcu')) $this->method = 'apcu';
            if (extension_loaded('apc')) $this->method = 'apc';
            else $this->method = 'disk';
        }
        elseif ($method === 'apc' && extension_loaded('apcu')) $this->method = 'apcu';
        elseif ($method === 'apc' && !extension_loaded('apc')) $this->method = 'disk';
        elseif ($method === 'apcu' && !extension_loaded('apcu')) $this->method = 'disk';

        if ($this->method === 'disk') {
            require_once('fileCache.php');

            if (is_writable($tmpDir)) {
                $this->fileCache = new FileCache($tmpDir . '/');
            }
            else {
                throw new Exception('Could not create disk cache. Please ensure that PHP temp directory is set and writable (current value: ' . $tmpDir . ').');
            }
        }

        /*    if ($this->method === 'memcache') {
              $memcache = new Memcache;

              foreach ($servers AS $server) {
                $memcache->addServer($server['host'], $server['port'], $server['persistent'], $server['weight'], $server['timeout'], $server['retry_interval']);
              }
            }*/
    }

    /**
     * Retrives a variable from the cache.
     *
     * @param string index - The name of the cache entry.
     * @return mixed - Value of cache entry.
     *
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function get($index) {
        switch ($this->method) {
        case 'none': return $this->data[$index]; break;
        case 'disk': return $this->fileCache->get($index); break;
        case 'apc': return apc_fetch($index); break;
        case 'apcu': return apcu_fetch($index); break;
        case 'memcache': break;
        default: throw new Exception('Unknown cache method.'); break;
        }
    }

    /**
     * Stores a variable in the cache.
     *
     * @param string index - The name of the cache entry.
     * @param mixed variable - The data to store.
     * @param int ttl - The time before the variable is considered to have "expired", in seconds. (Default is 1 year = 31536000 seconds)
     * @return void
     *
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function set($index, $variable, $ttl = 31536000) {
        switch ($this->method) {
        case 'none': $this->data[$index] = $variable; break;
        case 'disk': $this->fileCache->set($index, $variable, $ttl); break;
        case 'apc': apc_delete($index); apc_store($index, $variable, $ttl); break;
        case 'apcu': apcu_delete($index); apcu_store($index, $variable, $ttl); break;
        case 'memcache':  break;
        default: throw new Exception('Unknown cache method.'); break;
        }
    }

    /**
     * Determines if a variable exists in the cache.
     *
     * @param string index - The name of the cache entry.
     * @return void
     *
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function exists($index) {
        switch ($this->method) {
        case 'none': return isset($this->data[$index]); break;
        case 'disk': $this->fileCache->exists($index); break;
        case 'apc': return apc_exists($index); break;
        case 'apcu': return apcu_exists($index); break;
        case 'memcache': break;
        default: throw new Exception('Unknown cache method.'); break;
        }
    }


    /**
     * Destroys the entire cache. (With APC, this will clear the user and the opcode cache as well.)
     *
     * @return bool - True on success, false on failure.
     *
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function clearAll() {
        switch ($this->method) {
        case 'none': $this->data = array(); return true; break;
        case 'disk': return $this->fileCache->deleteAll(); break;

        case 'apc':
            if (apc_clear_cache() && apc_clear_cache('user') && apc_clear_cache('opcode')) return true;
            else return false;
        break;

        case 'apcu':
            if (apcu_clear_cache()) return true;
            else return false;
        break;

        case 'memcache': break;
        default: throw new Exception('Unknown cache method.'); break;
        }
    }

    public function dump() {
        switch ($this->method) {
            case 'apc':
                return apc_cache_info();
                break;

            case 'apcu':
                return apcu_cache_info();
                break;

            default:
                return [];
                break;
        }
    }
}
?>