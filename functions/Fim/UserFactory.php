<?php

namespace Fim;

use \fimUser;
use \Exception;

class UserFactory {
    static $instances = [];

    public static function getFromId(int $userId) {
        if (isset(UserFactory::$instances[$userId]))
            return UserFactory::$instances[$userId];

        elseif (\Fim\Cache::exists('fim_fimUser_' . $userId)
            && ($user = \Fim\Cache::get('fim_fimUser_' . $userId)) != false)
            return UserFactory::$instances[$userId] = $user;

        else
            return UserFactory::$instances[$userId] = new fimUser($userId);
    }

    public static function getFromData(array $userData) : fimUser {
        if (!isset($userData['id']))
            throw new Exception('Userdata must contain id');

        elseif (isset(UserFactory::$instances[$userData['id']])) {
            UserFactory::$instances[$userData['id']]->populateFromArray($userData);
            return UserFactory::$instances[$userData['id']];
        }

        elseif (\Fim\Cache::exists('fim_fimUser_' . $userData['id'])
            && ($user = \Fim\Cache::get('fim_fimUser_' . $userData['id'])) != false) {
            return UserFactory::$instances[$userData['id']] = $user;
        }

        else {
            return UserFactory::$instances[$userData['id']] = new fimUser($userData);
        }
    }

    public static function cacheInstances() {
        foreach (UserFactory::$instances AS $id => $instance) {
            if (!\Fim\Cache::exists('fim_fimUser_' . $id)) {
                \Fim\Cache::add('fim_fimUser_' . $id, $instance, \Fim\Config::$cacheDynamicObjectsTimeout);
            }
            elseif ($instance->doCache) {
                $instance->resolveAll();
                $instance->doCache = false;

                \Fim\Cache::set('fim_fimUser_' . $id, $instance, \Fim\Config::$cacheDynamicObjectsTimeout);
            }
        }
    }
}
?>