<?php

namespace Fim;

use \fimRoom;
use \fimUser;

class DatabaseResult extends \Database\DatabaseResult {
    /**
     * @return fimRoom[]
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
     * @return fimRoom
     */
    function getAsRoom() : fimRoom {
        return RoomFactory::getFromData($this->getAsArray(false));
    }


    /**
     * @return fimUser[]
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
     * @return fimUser
     */
    function getAsUser() : fimUser {
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


    function getAsObjects($objectType) {
        $return = array();

        for ($i = 0; $i < $this->count; $i++) {
            $return[] = new $objectType($this);
        }

        return $return;
    }

    function getAsObject($objectType) {
        return new $objectType($this);
    }

}
?>