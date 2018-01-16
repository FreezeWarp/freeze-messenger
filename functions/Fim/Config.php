<?php

namespace Fim;

use Fim\User;

/**
 * The Config class is used to reference all configuration variables.
 */
class Config {
    /* Registration Policies */

    /** @var bool Whether or not registration is enabled. */
    public static $registrationEnabled = true;

    /** @var bool Whether or not registration is enabled even if a non-vanilla login method is enabled (like PHPBB or vBulletin). */
    public static $registrationEnabledIgnoreForums = false;

    /** @var int The default permissions newly registered users have. This is a bitfield consisting of some combination of {@see Fim\fimUser::USER_PRIV_VIEW}, {@see Fim\fimUser::USER_PRIV_POST}, {@see Fim\fimUser::USER_PRIV_TOPIC}, {@see Fim\fimUser::USER_PRIV_CREATE_ROOMS}, {@see Fim\fimUser::USER_PRIV_PRIVATE_FRIENDS}, {@see Fim\fimUser::USER_PRIV_PRIVATE_ALL}, {@see Fim\fimUser::USER_PRIV_ACTIVE_USERS}. {@see Fim\fimUser::USER_PRIV_TOPIC} and {@see Fim\fimUser::USER_PRIV_PRIVATE_ALL}. */
    public static $defaultUserPrivs = User::USER_PRIV_VIEW | User::USER_PRIV_POST | User::USER_PRIV_CREATE_ROOMS | User::USER_PRIV_PRIVATE_FRIENDS | User::USER_PRIV_ACTIVE_USERS;

    /** @var bool Whether an email is required to sign up. The vanilla subsystem can function without email, and in truth; its not even used for anything in FIMv3 (where Vanilla is very IRC-like)`. Additionally, there are no email registration limits; all limits to having multiple accounts are enforced by IP. */
    public static $emailRequired = false;

    /** @var int (Vanilla logins only.) The minimum number of characters needed in a password for it to be valid. */
    public static $passwordMinimumLength = 4;

    /** @var bool (Vanilla logins only.) Whether a user is required to specify their age in order to sign-up. */
    public static $ageRequired = false;

    /** @var int (Vanilla logins only.) The minimum allowed age for a user signing up. */
    public static $ageMinimum = 13;


    /* User Settings Policies */

    /** @var int The maximum width allowed for a user avatar. (Vanilla logins only.) */
    public static $avatarMaximumWidth = 1000;

    /** @var int The maximum height allowed for a user avatar. (Vanilla logins only.) */
    public static $avatarMaximumHeight = 1000;

    /** @var int The minimum width allowed for a user avatar. (Vanilla logins only.) */
    public static $avatarMinimumWidth = 10;

    /** @var int The minimum height allowed for a user avatar. (Vanilla logins only.) */
    public static $avatarMinimumHeight = 10;

    /** @var int A regex any avatar path must match. (Vanilla logins only.) */
    public static $avatarMustMatchRegex = false;

    /** @var int A regex any avatar path must NOT match. (Vanilla logins only.) */
    public static $avatarMustNotMatchRegex = false;

    /** @var bool Whether or not a user-set avatar must exist (it will be checked by making an HTTP request). */
    public static $avatarMustExist = true;

    /** @var int An avatar to be used if a user has not specified one. */
    public static $avatarDefault = '';


    /** @var int A regex any user profile must match. (Vanilla logins only.) */
    public static $profileMustMatchRegex = false;

    /** @var int A regex any user profile must NOT match. (Vanilla logins only.) */
    public static $profileMustNotMatchRegex = false;

    /** @var bool Whether or not a user-set profile must exist (it will be checked by making an HTTP request). */
    public static $profileMustExist = true;



    /* Misc User */

    /** @var int The minimum ID that can be appended to the anonymous user name to differentiate between anonymous users. */
    public static $anonymousUserMinId = 1;

    /** @var int The maximum ID that can be appended to the anonymous user name to differentiate between anonymous users. */
    public static $anonymousUserMaxId = 100000;

