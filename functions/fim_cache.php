<?php
/* FreezeMessenger Copyright © 2017 Joseph Todd Parsons

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
 * @TODO All database queries should be moved to fimDatabase.php, which should, if possible, do _all_ database logic (currently its split between APIs and other functions.)
 *
 * @internal The fim class currently stores all of its calls in memory in addition to whatever other methods are provided (one of the methods is to store in mmemory, and we will duplicate it in this situation; however, you shouldn't EVER use that method anyway). This is because each function should be able to return a single index from a cache object, and having to retrieve the cache each time we want to call a single index of an array is, well, stupid (previously, we just stored the entire array outside of the class; this way, we can at least convert things super easily if we want to change methodologies). That said, this may be a problem with scripts that run longer than the cache -- currently not a problem, but it might be if we try to support persistent scripts in the future.
 *
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
class fimCache extends generalCache {
    private $defaultConfigFile;
    private $memory = array(); // Whenever the cache is retrieved, we store it in memory for the duration of the script's execution
    protected $database = false;
    protected $slaveDatabase = false;



    /** Sets the defaultConfigFile, and checks to ensure a database exists.
     *
     * @param string method - The database method, required by fimCache's construct.
     * @param mixed servers - The server information we are using, mainly for memcached. Obviously, if we aren't using memcached, there won't really be anything here.
     * @param object database - A fimDatabase object to use for queries.
     * @param object slaveDatabase - A fimData object to use for slave-enabled queries.
     */
    function __construct($servers, $method = '', $database, $slaveDatabase = false) {
        parent::__construct($method, $servers);



        if (!$database) {
            throw new Exception('No database provided');
        }
        else {
            $this->database = $database;
            $this->slaveDatabase = $slaveDatabase ? $slaveDatabase : $database;
        }
    }


    /**
     * If a cache value includes an index, we should use this to query it. At present, all this reduces is an isset() call, but in the future we could use it to retrieve values in other ways.
     * Fun fact: if we used eval() for our builder, we could totally save some cycles. Oh well.
     * Additionally, if an index is false, it will be understood as ending the sequence.
     *
     * @param array array - The cache array.
     * @param string index - The name of the array index.
     */
    protected function returnValue($array) {
        $arguments = func_get_args();

        for ($i = 1; $i <= count($arguments) - 1; $i++) {
            if (is_null($arguments[$i])) break;
            elseif ($arguments[$i] === false) throw new Exception('false is not a valid argument for returnValue.'); // Make sure we don't pass bad data accidentally.
            elseif (isset($array[$arguments[$i]])) $array = $array[$arguments[$i]];
            else return null;
        }

        return $array;
    }



    public function getActiveCensorWords($roomId) {
        global $config;

        if ($this->exists('fim_activeCensorWords_' . $roomId)) {
            $activeCensorWords = $this->get('fim_activeCensorWords' . $roomId);
        }
        else {
            $activeCensorWords = $this->slaveDatabase->getCensorWordsActive($roomId)->getAsArray(true);

            $this->set('fim_activeCensorWords_' . $roomId, $activeCensorWords, $config['censorWordsCacheRefresh']);
        }

        return $activeCensorWords;
    }


    /**
     *
     * @param $text - The text to censor
     * @param null $roomId - The roomID whose rules should be applied. If not specified, the global rules (for, e.g., usernames) will be used
     * @param bool $dontAsk - If true, we won't stop for words that merely trigger confirms
     * @param $matches - This array will fill with all matched words.
     *
     * @return Returns text with substitutions made.
     */
    public function censorScan($text, $roomId = null, $dontAsk = false, &$matches) {
        foreach ($this->getActiveCensorWords($roomId) AS $word) {
            if ($dontAsk && $word['severity'] === 'confirm') continue;

            if (stripos($text, $word['word']) !== FALSE) {
                switch ($word['severity']) {
                    // Automatically replaces text
                    case 'replace':
                        $text = str_ireplace($word['word'], $word['param'], $text);
                        break;

                    // Passes the word to $matches, to advise the user to be careful
                    case 'warn':
                        $matches[$word['word']] = $word['param'];
                        break;

                    // Blocks the word, throwing an exception
                    case 'block':
                        new fimError('blockCensor', "The message can not be sent: '{$word['word']}' is not allowed.");
                        break;

                    // Blocks the word, throwing an exception, but can be overwridden with $dontAsk
                    case 'confirm':
                        new fimError('confirmCensor', "The message must be resent because a word may not be allowed: {$word['word']} is discouraged: {$word['param']}.");
                        break;
                }
            }
        }

        return $text;
    }
}


