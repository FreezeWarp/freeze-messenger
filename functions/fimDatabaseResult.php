<?php
class fimDatabaseResult extends databaseResult {
    /**
     * @return fimRoom[]
     *
     * @internal This function may use too much memory. I'm not... exactly sure how to fix this.
     */
    function getAsRooms() : array {
        $rooms = $this->getAsArray('roomId');
        $return = array();

        foreach ($rooms AS $roomId => $room) {
            $return[$roomId] = new fimRoom($room);
        }

        return $return;
    }


    /**
     * @return fimUser[]
     *
     * @internal This function may use too much memory. I'm not... exactly sure how to fix this.
     */
    function getAsUsers() : array {
        $users = $this->getAsArray('userId');
        $return = array();

        foreach ($users AS $userId => $user) {
            $return[$userId] = fimUserFactory::getFromData($user);
        }

        return $return;
    }


    /**
     * @return fimGroup[]
     */
    function getAsGroups() : array {
        $groups = $this->getAsArray('groupId');
        $return = array();

        foreach ($groups AS $groupId => $group) {
            $return[$groupId] = fimGroupFactory::getFromData($group);
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
     * @return fimUser
     */
    function getAsUser() : fimUser {
        return fimUserFactory::getFromData($this->getAsArray(false));
    }


    /**
     * @return fimGroup
     */
    function getAsGroup() : fimGroup {
        return fimGroupFactory::getFromData($this->getAsArray(false));
    }

}
?>