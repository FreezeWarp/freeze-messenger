<?php
namespace Cache;

interface CacheInterface {
    /**
     * A cache that will be synced across all server instances.
     */
    const CACHE_TYPE_DISTRIBUTED = 'distributed';

    /**
     * A cache that will primarily write to disk.
     */
    const CACHE_TYPE_DISK = 'disk';

    /**
     * A cache that will primarily write to memory, and is not distributed.
     */
    const CACHE_TYPE_MEMORY = 'memory';


    /**
     * @return bool True if the cache is available to be loaded, false if it is not (e.g. an extension is not loaded).
     */
    public static function available() : bool;

    /**
     * @return string The type of the cache, one of the CACHE_TYPE_* constants.
     */
    public static function getCacheType() : string;


    /**
     * Get the cache entry stored at $index
     *
     * @param $index
     *
     * @return mixed
     */
    public function get($index);

    /**
     * Set a cache entry at $index with $value, which shouldn't be valid for more than $ttl.
     *
     * @param $index
     * @param $value
     * @param $ttl
     *
     * @return mixed
     */
    public function set($index, $value, $ttl);

    /**
     * @param $index
     *
     * @return mixed True if a key
     */
    public function exists($index) : bool;


    /**
     * Add $value to the set at $index.
     *
     * @param $index
     * @param $value
     *
     * @return mixed
     */
    public function setAdd($index, $value);

    /**
     * Remove $value from the set at $index.
     *
     * @param $index
     * @param $value
     *
     * @return mixed
     */
    public function setRemove($index, $value);

    /**
     * Whether the set at $index contains $value.
     *
     * @param $index
     * @param $value
     *
     * @return mixed
     */
    public function setContains($index, $value) : bool;


    /**
     * Delete the cache entry at $index.
     *
     * @param $index
     *
     * @return mixed
     */
    public function clear($index);

    /**
     * Delete all cache entries.
     *
     * @return mixed
     */
    public function clearAll();

    /**
     * Return a representation of the cache object and its values.
     *
     * @return mixed
     */
    public function dump();
}