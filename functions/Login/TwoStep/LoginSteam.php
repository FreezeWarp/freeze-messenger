<?php

namespace Login\TwoStep;

use Login\LoginFactory;
use Login\LoginTwoStep;

/**
 * Steam Login Provider
 * This will use the Steam client library to authenticate users using Steam login credentials.
 * It does not use a client secret.
 */
class LoginSteam extends LoginTwoStep {
    /**
     * @var \LightOpenID The LightOpenID instance.
     */
    public $client;
    /**
     * @var int The Steam API client ID.
     */
    public $clientId;


    /**
     * LoginSteam constructor.
     *
     * @param $loginFactory LoginFactory The LoginFactory instance used to create this object.
     * @param $clientId     string {@see $clientId}.
     * @param $clientSecret string Unused.
     */
    public function __construct(LoginFactory $loginFactory, $clientId, $clientSecret) {
        global $installUrl;
        parent::__construct($loginFactory);

        $this->client = new \LightOpenID($installUrl);
        $this->client->identity = 'http://steamcommunity.com/openid';
    }


    /**
     * @see LoginRunner::hasLoginCredentials()
     */
    public function hasLoginCredentials(): bool {
        return $this->client->mode && $this->client->mode != "cancel";
    }


    /**
     * @see LoginRunner::getLoginCredentials()
     */
    public function getLoginCredentials() {
        header('Location: ' . $this->client->authUrl());
        die();
    }


    /**
     * @see LoginRunner::setUser()
     */
    public function setUser() {
        global $loginConfig;

        if ($this->client->validate()) {
            $matches = [];
            preg_match("/^http:\/\/steamcommunity\.com\/openid\/id\/(\d+)$/", $this->client->identity, $matches);

            $steamId = $matches[1];

            if (isset($loginConfig['extraMethods']['steam']['clientId'])) {
                /* Get User Info & Create Account */
                $userInfo = \Http\CurlRequest::quickRunGET('http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/', [
                    'key' => $loginConfig['extraMethods']['steam']['clientId'],
                    'steamids' => $steamId,
                ])['response']['players'][0];

                if (!$userInfo) {
                    throw new \Exception('userInfo could not be retrieved from Steam API.');
                }


                /* Set User Info */
                $this->loginFactory->user = new \Fim\User([
                    'integrationMethod' => 'steam',
                    'integrationId' => (int) $userInfo['steamid'],
                ]);
                $this->loginFactory->user->resolveAll(); // This will resolve the ID if the user exists.
                $this->loginFactory->user->setDatabase([
                    'integrationMethod' => 'steam',
                    'integrationId' => (int) $userInfo['steamid'],
                    'name' => ($userInfo['realname'] ?? $userInfo['personaName']) ?: new \Fim\Error('steamApiNoName', 'No name found in Steam API response.'),
                    'avatar' => $userInfo['avatarfull'],
                    'profile' => $userInfo['profileurl']
                    //bio $userInfo->description
                ]);


                /* Lookup Games the User Plays */
                $games = \Http\CurlRequest::quickRunGET('http://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/', [
                    'key' => $loginConfig['extraMethods']['steam']['clientId'],
                    'steamid' => $steamId,
                    'format' => 'json',
                    'include_appinfo' => true,
                ]);

                if (isset($games['response']['games'])) {
                    $groupNames = [];
                    foreach ($games['response']['games'] AS $game) {
                        if ($game['playtime_forever'] > 0) { 
                            // TODO: group icon
                            // img_icon_url

                            $groupNames[] = 'Steam Players of ' . $game['name'];

                            // create group if doesn't exist
                            @\Fim\Database::instance()->createSocialGroup('Steam Players of ' . $game['name']);
                        }
                    }

                    $dbGroupIds = \Fim\Database::instance()->select([
                        \Fim\Database::$sqlPrefix . 'socialGroups' => 'id, name'
                    ], ['name' => \Fim\Database::instance()->in($groupNames)])->getColumnValues('id');

                    \Fim\Database::instance()->autoQueue(true);
                    foreach ($dbGroupIds AS $groupId) {
                        @\Fim\Database::instance()->enterSocialGroup($groupId, $this->loginFactory->user);
                    }
                    @\Fim\Database::instance()->autoQueue(false);
                }


                /* Lookup Steam Friends */
                $friends = \Http\CurlRequest::quickRunGET('http://api.steampowered.com/ISteamUser/GetFriendList/v0001/', [
                    'key' => $loginConfig['extraMethods']['steam']['clientId'],
                    'steamid' => $steamId,
                    'format' => 'json',
                ]);

                if (isset($friends['friendslist']['friends'])) {
                    $friendMatches = [];

                    foreach ($friends['friendslist']['friends'] AS $friend) {
                        $friendMatches[] = [
                            'integrationMethod' => 'steam',
                            'integrationId' => $friend['steamid']
                        ];
                    }

                    $dbFriends = \Fim\Database::instance()->select([
                        \Fim\Database::$sqlPrefix . 'users' => 'id, name, integrationId, integrationMethod'
                    ], ['either' => $friendMatches])->getColumnValues('id');

                    if (count($dbFriends)) {
                        $this->loginFactory->user->editList('friendedUsers', $dbFriends, 'create');
                    }
                }
            }
        }
        else {
            throw new \Exception('Failed to confirm user identity.');
        }
    }


    /**
     * Indicates that 'selfChangeAvatar' and 'selfChangeProfile' are disabled profile feature when using Steam logins.
     * @see LoginRunner::isProfileFeatureDisabled()
     */
    public static function isProfileFeatureDisabled($feature): bool {
        return in_array($feature, ['selfChangeAvatar', 'selfChangeProfile']);
    }

}