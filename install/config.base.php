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




/* If you have no idea what "PHP" or "MySQL" is...
 * We strongly encourage you to ask a friend who may know a thing or two to help.
 * Or, better yet, why not just use that handy-dandy installation tool?
 * If you are editing post-installation or for some reason trying without the tool, a few pointers:
 -- Arrays must be formatted properly. They will generally look something like this:
  array(
    'a' => 1,
    3 => 'b',
    '22' => 99,
  );

  Using a "=" in place of a "=>" won't work, and forgetting to add a comma is a big no-no.

 --
*/


/* If you do... sorry 'bout all the verboisity. */




///* MySQL Login *///

/* $sqlHost
 * Defines the MySQL server to be connected to, with an optional port attached.
 * If unsure, "localhost" will work if you are connecting to a local server (which is the case most of the time) */
$dbConnect['core']['host'] = 'localhost';
$dbConnect['slave']['host'] = 'localhost';
$dbConnect['integration']['host'] = 'localhost';

/* $sqlUser
 * Defines the user of the MySQL connection to be used.
 * If unsure, PHPMyAdmin can be used to create new users, or ask your webhost/geeky friend for help. */
$dbConnect['core']['username'] = '';
$dbConnect['slave']['username'] = '';
$dbConnect['integration']['username'] = '';

/* $sqlPassword
 * Defines the password associated with the user specified above. */
$dbConnect['core']['password'] = '';
$dbConnect['slave']['password'] = '';
$dbConnect['integration']['password'] = '';

/* $sqlDatabase
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
$dbConfig['integration']['tablePreix'] = '';




///* Forum Integration *///

/* $loginMethod
 * The method used for forum-integration.
 * If you are not integrating with a forum, use "vanilla".
 * Otherwise, "phpbb", "vbulletin3", and "vbulletin4" are supported by default. */
$loginConfig['method'] = 'vanilla';

/* $forumUrl
 * The URL of the forum you will be integrating with.
 * If not using integration (login method vanilla), you may leave this blank.
 * Otherwise, you are strongly encouraged to specify the accurate URL (such as http://example.com/forums/). */
$loginConfig['url'] = 'http://example.com/forums/'; // The URL of the forum being used.

/* $brokenUsers
 * An array of userIds whom are considered "broken" or not allowed authentication.
 * This is mainly used because of PHPBB's several pre-added users such as bots.
 * All autoadded users, aside from yourself, should be on this list.
 * As of version 3.0.8 (and possibly others), the default will work.
 * For vBulletin and Vanilla, this does not need to be changed.
 * Finally, note that this may not really be needed. We're not sure yet. */
$loginConfig['brokenUsers'] = array();

/* $superUsers
 * A list of userIds who have full control over the software.
 * In general, this should include at least yourself. Thus, 1 for vBulletin and Vanilla, and 2 for PHPBB. */
$loginConfig['superUsers'] = array();




///* Encryption *///

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




///* Uploads *///

/* $enableUploads
 * Disable or enable file uploads.
 * Set this to false to disale file uploading.
 * For the most part you are recommended to keep this enabled, unless you are low on bandwidth.
 * If disabled, the remaining upload parameters are ignored. */
$enableUploads = true;

/* $enableGeneralUploads
 * Disable or enable general file uploads.
 * By default files can be uploaded to the server without being embedded in a message.
 * Disable this to require that all file uploads be within messages. */
$enableGeneralUploads = true;

/* $uploadUserMaxFiles
 * The total number of files a user may upload.
 * "-1" means there is no limit. */
$uploadUserMaxFiles = -1;

/* $uploadMaxFiles
 * The total number of files that may be stored by the server.
 * "-1" means there is no limit. */
$uploadMaxFiles = -1;

/* $installUrl */
$installUrl = '';




///* Interfaces *///

/* $enabledInterfaces
 * Public interfaces that are set up on this server.
 * Currently, "webpro" is the only interface packaged.
 * Other interfaces may be found at http://code.google.com/p/freeze-messenger/downloads/list */
$enabledInterfaces = array(
  'webpro',
  'liteiron',
  'choose',
);

/* $defaultInterface
 * THe default interface used when no other is found. */
$defaultInterface = 'webpro';

/* $disableWeb
 * This will disable all public interfaces.
 * Instead, users will be required to use third-party interfaces set up to use the FreezeMessenger API. */
$disableWeb = false;



///* Permissions *///
///* The defaults here will usually work for most chats. *///

/* $bannedUserGroups
 * Users that are a part of any of these groups are treated as automatically banned.
 * This is generally only used for forum integration. However, it must also be implemented if you wish to add a BANNED usergroup on Vanilla. */
$bannedUserGroups = array();

/* $enableDF
 * Set values here to true/false to disable "categories" of default formatting that users may use.
 * The "general" entry encompasses underline, bold, strikethrough, and italics. */
$enableDF = array( // Default formatting users can user to differentiate their text.
  'color' => true,
  'font' => true,
  'highlight' => true,
  'general' => true, // Bold, italics, etc.
);