    /** @var int The time after which a user's vanilla data is resycned with its integration data, such as adminGroups. The default of 6 hours means that if a user is banned, for instance, in a forum, it may take 6 hours for this to be reflected in the messenger. TODO: probably remove. */
    public static $userSyncThreshold = 60 * 60 * 24 * 7;



    /* Lockout */

    /** @var int The maximum number of failed login attempts allowed before a user is locked out. Use 0 to disable the lockout subsystem. */
    public static $lockoutCount = 5;

    /** @var int The time before an active user lockout expires. */
    public static $lockoutExpires = 15 * 60;



    /* Flood */

    /** @var bool Whether global API-based flood detection is enabled. This relies on a memory table to be efficient, so it should generally be disabled if memory tables are not available. */
    public static $floodDetectionGlobal = true;

    /** @var int The maximum number of calls a user can make to api/acHelper per minute before triggering a flood lockout on that specific API. */
    public static $floodDetectionGlobal_acHelper_perMinute = 60;

    /** @var int The maximum number of calls a user can make to api/editFile per minute before triggering a flood lockout on that specific API. */
    public static $floodDetectionGlobal_editFile_perMinute = 5;

    /** @var int The maximum number of calls a user can make to api/editMessage per minute before triggering a flood lockout on that specific API. Note that message sends (which are also tracked by floodRoomLimitPerMinute and floodSiteLimitPerMinute) count against this as well. */
    public static $floodDetectionGlobal_editRoom_perMinute = 5;

    /** @var int The maximum number of calls a user can make to api/editUserOptions per minute before triggering a flood lockout on that specific API. */
    public static $floodDetectionGlobal_editUserOptions_perMinute = 10;

    /** @var int The maximum number of calls a user can make to api/editUserStatus per minute before triggering a flood lockout on that specific API. */
    public static $floodDetectionGlobal_editUserStatus_perMinute = 60;

    /** @var int The maximum number of calls a user can make to api/getActiveUsers per minute before triggering a flood lockout on that specific API. */
    public static $floodDetectionGlobal_getActiveUsers_perMinute = 20;

    /** @var int The maximum number of calls a user can make to api/getCensorLists per minute before triggering a flood lockout on that specific API. */
    public static $floodDetectionGlobal_getCensorLists_perMinute = 10;

    /** @var int The maximum number of calls a user can make to api/getFiles per minute before triggering a flood lockout on that specific API. */
    public static $floodDetectionGlobal_getFiles_perMinute = 10;

    /** @var int The maximum number of calls a user can make to api/getGroups per minute before triggering a flood lockout on that specific API. */
    public static $floodDetectionGlobal_getGroups_perMinute = 10;

    /** @var int The maximum number of calls a user can make to api/getKicks per minute before triggering a flood lockout on that specific API. */
    public static $floodDetectionGlobal_getKicks_perMinute = 10;

    /** @var int The maximum number of calls a user can make to api/getMessages per minute before triggering a flood lockout on that specific API. */
    public static $floodDetectionGlobal_getMessages_perMinute = 20;

    /** @var int The maximum number of calls a user can make to api/getRooms per minute before triggering a flood lockout on that specific API. */
    public static $floodDetectionGlobal_getRooms_perMinute = 30;

    /** @var int The maximum number of calls a user can make to api/getStats per minute before triggering a flood lockout on that specific API. */
    public static $floodDetectionGlobal_getStats_perMinute = 10;

    /** @var int The maximum number of calls a user can make to api/getUnreadMessages per minute before triggering a flood lockout on that specific API. */
    public static $floodDetectionGlobal_getUnreadMessages_perMinute = 10;

    /** @var int The maximum number of calls a user can make to api/getUsers per minute before triggering a flood lockout on that specific API. */
    public static $floodDetectionGlobal_getUsers_perMinute = 30;

