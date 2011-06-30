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


$continue = true; // Simple "stop" variable used throughout for hooks.
define("FIM_VERSION","3.0"); // Version to be used by plugins if needed.


if (!dbConnect($sqlHost,$sqlUser,$sqlPassword,$sqlDatabase)) { // Connect to MySQL
  die('Could not connect to the database; the application has exitted.'); // Die to prevent furthr execution.
}

unset($sqlPassword); // Security!


if ($compressOutput) { // Compress Output for transfer if configured to.
  ob_start(fim_htmlCompact);
}

require_once('validate.php'); // User Validation



///* Get Phrases *///

if ($reqPhrases) {
  $phrases2 = dbRows("SELECT * FROM {$sqlPrefix}phrases",'id');


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
  $hooks2 = dbRows("SELECT * FROM {$sqlPrefix}hooks",'id');

  if ($hooks2) {
    foreach ($hooks2 AS $hook) {
      $hooks[$hook['name']] = $hook['code'];
    }
  }

  unset($hooks2);
  unset($hook);
}



///* Get Templates *///

if ($reqPhrases) {
  $templates2 = dbRows("SELECT * FROM {$sqlPrefix}templates",'id');

  foreach ($templates2 AS $template) {
    $templates[$template['name']] = $template['data'];
    $templateVars[$template['name']] = $template['vars'];
  }

  unset($templates2);
  unset($template);
}


($hook = hook('global') ? eval($hook) : '');
?>