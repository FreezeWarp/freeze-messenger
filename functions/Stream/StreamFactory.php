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

require(__DIR__ . '/Stream.php');
require(__DIR__ . '/StreamDatabase.php');

/**
 * Exposes standard publish/subscribe model, automatically using whichever available driver is best.
 * This will use Redis first (if cacheConnectMethods['redis'] is set), PgSQL second (if cacheConnectMethods['pgsql'] is set or the primary database driver uses PgSQL), and a generic Database third (which will not be performant unless in-memory tables are supported).
 */
class StreamFactory {
    /**
     * @var Stream The currently in-use Stream instance.
     */
    private static $instance;
    /**
     * @var Stream A secondary DatabaseStream instance, used for the initial request in most implementors.
     */
    private static $databaseInstance;

    /**
     * @return Stream A stream instance (one will be created if not yet available).
     */
    public static function getInstance(): Stream {
        if (StreamFactory::$instance instanceof Stream) {
            return StreamFactory::$instance;
        }
        else {
            global $dbConnect, $cacheConnectMethods;

            if (isset($cacheConnectMethods['redis']['host']) && extension_loaded('redis')) {
                require(__DIR__ . '/StreamRedis.php');
                StreamFactory::$instance = new StreamRedis($cacheConnectMethods['redis']);
            }

            elseif ($dbConnect['core']['driver'] === 'pgsql' || $cacheConnectMethods['pgsql']['host']) {
                require(__DIR__ . '/StreamPgSQL.php');

                // Reuse existing PgSQL instance if available
                if ($dbConnect['core']['driver'] === 'pgsql') {
                    global $database;
                }

                // Otherwise, create new PgSQL instance.
                else {
                    $database = new DatabaseSQL();
                    if (!$database->connect(
                        $cacheConnectMethods['pgsql']['host'],
                        $cacheConnectMethods['pgsql']['port'],
                        $cacheConnectMethods['pgsql']['username'],
                        $cacheConnectMethods['pgsql']['password'],
                        false,
                        'pgsql'
                    )) {
                        new fimError('pgsqlConnectionFailure', 'Could not connect to the PgSQL server for Streaming.');
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
        global $database;
        return StreamFactory::$databaseInstance = new StreamDatabase($database);
    }

    public static function publish($stream, $eventName, $data) {
        return self::getInstance()->publish($stream, $eventName, $data);
    }

    public static function subscribe($stream, $lastId) {
        return self::getInstance()->subscribe($stream, $lastId);
    }
}