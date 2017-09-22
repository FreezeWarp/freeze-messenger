<?php
/* FreezeMessenger Copyright © 2017 Joseph Todd Parsons

 * This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>. */

class LoginFactory {
    /**
     * @var \OAuth2\Request
     */
    public $oauthRequest;

    /**
     * @var \OAuth2\Storage\FIMDatabaseOAuth
     */
    public $oauthStorage;

    /**
     * @var \OAuth2\Server
     */
    public $oauthServer;

    /**
     * @var LoginRunner
     */
    public $loginRunner = null;

    /**
     * @var fimUser
     */
    public $user;

    public function __construct(OAuth2\Request $oauthRequest, OAuth2\Storage\FIMDatabaseOAuth $oauthStorage, OAuth2\Server $oauthServer) {
        global $loginConfig;

        $this->oauthRequest = $oauthRequest;
        $this->oauthStorage = $oauthStorage;
        $this->oauthServer = $oauthServer;

        if (isset($_REQUEST['integrationMethod'])) {
            $loginName = $_REQUEST['integrationMethod'];
            $className = 'Login' . ucfirst($loginName);
            $includePath = __DIR__ . "/{$className}.php";

            if (!isset($loginConfig['extraMethods'][$loginName]['clientId'], $loginConfig['extraMethods'][$loginName]['clientSecret'])) {
                new fimError('disabledLogin', 'The attempted login method is disabled on this server.');
            }

            elseif (!file_exists($includePath)) {
                new fimError('uninstalledLogin', 'The attempted login method is enabled, but not installed, on this server.');
            }

            else {
                require($includePath);

                if (!class_exists($className)) {
                    new fimError('brokenLogin', 'The attempted login method is installed on this server, but appears to be named incorrectly.');
                }
                else {
                    $this->loginRunner = new $className(
                        $this,
                        $loginConfig['extraMethods'][$loginName]['clientId'],
                        $loginConfig['extraMethods'][$loginName]['clientSecret']
                    );
                }
            }
        }

        elseif (isset($_REQUEST['password'], $_REQUEST['username'])) {
            global $loginConfig, $database;

            $className = 'Login' . ucfirst($loginConfig['method']);
            $includePath = __DIR__ . "/{$className}.php";

            if (!file_exists($includePath)) { var_dump($includePath); die();
                new fimError('loginMisconfigured', 'Logins are currently misconfigured: a login method has been specified without a corresponding login class being available.');
            }
            else {
                require($includePath);

                if (!class_exists($className)) {
                    new fimError('loginMisconfigured', 'The attempted login method is installed on this server, but appears to be named incorrectly.');
                }
                else {
                    $this->loginRunner = new $className($this, $database);
                }
            }
        }

        elseif (isset($_REQUEST['grant_type'])) {
            global $database;

            require('LoginOAuth.php');
            $this->loginRunner = new LoginOAuth($this, $database);
        }
    }

    /**
     * Whether or not an integration login is available.
     * @return bool
     */
    public function hasLogin() {
        return $this->loginRunner != null;
    }


    /**
     * Perform a login.
     */
    public function getLogin() {
        if ($this->loginRunner->hasLoginCredentials()) {
            $this->loginRunner->setUser();
        }
        else {
            $this->loginRunner->getLoginCredentials();
        }
    }

    /**
     * Get the API response, following a login.
     */
    public function apiResponse() {
        $this->loginRunner->apiResponse();
    }
}