    /** @var int The maximum number of calls a user can make to api/markMessageRead per minute before triggering a flood lockout on that specific API. */
    public static $floodDetectionGlobal_markMessageRead_perMinute = 10;

    /** @var int The maximum number of calls a user can make to api/moderate per minute before triggering a flood lockout on that specific API. */
    public static $floodDetectionGlobal_moderate_perMinute = 15;

    /** @var int The maximum number of calls a user can make to api/sendMessage per minute before triggering a flood lockout on that specific API. */
    public static $floodDetectionGlobal_sendMessage_perMinute = 120;

    /** @var int The maximum number of calls a user can make to api/sendUser per minute before triggering a flood lockout on that specific API. */
    public static $floodDetectionGlobal_sendUser_perMinute = 1;


    /** @var bool Whether global API-based flood detection is enabled. This relies on a memory table to be efficient, so it should generally be disabled if memory tables are not available. */
    public static $floodDetectionRooms = true;

    /** @var int The maximum number of messages a user may send in any given room before trigger a flood lockout for sending additional messages in that room. */
    public static $floodRoomLimitPerMinute = 30;

    /** @var int The maximum number of messages a user may send site-wide before trigger a flood lockout for sending additional messages in ANY room. */
    public static $floodSiteLimitPerMinute = 60;



    /* Default Permissions */

    /** @var bool Whether a user is allowed to edit their posts. (Unimplemented) */
    public static $usersCanEditOwnPosts = true;

    /** @var bool Whether a user can delete their own posts. If $usersCanEditOwnPosts is enabled, then this is automatically enabled as well for consistency. (Unimplemented) */
    public static $usersCanDeleteOwnPosts = true;



    /* Rooms */

    /** @var int Requires all room names to be at least this many characters. */
    public static $roomLengthMinimum = 5;

    /** @var int Requires all room names to not exceed this many characters in length. */
    public static $roomLengthMaximum = 20;

    /** @var bool Whether or not to disable topic functionality. */
    public static $disableTopic = false;

    /** @var bool Whether _any_ user can create rooms, even those with the permission to do so. Setting this false, in effect; disables the entire feature. (Admins are excluded.) */
    public static $userRoomCreation = false;

    /** @var bool Whether _any_ user can create private rooms, even those with the permission to do so. Setting this false, in effect; disables the entire feature. */
    public static $userPrivateRoomCreation = true;

    /** @var int The maximum number of rooms a single user can create. */
    public static $userRoomMaximum = 1000;

    /** @var int The maximum number of rooms a single user can create times the number of years the user has been registered. TODO: Test */
    public static $userRoomMaximumPerYear = 50;

    /** @var bool Whether hidden rooms are enabled. Disable this if the functionality is deemed too confusing. */
    public static $hiddenRooms = true;

    /** @var bool Whether rooms should can be marked as "official." It may make sense to disable this if user room creation is disabled. */
    public static $officialRooms = true;

    /** @var int The room that new users will enter by default. */
    public static $defaultRoomId = 1;



    /* Private & OTR Rooms */

    /** @var bool Whether users are allowed to send direct messages to other users. (TODO?) */
    public static $privateRoomsEnabled = true;

    /** @var bool Whether users are allowed to send off-the-record messages to other users. (TODO?) */
    public static $otrRoomsEnabled = true;

    /** @var int The maximum number of users who can join a private room. If this is increased, then all string roomId fields in the database must be adjusted accordingly, according to the pack format ("H*", "A99999999"); with A99999999 appearing for the number of max users. */
    public static $privateRoomMaxUsers = 5;



    /* Kick Functionality */

    /** @var bool If true, the kick subsystem is enabled. */
    public static $kicksEnabled = true;

    /** @var bool If true, whenever a user is kicked a message will be sent by the kicking moderator informing the room of the action. */
    public static $kickSendMessage = true;

    /** @var int The minimum number of seconds a user can be kicked for. */
    public static $kickMinimumLength = 10;

    /** @var bool If true, whenever a user is unkicked a message will be sent by the unkicking moderator informing the room of the action. */
    public static $unkickSendMessage = true;



