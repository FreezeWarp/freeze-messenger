<?php
/* FreezeMessenger Copyright © 2014 Joseph Todd Parsons

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

/* Configuration File
 * Core MySQLLogin, Cookie/etc. Salt, and Site Data is stored here. */


////* PREREQUISITES *////

/* Make Sure Required PHP Extensions are Present
 *
 * Note that:
 * MySQL is present in all versions since PHP 4
 * JSON is present in all versions of PHP since 5.2 (but is not actually used in FIMv3)
 ** JSON is used if APC is not available
 * MBString is present in all versions of PHP since 4.3
 * MCrypt is present in all versions since PHP 4
 * PCRE is present in all versions since PHP 4
 * APC is present in PHP 5.4, Ubuntu's php-apc packge, and easily installed from PECL.net. It simply has to be a requirement for many of the functions (as far as the way they are designed).
 * DOM is in all of PHP 5.
 *
 * The following are used, but with safe fallbacks:
 * Hash is present in all versions since PHP 5.1.2; MHash is present in all versions since PHP4
 */
foreach (array('mysql', 'json', 'mbstring', 'mcrypt', 'pcre', 'dom') AS $module) { // Check that each extension has been loaded.
  if (!extension_loaded($module)) die("The module <strong>$module</strong> could not be found. Please install PHP <strong>$module</strong> compatibility. See the documentation for help.");
}

/*
 * I'm not yet sure how well the disk cache works for small installations. APC is still essential for larger ones, but the disk cache may suffice for everything else.
 * Ideally, a disk cache will be possible for extra-common data, of which there is some.

  if (!extension_loaded('apc') && !extension_loaded('memcache')) die("Neither the <strong>apc</strong> or <strong>memcache</strong> modules could not be found. Please install PHP <strong>apc</strong> or <strong>memcache</strong> compatibility. See the documentation for help."); // APC is required. Memcached is not yet supported.
if (!extension_loaded('hash') && !extension_loaded('mhash')) die("Neither the <strong>hash</strong> or <strong>mhash</strong> modules could not be found. Please install PHP <strong>hash</strong> or <strong>mhash</strong> compatibility. See the documentation for help."); // Either can be used.
*/

if ((bool) ini_get('allow_url_fopen') === false) {
  die('FOpen functionality is disable. Please enable allow_url_fopen in php.ini. More information can be found in the <a href="http://www.php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen">PHP manual</a>, and in the documentation.');
}


/* Version Requirement, Magic Quotes, Display Errors and Register Globals */

error_reporting(~E_NOTICE & ~E_USER_NOTICE); // There's no shortage of warnings and notices, so let's not show those. (Yes, that's not a good thing. No, I don't really want to fix them all right now.)

$phpVersion = floatval(PHP_VERSION);
if ($phpVersion < 5.2) { // We won't bother supporting older PHP; too much hassle. We will also raise this to 5.3 in the next version.
  die('The installed version of PHP is out of date. Only PHP versions 5.2 and above are supported. Contact your server host for more information if possible.');
}
elseif ($phpVersion <= 5.3) { // Removed outright in 5.4, may as well save a CPU cycle or two.
  if (function_exists('get_magic_quotes_runtime')) { // Really, in the future, even this function will be removed as well, but it is still there in 5.4 for all the good scripts that use it to disable 'em.
    if (get_magic_quotes_runtime()) { // Note: We should consider removing the set_magic_quotes_runtime to false; it is deprecated in 5.3, so if we make that the baseline in version 4 we will do it then.
      if (!set_magic_quotes_runtime(false)) {
        die('Magic Quotes is enabled and it was not possible to disable this "feature". Please disable magic quotes in php.ini. More information can be found in the <a href="http://php.net/manual/en/security.magicquotes.disabling.php">PHP manual</a>, and in the documentation.');
      }
    }

    if (get_magic_quotes_gpc()) { // Note: We will also assume the above function_exists counts for this one.
      die('Magic Quotes is enabled and it was not possible to disable this "feature". Please disable magic quotes in php.ini. More information can be found in the <a href="http://php.net/manual/en/security.magicquotes.disabling.php">PHP manual</a>, and in the documentation.'); // In theory, we could just strip the globals, but is it really worth the CPU cycles?
    }
  }

  if ((bool) ini_get('register_globals') === true) { // Note: This can not be altered with ini_set, so... we won't even bother. We will remove it in the next version most likely, as no one really uses it anyway.
    die('Register Globals is enabled. Please disable register_globals in php.ini. More information can be found in the <a href="http://www.php.net/manual/en/security.globals.php">PHP manual</a>, and in the documentation.');
  }
}


