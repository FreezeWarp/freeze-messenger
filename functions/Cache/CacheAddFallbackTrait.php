<?php
namespace Cache;

Trait CacheAddFallbackTrait {
    public function add($index, $value, $ttl = 3600) {
        if (!$this->instance->exists($index)) {
            return $this->instance->set($index, $value, $ttl);
        }

        return false;
    }
}