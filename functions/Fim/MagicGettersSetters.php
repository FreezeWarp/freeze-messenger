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
    protected function set($property, $value) {
        if (!property_exists($this, $property))
            throw new Exception('Invalid property to set in ' . get_called_class() . ': ' . $property);

        $setterName = 'set' . ucfirst($property);

        if (method_exists($this, $setterName))
            $this->$setterName($value);
        else
            $this->{$property} = $value;
    }


    /**
     * Invokes getters, or gets by property.
     *
     * @param $property string The property to get.
     *
     * @throws Exception If a property doesn't exist.
     */
    protected function get($property) {
        $getterName = 'get' . ucfirst($property);

        if (!$property)
            throw new Exception('No property specified.');
        if (method_exists($this, $getterName))
            return $this->$getterName();
        elseif (!property_exists($this, $property))
            throw new Exception('Invalid property to get in ' . get_called_class() . ': ' . $property);
        else
            return $this->{$property};
    }


}