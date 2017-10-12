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

class StreamRedis implements StreamInterface {
    /**
     * @var \Redis a Redis instance.
     */
    private $redis;

    public function __construct($server) {
        $this->redis = new \Redis();
        $this->redis->connect($server['host'], $server['port'], $server['timeout']);

        if ($server['password'])
            $this->redis->auth($server['password']);
    }

    public function publish($stream, $eventName, $data) {
        StreamFactory::getDatabaseInstance()->publish($stream, $eventName, $data);

        $this->redis->publish($stream, json_encode([
            'eventName' => $eventName,
            'data' => $data
        ]));
    }

    public function subscribe($stream, $lastId, $callback) {
        // The subscribe below will block, so we call this first. (Unfortunately, this does mean the    re is a small -- or possibly big -- window wherein messages may not be retrieved.)
        foreach (StreamFactory::getDatabaseInstance()->subscribeOnce($stream, $lastId) AS $result) {
            call_user_func($callback, $result);
        }

        // And now subscribe to the Redis socket.
        $this->redis->subscribe(["room_1"], function ($instance, $channel, $data) use ($callback) {
            $event = json_decode($data, true);

            call_user_func($callback, [
                'id' => time(),
                'eventName' => $event['eventName'],
                'data' => $event['data'],
            ]);
        });
    }

    public function __destruct() {
        $this->redis->close();
    }

    public function unsubscribe($stream) {
        $this->redis->close();
    }
}