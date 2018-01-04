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

use Database\Database;
use Database\Engine;
use Database\Type\Comparison;

use Stream\StreamInterface;

/**
 * An implementation of Stream using database tables. Uses Database wrapper to ensure compatibility.
 * Compared to other implementations, this one will be more finicky: table locking may become a concern, and create/deleting stream tables will have some overhead.
 */
class StreamDatabase implements StreamInterface {
    /**
     * @var Database
     */
    private $database;
    private $retries = 0;

    public function __construct(Database $database) {
        $this->database = $database;
    }

    /**
     * Ensures that the stream named $stream exists, and also performances maintence, deleting old streams, etc.
     *
     * @param $stream string StreamInterface to create.
     */
    private function createStreamIfNotExists($stream) {
        /* Create the Stream Table if it Doesn't Exist */
        @$this->database->createTable( $this->database->sqlPrefix . 'stream_' . $stream, '', Engine::memory, [
            'id' => [
                'type' => 'int',
                'maxlen' => 10,
                'autoincrement' => true,
            ],
            'chunk' => [
                'type' => 'int',
                'maxlen' => 3,
            ],
            'time' => [
                'type' => 'int',
                'default' => '__TIME__',
            ],
            'data' => [
                'type' => 'json',
                'maxlen' => 100,
            ],
            'eventName' => [
                'type' => 'string',
                'maxlen' => 20,
            ]
        ], [
            'id,chunk' => [
                'type' => \Database\Index\Type::primary,
                'storage' => \Database\Index\Storage::btree
            ],
            'time' => [
                'type' => \Database\Index\Type::index,
                'storage' => \Database\Index\Storage::btree
            ]
        ]);
    }


    public function subscribe($stream, $lastId, $callback) {
        while ($this->retries++ < \Fim\Config::$serverSentMaxRetries) {
            foreach ($this->subscribeOnce($stream, $lastId, false) AS $event) {
                if ($event['id'] > $lastId) $lastId = $event['id'];

                call_user_func($callback, $event);
            }

            usleep(\Fim\Config::$serverSentEventsWait * 1000000);
        }

        return [];
    }


    public function subscribeOnce($stream, $lastId, $createStream = true) {

        try {
            $output = $this->database->select([
                $this->database->sqlPrefix . 'stream_' . $stream => 'id, time, chunk, data, eventName'
            ], [
                'id'   => $this->database->int($lastId, Comparison::greaterThan),
                'time' => $this->database->now(-15, Comparison::greaterThan)
            ], [
                'id'    => 'ASC',
                'chunk' => 'ASC'
            ])->getAsArray('id', true);
        } catch (\Exception $ex) {

            $this->createStreamIfNotExists($stream);
            return $this->subscribeOnce($stream, $lastId);

        }

        $events = [];

        foreach ($output AS $id => $chunks) {
            $entry = $chunks[0];
            $entry['data'] = '';

            $this->database->startTransaction();
            foreach ($chunks AS $chunk) {
                $entry['data'] .= $chunk['data'];
            }

            $entry['data'] = json_decode($entry['data'], true);
            $events[] = $entry;
        }

        return $events;
    }


    public function publish($stream, $eventName, $data) {
        // Delete old messages (do so first, just in case we hit the maximum, this ensures that old messages will still be deleted first)
        try {
            $this->database->delete($this->database->sqlPrefix . 'stream_' . $stream, [
                'time' => $this->database->now(-15, 'lt')
            ]);
        } catch (\Exception $ex) {
            $this->createStreamIfNotExists($stream);
            return $this->publish($stream, $eventName, $data);
        }

        // Split up the data into chunks
        $chunks = str_split(json_encode($data), 100);

        // Insert all of the chunks (in a single transaction, ensuring they are all received together)
        $this->database->startTransaction();

        foreach ($chunks AS $i => $chunk) {
            $data = [
                'chunk'     => $i,
                'eventName' => $eventName,
                'data'      => $chunk,
            ];

            // Make sure all messages have the same ID
            if ($i > 0) {
                $data['id'] = $this->database->getLastInsertId();
            }

            $this->database->insert($this->database->sqlPrefix . 'stream_' . $stream, $data);
        }

        $this->database->endTransaction();


        /* Update the Streams Table to Keep This Stream Alive */
        $this->database->upsert($this->database->sqlPrefix . 'streams', [
            'streamName' => $stream,
        ], [
            'lastEvent' => $this->database->now()
        ]);

        /* Delete All Channels That Are More Than 5 Minutes Old */
        $condition = ['lastEvent' => $this->database->now(-60 * 5, Comparison::lessThan)];
        foreach ($this->database->select([$this->database->sqlPrefix . 'streams' => 'lastEvent, streamName'], $condition)->getColumnValues('streamName') AS $oldStream) {
            $this->database->deleteTable($this->database->sqlPrefix . 'stream_' . $oldStream);
            $this->database->delete($this->database->sqlPrefix . 'streams', ['streamName' => $oldStream]);
        }
    }


    public function unsubscribe($stream) {
        return;
    }


    public function getLastInsertId() {
        return $this->database->getLastInsertId();
    }
}
?>