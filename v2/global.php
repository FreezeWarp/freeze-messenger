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
$loginMethod = 'vbulletin'; // The product used for login. Currently only "vbulletin3.8" is supported.

$sqlHost = '10.10.10.1'; // The SQL host IP address (usually IPv4).
$sqlUser = 'vrim10'; // The SQL user used to connect.
$sqlPassword = 'WURZNKpHSfwzpyp7'; // The SQL password of the above user.
$sqlDatabase = 'vbulletin'; // The MySQL database.
$sqlPrefix = 'vrc_'; // The Prefix of all MySQL Tables, excluding those of vBulletin.
$sqlUserTable = 'user'; // The user table in the login method used.
$sqlUserIdCol = 'userid'; // The user ID column of the user table in the login method used.
$sqlUsernameCol = 'username'; // The username column of the user table in the login method used.
$sqlUsergroupCol = 'displaygroupid'; // The usergroup column of the user table in the login method used.

$installLoc = '/var/www/chatinterface/htdocs/v1/'; // The server location that the product is installed (global.php should be placed in this directory). This is not neccissarly needed, but is required if uploads are made to the server.
$installUrl = 'http://2.vrim.victoryroad.net/'; // The web accessible equivilent of the above path.


/* Encryption */
$salts = array( // DO NOT REMOVE ANY ENTRY. Entries can be freely added, with the last generally being used for all new data. Note that to disable encryption, make this empty. Alternatively, to disable encryption without losing all old messages, add a new entry at the bottom that is empty.
  101 => 'Fr33d0m*',
);
$encrypt = true;


/* Uploads */
$enableUploads = true;
$enableGeneralUploads = true; // If enabled, users can upload general files to the server, not just to rooms.
$uploadMimes = array('image/gif','image/jpeg','image/png','image/pjpeg','application/octet-stream'); // Mime types which all files must be.
$uploadExtensions = array('gif','jpg','jpeg','png'); // Files who use the octetstream mimetype will be checked against their extension instead.
$uploadMatchBoth = true; // If enabled, files must use both a compatible extension and mimetype.
$uploadMethod = 'database'; // Files can be uploaded both to a MySQL database and to the server. Choose either "database" or "server".
$encryptUploads = true; // Uploads can be encrypted on the server if uploaded to the server, though it takes up considerably more CPU (and a bit more storage space).


/* Misc Configuration */
$bannedUserGroups = array(8); // Usergroups which are not given access to the chat.
$messageLimit = 40; // The message limit for obtaining messages.
$onlineThreshold = 15; // The number of seconds befoer a user is removed from online lists.
$enableDF = array( // Default formatting users can user to differentiate their text.
  'colour' => true,
  'font' => true,
  'highlight' => true,
  'general' => true, // Bold, italics, etc.
);
$allowRoomCreation = true; // Use this to disable user room creation.
$hideRoomsOnline = true; // Use this to hide the names of rooms users are in to users who can't access said roms.
$bbcode = array( // Enable & Disable BBCode.
  'shortCode' => false,
  'buis' => true,
  'link' => true,
  'colour' => true,
  'image' => true,
  'video' => true,
  'emoticon' => true,
);

$parseFlags = true; // Messages sent under certain conditions will contain flags corrosponding to certain message data, like "video". Using this paramater, these messages will only contain the specific parameter and not the extra BBcode. This can be useful for certain APIs, data cleanliness, and so-on, but can also mean extra CPU cycles and incompatibility with older software, and also disables encryption for messages with parse flags. *DO NOT CHANGE THIS SETTING AFTER INITIAL SETUP*


/* DO NOT EDIT BELOW */
//ob_start(''); // Start the content buffer.
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

if ($novalidate == true) {}
else require_once('validate.php');

mysqlQuery('SET NAMES UTF8');
?>