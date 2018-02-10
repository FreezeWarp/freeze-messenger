<?php

namespace Cache;

/**
 * Class NS
 * @package Cache
 */
class NS
{
    /**
     * @var String[] A collection of namespace strings we are currently working with.
     */
    private $namespaces;

    /**
     * @var String Our last toString value, to avoid unnecessary cache lookups
     */
    private $toString;

    public function __construct($namespace)
    {
        $this->namespaces[] = $namespace;
    }


    /**
     * Add an additional namespace to the ones registered with this namespace object.
     *
     * @param $namespace String
     */
    public function ns($namespace) : NS
    {
        $this->namespaces[] = $namespace;
        $this->toString = null;

        return $this;
    }


    /**
     * Get the current namespace as a string for use in cache key/value operations.
     *
     * @return String
     */
    public function __toString(): String
    {
        // Return cached, if available
        if (!empty($this->toString))
            return $this->toString;

        // Sort the namespaces, so order of invocation doesn't matter
        sort($this->namespaces);

        // Get the corresponding namespace unique IDs from the cache, generating them if needed
        $namespaceValues = [];
        foreach ($this->namespaces AS $namespace) {
            if (!$namespaceValue = CacheFactory::get($namespace, DriverInterface::CACHE_TYPE_DISTRIBUTED)) {
                $namespaceValue = $namespace . ":" . uniqid();
                CacheFactory::add($namespace, $namespaceValue, 31536000, DriverInterface::CACHE_TYPE_DISTRIBUTED);
            }

            $namespaceValues[] = $namespaceValue;
        }

        // Return the namespace values separated by an underscore
        return implode('_', $namespaceValues);
    }


    /**
     * {@link CacheFactory::get($index, $preferredMethod}
     */
    public function get($index, $preferredMethod = false)
    {
        return CacheFactory::get($this->__toString() . '_' . $index, $preferredMethod);
    }


    /**
     * {@link CacheFactory::add($index, $value, $ttl, $preferredMethod)}
     */
    public function add($index, $value, $ttl = 31536000, $preferredMethod = false)
    {
        return CacheFactory::add($this->__toString() . '_' . $index, $value, $ttl, $preferredMethod);
    }


    /**
     * {@link CacheFactory::set($index, $value, $ttl, $preferredMethod)}
     */
    public function set($index, $value, $ttl = 31536000, $preferredMethod = false)
    {
        return CacheFactory::set($this->__toString() . '_' . $index, $value, $ttl, $preferredMethod);
    }


    /**
     * {@link CacheFactory::delete($index, $preferredMethod}
     */
    public function delete($index, $preferredMethod = false)
    {
        return CacheFactory::delete($this->__toString() . '_' . $index, $preferredMethod);
    }


    /**
     * Delete all entries belonging to any and all of the currently registered namespaces.
     */
    public function deleteAll()
    {
        $return = true;

        foreach ($this->namespaces AS $namespace) {
            $return = $return && CacheFactory::delete($namespace, DriverInterface::CACHE_TYPE_DISTRIBUTED);
        }

        return $return;
    }
}