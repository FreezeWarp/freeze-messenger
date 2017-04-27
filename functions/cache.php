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
    public $methods;
    private $data = [];

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
        $this->addMethod($method, $servers);
    }

    public function addMethod($method, $servers) {
        global $config, $tmpDir;

        if ($method === 'apc') {
            if (extension_loaded('apcu'))
                $this->methods[] = 'apcu';

            elseif (extension_loaded('apc'))
                $this->methods[] = 'apc';

            else {

            }
        }

        elseif ($method === 'disk') {
            require_once('fileCache.php');
            $directory = (isset($servers['directory']) ? $servers['directory'] : $tmpDir);

            if (is_writable($tmpDir)) {
                $this->methods[] = $method;
                $this->fileCache = new FileCache($tmpDir . '/');
            }
            else {
                throw new Exception('Could not create disk cache. Please ensure that PHP temp directory is set and writable (current value: ' . $tmpDir . ').');
            }
        }

        elseif ($method === 'memcached' && extension_loaded('memcached')) {
            $this->methods[] = 'memcached';

            $this->memcached = new Memcached;

            foreach ($servers AS $server) {
                $this->memcached->addServer($server['host'], $server['port'], $server['persistent'], $server['weight'], $server['timeout'], $server['retry_interval']);
            }
        }

        elseif ($method === 'redis' && extension_loaded('redis')) {
            $this->methods[] = 'redis';

            $this->redis = new Redis();
            $this->redis->pconnect($servers['host'], $servers['port'], $servers['timeout'], $servers['persistentId']);
            if ($servers['password'])
                $this->redis->auth($servers['password']);
        }
    }


    private function chooseMethod($preferredMethod) {
        switch ($preferredMethod) {
            case 'memcached':
            case false:
                if (in_array('memcached', $this->methods)) return 'memcached';
                elseif (in_array('apcu', $this->methods)) return 'apcu';
                elseif (in_array('apc', $this->methods)) return 'apc';
                else return 'none';
                break;

            case 'disk':
                if (in_array('disk', $this->methods)) return 'disk';
                else return 'none';
                break;

            case 'redis':
                if (in_array('redis', $this->methods)) return 'redis';
                else return 'none';
                break;

            case 'apc':
            case 'apcu':
                if (in_array('apcu', $this->methods)) return 'apcu';
                elseif (in_array('apc', $this->methods)) return 'apc';
                elseif (in_array('memcached', $this->methods)) return 'memcached';
                else return 'none';
                break;

            default:
                throw new Exception('Unrecognised method: ' . $preferredMethod);
                break;
        }
    }

    /**
     * Retrives a variable from the cache.
     *
     * @param string index - The name of the cache entry.
     * @return mixed - Value of cache entry.
     *
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function get($index, $preferredMethod = false) {
        switch ($this->chooseMethod($preferredMethod)) {
            case 'none':
                return $this->data[$index];
                break;

            case 'disk':
                return $this->fileCache->get($index);
                break;

            case 'apc':
                return apc_fetch($index);
                break;

            case 'apcu':
                return apcu_fetch($index);
                break;

            case 'memcached':
                return $this->memcached->get($index);
                break;

            default:
                throw new Exception('Get: unknown cache method.');
                break;
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
    public function set($index, $variable, $ttl = 31536000, $preferredMethod = false) {
        switch ($this->chooseMethod($preferredMethod)) {
            case 'none':
                $this->data[$index] = $variable;
                break;

            case 'disk':
                $this->fileCache->set($index, $variable, $ttl);
                break;

            case 'apc':
                apc_delete($index);
                apc_store($index, $variable, $ttl);
                break;

            case 'apcu':
                apcu_delete($index);
                apcu_store($index, $variable, $ttl);
                break;

            case 'memcached':
                return $this->memcached->set($index);
                break;

            default:
                throw new Exception('Set: unknown cache method.');
                break;
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
    public function exists($index, $preferredMethod = false) : bool {
        switch ($this->chooseMethod($preferredMethod)) {
            case 'none':
                return isset($this->data[$index]); break;

            case 'disk':
                $this->fileCache->exists($index);
                break;

            case 'apc':
                return apc_exists($index);
                break;

            case 'apcu':
                return apcu_exists($index);
                break;

            case 'redis':
                return $this->redis->exists($index);
                break;

            case 'memcached':
                $this->memcached->get($index);
                return $this->memcached->getResultCode() == Memcached::RES_NOTFOUND;
                break;

            default:
                throw new Exception('Exists: unknown cache method.');
                break;
        }
    }


    /**
     * Adds an item to a set.
     * Redis is the best method here. At the moment, it will not fall back to any other method, though such can be specified manually.
     * All methods are supported, but with the exception of Redis they are slow and un-atomic.
     *
     * @param $index
     * @param $value
     * @param bool $preferredMethod
     * @return mixed
     * @throws Exception
     */
    public function setAdd($index, $value, $preferredMethod = 'redis') {
        $method = $this->chooseMethod($preferredMethod);
        switch ($method) {
            // These are not atomic. They can fail.
            case 'none':
            case 'apc':
            case 'apcu':
            case 'disk':
            case 'memcached':
                if (!$this->exists($index, $method))
                    $this->set($index, [$value], null, $method);
                else
                    $this->set($index, array_merge($this->get($index), (array) $value), null, $method);
                break;

            case 'redis':
                $value = (array) $value;
                array_unshift($value, $index);
                return call_user_func_array(array($this->redis, 'sAdd'), $value);
                break;

            default:
                throw new Exception('setAdd: unknown cache method.');
                break;
        }
    }


    public function setRemove($index, $value, $preferredMethod = 'redis') {
        $method = $this->chooseMethod($preferredMethod);
        switch ($method) {
            // These are not atomic. They can fail.
            case 'none':
            case 'apc':
            case 'apcu':
            case 'disk':
            case 'memcached':
                $this->set($index, array_diff($this->get($index), (array) $value), null, $method);
                break;

            case 'redis':
                $value = (array) $value;
                array_unshift($value, $index);
                return call_user_func_array(array($this->redis, 'sRemove'), $value);
                break;

            default:
                throw new Exception('setRemove: unknown cache method.');
                break;
        }
    }


    public function clear($index, $preferredMethod = false) : bool {
        switch ($this->chooseMethod($preferredMethod)) {
            case 'none':
                unset($this->data[$index]);
                return true;
                break;

            case 'disk':
                return $this->fileCache->delete($index);
                break;

            case 'apc':
                return apc_delete($index);
                break;

            case 'apcu':
                return apcu_delete($index);
                break;

            case 'redis':
                return $this->redis->delete($index);
                break;

            case 'memcached':
                return $this->memcached->delete($index);
                break;

            default:
                throw new Exception('clear: unknown cache method.');
                break;
        }
    }



    /**
     * Destroys the entire cache. (With APC, this will clear the user and the opcode cache as well.)
     *
     * @return bool - True on success, false on failure.
     *
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function clearAll() : bool {
        foreach ($this->methods AS $method) {
            switch ($method) {
                case 'none':
                    $this->data = [];

                    return true;
                    break;

                case 'disk':
                    return $this->fileCache->deleteAll();
                    break;

                case 'apc':
                    if (apc_clear_cache() && apc_clear_cache('user') && apc_clear_cache('opcode')) return true;
                    else return false;
                    break;

                case 'apcu':
                    if (apcu_clear_cache()) return true;
                    else return false;
                    break;

                case 'memcached':
                    return $this->memcached->flush();
                    break;

                case 'redis':
                    return $this->redis->flushDb();
                    break;

                default:
                    throw new Exception('clearAll: unknown cache method.');
                    break;
            }
        }
    }


    /**
     * Return a mixed value containing as much information about the driver as possible.
     *
     * @return array|bool
     */
    public function dump($driver) {
        switch ($driver) {
            case 'apc':
                return apc_cache_info();
                break;

            case 'apcu':
                return apcu_cache_info();
                break;

            case 'redis':
                $info = $this->redis->info();
                $keys = $this->redis->keys('*');

                foreach ($keys AS $key) {
                    $info['contents'][$key] = $this->redis->sGetMembers($key);
                }

                return $info;
                break;

            default:
                return [];
                break;
        }
    }
}
?>