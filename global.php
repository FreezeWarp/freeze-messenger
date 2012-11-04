<?php
/* FreezeMessenger Copyright © 2011 Joseph Todd Parsons

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
 * MBString is present in all versions of PHP since 4.3
 * MCrypt is present in all versions since PHP 4
 * PCRE is present in all versions since PHP 4
 * APC is present in PHP 5.4, Ubuntu's php-apc packge, and easily installed from PECL.net. It simply has to be a requirement for many of the functions (as far as the way they are designed).
 * DOM is in all of PHP 5.
 *
 * The following are used, but with safe fallbacks:
 * Hash is present in all versions since PHP 5.1.2; MHash is present in all versions since PHP4
 */
foreach (array('mysql', 'json', 'mbstring', 'mcrypt', 'pcre', 'dom', 'curl') AS $module) { // Check that each extension has been loaded.
  if (!extension_loaded($module)) die("The module <strong>$module</strong> could not be found. Please install PHP <strong>$module</strong> compatibility. See the documentation for help.");
}

if (!extension_loaded('apc')/* && !extension_loaded('memcache')*/) die("Neither the <strong>apc</strong> or <strong>memcache</strong> modules could not be found. Please install PHP <strong>apc</strong> or <strong>memcache</strong> compatibility. See the documentation for help."); // APC is required. Memcached is not yet supported.
if (!extension_loaded('hash') && !extension_loaded('mhash')) die("Neither the <strong>hash</strong> or <strong>mhash</strong> modules could not be found. Please install PHP <strong>hash</strong> or <strong>mhash</strong> compatibility. See the documentation for help."); // Either can be used.

if ((bool) ini_get('allow_url_fopen') === false) {
  die('FOpen functionality is disable. Please enable allow_url_fopen in php.ini. More information can be found in the <a href="http://www.php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen">PHP manual</a>, and in the documentation.');
}



/* Version Requirement, Magic Quotes, Display Errors and Register Globals */

ini_set('display_errors', 0); // Ideally we would never have to worry about this, but sadly that's not the case. FIMv4 will hopefully make improvements.


