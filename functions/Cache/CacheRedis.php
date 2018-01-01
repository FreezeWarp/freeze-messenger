<?php
namespace Cache;

use Redis;

class CacheRedis implements CacheInterface {
    /**
     * @var Redis
     */
    private $instance;

    /* setNx doesn't support ttl, so emulate */
    use CacheAddFallbackTrait;


    public static function available() : bool {
        return extension_loaded('redis');
    }

    public static function getCacheType(): string {
        return CacheInterface::CACHE_TYPE_DISTRIBUTED;
    }


    public function __construct($servers) {
        $servers = array_merge([
            'host' => 'localhost',
            'port' => 6379,
            'timeout' => 1000,
            'persistentId' => false,
            'password' => false
        ], $servers);

        $this->instance = new Redis();
        $this->instance->pconnect($servers['host'], $servers['port'], $servers['timeout'], $servers['persistentId']);
        if ($servers['password'])
            $this->instance->auth($servers['password']);
        $this->instance->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP); // TODO: igBinary?
    }


    public function get($index) {
        return $this->instance->get($index);
    }

    public function set($index, $value, $ttl = 3600) {
        return $this->instance->set($index, $value, $ttl);
    }

    public function exists($index) : bool {
        return $this->instance->exists($index);
    }

    public function inc($index, $amt) {
        return $this->instance->incrBy($index, $amt);
    }


    public function setAdd($index, $value) {
        $value = (array) $value;
        array_unshift($value, $index);
        return call_user_func_array(array($this->redis, 'sAdd'), $value);
    }

    public function setRemove($index, $value) {
        $value = (array) $value;
        array_unshift($value, $index);
        return call_user_func_array(array($this->redis, 'sRemove'), $value);
    }

    public function setContains($index, $value) : bool {
        return $this->instance->sContains($index, $value);
    }


    public function clear($index) {
        $this->instance->delete($index);
    }

    public function clearAll() {
        return $this->instance->flushDb();
    }

    public function dump() {
        return "";
    }
}
?>