/*********************************************************
************************ START **************************
******************** Base Variables *********************
*********************************************************/

/* Requirements
 * All of these are pretty universal, but I want to be explicit with them. */
if (typeof Date === 'undefined') { window.location.href = 'browser.php'; throw new Error(window.phrases.errorBrowserDate); }
else if (typeof Math === 'undefined') { window.location.href = 'browser.php'; throw new Error(window.phrases.errorBrowserMath); }
else if (false === ('encodeURIComponent' in window || 'escape' in window)) { window.location.href = 'browser.php'; throw new Error(window.phrases.errorBrowserEscape); }

/* Prototyping
 * Only for Compatibility */

// Array indexOf
// Courtesy of https://developer.mozilla.org/en-US/docs/JavaScript/Reference/Global_Objects/Array/indexOf
if (!Array.prototype.indexOf) {
  Array.prototype.indexOf = function(elt /*, from*/) {
    var len = this.length >>> 0;

    var from = Number(arguments[1]) || 0;
    from = (from < 0)
         ? Math.ceil(from)
         : Math.floor(from);
    if (from < 0)
      from += len;

    for (; from < len; from++) {
      if (from in this && this[from] === elt) return from;
    }
    return -1;
  };
}


// Base64 encode/decode
//if (!window.btoa) window.btoa = $.base64.encode;
//if (!window.atob) window.atob = $.base64.decode;


// console.log
if (typeof console !== 'object' || typeof console.log !== 'function') {
  var console = {
    log : function() { return false; }
  };
}


/* Common Variables */

var userId, // The user ID who is logged in.
  roomId, // The ID of the room we are in.
  sessionHash, // The session hash of the active user.
  anonId, // ID used to represent anonymous posters.
  prepopup,
  serverSettings;



/* Function-Specific Variables */

window.isBlurred = false; // By default, we assume the window is active and not blurred.
var topic,
  favicon = $('#favicon').attr('href'),
  uploadSettings = {}, // Object
  requestSettings = {
    longPolling : false, // We may set this to true if the server supports it.
    timeout : 2400, // We may increase this dramatically if the server supports longPolling.
    firstRequest : true,
    totalFails : 0,
    lastMessage : 0,
    lastEvent : 0
  },
  timers = {t1 : false}; // Object



/* Objects for Cleanness, Caching. */

var roomRef = {}, roomIdRef = {}, modRooms = {}, // Just a whole bunch of objects.
  userRef = {}, userIdRef = {}, userData = {},
  groupRef = {}, groupIdRef = {}, fontIdRef = {},
  roomLists = {},

  roomList = [], userList = [], groupList = [], // Arrays that serve different purposes, notably looking up IDs from names.
  messageIndex = [],

  roomUlFavHtml = '', roomUlMyHtml = '', // A bunch of strings displayed at different points.
  roomUlHtml = '', ulText = '', roomTableHtml = '',

  active = {}; // This is used as a placeholder for JSON objects where code cleanity is nice.



/* Get Cookies */
window.webproDisplay = {
  'theme' : $.getCookie('webpro_theme', 'start'), // Theme (goes into effect in document.ready)
  'fontSize' : $.getCookie('webpro_fontSize', 1), // Font Size (goes into effect in document.ready)
  'settingsBitfield' : $.getCookie('webpro_settings', 8192 + 16777216 + 33554432), // Settings Bitfield (goes into effect all over the place); defaults with US Time, 12-Hour Format, Audio Ding
  'audioVolume' : $.getCookie('webpro_audioVolume', .5)
}



/* Audio File (a hack I placed here just for fun)
 * Essentially, if a cookie has a custom audio file, we play it instead.
 * If not, we will try to play the default, either via ogg, mp3, or wav. */
if (typeof Audio !== 'undefined') {
  var snd = new Audio();

  if ($.cookie('webpro_audioFile') !== null) audioFile = $.cookie('webpro_audioFile');
  else {
    if (snd.canPlayType('audio/ogg; codecs=vorbis')) audioFile = 'images/beep.ogg';
    else if (snd.canPlayType('audio/mp3')) audioFile = 'images/beep.mp3';
    else if (snd.canPlayType('audio/wav')) audioFile = 'images/beep.wav';
    else {
      audioFile = '';
      console.log('Audio Disabled');
    }
  }

  snd.setAttribute('src', audioFile);
  snd.volume = window.webproDisplay.audioVolume; // Audio Volume
}
else {
  var snd = {
    play : function() { return false; },
    volume : 0
  }
}



/* Get the absolute API path.
 * This is used for a few absolute referrences, and is checked with the server. */

var directory = window.location.pathname.split('/').splice(0, window.location.pathname.split('/').length - 2).join('/') + '/', // splice returns the elements removed (and modifies the original array), in this case the first two; the rest should be self-explanatory
  currentLocation = window.location.protocol + '//' + window.location.host + directory + 'webpro/';


/*********************************************************
************************* END ***************************
******************** Base Variables *********************
*********************************************************/






/*********************************************************
************************ START **************************
******************* Static Functions ********************
*********************************************************/

