<?php
namespace Cache\Driver;

use Cache\CacheAddFallbackTrait;
use Cache\DriverInterface;

/**
 * A standard cache driver for Redis.
 * Note that {@see Redis::inc()} only works with values stored as integers.
 *
 * @package Cache\Driver
 */
class Redis implements DriverInterface {
    /**
     * @var \Redis
     */
    private $instance;

    /* setNx doesn't support ttl, so emulate */
    use CacheAddFallbackTrait;


    public static function available() : bool {
        return extension_loaded('redis');
    }

    public static function getCacheType(): string {
        return DriverInterface::CACHE_TYPE_DISTRIBUTED;
    }


    public function __construct($servers) {
        $servers = array_merge([
            'host' => 'localhost',
            'port' => 6379,
            'timeout' => 1000,
            'persistentId' => false,
            'password' => false
        ], $servers);

        $this->instance = new \Redis();
        $this->instance->pconnect($servers['host'], $servers['port'], $servers['timeout'], $servers['persistentId']);
        if ($servers['password'])
            $this->instance->auth($servers['password']);
    }


    public function get($index) {
        if ($this->instance->type($index) === \Redis::REDIS_SET) {
            return $this->instance->sMembers($index);
        }
        else {
            return ctype_digit($value = $this->instance->get($index))
                ? (int)$value
                : unserialize($value);
        }
    }

    public function set($index, $value, $ttl = 3600) {
        if (!is_int($value))
            $value = serialize($value);

        return $this->instance->set($index, $value, $ttl);
    }

    public function exists($index) : bool {
        return $this->instance->exists($index);
    }

    public function inc($index, int $amt = 1) {
        return $this->instance->incrBy($index, $amt) !== false;
    }


    public function setAdd($index, $value) {
        $value = (array) $value;
        array_unshift($value, $index);
        return call_user_func_array(array($this->instance, 'sAdd'), $value);
    }

    public function setRemove($index, $value) {
        $value = (array) $value;
        array_unshift($value, $index);
        return call_user_func_array(array($this->instance, 'sRemove'), $value);
    }

    public function setContains($index, $value) : bool {
        return $this->instance->sContains($index, $value);
    }


    public function clear($index) {
        return $this->instance->delete($index) === 1;
    }

    public function clearAll() {
        return $this->instance->flushDb();
    }

    public function dump() {
        $keys = [];

        foreach ($this->instance->getKeys('*') AS $key) {
            $keys[$key]['value'] = $this->get($key);
            $keys[$key]['ttl'] = $this->instance->ttl($key);
        }

        return $keys;
    }
}
