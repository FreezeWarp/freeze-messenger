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

/**
 * An interface for Login implementations.
 */
interface LoginRunner {

    /**
     * Checks if login credentials are available in the current environment (typically through $_REQUEST variables).
     * @return bool
     */
    public function hasLoginCredentials(): bool;

    /**
     * Attempt to obtain login credentials if they are not available. This will not be possible in all LoginRunner instances.
     * @return void
     */
    public function getLoginCredentials();

    /**
     * Initialise the user object, storing it in the associated LoginFactory->user instance.
     */
    public function setUser();

    /**
     * Get the LoginFactory used to create this LoginRunner. It will hold OAuth, database, and user information.
     *
     * @return LoginFactory
     */
    public function getLoginFactory() : LoginFactory;

    /**
     * Create an API response based on the current information. This should end the program execution, though it may first pass redirect headers, etc.
     */
    public function apiResponse();

    /**
     * @param $feature
     *
     * @return bool True if the feature is provided by the login runner, false otherwise.
     */
    public static function isProfileFeatureDisabled($feature): bool;

}