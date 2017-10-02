<?php

use Database\DatabaseResult;

class fimDatabaseResult extends DatabaseResult {
    /**
     * @return fimRoom[]
     *
     * @internal This function may use too much memory. I'm not... exactly sure how to fix this.
     */
    function getAsRooms() : array {
        $rooms = $this->getAsArray('id');
        $return = array();

        foreach ($rooms AS $room) {
            $return[] = new fimRoom($room);
        }

        return $return;
    }

    /**
     * @return fimRoom
     */
    function getAsRoom() : fimRoom {
        return new fimRoom($this->getAsArray(false));
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
            $return[] = fimUserFactory::getFromData($user);
        }

        return $return;
    }

    /**
     * @return fimUser
     */
    function getAsUser() : fimUser {
        return fimUserFactory::getFromData($this->getAsArray(false));
    }


    /**
     * @return fimGroup[]
     */
    function getAsGroups() : array {
        $groups = $this->getAsArray('groupId');
        $return = array();

        foreach ($groups AS $group) {
            $return[] = fimGroupFactory::getFromData($group);
        }

        return $return;
    }

    /**
     * @return fimGroup
     */
    function getAsGroup() : fimGroup {
        return fimGroupFactory::getFromData($this->getAsArray(false));
    }


    /**
     * @return fimMessage[]
     */
    function getAsMessages() : array {
        $return = array();

        for ($i = 0; $i < $this->count; $i++) {
            $message = new fimMessage($this);
            $return[] = $message;
        }

        return $return;
    }

    /**
     * @return fimMessage
     */
    function getAsMessage() : fimMessage {
        return new fimMessage($this);
    }

}
?>