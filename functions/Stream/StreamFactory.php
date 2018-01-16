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
namespace Stream;

use Database\SQL\DatabaseSQL;

use Stream\Streams\StreamRedis;
use Stream\Streams\StreamPgSQL;
use Stream\Streams\StreamDatabase;
use Stream\Streams\StreamKafka;


/**
 * Exposes standard publish/subscribe model, automatically using whichever available driver is best.
 * This will use Redis first (if streamMethods['redis'] is set), PgSQL second (if streamMethods['pgsql'] is set or the primary database driver uses PgSQL), and a generic Database third (which will not be performant unless in-memory tables are supported).
 */
class StreamFactory {
    /**
     * @var StreamInterface The currently in-use Stream instance.
     */
    private static $instance;

    /**
     * @var StreamInterface A secondary DatabaseStream instance, used for the initial request in most implementors.
     */
    private static $databaseInstance;

    /**
     * @return StreamInterface A stream instance (one will be created if not yet available).
     */
    public static function getInstance(): StreamInterface {
        if (StreamFactory::$instance instanceof StreamInterface) {
            return StreamFactory::$instance;
        }
        else {
            global $dbConnect, $streamMethods;

            if (isset($streamMethods['kafka']['brokers']) && extension_loaded('rdkafka')) {
                return StreamFactory::$instance = new StreamKafka($streamMethods['kafka']);
            }

            elseif (isset($streamMethods['redis']['host']) && extension_loaded('redis')) {
                return StreamFactory::$instance = new StreamRedis($streamMethods['redis']);
            }

            elseif ($dbConnect['core']['driver'] === 'pgsql' || $streamMethods['pgsql']['host']) {
                // Reuse existing PgSQL instance if available
                if ($dbConnect['core']['driver'] === 'pgsql') {
                    global $database;
                }

                // Otherwise, create new PgSQL instance.
                else {
                    $database = new DatabaseSQL();
                    if (!$database->connect(
                        $streamMethods['pgsql']['host'],
                        $streamMethods['pgsql']['port'],
                        $streamMethods['pgsql']['username'],
                        $streamMethods['pgsql']['password'],
                        false,
                        'pgsql'
                    )) {
                        new \Fim\Error('pgsqlConnectionFailure', 'Could not connect to the PgSQL server for Streaming.');
                    }
                }

                return StreamFactory::$instance = new StreamPgSQL($database);
            }

            else {
                return self::getDatabaseInstance();
            }
        }
    }

    public static function getDatabaseInstance() : StreamDatabase {
        return StreamFactory::$databaseInstance = new StreamDatabase(\Fim\Database::instance());
    }

    public static function publish($stream, $eventName, $data) {
        return self::getInstance()->publish($stream, $eventName, $data);
    }

    public static function subscribe($stream, $lastId, $callback) {
        return self::getInstance()->subscribe($stream, $lastId, $callback);
    }
}