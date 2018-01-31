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

/**
 * General cache facade that can register cache methods and invoke cache functions best matching a given cache method.
 *
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
class CacheFactory {
    public static $methods = [];


    /**
     * Add a cache method to the recognised cache types.
     *
     * @param string $method The name of the method.
     * @param mixed $servers Information to instantiate the cache method, usually server information.
     */
    public static function addMethod(string $method, $servers) {
        $classNameSpaced = '\Cache\Driver\\' . ucfirst($method);

        if (!class_exists($classNameSpaced)) {
            new \Fim\Error('cacheMisconfigured', "Caches are currently misconfigured: A cache method, $method, is installed on this server, but appears to be named incorrectly.");
        }
        else {
            /**
             * @var $methodObject DriverInterface
             */
            $methodObject = new $classNameSpaced($servers);

            if (!$methodObject::available()) {
                throw new \Exception("The cache method '$method' cannot be loaded, as the system does not support it.");
            }

            self::$methods[$methodObject->getCacheType()] = $methodObject;
        }
    }


    /**
     * Get the cache method that is most similar to the desired cache method type.
     *
     * @param string $preferredMethod The type of cache method, one of the {@see DriverInterface} CACHE_TYPE constants.
     * @return mixed|null
     */
    private static function chooseMethod($preferredMethod) {
        switch ($preferredMethod) {
            case DriverInterface::CACHE_TYPE_DISTRIBUTED_CRITICAL:
                return self::$methods[DriverInterface::CACHE_TYPE_DISTRIBUTED]
                    ?? null;
            break;

            case DriverInterface::CACHE_TYPE_DISTRIBUTED:
                return self::$methods[DriverInterface::CACHE_TYPE_DISTRIBUTED]
                    ?? self::$methods[DriverInterface::CACHE_TYPE_MEMORY]
                    ?? null;
            break;

            case DriverInterface::CACHE_TYPE_MEMORY:
                return self::$methods[DriverInterface::CACHE_TYPE_MEMORY]
                    ?? self::$methods[DriverInterface::CACHE_TYPE_DISTRIBUTED]
                    ?? null;
            break;

            case DriverInterface::CACHE_TYPE_DISK:
            case false:
                return self::$methods[DriverInterface::CACHE_TYPE_MEMORY]
                    ?? self::$methods[DriverInterface::CACHE_TYPE_DISTRIBUTED]
                    ?? self::$methods[DriverInterface::CACHE_TYPE_DISK]
                    ?? null;
            break;

            default:
                throw new \Exception('Unknown cache method: ' . $preferredMethod);
            break;
        }
    }

    /**
     * Check if a cache method of a given type has been registered.
     *
     * @param string $preferredMethod The type of cache method, one of the {@see DriverInterface} CACHE_TYPE constants.
     * @return bool
     */
    public static function hasMethod($preferredMethod) {
        return self::chooseMethod($preferredMethod) !== null;
    }

    /**
     * {@link DriverInterface::get($index)}
     */
    public static function get($index, $preferredMethod = false) {
        if (self::chooseMethod($preferredMethod))
            return self::chooseMethod($preferredMethod)->get($index);
        else
            return false;
    }

    /**
     * {@link DriverInterface::add($index, $value, $ttl)}
     */
    public static function add($index, $value, $ttl = 31536000, $preferredMethod = false) {
        if (self::chooseMethod($preferredMethod))
            return self::chooseMethod($preferredMethod)->add($index, $value, $ttl);
        else
            return false;
    }

    /**
     * {@link DriverInterface::set($index, $value, $ttl)}
     */
    public static function set($index, $value, $ttl = 31536000, $preferredMethod = false) {
        if (self::chooseMethod($preferredMethod))
            return self::chooseMethod($preferredMethod)->set($index, $value, $ttl);
        else
            return false;
    }

    /**
     * {@link DriverInterface::exists($index, $value, $ttl)}
     */
    public static function exists($index, $preferredMethod = false) : bool {
        if (self::chooseMethod($preferredMethod))
            return self::chooseMethod($preferredMethod)->exists($index);
        else
            return false;
    }


    /**
     * {@link DriverInterface::inc($index, $amt)}
     */
    public static function inc($index, $amt = 1, $preferredMethod = false) {
        if (self::chooseMethod($preferredMethod))
            return self::chooseMethod($preferredMethod)->inc($index, $amt);
        else
            return false;
    }


    /**
     * Adds an item to a set.
     * Redis is the best method here. At the moment, it will not fall back to any other method, though such can be specified manually.
     * All methods are supported, but with the exception of Redis they are slow and un-atomic.
     *
     * {@link DriverInterface::setAdd($index, $value)}
     */
    public static function setAdd($index, $value, $preferredMethod = DriverInterface::CACHE_TYPE_DISTRIBUTED) {
        if (self::chooseMethod($preferredMethod))
            return self::chooseMethod($preferredMethod)->setAdd($index, $value);
        else
            return false;
    }


    /**
     * {@link DriverInterface::setRemove($index, $value)}
     */
    public static function setRemove($index, $value, $preferredMethod = DriverInterface::CACHE_TYPE_DISTRIBUTED) {
        if (self::chooseMethod($preferredMethod))
            return self::chooseMethod($preferredMethod)->setRemove($index, $value);
        else
            return false;
    }


    /**
     * {@link DriverInterface::clear($index)}
     */
    public static function clear($index, $preferredMethod = false) {
        if (self::chooseMethod($preferredMethod))
            return self::chooseMethod($preferredMethod)->clear($index);
        else
            return false;
    }



    /**
     * {@link DriverInterface::clearAll()}
     */
    public static function clearAll() {
        $return = true;

        foreach (self::$methods AS $method) {
            $return = $method->clearAll() && $return;
        }

        return $return;
    }


    /**
     * Return a mixed value containing as much information about the driver as possible.
     *
     * {@link DriverInterface::dump()}
     */
    public static function dump($driver) {
        return self::chooseMethod($driver)->dump();
    }
}
?>