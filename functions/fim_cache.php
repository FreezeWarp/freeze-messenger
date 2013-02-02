<?php
/* FreezeMessenger Copyright © 2012 Joseph Todd Parsons

 * This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>. */


/* Note: "none" is used solely for speed testing. NEVER EVER EVER use it. (at least with FIM) */

/**
 * Class designed to store and recall cache variables.
 *
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
class fimCache extends generalCache {
  private $defaultConfigFile;
  protected $database = false;
  
  function __construct($method = '', $servers, $database) {
    parent::__construct($method, $servers);
    
    $this->defaultConfigFile = dirname(__FILE__) . '/defaultConfig.php';
    
    if (!$database) {
      throw new Exception('No database provided');
    }
    else {
      $this->database = $database;
    }
  }
  
  /**
   * Retrieve and store configuration data into cache.
   *
   * @global bool disableConfig - If true, __only__ default config will be used, and the database will not be queried. This I picked up from vBulletin, and it makes it possible to disable the database-stored configuration if something goes horribly wrong.
   *
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  function storeConfig() {
    global $disableConfig;

    if (!($config = $this->get('fim_config')) || $disableConfig) {
      require($defaultConfigFile);

      if (!$disableConfig) {
        $config2 = $slaveDatabase->select(
          array(
            "{$sqlPrefix}configuration" => 'directive, value, type',
          )
        );
        $config2 = $config2->getAsArray(true);

        if (is_array($config2)) {
          if (count($config2) > 0) {
            foreach ($config2 AS $config3) {
              switch ($config3['type']) {
                case 'int':    $config[$config3['directive']] = (int) $config3['value']; break;
                case 'string': $config[$config3['directive']] = (string) $config3['value']; break;
                case 'array':  $config[$config3['directive']] = (array) fim_explodeEscaped(',', $config3['value']); break;
                case 'bool':
                if (in_array($config3['value'],array('true','1',true,1),true)) $config[$config3['directive']] = true; // We include the non-string counterparts here on the off-chance the database driver supports returning non-strings. The third parameter in the in_array makes it a strict comparison.
                else $config[$config3['directive']] = false;
                break;
                case 'float':  $config[$config3['directive']] = (float) $config3['value']; break;
              }
            }

            unset($config3);
          }
        }

        unset($config2);
      }

      foreach ($defaultConfig AS $key => $value) {
        if (!isset($config[$key])) $config[$key] = $value;
      }


      $this->set('fim_config', $config, $config['configCacheRefresh']);
    }
  }
}

?>