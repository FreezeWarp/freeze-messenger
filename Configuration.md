**In Need of Review**

# Quick Notes #

  * The following define each type of variable the below can be categorized as. This type represents a variable’s syntax (or how it looks within the file) and its possible values, listed respectively.
    * BOOL or Boolean - A true/false value. All possible values are:
      * true
      * false
    * STR or String - A string of text, characters, and numbers. It will usually have a single apostrophe both before and after its value, and any apostrophes within its value must have a backslash “/” before them. For instance:
      * &apos;hello&apos;
      * &apos;I\&apos;m fine&apos;
      * &apos;Never gonna give you up.&apos;
    * INT or Integer - A whole number value that is in most cases positive. Example values below might include:
      * 42
      * 10000
      * 0
    * FLOAT or Float - A number that may be whole or a decimal. For instance:
      * 1.5
      * 10000
      * 0
      * 3.91
    * ARRAY - A list of any of the above. Arrays must be formatted properly in order for the product to work. The general syntax is: array( 1, ‘2’, true );
  * In general installation and configuration of FIM does not require any programming expertise; all of the values detailed within can be modified without worry using the supplied creation script (see INSTALLATION). However, advanced configurations may require tweaking these. Always create a backup of the file before editing unless you are experienced.
  * Where possible, the config file itself attempts to explain each value, noting important things that should accommodate most users, inexperienced and experienced alike. However, the following data attepts to explain all data, and may become rather verbose.

# MySQL Configuration #

  * $dbConnect['core']['host'], $dbConnect['slave']['host'], $dbConnect['integration']['host'] (STR) - The location of the MySQL host. In most cases this is simply “localhost” but it may also be a web address or IP address, such as “12.134.16.1” or “http://www.google.com/”. If unsure, “localhost” will work in most situations.
  * $dbConnect['core']['user'], $dbConnect['slave']['user'], $dbConnect['integration']['user'] (STR) - The user of the MySQL account that will be used to run FIM. If unsure and integrating with a forum, it is most likely the same value used by the forum (vBulletin’s configuration can be found in includes/config.php and PHPBB’s under config.php).

> Note: This user must be created or already exist. Most webserver control panels can create MySQL users under a “databases” section.

> Note2: This user must be able to run DELETE, UPDATE, INSERT, and SELECT queries on all installed tables, as well as a forum’s user, group, emoticon, and socialgroup tables. Additionally, CREATE is requried to use the login script.
  * $dbConnect['core']['password'], $dbConnect['slave']['password'], $dbConnect['integration']['password'] (STR) - The password of the above MySQL user account. If possible use a random password.
  * $dbConnect['core']['database'], $dbConnect['slave']['database'], $dbConnect['integration']['database'] (STR) - The database all of FIM’s data will be stored in. If you are integrating with a forum (such as PHPBB or vBulletin), it MUST be the same database.
  * $dbConfig['vanilla']['tablePrefix'] (STR) - The prefix of all tables installed by FIM. If integrating with a forum, it MUST differ from the prefix of the forum.

# Forum Integration #

  * loginMethod (STR) - The method used for login. Supported values are (case sensitive):
    * vbulletin3 - Support for vBulletin 3.8
    * vbulletin4 - Support for vBulletin 4
    * phpbb3 - Support for PHPBB 3.0
    * vanilla - No Intergration

> Support for other products may be trivially added by anyone experienced with the product they wish to integrate; see ADDON DEVELOPMENT.
  * installLoc (STR) - The location the product is installed to. Assuming you are using a Linux system, this will often be something similar to:
    * /var/www/
    * /var/www/product/htdocs/

> Note: By default, this value is left out of the configuration file. However, it is recognized and may be used, and in the stock configuration is not included at all.
  * installUrl (STR) - The URL the product will be installed to. This should contain the “index.php” and “chat.php” files, the “client” directory, and so-on.

> Note: This value is, for the most part, no longer used, but may be used by plugins. In general, it can be safely left blank, and in the stock configuration is not included at all.
  * forumUrl (STR) - The URL of a forum if used for integration. This will be used to link to user profiles, avatars, smilies, and so-on.

> Note: This value is only required for avatars and profile. It is however recommended you specify a valid link, unless you are using vanilla logins.
  * forumSalt (STR) - Some forums (including vBulletin and PHPBB) use a specific “cookie salt” that varies installation-to-installation. This must be retrieved and set equal tot he forum’s value in order for cross-domain cookie support. It is only used if useSameCookies is TRUE.
  * forumCookiePrefix (STR) - The prefix of cookies used by the integrated product. It is only used if useSameCookies is TRUE.
  * forumTablePrefix (STR) - The prefix of tables within the forum.
Encryption

salts (ARRAY) - An array of salts that will be used to encrypt messages in addition to the randomly generated IVs. A key should be a positive integer value starting with one and the value should be a corresponding salt. To change the salt, add a new entry with a greater key value; if old entries are removed, older messages will not be able to be unencrypted.
encrypt (BOOL) - Whether or not encryption is enabled. Set to false while keeping any prior salts to disable encryption without losing old encrypted data. Note: The message reparsing script in the Admin Control Panel can be used to eliminate all database encryption if this is set to false, all messages are reparsed, and then the salts are removed. However, for even medium (~100,000) databases, this may take a matter of days to complete.
encryptUploads (BOOL) - Whether or not uploads should be encrypted like messages normally are.

