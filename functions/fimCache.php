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

require('Cache/CacheFactory.php');
use Cache\CacheFactory;

/**
 * Class designed to store and recall cache variables.
 * @TODO All database queries should be moved to fimDatabase.php, which should, if possible, do _all_ database logic (currently its split between APIs and other functions.)
 *
 * @internal The fim class currently stores all of its calls in memory in addition to whatever other methods are provided (one of the methods is to store in mmemory, and we will duplicate it in this situation; however, you shouldn't EVER use that method anyway). This is because each function should be able to return a single index from a cache object, and having to retrieve the cache each time we want to call a single index of an array is, well, stupid (previously, we just stored the entire array outside of the class; this way, we can at least convert things super easily if we want to change methodologies). That said, this may be a problem with scripts that run longer than the cache -- currently not a problem, but it might be if we try to support persistent scripts in the future.
 *
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
class fimCache extends CacheFactory {
    protected $slaveDatabase = false;



    /**
     * @param fimDatabase $database
     */
    function __construct($database) {
        if (!$database) {
            throw new Exception('No database provided');
        }

        $this->slaveDatabase = $database;
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


    public function loadFimConfig() {
        global $disableConfig;

        if (!$disableConfig) {
            if ($this->exists('fim_config')) {
                $configData = $this->get('fim_config');
            }
            else {
                $configData = $this->slaveDatabase->getConfigurations()->getAsArray(true);
                $this->set('fim_config', $configData);
            }

            foreach ($configData AS $configDatabaseRow) {
                switch ($configDatabaseRow['type']) {
                    case 'int':
                    case 'string':
                    case 'float':
                    case 'bool':
                        fimConfig::${$configDatabaseRow['directive']} = fim_cast($configDatabaseRow['type'], $configDatabaseRow['value']);
                        break;

                    case 'json':
                        fimConfig::${$configDatabaseRow['directive']} = (array) json_decode($configDatabaseRow['value']);
                        break;
                }
            }
        }
    }



    public function getActiveCensorWords($roomId) {
        if ($this->exists('fim_activeCensorWords_' . $roomId)) {
            $activeCensorWords = $this->get('fim_activeCensorWords_' . $roomId);
        }
        else {
            $activeCensorWords = $this->slaveDatabase->getCensorWordsActive($roomId)->getAsArray(true);

            $this->set('fim_activeCensorWords_' . $roomId, $activeCensorWords, fimConfig::$censorWordsCacheRefresh);
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
?>