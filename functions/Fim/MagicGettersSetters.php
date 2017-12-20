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
    public function set($property, $value) {
        if (!property_exists($this, $property))
            throw new Exception('Invalid property to set in ' . get_called_class() . ': ' . $property);

        $setterName = $this->setterName($property);

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
    public function get($property) {
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


    public function getterName($property) {
        return 'get' . ucfirst($property);
    }

    public function hasGetter($property) {
        return method_exists($this, $this->getterName($property));
    }

    public function setterName($property) {
        return 'set' . ucfirst($property);
    }

    public function hasSetter($property) {
        return method_exists($this, $this->setterName($property));
    }

    public function propertyExists($property) {
        return property_exists($this, $property);
    }


}