/**
 * Quit with a pretty little message. This can be used whenever an unrecoverable error occurs.
 * 
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
function fim_quit() {
  $('body').replaceWith(window.phrases.errorQuitMessage);
  throw new Error(window.phrases.errorGenericQuit);
}



/**
 * Escapes Data for Server Storage
 * Internally, it will use either encodeURIComponent or escape, with custom replacements.
 * 
 * @param str - The string to encode.
 *
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
function fim_eURL(str) {
  if ('encodeURIComponent' in window) { return window.encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+'); }
  else if ('escape' in window) { return window.escape(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+'); } // Escape is a bit overzealous, but it still works.
  else { throw new Error('You dun goofed.'); }
}



/**
 * Encode data for XML attributes.
 * Really, all this does is make sure backslashes and '"' don't throw things off.
 * 
 * @param str - The string to encode.
 *
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
function fim_eXMLAttr(str) { // Escapes data that is stored via doublequote-encased attributes.
  return str.replace(/\"/g, '&quot;').replace(/\\/g, '\\\\');
}



/**
 * Scrolls the message list to the bottom.
 *
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
function fim_toBottom() { // Scrolls the message list to the bottom.
  document.getElementById('messageList').scrollTop = document.getElementById('messageList').scrollHeight;
}



/**
 * Attempts to "flash" the favicon once called, or stop flashing if already flashing.
 * This has been tested to work in Google Chrome.
 * 
 * @param str - The string to encode.
 *
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
function fim_faviconFlash() { // Changes the state of the favicon from opaque to transparent or similar.
  if ($('#favicon').attr('href') === 'images/favicon.ico') $('#favicon').attr('href', 'images/favicon2.ico');
  else $('#favicon').attr('href', 'images/favicon.ico');
}



/**
 * Helper function to trigger webkit notifications.
 * @param object data - Data to be displayed in the popup.
 * 
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
function messagePopup(data) {
  if (typeof notify != 'undefined' && typeof window.webkitNotifications === 'object') {
    notify.webkitNotify('images/favicon.ico', 'New Message', data);
  }
}



/**
 * Formats a timestamp into a date string.
 * 
 * @param int timestamp - The UNIX timestamp that will be formatted.
 * @param bool full - If true, will include 
 * 
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
function dateFormat(timestamp, full) {
  // This pads zeros to the start of time values.
  _zeropad = function (number, newLength) {
    var numberString = number + '';

    for (var i = numberString.length; i < newLength; i++) { number = '0' + number; }

    return number;
  }


  // Create the date object; set it to the specified timestamp.
  var jsdate = new Date;
  
  // Create the string we will eventually return.
  var timeString = '';

  jsdate.setTime(timestamp * 1000);


  // Time-part object -- this makes the below formats a bit more readable (...and writable).
  _timepart = {
    seconds: function () { return _zeropad(jsdate.getSeconds(), 2); }, // Seconds
    minutes: function () { return _zeropad(jsdate.getMinutes(), 2); }, // Minutes
    hours: function () { return _zeropad((jsdate.getHours() % 12 || 12), 2); }, // 12-Hours
    hours24: function () { return _zeropad(jsdate.getHours(), 2); }, // 24-Hours
    days: function () { return _zeropad(jsdate.getDate(), 2); }, // Days
    months: function () { return _zeropad(jsdate.getMonth() + 1, 2); }, // Month
    years: function () { return jsdate.getFullYear(); } // Year
  };


  // If the message as sent on the previous day, we will force the full code.
  if (!full) {
    var today = new Date;
    var lastMidnight = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 0, 0, 0, 0).getTime() / 1000; // Finds the date of the last midnight as a timestamp.

    if (timestamp < lastMidnight) { full = true; } // If the current time is before the last midnight...
  }

  
  // Format String
  if (full) { // Include the full code.
    timestring += ((settings.usTime ?
      (_timepart.months() + '-' + _timepart.days() + '-' + _timepart.years()) :
      (_timepart.days() + '-' + _timepart.months() + '-' + _timepart.years())) +
    ' ');
  }

  timestring += (settings.twelveHourTime ?
    _timepart.hours() :
    _timepart.hours24()) +
  ':' + _timepart.minutes() + ':' + _timepart.seconds();

  return timestring;
}



/**
 * Formats received message data for display in either the message list or message table.
 * 
 * @param object json - The data to format.
 * @param string format - The format to use.
 *
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
function messageFormat(json, format) {
  var mjson = json.messageData,
    ujson = json.userData,
    data,
    text = mjson.messageText,
    messageTime = fim_dateFormat(mjson.messageTime),
    messageId = mjson.messageId,

    userName = ujson.userName,
    userId = ujson.userId,
    groupFormatStart = ujson.startTag,
    groupFormatEnd = ujson.endTag,
    avatar = ujson.avatar,

    styleColor = ujson.defaultFormatting.color,
    styleHighlight = ujson.defaultFormatting.highlight,
    styleFontface = ujson.defaultFormatting.fontface,
    styleGeneral = ujson.defaultFormatting.general,
    style = '',

    flag = mjson.flags;

  text = text.replace(/\</g, '&lt;').replace(/\>/g, '&gt;').replace(/\n/g, '<br />');

  if (text.length > 1000) {
    text = '[Message Too Long]';
  }
  else {
    switch (flag) {
      // Youtube, etc.
      case 'source':
        text = text.replace(regexs.url, function($1) {
          if ($1.match(regexs.youtubeFull) || $1.match(regexs.youtubeShort)) {
            var code = false;

            if (text.match(regexs.youtubeFull) !== null) { code = text.replace(regexs.youtubeFull, "$8"); }
            else if (text.match(regexs.youtubeShort) !== null) { code = text.replace(regexs.youtubeShort, "$5"); }

            if (code) {
              if (settings.disableVideo) { return '<a href="https://www.youtu.be/' + code + '" target="_BLANK">[Youtube Video]</a>'; }
              else { return '<iframe width="425" height="349" src="https://www.youtube.com/embed/' + code + '?rel=0&wmode=transparent" frameborder="0" allowfullscreen></iframe>'; }
            }
            else { return '[Logic Error]'; }
          }
        });
      break;

      // Image
      case 'image': // We append the parentalAge flags regardless of an images source. It will potentially allow for other sites to use the same format (as far as I know, I am the first to implement the technology, and there are no related standards.)
        if (settings.disableImage) text = '<a href="' + fim_eXMLAttr(text) + '&parentalAge=' + userData[userId].parentalAge + '&parentalFlags=' + userData[userId].parentalFlags.join(',') + '" class="imglink" target="_BLANK">[Image]</a>';
        else text = '<a href="' + text + '" target="_BLANK"><img src="' + fim_eXMLAttr(text) + '" style="max-width: 250px; max-height: 250px;" /></a>';
      break;

      // Video
      case 'video':
        if (settings.disableVideo) text = '<a href="' + fim_eXMLAttr(text) + '" target="_BLANK">[Video]</a>';
        else text = '<video src="' + fim_eXMLAttr(text) + '" controls></video>';
      break;

      // Audio
      case 'audio':
        if (settings.disableVideo) text = '<a href="' + fim_eXMLAttr(text) + '" target="_BLANK">[Video]</a>';
        else text = '<audio src="' + fim_eXMLAttr(text) + '" controls></audio>';
      break;

      // Email Link
      case 'email':
        text = '<a href="mailto: ' + fim_eXMLAttr(text) + '" target="_BLANK">' + text + '</a>';
      break;

      // Various Files and URLs
      case 'url': case 'text': case 'html': case 'archive': case 'other':
        if (text.match(/^(http|https|ftp|data|gopher|sftp|ssh)/)) { // Certain protocols (e.g. "javascript:") could be malicious. Thus, we use a whitelist of trusted protocols instead.
          text = '<a href="' + text + '" target="_BLANK">' + text + '</a>';
        }
        else {
          text = '[Hidden Link]';
        }
      break;

      // Unspecified
      case '':
        // URL Autoparse (will also detect youtube & image)
        text = text.replace(regexs.url, function($1) {
          if ($1.match(regexs.url2)) {
            var $2 = $1.replace(regexs.url2, "$2");
            $1 = $1.replace(regexs.url2, "$1"); // By doing this one second we don't have to worry about storing the variable first to get $2
          }
          else {
            var $2 = '';
          }

          // Youtube Autoparse
          if ($1.match(regexs.youtubeFull) || $1.match(regexs.youtubeShort)) {
            var code = false;

            if (text.match(regexs.youtubeFull) !== null) { code = text.replace(regexs.youtubeFull, "$8"); }
            else if (text.match(regexs.youtubeShort) !== null) { code = text.replace(regexs.youtubeShort, "$5"); }

            if (code) {
              if (settings.disableVideo) { return '<a href="https://www.youtu.be/' + code + '" target="_BLANK">[Youtube Video]</a>'; }
              else { return '<iframe width="425" height="349" src="https://www.youtube.com/embed/' + code + '?rel=0&wmode=transparent" frameborder="0" allowfullscreen></iframe>'; }
            }
            else { return '[Logic Error]'; }
          }
          
          // Image Autoparse
          else if ($1.match(regexs.image)) { return '<a href="' + $1 + '" target="_BLANK" class="imglink">' + (settings.disableImage ? '[IMAGE]' : '<img src="' + $1 + '" style="max-width: 250px; max-height: 250px;" />') + '</a>' + $2; }
          
          // Normal URL
          else { return '<a href="' + $1 + '" target="_BLANK">' + $1 + '</a>' + $2; }
        });

        // "/me" parse
        if (/^\/me/.test(text)) {
          text = text.replace(/^\/me/,'');

          if (settings.disableFormatting) { text = '<span style="padding: 10px;">* ' + userName + ' ' + text + '</span>'; }
          else { text = '<span style="color: red; padding: 10px; font-weight: bold;">* ' + userName + ' ' + text + '</span>'; }
        }
        
        // "/topic" parse
        else if (/^\/topic/.test(text)) {
          text = text.replace(/^\/topic/,'');

          $('#topic').html(text);

          if (settings.disableFormatting) { text = '<span style="padding: 10px;">* ' + userName + ' ' + text + '</span>'; }
          else { text = '<span style="color: red; padding: 10px; font-weight: bold;">* ' + userName + ' changed the topic to "' + text + '".</span>'; }
        }

        // Default Formatting
        if (!settings.disableFormatting) {
          if (styleColor) style += 'color: rgb(' + styleColor + ');';
          if (styleHighlight) style += 'background: rgb(' + styleHighlight + ');'
          if (styleFontface) style += 'font-family: ' + window.serverSettings.formatting.fonts[styleFontface] + ';';

          if (styleGeneral & 256) style += 'font-weight: bold;';
          if (styleGeneral & 512) style += 'font-style: oblique;';
          if (styleGeneral & 1024) style += 'text-decoration: underline;';
          if (styleGeneral & 2048) style += 'text-decoration: line-through;';
          if (styleGeneral & 4096) style += 'text-decoration: overline;';
        }
      break;
    }
  }

  
  /* Format for Table/List Display */
  switch (format) {
    case 'table':
      data = '<tr id="archiveMessage' + messageId + '" style="word-wrap: break-word;"><td>' + groupFormatStart + '<span class="userName userNameTable" data-userId="' + userId + '">' + userName + '</span>' + groupFormatEnd + '</td><td>' + messageTime + '</td><td style="' + style + '" data-messageId="' + messageId + '" data-roomId="' + roomId + '">' + text + '</td><td><a href="javascript:void();" data-messageId="' + messageId + '"  data-roomId="' + roomId + '" class="updateArchiveHere">Show</a></td></tr>';
    break;

    case 'list':
      if (settings.showAvatars) data = '<span id="message' + messageId + '" class="messageLine messageLineAvatar"><span class="userName userNameAvatar" data-userId="' + userId + '" tabindex="1000"><img alt="' + userName + '" src="' + avatar + '" /></span><span style="' + style + '" class="messageText" data-messageId="' + messageId + '" data-roomId="' + roomId + '" data-time="' + messageTime + '" tabindex="1000">' + text + '</span><br />';
      else data = '<span id="message' + messageId + '" class="messageLine"><span class="userName userNameTable" data-userId="' + userId + '" tabindex="1000">' + groupFormatStart + userName + groupFormatEnd + '</span> @ <em>' + messageTime + '</em>: <span style="' + style + '" class="messageText" data-messageid="' + messageId + '" data-roomId="' + roomId + '" tabindex="1000">' + text + '</span><br />';
    break;
  }

  return data;
}



