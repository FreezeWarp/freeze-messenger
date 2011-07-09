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



///* Prerequisites *///

require_once('config.php'); // Configuration Variables
require_once('functions/mysql.php'); // MySQL Library
require_once('functions/generalFunctions.php'); // Various Functions


//error_reporting(E_ALL ^ E_NOTICE); // Report all errors, except those of the "Notice" type.
date_default_timezone_set('GMT'); // Set the timezone to GMT.
//$errorHandlerOriginal = set_error_handler("errorHandler"); // Error Handler


$continue = true; // Simple "stop" variable used throughout for hooks and live code. (Not neccissarly best practice, but it works better than most of the alternatives.)
$hook = false;
$errStr = '';
$errDesc = '';
$templates = array();
$templateVars = array();
$config = array(); // Database configuration for the future. Plugins can fill the gaps right now, though.

define("FIM_VERSION","3.0"); // Version to be used by plugins if needed.
define("DB_BACKEND","MYSQL"); // Database backend to be used by plugins if needed; in the future other backends will be supported, and if the defined database class for whatever reason won't do, this can be used to also support others. At present, PostGreSQL is the only for-sure future backend to be supported. Definite values, if they are to be supported: "MSSQL", "ORACLE", "POSTGRESQL"
define("DB_DRIVER","MYSQL"); // Drive used for connection to the database. This may be totally useless to plugins, but again for future compatibility is included; other possible example values: "MYSQLi", "PDO" (actually, it would prolly be more useful to plugin authors of a future version wishing to support old versions)
define("FIM_LANGUAGE","EN_US"); // No plans to change this exist, but again, just in case...

$sqlPrefix = $dbConfig['vanilla']['tablePrefix']; // It's more sane this way...
$forumTablePrefix = $dbConfig['integration']['tablePreix'];

if (!isset($defaultLanguage)) { // The defaultLanguage flag was created with the WebPro interface in mind, however it's a good one for all people to have (as with the template and phrase tables). Likewise, it could even be used in the API in theory, but... meh. Anyway, even if set to anything other than en, don't expect much (so far as the WebPro interface goes).
  $defaultLanguage = 'en';
}


if ($dbConnect['core'] == $dbConnect['integration']) {
  $integrationConnect = false;
}
if ($dbConnect['core'] == $dbConnect['slave']) {
  $slaveConnect = false;
}


$database = new database;
if (!$database->connect($dbConnect['core']['host'],$dbConnect['core']['username'],$dbConnect['core']['password'],$dbConnect['core']['database'])) { // Connect to MySQL
  die('Could not connect to the database; the application has exitted.'); // Die to prevent further execution.
}

if ($integrationConnect) {
  $integrationDatabase = new database;

  if (!$database->connect($dbConnect['integration']['host'],$dbConnect['integration']['username'],$dbConnect['integration']['password'],$dbConnect['integration']['database'])) { // Connect to MySQL
    die('Could not connect to the integration database; the application has exitted.'); // Die to prevent further execution.
  }
}
else {
  $integrationDatabase = $database;
}

if ($slaveConnect) {
  $slaveDatabase = new database;

  if (!$database->connect($dbConnect['slave']['host'],$dbConnect['slave']['username'],$dbConnect['slave']['password'],$dbConnect['slave']['database'])) { // Connect to MySQL
    die('Could not connect to the slave database; the application has exitted.'); // Die to prevent further execution.
  }
}
else {
  $slaveDatabase = $database;
}


unset($dbConnect); // Security!


if ($compressOutput) { // Compress Output for transfer if configured to.
  ob_start(fim_htmlCompact);
}

require_once('validate.php'); // User Validation



///* Get Phrases *///

if (isset($reqPhrases)) {
  if ($reqPhrases === true) {
    $phrases2 = $slaveDatabase->select(
      array(
        "{$sqlPrefix}phrases" => array(
          'phraseId' => 'phraseId',
          'phraseName' => 'phraseName',
          'text_en' => 'text_en',
          'text_jp' => 'text_jp',
          'text_sp' => 'text_sp',
          'text_fr' => 'text_fr',
          'text_ge' => 'text_ge'
        ),
      )
    );
    $phrases2 = $phrases2->getAsArray('phraseId');


    // Generate the language, based on:
    // $_REQUEST[lang] -> $user[lang] -> $defaultLanguage -> 'en'
    // (c/g/p spec.)      (user spec.)   (admin spec.)       (hard coded default)
    $lang = (isset($_REQUEST['lang']) ? $_REQUEST['lang'] :
      (isset($user['lang']) ? $user['lang'] :
        (isset($defaultLanguage) ? $defaultLanguage : 'en')));

    if (isset($phrases2)) {
      if (count($phrases2) > 0) {
        foreach ($phrases2 AS $phrase) {
          $phrases[$phrase['phraseName']] = $phrase['text_' . $lang];

          if (!$phrases[$phrase['phraseName']] && $phrase['text_en']) { // If a value for the language doesn't exist, default to english.
            $phrases[$phrase['phraseName']] = $phrase['text_en'];
          }
        }

        unset($phrase);
      }
    }

    unset($phrases2);
  }
}


///* Get Code Hooks *///

if (isset($reqHooks)) {
  if ($reqHooks === true) {
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
  }
}



///* Get Templates *///

if (isset($reqPhrases)) {
  if ($reqPhrases === true) {
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
  }
}


($hook = hook('global') ? eval($hook) : '');
?>