    /* Message Sending & Formatting */

    /** @var int The maximum length every message must be. This will additionally be limited by the database. */
    public static $messageMaxLength = 5000;

    /** @var int The minimum length every message must be. */
    public static $messageMinLength = 1;


    /** @var bool Whether to allow users to set a default background formatting color. */
    public static $defaultFormattingHighlight = true;

    /** @var bool Whether to allow users to set a default foreground formatting color. */
    public static $defaultFormattingColor = true;

    /** @var bool The minimum contrast (between 1 and 21) that a message's colours must have. 3 is generally the absolute minimum for a good experience, and 4.5 is recommended. */
    public static $defaultFormattingMinimumContrast = 4.5;

    /** @var bool Whether to allow users to set default formatting using italics. */
    public static $defaultFormattingItalics = false;

    /** @var bool Whether to allow users to set default formatting using bolded text. */
    public static $defaultFormattingBold = false;

    /** @var bool Whether to allow users to set default formatting font. */
    public static $defaultFormattingFont = false;


    /** @var array The list of fonts (and corresponding font-families) available for message formatting. */
    public static $fonts = array(
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
    );



    /* Message Retrieval */

    /** @var int The default number of messages that will be returned by api/getMessages.php. */
    public static $defaultMessageLimit = 50;

    /** @var int The default number of rooms that will be returned by api/getRooms.php. */
    public static $defaultRoomLimit = 50;


    /** @var bool Whether to enable SSE. These are fairly stable; but some server configurations will still have problems with server sent events (in particular, enabling this means that every user will open at least two PHP threads that will stay open for the duration of the user's activity; as such, only use this if you have enough available PHP threads and memory for those PHP threads to serve your expected number of users). Disable if you have issues. */
    public static $serverSentEvents = true;

    /** @var float Server sent events are more controlled; so we can call them at a greater frequency. Note that this setting is ignored if using Kafka or Redis message streaming; it is still used for regular database streaming, and with Postgres LISTEN/NOTIFY. */
    public static $serverSentEventsWait = .5;

    /** @var int The number of tries the server will requery before requiring the client to resend a SSE request. Note that this setting is ignored if using Kafka or Redis message streaming; it is still used for regular database streaming, and with Postgres LISTEN/NOTIFY. */
    public static $serverSentMaxRetries = 240;

    /** @var int This is how long the server-sent events script is allowed to run for. It relies on PHP's set_time_limit, and may as a result be inconsistent between Windows and Linux (in-fact, it's basically useless on Windows -- rely on serverSentMaxRetries instead); and will be ignored in safe mode (though how well the chat runs in safe mode I'm not sure). */
    public static $serverSentTimeLimit = 150;



    /* Searching */

    /** @var string The transliteration code that will be used if Transliterator is installed. */
    public static $searchTransliteration = 'Any-Latin; Latin-ASCII; [\u0080-\u7fff] remove; Lower();';

    /** @var string The transliteration code that will be used by iconv if Transliterator is not installed. */
    public static $searchIconV = 'us-ascii//TRANSLIT';

    /** @var string When set, all characters not matching this PREG range will be removed. It can certainly be removed, as the other functions do a very good job of dealing with most characters, but this is ultimately the most sure-fire option. */
    public static $searchWhiteList = 'a-zA-Z ';

    /** @var array Punctuation marks that are will included in phrases. (searchWhiteList will typically cover this when specified.) */
    public static $searchWordPunctuation = array(',','.',';',':','-','"','\'','=','?','\\','/','[',']','^','&','#','@','!','%','*','(',')','‘','’','¡','¿','¦');



    /* Active Users */

    /** @var int The default period of time after which a user is considered inactive. */
    public static $defaultOnlineThreshold = 90;



    /* File Uploads */

    /** @var bool Whether to allow file uploads site-wide. */
    public static $enableUploads = true;

    /** @var bool Whether to allow file uploads that are not anchored to a room. */
    public static $enableGeneralUploads = false;