/* $userDefaults
 * This defines default permissions used for users when they are added to the database.
 * These can be changed manually on a per-user basis by administrators. */
$userPermissions = array(
  'roomCreation' => true, // The user can create rooms.
  'privateRoomCreation' => true, // The user can create private rooms.
);




///* Defaults *///
///* You do not need to edit this section, but you can for nit-picky reasons. *///

/* $compressOutput
 * Set this to true to enable "shrunk" output.
 * This is great for servers that don't have GZIP enabled, and still beneficial otherwise. It does, however, result in slightly increased server load.
 * Note: Setting this to true will inevitably break some file downloads (file.php). This will be fixed if the directive is supported in a future version. */
$compressOutput = false; // Set this to off to avoid compacting PHP-generated HTML and XML output.

/* $disableTopic
 * Set to true to disable the changing of room topics.
 * Not well integrated yet, but it still does the job. */
$disableTopic = false;

/* $searchWordLength
 * This alters the minimum length of search in the archives.
 * Smaller lengths will dramatically increase the server power needed.
 * Specify "-1" to disable archive searching entirely (this may be useful if you are worried about its lack of encryption).
 * NOTE: The archive search feature is not yet stable. It will be in the FIM3 stable release ("FIM3GOLD"). */
$searchWordLength = 4;

/* $searchWordOmissions
 * This eliminates certain words from the search archive, good if they are especially common. */
$searchWordOmissions = array();

/* $searchWordPunctuation
 * These are symbols that are ignored in the archive search index.
 * The default works fairly good for most Latin an Japanese texts. */
$searchWordPunctuation = array(
  ',', '.', ';', ':', '-',
  '"', '\'',
  '=', '?', '\\', '/', '[',
  ']', '^', '&', '#', '@',
  '!', '%', '*', '(', ')',

  '‘', '’','¡','¿','¦',
);

/* $searchWordConverts
 * These are character(s) that will be converted to other characters when stored in the search archival database.
 * By default, both common European symbols and Japanese punctuation are included. */
$searchWordConverts = array(
  // European Conversions (i.e. Latin, French, German, Spanish)
  'é' => 'e', 'ë' => 'e',
  'É' => 'E', 'Ë' => 'E',
  'ó' => 'o', 'ö' => 'o',
  'ø' => 'o', 'Ó' => 'O',
  'Ö' => 'O', 'Ø' => 'O',
  'í' => 'i', 'ï' => 'i',
  'Í' => 'I', 'Ï' => 'I',
  'ú' => 'u', 'ü' => 'u',
  'Ú' => 'U', 'Ü' => 'U',
  'ñ' => 'n',

  // Japanese Conversions
  '？' => '?', '！' => '!',
  '。' => '.', '、' => ',',
  '；' => ';', '：' => ':',
  '／' => '/', '｜' => '|',
  '＠' => '@', '＃' => '#',
  '＄' => '$', '％' => '%',
  '＾' => '^', '＆' => '&',
  '＊' => '*',
  '（' => '(', '）' => ')',
  '「' => '[', '」' => ']',
  '｛' => '{', '｝' => '}',
  '＜' => '<', '＞' => '>',
);



///* Bleeding-Edge *///
///* These are provided to enable technology that may help considerably but may also screw things up. *///
///* Change at your own risk. *///

/* $longPolling
 * If enabled, experimental $longPolling support will be used instead of normal polling.
 * This has several benefits: it places less load on the server, less load on the user, allows for far faster connections, and is generally smoother.
 * However, at the same time, many (if not most) server configurations will have issues with this. */
$longPolling = false;

/* $anonymousUser
 * If specified, a single user can be used to allow all unregistered / not-logged in to post messages.
 * These users will be appended a number (stored in their session hash but lost quickly) to distinguish between themselves.
 * Leave false to not enable this feature. */
$anonymousUser = 0;

/* $config['fullTextArchive']
 * This enables full text archive searching.
 * Like all $config directives, it will be moved to the database in v4.
 * This feature is completely stable, but is also VERY slow on large installations. It is not optimized, but could still work. */
$config['fullTextArchive'] = false;

/* $config['cacheKicks']
 * This enables APC caching of kicked users.
 * This feature is mostly stable, SIGNIFICANTLY reduces the number of queries required at each page load, but on large installations could fill up the APC cache, or be used as an attack vector to do so (we're not sure).
 * Note that this is normally stored in a memory table anyway, and thus the speed boost is normally not that noticable. */
$config['cacheKicks'] = false;

/* $config['cacheKicksRefresh']
 * This is the time (in seconds) after which the kicks will be refreshed if cached.
 * 60 seconds is a fairly sane default.*/
$config['cacheKicksRefresh'] = 60;


// Unlisted variables: $config['defaultMessageLimit'], $config['defaultOnlineThreshold'], $installUrl, $installLoc, $sessionExpire, $roomLengthLimit
// Learn about them in the documentation.


// In future versions, many configuration variables may be moved to the DB. But, as of now, this is significantly faster.
?>