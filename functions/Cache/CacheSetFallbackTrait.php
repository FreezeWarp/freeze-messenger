<?php
namespace Cache;

trait CacheSetFallbackTrait {
    public function setAdd($index, $value) {
        if (!$this->exists($index))
            $this->set($index, [$value], null);

        else
            $this->set($index, array_merge($this->get($index), (array) $value), null);
    }

    public function setRemove($index, $value) {
        $this->set($index, array_diff($this->get($index), (array) $value), null);
    }

    public function setContains($index, $value) : bool {
        return in_array($value, $this->get($index));
    }
}