/**
 * Registers a new message in the caches and triggers alerts to users.
 * @param string messageText
 * @param int messageId
 * 
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
function newMessage(messageText, messageId) {
  if (settings.reversePostOrder) $('#messageList').append(messageText); // Put the data at the end of the list if reversePostOrder.
  else $('#messageList').prepend(messageText); // Otherwise, put it at top.

  if (messageId > requestSettings.lastMessage) requestSettings.lastMessage = messageId; // Update the interal lastMessage.

  messageIndex.push(requestSettings.lastMessage); // Update the internal messageIndex array.

  if (messageIndex.length === 100) { // Only list 100 messages in the table at any given time. This prevents memory excess (this usually isn't a problem until around 1,000, but 100 is usually all a user is going to need).
    var messageOut = messageIndex[0];
    $('#message' + messageOut).remove();
    messageIndex = messageIndex.slice(1,99);
  }

  if (settings.reversePostOrder) fim_toBottom();

  if (window.isBlurred) {
    if (settings.audioDing) snd.play();

    window.clearInterval(timers.t3);
    timers.t3 = window.setInterval(faviconFlash, 1000);

    if (typeof window.external === 'object') {
      if (typeof window.external.msIsSiteMode !== 'undefined' && typeof window.external.msSiteModeActivate !== 'undefined') {
        try {
          if (window.external.msIsSiteMode()) { window.external.msSiteModeActivate(); } // Task Bar Flashes
        }
        catch(ex) { } // Ya know, its very weird IE insists on this when the "in" statement works just as well...
      }
    }
  }

  contextMenuParseMessage();
  contextMenuParseUser('#messageList');

  $('.messageLine .messageText, .messageLine .userName, body').unbind('keydown');

  $('.messageLine .messageText').bind('keydown', function(e) {
    if (window.restrictFocus === 'contextMenu') return true;

    if (e.which === 38) { $(this).parent().prev('.messageLine').children('.messageText').focus(); return false; } // Left
    else if (e.which === 37 || e.which === 39) { $(this).parent().children('.userName').focus(); return false; } // Right+Left
    else if (e.which === 40) { $(this).parent().next('.messageLine').children('.messageText').focus(); return false; } // Down
  });

  $('.messageLine .userName').bind('keydown', function(e) {
    if (window.restrictFocus === 'contextMenu') return true;

    if (e.which === 38) { $(this).parent().prev('.messageLine').children('.userName').focus(); return false; } // Up
    else if (e.which === 39 || e.which === 37) { $(this).parent().children('.messageText').focus(); return false; } // Left+Right
    else if (e.which === 40) { $(this).parent() .next('.messageLine').children('.userName').focus(); return false; } // Down
  });

  $('body').bind('keydown', function(e) {
    if ($('input:focus, textarea:focus, button:focus').length === 0) { // Make sure a text-entry field does not have focus
      if (e.which === 192 || e.which === 49) { // "`", "1"
        if (e.ctrlKey || e.shiftKey || e.altKey || e.metaKey) { return true; }
        else { $('.messageLine .messageText').first().focus(); return false; }
      }
      else if (e.which === 32) { $('#messageInput').focus(); return false; } // Space
    }
  });
}



/**
 * Hash Parse for URL-Defined Actions.
 * 
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
function hashParse(options) {
  var urlHash = window.location.hash,
    urlHashComponents = urlHash.split('#'),
    page = '', // String
    i = 0,
    componentPieces = [],
    messageId = 0,
    roomIdLocal,
    messageId;

  for (i = 0; i < urlHashComponents.length; i += 1) {
    if (urlHashComponents[i]) {
      componentPieces = urlHashComponents[i].split('=');
      switch (componentPieces[0]) {
        case 'page': page = componentPieces[1]; break;
        case 'room': roomIdLocal = componentPieces[1]; break;
        case 'message': messageId = componentPieces[1]; break;
      }
    }
  }

  switch (page) {
    case 'archive':
    prepopup = function() {
      popup.archive({
        'roomId' : roomIdLocal,
        'idMin' : messageId - 1
      });
    };
    break;

    case 'settings':
    prepopup = function() { popup.userSettings(); };
    break;
  }

  if (!roomIdLocal && options.defaultRoomId) {
    roomIdLocal = options.defaultRoomId;
  }

  if (roomId !== roomIdLocal) {
    standard.changeRoom(roomIdLocal); // If the room is different than current, change it.
  }
}

/*********************************************************
************************* END ***************************
******************* Static Functions ********************
*********************************************************/






