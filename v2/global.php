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
  die('VRIM has been disabled by an administrator. Please remain patient while order is restored.');
}

/* MySQL Login */
$sqlHost = '10.10.10.1';
$sqlUser = 'vrim10';
$sqlPassword = 'WURZNKpHSfwzpyp7';
$sqlDatabase = 'vbulletin';
$sqlPrefix = 'vrc_'; // The Prefix of all MySQL Tables, excluding those of vBulletin.
$sqlUserTable = 'user';
$sqlUserIdCol = 'userid';
$sqlUsernameCol = 'username';
$sqlUsergroupCol = 'displaygroupid';


/* Security Data */
$salt = 'Fr33d0m*'; // Security pass. This is used for encypting data.


/* Other Configuration Data */
$loginMethod = 'vbulletin';
$bannedUserGroups = array(8);
$messageLimit = 40;
$onlineThreshold = 15;
$enableDF = array(
  'colour' => true,
  'font' => true,
  'highlight' => true,
  'general' => true, // Bold, italics, etc.
);
$allowRoomCreation = true; // Use this to disable user room creation.
$hideRoomsOnline = true; // Use this to hide the names of rooms users are in to users who can't access said roms.
$bbcode = array(
  'shortCode' => true,
  'buis' => true,
  'link' => true,
  'colour' => true,
  'image' => true,
  'video' => true,
  'emoticon' => true,
);
$encrypt = true; // Disabling encyption is not recommended, but does have its advantages. This setting can be freely changed.
$parseFlags = true; // Messages sent under certain conditions will contain flags corrosponding to certain message data, like "video". Using this paramater, these messages will only contain the specific parameter and not the extra BBcode. This can be useful for certain APIs, data cleanliness, and so-on, but can also mean extra CPU cycles and incompatibility with older software. *DO NOT CHANGE THIS SETTING AFTER INITIAL SETUP*
$salts = array(
  101 => 'Fr33d0m*',
); // DO NOT REMOVE ANY ENTRY. Entries can be freely added, with the last generally being used for all new data.

/* Other Stuffz */
//ob_start(''); // Start the content buffer.
date_default_timezone_set('GMT'); // Set the timezone to GMT.


require_once('functions/mysql.php'); // Core MySQL Functions
require_once('functions/time.php'); // Timevictoryroad.net is a big deal, right?
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

if ($novalidate == true) {}
else require_once('validate.php');

mysqlQuery('SET NAMES UTF8');
?>
