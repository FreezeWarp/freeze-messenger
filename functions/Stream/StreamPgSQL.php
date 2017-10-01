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
 * Implements Publisher/Subscriber model using Postgres' NOTIFY/LISTEN queries.
 */
class StreamPgSQL implements Stream {
    /**
     * @var DatabaseSQL
     */
    private $database;

    /**
     * @var int Number of listen queries that have so far been executed for this instance.
     */
    private $retries = 0;

    public function __construct(DatabaseSQL $database) {
        $this->database = $database;
    }

    public function publish($stream, $eventName, $data) {
        $json = json_encode([
           'eventName' => $eventName,
           'data' => $data
        ]);

        $this->database->rawQuery('NOTIFY ' . $this->database->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $stream) . ', ' . $this->database->formatValue(DatabaseTypeType::string, $json));
    }

    public function subscribe($stream, $lastId, $callback) {
        global $config;

        $databaseStream = StreamFactory::getDatabaseInstance();

        // Perform listen right away
        $this->database->rawQuery('LISTEN ' . $this->database->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $stream));

        // Now query database for recent missed events
        foreach ($databaseStream->subscribeOnce($stream, $lastId) AS $result) {
            call_user_func($callback, $result);
        }

        // Now get the listen results as they come in
        while ($this->retries++ < fimConfig::$serverSentMaxRetries) {
            $message = pg_get_notify($this->database->sqlInterface->connection, PGSQL_ASSOC);

            if ($message) {
                $event = json_decode($message['payload'], true);

                call_user_func($callback, [
                    'id' => time(),
                    'eventName' => $event['eventName'],
                    'data' => $event['data'],
                ]);
            }

            usleep(fimConfig::$serverSentEventsWait * 1000000);
        }

        return [];
    }

    public function unsubscribe($stream) {
        $this->database->rawQuery('UNLISTEN ' . $this->database->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $stream));
    }
}