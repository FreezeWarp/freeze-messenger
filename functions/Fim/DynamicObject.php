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
    public function set($property, $value) {
        parent::set($property, $value);

        if (!in_array($property, $this->resolved))
            $this->resolved[] = $property;
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