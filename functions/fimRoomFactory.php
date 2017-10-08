<?php
/**
 * Class fimRoomFactory
 */
class fimRoomFactory {
    static $instances = [];

    public static function getFromId(int $roomId) {
        global $generalCache;

        if (isset(fimRoomFactory::$instances[$roomId]))
            return fimRoomFactory::$instances[$roomId];

        elseif ($generalCache->exists('fim_fimRoom_' . $roomId)
            && ($room = $generalCache->get('fim_fimRoom_' . $roomId)) != false)
            return fimRoomFactory::$instances[$roomId] = $room;

        else
            return fimRoomFactory::$instances[$roomId] = new fimRoom($roomId);
    }

    public static function getFromData(array $roomData) : fimRoom {
        global $generalCache;

        if (!isset($roomData['id']))
            throw new Exception('Roomdata must contain id');

        elseif (isset(fimRoomFactory::$instances[$roomData['id']]))
            return fimRoomFactory::$instances[$roomData['id']];

        elseif ($generalCache->exists('fim_fimRoom_' . $roomData['id'])
            && ($room = $generalCache->get('fim_fimRoom_' . $roomData['id'])) != false) {
            $room->populateFromArray($roomData);
            return fimRoomFactory::$instances[$roomData['id']] = $room;
        }

        else {
            return fimRoomFactory::$instances[$roomData['id']] = new fimRoom($roomData);
        }
    }

    public static function cacheInstances() {
        global $generalCache;

        foreach (fimRoomFactory::$instances AS $id => $instance) {
            if (!$generalCache->exists('fim_fimRoom_' . $id)) {
                $instance->resolveAll();
                $generalCache->add('fim_fimRoom_' . $id, $instance, 5 * 60);
            }
        }
    }
}
?>