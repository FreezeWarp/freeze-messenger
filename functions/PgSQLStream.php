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

class PgSQLStream extends DatabaseSQL implements Stream {
    /**
     * @var DatabaseSQL
     */
    private $database;
    private $retries = 0;

    public function __construct(DatabaseSQL $database) {
        $this->database = $database;
    }

    public function publish($stream, $eventName, $data) {
        $json = json_encode([
           'eventName' => $eventName,
           'data' => $data
        ]);

        $this->rawQuery('NOTIFY ' . $this->database->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $stream) . ', ' . $this->formatValue(DatabaseTypeType::string, $json));
    }

    public function subscribe($stream, $lastId) {
        global $config;

        $this->rawQuery('LISTEN' . $this->database->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $stream));

        do {
            $message = pg_get_notify($this->database->connection, PGSQL_ASSOC);
        } while (usleep($config['serverSentEventsWait'] * 1000000) || (!$message && $this->retries++ < $config['serverSentMaxRetries']));

        return $message;
    }
}