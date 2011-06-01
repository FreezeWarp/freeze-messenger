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



require_once('config.php'); // Configuration Variables
require_once('functions/mysql.php');
require_once('functions/generalFunctions.php');


error_reporting(E_ALL ^ E_NOTICE);
date_default_timezone_set('GMT'); // Set the timezone to GMT.
$errorHandlerOriginal = set_error_handler("errorHandler"); // Error Handler



// Connect to MySQL
if (!mysqlConnect($sqlHost,$sqlUser,$sqlPassword,$sqlDatabase)) {
  die('Could not connect');
}




if ($novalidate == true) {
  /* Do Nothing */
}
else {
  require_once('validate.php');
}



//mysqlQuery('SET NAMES UTF8');


/*** Get Phrases ***/

if ($reqPhrases) {
  $phrases2 = sqlArr("SELECT * FROM {$sqlPrefix}phrases",'id');

  if ($_GET['lang']) {
    $lang = $_GET['lang'];
  }
  elseif (!$lang) {
    $lang = 'en';
  }

  if ($phrases2) {
    foreach ($phrases2 AS $phrase) {
      $phrases[$phrase['name']] = $phrase['text_' . $lang];

      if (!$phrases[$phrase['name']] && $phrase['text_en']) {
        $phrases[$phrase['name']] = $phrase['text_en'];
      }
    }
  }

  unset($phrases2);
  unset($phrase);
}


/*** Get Code Hooks ***/
if ($reqHooks) {
  $hooks2 = sqlArr("SELECT * FROM {$sqlPrefix}hooks",'id');

  if ($hooks2) {
    foreach ($hooks2 AS $hook) {
      $hooks[$hook['name']] = $hook['code'];
    }
  }

  unset($hooks2);
  unset($hook);
}

/*** Get Code Hooks ***/
if ($reqPhrases) {
  $templates2 = sqlArr("SELECT * FROM {$sqlPrefix}templates",'id');

  foreach ($templates2 AS $template) {
    $templates[$template['name']] = $template['data'];
    $templateVars[$template['name']] = $template['vars'];
  }

  unset($templates2);
  unset($template);
}

?>