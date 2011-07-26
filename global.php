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



///* PREREQUISITES *///

/* Default Configuration Settings
 * These are the simplified defaults of the configuration directives stored in the database.
 * Unlike the standard database defaults, these only include the barebones data (e.g. searchWord directives). */
$defaultConfig = array(
  'defaultMessageHardLimit' => 50,
  'defaultMessageLimit' => 10000,
  'defaultOnlineThreshold' => 15,
  'fullTextArchive' => false,
  'searchWordMinimum' => 4,
  'searchWordMaximum' => 10,
  'searchWordOmissions' => array(),
  'searchWordPunctuation' => array(),
  'searchWordConvertsFind' => array(),
  'searchWordConvertsReplace' => array(),
  'cacheKicksRefresh' => 60,
  'cacheKicks' => false,
  'cachePhrasesRefresh' => 3600,
  'cacheTemplatesRefresh' => 3600,
  'cacheConfigRefresh' => 3600,
  'longPollingWait' => 2,
  'longPolling' => false,
  'serverSentEventsWait' => .5, // Server sent events are more controlled, so we can call them at a greater frequency.
  'serverSentEvents' => false,
);



/* Make Sure Required PHP Extensions are Present
 * Note that:
 * MySQL is present in all versions since PHP 4
 * JSON is present in all versions of PHP since 5.2 (but is not actually used in FIMv3)
 * MBString is present in all versions of PHP since 4.3
 * MCrypt is present in all versions since PHP 4
 * Hash is present in all versions since PHP 5.1.2
 * PCRE is present in all versions since PHP 4
 */
foreach (array('mysql','json','mbstring','mcrypt','hash','pcre') AS $module) {
  if (!extension_loaded($module)) {
    die("The module $module could not be found or loaded (loading is disabled). Please install PHP $module compatibility. See the documetation for help.");
  }
}



/* Define Void Functions if APC is not present
 * APC is used for caching many directives, but is generally not required.
 * It will be included by default in PHP 5.4, and is already easy to install in a number of Linux repositories (e.g. Ubuntu = php-apc). */
if (!extension_loaded('apc')) { // APC is strongly recommended, but at least in version 3 not required. Instead, we simply use wrappers that do nothing.
  function apc_fetch() {
    return false;
  }

  function apc_store() {
    return false;
  }
}



/* Version Requirement, Magic Quotes, Display Errors and Register Globals */
ini_set('display_errors',0); // Ideally we would never have to worry about this, but sadly that's not the case. FIMv4 will hopefully make improvements.

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

  if (ini_get('register_globals') === true) { // Note: This can not be altered with ini_set, so... we won't even bother. We will remove it in the next version most likely, as no one really uses it anyway.
    die('Register Globals is enabled. Please disable register_globals in php.ini. More information can be found in the <a href="http://www.php.net/manual/en/security.globals.php">PHP manual</a>, and in the documentation.');
  }
}



/* Require Libraries */
require(dirname(__FILE__) . '/config.php'); // Configuration Variables
require(dirname(__FILE__) . '/functions/mysql.php'); // MySQL Library (DEPRECATED)
require(dirname(__FILE__) . '/functions/mysqlOOP.php'); // MySQL OOP Library
require(dirname(__FILE__) . '/functions/commonQueries.php'); // FIM-specific Extension to MySQL OOP Library
require(dirname(__FILE__) . '/functions/generalFunctions.php'); // Various Functions



/* Other Stuff */
if (isset($apiRequest)) {
  if ($apiRequest === true) {
    sleep((isset($config['apiPause']) ? $config['apiPause'] : .125)); // This prevents flooding the server/DoSing. It's included since I've done it to myself during development...
  }
}



/* Blanket Defaults */
date_default_timezone_set('GMT'); // Set the timezone to GMT.

$continue = true; // Simple "stop" variable used throughout for hooks and live code. (Not neccissarly best practice, but it works better than most of the alternatives.)
$hook = false;
$errStr = '';
$errDesc = '';




/* Constants
 * These are most beneficial to third-party plugins. */
define("FIM_VERSION","3.0"); // Version to be used by plugins if needed.
define("FIM_LANGUAGE","EN_US"); // No plans to change this exist, but again, just in case...
define("FIMDB_BACKEND","MYSQL"); // Database backend to be used by plugins if needed; in the future other backends will be supported, and if the defined database class for whatever reason won't do, this can be used to also support others. At present, PostGreSQL is the only for-sure future backend to be supported. Definite values, if they are to be supported: "MSSQL", "ORACLE", "POSTGRESQL"
define("FIMDB_DRIVER","MYSQL"); // Drive used for connection to the database. This may be totally useless to plugins, but again for future compatibility is included; other possible example values: "MYSQLi", "PDO" (actually, it would prolly be more useful to plugin authors of a future version wishing to support old versions)




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





///* Database Stuff *///

/* If the connections are the same, do not make multiple below. */
if ($dbConnect['core'] == $dbConnect['integration']) {
  $integrationConnect = false;
}
else {
  $integrationConnect = true;
}