if (floatval(PHP_VERSION) < 5.2) { // We won't bother supporting older PHP; too much hassle. We will also raise this to 5.3 in the next version.
  die('The installed version of PHP is out of date. Only PHP versions 5.2 and above are supported. Contact your server host for more information if possible.');
}
elseif (floatval(PHP_VERSION) <= 5.3) { // Removed outright in 5.4, may as well save a CPU cycle or two.
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
require(dirname(__FILE__) . '/functions/database.php'); // MySQL OOP Library
require(dirname(__FILE__) . '/functions/fim_database.php'); // FIM-specific Extension to MySQL OOP Library
require(dirname(__FILE__) . '/functions/fim_cache.php'); // APC Wrapper (may use for alteratives like memcached later)
require(dirname(__FILE__) . '/functions/fim_general.php'); // Various Functions
require(dirname(__FILE__) . '/functions/fim_parser.php'); // Message parser



/* Blanket Defaults */
$continue = true; // Simple "stop" variable used throughout for hooks and live code. (Not neccissarly best practice, but it works better than most of the alternatives.)
$hook = false;
$errStr = '';
$errDesc = '';



/* Constants
 * These are mostly beneficial to third-party plugins. */
define("FIM_VERSION", "3.0"); // Version to be used by plugins if needed.
define("FIM_LANGUAGE", "EN_US"); // No plans to change this exist, but again, just in case...




/* Legacy Code
 * I was too laxy to rewrite stuff. */
$sqlPrefix = $dbConfig['vanilla']['tablePrefix']; // It's more sane this way...
$forumTablePrefix = $dbConfig['integration']['tablePreix'];




/* Language
 * The defaultLanguage flag was created with the WebPro interface in mind, however it's a good one for all people to have (as with the template and phrase tables). Likewise, it could even be used in the API in theory, but... meh. Anyway, even if set to anything other than en, don't expect much (so far as the WebPro interface goes).
 * Sadly, the entire language backend more or less is broken in FIMv3. */
if (!isset($defaultLanguage)) {
  $defaultLanguage = 'en';
}



/* API Mode
 * Determine if we are in the API or not.
 * If we are, we disable things like the error handler.
 * In rare cases (like validate.php, where it can act both as an API and as a part of the core system), this is already defined and will be left alone. Otherwise, API files should set $apiRequest to true, and it will be converted to $api here. */
if (!isset($api)) {
  if (isset($apiRequest)) $api = (bool) $apiRequest;
  else $api = false;
}



/* Better Error Handling and Output Buffering */
set_error_handler('fim_errorHandler'); // Defined in fim_general.php
set_exception_handler('fim_exceptionHandler'); // Defined in fim_general.php



////* Database Stuff *////

/* If the connections are the same, do not make multiple below. */
if ($dbConnect['core'] == $dbConnect['integration']) $integrationConnect = false;
else $integrationConnect = true;

if ($dbConnect['core'] == $dbConnect['slave']) $slaveConnect = false;
else $slaveConnect = true;


/* Connect to the Main Database */
$database = new fimDatabase;
if (!$database->connect($dbConnect['core']['host'], $dbConnect['core']['port'], $dbConnect['core']['username'], $dbConnect['core']['password'], $dbConnect['core']['database'], $dbConnect['core']['driver'])) {
  die('Could not connect to the database: ' . $database->error . '; the application has exitted.'); // Die to prevent further execution.
}


/* Connect to the Integration DB
 * On the whole, the product was designed such that all tables are in one database, but for the advanced users out there... */
if ($integrationConnect) {
  $integrationDatabase = new fimDatabase;

  if (!$database->connect($dbConnect['integration']['host'], $dbConnect['integration']['port'], $dbConnect['integration']['username'], $dbConnect['integration']['password'], $dbConnect['integration']['database'], $dbConnect['integration']['driver'])) { // Connect to MySQL
    die('Could not connect to the integration database: ' . $database->error . '; the application has exitted.');
  }
}
else {
  $integrationDatabase = $database;
}


/* Connect to the DB Slave
 * Unfortunately, this can not be reliably used in v3. It will be more of a focus in the future.
 * Still, if you do use it, it can ease load. */
if ($slaveConnect) {
  $slaveDatabase = new fimDatabase;

  if (!$database->connect($dbConnect['slave']['host'], $dbConnect['slave']['port'], $dbConnect['slave']['username'], $dbConnect['slave']['password'], $dbConnect['slave']['database'], $dbConnect['slave']['driver'])) { // Connect to MySQL
    die('Could not connect to the slave database: ' . $database->error . '; the application has exitted.');
  }
}
else {
  $slaveDatabase = $database;
}



unset($dbConnect); // There is no reason the login credentials should still be active. A variety of exploits could take advantage of this otherwise -- buffer overflow, issues in plugins, templates, and so-fourth. If anyone knows about the vBulletin 3.8.6 mess... you know what I'm talking about. (Yes, this program is that old. So... many... betas.)



////* Connect to Cache *////

$generalCache = new generalCache($cacheConnect['driver'], $cacheConnect['servers']);



////* Get Database-Stored Configuration *////

if (!($config = $generalCache->get('fim_config')) || $disableConfig) { // Disable config is a trick I picked up from vBulletin. It makes it possible to disable the database-stored configuration if something goes horribly wrong.
  require(dirname(__FILE__) . '/defaultConfig.php');

  if (!$disableConfig) {
    $config2 = $slaveDatabase->select(
      array(
        "{$sqlPrefix}configuration" => 'directive, value, type',
      )
    );
    $config2 = $config2->getAsArray(true);

    if (is_array($config2)) {
      if (count($config2) > 0) {
        foreach ($config2 AS $config3) {
          switch ($config3['type']) {
            case 'int':    $config[$config3['directive']] = (int) $config3['value']; break;
            case 'string': $config[$config3['directive']] = (string) $config3['value']; break;
            case 'array':  $config[$config3['directive']] = (array) fim_explodeEscaped(',',$config3['value']); break;
            case 'bool':
            if (in_array($config3['value'],array('true','1',true,1),true)) $config[$config3['directive']] = true; // We include the non-string counterparts here on the off-chance the database driver supports returning non-strings. The third parameter in the in_array makes it a strict comparison.
            else $config[$config3['directive']] = false;
            break;
            case 'float':  $config[$config3['directive']] = (float) $config3['value']; break;
          }
        }

        unset($config3);
      }
    }

    unset($config2);
  }

  foreach ($defaultConfig AS $key => $value) {
    if (!isset($config[$key])) {
      $config[$key] = $value;
    }
  }


  $generalCache->set('fim_config', $config, $config['configCacheRefresh']);
}



////* Things That Require Config *////

if ($api === true) {
  sleep($config['apiPause']); // This prevents flooding the server/DoSing. It's included since I've done it to myself during development...
}
else {
  sleep($config['pause']);
}



////* Get Code Hooks *////

if (isset($reqHooks)) {
  if ($reqHooks === true) {
    if (!$hooks = $generalCache->get('fim_hooksCache')) {
      $hooks2 = $slaveDatabase->select(
        array(
          "{$sqlPrefix}hooks" => 'hookId, hookName, code',
        )
      );
      $hooks2 = $hooks2->getAsArray('hookId');


      if (is_array($hooks2)) {
        if (count($hooks2) > 0) {
          foreach ($hooks2 AS $hook) $hooks[$hook['hookName']] = $hook['code'];
          unset($hook);
        }
      }

      unset($hooks2);
      $generalCache->set('fim_hooksCache', $hooks, $config['hooksCacheRefresh']);
    }
  }
}



////* Kicks Cache *////
////* Caches Entire Table as kicks[roomId][userId] = true *////

$kicksCache = $generalCache->get('fim_kicksCache');

if ($kicksCache === null || $kicksCache === false) {
  $kicksCache = array();

  $queryParts['kicksCacheSelect']['columns'] = array(
    "{$sqlPrefix}kicks" => 'kickerId kkickerId, userId kuserId, roomId kroomId, length klength, time ktime',
    "{$sqlPrefix}users user" => 'userId, userName, userFormatStart, userFormatEnd',
    "{$sqlPrefix}users kicker" => 'userId kickerId, userName kickerName, userFormatStart kickerFormatStart, userFormatEnd kickerFormatEnd',
    "{$sqlPrefix}rooms" => 'roomId, roomName',
  );
  $queryParts['kicksCacheSelect']['conditions'] = array(
    'both' => array(
      array(
        'type' => 'e',
        'left' => array(
          'type' => 'column',
          'value' => 'kuserId',
        ),
        'right' => array(
          'type' => 'column',
          'value' => 'userId',
        ),
      ),
      array(
        'type' => 'e',
        'left' => array(
          'type' => 'column',
          'value' => 'kroomId',
        ),
        'right' => array(
          'type' => 'column',
          'value' => 'roomId',
        ),
      ),
      array(
        'type' => 'e',
        'left' => array(
          'type' => 'column',
          'value' => 'kkickerId',
        ),
        'right' => array(
          'type' => 'column',
          'value' => 'kickerId',
        ),
      ),
    ),
  );
  $queryParts['kicksCacheSelect']['sort'] = array(
    'roomId' => 'asc',
    'userId' => 'asc'
  );

  $kickCachesPre = $database->select($queryParts['kicksCacheSelect']['columns'],
    $queryParts['kicksCacheSelect']['conditions'],
    $queryParts['kicksCacheSelect']['sort']);
  $kickCachesPre = $kickCachesPre->getAsArray(true);

  $kicksCache = array();

  foreach ($kickCachesPre AS $kickCache) {
    if ($kickCache['ktime'] + $kickCache['klength'] < time()) { // Automatically delete old entires when cache is regenerated.
      $database->delete("{$sqlPrefix}kicks",array(
        'userId' => $kickCache['userId'],
        'roomId' => $kickCache['roomId'],
      ));
    }
    else {
      $kicksCache['byRoomId'][$kickCache['roomId']][$kickCache['userId']] = true;
    }
  }

  $generalCache->set('fim_kicksCache', $kicksCache, $config['kicksCacheRefresh']);
}



////* Permissions Cache *////
////* Caches Entire Table as roomPermissions[roomId] = [roomId, attribute, param, permissions] *////

$permissionsCache = $generalCache->get('fim_permissionsCache');

if ($permissionsCache === null || $permissionsCache === false) {
  $permissionsCache = array();

  $queryParts['permissionsCacheSelect']['columns'] = array(
    "{$sqlPrefix}roomPermissions" => 'roomId, attribute, param, permissions',
  );

  $permissionsCachePre = $database->select($queryParts['permissionsCacheSelect']['columns']);
  $permissionsCachePre = $permissionsCachePre->getAsArray(true);

  foreach ($permissionsCachePre AS $cachePerm) {
    $permissionsCache['byRoomId'][$cachePerm['roomId']][$cachePerm['attribute']][$cachePerm['param']] = $cachePerm['permissions'];
  }

  $generalCache->set('fim_permissionCache', $permissionsCache, $config['permissionsCacheRefresh']);
}



////* Watch Rooms *////
////* Caches Entire Table as watchRooms[userId] = [roomId, userId] *////

$watchRoomsCache = $generalCache->get('fim_watchRoomsCache');

if ($watchRoomsCache === null || $watchRoomsCache === false) {
  $watchRoomsCache = array();

  $queryParts['watchRoomsCacheSelect']['columns'] = array(
    "{$sqlPrefix}watchRooms" => 'roomId, userId',
  );

  $watchRoomsCachePre = $database->select($queryParts['watchRoomsCacheSelect']['columns']);
  $watchRoomsCachePre = $watchRoomsCachePre->getAsArray(true);

  foreach ($watchRoomsCachePre AS $cachePerm) {
    if (!isset($watchRoomsCache['byUserId'][$cachePerm['userId']])) $watchRoomsCache['byUserId'][$cachePerm['userId']] = array();

    $watchRoomsCache['byUserId'][$cachePerm['userId']][] = $cachePerm['roomId'];
  }

  $generalCache->set('fim_watchRoomsCache', $watchRoomsCache, $config['watchRoomsCacheRefresh']);
}



////* Censor Lists *////
////* Caches Entire Table as censorLists[listId] = [listId, listName, listType, options] *////

$censorListsCache = $generalCache->get('fim_censorListsCache');

if ($censorListsCache === null || $censorListsCache === false) {
  $censorListsCache = array('byListId' => array());

  $queryParts['censorListsCacheSelect']['columns'] = array(
    "{$sqlPrefix}censorLists" => 'listId, listName, listType, options',
  );

  $censorListsCachePre = $database->select($queryParts['censorListsCacheSelect']['columns']);
  $censorListsCachePre = $censorListsCachePre->getAsArray(true);

  foreach ($censorListsCachePre AS $cachePerm) {
    $censorListsCache['byListId'][$cachePerm['listId']] = $cachePerm;
  }


  $generalCache->set('fim_censorListsCache', $censorListsCache, $config['censorListsCache']);
}



////* Censor Words *////
////* Caches Entire Table as censorWords[word] = [listId, word, severity, param] *////

$censorWordsCache = $generalCache->get('fim_censorWordsCache');

if ($censorWordsCache === null || $censorWordsCache === false) {
  $censorWordsCache = array('byWord' => array());

  $queryParts['censorWordsCacheSelect']['columns'] = array(
    "{$sqlPrefix}censorWords" => 'listId, word, severity, param',
  );

  $censorWordsCachePre = $database->select($queryParts['censorWordsCacheSelect']['columns']);
  $censorWordsCachePre = $censorWordsCachePre->getAsArray(true);

  foreach ($censorWordsCachePre AS $cachePerm) {
    $censorWordsCache['byWord'][$cachePerm['word']] = $cachePerm;
  }

  $generalCache->set('fim_censorWordsCache', $censorWordsCache, $config['censorWordsCacheRefresh']);
}



////* Room List Names Cache *////
////* Caches Entire Table as roomListNames[[listName, listId]] = [listId, listName] *////

$roomListNamesCache = $generalCache->get('fim_roomListNamesCache');

if ($roomListNamesCache === null || $roomListNamesCache === false) {
  $roomListNamesCache = array('byListName' => array(), 'byListId' => array());

  $queryParts['roomListNamesCacheSelect']['columns'] = array(
    "{$sqlPrefix}roomListNames" => 'listId, listName',
  );

  $roomListNamesCachePre = $database->select($queryParts['roomListNamesCacheSelect']['columns']);
  $roomListNamesCachePre = $roomListNamesCachePre->getAsArray(true);

  foreach ($roomListNamesCachePre AS $cachePerm) {
    $roomListNamesCache['byListId'][$cachePerm['listId']] = $cachePerm;
    $roomListNamesCache['byListName'][$cachePerm['listName']] = $cachePerm;
  }

  $generalCache->set('fim_roomListNamesCache', $roomListNamesCache, $config['roomListNamesCacheRefresh']);
}





////* User Login (Requires Database) *////

require_once(dirname(__FILE__) . '/validate.php'); // This is where all the user validation stuff occurs.



////* Global Hook *////

($hook = hook('global') ? eval($hook) : '');




////* Other Stuff *////

if (defined('FIM_LOGINRUN')) {
  if ($api && $banned) {
    throw new Exception('The user has been banned.'); // This isn't technically an exception, but the API is designed to throw all quit errors with the exception schema.
  }
}

if ($api && $config['compressOutput']) {
  ob_start('fim_apiCompact');
}
?>