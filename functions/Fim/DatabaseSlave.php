<?php
namespace Fim;

use \fimDatabase;

/**
 * A singleton instance of {@see fimDatabase} that can be used for time-insensitive database operations.
 *
 * @package Fim
 */
class DatabaseSlave {
    /**
     * @var fimDatabase The current instance of {@see fimDatabase} used by this singleton.
     */
    private static $instance;

    /**
     * Connect the {@see $instance} to a DBMS server.
     * @see fimDatabase::connect() For the paramaters of this method.
     */
    public static function connect($host, $port, $user, $password, $database, $driver, $tablePrefix = '') {
        return self::$instance = new fimDatabase($host, $port, $user, $password, $database, $driver, $tablePrefix);
    }

    /**
     * Replace the current {@see $instance} used by this class.
     */
    public static function setInstance(fimDatabase $instance) {
        return self::$instance = $instance;
    }

    /**
     * @return {@see $instance}
     */
    public static function instance() : fimDatabase {
        return self::$instance;
    }
}