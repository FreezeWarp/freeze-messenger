<?php
namespace Http;

class ApiOutputList {
    private $array;

    function __construct($array) {
        $this->array = $array;
    }

    function getArray() {
        return $this->array;
    }
}