# File Uploads #
  * enableUploads (BOOL) - Whether or not to enable any form of file uploads.
  * enableGeneralUploads (BOOL) - Whether or not general, hosted uploads should be enabled. If dissabled, files can only be uploaded as a part of sending a message.
  * uploadExtensions (ARRAY) - An array containing key-value pairs that corrospond to mime types. If a file's extension is not in this array, it can not be uploaded. Note:
> In general, it is possible to upload malicious content using "fake" file extensions. For instance, a file could be uploaded as "data.png" and contain an executable virus. However, uploaded files can never be executed on the server and will only be sent to the user with the admin-specifed mime. ONLY if a mimetype corrosponds to an "insecure" file does any risk exist.
> Among common "insecure" mime types, "application/octet-stream" is the most common. It is required for many files that don't othrewise corrospond to a mimetype, but has an added risk: it may allow users to upload malicious content under unrepresented mimetypes. For instance, "virus.pdf" poses a risk if the "pdf" extension corrosponds to "application/octet-stream" in the database.
  * uploadMaxSize (NOT IMPLEMENTED) (INT) - The maximum size of all uploads in bytes. "1024" corrosponds to a Kilobyte while "1048576" corrosponds to Megabyte.
uploadUserMaxFiles (INT) - The maximum number of files any user may upload.
  * uploadMaxFiles (NOT IMPLEMENTED) (INT) - The maximum number of uploads that may be stored on the server at any time.

# Interfaces #

  * enabledInterfaces (ARRAY) - Enabled server-facing interfaces. The packaged interfaces 'webpro' and 'liteiron' are included by defaults, while the "fake" interface 'choose' is also included.
defaultInterface (STRING) - The default interface if a server visits FIM's root without having set their own. The only packaged interface that allows users to specify their own is 'webpro', though the "fake" interface 'choose' explictly asks the specify, but does nothing else.

  * disableWeb (BOOL) - Whether or not to allow redirects for web interfaces. If disabled, only third-party clients can be used.

# Permissions #

  * bannedUserGroups (ARRAY) - A list of admin-set user groups (see the distinction in CORE) that are automatically banned.
enableDF (ARRAY) - Enable or disable user-default formatting. These can be specified independent of BBCode, just like the flag system (see CORE) allows for images and videos to be used in spite of not being allowed in BBCode. The following keys should corrospond to BOOL values for each portion of default formatting:
general - Bold and/or italics. Other general features, such as underline and strikethrough, can not be used as they are counterintuitive in general text.
color - The foreground colour of the user's text.
highlight - The highlight/background colour of the user's text.
font - The fontface used, among those found in the database.
userPermissions (ARRAY) - Defaults for users when added to the system.
roomCreation
privateRoomCreation
roomsOnline
postCounts
bbcode (ARRAY) - Whether or not to enable predefined BBCode.
shortCode
buis
link
color
image
video
emoticon
Defaults

cacheTableLimit (INT) - The maximum number of messages that can be stored in the cache table.
compressOutput (BOOL) - Enable or disable output compression. It may (or will) break file uploads, but is included since it can be good in a tight spot.
disableTopic (BOOL) - Enable or disable room topics. We don't know why it exists, but it works.
searchWordLength (INT) - The minimum length of keywords used for archive searching (see CORE for more information on this feature).
searchWordOmissions (ARRAY) - An array of words to omit from the archive, which can be useful if they are overly common. For instance, if you decrease the normal searchWordLength you may want to use this for especially common words (like "they", "us", "who", "are", etc.)
searchWordPunctuation (ARRAY) - An array of characters that are considered punctuation and will be internally be replaced with spaces to determine a message's keywords. For instance, if this includes ":", "hello:my name is bob" would result in the keywords (assuming the searchWordLength is in this case 2) "hello", "my", "name", "is", and "bob".
searchWordConverts (ARRAY) - An array of key-value pairs that converts character variations to the "common" script. For instance, the entry "é" => "e" allows for "café" to be matched in a search for "cafe" (or vice-versa). Replacements can be made with more than one unicode character, for instance replacing "æ" with "ae".
Experimental Features

longPolling (BOOL) - Whether or not to enable experimental longpolling support. IIS and Apache web servers are likely to have severe issues, but it is relatively simple to safely enable this flag (and benefit from the speed boost) by using one of these configurations:
LigHTTPD Web Server
nginx Web Server
anonymousUser (INT) - The user ID of a user that is treated as an "anonymous" or "unregistered" user. If specified (and greater than 0) this allows for users to talk without registering, though several issues exist that make it somewhat stable:
Users can not be identified by other users. The usernames of anonymous users are appended with a unique ID when the username is added to the message cache, but the archive does not store this data. However, IP addresses are logged in the permanent message storage, which could be used in the future in a non-reverse engineerable way.
User message permissions are not well tested. It is possible certain security risks exist in allowing anonymous users to post, though these are likely minor and will be fixed if reported in FIMv3 bugfix releases.
Anonymous users can not be moderated effectively. If kicked, all are kicked. If allowed access to a room, all are allowed access to a room.

# Others #