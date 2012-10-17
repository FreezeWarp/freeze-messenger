<?php
/* Default Configuration Settings
 * These are the defaults to the $config system (which is a lot like about:config in Firefox).
 * Some of these are really barebones compared to the data used by the install script (e.g. searchWord directives), while others are ommitted from the install script due to their relative rarity in use.
 * This file will only need to be loaded when the $config data is out-of-date.
 * Finally, every single $config variable that is at any time used is in this file, in case you need a referrence.
*/

$defaultConfig = array(
  'roomLengthMinimum' => 5,
  'roomLengthMaximum' => 20,

  'defaultLanguage' => 'en',

  'defaultMessageHardLimit' => 50,
  'defaultMessageLimit' => 10000,
  'defaultOnlineThreshold' => 15,

  'fullTextArchive' => false,

  'searchWordMinimum' => 4,
  'searchWordMaximum' => 10,
  'searchWordOmissions' => array(),
  'searchWordPunctuation' => array(),
  'searchWordConvertsFind' => array(),
  'searchWordConvertsReplace' => array(),

  'kicksCacheRefresh' => 30,
  'permissionsCacheRefresh' => 30,
  'phrasesCacheRefresh' => 600,
  'templatesCacheRefresh' => 600,
  'hooksCacheRefresh' => 600,
  'configCacheRefresh' => 600,

  'longPolling' => false,
  'longPollingWait' => 2,
  'longPollingMaxRetries' => 50,

  'serverSentEvents' => true,
  'serverSentEventsWait' => .5, // Server sent events are more controlled, so we can call them at a greater frequency.
  'serverSentMaxRetries' => 50,
  'serverSentFastCGI' => true, // This MUST be true for FastCGI compatibility.
  'serverSentTimeLimit' => 0, // This MUST be true for many PHP setups, notably on IIS.

  'compressOutput' => true,

  'disableTopic' => false,

  'enableUploads' => false,
  'enableGeneralUploads' => false,
  'fileUploadChunkSize' => 1024,
  'uploadMaxFiles' => -1,
  'uploadMaxUserFiles' => -1,

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

  'emailRequired' => true, // The vanilla subsystem can function without email, but of-course email is required for password reminders.

  'parentalEnabled' => true, // Is the system enabled by default?
  'parentalForced' => true, // Can the user disable/enable the system him or herself?
  'parentalAgeDefault' => 13, // Age used in lieu of a birthdate, if the user has not provided one. (see "ageRequired" above)
  'parentalAgeChangable' => true, // Can the user override his or her age group upwards? (No matter what, a user may set it downwards).
  'parentalFlagsDefault' => array(), // Flags on by default.
  'parentalRegistrationAge' => 0, // Age required to register.
);
?>