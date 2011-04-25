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



error_reporting(E_ALL ^ E_NOTICE);


if (file_exists('/var/www/chatinterface/htdocs/.tempStop') && $_GET['action'] != 'moderate') {
  die('The chat has been disabled by an administrator. Please remain patient while order is restored.');
}


require_once('config.php');


date_default_timezone_set('GMT'); // Set the timezone to GMT.


require_once('functions/mysql.php'); // Core MySQL Functions
require_once('functions/time.php'); // Time is a big deal, right?
require_once('functions/errorHandler.php');
require_once('functions/generalFunctions.php');


// Run Key MySQL Queries
if (!mysqlConnect($sqlHost,$sqlUser,$sqlPassword,$sqlDatabase)) {
  error_log('Could not connect to MySQL.');

  die('Could not connect');
}
else {
  error_log('MySQL connection established.');
}


if ($novalidate == true) {
  /* Do Nothing */
}
else {
  require_once('validate.php');
}


mysqlQuery('SET NAMES UTF8');


/*** Get Phrases ***/

$phrases2 = sqlArr("SELECT * FROM {$sqlPrefix}phrases",'id');

if ($_GET['lang']) {
  $lang = $_GET['lang'];
}
elseif (!$lang) {
  $lang = 'en';
}

foreach ($phrases2 AS $phrase) {
  $phrases[$phrase['name']] = $phrase['text_' . $lang];
}

unset($phrases2);
unset($phrase);


/*** Get Code Hooks ***/

$hooks2 = sqlArr("SELECT * FROM {$sqlPrefix}hooks",'id');
foreach ($hooks2 AS $hook) {
  $hooks[$hook['name']] = $hook['code'];
}

unset($hooks2);
unset($hook);
?>