<?php
namespace Cache;

use Cache\FileCache;

class CacheDisk implements CacheInterface {
    use CacheSetFallbackTrait;

    /**
     * @var FileCache
     */
    private $instance;

    use CacheAddFallbackTrait;


    public static function available() : bool {
        return file_exists(__DIR__ . '/FileCache.php');
    }

    public static function getCacheType(): string {
        return CacheInterface::CACHE_TYPE_DISK;
    }


    public function __construct($servers) {
        require_once(__DIR__ . '/FileCache.php');

        $directory = (isset($servers['directory']) ? $servers['directory'] : realpath(sys_get_temp_dir()));

        if (is_writable($directory)) {
            $this->instance = new FileCache($directory . '/');
        }
        else {
            throw new \Exception('Could not create disk cache. Please ensure that PHP temp directory is set and writable (current value: ' . $directory . ').');
        }
    }


    public function get($index) {
        return $this->instance->get($index);
    }

    public function set($index, $value, $ttl) {
        return $this->instance->set($index, $value, $ttl);
    }

    public function exists($index) : bool {
        return $this->instance->exists($index);
    }

    public function clear($index) {
        return $this->instance->delete($index);
    }

    public function clearAll() {
        return $this->instance->deleteAll();
    }

    public function dump() {
        return $this->instance->dumpAll();
    }
}