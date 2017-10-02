<?php

namespace Database;

interface DatabaseResultInterface {
    /**
     * @return array The next row from the resultset, or false if no more results are available.
     */
    public function fetchAsArray();

    /**
     * @return int The number of rows in the resultset.
     */
    public function getCount();
}