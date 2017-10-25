<?php
/**
 * Class fimUserFactory
 */
class fimUserFactory {
    static $instances = [];

    public static function getFromId(int $userId) {
        global $generalCache;

        if (isset(fimUserFactory::$instances[$userId]))
            return fimUserFactory::$instances[$userId];

        elseif ($generalCache->exists('fim_fimUser_' . $userId)
            && ($user = $generalCache->get('fim_fimUser_' . $userId)) != false)
            return fimUserFactory::$instances[$userId] = $user;

        else
            return fimUserFactory::$instances[$userId] = new fimUser($userId);
    }

    public static function getFromData(array $userData) : fimUser {
        global $generalCache;

        if (!isset($userData['id']))
            throw new Exception('Userdata must contain id');

        elseif (isset(fimUserFactory::$instances[$userData['id']])) {
            fimUserFactory::$instances[$userData['id']]->populateFromArray($userData);
            return fimUserFactory::$instances[$userData['id']];
        }

        elseif ($generalCache->exists('fim_fimUser_' . $userData['id'])
            && ($user = $generalCache->get('fim_fimUser_' . $userData['id'])) != false) {
            return fimUserFactory::$instances[$userData['id']] = $user;
        }

        else {
            return fimUserFactory::$instances[$userData['id']] = new fimUser($userData);
        }
    }

    public static function cacheInstances() {
        global $generalCache;

        foreach (fimUserFactory::$instances AS $id => $instance) {
            if (!$generalCache->exists('fim_fimUser_' . $id)) {
                $generalCache->add('fim_fimUser_' . $id, $instance, 5 * 60);
            }
            elseif ($instance->doCache) {
                $instance->resolveAll();
                $instance->doCache = false;

                $generalCache->set('fim_fimUser_' . $id, $instance, 5 * 60);
            }
        }
    }
}
?>