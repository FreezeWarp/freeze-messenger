<?php
require('Stream.php');

class StreamFactory {
    private static $instance;

    public static function getInstance(): Stream {
        if (StreamFactory::$instance instanceof Stream) {
            return StreamFactory::$instance;
        }
        else {
            switch ($config['streamMethod'] ?? false) {
                default:
                    require('DatabaseStream.php');
                    global $database;
                    return StreamFactory::$instance = new DatabaseStream($database);
                    break;
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