<?php
/**
 * Class fimRoomFactory
 */
class fimRoomFactory {
    public static function getFromId(int $roomId) {
        if (function_exists('apc_fetch') && apc_exists('fim_fimRoom_' . $roomId)) {
            return apc_fetch('fim_fimRoom_' . $roomId);
        }

        else if (function_exists('apcu_fetch') && apcu_exists('fim_fimRoom_' . $roomId)) {
            return apcu_fetch('fim_fimRoom_' . $roomId);
        }

        else {
            return new fimRoom($roomId);
        }
    }

    public static function getFromData(array $roomData) : fimRoom {
        if (!isset($roomData['id'])) {
            throw new Exception('Roomdata must contain id');
        }

        elseif (function_exists('apc_fetch') && apc_exists('fim_fimRoom_' . $roomData['id'])) {
            $room = apc_fetch('fim_fimRoom_' . $roomData['id']);
            $room->populateFromArray($roomData);
            return $room;
        }

        else {
            return new fimRoom($roomData);
        }
    }
}
?>