/*********************************************************
************************ START **************************
******************* Variable Setting ********************
*********************************************************/

/* Get Server-Specific Variables
* We Should Not Call This Again */

$.ajax({
  url: directory + 'api/getServerStatus.php?fim3_format=json',
  type: 'GET',
  timeout: 1000,
  dataType: 'json',
  success: function(json) {
    requestSettings.longPolling = json.getServerStatus.serverStatus.requestMethods.longPoll;
    serverSettings = json.getServerStatus.serverStatus; console.log('forum: ' + serverSettings.branding.forumType);

    if (typeof window.EventSource == 'undefined') { requestSettings.serverSentEvents = false; }
    else { requestSettings.serverSentEvents = json.getServerStatus.serverStatus.requestMethods.serverSentEvents; }

    if (json.getServerStatus.serverStatus.installUrl != (window.location.protocol + '//' + window.location.host + directory)) {
      dia.error(window.phrases.errorBadInstall);
    }

    return false;
  },
  error: function() {
    dia.error('Could not obtain serverStatus. All advanced functionality will be disabled.');

    requestSettings.longPolling = false;
    requestSettings.serverSentEvents = false;

    return false;
  }
});



/* Permission Dead Defaults
* Specifically, These All Start False then Change on-Login */
var userPermissions = {
  createRoom : false, privateRoom : false
};

var adminPermissions = {
  modPrivs : false, modCore : false, modUsers : false,
  modImages : false, modCensor : false, modPlugins : false,
  modTemplates: false, modHooks : false, modTranslations : false
};



/* Settings
 * These Are Set Based on Cookies */
var settings = {
  // Formatting
  disableFormatting : (window.webproDisplay.settingsBitfield & 16 ? true : false),
  disableImage : (window.webproDisplay.settingsBitfield & 32 ? true : false),
  disableVideos : (window.webproDisplay.settingsBitfield & 64 ? true : false),

  // Fun Stuff
  reversePostOrder : (window.webproDisplay.settingsBitfield & 1024 ? true : false), // Show posts in reverse?
  showAvatars : (window.webproDisplay.settingsBitfield & 2048 ? true : false), // Use the complex document style?
  audioDing : (window.webproDisplay.settingsBitfield & 8192 ? true : false), // Fire an HTML5 audio ding during each unread message?

  // Accessibility
  disableFx : (window.webproDisplay.settingsBitfield & 262144 ? true : false), // Disable jQuery Effects?
  disableRightClick : (window.webproDisplay.settingsBitfield & 1048576 ? true : false),

  // Localisation
  usTime : (window.webproDisplay.settingsBitfield & 16777216 ? true : false),
  twelveHourTime : (window.webproDisplay.settingsBitfield & 33554432 ? true : false),

  // Experimental Features
  webkitNotifications : (window.webproDisplay.settingsBitfield & 536870912 ? true : false)
};

/* Regexes */
var regexs = {
  url : new RegExp("(" +
    "(http|https|ftp|data|gopher|sftp|ssh)" + // List of acceptable protocols. (so far: "http")
    ":" + // Colon! (so far: "http:")
    "(//|)" + // "//" is optional; this allows for it or nothing. (so far: "http://")
    "((" +
      "(([a-zA-Z0-9]+)\\.)+" + // Anything up to a period (minus forbidden symbols), but optional. (so far: "http://www.")
      "(aero|asia|biz|cat|com|coop|edu|gov|info|int|jobs|mil|mobi|museum|name|net|org|pro|tel|travel|xxx|ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cs|cu|cv|cx|cy|cz|dd|de|dj|dk|dm|do|dz|ec|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|pa|pe|pf]pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|qa|re|ro|rs|ru|рф|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|ss|st|su|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|za|zm|zw)" + // The list of current TLDs. (so far: "http://www.google.com")
      ")|localhost" + // Largely for dev, support "localhost" too.
    ")" +
    "(" +
      ":" + // Colon for the port.
      "([0-9]+)" + // Numeric port.
      "|" + // This is all optional^
    ")" +
    "(" +
      "(\/)" + // The slash! (so far: "http://www.google.com/")
      "([^\\ \\n\\<\\>\\\"]*)" + // Almost anything, except spaces, new lines, <s, >s, or quotes
      "|" + // This is all optional^
    ")" +
  ")", "g"), // Nor the BBCode or HTML symbols.

  url2 : new RegExp("^(.+)([\\\"\\?\\!\\.])$"),

  image : new RegExp("^(.+)\\.(jpg|jpeg|gif|png|svg|svgz|bmp|ico)$"),

  youtubeFull : new RegExp("^(" +
    "(http|https)" + // List of acceptable protocols. (so far: "http")
    ":" + // Colon! (so far: "http:")
    "(//|)" + // "//" is optional; this allows for it or nothing. (so far: "http://")
    "(www\\.|)" + // "www" optional (so far: "http://www")
    "youtube\\.com/" + // Period and domain after "www" (so far: "http://www.youtube.com/")
    "([^\\ ]*?)" + // Anything except spaces
    "(\\?|\\&)" + // ? or &
    "(w|v)=([a-zA-Z0-9\-\_]+)" + // The video ID
  ")$", "i"),

  youtubeShort : new RegExp("^(" +
    "(http|https)" + // List of acceptable protocols. (so far: "http")
    ":" + // Colon! (so far: "http:")
    "(//|)" + // "//" is optional; this allows for it or nothing. (so far: "http://")
    "(www\\.|)" + // "www." optional (so far: "http://www")
    "youtu\\.be/" + // domain after "www." (so far: "http://www.youtu.be/")
    "([a-zA-Z0-9\-\_]+)" + // THe video ID
  ")$", "i")
}

/*********************************************************
************************* END ***************************
******************* Variable Setting ********************
*********************************************************/








/*********************************************************
************************ START **************************
******************** Data Population ********************
*********************************************************/

