<?php
/* Default Configuration Settings
* These are the defaults to the $config system (which is a lot like about:config in Firefox).
* Some of these are really barebones compared to the data used by the install script (e.g. searchWord directives), while others are ommitted from the install script due to their relative rarity in use.
* This file will only need to be loaded when the $config data is out-of-date.
* Finally, every single $config variable that is at any time used is in this file, in case you need a referrence.
*/

/* DO NOT EDIT
* (Unless you really really really want to. In which case, be prepared to have to reinstall stuff.) */

$defaultConfig = array(
  /*** User Management ***/
  /* Anonymous Users */
  'anonymousUserId' => 2,


  /* Integration Users */
  'bannedUserGroups' => array(),
  'userSyncThreshold' => 60 * 60 * 24 * 7, // The time after which a user's vanilla data is resycned with its integration data, such as adminGroups. The default of 6 hours means that if a user is banned, for instance, in a forum, it may take 6 hours for this to be reflected in the messenger.


  /* Vanilla Users */
  'ageRequired' => true,
  'ageMinimum' => 13,
  'ageMaximum' => 100,

  'emailRequired' => false, // The vanilla subsystem can function without email, and in truth, its not even used for anything in FIMv3 (where Vanilla is very IRC-like).

  'avatarMaximumWidth' => 1000,
  'avatarMaximumHeight' => 1000,
  'avatarMinimumWidth' => 10,
  'avatarMinimumHeight' => 10,


  /* Sessions */
  'sessionExpires' => 60 * 15, // 15 minutes


  /*** FIM Features ***/

  /* Rooms */
  'roomLengthMinimum' => 5, // integer Requires all room names to be at least this many characters.
  'roomLengthMaximum' => 20, // integer Requires all room names to not exceed this many characters in length.

  'disableTopic' => false, // bool Whether or not to disable topic functionality.

  'userRoomCreation' => false,
  'userPrivateRoomCreation' => true,
  'hiddenRooms' => true,
  'officialRooms' => true,
  'defaultRoom' => 1,


  /* Private & OTR Rooms */
  'privateRoomsEnabled' => true,


  /* Default Formatting */
  'defaultFormattingColor' => true,
  'defaultFormattingFont' => true,
  'defaultFormattingHighlight' => true,
  'defaultFormattingBold' => true,
  'defaultFormattingItalics' => true,
  'defaultFormattingUnderline' => false,
  'defaultFormattingStrikethrough' => false,
  'defaultFormattingOverline' => false,


  /* Message Sending & Formatting */
  'maxMessageLength' => 1000,

  'fonts' => array(
    'FreeMono' => "FreeMono, TwlgMono, 'Courier New', Consolas, monospace",
    'Courier New' => "'Courier New', FreeMono, TwlgMono, Consolas, Courier, monospace",
    'Consolas' => "Consolas, 'Courier New', FreeMono, TwlgMono, monospace",
    'Courier' => "Courier, 'Courier New', Consolas, monospace",
    'Liberation Mono'=> "'Liberation Mono', monospace",
    'Times New Roman' => "'Times New Roman', 'Liberation Serif', Georgia, FreeSerif, Cambria, serif",
    'Liberation Serif' => "'Liberation Serif', FreeSerif, 'Times New Roman', Georgia, Cambria, serif",
    'Georgia' => "Georgia, Cambria, 'Liberation Serif', 'Times New Roman', serif",
    'Cambria' => "Cambria, Georgia, 'Liberation Serif', 'Times New Roman', serif",
    'Segoe UI' => "'Segoe UI', serif",
    'Garamond' => "Garamond, serif",
    'Century Gothic' => "'Century Gothic', Ubuntu, sans-serif",
    'Trebuchet MS' => "'Trebuchet MS', Arial, Tahoma, Verdana, FreeSans, sans-serif",
    'Arial' => "Arial, 'Trebuchet MS', Tahoma, Verdana, FreeSans, sans-serif",
    'Verdana' => "Verdana, 'Trebuchet MS', Tahoma, Arial, sans-serif",
    'Tahoma' => "Tahoma, Verdana, 'Trebuchet MS', Arial, FreeSans, sans-serif",
    'Ubuntu' => "Ubuntu, FreeSans, Tahoma, sans-serif",
    'Comic Sans MS' => "'Comic Sans MS', cursive",
    'Liberation Sans' => "Liberation Sans, sans-serif",
    "Bauhaus 93" => "'Bauhaus 93', fantasy",
    "Impact" => "Impact, fantasy",
    "Papyrus" => "Papyrus, fantasy",
    "Copperplate Gothic Bold" => "'Copperplate Gothic Bold', fantasy",
    "Rockwell Extra Bold" => "'Rockwell Extra Bold', fantasy",
  ),


  /* Message Retrieval */
  'defaultMessageHardLimit' => 50, // integer The default number of messages that will be returned by api/getMessages.php.
  'maxMessageHardLimit' => 500, // integer The maximum number of messages that will be returned by api/getMessages.php.

  'defaultMessageLimit' => 10000, // integer The default message range getMessages.php will query.
  'maxMessageLimit' => 10000, // integer The maximum message range getMessages.php will query.

  'serverSentEvents' => true, // bool Whether to enable SSE. These are fairly stable, but some server configurations will still have problems with server sent events. Disable if you have issues.
  'serverSentEventsWait' => .5, // float Server sent events are more controlled, so we can call them at a greater frequency.
  'serverSentMaxRetries' => 50, // int The number of tries the server will requery before requiring the client to resend a SSE request.


  /* Searching */
  'fullTextArchive' => false, // bool Whether or not to enable full text archive search. This will search the "phrase" table in full-text mode, which is much slower, as opposed to the default full-match mode. If you have the server power, this is good functionality to enable, but most people do not.

  'searchWordMinimum' => 4, // int The minimum length a string must be to be added to the "phrase" table.
  'searchWordMaximum' => 12, // int The maximum length a string can be to be added to the "phrase" table.
  'searchWordOmissions' => array('that','them','then','than','thus','have','with','will','would','there','their','what','about','when','make','like','also','think','over','into','time','year','just','know','take','which','about','back','after','well','even','want','because','these','most'), // array Words that are not wll be ommitted from the "phrase" table.
  'searchWordPunctuation' => array(',','.',';',':','-','"','\'','=','?','\\','/','[',']','^','&','#','@','!','%','*','(',')','‘','’','¡','¿','¦'), // array Punctuation marks that are not included in phrases.
  'searchWordConvertsFind' => array('é','É','ë','Ë','ó','Ó','ö','Ö','ø','Ø','í','Í','ï','Ï','ú','Ú','ü','Ü','ñ','？','！','。','、','；','：','／','｜','＠','＃','＄','％','＾','＆','＊','（','）','「','」','｛','｝','＜','＞'), // array An array of characters that will be replaced in the "phrase" table. Not that the "romanisation" configuration rules are applied to phrases as well, so you should not include these.
  'searchWordConvertsReplace' => array('e','E','e','E','o','O','o','O','o','O','i','I','i','I','u','U','u','U','n','?','!','.','\',',';',':','/','|','@','#','$','%','^','&','*','(',')','[',']','{','}','<','>'), // array See "searchWordConvertsFind"


  /* Active Users */
  'defaultOnlineThreshold' => 15, // integer The default period of time after which a user is considered inactive. NOTE: This functionality will be modified either in B3 or B4 significantly, such that users are no longer pinged as frequently, and instead will send a signal only when they leave. Thus, this will be increase to around 120.


  /* File Uploads */
  'fileSuffixes' => array('B', 'KiB', 'MiB', 'GiB', 'PiB', 'EiB', 'ZiB', 'YiB'),
  'fileIncrementSize' => 1024,

  'enableUploads' => true,
  'enableGeneralUploads' => false,
  'fileUploadChunkSize' => 1024,
  'uploadMaxFiles' => -1,
  'uploadMaxUserFiles' => -1,
  'allowEmptyFiles' => false,
  'allowOrphanFiles' => false,
  'extensionChanges' => array(
    'jpe' => 'jpg',
    'jpeg' => 'jpg',
    'tar.gz' => 'tgz',
    'tar.bz2' => 'tbz2',
    'mpeg' => 'mpg',
    'html' => 'htm',
    'text' => 'txt',
    'php4' => 'php',
    'php5' => 'php',
    'tiff' => 'tif',
  ),
  'fileContainers' => array(
    'exe' => 'application', 'msi' => 'application', 'cab' => 'application',
    'swf' => 'flash', 'flv' => 'flash',

    'rtf' => 'application',
    'doc' => 'application', 'docx' => 'application',  'dotx' => 'application',
    'xls' => 'application', 'xlsx' => 'application', 'xltx' => 'application', 'xlam' => 'application', 'xlsb' => 'application',
    'ppt' => 'application', 'potx' => 'application', 'ppsx' => 'application', 'pptx' => 'application', 'sldx' => 'application',
    'ods' => 'application', 'odt' => 'application', 'odp' => 'application',

    'txt' => 'text', 'htm' => 'text', 'php' => 'text', 'css' => 'text', 'js' => 'text', 'json' => 'text', 'xml' => 'text',

    'png' => 'image', 'jpg' => 'image', 'gif' => 'image', 'bmp' => 'image', 'ico' => 'image', 'tif' => 'image', 'svg' => 'image', 'svgz' => 'image',

    'mp2' => 'audio', 'mp3' => 'audio', 'ogg' => 'audio', 'flac' => 'audio', 'm4a' => 'audio', 'wav' => 'audio', 'wma' => 'audio',

    'mp4' => 'video', 'm4v' => 'video', 'ogv' => 'video', 'mov' => 'video', 'wmv' => 'video',

    'zip' => 'archive', 'rar' => 'archive', '7z' => 'archive', 'tgz' => 'archive', 'tbz2' => 'archive',
  ),
  'imageTypes' => array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG),
  'allowedExtensions' => array('gif', 'png', 'jpg', 'txt', 'ogg', 'mp3', 'flac'),
  'uploadMimes' => array( // We transfer a file with a specific mimetype. Obviously, certain types are more prone to viruses than others.
    'txt' => 'text/plain',
    'htm' => 'text/html',
    'php' => 'text/html',
    'css' => 'text/css',
    'js' => 'application/javascript',
    'json' => 'application/json',
    'xml' => 'application/xml',

    'exe' => 'application/x-msdownload',
    'msi' => 'application/x-msdownload',
    'cab' => 'application/vnd.ms-cab-compressed',

    'swf' => 'application/x-shockwave-flash',
    'flv' => 'video/x-flv',
    'pdf' => 'application/pdf',
    'rtf' => 'application/rtf',
    'doc' => 'application/msword',
    'xls' => 'application/vnd.ms-excel',
    'ppt' => 'application/vnd.ms-powerpoint',

    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
    'xlam' => 'application/vnd.ms-excel.addin.macroEnabled.12',
    'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
    'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
    'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'sldx' => 'application/vnd.openxmlformats-officedocument.presentationml.slide',

    'odt' => 'application/vnd.oasis.opendocument.text',
    'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    'odp' => 'application/vnd.oasis.opendocument.presentation',

    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'gif' => 'image/gif',
    'bmp' => 'image/bmp',
    'ico' => 'image/vnd.microsoft.icon',
    'tif' => 'image/tiff',
    'svg' => 'image/svg+xml',
    'svgz' => 'image/svg+xml',

    'mp2' => 'audio/mpeg',
    'mp3' => 'audio/mpeg',
    'ogg' => 'audio/ogg',
    'flac' => 'audio/flac',
    'm4a' => 'audio/m4a',
    'wav' => 'audio/wav',
    'wma' => 'audio/x-ms-wma',

    'mp4' => 'video/mp4',
    'm4v' => 'video/mp4',
    'ogv' => 'video/ogg',
    'mov' => 'video/quicktime',
    'wmv' => 'video/x-ms-wmv',

    'zip' => 'application/zip',
    'rar' => 'application/x-rar-compressed',
    '7z' => 'application/x-7z-compressed',
    'tgz' => 'application/x-compressed-tar',
    'tbz2' => 'application/x-compressed-tar',
  ),
  'uploadMimeProof' => array( // When uploading files, we don't normally ensure a file is what it says (that's kinda hard). The mimetypes in uploadMimes will be checked against the detected mime type, however, if you include it here.
    'gif', 'jpg', 'png',
  ),
  'uploadSizeLimits' => array(
    'txt' => 1 * 1024 * 1024, // 1MB
    'htm' => 1 * 1024 * 1024, // 1MB
    'php' => 1 * 1024 * 1024, // 1MB
    'css' => 1 * 1024 * 1024, // 1MB
    'js' => 1 * 1024 * 1024, // 1MB
    'json' => 1 * 1024 * 1024, // 1MB
    'xml' => 1 * 1024 * 1024, // 1MB

    'exe' => 50 * 1024 * 1024, // 50MB
    'msi' => 50 * 1024 * 1024, // 50MB
    'cab' => 50 * 1024 * 1024, // 50MB

    'swf' => 10 * 1024 * 1024, // 10MB
    'flv' => 10 * 1024 * 1024, // 10MB
    'pdf' => 10 * 1024 * 1024, // 10MB
    'rtf' => 10 * 1024 * 1024, // 10MB
    'doc' => 10 * 1024 * 1024, // 10MB
    'xls' => 10 * 1024 * 1024, // 10MB
    'ppt' => 10 * 1024 * 1024, // 10MB

    'docx' => 10 * 1024 * 1024, // 10MB
    'dotx' => 10 * 1024 * 1024, // 10MB
    'xlsx' => 10 * 1024 * 1024, // 10MB
    'xltx' => 10 * 1024 * 1024, // 10MB
    'xlam' => 10 * 1024 * 1024, // 10MB
    'xlsb' => 10 * 1024 * 1024, // 10MB
    'potx' => 10 * 1024 * 1024, // 10MB
    'ppsx' => 10 * 1024 * 1024, // 10MB
    'pptx' => 10 * 1024 * 1024, // 10MB
    'sldx' => 10 * 1024 * 1024, // 10MB

    'odt' => 10 * 1024 * 1024, // 10MB
    'ods' => 10 * 1024 * 1024, // 10MB
    'odp' => 10 * 1024 * 1024, // 10MB

    'png' => 10 * 1024 * 1024, // 10MB
    'jpg' => 10 * 1024 * 1024, // 10MB
    'gif' => 10 * 1024 * 1024, // 10MB
    'bmp' => 10 * 1024 * 1024, // 10MB
    'ico' => 1 * 1024 * 1024, // 1MB
    'tif' => 10 * 1024 * 1024, // 10MB
    'svg' => 10 * 1024 * 1024, // 10MB
    'svgz' => 10 * 1024 * 1024, // 10MB

    'mp2' => 20 * 1024 * 1024, // 20MB
    'mp3' => 20 * 1024 * 1024, // 20MB
    'ogg' => 20 * 1024 * 1024, // 20MB
    'flac' => 50 * 1024 * 1024, // 50MB
    'm4a' => 20 * 1024 * 1024, // 20MB
    'wav' => 50 * 1024 * 1024, // 50MB
    'wma' => 20 * 1024 * 1024, // 20MB

    'mp4' => 10 * 1024 * 1024, // 100MB
    'm4v' => 10 * 1024 * 1024, // 100MB
    'ogv' => 10 * 1024 * 1024, // 100MB
    'wmv' => 20 * 1024 * 1024, // 20MB
    'mov' => 20 * 1024 * 1024, // 20MB

    'zip' => 50 * 1024 * 1024, // 50MB
    'rar' => 50 * 1024 * 1024, // 50MB
    '7z' => 50 * 1024 * 1024, // 50MB
    'tgz' => 50 * 1024 * 1024, // 50MB
    'tbz2' => 50 * 1024 * 1024, // 50MB
  ),


  /* Parental Controls */
  'parentalEnabled' => true, // Is the system enabled by default?
  'parentalForced' => true, // Can the user disable/enable the system him or herself?
  'parentalAgeDefault' => 13, // Age used in lieu of a birthdate, if the user has not provided one. (see "ageRequired" above)
  'parentalAgeChangeable' => true, // Can the user override his or her age group upwards? (No matter what, a user may set it downwards).
  'parentalFlagsDefault' => array(), // Flags on by default.
  'parentalRegistrationAge' => 0, // Age required to register.
  'parentalFlags' => array('language', 'violence', 'gore', 'drugs', 'gambling', 'nudity', 'suggestive', 'weapons'),
  'parentalAges' => array(6, 10, 13, 16, 18),


  /* Censor */
  'censorEnabled' => true,



  /*** Localisation ***/
  'defaultLanguage' => 'en', // string The language that is default when a user has not specified one. 'en' is the only value supported by default. TODO

  'romanisation' => array(
    'á' => 'a', 'ä' => 'a', 'å' => 'a', 'Á' => 'A', 'Ä' => 'A', 'Å' => 'A',
    'é' => 'e', 'ë' => 'e', 'É' => 'E', 'Ë' => 'E',
    'ú' => 'u', 'ü' => 'u', 'Ú' => 'U', 'Ü' => 'U',
    'í' => 'i', 'ï' => 'i', 'Í' => 'I', 'Ï' => 'I',
    'ó' => 'o', 'ö' => 'o', 'Ó' => 'O', 'Ö' => 'O',
    'æ' => 'ae', 'Æ' => 'AE',
    'ß' => 'ss',
    'ð' => 'd', 'Ð' => 'd',
    'œ' => 'ce', 'Œ' => 'CE',
    'þ' => 'th', 'Þ' => 'TH',
    'ñ' => 'n',
    'µ' => 'mu',
    'œ' => 'oe',
  ),



  /*** Caching ***/
  'hooksCacheRefresh' => 3600, // int The number of seconds after which the hooks cache will be refreshed. Because this is a *full* cache, the table will be read in its entirety every time this ammount of time elapses, so it should only be so low. It can be very high, however, because you should be able to manually clear this cache when you update hooks.
  'configCacheRefresh' => 86400, // int The number of seconds after which the config cache will be refreshed. Because this is a *full* cache, the table will be read in its entirety every time this ammount of time elapses, so it should only be so low. It can be very high, however, because you should normally be able to manually clear this cache when you update the config.
  'censorListsCacheRefresh' => 86400, // int The number of seconds after which the censor lists cache will be refreshed. Because this is a *full* cache, the table will be read in its entirety every time this ammount of time elapses, so it should only be so low. It can be very high, however, because you should normally be able to manually clear this cache when you update censor lists.
  'censorWordsCacheRefresh' => 86400, // int The number of seconds after which the censor words cache will be refreshed. Because this is a *full* cache, the table will be read in its entirety every time this ammount of time elapses, so it should only be so low. It can be very high, however, because you should normally be able to manually clear this cache when you update censor words.
  'enableWatchRoomsCache' => true, // bool If enabled, watch rooms will be queried using the user table instead of the watchRooms table. If set to false, the watchRoomsCache column will no longer be referenced, allowing it's removal. If set t-o true, the watchRoomsCache column will limit the total number of watchRooms a user may have to its length (when the column does not end with the "." symbol, the column will be considered invalid).
  'userIgnoreListCacheEnabled' => true, // bool If enabled, the user ignore list will be queried using the user table instead of the userIgnoreList table. See 'enableWatchRoomsCache' documentation for more information.
  'usersFriendsListCacheEnabled' => true, // bool If enabled, the user friends list will be queried using the user table instead of the userFriendList table. See 'enableWatchRoomsCache' documentation for more information.
  'roomPermissionsCacheEnabled' => true, // bool If true, permissions will be cached in the roomPermissionsCache table.
  'roomPermissionsCacheAutoUpdate' => true, // bool If true, permissions changes will automatically update in the table. May be disabled if too many queries are being made to the table.
  'roomPermissionsCacheExpires' => 60 * 60 * 24 * 7, // int Time after which a cache entry will no longer be considered valid. A low value will keep the cache table small (which may be required in some installations), while a high value will cause the greatest speed-up. However, a high value should _only_ be used if roomPermissionsCacheAutoUpdate is true; otherwise, a low value should be used.

  'messageIndexCounter' => 1000, // If changed, rebuild the messageIndex table!
  'messageTimesCounter' => 60 * 60 * 24, // If changed, rebuild the messageTimes table!



  /*** Output ***/
  'compressOutput' => true, // bool Whether or not to remove whitespace from API responses. Servers that enable GZ probably don't need to enable this, but it is safe eitherway.

  'apiPause' => .125,

  'cacheTableMaxRows' => 100,

  'enableUnreadMessages' => true,
  'enableWatchRooms' => true,
  'enableEvents' => true,

  /* XML Output */
  'encodeXmlEntitiesFind' => array('&', '\'', '<', '>', '"'),
  'encodeXmlEntitiesReplace' => array('&amp;', '&apos;', '&lt;', '&gt;', '&quot;'),
  'encodeXmlAttrEntitiesFind' => array('&', '\'', '<', '>', '"'),
  'encodeXmlAttrEntitiesReplace' => array('&amp;', '&apos;', '&lt;', '&gt;', '&quot;'),

  'compactXmlStringsFind' => array('/\ {2,}/', "/(\n|\n\r|\t|\r)/", "/\<\!-- (.+?) --\>/", "/\>(( )+?)\</"),
  'compactXmlStringsReplace' => array('', '', '', '><'),
  'compactJsonStringsFind' => array('/\ {2,}/', "/(\n|\n\r|\t|\r)/"),
  'compactJsonStringsReplace' => array('', ''),



  /*** Error Handling and Logging ***/
  'email' => '',

  'emailErrors' => true,
  'emailExeptions' => true,
  'logErrors' => true,
  'logExceptions' => true,
  'logErrorsFile' => '',
  'logExceptionsFile' => '',
  'displayExceptions' => true,

  /* Advanced Logging */
  'accessLogEnabled' => true,
  'modLogEnabled' => true,
  'fullLogEnabled' => true,



  /*** PHP Functions ***/
  'jsonDecodeRecursionLimit' => 6, // Places a limit on the maximum recursion depth for json_decode when handling user input. The software generally expects a depth of around 3 to be available, but greater depths may be provided for for plugins, etc.

  'curlUA' => 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)',



  /*** MISC ***/
  'dev' => false,

  'enabledInterfaces' => array(),
  'defaultInterface' => '',
  'disableWeb' => false,

  'recaptchaPublicKey' => '',
  'recaptchaPrivateKey' => '',
);
?>