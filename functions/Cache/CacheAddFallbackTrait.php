<?php
namespace Cache;

Trait CacheAddFallbackTrait {
    public function add($index, $value, $ttl) {
        if (!$this->instance->exists($index)) {
            $this->instance->set($index, $value, $ttl);
        }
    }
}