    /** @var int The number of bytes to read/write at once during uploads. Higher numbers allow faster uploads, but use more memory. */
    public static $fileUploadChunkSize = 4096;

    /** @var int The maximum number of files that can be uploaded to the server as a whole. -1 for unlimited. */
    public static $uploadMaxFiles = -1;

    /** @var int The maximum number of files a single user can upload to the server. -1 for unlimited. */
    public static $uploadMaxUserFiles = -1;

    /** @var int The maximum space that can be taken by all files uploaded to the server as a whole. -1 for unlimited. */
    public static $uploadMaxSpace = -1;

    /** @var int The maximum space that can be taken by the files belonging to a single user. -1 for unlimited. */
    public static $uploadMaxUserSpace = -1;

    /** @var bool Whether uploads (and, if applicable, thumbnails) should be written to disk. In many cases, it is slightly faster to do so, but potentially harder to maintain/perform backups. (TODO) */
    public static $uploadUseFilesystem = false;

    /** @var bool Whether files can be uploaded separately from a room. */
    public static $allowOrphanFiles = false;

    /** @var array File extensions that are automatically converted to more familiar alternatives. (The new extension will be used for size, mime, etc. calculations) */
    public static $extensionChanges = array(
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
    );

    /** @var array How to send different files according to their extension. (e.g. video may be shown in HTML <video> tags, etc.) */
    public static $fileContainers = array(
        'txt' => 'text', 'htm' => 'text', 'php' => 'text', 'css' => 'text', 'java' => 'text', 'js' => 'text', 'json' => 'text', 'xml' => 'text', 'ini' => 'text',

        'exe' => 'application', 'msi' => 'application', 'cab' => 'application',

        'swf' => 'flash', 'flv' => 'flash',

        'rtf' => 'application', 'pdf' => 'application',

        'doc' => 'application', 'docx' => 'application',  'dotx' => 'application',
        'xls' => 'application', 'xlsx' => 'application', 'xltx' => 'application', 'xlam' => 'application', 'xlsb' => 'application',
        'ppt' => 'application', 'potx' => 'application', 'ppsx' => 'application', 'pptx' => 'application', 'sldx' => 'application',
        'ods' => 'application', 'odt' => 'application', 'odp' => 'application',

        'png' => 'image', 'jpg' => 'image', 'gif' => 'image', 'bmp' => 'image', 'ico' => 'image', 'tif' => 'image', 'svg' => 'image', 'svgz' => 'image',

        'mp2' => 'audio', 'mp3' => 'audio', 'ogg' => 'audio', 'flac' => 'audio', 'm4a' => 'audio', 'wav' => 'audio', 'wma' => 'audio',

        'mp4' => 'video', 'm4v' => 'video', 'ogv' => 'video', 'mov' => 'video', 'wmv' => 'video',

        'zip' => 'archive', 'rar' => 'archive', '7z' => 'archive', 'tgz' => 'archive', 'tbz2' => 'archive',
    );

    /** @var array Extensions that correspond with images, and thus will have thumbnails generated for. */
    public static $imageTypes = ['gif', 'png', 'jpg'];

    /** @var array Avatars will be checked to be of these mimetypes according to PHP/GD. */
    public static $imageTypesAvatar = [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG];

    /** @var array Thumbnail of these ratios will be created when an image is uploaded. */
    public static $imageThumbnails = [.5, .25, .1];

    /** @var int Images larger than this height will not have thumbnails generated. */
    public static $imageResizeMaxHeight = 10000;

    /** @var int Images larger than this width will not have thumbnails generated. */
    public static $imageResizeMaxWidth = 10000;

    /** @var array Only files with these extensions can be uploaded. (They are also then outputted according to uploadMimes, and can only be as big as specified in uploadSizeLimits.) */
    public static $allowedExtensions = ['txt', 'css', 'java', 'js', 'json', 'xml', 'ini', 'pdf', 'rtf', 'docx', 'xlsx', 'pptx', 'gif', 'png', 'jpg', 'ogg', 'mp3', 'flac'];

