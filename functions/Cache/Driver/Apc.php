<?php

namespace Cache\Driver;

class Apc implements \Cache\DriverInterface
{
    use \Cache\CacheSetFallbackTrait;


    public static function available(): bool
    {
        return extension_loaded('apc');
    }

    public static function getCacheType(): string
    {
        return \Cache\DriverInterface::CACHE_TYPE_MEMORY;
    }


    public function get($index)
    {
        return apc_fetch($index);
    }

    public function set($index, $value, $ttl = 3600)
    {
        return apc_store($index, $value, $ttl);
    }

    public function add($index, $value, $ttl = 3600)
    {
        return apc_add($index, $value, $ttl);
    }

    public function exists($index): bool
    {
        return apc_exists($index);
    }

    public function inc($index, int $value = 1): bool
    {
        return apc_inc($index, $value) !== false;
    }

    public function delete($index)
    {
        return apc_delete($index);
    }

    public function deleteAll()
    {
        return apc_clear_cache('user');
    }

    public function dump()
    {
        return apc_cache_info();
    }
}