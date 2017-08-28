<?php
/**
 * Class fimRoomFactory
 */
class fimRoomFactory {
    public static function getFromId(int $roomId) {
        if (function_exists('apc_fetch') && apc_exists('fim_fimRoom_' . $roomId)) {
            return apc_fetch('fim_fimRoom_' . $roomId);
        }

        else {
            return new fimRoom($roomId);
        }
    }

    public static function getFromData(array $roomData) : fimRoom {
        if (!isset($roomData['roomId'])) {
            throw new Exception('Roomdata must contain roomId');
        }

        elseif (function_exists('apc_fetch') && apc_exists('fim_fimRoom_' . $roomData['roomId'])) {
            $room = apc_fetch('fim_fimRoom_' . $roomData['roomId']);
            $room->populateFromArray($roomData);
            return $room;
        }

        else {
            return new fimRoom($roomData);
        }
    }
}
?>