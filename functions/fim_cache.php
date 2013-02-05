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


/**
 * Class designed to store and recall cache variables.
 *
 * @internal The fim class currently stores all of its calls in memory in addition to whatever other methods are provided (one of the methods is to store in mmemory, and we will duplicate it in this situation; however, you shouldn't EVER use that method anyway). This is because each function should be able to return a single index from a cache object, and having to retrieve the cache each time we want to call a single index of an array is, well, stupid (previously, we just stored the entire array outside of the class; this way, we can at least convert things super easily if we want to change methodologies). That said, this may be a problem with scripts that run longer than the cache -- currently not a problem, but it might be if we try to support persistent scripts in the future.
 *
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
class fimCache extends generalCache {
  private $defaultConfigFile;
  private $memory = array(); // Whenever the cache is retrieved, we store it in memory for the duration of the script's execution
  protected $database = false;

  function __construct($method = '', $servers, $database) {
    parent::__construct($method, $servers);
    
    $this->defaultConfigFile = dirname(dirname(__FILE__)) . '/defaultConfig.php';
    
    if (!$database) {
      throw new Exception('No database provided');
    }
    else {
      $this->database = $database;
    }
  }
  
  private function returnValue($array, $index) {
    if ($index === false) return $array;
    else return (isset($array[$index]) ? $array[$index] : null);
  }
  
  /**
   * Stores an object both using the specified driver and in memory.
   *
   * @param string index - The name the data should be stored under.
   * @param mixed data - The data to cache.
   * @param mixed refresh - The period after which the cache will no longer be valid.
   */
  private function storeMemory($index, $data, $refresh) {
    $this->set('fim_config', $data, $config['configCacheRefresh']);
    
    $this->memory[$index] = $data;
  }
  
  private function issetMemory($index) {
    if (isset($this->memory[$index])) return true;
    else return false;
  }
  
  private function getMemory($index) {
    return $this->memory[$index];
  }
  
  /**
   * Retrieve and store configuration data into cache.
   *
   * @param mixed index -- If false, all configuration information will be returned. Otherwise, will return the value of the index specified.
   *
   * @global bool disableConfig - If true, only the default cache will be used (note that the configuration will not be cahced so long as disableConfig is in effect -- it will need to be disabled ASAP). This I picked up from vBulletin, and it makes it possible to disable the database-stored configuration if something goes horribly wrong.
   *
   * @return
   *
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  public function getConfig($index = false) {
    global $disableConfig, $slaveDatabase;

    if ($this->issetMemory('fim_config')) {
      $hooks = $this->getMemory('fim_config');
    }
    elseif ($this->exists('fim_config') && !$disableConfig) {
      $config = $this->get('fim_config');
    }
    else {
      require_once($this->defaultConfigFile); // Not exactly best practice, but the best option for reducing resources. (The alternative is to parse it with JSON, but really, why?)
      $config = array();

      if (!$disableConfig) {
        $configDatabase = $slaveDatabase->select(
          array(
            "{$sqlPrefix}configuration" => 'directive, value, type',
          )
        );
        $configDatabase = $configDatabase->getAsArray(true);

        if (is_array($configDatabase) && count($configDatabase) > 0) {
          foreach ($configDatabase AS $configDatabaseRow) {
            switch ($configDatabaseRow['type']) {
              case 'int':    $config[$configDatabaseRow['directive']] = (int) $configDatabaseRow['value']; break;
              case 'string': $config[$configDatabaseRow['directive']] = (string) $configDatabaseRow['value']; break;
              case 'array':  $config[$configDatabaseRow['directive']] = (array) fim_explodeEscaped(',', $configDatabaseRow['value']); break;
              case 'bool':
              if (in_array($configDatabaseRow['value'], array('true', '1', true, 1), true)) $config[$configDatabaseRow['directive']] = true; // We include the non-string counterparts here on the off-chance the database driver supports returning non-strings. The third parameter in the in_array makes it a strict comparison.
              else $config[$configDatabaseRow['directive']] = false;
              break;
              case 'float':  $config[$configDatabaseRow['directive']] = (float) $configDatabaseRow['value']; break;
            }

            unset($configDatabaseRow);
          }
        }

        unset($configDatabase);
      }

      foreach ($defaultConfig AS $key => $value) {
        if (!isset($config[$key])) $config[$key] = $value;
      }


      $this->storeMemory('fim_config', $config, $config['configCacheRefresh']);
    }
    
    $this->returnValue($config, $index);
  }
  
  public function getHooks($index) {
    global $disableHooks, $slaveDatabase;
    
    if ($this->issetMemory('fim_hooks')) {
      $hooks = $this->getMemory('fim_hooks');
    }
    elseif ($this->exists('fim_hooks')) {
      $hooks = $this->get('fim_hooks');
    }
    else {
      $hooks = array();

      if ($disableHooks !== true) {
        $hooksDatabase = $slaveDatabase->select(
          array(
            "{$sqlPrefix}hooks" => 'hookId, hookName, code',
          )
        );
        $hooksDatabase = $hooksDatabase->getAsArray('hookId');

        if (is_array($hooksDatabase) && count($hooksDatabase) > 0) {
          foreach ($hooksDatabase AS $hook) $hooks[$hook['hookName']] = $hook['code'];
        }

        $this->storeMemory('fim_hooks', $hooks, $config['hooksCacheRefresh']);
      }
    }
    
    if ($index === false) return $hooks;
    else return (isset($hooks[$index]) ? $hooks[$index] : null);
  }
}

?>