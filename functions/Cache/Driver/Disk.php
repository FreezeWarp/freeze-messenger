<?php

namespace Cache\Driver;

class Disk implements \Cache\DriverInterface
{
    use \Cache\CacheSetFallbackTrait;

    /**
     * @var \Cache\FileCache
     */
    private $instance;

    use \Cache\CacheAddFallbackTrait;


    public static function available(): bool
    {
        return class_exists('\\Cache\\FileCache');
    }

    public static function getCacheType(): string
    {
        return \Cache\DriverInterface::CACHE_TYPE_DISK;
    }


    public function __construct($servers)
    {
        $directory = (isset($servers['directory']) ? $servers['directory'] : realpath(sys_get_temp_dir()));

        if (is_writable($directory)) {
            $this->instance = new \Cache\FileCache($directory . '/');
        }
        else {
            throw new \Exception('Could not create disk cache. Please ensure that PHP temp directory is set and writable (current value: ' . $directory . ').');
        }
    }


    public function get($index)
    {
        return $this->instance->get($index);
    }

    public function set($index, $value, $ttl = 3600)
    {
        return $this->instance->set($index, $value, $ttl);
    }

    public function exists($index): bool
    {
        return $this->instance->exists($index);
    }

    public function inc($index, int $amt = 1)
    {
        return $this->instance->inc($index, $amt);
    }

    public function delete($index)
    {
        return $this->instance->delete($index);
    }

    public function deleteAll()
    {
        return $this->instance->deleteAll();
    }

    public function dump()
    {
        return $this->instance->dumpAll();
    }
}