if ($dbConnect['core'] == $dbConnect['slave']) {
  $slaveConnect = false;
}
else {
  $slaveConnect = true;
}


/* Connect to the Main Database */
$database = new fimDatabase;
if (!$database->connect($dbConnect['core']['host'],$dbConnect['core']['username'],$dbConnect['core']['password'],$dbConnect['core']['database'])) {
  die('Could not connect to the database: ' . $database->error . '; the application has exitted.'); // Die to prevent further execution.
}


/* Connect to the Integration DB
 * On the whole, the product was designed such that all tables are in one database, but for the advanced users out there... */
if ($integrationConnect) {
  $integrationDatabase = new fimDatabase;

  if (!$database->connect($dbConnect['integration']['host'],$dbConnect['integration']['username'],$dbConnect['integration']['password'],$dbConnect['integration']['database'])) { // Connect to MySQL
    die('Could not connect to the integration database: ' . $database->error . '; the application has exitted.');
  }
}
else {
  $integrationDatabase = $database;
}


/* Connect to the DB Slave
 * Unfortunately, this can not be reliably used in v3. It will more of a focus in the future.
 * Still, if you do use it, it can ease load. */
if ($slaveConnect) {
  $slaveDatabase = new fimDatabase;

  if (!$database->connect($dbConnect['slave']['host'],$dbConnect['slave']['username'],$dbConnect['slave']['password'],$dbConnect['slave']['database'])) { // Connect to MySQL
    die('Could not connect to the slave database: ' . $database->error . '; the application has exitted.');
  }
}
else {
  $slaveDatabase = $database;
}



unset($dbConnect); // There is no reason the login credentials should still be active. A variety of exploits could take advantage of this otherwise -- buffer overflow, issues in plugins, templates, and so-fourth. If anyone knows about the vBulletin 3.8.6 mess... you know what I'm talking about.




///* USER LOGIN (REQUIRES DATABASE) *///

require_once(dirname(__FILE__) . '/validate.php'); // This is where all the user validation stuff occurs.





///* OTHER STUFF *///


if (isset($banned,$apiRequest)) { // A blanket die for the API when the user is banned.
  if ($apiRequest == true && $banned == true) {
    die();
  }
}

if (isset($compressOutput,$apiRequest)) { // Compress Output for transfer if configured to, and if we are outputting data from the API (file downloads, interfaces, etc. don't apply).
  if ($apiRequest == true && $compressOutput == true) {
//    ob_start('fim_htmlCompact');
  }
}




///* GET DATABASE-STORED CONFIGURATION *///
if (!$config = apc_fetch('fim_config') || $disableConfig) {
  $config2 = $slaveDatabase->select(
    array(
      "{$sqlPrefix}configuration" => array(
        'directive' => 'directive',
        'value' => 'value',
        'type' => 'type',
      ),
    )
  );
  $config2 = $config2->getAsArray(true);

  if (is_array($config2)) {
    if (count($config2) > 0) {
      foreach ($config2 AS $config3) {
        switch ($config3['type']) {
          case 'int':
          $config[$config3['directive']] = (int) $config3['value'];
          break;

          case 'string':
          $config[$config3['directive']] = (string) $config3['value'];
          break;

          case 'array':
          $config[$config3['directive']] = (array) fim_explodeEscaped(',',$config3['value']);
          break;

          case 'bool':
          if (in_array($config3['value'],array('true','1',true,1),true)) { // We include the non-string counterparts here on the off-chance the database driver supports returning non-strings. The third parameter in the in_array makes it a strict comparison.
            $config[$config3['directive']] = true;
          }
          else {
            $config[$config3['directive']] = false;
          }
          break;

          case 'float':
          $config[$config3['directive']] = (float) $config3['value'];
          break;
        }
      }

      unset($config3);
    }
  }

  unset($config2);

  foreach ($defaultConfig AS $key => $value) {
    if (!isset($config[$key])) {
      $config[$key] = $value;
    }
  }

  $config['searchWordConverts'] = array_combine($config['searchWordConvertsFind'],$config['searchWordConvertsReplace']);

  apc_store('fim_config',$config,$config['cacheConfigRefresh']);
}


///* GET PHRASES *///

if (isset($reqPhrases)) { // TODO Languages currently overwrite eachother
  if ($reqPhrases === true) {
    if (!$phrases = apc_fetch('fim_phrases')) {
      $lang = (isset($_REQUEST['lang']) ? $_REQUEST['lang'] :
        (isset($user['lang']) ? $user['lang'] :
          (isset($defaultLanguage) ? $defaultLanguage : 'en')));

      $phrases2 = $slaveDatabase->select(
        array(
          "{$sqlPrefix}phrases" => array(
            'phraseName' => 'phraseName',
            'languageCode' => 'languageCode',
            'text' => 'text',
          ),
        ),
        array(
          'both' => array(
            array(
              'left' => array(
                'type' => 'column',
                'value' => 'languageCode',
              ),
              'right' => array(
                'type' => 'string',
                'value' => $lang,
              ),
              'type' => 'e',
            ),
          ),
        )
      );
      $phrases2 = $phrases2->getAsArray(true);

      if (is_array($phrases2)) {
        if (count($phrases2) > 0) {
          foreach ($phrases2 AS $phrase) {
            $phrases[$phrase['phraseName']] = $phrase['text'];

            if (!$phrases[$phrase['phraseName']] && $phrase['text']) { // If a value for the language doesn't exist, default to english.
              $phrases[$phrase['phraseName']] = $phrase['text'];
            }
          }

          unset($phrase);
        }
      }

      unset($phrases2);

      apc_store('fim_phrases',$phrases,$config['cachePhrasesRefresh']);
    }
  }
}




