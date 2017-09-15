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

require('Stream.php');

class StreamFactory {
    private static $instance;

    public static function getInstance(): Stream {
        if (StreamFactory::$instance instanceof Stream) {
            return StreamFactory::$instance;
        }
        else {
            global $dbConnect, $cacheConnectMethods;

            // todo: dbConnect stream driver, reuse $database if possible

            if (isset($cacheConnectMethods['redis']['host']) && extension_loaded('redis')) {
                require('RedisStream.php');
                StreamFactory::$instance = new RedisStream($cacheConnectMethods['redis']);
            }
            else {
                switch ($dbConnect['core']['driver']) {
                    case 'pgsql':
                        require('PgSQLStream.php');
                        global $database;
                        StreamFactory::$instance = new PgSQLStream($database);
                    break;

                    default:
                        require('DatabaseStream.php');
                        global $database;
                        StreamFactory::$instance = new DatabaseStream($database);
                    break;
                }
            }
        }
    }

    public static function publish($stream, $eventName, $data) {
        return self::getInstance()->publish($stream, $eventName, $data);
    }

    public static function subscribe($stream, $lastId) {
        return self::getInstance()->subscribe($stream, $lastId);
    }
}