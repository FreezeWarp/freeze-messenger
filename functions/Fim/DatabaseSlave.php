<?php
namespace Fim;

/**
 * A singleton instance of {@see fimDatabase} that can be used for time-insensitive database operations.
 *
 * @package Fim
 */
class DatabaseSlave {
    /**
     * @var DatabaseInstance The current instance of {@see fimDatabase} used by this singleton.
     */
    private static $instance;

    /**
     * Connect the {@see $instance} to a DBMS server.
     * @see DatabaseInstance::connect() For the paramaters of this method.
     */
    public static function connect($host, $port, $user, $password, $database, $driver, $tablePrefix = '') {
        return self::$instance = new DatabaseInstance($host, $port, $user, $password, $database, $driver, $tablePrefix);
    }

    /**
     * Replace the current {@see $instance} used by this class.
     */
    public static function setInstance(DatabaseInstance $instance) {
        return self::$instance = $instance;
    }

    /**
     * @return {@see $instance}
     */
    public static function instance() : DatabaseInstance {
        return self::$instance;
    }
}