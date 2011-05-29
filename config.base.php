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

$parseFlags = true; // Messages sent under certain conditions will contain flags corrosponding to certain message data, like "video". Using this paramater, these messages will only contain the specific parameter and not the extra BBcode. This can be useful for certain APIs, data cleanliness, and so-on, but can also mean extra CPU cycles and incompatibility with older software, and also disables encryption for messages with parse flags. *DO NOT CHANGE THIS SETTING AFTER INITIAL SETUP*
$longPolling = false; // If true, experimental longpolling support will be enabled. In general, it is recommended you NOT set this to true.

$enableForeignApi = true;
$insecureApi = true;
?>