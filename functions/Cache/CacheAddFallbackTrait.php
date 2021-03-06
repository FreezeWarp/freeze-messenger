<?php
namespace Cache;

Trait CacheAddFallbackTrait {
    public function add($index, $value, $ttl = 3600) {
        if (!$this->exists($index)) {
            return $this->set($index, $value, $ttl);
        }

        return false;
    }
}