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

namespace Cache;

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
class CacheFactory {
    public $methods = [];


    public function addMethod($method, $servers) {
        global $tmpDir;

        $className = 'Cache' . ucfirst($method);
        $classNameSpaced = "\\Cache\\$className";
        $includePath = __DIR__ . "/{$className}.php";

        if (!file_exists($includePath)) {
            new \fimError('cacheMisconfigured', "Caches are currently misconfigured: a cache method, $method, has been specified without a corresponding cache class being available.");
        }
        else {
            require($includePath);

            if (!class_exists($classNameSpaced)) {
                new \fimError('cacheMisconfigured', "Caches are currently misconfigured: A cache method, $method, is installed on this server, but appears to be named incorrectly.");
            }
            else {
                /**
                 * @var CacheInterface
                 */
                $methodObject = new $classNameSpaced($servers);

                if (!$methodObject::available()) {
                    throw new \Exception("The cache method '$method' cannot be loaded, as the system does not support it.");
                }

                $this->methods[$methodObject->getCacheType()] = $methodObject;
            }
        }
    }


    private function chooseMethod($preferredMethod) {
        switch ($preferredMethod) {
            case 'memcached': // TODO: remove
            case 'redis':
            case CacheInterface::CACHE_TYPE_DISTRIBUTED:
                return $this->methods[CacheInterface::CACHE_TYPE_DISTRIBUTED]
                    ?? $this->methods[CacheInterface::CACHE_TYPE_MEMORY]
                    ?? null;
                break;

            case 'apc':
            case 'apcu':
            case CacheInterface::CACHE_TYPE_MEMORY:
                return $this->methods[CacheInterface::CACHE_TYPE_MEMORY]
                    ?? $this->methods[CacheInterface::CACHE_TYPE_DISTRIBUTED]
                    ?? null;


            case 'disk':
            case CacheInterface::CACHE_TYPE_DISK:
            default:
                return $this->methods[CacheInterface::CACHE_TYPE_MEMORY]
                    ?? $this->methods[CacheInterface::CACHE_TYPE_DISTRIBUTED]
                    ?? $this->methods[CacheInterface::CACHE_TYPE_DISK]
                    ?? null;
        }
    }

    /**
     * {@link CacheInterface::get($index)}
     */
    public function get($index, $preferredMethod = false) {
        if ($this->chooseMethod($preferredMethod))
            return $this->chooseMethod($preferredMethod)->get($index);
        else
            return false;
    }

    /**
     * {@link CacheInterface::set($index, $value, $ttl)}
     */
    public function set($index, $value, $ttl = 31536000, $preferredMethod = false) {
        if ($this->chooseMethod($preferredMethod))
            return $this->chooseMethod($preferredMethod)->set($index, $value, $ttl);
        else
            return false;
    }

    /**
     * {@link CacheInterface::exists($index, $value, $ttl)}
     */
    public function exists($index, $preferredMethod = false) : bool {
        if ($this->chooseMethod($preferredMethod))
            return $this->chooseMethod($preferredMethod)->exists($index);
        else
            return false;
    }


    /**
     * Adds an item to a set.
     * Redis is the best method here. At the moment, it will not fall back to any other method, though such can be specified manually.
     * All methods are supported, but with the exception of Redis they are slow and un-atomic.
     *
     * {@link CacheInterface::setAdd($index, $value)}
     */
    public function setAdd($index, $value, $preferredMethod = 'redis') {
        if ($this->chooseMethod($preferredMethod))
            $this->chooseMethod($preferredMethod)->setAdd($index, $value);
        else
            return false;
    }


    /**
     * {@link CacheInterface::setRemove($index, $value)}
     */
    public function setRemove($index, $value, $preferredMethod = 'redis') {

        if ($this->chooseMethod($preferredMethod))
            $this->chooseMethod($preferredMethod)->setRemove($index, $value);
        else
            return false;

    }


    /**
     * {@link CacheInterface::clear($index)}
     */
    public function clear($index, $preferredMethod = false) {
        if ($this->chooseMethod($preferredMethod))
            $this->chooseMethod($preferredMethod)->clear($index);
        else
            return false;
    }



    /**
     * {@link CacheInterface::clearAll()}
     */
    public function clearAll() {
        foreach ($this->methods AS $method) {
            $method->clearAll();
        }
    }


    /**
     * Return a mixed value containing as much information about the driver as possible.
     *
     * {@link CacheInterface::dump()}
     */
    public function dump($driver) {
        return $this->chooseMethod($driver)->dump();
    }
}
?>