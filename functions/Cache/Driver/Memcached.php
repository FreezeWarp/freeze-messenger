<?php

namespace Cache\Driver;

class Memcached implements \Cache\DriverInterface
{
    use \Cache\CacheSetFallbackTrait;

    /**
     * @var \Memcached
     */
    private $instance;


    public static function available(): bool
    {
        return extension_loaded('memcached');
    }

    public static function getCacheType(): string
    {
        return \Cache\DriverInterface::CACHE_TYPE_DISTRIBUTED;
    }


    public function __construct($servers = [[]])
    {
        $this->instance = new \Memcached();

        $memcachedServers = [];
        foreach ($servers AS $server) {
            $server = array_merge([
                'host'   => '127.0.0.1',
                'port'   => 11211,
                'weight' => 0,
            ], $server);

            $memcachedServers[] = [$server['host'], $server['port'], $server['weight']];
        }

        $this->instance->addServers($memcachedServers);
    }

    public function get($index)
    {
        return $this->instance->get($index);
    }

    public function set($index, $value, $ttl = 3600)
    {
        return $this->instance->set($index, $value, $ttl);
    }

    public function add($index, $value, $ttl = 3600)
    {
        return $this->instance->add($index, $value, $ttl);
    }

    public function exists($index): bool
    {
        $this->instance->get($index);

        return $this->instance->getResultCode() != Memcached::RES_NOTFOUND;
    }

    public function inc($index, int $amt = 1)
    {
        return $this->instance->increment($index, $amt) !== false;
    }

    public function delete($index)
    {
        return $this->instance->delete($index);
    }

    public function deleteAll()
    {
        return $this->instance->flush();
    }

    public function dump()
    {
        return "";
    }
}