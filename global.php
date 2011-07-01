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

define("FIM_VERSION","3.0"); // Version to be used by plugins if needed.
define("DB_BACKEND","MYSQL"); // Database backend to be used by plugins if needed; in the future other backends will be supported, and if the defined database class for whatever reason won't do, this can be used to also support others. At present, PostGreSQL is the only for-sure future backend to be supported. Definite values, if they are to be supported: "MSSQL", "ORACLE", "POSTGRESQL"
define("DB_DRIVER","MYSQL"); // Drive used for connection to the database. This may be totally useless to plugins, but again for future compatibility is included; other possible example values: "MYSQLi", "PDO" (actually, it would prolly be more useful to plugin authors of a future version wishing to support old versions)

$sqlPrefix = $dbConfig['vanilla']['tablePrefix']; // It's more sane this way...
$forumTablePrefix = $dbConfig['integration']['tablePreix'];



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

if ($reqPhrases) {
  $phrases2 = dbRows("SELECT * FROM {$sqlPrefix}phrases",'phraseId');

  $phrases2 = database->select(
    array(
      'phrases' => array(
        'phraseId' => 'phraseId',
        'name' => 'name',
        'text_en' => 'text_en',
        'text_jp' => 'text_jp',
        'text_sp' => 'text_sp',
        'text_fr' => 'text_fr',
        'text_ge' => 'text_ge'
      ),
    )
  );


  // Generate the language, based on:
  // $_REQUEST[lang] -> $user[lang] -> $defaultLanguage -> 'en'
  // (c/g/p spec.)      (user spec.)   (admin spec.)       (hard coded default)
  $lang = ($_REQUEST['lang'] ? $_REQUEST['lang'] :
    ($user['lang'] ? $user['lang'] :
      ($defaultLanguage ? $defaultLanguage : 'en')));


  if ($phrases2) {
    foreach ($phrases2 AS $phrase) {
      $phrases[$phrase['name']] = $phrase['text_' . $lang];

      if (!$phrases[$phrase['name']] && $phrase['text_en']) { // If a value for the language doesn't exist, default to english.
        $phrases[$phrase['name']] = $phrase['text_en'];
      }
    }
  }

  unset($phrases2);
  unset($phrase);
}


///* Get Code Hooks *///

if ($reqHooks) {
  $hooks2 = dbRows("SELECT * FROM {$sqlPrefix}hooks",'hookId');

  if ($hooks2) {
    foreach ($hooks2 AS $hook) {
      $hooks[$hook['name']] = $hook['code'];
    }

    unset($hook);
  }

  unset($hooks2);
}



///* Get Templates *///

if ($reqPhrases) {
  $templates2 = dbRows("SELECT * FROM {$sqlPrefix}templates",'templateId');

  if ($templates2) {
    foreach ($templates2 AS $template) {
      $templates[$template['name']] = $template['data'];
      $templateVars[$template['name']] = $template['vars'];
    }

    unset($template);
  }

  unset($templates2);
}


($hook = hook('global') ? eval($hook) : '');
?>