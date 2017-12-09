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
 *
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
class fimCache extends CacheFactory {
    /**
     * Return data in $index if it exists, otherwise invoke $callbackToCreate to generate the data, and then store and return it.
     *
     * @param string $index The index to return.
     * @param callable $callbackToCreate A function that will return the data, if it is not available.
     *
     * @return mixed
     */
    private function getGeneric($index, $callbackToCreate) {
        if ($this->exists($index)) {
            return $this->get($index);
        }
        else {
            $data = $callbackToCreate();

            $this->set($index, $data);
            return $data;
        }
    }


    /**
     * Retrieve administer-set changes to \Fim\Config's configuration data, and use it to modify the defaults of \Fim\Config.
     */
    public function loadConfig() {
        global $disableConfig;

        if (!$disableConfig) {
            $configData = $this->getGeneric('fim_config', function() {
                return \Fim\DatabaseSlave::instance()->getConfigurations()->getAsArray(true);
            });

            foreach ($configData AS $configDatabaseRow) {
                if (($value = @unserialize($configDatabaseRow['value'])) !== false || $configDatabaseRow['value'] === serialize(false)) {
                    \Fim\Config::${$configDatabaseRow['directive']} = $value;
                }
            }
        }
    }


    /**
     * Retrieve the emoticons registered for the messenger.
     *
     * @return array, where entries are arrays with the 'emoticonText' and 'emoticonFile' indexes.
     */
    public function getEmoticons() {
        return $this->getGeneric('fim_emoticons', function() {
            return \Fim\DatabaseSlave::instance()->select(array(
                \Fim\DatabaseSlave::$sqlPrefix . "emoticons" => 'emoticonText, emoticonFile'
            ))->getAsArray(true);
        });
    }
}
?>