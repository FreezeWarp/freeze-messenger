<?php

namespace Login;

/**
 * This is the broad functionality used by any Database login provider: one that, given a username and password, can look it up in a database, and then fetch additional user data.
 */
abstract class LoginDatabase implements LoginRunner
{
    /**
     * @var LoginFactory
     */
    public $loginFactory;

    /**
     * @var \OAuth2\GrantType\GrantTypeInterface A grant type that can be used to obtain a session token. It must be set by an implementor.
     */
    public $oauthGrantType;


    /**
     * LoginDatabase constructor.
     *
     * @param $loginFactory LoginFactory The LoginFactory instance used to create this object.
     */
    public function __construct(LoginFactory $loginFactory)
    {
        $this->loginFactory = $loginFactory;
    }

    /**
     * @see LoginRunner::hasLoginCredentials()
     */
    abstract public function hasLoginCredentials(): bool;

    /**
     * @see LoginRunner::getLoginCredentials()
     */
    abstract public function getLoginCredentials();

    /**
     * @see LoginRunner::setUser()
     */
    abstract public function setUser();

    /**
     * @see LoginRunner::getLoginFactory()
     */
    public function getLoginFactory(): LoginFactory
    {
        return $this->loginFactory;
    }


    /**
     * @see LoginRunner::apiResponse()
     */
    public function apiResponse()
    {
        /* Get Session Information */
        // Add our grant type, set by an implementor.
        $this->loginFactory->oauthServer->addGrantType($this->oauthGrantType);

        // Process the token request from $_REQUEST variables.
        $oauthResponse = $this->loginFactory->oauthServer->handleTokenRequest($this->loginFactory->oauthRequest);

        // Error, if needed
        if ($oauthResponse->getStatusCode() !== 200) {
            new \Fim\Error($oauthResponse->getParameters()['error'], $oauthResponse->getParameters()['error_description']);
        }
        else {
            // Clean Our Database
            $this->loginFactory->oauthStorage->cleanSessions();

            // Get the user object from a user ID
            $user = \Fim\UserFactory::getFromId((int)$this->oauthGrantType->getUserId());
            $user->setSessionHash($oauthResponse->getParameter('access_token')); // Mainly for logging.
            $user->setClientCode($oauthResponse->getParameter('client_id')); // Mainly for logging.

            // Set the anonymous user ID, if applicable
            if ($user->isAnonymousUser()) {
                global $anonId;

                if ($this->oauthGrantType instanceof \Fim\OAuthGrantTypes\RefreshTokenGrantType) {
                    $anonId = $this->oauthGrantType->getAnonId();
                }

                $user->setAnonId($anonId);
            }

            /* Output User & Session Information */
            $user->resolveAll();
            $this->loginFactory->user = $user;

            die(new \Http\ApiData([
                'login' => [
                    'access_token'  => $user->sessionHash,
                    'refresh_token' => $oauthResponse->getParameter('refresh_token'),
                    'expires'       => $oauthResponse->getParameter('expires_in'),
                    'userData'      => array_merge([
                        'permissions' => $user->getPermissionsArray()
                    ], \Fim\Utilities::castArrayEntry(
                        \Fim\Utilities::objectArrayFilterKeys(
                            $user,
                            ['id', 'anonId', 'name', 'nameFormat', 'mainGroupId', 'socialGroupIds', 'avatar', 'profile', 'parentalAge', 'parentalFlags', 'messageFormatting', 'defaultRoomId', 'options', 'ignoredUsers', 'friendedUsers', 'favRooms', 'privacyLevel']
                        ), ['socialGroupIds', 'parentalFlags', 'ignoredUsers', 'friendedUsers', 'favRooms'], '\Http\ApiOutputList'
                    ))
                ],
            ]));
        }
    }


    /**
     * Update our {@link http://josephtparsons.com/messenger/docs/database.htm#emoticons emoticons} table using a third party data set.
     *
     * @param $emoticons array An array of emoticons, indexed by the 'emoticonText' value, which in turn contains an array with the keys 'emoticonText' and 'emoticonFile'
     */
    public function syncEmoticons($emoticons, $basePath = '')
    {
        global $loginConfig;

        // Queue the queries, which may combine queries (and may not).
        \Fim\Database::instance()->autoQueue(true);

        // Start by upserting all of the emoticons we fetched
        foreach ($emoticons AS $emoticon) {
            @\Fim\Database::instance()->upsert(\Fim\Database::$sqlPrefix . 'emoticons', [
                'text' => $emoticon['emoticonText'],
            ], [
                'file' => "{$loginConfig['url']}{$basePath}{$emoticon['emoticonFile']}"
            ]);
        }

        // Now delete all the ones we didn't
        @\Fim\Database::instance()->delete(\Fim\Database::$sqlPrefix . 'emoticons', [
            '!text' => @\Fim\Database::instance()->in(array_keys($emoticons))
        ]);

        // Commit the queued queries.
        @\Fim\Database::instance()->autoQueue(false);
    }


    /**
     * @see LoginRunner::isProfileFeatureDisabled()
     */
    public static function isProfileFeatureDisabled($feature): bool
    {
        return false;
    }

    /**
     * @see LoginRunner::isSiteFeatureDisabled()
     */
    public static function isSiteFeatureDisabled($feature): bool
    {
        return false;
    }
}