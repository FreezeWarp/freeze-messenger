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
 * A basic interface that allows for clients to publish to a stream by name, and to listen for new events in the stream.
 */
interface Stream {
    /**
     * Add new data to a stream.
     *
     * @param $stream string The stream named.
     * @param $eventName string An event classification for the stream.
     * @param $data
     *
     * @return mixed
     */
    public function publish($stream, $eventName, $data);


    /**
     * Gets all events since lastId, and wait until at least one exists. This _may_ return empty if needed.
     *
     * @param $stream string The name of the stream.
     * @param $lastId int Only get new events since this event ID.
     *
     * @return array An array containing all results since lastId at time of execution (waiting until a result appears if needed). The array will be an array of arrays with the indexes 'id', 'eventName', and 'data', where 'data' contains the data sent via publish.
     */
    public function subscribe($stream, $lastId);
}
?>