/**
 * Query and update the various caches.
 * 
 * @param object options - An object containing various options, including:
 ** function 'callback' - A function to execute once populate has finished.
 * 
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
function populate(options) {
  $.when(
    $.ajax({
      url: directory + 'api/getUsers.php?fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
      type: 'GET',
      timeout: 5000,
      cache: false,
      success: function(json) {
        userList = []; // Array // Clear so we don't get repeat values on regeneration.
        userRef = {}; // Object

        for (i in json.getUsers.users) {
          var userName = json.getUsers.users[i].userName,
            userId = json.getUsers.users[i].userId;

          if ('parentalFlags' in json.getUsers.users[i]) { // Normally, only for the logged in user.
            json.getUsers.users[i].parentalFlags = $.map(json.getUsers.users[i].parentalFlags, function (value, key) { return value; }); // The map function here will convert the object to an array.
          }

          userRef[userName] = userId;
          userData[userId] = json.getUsers.users[i];
          userList.push(userName);
        }

        return false;
      },
      error: function() {
        dia.error(window.phrases.errorUsersNotRetrieved);
        fim_quit();

        return false;
      }
    }),


    $.ajax({
      url: directory + 'api/getRooms.php?permLevel=view&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(json) {
        roomList = []; // Array // Clear so we don't get repeat values on regeneration.
        roomIdRef = {}; // Object
        roomRef = {}; // Object
        roomTableHtml = '';
        roomUlHtml = '';
        roomUlMyHtml = '';
        roomUlFavHtml = '';

        active = json.getRooms.rooms;

        for (i in active) {
          var roomName = active[i].roomName,
            roomId = active[i].roomId,
            roomTopic = active[i].roomTopic,
            isAdmin = active[i].permissions.canAdmin,
            isModerator = active[i].permissions.canModerate,
            messageCount = active[i].messageCount,
            isOwner = (active[i].owner === userId ? true : false);

          if (isOwner) { roomUlMyHtml += ulText; }
          else { roomUlHtml += ulText; }

          roomRef[roomName] = roomId;
          roomIdRef[roomId] = {
            'roomId' : roomId,
            'roomName' : roomName,
            'roomTopic' : roomTopic,
            'messageCount' : messageCount,
            'isAdmin' : isAdmin,
            'isModerator' : isModerator,
            'isOwner' : isOwner,
          }
          roomList.push(roomName);


          if (isAdmin) { modRooms[roomId] = 2; }
          else if (isModerator) { modRooms[roomId] = 1; }
          else { modRooms[roomId] = 0; }
        }

        if (!roomList.length) {
          dia.error('You have not be granted access to any rooms. Sorry!');

          disableSender();

          $(document).ready(function() {
            windowDraw();
            windowDynaLinks();
          });
        }

        return false;
      },
      error: function() {
        dia.error(window.phrases.errorRoomsNotRetrieved);
        fim_quit();

        return false;
      }
    }),


    $.ajax({
      url: directory + 'api/getRoomLists.php?fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(json) {
        for (i in json.getRoomLists.roomLists) {
          var listId = json.getRoomLists.roomLists[i].listId,
            listName = json.getRoomLists.roomLists[i].listName;

          roomLists[listName] = new Array();

          for (j in json.getRoomLists.roomLists[i].listEntries) {
            roomLists[listName].push(json.getRoomLists.roomLists[i].listEntries[j]);
          }
        }
        return false;
      },
      error: function() {
        dia.error(window.phrases.errorRoomListsNotRetrieved);

        return false;
      }
    }),


    $.ajax({
      url: directory + 'api/getGroups.php?fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(json) {
        for (i in json.getGroups.groups) {
          var groupName = json.getGroups.groups[i].groupName,
            groupId = json.getGroups.groups[i].groupId;

          groupRef[groupName] = groupId;
          groupIdRef[groupId] = groupName;
          groupList.push(groupName);
        }

        return false;
      },
      error: function() {
        dia.error(window.phrases.errorUserGroupsNotRetrieved); // TODO: Disable certain features.

        return false;
      }
    })
  ).always(function() {
    if (typeof options.callback === 'function') {
      options.callback();
    }

    return true;
  });

  return false;
}

/*********************************************************
************************* END ***************************
******************** Data Population ********************
*********************************************************/








/*********************************************************
************************ START **************************
******************* Content Functions *******************
*********************************************************/

/**
 * Submit a youtube video based on the video's ID.
 * This is a helper function for updateVids.
 * 
 * @param string id - The video's unique ID.
 * 
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
function youtubeSend(id) {
  standard.sendMessage('http://www.youtube.com/watch?v=' + id, 0, 'source');

  $('#textentryBoxYoutube').dialog('close');
}



/**
 * Redraw the search results with the information for a new search string.
 * 
 * @param string id - The video's unique ID.
 * 
 * @todo Support for video sorting.
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
function updateVids(searchPhrase) {
  jQTubeUtil.search(searchPhrase, function(response) {
    var html = "",
      num = 0,
      video;

    for (vid in response.videos) {
      video = response.videos[vid];
      num += 1;

      if (num % 3 === 1) { html += '<tr>'; }
      html += '<td><img src="http://i2.ytimg.com/vi/' + video.videoId + '/default.jpg" style="width: 80px; height: 60px;" /><br /><small><a href="javascript: false(0);" onclick="youtubeSend(&apos;' + video.videoId + '&apos;)">' + video.title + '</a></small></td>';
      if (num % 3 === 0) { html += '</tr>'; }
    }

    if (num % 3 !== 0) { html += '</tr>'; }

    $('#youtubeResults').html(html);

    return false;
  });

  return false;
}


/**
 * This object is used to handle the "list" interface that is used for adding and removing objects from lists through the interface.
 * 
 * @param string id - The video's unique ID.
 * 
 * @todo Pictures in dropdowns, updated interface for user lists
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
autoEntry = {
  addEntry : function(type, source, id) {
    var val,
      type2;

    if (!id) {
      val = $("#" + type + "Bridge").val();
      switch(type) {
        case 'watchRooms': id = roomRef[val]; type2 = 'Room'; break;
        case 'moderators': case 'allowedUsers': case 'ignoreList': id = userRef[val]; type2 = 'User'; break;
        case 'allowedGroups': id = groupRef[val]; type2 = 'Group'; break;
      }
    }
    else {
      switch(type) {
        case 'watchRooms': val = roomIdRef[id].roomName; type2 = 'Room'; break;
        case 'moderators': case 'allowedUsers': case 'ignoreList': val = userData[id].userName; type2 = 'User'; break;
        case 'allowedGroups': val = groupIdRef[id]; type2 = 'Group'; break;
      }
    }

    if (!id) {
      dia.error(type2 + ' does not exist.');
    }
    else {
      var currentRooms = $("#" + type).val().split(",");
      currentRooms.push(id);

      $("#" + type + "List").append("<span id=\"" + type + "SubList" + id + "\">" + val + " (<a href=\"javascript:false(0);\" onclick=\"autoEntry.removeEntry('" + type + "'," + id + ");\">×</a>), </span>");
      $("#" + type).val(currentRooms.toString(","));

      $("#" + type + "Bridge").val('');
    }

    return false;
  },

  removeEntry : function(type, id) {
    var currentRooms = $("#" + type).val().split(","),
      i = 0;

    for (i = 0; i < currentRooms.length; i += 1) {
      if(currentRooms[i] == id) {
        currentRooms.splice(i, 1);
        break;
      }
    }

    $("#" + type).val(currentRooms.toString(","));

    $("#" + type + "SubList" + id).fadeOut(500, function() {
      $(this).remove();
    });

    return false;
  },

  showEntries : function(type, string) {
    var source,
      i = 0;

    if (typeof string === 'object' || typeof string === 'array') { entryList = string; } // String is already not a string! (yeah...) Also, "array" doesn't exist as a type far as I know, but I don't really want to remove it for whatever reason.
    else if (typeof string === 'string' && string.length > 0) { entryList = string.split(','); } // String is a string and not empty.
    else { entryList = []; }

    switch(type) {
      case 'watchRooms': source = roomRef; break;
      case 'moderators': case 'allowedUsers': case 'ignoreList': source = userRef; break;
      case 'allowedGroups': source = groupRef; break;
    }


    for (i = 0; i < entryList.length; i += 1) {
      if (!entryList[i]) { continue; }

      autoEntry.addEntry(type, source, entryList[i]);
    }

    return false;
  }
};

/*********************************************************
************************* END ***************************
******************* Content Functions *******************
*********************************************************/







