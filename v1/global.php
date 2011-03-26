<?php
/* Configuration File
 * Core MySQLLogin, Cookie/etc. Salt, and Site Data is stored here. */

error_reporting(E_ALL ^ E_NOTICE);

if (file_exists('/var/www/chatinterface/htdocs/.tempStop') && $_GET['action'] != 'moderate') {
  die('VRIM has been disabled by an administrator. Please remain patient while order is restored.');
}

/* MySQL Login */
$sqlHost = '10.10.10.1';
$sqlUser = 'vrim10';
$sqlPassword = 'FyRwtruusT94TvMA';
$sqlDatabase = 'vbulletin';
$sqlPrefix = 'vrc_'; // The Prefix of all MySQL Tables, excluding those of vBulletin.


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
$allowRoomCreation = true;
$hideRoomsOnline = true;

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
