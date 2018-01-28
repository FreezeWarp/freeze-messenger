<?php
/**
 * Created by PhpStorm.
 * User: joseph
 * Date: 25/01/18
 * Time: 00:44
 */

namespace Stream;

use Minishlink\WebPush\WebPush;

class WebPushHandler
{
    public static $resolvedEndpoints = [];

    /**
     * @var WebPush
     */
    public static $webPush;

    public static function init()
    {
        self::$webPush = new WebPush([
            'VAPID' => [
                'subject'    => 'https://messenger.josephtparsons.com',
                'publicKey'  => \Fim\Config::$pushNotificationsPublicKey, // (recommended) uncompressed public key P-256 encoded in Base64-URL
                'privateKey' => \Fim\Config::$pushNotificationsPrivateKey, // (recommended) in fact the secret multiplier of the private key encoded in Base64-URL
            ],
        ]);
        self::$webPush->setAutomaticPadding(false);
    }

    public static function push($userId, $data)
    {
        foreach (\Cache\CacheFactory::get('pushSubs_' . $userId, \Cache\DriverInterface::CACHE_TYPE_DISTRIBUTED) AS $endpoint) {
            self::$resolvedEndpoints[$endpoint] = $userId;
            list($public, $private) = \Cache\CacheFactory::get('pushSubsKeys_' . $endpoint, \Cache\DriverInterface::CACHE_TYPE_DISTRIBUTED);
            self::$webPush->sendNotification($endpoint, json_encode($data), $public, $private);
        }
    }

    public static function commit()
    {
        $response = self::$webPush->flush();

        if (is_array($response)) {
            foreach ($response AS $res) {
                if (!$res['success']) {
                    \Cache\CacheFactory::setRemove('pushSubs_' . self::$resolvedEndpoints[$res['endpoint']], $res['endpoint'], \Cache\DriverInterface::CACHE_TYPE_DISTRIBUTED);
                }
            }
        }
    }
}