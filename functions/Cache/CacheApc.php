<?php
namespace Cache;

class CacheApc implements CacheInterface {
    use CacheSetFallbackTrait;


    public static function available() : bool {
        return extension_loaded('apc');
    }

    public static function getCacheType(): string {
        return CacheInterface::CACHE_TYPE_MEMORY;
    }


    public function get($index) {
        return apc_fetch($index);
    }

    public function set($index, $value, $ttl = 3600) {
        return apc_store($index, $value, $ttl);
    }

    public function add($index, $value, $ttl = 3600) {
        return apc_add($index, $value, $ttl);
    }

    public function exists($index) : bool {
        return apc_exists($index);
    }

    public function inc($index, $value) : bool {
        return apc_inc($index, $value) !== false;
    }

    public function clear($index) {
        return apc_delete($index);
    }

    public function clearAll() {
        return (apc_clear_cache()
            && apc_clear_cache('user')
            && apc_clear_cache('opcode'));
    }

    public function dump() {
        return apc_cache_info();
    }
}