/*********************************************************
************************ START **************************
********* DOM Event Handling & Window Painting **********
*********************************************************/

/**
 * Draw the interace.
 * 
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
function windowDraw() {
  console.log('Redrawing window.');


  /*** Context Menus ***/
  contextMenuParseRoom();


  /*** Funky Little Dialog Thing ***/
  $('.ui-dialog-titlebar').dblclick(function() {
    var newHeight = $(window).height();
    var newWidth = $(window).width();

    if (($(this).parent().css('width') == newWidth && $(this).parent().css('height') == newHeight) === false) { // Only maximize if not already maximized.
      $(this).parent().css({ width: newWidth, height: newHeight, left: 0, top : 0 });  // Set to the size of the window, realign to the upper-let corner.
      $(this).removeClass('ui-dialog-draggable'); // Remove the drag indicator.
      $(this).parent().draggable("destroy").resizable("destroy"); // Remove the ability to drag and resize.
    }
  });


  /*** General Generic Styling ***/
  $('table > thead > tr:first-child > td:first-child, table > tr:first-child > td:first-child').addClass('ui-corner-tl');
  $('table > thead > tr:first-child > td:last-child, table > tr:first-child > td:last-child').addClass('ui-corner-tr');
  $('table > tbody > tr:last-child > td:first-child, table > tr:last-child > td:first-child').addClass('ui-corner-bl');
  $('table > tbody > tr:last-child > td:last-child, table > tr:last-child > td:last-child').addClass('ui-corner-br');

  $('button').button();
  $('legend').addClass('ui-widget-header').addClass('ui-corner-all'); // Can these combine?
  $('fieldset').addClass('ui-widget ui-widget-content');

  $('thead').addClass('ui-widget-header');
  $('tbody').addClass('widget ui-widget-content');


  // Disable the chatbox if the user is not allowed to post.
  if (roomId && (userId | anonId)) { /* TODO */ } // The user is able to post.
  else { disableSender(); } // The user is _not_ able to post.


  /*** Call Resize ***/
  windowResize();


  /*** Return ***/
  return false;
}


