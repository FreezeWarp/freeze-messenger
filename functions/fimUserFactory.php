<?php
/**
 * Class fimUserFactory
 */
class fimUserFactory {
    public static function getFromId(int $userId) {
        if (function_exists('apc_fetch') && apc_exists('fim_fimUser_' . $userId)) {
            return apc_fetch('fim_fimUser_' . $userId);
        }

        else if (function_exists('apcu_fetch') && apcu_exists('fim_fimUser_' . $userId)) {
            return apcu_fetch('fim_fimUser_' . $userId);
        }

        else {
            return new fimUser($userId);
        }
    }

    public static function getFromData(array $userData) : fimUser {
        if (!isset($userData['id'])) {
            throw new Exception('Userdata must contain id');
        }

        elseif (function_exists('apc_fetch') && apc_exists('fim_fimUser_' . $userData['userId'])) {
            $user = apc_fetch('fim_fimUser_' . $userData['userId']);
            $user->populateFromArray($userData);
            return $user;
        }

        elseif (function_exists('apcu_fetch') && apcu_exists('fim_fimUser_' . $userData['userId'])) {
            $user = apcu_fetch('fim_fimUser_' . $userData['userId']);
            $user->populateFromArray($userData);
            return $user;
        }

        else {
            return new fimUser($userData);
        }
    }
}
?>