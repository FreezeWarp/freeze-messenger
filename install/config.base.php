<?php
/* FreezeMessenger Copyright © 2017 Joseph Todd Parsons

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

/* $dbConnect['vanilla']['tablePrefix']
 * A prefix used for all tables.
 * If uncertain, a random string is often the best bet.
 * If you are integrating with a forum, it is imperative you not leave this blank. */
$dbConnect['vanilla']['tablePrefix'] = '';
$dbConnect['integration']['tablePrefix'] = '';




////* Cache Servers *////

/* $cacheConnectMethods['disk']
 * If specified, this will enable disk caching, where applicable. */
$cacheConnectMethods['disk'] = [
    //'directory' => '', // If omitted, defaults to sys_get_temp_dir().
];


/* $cacheConnectMethods['apc']
 * If specified, this will enable APC caching, where applicable. */
/*$cacheConnectMethods['apc'] = [];*/


/* $cacheConnectMethods['apcu']
 * If specified, this will enable APCu caching, where applicable. */
/*$cacheConnectMethods['apcu'] = [];*/


/* $cacheConnectMethods['redis']
 * If specified, this will enable Redis caching. Refer to $streamMethods['redis'] to enable streaming as well. */
/*$cacheConnectMethods['redis'] = [
    'host' => '127.0.0.1',
    'port' => 6379,
    'timeout' => 0,
    'password' => false,
];*/


/* $cacheConnectMethods['memcached']
 * If specified, this will enable memcached with the attached list of servers to use in the connection pool. */
/* $cacheConnectMethods['memcached'] = array(
    0 => array(
        'host' => '127.0.0.1',
        'port' => 11211,
        'persistent' => true,
        'weight' => 1,
        'timeout' => 1,
        'retry_interval' => 15,
    ),
);*/




////* Message Streaming Servers*////

/* $streamMethods['kafka']
 * If specified, this will enable Kafka message streaming, which takes priority over all other message systems. */
/* $streamMethods['kafka'] = [
    'brokers' => 'localhost:9092', // This is a comma-separated list of Kafka servers.
]; */


/* $streamMethods['redis']
 * If specified, this will enable Redis for message streaming, which takes priority over Postgres. */
/*$streamMethods['redis'] = $cacheConnectMethods['redis'];*/


/* $streamMethods['pgsql']
 * If specified, this will enable PostGreSQL pub/sub. */
/*$streamMethods['pgsql'] = [
    'host' => '',
    'port' => 5432,
    'username' => '',
    'password' => '',
];*/




////* Forum Integration *////

/* $loginConfig['method']
 * The method used for forum-integration.
 * If you are not integrating with a forum, use "vanilla".
 * Otherwise, "phpbb", "vbulletin3", and "vbulletin4" are supported by default. */
$loginConfig['method'] = 'vanilla';

/* $loginConfig['extraMethods']
 * Additional methods that can be enabled to allow seemless logins. */

/*$loginConfig['extraMethods']['google'] = [
    'clientId' => '',
    'clientSecret' => ''
];*/
/*$loginConfig['extraMethods']['twitter'] = [
    'clientId' => '',
    'clientSecret' => ''
];*/
/*$loginConfig['extraMethods']['facebook'] = [
    'clientId' => '',
    'clientSecret' => ''
];*/
/*$loginConfig['extraMethods']['microsoft'] = [
    'clientId' => '',
    'clientSecret' => ''
];*/
/*$loginConfig['extraMethods']['steam'] = [
    'clientId' => '',
    'clientSecret' => ''
];*/
/*$loginConfig['extraMethods']['reddit'] = [
    'clientId' => '',
    'clientSecret' => ''
];*/

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

/* $loginConfig['adminGroups']
 * A list of groupIds who have most administrative functionality.
 * On vBulletin, this will be the admin group, ID = 6. */
$loginConfig['adminGroups'] = array();

/* $loginConfig['bannedGroups']
 * A list of groupIds who are "banned", and can not access the chat software.
 * On vBulletin, this will be the banned (ID = 8) and COPPA (ID = 4) groups. */
$loginConfig['bannedGroups'] = array();




////* General *////

/* $installUrl */
$installUrl = '';



////* Save Me! *////
////* These disable certain customizations in case things go horribly wrong. *////

/* $disableConfig
 * Disables config modifications read from the database, instead forcing the default configuration.
 * Use this if a configuration change makes it impossible to revert said change. */
$disableConfig = false;


