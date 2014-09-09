<?php
/* FreezeMessenger Copyright © 2014 Joseph Todd Parsons

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



////* Database Login *////

/* $dbConnect['core']['driver']
 * Defines the driver to use for database connections.
 * "mysql" and "mysqli" are both acceptable */
$dbConnect['core']['driver'] = 'mysqli';
$dbConnect['slave']['driver'] = 'mysqli';
$dbConnect['integration']['driver'] = 'mysqli';

/* $dbConnect['core']['host']
 * Defines the MySQL server to be connected to.
 * If unsure, "localhost" will work if you are connecting to a local server (which is the case most of the time) */
$dbConnect['core']['host'] = 'localhost';
$dbConnect['slave']['host'] = 'localhost';
$dbConnect['integration']['host'] = 'localhost';

/* $dbConnect['core']['port']
 * Defines the MySQL port the MySQL server can be accessed to.
 * If unsure, 3306 will usually be the default. */
$dbConnect['core']['port'] = 3306;
$dbConnect['slave']['port'] = 3306;
$dbConnect['integration']['port'] = 3306;

/* $dbConnect['core']['username']
 * Defines the user of the MySQL connection to be used.
 * If unsure, PHPMyAdmin can be used to create new users, or ask your webhost/geeky friend for help. */
$dbConnect['core']['username'] = '';
$dbConnect['slave']['username'] = '';
$dbConnect['integration']['username'] = '';

/* $dbConnect['core']['password']
 * Defines the password associated with the user specified above. */
$dbConnect['core']['password'] = '';
$dbConnect['slave']['password'] = '';
$dbConnect['integration']['password'] = '';

/* $dbConnect['core']['database']
 * Defines the database to connect to.
 * The above user must have permission to SELECT, INSERT, DELETE, and UPDATE in this database.
 * Note that, when integrating with forums, you __MUST__ use the same database as the forum. */
$dbConnect['core']['database'] = '';
$dbConnect['slave']['database'] = '';
$dbConnect['integration']['database'] = '';

/* $sqlPrefix
 * A prefix used for all tables.
 * If uncertain, a random string is often the best bet.
 * If you are integrating with a forum, it is imperative you not leave this blank. */
$dbConfig['vanilla']['tablePrefix'] = '';
$dbConfig['integration']['tablePrefix'] = '';




////* Cache Server *////
/* $cacheConnect['driver']
 * Defines the driver to use for caching.
 * "apc" and "memcache" are both acceptable.
 * Because APC does not require any advanced set-up, it is default. However, memcache is a must for large installations. */
$cacheConnect['driver'] = '';

/* $cacheConnect['servers']
 * For memcache, this is the list of servers to use in the connection pool.
 * If using APC, this directive is ignored. */
$cacheConnect['servers'] = array(
  0 => array(
    'host' => '127.0.0.1',
    'port' => 11211,
    'persistent' => true,
    'weight' => 1,
    'timeout' => 1,
    'retry_interval' => 15,
  ),
);



////* Forum Integration *////

/* $loginConfig['method']
 * The method used for forum-integration.
 * If you are not integrating with a forum, use "vanilla".
 * Otherwise, "phpbb", "vbulletin3", and "vbulletin4" are supported by default. */
$loginConfig['method'] = 'vanilla';

/* $loginConfig['portableHashing']
 * Whether FreezeMessenger has been set up to use portable hashing.
 * This should not be changed without porting over all passwords, which would most likely require a reset. */
$loginConfig['portableHashing'] = array();

/* $loginConfig['url']
 * The URL of the forum you will be integrating with.
 * If not using integration (login method vanilla), you may leave this blank.
 * Otherwise, you are strongly encouraged to specify the accurate URL (such as http://example.com/forums/). */
$loginConfig['url'] = 'http://example.com/forums/'; // The URL of the forum being used.

/* $loginConfig['superUsers']
 * A list of userIds who have full control over the software.
 * In general, this should include at least yourself. Thus, 1 for vBulletin and Vanilla, and 2 for PHPBB. */
$loginConfig['superUsers'] = array();




////* Encryption *////

/*
 * $blowFish
 * When set to true, FreezeMessenger will attempt to use BlowFish for all password encrpytions. Note that doing so is the best for user security.
 * However, blowFish is not widely supported, and once passwords are encoded using blowFish, FreezeMessenger can not be moved to systems that do not support it.
 * DO NOT CHANGE THIS AT ANY TIME POST-INSTALLATION!
 */
$blowFish = false;

/*
 * $sha256Rounds
 * If $blowFish is false, FreezeMessenger will fallback to sha256. For small installations, a low number of sha256 rounds is ideal.
 * Post-installation, this number can be increased as long as administrators update the database first.
 */
$sha256Rounds = 5000;

/* $salts
 * An array of salts.
 * You can add values to this any time you want, but never remove them.
 * By adding values often, you will increase the general security of the system as long as no one is able to read this file. However, it is by no means required.
 * You are __STRONGLY__ encouraged to set at least one value (which defaults to "xxx" shown below) for generation of things like passwords and session hashes even if you don't want to encrypt anything else.. */
$salts = array(
  101 => 'xxx',
);

/* $encrypt
 * Set to false to disable message encrpytion.
 * You are free to change this at any time, as long as no values are removed in the $salts entry above. */
$encrypt = true;

/* $encryptUploads
 * Whether or not uploaded files should be encrypted.
 * Doing so is encouraged, but not required. It does mean greater CPU stress. */
$encryptUploads = true;




////* General *////

/* $installUrl */
$installUrl = '';



/* $tmpDir */
$tmpDir = '';




////* Save Me! *////
////* These disable certain customizations in case things go horribly wrong. *////

/* $disableConfig
 * Disables config modifications read from the database, instead forcing the default configuration.
 * Use this if a configuration change makes it impossible to revert said change. */
$disableConfig = false;

// Unlisted variables: $installUrl, $installLoc, $sessionExpire
// Learn about them in the documentation.
?>