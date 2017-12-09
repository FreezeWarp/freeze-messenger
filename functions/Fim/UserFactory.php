<?php

namespace Fim;

use \fimUser;
use \Exception;

class UserFactory {
    static $instances = [];

    public static function getFromId(int $userId) {
        global $generalCache;

        if (isset(UserFactory::$instances[$userId]))
            return UserFactory::$instances[$userId];

        elseif ($generalCache->exists('fim_fimUser_' . $userId)
            && ($user = $generalCache->get('fim_fimUser_' . $userId)) != false)
            return UserFactory::$instances[$userId] = $user;

        else
            return UserFactory::$instances[$userId] = new fimUser($userId);
    }

    public static function getFromData(array $userData) : fimUser {
        global $generalCache;

        if (!isset($userData['id']))
            throw new Exception('Userdata must contain id');

        elseif (isset(UserFactory::$instances[$userData['id']])) {
            UserFactory::$instances[$userData['id']]->populateFromArray($userData);
            return UserFactory::$instances[$userData['id']];
        }

        elseif ($generalCache->exists('fim_fimUser_' . $userData['id'])
            && ($user = $generalCache->get('fim_fimUser_' . $userData['id'])) != false) {
            return UserFactory::$instances[$userData['id']] = $user;
        }

        else {
            return UserFactory::$instances[$userData['id']] = new fimUser($userData);
        }
    }

    public static function cacheInstances() {
        global $generalCache;

        foreach (UserFactory::$instances AS $id => $instance) {
            if (!$generalCache->exists('fim_fimUser_' . $id)) {
                $generalCache->add('fim_fimUser_' . $id, $instance, \Fim\Config::$cacheDynamicObjectsTimeout);
            }
            elseif ($instance->doCache) {
                $instance->resolveAll();
                $instance->doCache = false;

                $generalCache->set('fim_fimUser_' . $id, $instance, \Fim\Config::$cacheDynamicObjectsTimeout);
            }
        }
    }
}
?>