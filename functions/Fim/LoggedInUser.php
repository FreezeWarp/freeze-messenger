<?php

namespace Fim;

class LoggedInUser
{
    static $user;

    public static function setUser($user) {
        self::$user = $user;
    }

    public static function instance() {
        if (empty(self::$user)) {
            self::$user = new User(0);
        }

        return self::$user;
    }
}