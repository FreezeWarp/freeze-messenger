<?php

namespace Fim;

use \fimRoom;
use \Exception;

class RoomFactory {
    static $instances = [];

    public static function getFromId($roomId) {
        global $generalCache;

        if (isset(RoomFactory::$instances[$roomId]))
            return RoomFactory::$instances[$roomId];

        elseif ($generalCache->exists('fim_fimRoom_' . $roomId)
            && ($room = $generalCache->get('fim_fimRoom_' . $roomId)) != false)
            return RoomFactory::$instances[$roomId] = $room;

        else
            return RoomFactory::$instances[$roomId] = new fimRoom($roomId);
    }

    public static function getFromData(array $roomData) : fimRoom {
        global $generalCache;

        if (!isset($roomData['id']))
            throw new Exception('Roomdata must contain id');

        elseif (isset(RoomFactory::$instances[$roomData['id']])) {
            RoomFactory::$instances[$roomData['id']]->populateFromArray($roomData);
            return RoomFactory::$instances[$roomData['id']];
        }

        elseif ($generalCache->exists('fim_fimRoom_' . $roomData['id'])
            && ($room = $generalCache->get('fim_fimRoom_' . $roomData['id'])) != false) {
            $room->populateFromArray($roomData);
            return RoomFactory::$instances[$roomData['id']] = $room;
        }

        else {
            return RoomFactory::$instances[$roomData['id']] = new fimRoom($roomData);
        }
    }

    public static function cacheInstances() {
        global $generalCache;

        // todo: docache

        foreach (RoomFactory::$instances AS $id => $instance) {
            if (!$generalCache->exists('fim_fimRoom_' . $id)) {
                $instance->resolveAll();
                $generalCache->add('fim_fimRoom_' . $id, $instance, \Fim\Config::$cacheDynamicObjectsTimeout);
            }
        }
    }
}
?>