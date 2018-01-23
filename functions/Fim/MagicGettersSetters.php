<?php

namespace Fim;

use \Exception;

class MagicGettersSetters {
    /**
     * Invokes setters, or sets by property.
     *
     * @param $property string The property to set.
     * @param $value mixed The value to set the property to.
     *
     * @throws Exception If a property doesn't exist.
     */
    public function set(string $property, $value) {
        $setterName = $this->setterName($property);

        if (!$property)
            throw new Exception('No property specified.');
        if (method_exists($this, $setterName))
            $this->$setterName($value);
        elseif ($this->propertyExists($property))
            $this->{$property} = $value;
        else
            throw new Exception('Invalid property to set in ' . get_called_class() . ': ' . $property);
    }


    /**
     * Invokes getters, or gets by property.
     *
     * @param $property string The property to get.
     *
     * @throws Exception If a property doesn't exist.
     */
    public function get(string $property) {
        $getterName = $this->getterName($property);

        if (!$property)
            throw new Exception('No property specified.');
        if (method_exists($this, $getterName))
            return $this->$getterName();
        elseif ($this->propertyExists($property))
            return $this->{$property};
        else
            throw new Exception('Invalid property to get in ' . get_called_class() . ': ' . $property);
    }


    public function getterName(string $property) {
        return 'get' . ucfirst($property);
    }

    public function hasGetter(string $property) {
        return method_exists($this, $this->getterName($property));
    }

    public function setterName(string $property) {
        return 'set' . ucfirst($property);
    }

    public function hasSetter(string $property) {
        return method_exists($this, $this->setterName($property));
    }

    public function propertyExists(string $property) {
        return property_exists($this, $property);
    }


}