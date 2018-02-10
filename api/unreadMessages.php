<?php
/**
 * Get the Active User's Unread Messages.
 * An "unread message" is created whenever a message is inserted into a room watched by a user, or a private room that that user is a part of, and the user does not appear to be online (according to the ping table/database->getActiveUsers()).
 */

class unreadMessages {
    static $xmlData;

    static $requestHead;

    static function init() {
        global $user;

        self::$requestHead = \Fim\Utilities::sanitizeGPC('g', [
            '_action' => [],
        ]);

        /* Make Sure the User is Valid */
        if (!$user->isValid() || $user->isAnonymousUser())
            new \Fim\Error('loginRequired', 'You must be logged in to get your unread messages.');

        self::{self::$requestHead['_action']}();
    }

    static function get() {
        \Fim\Database::instance()->accessLog('getUnreadMessages', []);

        self::$xmlData = [
            'unreadMessages' => array_merge(
                \Fim\Database::instance()->getUnreadMessages()->getAsArray(true),
                \Fim\Database::instance()->getUnreadPrivateMessages()->getAsArray(true)
            )
        ];
    }

    static function delete() {
        self::$requestHead = array_merge(self::$requestHead, \Fim\Utilities::sanitizeGPC('g', [
            'roomId' => [
                'cast'    => 'roomId',
                'require' => true
            ]
        ]));

        \Fim\Database::instance()->accessLog('markMessageRead', self::$requestHead);
        \Fim\Database::instance()->markMessageRead(self::$requestHead['roomId'], \Fim\LoggedInUser::instance()->id);

        self::$xmlData = [
            'markMessageRead' => []
        ];
    }
}


/* Entry Point Code */
$apiRequest = true;
require('../global.php');
unreadMessages::init();
echo new Http\ApiData(unreadMessages::$xmlData);