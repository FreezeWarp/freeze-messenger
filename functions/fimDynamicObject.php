<?php
abstract class fimDynamicObject {
    /**
     * @var array The parameters that have been resolved for this instance of fimRoom. If an unresolved parameter is accessed, it will be resolved.
     */
    protected $resolved = array();

    /**
     * @var int Whether or not the object is known to have a corresponding entry in the database. (For now, true means it does, false either means we're not sure or it doesn't.)
     */
    protected $exists = false;


    /**
     * @var bool If this instance should be recached (because some major change has occurred).
     */
    public $doCache;


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
        else {
            $this->{$property} = $value;
        }


        if (!in_array($property, $this->resolved))
            $this->resolved[] = $property;
    }


    /**
     * Populates the object's parameters based on an associative array.
     *
     * @param array $data An array of object data.
     * @return bool Returns false if object data is empty, true otherwise.
     */
    public function populateFromArray(array $data) : bool {
        if ($data) {
            foreach ($data AS $attribute => $value) {
                $this->set($attribute, $value);
            }

            return true;
        }
        else {
            return false;
        }
    }


    /**
     * Caches the object instance, uniquely identified by its id parameter, to an available memory cache.
     * Because this is a destructor, we can't rely on caches that require instance information, e.g. redis and memcache, which both require server information to connect to, are off the table.
     * But we use apc and apcu if available.
     *
     * Note as well the usage of "apc_add" instead of "apc_store". This ensures that the cache does eventually become stale and get reread; otherwise, the cache may keep getting refreshed _from cached data_.
     */
    public function __destruct() {
        $key = 'fim_' . get_called_class() . '_' . $this->id;

        if ($this->id !== 0) {
            if (function_exists('apc_store'))
                apc_store($key, $this, 500);
            else if (function_exists('apcu_store'))
                apcu_store($key, $this, 500);
        }
    }


    abstract protected function getColumns(array $columns) : bool;


    /**
     * Resolves the list of properties from the database. Previously resolved properties are left alone.
     *
     * @param $properties array list of properties ot resolve
     */
    public function resolve(array $properties) {
        return $this->getColumns(array_diff($properties, $this->resolved));
    }

    /**
     * Resolves all database properties.
     */
    abstract public function resolveAll();


    /**
     * @return bool true when the object has a corresponding entry in the database, false otherwise
     */
    abstract public function exists() : bool;
}
?>