    /** @var array The mimetypes used to transfer different files. Obviously, certain types are more prone to viruses than others. */
    public static $uploadMimes = array(
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'java' => 'text/x-java-source',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'ini' => 'text/plain',

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
    );

    /** @var array When uploading files, we don't normally ensure a file is what it says (that's kinda hard). The mimetypes in uploadMimes will be checked against the PHP-detected mime type, however; if you include it here. */
    public static $uploadMimeProof = ['pdf', 'rtf', 'docx', 'xlsx', 'pptx', 'gif', 'png', 'jpg', 'ogg', 'mp3', 'flac'];

    /** @var array The maximum allowed size for different filetypes. */
    public static $uploadSizeLimits = array(
        'txt' => 1 * 1024 * 1024, // 1MB
        'htm' => 1 * 1024 * 1024, // 1MB
        'php' => 1 * 1024 * 1024, // 1MB
        'css' => 1 * 1024 * 1024, // 1MB
        'js' => 1 * 1024 * 1024, // 1MB
        'json' => 1 * 1024 * 1024, // 1MB
        'java' => 1 * 1024 * 1024, // 1MB
        'xml' => 1 * 1024 * 1024, // 1MB
        'ini' => 1 * 1024 * 1024, // 1MB

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
    );

    /** @var bool Whether we attempt to block uploaded files from being used in HTML frames, in effect blocking HTML files from being embedded. It relies on HTTP headers; and thus is not perfect. */
    public static $blockFrames = true;



    /* Parental Controls */

    /** @var bool Whether or not the parental controls are enabled. */
    public static $parentalEnabled = true;

    /** @var int Age used in lieu of a birthdate; if the user has not provided one. (see "ageRequired") */
    public static $parentalAgeDefault = 13;

    /** @var bool Whether or not a user can override his or her age group upwards. (No matter what; a user may set it downwards). */
    public static $parentalAgeChangeable = true;

    /** @var array A list of parental flags enabled for users by default. */
    public static $parentalFlagsDefault = array();

    /** @var array The list of content flags that can be set on files, rooms, etc. */
    public static $parentalFlags = array('language', 'violence', 'gore', 'drugs', 'gambling', 'nudity', 'suggestive', 'weapons');

    /** @var array The list of settable parental ages. (In effect, we can mark content as being appropriate for these ages.) */
    public static $parentalAges = array(6, 10, 13, 16, 18);



    /* Censor */

    /** @var bool Whether the censor is enabled. Generally, there is no expected performance gain from disabling it, especially when APC or Memcached is available. */
    public static $censorEnabled = true;



    /*** Localisation ***/

    /** @var string The language that is default when a user has not specified one. 'en' is the only value supported by default. TODO */
    public static $defaultLanguage = 'en';

    /** @var array An array of characters that will be replaced in the "phrase" table. Note that the "romanisation" configuration rules are applied to phrases as well, so you should not include these. */
    public static $romanisation = array(
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
    );




    /** @var bool If true, permissions will be cached in the roomPermissionsCache table. */
    public static $roomPermissionsCacheEnabled = true;

    /** @var int  Time after which a cache entry will no longer be considered valid. A low value will keep the cache table small (which may be required in some installations), while a high value will cause the greatest speed-up. Of-course, the higher the value, the more delay there may be in permission updates; while the system will automatically prune permission cache entries when users are kicked and when room permissions change, the system will *not* prune permission caches when a kick expires; thus, very high values should be avoided if kicks are enabled. */
    public static $roomPermissionsCacheExpires = 300;

    /** @var int The time in seconds that a user or room object is considered valid for in the cache. */
    public static $cacheDynamicObjectsTimeout = 300;

    /** @var int The time in seconds that a the config cache is considered valid. (Note that the first time the config cache is generated, it will typically use the default value.) */
    public static $configCacheTimeout = 60 * 60 * 24;

