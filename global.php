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


/* Version Requirement, Magic Quotes, Display Errors and Register Globals */

$phpVersion = floatval(PHP_VERSION);
if ($phpVersion < 5.5) {
    die('The installed version of PHP is out of date. Only PHP versions 5.5 and above are supported. Contact your server host for more information if possible.');
}
elseif ($phpVersion < 7) { // Removed outright in 5.4, may as well save a CPU cycle or two.
    die('The installed version of PHP is not supported on development branches. Please wait until Preview Release 2 to for PHP 5.5 and 5.6 support.');
}


/* Require Libraries */
require_once(__DIR__ . '/config.php'); // Configuration Variables
require_once(__DIR__ . '/functions/ApiData.php'); // API Data output wrapper.
require_once(__DIR__ . '/functions/Database.php'); // Database
require_once(__DIR__ . '/functions/DatabaseSQL.php'); // Database (SQL)
require_once(__DIR__ . '/functions/fimDatabase.php'); // FIM-specific Extensions
require_once(__DIR__ . '/functions/fimDatabaseUAC.php'); // FIM-specific Extensions, UAC (it gets its own file because it might be shipped seperately to support additional intgration methods.)
require_once(__DIR__ . '/functions/fimUser.php'); // FIM-specific Extensions
require_once(__DIR__ . '/functions/fimRoom.php'); // FIM-specific Extensions
require_once(__DIR__ . '/functions/fimMessage.php'); // FIM-specific Extensions
require_once(__DIR__ . '/functions/fimCache.php'); // FIM-specific Extension to APC Wrapper
require_once(__DIR__ . '/functions/fimConfig.php'); // FIM config and factory
require_once(__DIR__ . '/functions/fimError.php'); // FIM Custom Error Class
require_once(__DIR__ . '/functions/fim_general.php'); // Various Functions


require_once(__DIR__ . '/vendor/autoload.php'); // Various Functions




/* Constants
 * These are mostly beneficial to third-party plugins. */
define("FIM_VERSION", "1.0-nightly"); // Version to be used by plugins if needed.
define("FIM_LANGUAGE", "EN_US"); // No plans to change this exist, but again, just in case...

define("CENSORLIST_INACTIVE", 1);
define("CENSORLIST_FORCED", 2);
define("CENSORLIST_HIDDEN", 4);
define("CENSORLIST_DISABLED_PRIVATE", 256);

define("POST_FORMAT_BOLD", 0x1);
define("POST_FORMAT_ITALICS", 0x2);




/* Legacy Code
 * Will be removed shortly, maybe.*/
$sqlPrefix = $dbConfig['vanilla']['tablePrefix']; // It's more sane this way...
//$forumTablePrefix = $dbConfig['integration']['tablePrefix'];




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
$database = new fimDatabase();

if (!$database->connect($dbConnect['core']['host'],
    $dbConnect['core']['port'],
    $dbConnect['core']['username'],
    $dbConnect['core']['password'],
    $dbConnect['core']['database'],
    $dbConnect['core']['driver'],
    $dbConfig['vanilla']['tablePrefix'])) {
    die('Could not connect to the database: ' . $database->getLastError() . '; the application has exitted.'); // Die to prevent further execution.
}
else {
    require('databaseParameters.php');
}


/* Connect to the Integration DB  */
if ($loginConfig['method'] != 'vanilla') {
    $integrationDatabase = new fimDatabase();

    if (!$integrationDatabase->connect($dbConnect['integration']['host'],
        $dbConnect['integration']['port'],
        $dbConnect['integration']['username'],
        $dbConnect['integration']['password'],
        $dbConnect['integration']['database'],
        $dbConnect['integration']['driver'],
        $dbConfig['integration']['tablePrefix'])) { // Connect to MySQL
        die('Could not connect to the integration database: ' . $database->error . '; the application has exitted.');
    }
}
else {
    $integrationDatabase = $database;
}


/* Connect to the DB Slave
 * This assumes external replication is set up, and the slave is a copy of the master with possible latency. (Thus, we use it when a little latency is okay.) */
if ($dbConnect['core'] != $dbConnect['slave']) {
    $slaveDatabase = new fimDatabase;

    if (!$database->connect($dbConnect['slave']['host'],
        $dbConnect['slave']['port'],
        $dbConnect['slave']['username'],
        $dbConnect['slave']['password'],
        $dbConnect['slave']['database'],
        $dbConnect['slave']['driver'],
        $dbConfig['vanilla']['tablePrefix'])) { // Connect to MySQL
        die('Could not connect to the slave database: ' . $database->error . '; the application has exitted.');
    }
}
else {
    $slaveDatabase = $database;
}



unset($dbConnect); // There is no reason the login credentials should still be active. A variety of exploits could take advantage of this otherwise -- buffer overflow, issues in plugins, templates, and so-fourth. If anyone knows about the vBulletin 3.8.6 mess... you know what I'm talking about. (Yes, this program is that old. So... many... betas.)



////* Bulk Data Caching *////
/* Only small tables are cached this way. */

// Initiate cache object.
$generalCache = new fimCache(null, null, $database, $slaveDatabase);
foreach ($cacheConnectMethods AS $cacheConnectName => $cacheConnectParams) {
    $generalCache->addMethod($cacheConnectName, $cacheConnectParams);
}

// Get Configuration Data
$config = fimConfigFactory::init($slaveDatabase);


$database->registerConfig($config);
$database->queryLogToFile = (fimConfig::$logQueries ? fimConfig::$logQueriesFile : false);


////* User Login (Requires Database) *////
require_once(dirname(__FILE__) . '/validate.php'); // This is where all the user validation stuff occurs.
?>