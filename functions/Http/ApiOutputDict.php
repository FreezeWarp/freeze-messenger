<?php
namespace Http;

class ApiOutputDict {
    private $array;

    function __construct($array) {
        $this->array = $array;
    }

    function getArray() {
        return $this->array;
    }
}
