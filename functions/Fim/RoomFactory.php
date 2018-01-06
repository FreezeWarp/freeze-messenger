<?php

namespace Fim;

use Fim\Room;
use \Exception;

class RoomFactory {
    /**
     * @var Room[]
     */
    static $instances = [];

    public static function getFromId($roomId) {
        if (isset(RoomFactory::$instances[$roomId]))
            return RoomFactory::$instances[$roomId];

        elseif (\Fim\Cache::exists('fim_fimRoom_' . $roomId)
            && ($room = \Fim\Cache::get('fim_fimRoom_' . $roomId)) != false)
            return RoomFactory::$instances[$roomId] = $room;

        else
            return RoomFactory::$instances[$roomId] = new Room($roomId);
    }

    public static function getFromData(array $roomData) : Room {
        if (!isset($roomData['id']))
            throw new Exception('Roomdata must contain id');

        elseif (isset(RoomFactory::$instances[$roomData['id']])) {
            RoomFactory::$instances[$roomData['id']]->populateFromArray($roomData);
            return RoomFactory::$instances[$roomData['id']];
        }

        elseif (\Fim\Cache::exists('fim_fimRoom_' . $roomData['id'])
            && ($room = \Fim\Cache::get('fim_fimRoom_' . $roomData['id'])) != false) {
            $room->populateFromArray($roomData);
            return RoomFactory::$instances[$roomData['id']] = $room;
        }

        else {
            return RoomFactory::$instances[$roomData['id']] = new Room($roomData);
        }
    }

    public static function cacheInstances() {
        // todo: docache

        foreach (RoomFactory::$instances AS $id => $instance) {
            if (!\Fim\Cache::exists('fim_fimRoom_' . $id) || $instance->doCache) {
                $instance->resolveAll();
                $instance->getCensorWords();
                $instance->getWatchedByUsers();

                if ($instance->doCache)
                    \Fim\Cache::set('fim_fimRoom_' . $id, $instance, \Fim\Config::$cacheDynamicObjectsTimeout);
                else
                    \Fim\Cache::add('fim_fimRoom_' . $id, $instance, \Fim\Config::$cacheDynamicObjectsTimeout);

                $instance->doCache = false;
            }
        }
    }
}
?>