/* Require Libraries */
require(dirname(__FILE__) . '/config.php'); // Configuration Variables
require(dirname(__FILE__) . '/functions/cache.php'); // APC Wrapper (may use for alteratives like memcached later)
require(dirname(__FILE__) . '/functions/apiData.php'); // API Data output wrapper.
require(dirname(__FILE__) . '/functions/database.php'); // Database
require(dirname(__FILE__) . '/functions/databaseSQL.php'); // Database (SQL)
require(dirname(__FILE__) . '/functions/fim_database.php'); // FIM-specific Extensions
require(dirname(__FILE__) . '/functions/fim_databaseUAC.php'); // FIM-specific Extensions, UAC (it gets its own file because it might be shipped seperately to support additional intgration methods.)
require(dirname(__FILE__) . '/functions/fim_user.php'); // FIM-specific Extensions
require(dirname(__FILE__) . '/functions/fim_room.php'); // FIM-specific Extensions
require(dirname(__FILE__) . '/functions/fim_cache.php'); // FIM-specific Extension to APC Wrapper
require(dirname(__FILE__) . '/functions/fim_error.php'); // FIM Custom Error Class
require(dirname(__FILE__) . '/functions/fim_general.php'); // Various Functions




/* Constants
 * These are mostly beneficial to third-party plugins. */
define("FIM_VERSION", "1.0"); // Version to be used by plugins if needed.
define("FIM_LANGUAGE", "EN_US"); // No plans to change this exist, but again, just in case...

define("ROOM_OFFICIAL", 1);
define("ROOM_DELETED", 4);
define("ROOM_HIDDEN", 8);
define("ROOM_ARCHIVED", 16);
define("ROOM_R9000", 256);

define("CENSORLIST_INACTIVE", 1);
define("CENSORLIST_FORCED", 2);
define("CENSORLIST_HIDDEN", 4);
define("CENSORLIST_DISABLED_PRIVATE", 256);

define("ROOM_PERMISSION_VIEW", 1);
define("ROOM_PERMISSION_POST", 2);
define("ROOM_PERMISSION_TOPIC", 4);
define("ROOM_PERMISSION_MODERATE", 8);
define("ROOM_PERMISSION_PROPERTIES", 16);
define("ROOM_PERMISSION_GRANT", 128);

define("ROOM_TYPE_PRIVATE", 'private');
define("ROOM_TYPE_OTR", 'otr');

define("USER_PRIV_VIEW", 0x1);
define("USER_PRIV_POST", 0x2);
define("USER_PRIV_TOPIC", 0x4);
define("USER_PRIV_CREATE_ROOMS", 0x20);
define("USER_PRIV_PRIVATE_FRIENDS", 0x40);
define("USER_PRIV_PRIVATE_ALL", 0x80);
define("USER_PRIV_ACTIVE_USERS", 0x400);
define("USER_PRIV_POST_COUNTS", 0x800);

define("ADMIN_GRANT", 0x10000);
define("ADMIN_PROTECTED", 0x20000);
define("ADMIN_ROOMS", 0x40000);
define("ADMIN_VIEW_PRIVATE", 0x80000);
define("ADMIN_USERS", 0x100000);
define("ADMIN_FILES", 0x400000);
define("ADMIN_CENSOR", 0x1000000);




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
$database = new fimDatabaseUAC;
if (!$database->connect($dbConnect['core']['host'],
  $dbConnect['core']['port'],
  $dbConnect['core']['username'],
  $dbConnect['core']['password'],
  $dbConnect['core']['database'],
  $dbConnect['core']['driver'],
  $dbConfig['vanilla']['tablePrefix'])) {
  die('Could not connect to the database: ' . $database->error . '; the application has exitted.'); // Die to prevent further execution.
}


/* Connect to the Integration DB
 * On the whole, the product was designed such that all tables are in one database, but for the advanced users out there... */
if ($dbConnect['core'] != $dbConnect['integration']) {
  $integrationDatabase = new fimDatabaseUAC;

  if (!$database->connect($dbConnect['integration']['host'],
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
 * Unfortunately, this can not be reliably used in v3. It will be more of a focus in the future.
 * Still, if you do use it, it can ease load.
 * NOTE:
 ** Slave Database should be used, at least in the future, to referrence values that can have high latency. For instance, kicks, which can change within the minute, require relatively low latency, but roomData can have high latency. Thus, when trying to obtain a value for roomData, we should generally use the slave, while if we are trying to obtain kick information, we should use the slave. */
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
$generalCache = new fimCache($cacheConnect['servers'], $cacheConnect['driver'], $database, $slaveDatabase);

// Get Configuration Data
$config = $generalCache->getConfig();
$database->setConfig($config);

// Get Censor Data
/* Transitional note:
 * The new cache system is intended to be used with per-value querying, as opposed to loading the entire cache into memory like this every time. However, this conversion will take a while, so for now, we will go with the old way as shown below. */

$censorListsCache = $generalCache->getCensorLists();
$censorWordsCache = $generalCache->getCensorWords();




////* User Login (Requires Database) *////
require_once(dirname(__FILE__) . '/validate.php'); // This is where all the user validation stuff occurs.
?>