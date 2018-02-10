<?php

namespace Fim;

use Fim\Room;
use Fim\User;

class DatabaseResult extends \Database\Result {
    /**
     * @return Room[]
     *
     * @internal This function may use too much memory. I'm not... exactly sure how to fix this.
     */
    function getAsRooms() : array {
        $rooms = $this->getAsArray('id');
        $return = array();

        foreach ($rooms AS $room) {
            $return[] = RoomFactory::getFromData($room);
        }

        return $return;
    }

    /**
     * @return Room
     */
    function getAsRoom() : Room {
        return RoomFactory::getFromData($this->getAsArray(false));
    }


    /**
     * @return User[]
     *
     * @internal This function may use too much memory. I'm not... exactly sure how to fix this.
     */
    function getAsUsers() : array {
        $users = $this->getAsArray('id');
        $return = array();

        foreach ($users AS $user) {
            $return[] = UserFactory::getFromData($user);
        }

        return $return;
    }

    /**
     * @return User
     */
    function getAsUser() : User {
        return UserFactory::getFromData($this->getAsArray(false));
    }


    /**
     * @return Message[]
     */
    function getAsMessages() : array {
        $return = array();

        for ($i = 0; $i < $this->count; $i++) {
            $message = new Message($this);
            $return[] = $message;
        }

        return $return;
    }

    /**
     * @return Message
     */
    function getAsMessage() : Message {
        return new Message($this);
    }

}
