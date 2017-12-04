<?php
namespace Cache;

use Memcached;

class CacheMemcached implements CacheInterface {
    use CacheSetFallbackTrait;

    /**
     * @var Memcached
     */
    private $instance;


    public static function available() : bool {
        return extension_loaded('memcached');
    }

    public static function getCacheType(): string {
        return CacheInterface::CACHE_TYPE_DISTRIBUTED;
    }


    public function __construct($servers) {
        $this->instance = new Memcached();

        foreach ($servers AS $server) {
            $this->instance->addServer($server['host'], $server['port'], $server['persistent'], $server['weight'], $server['timeout'], $server['retry_interval']);
        }
    }

    public function get($index) {
        return $this->instance->get($index);
    }

    public function set($index, $value, $ttl) {
        return $this->instance->set($index, $value, $ttl);
    }

    public function add($index, $value, $ttl) {
        return $this->instance->add($index, $value, $ttl);
    }

    public function exists($index) : bool {
        $this->instance->get($index);
        return $this->instance->getResultCode() != Memcached::RES_NOTFOUND;
    }

    public function inc($index, $amt) {
        return $this->instance->increment($index, $amt);
    }

    public function clear($index) {
        return $this->instance->delete($index);
    }

    public function clearAll() {
        return $this->instance->flush();
    }

    public function dump() {
        return "";
    }
}