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
  'roomLengthMinimum' => 5,
  'roomLengthMaximum' => 20,

  'defaultLanguage' => 'en',

  'defaultMessageHardLimit' => 50,
  'maxMessageHardLimit' => 500,

  'defaultMessageLimit' => 10000,
  'maxMessageLimit' => 10000,

  'defaultOnlineThreshold' => 15,

  'fullTextArchive' => false,

  'searchWordMinimum' => 4,
  'searchWordMaximum' => 10,
  'searchWordOmissions' => array(),
  'searchWordPunctuation' => array(),
  'searchWordConvertsFind' => array(),
  'searchWordConvertsReplace' => array(),

  // All Seconds
  'kicksCacheRefresh' => 30,
  'permissionsCacheRefresh' => 30,
  'phrasesCacheRefresh' => 600,
  'templatesCacheRefresh' => 600,
  'hooksCacheRefresh' => 600,
  'configCacheRefresh' => 600,
  'censorListsCacheRefresh' => 600,
  'censorWordsCacheRefresh' => 600,
  'watchRoomsCacheRefresh' => 600,
  'roomListNamesCacheRefresh' => 3600, // This isn't used much (and is mostly a placeholder), which is why this value is so high.

  'longPolling' => false,
  'longPollingWait' => 2,
  'longPollingMaxRetries' => 50,

  'serverSentEvents' => true,
  'serverSentEventsWait' => .5, // Server sent events are more controlled, so we can call them at a greater frequency.
  'serverSentMaxRetries' => 50,
  'serverSentFastCGI' => false, // This MUST be true for FastCGI compatibility.
  'serverSentTimeLimit' => 0, // This MUST be true for many PHP setups, notably on IIS.

  'compressOutput' => true,

  'disableTopic' => false,

  'enableUploads' => false,
  'enableGeneralUploads' => false,
  'fileUploadChunkSize' => 1024,
  'uploadMaxFiles' => -1,
  'uploadMaxUserFiles' => -1,
  'allowEmptyFiles' => false,
  'allowOrphanFiles' => false,
  'extensionChanges' => array(
    'jpe' => 'jpg',
    'jpeg' => 'jpeg',
    'tar.gz' => 'tgz',
    'tar.bz2' => 'tbz2',
    'mpeg' => 'mpg',
    'html' => 'htm',
    'text' => 'txt',
    'php4' => 'php',
    'php5' => 'php',
  ),
  'fileContainers' => array(
    // application    
    'exe' => 'application',
    'msi' => 'application',
    'cab' => 'application',

    'swf' => 'flash',
    'flv' => 'flash',
    'rtf' => 'application',

    'doc' => 'application',
    'xls' => 'application',
    'ppt' => 'application',

    'docx' => 'application',
    'dotx' => 'application',
    'xlsx' => 'application',
    'xltx' => 'application',
    'xlam' => 'application',
    'xlsb' => 'application',
    'potx' => 'application',
    'ppsx' => 'application',
    'pptx' => 'application',
    'sldx' => 'application',

    'odt' => 'application',
    'ods' => 'application',
    'odp' => 'application',

    // text
    'txt' => 'text',
    'htm' => 'text',
    'php' => 'text',
    'css' => 'text',
    'js' => 'text',
    'json' => 'text',
    'xml' => 'text',

    // image
    'png' => 'image',
    'jpe' => 'image',
    'jpeg' => 'image',
    'jpg' => 'image',
    'gif' => 'image',
    'bmp' => 'image',
    'ico' => 'image',
    'tiff' => 'image',
    'tif' => 'image',
    'svg' => 'image',
    'svgz' => 'image',

    // archives
    'zip' => 'archive',
    'rar' => 'archive',
    '7z' => 'archive',
    'tgz' => 'archive',
    'tbz2' => 'archive',
  ),
  'imageTypes' => array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG),
  'allowedExtensions' => array('gif', 'png', 'jpg', 'jpeg'),
  'uploadMimes' => array( // We transfer a file with a specific mimetype. Obviously, certain types are more prone to viruses than others.
    // text
    'txt' => 'text/plain',
    'htm' => 'text/html',
    'html' => 'text/html',
    'php' => 'text/html',
    'css' => 'text/css',
    'js' => 'application/javascript',
    'json' => 'application/json',
    'xml' => 'application/xml',
    
    // application    
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

    // image
    'png' => 'image/png',
    'jpe' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'jpg' => 'image/jpeg',
    'gif' => 'image/gif',
    'bmp' => 'image/bmp',
    'ico' => 'image/vnd.microsoft.icon',
    'tiff' => 'image/tiff',
    'tif' => 'image/tiff',
    'svg' => 'image/svg+xml',
    'svgz' => 'image/svg+xml',

    // audio
    'mp2' => 'audio/mpeg',
    'mp3' => 'audio/mpeg',
    'ogg' => 'audio/ogg',
    'flac' => 'audio/flac',
    'm4a' => 'audio/m4a',
    'wav' => 'audio/wav',
    'wma' => 'audio/x-ms-wma',
    'mov' => 'video/quicktime',
    
    // video
    'mp4' => 'video/mp4',
    'm4v' => 'video/mp4',
    'ogv' => 'video/ogg',

    // archive
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
    'gif' => 10 * 1024 * 1024, // 10MB
    'png' => 10 * 1024 * 1024, // 10MB
    'jpeg' => 10 * 1024 * 1024, // 10MB
  ),

  'avatarMaximumWidth' => 1000,
  'avatarMaximumHeight' => 1000,
  'avatarMinimumWidth' => 10,
  'avatarMinimumHeight' => 10,

  'maxMessageLength' => 1000,

  'apiPause' => .125,

  'cacheTableMaxRows' => 100,

  'enableUnreadMessages' => true,
  'enableWatchRooms' => true,
  'enableEvents' => true,

  'encodeXmlEntitiesFind' => array('&', '\'', '<', '>', '"'),
  'encodeXmlEntitiesReplace' => array('&amp;', '&apos;', '&lt;', '&gt;', '&quot;'),
  'encodeXmlAttrEntitiesFind' => array('&', '\'', '<', '>', '"'),
  'encodeXmlAttrEntitiesReplace' => array('&amp;', '&apos;', '&lt;', '&gt;', '&quot;'),

  'defaultTimeZone' => 0,

  'fileSuffixes' => array('B', 'KiB', 'MiB', 'GiB', 'PiB', 'EiB', 'ZiB', 'YiB'),
  'fileIncrementSize' => 1024,

  'compactXmlStringsFind' => array('/\ {2,}/', "/(\n|\n\r|\t|\r)/", "/\<\!-- (.+?) --\>/", "/\>(( )+?)\</"),
  'compactXmlStringsReplace' => array('', '', '', '><'),
  'compactJsonStringsFind' => array('/\ {2,}/', "/(\n|\n\r|\t|\r)/"),
  'compactJsonStringsReplace' => array('', ''),

  'dev' => false,

  'email' => '',

  'emailErrors' => true,
  'emailExeptions' => true,
  'logErrors' => true,
  'logExceptions' => true,
  'logErrorsFile' => '',
  'logExceptionsFile' => '',
  'displayExceptions' => true,

  'anonymousUserId' => 0,

  'bannedUserGroups' => array(),

  'enabledInterfaces' => array(),
  'defaultInterface' => '',
  'disableWeb' => false,

  'defaultFormattingColor' => true,
  'defaultFormattingFont' => true,
  'defaultFormattingHighlight' => true,
  'defaultFormattingBold' => true,
  'defaultFormattingItalics' => true,
  'defaultFormattingUnderline' => false,
  'defaultFormattingStrikethrough' => false,
  'defaultFormattingOverline' => false,

  'userRoomCreation' => false,
  'userPrivateRoomCreation' => true,

  'messageIndexCounter' => 1000, // If changed, rebuild the messageIndex table!
  'messageTimesCounter' => 60 * 60 * 24, // If changed, rebuild the messageTimes table!

  'recaptchaPublicKey' => '',
  'recaptchaPrivateKey' => '',

  'ageRequired' => true,
  'ageMinimum' => 13,
  'ageMaximum' => 100,

  'emailRequired' => false, // The vanilla subsystem can function without email, and in truth, its not even used for anything in FIMv3 (where Vanilla is very IRC-like).

  'parentalEnabled' => true, // Is the system enabled by default?
  'parentalForced' => true, // Can the user disable/enable the system him or herself?
  'parentalAgeDefault' => 13, // Age used in lieu of a birthdate, if the user has not provided one. (see "ageRequired" above)
  'parentalAgeChangeable' => true, // Can the user override his or her age group upwards? (No matter what, a user may set it downwards).
  'parentalFlagsDefault' => array(), // Flags on by default.
  'parentalRegistrationAge' => 0, // Age required to register.
  'parentalFlags' => array('language', 'violence', 'gore', 'drugs', 'gambling', 'nudity', 'suggestive', 'weapons'),
  'parentalAges' => array(6, 10, 13, 16, 18),
  
  'curlUA' => 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)',

  'enableCensor' => true,

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
);
?>