///* GET CODE HOOKS *///

if (isset($reqHooks)) {
  if ($reqHooks === true) {
    if (!$hooks = apc_fetch('fim_hooks')) {
      $hooks2 = $slaveDatabase->select(
        array(
          "{$sqlPrefix}hooks" => array(
            'hookId' => 'hookId',
            'hookName' => 'hookName',
            'code' => 'code',
          ),
        )
      );
      $hooks2 = $hooks2->getAsArray('hookId');


      if (is_array($hooks2)) {
        if (count($hooks2) > 0) {
          foreach ($hooks2 AS $hook) {
            $hooks[$hook['hookName']] = $hook['code'];
          }

          unset($hook);
        }
      }

      unset($hooks2);
      apc_store('fim_hooks',$hooks,$config['cacheHooksRefresh']);
    }
  }
}





///* GET TEMPLATES *///

if (isset($reqPhrases)) {
  if ($reqPhrases === true) {
    $templates = apc_fetch('fim_templates');
    $templateVars = apc_fetch('fim_templateVars');

    if (!$templates || !$templateVars) {
      $templates = array();
      $templateVars = array();

      $templates2 = $slaveDatabase->select(
        array(
          "{$sqlPrefix}templates" => array(
            'templateId' => 'templateId',
            'templateName' => 'templateName',
            'vars' => 'vars',
            'data' => 'data',
          ),
        )
      );
      $templates2 = $templates2->getAsArray('templateId');

      if (is_array($templates2)) {
        if (count($templates2) > 0) {
          foreach ($templates2 AS $template) {
            $templates[$template['templateName']] = $template['data'];
            $templateVars[$template['templateName']] = $template['vars'];
          }

          unset($template);
        }
      }

      unset($templates2);
      apc_store('fim_templates',$templates,$config['cacheTemplatesRefresh']);
      apc_store('fim_templateVars',$templateVars,$config['cacheTemplatesRefresh']);
    }
  }
}



///* CACHED DIRECTIVES (REQUIRES APC) *///

if ($config['cacheKicks']) {
  $cacheKicks = apc_fetch('fim_kickCache');

  if ($cacheKicks === null || $cacheKicks === false) {
    $cacheKicks = array();

    $queryParts['cacheKicksSelect']['columns'] = array(
      "{$sqlPrefix}kicks" => array(
        'kickerId' => 'kkickerId',
        'userId' => 'kuserId',
        'roomId' => 'kroomId',
        'length' => 'klength',
        'time' => array(
          'name' => 'ktime',
          'context' => 'time',
        ),
      ),
      "{$sqlPrefix}users user" => array(
        'userId' => 'userId',
        'userName' => 'userName',
        'userFormatStart' => 'userFormatStart',
        'userFormatEnd' => 'userFormatEnd',
      ),
      "{$sqlPrefix}users kicker" => array(
        'userId' => 'kickerId',
        'userName' => 'kickerName',
        'userFormatStart' => 'kickerFormatStart',
        'userFormatEnd' => 'kickerFormatEnd',
      ),
      "{$sqlPrefix}rooms" => array(
        'roomId' => 'roomId',
        'roomName' => 'roomName',
      ),
    );
    $queryParts['cacheKicksSelect']['conditions'] = array(
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
    $queryParts['cacheKicksSelect']['sort'] = array(
      'roomId' => 'asc',
      'userId' => 'asc'
    );

    $cacheKicksPre = $database->select($queryParts['cacheKicksSelect']['columns'],
      $queryParts['cacheKicksSelect']['conditions'],
      $queryParts['cacheKicksSelect']['sort']);
    $cacheKicksPre = $cacheKicksPre->getAsArray(true);

    $cacheKicks = array();

    foreach ($cacheKicksPre AS $cacheKick) {
      if ($cacheKick['ktime'] + $cacheKick['klength'] < time()) {
        $database->delete("{$sqlPrefix}kicks",array(
          'userId' => $cacheKick['userId'],
          'roomId' => $cacheKick['roomId'],
        ));
      }
      else {
        $cacheKicks[$cacheKick['roomId']][$cacheKick['userId']] = true;
      }
    }

    apc_add('fim_kickCache',$cacheKicks,$config['cacheKicksRefresh']);
  }
}



($hook = hook('global') ? eval($hook) : '');
?>