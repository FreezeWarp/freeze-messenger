<?php

namespace Database;

class DatabaseIndexType {
    const __default = self::index;

    const index = 'index';
    const primary = 'primary';
    const unique = 'unique';
}