/**
 * The fimConfig class is used to reference all configuration variables. It is not currently optimised, but we may eventually cache config variables seperately, lowering the memory footprint.
 */
class fimConfig extends fimCache implements ArrayAccess {
    private $container = [];
    private $defaultConfigFile;

    public function __construct($servers, $method, $database, $slaveDatabase) {
        parent::__construct($servers, $method, $database, $slaveDatabase);

        $this->defaultConfigFile = dirname(dirname(__FILE__)) . '/defaultConfig.php';
        $this->container = $this->getConfig();
    }

    /**
     * Retrieve and store configuration data into cache.
     * The database is stored as $config[index].
     *
     * @param mixed index -- If false, all configuration information will be returned. Otherwise, will return the value of the index specified.
     *
     * @global bool disableConfig - If true, only the default cache will be used (note that the configuration will not be cahced so long as disableConfig is in effect -- it will need to be disabled ASAP). This I picked up from vBulletin, and it makes it possible to disable the database-stored configuration if something goes horribly wrong.
     *
     * @return mixed -- The config array if no index is specified, otherwise the formatted config value corresponding to the index (this may be an array, but due to the nature of $config, we will not support going two levels in).
     *
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function getConfig($index = null) {
        global $disableConfig;

        if ($this->exists('fim_config') && !$disableConfig) {
            $this->container = $this->get('fim_config');
        }

        else {
            $defaultConfig = array();
            require_once($this->defaultConfigFile); // Not exactly best practice, but the best option for reducing resources. (The alternative is to parse it with JSON, but really, why?). This should provide $defaultConfig.

            $config = array();

            if (!$disableConfig) {
                $configDatabase = $this->slaveDatabase->getConfigurations()->getAsArray(true);

                if (is_array($configDatabase) && count($configDatabase) > 0) {
                    foreach ($configDatabase AS $configDatabaseRow) {
                        switch ($configDatabaseRow['type']) {
                        case 'int':    $config[$configDatabaseRow['directive']] = (int) $configDatabaseRow['value']; break;
                        case 'string': $config[$configDatabaseRow['directive']] = (string) $configDatabaseRow['value']; break;
                        case 'array':
                        case 'associative': $config[$configDatabaseRow['directive']] = (array) json_decode($configDatabaseRow['value']); break;
                        case 'bool':
                            if (in_array($configDatabaseRow['value'], array('true', '1', true, 1), true)) $config[$configDatabaseRow['directive']] = true; // We include the non-string counterparts here on the off-chance the database driver supports returning non-strings. The third parameter in the in_array makes it a strict comparison.
                            else $config[$configDatabaseRow['directive']] = false;
                        break;
                        case 'float':  $config[$configDatabaseRow['directive']] = (float) $configDatabaseRow['value']; break;
                        }
                    }
                }
            }

            foreach ($defaultConfig AS $key => $value) {
                if (!isset($config[$key])) $config[$key] = $value;
            }


            $this->set('fim_config', $config, $config['configCacheRefresh']);
        }

        // sure, we'd get this same error if notices were on, but I'm an idiot who has too many notices to be able to do that.
        if ($index && !isset($this->container[$index])) {
            throw new Exception('Invalid config entry requested: ' . $index);
        }

        return $this->container[$index];
    }




    public function offsetSet($offset, $value) {
        throw new Exception('Configuration directives may not be set.');
    }

    public function offsetExists($offset) {
        return isset($this->container[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->container[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->container[$offset]) ? $this->container[$offset] : $this->getConfig($offset);
    }
}

?>