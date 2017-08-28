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

class fimMessage {
    public $room = false;
    public $id = false;

    public $user;
    public $text;
    public $deleted;
    public $time;
    public $flag;
    public $formatting;

    protected $messageData;

    private $generalCache;

    /**
     * @param $roomData mixed Should either be an array or an integer (other values will simply fail to populate the object's data). If an array, should correspond with a row obtained from the `rooms` database, if an integer should correspond with the room ID.
     */
    function __construct($messageData) {
        global $generalCache;
        $this->generalCache = $generalCache;

        if (is_array($messageData)) {
            $this->user = fimUserFactory::getFromData([
                'userName' => ($messageData['userName']),
                'userId' => (int) $messageData['userId'],
                'userGroupId' => (int) $messageData['userGroup'],
                'avatar' => ($messageData['avatar']),
                'socialGroupIds' => ($messageData['socialGroups']),
                'userNameFormat' => ($messageData['userNameFormat']),
            ]);
            $this->room = new fimRoom((int) $messageData['roomId']);
            $this->text = fim_decrypt($messageData['text'], $messageData['salt'], $messageData['iv']);
            $this->flag = $messageData['flag'];
            $this->time = $messageData['time'];
            $this->formatting = $messageData['messageFormatting'];
        }

        elseif ($messageData === false)
            $this->id = false;

        else
            throw new Exception('Invalid message data specified -- must either be an associative array corresponding to a table row. Passed: ' . print_r($messageData, true));

        $this->messageData = $messageData;
    }


    public function __get($property) {
        if (!property_exists($this, $property))
            throw new Exception("Invalid property accessed in fimMessage: $property");

        return $this->$property;
    }


    /**
     * Modify or create a room.
     *
     * @param $roomParameters - The room's data.
     * @param $dbNameMapping - Set this to true if $databaseFields is using column names (e.g. roomParentalAge) instead of class property names (e.g. parentalAge)
     *
     * @return bool|resource
     *//*
    public function setDatabase(array $roomParameters)
    {
        global $database;

        if ($this->isPrivateRoom())
            throw new Exception('Can\'t call fimRoom->setDatabase on private rooms.');

        fim_removeNullValues($roomParameters);
        $this->populateFromArray($roomParameters, true);

        if ($this->id) {
            $database->startTransaction();

            if ($existingRoomData = $database->getRooms([
                'roomIds' => [$this->id],
                'columns' => explode(', ', $database->roomHistoryColumns), // TODO: uh... shouldn't roomHistoryColumns be array?
            ])->getAsArray(false)) {
                $database->insert($database->sqlPrefix . "roomHistory", [
                    'roomId' => $this->id,
                    'roomName' => $existingRoomData['roomName'],
                    'roomTopic' => $existingRoomData['roomTopic'],
                    'options' => (int) $existingRoomData['options'],
                    'ownerId' => (int) $existingRoomData['ownerId'],
                    'defaultPermissions' => (int) $existingRoomData['defaultPermissions'],
                    'roomParentalFlags' => $existingRoomData['roomParentalFlags'],
                    'roomParentalAge' => (int) $existingRoomData['roomParentalAge'],
                ]);

                $return = $database->update($database->sqlPrefix . "rooms", $roomParameters, array(
                    'roomId' => $this->id,
                ));
            }

            $database->endTransaction();

            return $return;
        }

        else {
            $database->insert($database->sqlPrefix . "rooms", $roomParameters);
            return $this->id = $database->insertId;
        }
    }*/
}
?>