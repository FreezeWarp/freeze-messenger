<?php
class fimDatabaseAndConfigFactory {
    public static function init($host, $port, $userName, $password, $databaseName, $driver, $prefix = '') {
        $database = new fimDatabase($host, $port, $userName, $password, $databaseName, $driver, $prefix);
        $config = fimConfigFactory::init($database);
        $database->registerConfig($config);

        return [$database, $config];
    }
}
?>