    /** @var int The time in seconds that a the emoticon cache is considered valid. This will typically be automatically cleared when emoticons are added, but if using an emoticon-providing login provider (like vBulletin), you may wish to lower this. */
    public static $emoticonCacheTimeout = 60 * 60 * 24;

    /** @var float Delays all API requests by a fixed amount. Good for preventing overloading in some cases; but in practice better served by limiting number of connections by a single IP. (Which is up to you to do in your webserver configuration.) */
    public static $apiPause = 0.0;

    /** @var bool Set this to false to disable unread messages functionality. Can be useful if that functionality is causing database overload; though this is rare. */
    public static $enableUnreadMessages = true;

    /** @var bool Set this to false to disable events. You probably shouldn't. */
    public static $enableEvents = true;



    /*** Error Handling and Logging ***/

    /** @var string If specified, emails may automatically be sent to this address when errors occur. */
    public static $email = '';

    /** @var bool When an error occurs, print a backtrace. Doing so likely isn't _super_ insecure, but it is still inadvisible, not to mention the slowdown it could cause. */
    public static $displayBacktrace = false;

    /** @var bool The database will write all queries to $logQueriesFile if this is true. This should only be used for testing/diagnostics, as the file will quickly become VERY large! */
    public static $logQueries = false;

    /** @var string The database will write all queries (and the time they took) to this file, if $logQueries is true. */
    public static $logQueriesFile = "/tmp/fim_queryLog";

    /** @var bool When true, ALL API accesses will be logged to a table. This table will quickly become VERY large, so such functionality is generally ill-advised unless your server is optimised for it. */
    public static $accessLogEnabled = false;

    /** @var bool When true, a summary of all mod actions will be logged to a table. Such actions are relatively rare, so this log is generally best kept enabled for auditing purposes. */
    public static $modLogEnabled = true;

    /** @var bool When true, a larger, more detailed log of all mod actions will be logged to a table. Generally speaking, this is an overkill amount of logging. */
    public static $fullLogEnabled = true;

    /** @var array These entries in $_SERVER will be logged to fullLog when $fullLogEnabled is true. */
    public static $fullLogServerDirectives = ["HTTP_HOST", "HTTP_ORIGIN", "HTTP_REFERER", "SERVER_SIGNATURE", "SERVER_SOFTWARE", "SERVER_NAME", "SERVER_ADDR", "SERVER_PORT", "REMOTE_ADDR", "DOCUMENT_ROOT", "REQUEST_SCHEME", "SCRIPT_FILENAME", "REMOTE_PORT", "GATEWAY_INTERFACE", "SERVER_PROTOCOL", "QUERY_STRING"];



    /*** PHP Functions ***/

    /** @var int The maximum depth of JSON decoding as part of cURL requests. Should generally be at least around 6 unless extensions expect more, or detailed exception handling is enabled. */
    public static $jsonDecodeRecursionLimit = 6; // Places a limit on the maximum recursion depth for json_decode when handling user input. The software generally expects a depth of around 3 to be available; but greater depths may be provided for for plugins; exceptions; etc.

    /** @var string The user-agent to use in cURL requests. Generally only needed for, e.g., validating URLs. */
    public static $curlUA = 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)';



    /*** MISC ***/

    /** @var bool Whether "dev mode" is enabled. */
    public static $dev = false;

    /** @var string The default interface to redirect to when the FreezeMessenger "root" directory is visited. */
    public static $defaultInterface = 'webpro';

    /** @var int The number of kilobytes to send when flushing the output buffer. On most hosts, 4 is more than sufficient. However, values as high as 100 have been observed helpful. */
    public static $outputFlushPaddingKilobytes = 4;

    /** @var bool Whether or not SSL requests should be verified by the Guzzle library. This should generally be left on, and is included primarily for testing purposes. */
    public static $sslVerify = true;

    public static $recaptchaPublicKey = '';

    public static $recaptchaPrivateKey = '';
}
?>