<?php
///* MySQL Login *///
$sqlHost = 'localhost'; // The SQL host IP address (usually IPv4).
$sqlUser = ''; // The SQL user used to connect.
$sqlPassword = ''; // The SQL password of the above user.
$sqlDatabase = ''; // The MySQL database.
$sqlPrefix = ''; // The Prefix of all MySQL Tables, excluding those of vBulletin.

///* Forum Integration *///
$loginMethod = 'vbulletin'; // The product used for login. Currently only "vbulletin3.8" is supported.
$installLoc = ''; // The server location that the product is installed (global.php should be placed in this directory). This is not neccissarly needed, but is required if uploads are made to the server.
$installUrl = ''; // The web accessible equivilent of the above path.
$forumUrl = ''; // The URL of the forum being used.

$forumCookieSalt = ''; // The cookie salt used with vBulletin
$forumTablePrefix = ''; // The table prefix of all forum tables.
$forumCookiePrefix = 'bb'; // The cookie prefix of forum cookies.

$brokenUsers = array(1,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30); // This is generally only needed with PHPBB, which allows for several "broken" accounts to be used for posting - several bots as well as the anonymous account. At least in the latter case, it may be beneficial to allow it.
$superUsers = array(); // A list of users who have all priviledges. For PHPBB, user 2 should be included in most cases, while for vBulletin and Vanilla 1 should be added.

///* Encryption *///
$salts = array( // DO NOT REMOVE ANY ENTRY. Entries can be freely added, with the last generally being used for all new data. Note that to disable encryption, make this empty. Alternatively, to disable encryption without losing all old messages, add a new entry at the bottom that is empty.
  101 => 'xxx',
);
$encrypt = true;


///* Uploads *///
$enableUploads = true;
$enableGeneralUploads = true; // If enabled, users can upload general files to the server, not just to rooms.
$uploadMimes = array('image/gif','image/jpeg','image/png','image/pjpeg','application/octet-stream'); // Mime types which all files must be.
$uploadExtensions = array('gif','jpg','jpeg','png'); // Files who use the octetstream mimetype will be checked against their extension instead.
$uploadMatchBoth = true; // If enabled, files must use both a compatible extension and mimetype.
$uploadMethod = 'database'; // Files can be uploaded both to a MySQL database and to the server. Choose either "database" or "server".
$encryptUploads = true; // Uploads can be encrypted on the server if uploaded to the server, though it takes up considerably more CPU (and a bit more storage space).


///* Misc Configuration *///
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
$allowPrivateRooms = true; // Use this to disable user room creation.
$hideRoomsOnline = false; // Use this to hide the names of rooms users are in to users who can't access said roms.
$hidePostCounts = false; // If enabled, users will not be able to view post counts in rooms they are not able to access.
$bbcode = array( // Enable & Disable BBCode.
  'shortCode' => false,
  'buis' => true,
  'link' => true,
  'colour' => true,
  'image' => true,
  'video' => true,
  'emoticon' => true,
);

$longPolling = false; // If true, experimental longpolling support will be enabled. In general, it is recommended you NOT set this to true.

$enableForeignApi = true; // Set this to off to disable interfaces other than the provided one.
$insecureApi = true; // Should be off; will be removed.

$defaultTheme = 4; // Default of the provided themes, 1-5. 4 is normally the default.
$compressOutput = true; // Set this to off to avoid compacting PHP-generated HTML and XML output.
?>