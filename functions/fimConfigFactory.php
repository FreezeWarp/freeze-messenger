<?php

class fimConfigFactory
{

    public static function init(fimDatabase $database)
    {
        global $disableConfig, $generalCache;

        if (!$disableConfig) {
            if ($generalCache->exists('fim_config')) {
                $configData = $generalCache->get('fim_config');
            }
            else {
                $configData = $database->getConfigurations()->getAsArray(true);
                $generalCache->set('fim_config', $configData);
            }


            foreach ($database->getConfigurations()->getAsArray(true) AS $configDatabaseRow) {
                switch ($configDatabaseRow['type']) {
                    case 'int':
                    case 'string':
                    case 'float':
                    case 'bool':
                        fimConfig::${$configDatabaseRow['directive']} = fim_cast($configDatabaseRow['type'], $configDatabaseRow['value']);
                    break;

                    case 'array':
                    case 'associative':
                        fimConfig::${$configDatabaseRow['directive']} = (array) json_decode($configDatabaseRow['value']);
                        break;
                }
            }
        }
    }

}

?>