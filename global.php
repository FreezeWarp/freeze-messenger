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

/**
 * This is the global runner for FreezeMessenger.
 * It will include all needed resources, defines a small number of constants,
 * @global $cacheConnectMethods array
 * @global $loginConfig array
 * @global $dbConnect array
 */

/* Version Requirement, Magic Quotes, Display Errors and Register Globals */

$phpVersion = floatval(PHP_VERSION);
if ($phpVersion < 5.5) {
    die('The installed version of PHP is out of date. Only PHP versions 5.5 and above are supported. Contact your server host for more information if possible.');
}
elseif ($phpVersion < 7) {
    die('The installed version of PHP is not supported on development branches. Please wait until Release Candidate 1 for PHP 5.5 and 5.6 support.');
}


/* Require Libraries */
require_once(__DIR__ . '/vendor/autoload.php'); // Various Functions

require_once(__DIR__ . '/config.php'); // Configuration Variables
require_once(__DIR__ . '/functions/fim_general.php'); // Various Functions




/* Constants
 * These are mostly beneficial to third-party plugins. */
define("FIM_VERSION", "1.0-nightly"); // Version to be used by plugins if needed.
define("FIM_LANGUAGE", "EN_US"); // No plans to change this exist, but again, just in case...



/* Language
 * The defaultLanguage flag was created with the WebPro interface in mind, however it's a good one for all people to have (as with the template and phrase tables). Likewise, it could even be used in the API in theory, but... meh. Anyway, even if set to anything other than en, don't expect much (so far as the WebPro interface goes).
 * Sadly, the entire language backend more or less is broken in FIMv3. */
if (!isset($defaultLanguage)) {
    $defaultLanguage = 'en';
}



/* Better Error Handling */
set_exception_handler('fim_exceptionHandler'); // Defined in fim_general.php


////* Database Stuff *////

/* If the connections are the same, do not make multiple below. */
/* Connect to the Main Database */
if (!\Fim\Database::connect(
    $dbConnect['core']['host'],
    $dbConnect['core']['port'],
    $dbConnect['core']['username'],
    $dbConnect['core']['password'],
    $dbConnect['core']['database'],
    $dbConnect['core']['driver'],
    $dbConnect['vanilla']['tablePrefix']
)) {
    die('Could not connect to the database: ' . \Fim\Database::instance()->getLastError() . '; the application has exitted.'); // Die to prevent further execution.
}


/* Connect to the Integration DB  */
if ($loginConfig['method'] != 'vanilla') {
    if (!\Fim\DatabaseLogin::connect(
        $dbConnect['integration']['host'],
        $dbConnect['integration']['port'],
        $dbConnect['integration']['username'],
        $dbConnect['integration']['password'],
        $dbConnect['integration']['database'],
        $dbConnect['integration']['driver'],
        $dbConnect['integration']['tablePrefix'])
    ) {
        die('Could not connect to the integration database: ' . \Fim\DatabaseLogin::instance()->getLastError() . '; the application has exitted.');
    }
}
else {
    \Fim\DatabaseLogin::setInstance(\Fim\Database::instance());
}


/* Connect to the DB Slave
 * This assumes external replication is set up, and the slave is a copy of the master with possible latency. (Thus, we use it when a little latency is okay.) */
if ($dbConnect['core'] != $dbConnect['slave']) {
    if (!\Fim\DatabaseSlave::connect(
        $dbConnect['slave']['host'],
        $dbConnect['slave']['port'],
        $dbConnect['slave']['username'],
        $dbConnect['slave']['password'],
        $dbConnect['slave']['database'],
        $dbConnect['slave']['driver'],
        $dbConnect['vanilla']['tablePrefix'])
    ) {
        die('Could not connect to the slave database: ' . \Fim\DatabaseSlave::instance()->getLastError() . '; the application has exitted.');
    }
}
else {
    \Fim\DatabaseSlave::setInstance(\Fim\Database::instance());
}



unset($dbConnect); // There is no reason the login credentials should still be active. A variety of exploits could take advantage of this otherwise -- buffer overflow, issues in plugins, templates, and so-fourth. If anyone knows about the vBulletin 3.8.6 mess... you know what I'm talking about. (Yes, this program is that old. So... many... betas.)



////* Bulk Data Caching *////
/* Only small tables are cached this way. */

// Initiate cache object.
if (isset($cacheConnectMethods)) {
    foreach ($cacheConnectMethods AS $cacheConnectName => $cacheConnectParams) {
        \Cache\CacheFactory::addMethod($cacheConnectName, $cacheConnectParams);
    }
}

// Get Configuration Data
\Fim\Cache::loadConfig();


// Log queries, if enabled.
\Fim\Database::instance()->queryLogToFile = (\Fim\Config::$logQueries ? \Fim\Config::$logQueriesFile : false);


// Cache object instances at shutdown.
register_shutdown_function(function() {
    \Fim\UserFactory::cacheInstances();
    \Fim\RoomFactory::cacheInstances();
});


////* User Login (Requires Database) *////
require_once(dirname(__FILE__) . '/validate.php'); // This is where all the user validation stuff occurs.
?>