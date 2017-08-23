<?php
class fimConfigFactory {
    public static function init(fimDatabase $database) {
        if (apc_exists('fim_cache')) {
            return apc_fetch('fim_cache');
        }
        else {
            global $disableConfig;
            require_once('fimConfig.php');
            $config = new fimConfig();

            if (!$disableConfig) {
                foreach ($database->getConfigurations()->getAsArray(true) AS $configDatabaseRow) {
                    switch ($configDatabaseRow['type']) {
                    case 'int':
                        $config->{$configDatabaseRow['directive']} = (int)$configDatabaseRow['value'];
                    break;

                    case 'string':
                        $config->{$configDatabaseRow['directive']} = (string)$configDatabaseRow['value'];
                    break;

                    case 'array':
                    case 'associative':
                        $config->{$configDatabaseRow['directive']} = (array)json_decode($configDatabaseRow['value']);
                    break;

                    case 'bool':
                        if (in_array($configDatabaseRow['value'], ['true', '1', true, 1], true)) $config->{$configDatabaseRow['directive']} = true; // We include the non-string counterparts here on the off-chance the database driver supports returning non-strings. The third parameter in the in_array makes it a strict comparison.
                        else $config->{$configDatabaseRow['directive']} = false;
                    break;

                    case 'float':
                        $config->{$configDatabaseRow['directive']} = (float)$configDatabaseRow['value'];
                    break;
                    }
                }
            }
        }

        return $config;
    }
}
?>