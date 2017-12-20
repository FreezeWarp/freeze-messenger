<?php
namespace Fim;

abstract class DynamicObject extends MagicGettersSetters {
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
     * Adds properties to list of resolved properties.
     * @see magicGettersSetters::set()
     */
    public function set(string $property, $value) {
        parent::set($property, $value);

        if (!in_array($property, $this->resolved))
            $this->resolved[] = $property;
    }


    public function get(string $property) {
        if (!$this->hasGetter($property)
            && !in_array($property, $this->resolved)) {
            $this->resolveFromPullGroup($property);
        }

        return parent::get($property);
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
     * Resolves the needle property and all similar properties.
     *
     * @param $needle
     *
     * @throws Exception If matching pullgroup not found.
     */
    public function resolveFromPullGroup(string $needle) {
        $groupPointer = [];

        foreach (static::$pullGroups AS $group) {
            if (in_array($needle, $group)) {
                $groupPointer =& $group;
                break;
            }
        }

        if ($groupPointer) {
            $this->resolve($groupPointer);
        }
        else
            throw new \Exception("Selection group not found for '$needle'");
    }


    /**
     * @return bool true when the object has a corresponding entry in the database, false otherwise
     */
    abstract public function exists() : bool;
}
?>