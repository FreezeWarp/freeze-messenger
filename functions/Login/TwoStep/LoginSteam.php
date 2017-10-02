<?php

namespace Login\TwoStep;

use Login\LoginFactory;
use Login\LoginTwoStep;

class LoginSteam extends LoginTwoStep {
    public $client;

    public function __construct(LoginFactory $loginFactory, $clientId, $clientSecret) {
        global $installUrl;
        parent::__construct($loginFactory);

        $this->client = new \LightOpenID($installUrl);
        $this->client->identity = 'http://steamcommunity.com/openid';
    }

    public function hasLoginCredentials(): bool {
        return $this->client->mode && $this->client->mode != "cancel";
    }

    public function getLoginCredentials() {
        header('Location: ' . $this->client->authUrl());
        die();
    }

    public function setUser() {
        global $loginConfig;

        if ($this->client->validate()) {
            $matches = [];
            preg_match("/^http:\/\/steamcommunity\.com\/openid\/id\/(\d+)$/", $this->client->identity, $matches);

            $steamId = $matches[1];

            if (isset($loginConfig['extraMethods']['steam']['clientId'])) {
                /* Get User Info & Create Account */
                $userInfo = \Http\curlRequest::quickRunGET('http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/', [
                    'key' => $loginConfig['extraMethods']['steam']['clientId'],
                    'steamids' => $steamId,
                ])['response']['players'][0];

                if (!$userInfo) {
                    throw new \Exception('userInfo could not be retrieved from Steam API.');
                }

                $this->loginFactory->user = new \fimUser([
                    'integrationMethod' => 'steam',
                    'integrationId' => (int) $userInfo['steamid'],
                ]);
                $this->loginFactory->user->resolveAll(); // This will resolve the ID if the user exists.
                $this->loginFactory->user->setDatabase([
                    'integrationMethod' => 'steam',
                    'integrationId' => (int) $userInfo['steamid'],
                    'name' => ($userInfo['realname'] ?? $userInfo['personaName']) ?: new \fimError('steamApiNoName', 'No name found in Steam API response.'),
                    'avatar' => $userInfo['avatarfull'],
                    'profile' => $userInfo['profileurl']
                    //bio $userInfo->description
                ]);


                /* Lookup Games the User Plays */
                $games = \Http\curlRequest::quickRunGET('http://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/', [
                    'key' => $loginConfig['extraMethods']['steam']['clientId'],
                    'steamid' => $steamId,
                    'format' => 'json',
                    'include_appinfo' => true,
                ]);

                foreach ($games['response']['games'] AS $game) {
                    if ($game['playtime_forever'] > 0) {
                        // TODO: group icon
                        // img_icon_url

                        // create group if doesn't exist
                        // name: 'Steam Players of ' . $game['name']
                    }
                }
            }
        }
        else {
            throw new \Exception('Failed to confirm user identity.');
        }
    }

}