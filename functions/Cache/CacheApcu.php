<?php
namespace Cache;

class CacheApcu implements CacheInterface {
    use CacheSetFallbackTrait;


    public static function available() : bool {
        return extension_loaded('apcu');
    }

    public static function getCacheType(): string {
        return CacheInterface::CACHE_TYPE_MEMORY;
    }


    public function get($index) {
        return apcu_fetch($index);
    }

    public function set($index, $value, $ttl = 3600) {
        return apcu_store($index, $value, $ttl);
    }

    public function add($index, $value, $ttl = 3600) {
        return apcu_add($index, $value, $ttl);
    }

    public function inc($index, $amt) : bool {
        return apcu_inc($index, (int) $amt) !== false;
    }

    public function exists($index) : bool {
        return apcu_exists($index);
    }

    public function clear($index) {
        return apcu_delete($index);
    }

    public function clearAll() {
        return apcu_clear_cache();
    }

    public function dump() {
        return apcu_cache_info();
    }
}