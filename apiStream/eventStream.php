<?php
/* FreezeMessenger Copyright © 2014 Joseph Todd Parsons

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

if (defined('FIM_EVENTSOURCE')) {
  /* Get Events */
  if ($config['enableEvents']) {
    $events = $database->getEvents(array(
      'userIds' => array($user['userId']),
      'roomIds' => array($request['roomId']),
      'lastEvent' => $request['lastEvent']
    ))->getAsArray('eventId');

    if (count($events) > 0) {
      foreach ($events AS $eventId => $event) {
        if ($eventId > $request['lastEvent']) $request['lastEvent'] = $eventId;

        echo "id: " . (int) $message['messageId'] . "\n";
        echo "event: " . $event['eventName'] . "\n";
        echo "data: " . json_encode($event) . "\n\n";

        fim_flush();
        $outputStarted = true;
      }

      fim_flush(); // Force the server to flush.
    }

    unset($events); // Free memory.
  }
}

?>