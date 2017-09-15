<?php
require_once('Stream.php');

/**
 * An implementation of Stream using database tables. Uses Database wrapper to ensure compatibility.
 * Compared to other implementations, this one will be more finicky: table locking may become a concern, and create/deleting stream tables will have some overhead.
 */
class DatabaseStream implements Stream {
    private $database;
    private $retries = 0;

    public function __construct(Database $database) {
        $this->database = $database;
    }

    /**
     * Ensures that the stream named $stream exists, and also performances maintence, deleting old streams, etc.
     *
     * @param $stream Stream to create.
     */
    private function createStreamIfNotExists($stream) {
        /* Create the Stream Table if it Doesn't Exist */
        $this->database->createTable($this->database->sqlPrefix . 'stream_' . $stream, '', DatabaseEngine::memory, [
            'id' => [
                'type' => 'int',
                'maxlen' => 10,
                'autoincrement' => true,
            ],
            'chunk' => [
                'type' => 'int',
                'maxlen' => 3,
            ],
            'data' => [
                'type' => 'string', // TODO: json
                'maxlen' => 1000, // TODO: support chunking
            ],
            'eventName' => [
                'type' => 'string',
                'maxlen' => 10,
            ]
        ], [
            'id' => [
                'type' => DatabaseIndexType::primary
            ]
        ]);

        /* Update the Streams Table to Keep This Stream Alive */
        $this->database->upsert($this->database->sqlPrefix . 'streams', [
            'streamName' => $stream,
        ], [
            'lastEvent' => $this->database->now()
        ]);

        /* Delete All Channels That Are More Than 5 Minutes Old */
        foreach ($this->database->select([$this->database->sqlPrefix . 'streams' => 'lastEvent, streamName'], [
            'lastEvent' => $this->database->now(-60 * 5, DatabaseTypeComparison::lessThan)
        ])->getColumnValues('streamName') AS $oldStream) {
            $this->database->deleteTable($this->database->sqlPrefix . 'stream_' . $oldStream);
        }
    }

    public function subscribe($stream, $lastId) {
        global $config;

        $this->createStreamIfNotExists($stream);

        do {
            $output = $this->database->select([
                $this->database->sqlPrefix . 'stream_' . $stream => 'id, chunk, data, eventName'
            ], [
                'id' => $this->database->int($lastId, DatabaseTypeComparison::greaterThan)
            ], [
                'id' => 'ASC',
                'chunk' => 'ASC'
            ])->getAsArray(true);
        } while (usleep($config['serverSentEventsWait'] * 1000000) || (count($output) == 0 && $this->retries++ < $config['serverSentMaxRetries']));

        foreach ($output AS &$entry) {
            $entry['data'] = json_decode($entry['data']);
        }

        return $output;
    }


    public function publish($stream, $eventName, $data) {
        $this->createStreamIfNotExists($stream);

        $this->database->insert($this->database->sqlPrefix . 'stream_' . $stream, [
            'chunk' => 1,
            'eventName' => $eventName,
            'data' => json_encode($data),
        ]);
        $this->database->delete($this->database->sqlPrefix . 'stream_' . $stream, [
            'id' => $this->database->int($this->database->getLastInsertId() - 100, DatabaseTypeComparison::lessThan)
        ]);
    }
}
?>