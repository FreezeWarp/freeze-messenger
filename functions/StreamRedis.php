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

class StreamRedis implements Stream {
    private $retries = 0;

    public function __construct($servers) {
        $this->redis = new Redis();
        $this->redis->pconnect($servers['host'], $servers['port'], $servers['timeout'], $servers['persistentId']);
        if ($servers['password'])
            $this->redis->auth($servers['password']);
    }

    public function publish($stream, $eventName, $data) {
        $this->redis->publish($stream, json_encode([
            'eventName' => $eventName,
            'data' => $data
        ]));
    }

    public function subscribe($stream, $lastId) {
        global $config;

        $return = [];
        $this->redis->subscribe($stream, function($instance, $channel, $data) {
            global $return;

            $return = $data;
        });

        while (usleep($config['serverSentEventsWait'] * 1000000) || (count($return) == 0 && $this->retries++ < $config['serverSentMaxRetries']))
            ;

        return $return;
    }
}