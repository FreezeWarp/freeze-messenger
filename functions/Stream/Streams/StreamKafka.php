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
namespace Stream\Streams;

use Stream\StreamInterface;
use Stream\StreamFactory;

class StreamKafka implements StreamInterface {
    /**
     * @var array An array with server configuration. ['broker']
     */
    private $server;

    /**
     * @var int Number of listen queries that have so far been executed for this instance.
     */
    private $retries = 0;

    public function __construct($server) {
        $this->server = $server;
    }

    public function publish($stream, $eventName, $data) {
        StreamFactory::getDatabaseInstance()->publish($stream, $eventName, $data);

        $kafka = new \RdKafka\Producer();
        $kafka->setLogLevel(LOG_DEBUG);
        $kafka->addBrokers($this->server['brokers']);

        $topic = $kafka->newTopic($stream);
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode([
            'id' => StreamFactory::getDatabaseInstance()->getLastInsertId(),
            'eventName' => $eventName,
            'data' => $data
        ]));
    }

    public function subscribe($stream, $lastId, $callback) {
        $kafka = new \RdKafka\Consumer();
        $kafka->setLogLevel(LOG_DEBUG);
        $kafka->addBrokers($this->server['brokers']);

        $topic = $kafka->newTopic($stream);
        $topic->consumeStart(0, RD_KAFKA_OFFSET_END);

        foreach (StreamFactory::getDatabaseInstance()->subscribeOnce($stream, $lastId) AS $result) {
            call_user_func($callback, $result);
        }

        // Now get the listen results as they come in
        while (true) {
            $message = $topic->consume(0, 1000);

            if ($message) {
                call_user_func($callback, json_decode($message->payload, true));
            }
        }
    }

    public function __destruct() {
    }

    public function unsubscribe($stream) {
    }
}