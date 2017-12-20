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

namespace Login;

use Database\SQL\DatabaseSQL;

/**
 * A factory that looks that the $_REQUEST information and initialises an appropriate LoginRunner instance.
 * Use hasLogin() to check if a valid LoginRunner is available. Use getLogin() to run the LoginRunner. Use apiResponse() to return an appropriate API response given the current LoginRunner status.
 */
class LoginFactory {
    /**
     * @var \OAuth2\Request
     */
    public $oauthRequest;

    /**
     * @var \Fim\OAuthProvider
     */
    public $oauthStorage;

    /**
     * @var \OAuth2\Server
     */
    public $oauthServer;

    /**
     * @var LoginRunner The currently available LoginRunner, created on construction of this class.
     */
    public $loginRunner = null;

    /**
     * @var \DatabaseSQL A DatabaseSQL instance connected to the source of the login information.
     */
    public $database;

    /**
     * @var \fimUser The user object created from a successful login.
     */
    public $user;


    public function __construct(\OAuth2\Request $oauthRequest, \Fim\OAuthProvider $oauthStorage, \OAuth2\Server $oauthServer, DatabaseSQL $database) {
        global $loginConfig;

        $this->oauthRequest = $oauthRequest;
        $this->oauthStorage = $oauthStorage;
        $this->oauthServer = $oauthServer;
        $this->database = $database;

        if (isset($_REQUEST['integrationMethod'])) {
            $loginName = $_REQUEST['integrationMethod'];
            $className = 'Login' . ucfirst($loginName);
            $classNameSpaced = "\\Login\\TwoStep\\$className";
            $includePath = __DIR__ . "/TwoStep/{$className}.php";

            if (!isset($loginConfig['extraMethods'][$loginName]['clientId'], $loginConfig['extraMethods'][$loginName]['clientSecret'])) {
                new \fimError('disabledLogin', 'The attempted login method is disabled on this server.');
            }

            elseif (!file_exists($includePath)) {
                new \fimError('uninstalledLogin', 'The attempted login method is enabled, but not installed, on this server.');
            }

            else {
                require($includePath);

                if (!class_exists($classNameSpaced)) {
                    new \fimError('brokenLogin', 'The attempted login method is installed on this server, but appears to be named incorrectly.');
                }
                else {
                    $this->loginRunner = new $classNameSpaced(
                        $this,
                        $loginConfig['extraMethods'][$loginName]['clientId'],
                        $loginConfig['extraMethods'][$loginName]['clientSecret']
                    );
                }
            }
        }

        elseif (isset($_REQUEST['password'], $_REQUEST['username'])) {
            $className = 'Login' . ucfirst($loginConfig['method']);
            $classNameSpaced = "\\Login\\Database\\$className";
            $includePath = __DIR__ . "/Database/{$className}.php";

            if (!file_exists($includePath)) {
                new \fimError('loginMisconfigured', 'Logins are currently misconfigured: a login method has been specified without a corresponding login class being available.');
            }
            else {
                require($includePath);

                if (!class_exists($classNameSpaced)) {
                    new \fimError('loginMisconfigured', 'The attempted login method is installed on this server, but appears to be named incorrectly.');
                }
                else {
                    $this->loginRunner = new $classNameSpaced($this);
                }
            }
        }

        elseif (isset($_REQUEST['grant_type'])) {
            $this->loginRunner = new LoginOAuth($this);
        }
    }


    public static function getLoginRunnerFromName($name) {
        $className = 'Login' . ucfirst($name);

        if (class_exists("\\Login\\Database\\$className")) {
            $className = "\\Login\\Database\\$className";
            return $className;
        }

        else if (class_exists("\\Login\\TwoStep\\$className")) {
            $className = "\\Login\\TwoStep\\$className";
            return $className;
        }

        else {
            return null;
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
     * Get the API response following a login. This should generally exit execution, though may cause redirects.
     */
    public function apiResponse() {
        $this->loginRunner->apiResponse();
    }


    /**
     * Get an IntegrationLogin GrantType instance from the current {LoginFactory::$user} object and set the appropriate request parameters to use it.
     *
     * @return \OAuth2\GrantType\IntegrationLogin
     */
    public function oauthGetIntegrationLogin() {
        if (empty($this->oauthRequest->request['client_id']))
            $this->oauthRequest->request['client_id'] = 'IntegrationLogin'; // Pretend we have this (if we don't).

        $this->oauthRequest->request['grant_type'] = 'integrationLogin'; // Pretend we have this. It isn't used for verification.
        $this->oauthRequest->server['REQUEST_METHOD'] =  'POST'; // Pretend we're a POST request for the OAuth library. A better solution would be to forward, but honestly, it's hard to see the point.
        return new \OAuth2\GrantType\IntegrationLogin($this->user);
    }
}