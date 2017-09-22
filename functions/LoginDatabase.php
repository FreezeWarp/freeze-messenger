<?php

require_once('LoginRunner.php');
abstract class LoginDatabase implements LoginRunner {

    /**
     * @var LoginFactory
     */
    public $loginFactory;

    /**
     * @var OAuth2\GrantType\GrantTypeInterface A grant type that can be used to obtain a session token. It must be set by an implementor.
     */
    public $oauthGrantType;


    public function __construct($loginFactory) {
        $this->loginFactory = $loginFactory;
    }

    abstract public function hasLoginCredentials(): bool;
    abstract public function getLoginCredentials();
    abstract public function setUser();

    public function getLoginFactory(): LoginFactory {
        return $this->loginFactory;
    }

    public function apiResponse() {
        /* Get Session Information */
        // Add our grant type, set by an implementor.
        $this->loginFactory->oauthServer->addGrantType($this->oauthGrantType);

        // Process the token request from $_REQUEST variables.
        $oauthResponse = $this->loginFactory->oauthServer->handleTokenRequest($this->loginFactory->oauthRequest);

        // Error, if needed
        if ($oauthResponse->getStatusCode() !== 200) {
            new fimError($oauthResponse->getParameters()['error'], $oauthResponse->getParameters()['error_description']);
        }
        else {
            // Get the user object from a user ID
            $user = fimUserFactory::getFromId((int) $this->oauthGrantType->getUserId());
            $user->setSessionHash($oauthResponse->getParameter('access_token')); // Mainly for logging.
            $user->setClientCode($oauthResponse->getParameter('client_id')); // Mainly for logging.

            // Set the anonymous user ID, if applicable
            if ($_REQUEST['grant_type'] === 'anonymous') {
                $user->setAnonId($this->oauthGrantType->getAnonymousUserId());
            }

            /* Output User & Session Information */
            $user->resolveAll();
            $this->loginFactory->user = $user;

            die(new ApiData(array(
                'login' => array(
                    'access_token' => $user->sessionHash,
                    'refresh_token' => $oauthResponse->getParameter('refresh_token'),
                    'expires' => $oauthResponse->getParameter('expires_in'),
                    'userData' => array_merge([
                        'permissions' => $user->getPermissionsArray()
                    ], fim_castArrayEntry(
                        fim_objectArrayFilterKeys(
                            $user,
                            ['id', 'anonId', 'name', 'nameFormat', 'mainGroupId', 'socialGroupIds', 'avatar', 'profile', 'parentalAge', 'parentalFlags', 'messageFormatting', 'defaultRoomId', 'options', 'ignoredUsers', 'friendedUsers', 'favRooms', 'watchRooms']
                        ), ['socialGroupIds', 'parentalFlags', 'ignoredUsers', 'friendedUsers', 'favRooms', 'watchRooms'], 'ApiOutputList'
                    ))
                ),
            )));
        }
    }

}