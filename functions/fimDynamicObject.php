<?php
class fimDynamicObject {
    /**
     * Invokes setters, or sets by property. Adds properties to list of resolved properties.
     *
     * @param $property string The property to set.
     * @param $value mixed The value to set the property to.
     *
     * @throws Exception If a property doesn't exist.
     */
    protected function set($property, $value) {
        if (!property_exists($this, $property))
            throw new Exception('Invalid property to set in ' . get_called_class() . ': ' . $property);

        $setterName = 'set' . ucfirst($property);

        if (method_exists($this, 'set' . ucfirst($property)))
            $this->$setterName($value);
        else
            $this->{$property} = $value;

        if (!in_array($property, $this->resolved))
            $this->resolved[] = $property;
    }


    /**
     * Caches the object instance, uniquely identified by its id parameter, to an available memory cache.
     * Because this is a destructor, we can't rely on caches that require instance information, e.g. redis and memcache, which both require server information to connect to, are off the table.
     * But we use apc and apcu if available.
     *
     * Note as well the usage of "apc_add" instead of "apc_store". This ensures that the cache does eventually become stale and get reread; otherwise, the cache may keep getting refreshed _from cached data_.
     */
    public function __destruct() {
        $key = 'fim_' . get_called_class();

        if ($this->id !== 0) {
            if (function_exists('apc_store'))
                apc_store($key, $this, 500);
            else if (function_exists('apcu_store'))
                apcu_store($key, $this, 500);
        }
    }
}
?>