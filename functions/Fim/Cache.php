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

namespace Fim;

/**
 * Class designed to store and recall cache variables.
 *
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
class Cache extends \Cache\CacheFactory {
    /** @var string The cache key used for emoticons. */
    const EMOTICON_KEY = 'fim_emoticons';

    /** @var string The cache key used for configuration data. */
    const CONFIG_KEY = 'fim_config';


    /**
     * Return data in $index if it exists, otherwise invoke $callbackToCreate to generate the data, and then store and return it.
     *
     * @param string $index The index to return.
     * @param callable $callbackToCreate A function that will return the data, if it is not available.
     *
     * @return mixed
     */
    private static function getGeneric($index, $callbackToCreate, $ttl = 31536000) {
        if (self::exists($index)) {
            return self::get($index);
        }
        else {
            $data = $callbackToCreate();

            self::set($index, $data, $ttl);
            return $data;
        }
    }


    /**
     * Retrieve administer-set changes to \Fim\Config's configuration data, and use it to modify the defaults of \Fim\Config.
     */
    public static function loadConfig() {
        global $disableConfig;

        if (!$disableConfig) {
            $configData = self::getGeneric(self::CONFIG_KEY, function() {
                return \Fim\DatabaseSlave::instance()->getConfigurations()->getAsArray(true);
            }, \Fim\Config::$configCacheTimeout);

            foreach ($configData AS $configDatabaseRow) {
                if (($value = @unserialize($configDatabaseRow['value'])) !== false || $configDatabaseRow['value'] === serialize(false)) {
                    \Fim\Config::${$configDatabaseRow['directive']} = $value;
                }
            }
        }
    }

    /**
     * Clear the cache entry for emoticons.
     *
     * @return True on success, false on failure.
     */
    public static function clearConfig() {
        return self::clear(self::CONFIG_KEY);
    }


    /**
     * Retrieve the emoticons registered for the messenger.
     *
     * @return array, where entries are arrays with the 'emoticonText' and 'emoticonFile' indexes.
     */
    public static function getEmoticons() {
        return self::getGeneric(self::EMOTICON_KEY, function() {
            return \Fim\DatabaseSlave::instance()->select(array(
                \Fim\DatabaseSlave::$sqlPrefix . "emoticons" => 'emoticonId, emoticonText, emoticonFile'
            ))->getAsArray('emoticonId');
        }, \Fim\Config::$emoticonCacheTimeout);
    }

    /**
     * Clear the cache entry for emoticons.
     *
     * @return True on success, false on failure.
     */
    public static function clearEmoticons() {
        return self::clear(self::EMOTICON_KEY);
    }
}
?>