/**
 * Redraws all links. This is required when changing rooms, users, etc.
 * 
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
function windowDynaLinks() {
  var noAdminCounter = 0, // This is probably a bad way of doing what we'll do, but meh.
    noModCounter = 0; // Same as above...


  // Show All Links At Start, Erasing the Effects of Below
  $('#moderateCat').show();
  $('#moderateCat').next().children().children().show(); // LIs
  $('#quickCat').next().children().children().show(); // LIs
  $('#moderateCat').next().children().children().children().show(); // Admin LIs
  $('#userMenu li').show(); // Context LIs


  // Hide DOM Elements Based on User's Permissions
  if (!userPermissions.createRoom) { $('li > #createRoom').parent().hide(); }
  if (!userPermissions.privateRoom) { $('li > #privateRoom').parent().hide(); $('#userMenu a[data-action="private_im"]').parent().hide(); }
  if (!adminPermissions.modUsers) { $('li > #modUsers').parent().hide(); $('ul#userMenu > li > a[data-action="ban"]').hide(); noAdminCounter += 1; }
  if (!adminPermissions.modRooms) { $('ul#roomMenu > li > a[data-action="delete"]').hide(); noAdminCounter += 1; }
  if (!adminPermissions.modImages) { $('li > #modImages').parent().hide(); $('ul#messageMenu > li > a[data-action="deleteimage"]').hide(); noAdminCounter += 1; }
  if (!adminPermissions.modCensor) { $('li > #modCensor').parent().hide(); noAdminCounter += 1; }
  if (!adminPermissions.modTemplates) { $('li > #modPhrases').parent().hide(); noAdminCounter += 1; }
  if (!adminPermissions.modTemplates) { $('li > #modTemplates').parent().hide(); noAdminCounter += 1; }
  if (!adminPermissions.modPrivs) { $('li > #modPrivs').parent().hide(); noAdminCounter += 1; }
  if (!adminPermissions.modHooks) { $('li > #modHooks').parent().hide(); noAdminCounter += 1; }
  if (!adminPermissions.modCore) { $('li > #modCore').parent().hide(); noAdminCounter += 1; }

  if (roomId) {
    if (roomId.toString().substr(0,1) === 'p' || modRooms[roomId] < 1) { $('li > #kick').parent().hide(); $('li > #manageKick').parent().hide(); $('#userMenu a[data-action="kick"]').parent().hide(); $('ul#messageMenu > li > a[data-action="delete"], ul#messageMenuImage > li > a[data-action="delete"], ul#messageMenuLink > li > a[data-action="delete"], ul#messageMenuVideo > li > a[data-action="delete"]').hide(); noModCounter += 2; }
    if (roomId.toString().substr(0,1) === 'p' || modRooms[roomId] < 2) { $('li > #editRoom').parent().hide(); noModCounter += 1; }
  }
  else {
    $('li > #editRoom').parent().hide(); noModCounter += 1; $('li > #kick').parent().hide(); $('li > #manageKick').parent().hide(); $('#userMenu a[data-action="kick"]').parent().hide();
  }

  // Remove Link Categories If They Are to Appear Empty (the counter is incremented in the above code block)
  if (noAdminCounter === 8) { $('li > #modGeneral').parent().hide(); }
  if (noModCounter === 3 && noAdminCounter === 8) { $('#moderateCat').hide(); }

  // Show Login or Logout Only
  if (userId && !anonId) { $('li > #logout').parent().show(); $('li > #login').parent().hide(); }
  else { $('li > #login').parent().hide(); $('li > #logout').parent().hide(); }


  // Room Lists (this is a bit slow -- we should optimise TODO)
  $('#roomListLong > ul').html('<li>My Rooms<ul id="myRooms1"></ul></li>');
  $('#roomListShort > ul').html('<li>My Rooms<ul id="myRooms2"></ul></li>');

  for (i in roomIdRef) {
    if (roomIdRef[i].isOwner) {
      $('#myRooms1, #myRooms2').append('<li>' + roomIdRef[i].roomName + '</li>');
    }
  }

  for (i in roomLists) {
    $('#roomListLong > ul').append('<li>' + window.phrases.roomListNames[i] + '<ul id="roomList' + i + '"></ul></li>');

    for (j = 0; j < roomLists[i].length; j++) {
      $('#roomList' + i).append('<li><a href="#room=' + roomIdRef[roomLists[i][j]].roomId + '" class="room" data-roomId="' + roomIdRef[roomLists[i][j]].roomId + '">' + roomIdRef[roomLists[i][j]].roomName + '</a></li>');
    }
  }
}



/**
 * Disables the input boxes.
 * 
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
function disableSender() {
  $('#messageInput').attr('disabled','disabled'); // Disable input boxes.
  $('#icon_url').button({ disabled : true }); // "
  $('#icon_submit').button({ disabled : true }); // "
  $('#icon_reset').button({ disabled : true }); // "
}



/**
 * Enables the input boxes.
 * 
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
function enableSender() {
  $('#messageInput').removeAttr('disabled'); // Make sure the input is not disabled.
  $('#icon_url').button({ disabled : false }); // "
  $('#icon_submit').button({ disabled : false }); // "
  $('#icon_reset').button({ disabled : false }); // "
}



/**
 * (Re-)Parse the "user" context menus.
 * 
 * @param container - A jQuery selector that can be used to restrict the results. For example, specifying "#funStuff" would only reparse menus that are within the "#funStuff" node.
 * 
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
function contextMenuParseUser(container) {
  $((container ? container + ' ' : '') + '.userName').contextMenu({
    menu: 'userMenu',
    altMenu : settings.disableRightClick
  },
  function(action, el) {
    var userId = $(el).attr('data-userId'),
      userName = '',
      avatarUrl = '',
      profileUrl = '';

    switch(action) {
      case 'profile':
      dia.full({
        title : 'User Profile',
        id : 'messageLink',
        content : (userData[userId].profile ? '<iframe src="' + userData[userId].profile + '" style="width: 100%; height: 90%;" /><br /><a href="' + userData[userId].profile + '" target="_BLANK">Visit The Page Directly</a>' : 'The user has not yet registered a profile.'),
        width: $(window).width() * .8,
        height: $(window).height() * .9,
      });
      break;

      case 'private_im': standard.privateRoom(userId); break;
      case 'kick': popup.kick(userId, roomId); break;
      case 'ban': standard.banUser(userId); break; // TODO
      case 'ignore': standard.ignoreUser(userId); break; // TODO
    }
  });
}



/**
 * (Re-)Parse the "message" context menus, including menus for embedded images and links.
 * 
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
function contextMenuParseMessage() {
  $('.messageLine .messageText').contextMenu({
    menu: 'messageMenu',
    altMenu : settings.disableRightClick
  },
  function(action, el) {
    var messageId = $(el).attr('data-messageId'),
      roomId = $(el).attr('data-roomId');

    switch(action) {
      case 'delete':
      dia.confirm({
        text : 'Are you sure you want to delete this message?',
        'true' : function() {
          standard.deleteMessage(messageId);

          $(el).parent().fadeOut();

          return false;
        }
      });
      break;

      case 'link':
      dia.full({
        title : 'Link to this Message',
        id : 'messageLink',
        content : 'This message can be bookmarked using the following archive link:<br /><br /><input type="text" value="' + currentLocation + '#page=archive#room=' + roomId + '#message=' + messageId + '" style="width: 100%;" />',
        width: 600,
      });
      break;
    }

    return false;
  });

  $('.messageLine .messageText img').contextMenu({
    menu: 'messageMenuImage',
    altMenu : settings.disableRightClick
  },
  function(action, el) {
    var messageId = $(el).parent().attr('data-messageId'),
      roomId = $(el).parent().attr('data-roomId'),
      src = $(el).attr('src');

    switch(action) {
      case 'url':
      dia.full({
        title : 'Copy Image URL',
        content : '<img src="' + src + '" style="max-width: 550px; max-height: 550px; margin-left: auto; margin-right: auto; display: block;" /><br /><br /><input type="text" name="url" value="' + src +  '" style="width: 100%;" />',
        width : 800,
        position : 'top',
        oF : function() {
          $('input[name=url]', this).first().focus();
        }
      });
      break;

      case 'delete':
      dia.confirm({
        text : 'Are you sure you want to delete this message?',
        'true' : function() {
          standard.deleteMessage(messageId);

          $(el).parent().fadeOut();
        }
      });
      break;

      case 'link':
      dia.full({
        title : 'Link to this Message',
        id : 'messageLink',
        content : 'This message can be bookmarked using the following archive link:<br /><br /><input type="text" value="' + currentLocation + '/#page=archive#room=' + roomId + '#message=' + messageId + '" style="width: 100%;" />',
        width: 600,
      });
      break;
    }

    return false;
  });

  $('.messageLine .messageText a').not('.imglink').contextMenu({
    menu: 'messageMenuLink',
    altMenu : settings.disableRightClick
  },
  function(action, el) {
    var messageId = $(el).parent().attr('data-messageId'),
      roomId = $(el).parent().attr('data-roomId'),
      src = $(el).attr('href');

    switch(action) {
      case 'url':
      dia.full({
        title : 'Copy URL',
        position : 'top',
        content : '<iframe style="width: 100%; display: none; height: 0px;"></iframe><a href="javascript:void(0);" onclick="$(this).prev().attr(\'src\',\'' + src.replace(/\'/g, "\\'").replace(/\"/g, '\\"') + '\').show().animate({height : \'80%\'}, 500); $(this).hide();">View<br /></a><br /><input type="text" name="url" value="' + src.replace(/\"/g, '\\"') +  '" style="width: 100%;" />',
        width : 800,
        oF : function() {
          $('input[name=url]', this).first().focus();
        }
      });
      break;

      case 'delete':
      dia.confirm({
        text : 'Are you sure you want to delete this message?',
        'true' : function() {
          standard.deleteMessage(messageId);

          $(el).parent().fadeOut();
        }
      });
      break;

      case 'link':
        dia.full({
          title : 'Link to this Message',
          id : 'messageLink',
          content : 'This message can be bookmarked using the following archive link:<br /><br /><input type="text" value="' + currentLocation + '/#page=archive#room=' + roomId + '#message=' + messageId + '" style="width: 100%;" />',
          width: 600,
        });
      break;
    }

    return false;
  });
}



/**
 * (Re-)Parse the "room" context menus.
 * 
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
function contextMenuParseRoom() {
  $('.room').contextMenu({
    menu: 'roomMenu',
    altMenu : settings.disableRightClick
  },
  function(action, el) {
    roomId = $(el).attr('data-roomId');

    switch(action) {
      case 'delete':
      dia.confirm({
        text : 'Are you sure you want to delete this room?',
        'true' : function() {
          standard.deleteRoom(roomId);

          $(el).parent().fadeOut();

          return false;
        }
      });
      break;

      case 'edit': popup.editRoom(roomId); break;
      case 'archive': popup.archive({roomId : roomId}); break;
      case 'enter': standard.changeRoom(roomId); break;
    }

    return false;
  });

  return false;
}



/**
 * Blast-off.
 * There's a million things that happen here
 * 
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
$(document).ready(function() {
  // Start by injecting the stylesheets into the DOM.
  $('head').append('<link rel="stylesheet" id="stylesjQ" type="text/css" href="client/css/' + window.webproDisplay.theme + '/jquery-ui-1.8.16.custom.css" /><link rel="stylesheet" id="stylesFIM" type="text/css" href="client/css/' + window.webproDisplay.theme + '/fim.css" /><link rel="stylesheet" type="text/css" href="client/css/stylesv2.css" />');


  if (window.webproDisplay.fontSize) $('body').css('font-size', window.webproDisplay.fontSize + 'em');


  if ($.cookie('webpro_userId') > 0) {
    standard.login({
      userId : $.cookie('webpro_userId'),
      password : $.cookie('webpro_password'),
      finish : function() {
        if (!userId) { // The user is not actively logged in.
          popup.login();
        }
      }
    });
  }
  else {
    popup.login();
  }


  if (settings.disableFx) {
    jQuery.fx.off = true;
  }


  /*** Time Tooltip ***/
  if (settings.showAvatars) {
    $('.messageText').tipTip({
      activate: 'hover',
      attribute: 'data-time'
    });
  }


  /*** Hover Tooltip ***/
  $('.userName').ezpz_tooltip({
    contentId: 'tooltext',
    beforeShow: function(content, el) {
      var userId = $(el).attr('data-userId');

      if (userId != $('#tooltext').attr('data-lastuserId')) {
        $('#tooltext').attr('data-lastuserId', userId);

        content.html('<div style="width: 400px;">' + (userData[userId].avatar.length > 0 ? '<img alt="" src="' + userData[userId].avatar + '" style="float: left; max-height: 200px; max-width: 200px;" />' : '') + '<span class="userName" data-userId="' + userId + '">' + userData[userId].startTag + userData[userId].userName + userData[userId].endTag + '</span>' + (userData[userId].userTitle.length > 0 ? '<br />' + userData[userId].userTitle : '') + '<br /><em>Posts</em>: ' + userData[userId].posts + '<br /><em>Member Since</em>: ' + userData[userId].joinDate + '</div>');
      }
    }
  });


  /*** Create the Accordion Menu ***/
  $('#menu').accordion({
    autoHeight: false,
    navigation: true,
    clearStyle: true,
    active : Number($.cookie('webpro_menustate')) - 1,
    change: function(event, ui) {
      var sid = ui.newHeader.children('a').attr('data-itemId');

      $.cookie('webpro_menustate', sid, { expires: 14 });
    }
  });


  /*** Image Buttons! ***/
  $("#icon_help").button({ icons: {primary:'ui-icon-help'} });
  $("#icon_note").button({ icons: {primary:'ui-icon-note'} });
  $("#icon_settings").button({ icons: {primary:'ui-icon-wrench'} });
  $("#icon_url").button({ icons: {primary: 'ui-icon-link'} });
  $("#icon_submit").button({ icons: {primary: 'ui-icon-circle-check'} });
  $("#icon_reset").button({ icons: {primary: 'ui-icon-circle-close'} });

  $("#imageUploadSubmitButton").button("option", "disabled", true);


  /*** Button Click Events ***/
  $('#icon_note, #messageArchive, a#editRoom').unbind('click'); // Cleanup

  $('#icon_note, #messageArchive').bind('click', function() { popup.archive({roomId : roomId}); }); // Archive
  $('a#editRoom').bind('click', function() { popup.editRoom(roomId); }); // Edit Room
  $('#login').bind('click', function() { popup.login(); }); // Login
  $('#logout').bind('click', function() { standard.logout(); popup.login(); }); // Logout
  $('a#kick').bind('click', function() { popup.kick(); }); // Kick
  $('a#privateRoom').bind('click', function() { popup.privateRoom(); }); // Private Room
  $('a#manageKick').bind('click', function() { popup.manageKicks(); }); // Manage Kicks
  $('a#online').bind('click', function() { popup.online(); }); // Online
  $('a#createRoom').bind('click', function() { popup.editRoom();}); // Create Room
  $('a.editRoomMulti').bind('click', function() { popup.editRoom($(this).attr('data-roomId')); }); // Edit Room
  $('#icon_help').bind('click', function() { popup.help(); }); // Help
  $('#roomList, #roomList2').bind('click', function() { popup.selectRoom(); }); // Room List
  $('#viewStats').bind('click', function() { popup.viewStats(); }); // Room Post Stats
  $('#copyrightLink').bind('click', function() { popup.copyright(); }); // Copyright & Credits
  $('#icon_settings, #changeSettings, a.changeSettingsMulti').bind('click', function() { popup.userSettings(); }); // User Settings
  $('#viewUploads').bind('click', function() { popup.viewUploads(); }); // View My Uploads
  $('#icon_url').bind('click', function() { popup.insertDoc('url'); }); // Upload

  // Room Shower Thing
  $('#showMoreRooms').bind('click', function() { $('#roomListShort').slideUp(); $('#roomListLong').slideDown(); });
  $('#showFewerRooms').bind('click', function() { $('#roomListLong').slideUp(); $('#roomListShort').slideDown(); });


  /*** Youtube Videos for Uploads ***/
  jQTubeUtil.init({
    key: 'AI39si5_Dbv6rqUPbSe8e4RZyXkDM3X0MAAtOgCuqxg_dvGTWCPzrtN_JLh9HlTaoC01hCLZCxeEDOaxsjhnH5p7HhZVnah2iQ',
    orderby: 'relevance',  // *optional -- 'viewCount' is set by default
    time: 'this_month',   // *optional -- 'this_month' is set by default
    maxResults: 20   // *optional -- defined as 10 results by default
  });


  /*** Send Messages, Yay! ***/
  $('#sendForm').bind('submit', function() {
    var message = $('textarea#messageInput').val();

    if (message.length === 0) { dia.error('Please enter your message.'); }
    else {
      standard.sendMessage(message); // Send the messaage
      $('textarea#messageInput').val(''); // Clear the textbox
    }

    return false;
  });


  /*** Process Enter for Message Input ***/
  $('#messageInput').bind('keydown', function(e) {
    if (e.keyCode === 13 && !e.shiftKey) { // Enter w/o shift
      $('#sendForm').submit();
      return false;
    }

    return true;
  });


  /*** Window Manipulation (see below) ***/
  $(window).bind('resize', windowResize);
  $(window).bind('blur', windowBlur);
  $(window).bind('focus', windowFocus);
  $(window).bind('hashchange', hashParse);


  return false;
});

/*********************************************************
************************* END ***************************
********* DOM Event Handling & Window Painting **********
*********************************************************/








/*********************************************************
************************ START **************************
***** Window Manipulation and Multi-Window Handling *****
*********************************************************/

function windowResize() {
  var windowWidth = $(window).width(); // Get the browser window "viewport" width, excluding scrollbars.
  var windowHeight = $(window).height(); // Get the browser window "viewport" height, excluding scrollbars.

  $('#messageList').css('height', (windowHeight - 250)); // Set the message list height to fill as much of the screen that remains after the textarea is placed.
  $('#messageList').css('max-width', ((windowWidth - 20) * .75)); // Prevent box-stretching. This is common on... many chats.

  $('body').css('min-height', windowHeight); // Set the body height to equal that of the window; this fixes many gradient issues in theming.
  
  $('.ui-widget-overlay').each(function() {
    $(this).height(windowHeight);
    $(this).width(windowWidth);
  });
}

function windowBlur() {
  window.isBlurred = true;
}

function windowFocus() {
  window.isBlurred = false;
  window.clearInterval(timers.t3);

  $('#favicon').attr('href', favicon);
}

/*********************************************************
************************* END ***************************
***** Window Manipulation and Multi-Window Handling *****
*********************************************************/