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

/* Global Definitions
* These are used throughout all other Javascript files, so are defined before all other FIM-specific files.


* Needed Changes:
  * Consistency in use of templates+raw HTML.
  * Password Encryption */






/*********************************************************
************************ START **************************
******************** Base Variables *********************
*********************************************************/


/* Requirements */

if (false === ('btoa' in window)) {
  window.location.href = 'browser.php';

  throw new Error('Your browser does not seem to support Base64 operations. The script has exited.');
}
else if (typeof Date === 'undefined') {
  window.location.href = 'browser.php';

  throw new Error('Your browser does not seem to support the Date object. The script has exited.');
}
else if (typeof Math === 'undefined') {
  window.location.href = 'browser.php';

  throw new Error('Your browser does not seem to support the Math object. The script has exited.');
}



/* Common Variables */

var userId, // The user ID who is logged in.
  roomId, // The ID of the room we are in.
  sessionHash, // The session hash of the active user.
  anonId, // ID used to represent anonymous posters.
  prepopup;



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
  userRef = {}, userIdRef = {}, groupRef = {},
  groupIdRef = {}, fontIdRef = {}, uploadFileTypes = {},

  roomList = [], userList = [], groupList = [], // Arrays that serve different purposes, notably looking up IDs from names.
  messageIndex = [],

  roomUlFavHtml = '', roomUlMyHtml = '', roomUlPrivHtml = '', // A bunch of strings displayed at different points.
  roomUlHtml = '', ulText = '', roomTableHtml = '',
  roomSelectHtml = '', userSelectHtml = '', fontSelectHtml = '',

  active = {}; // This is used as a placeholder for JSON objects where code cleanity is nice.



/* Get Cookies */
var theme = $.cookie('webpro_theme'), // Theme (goes into effect in document.ready)
  fontsize = $.cookie('webpro_fontsize'), // Font Size (goes into effect in document.ready)
  settingsBitfield = $.cookie('webpro_settings'); // Settings Bitfield (goes into effect all over the place)

if (theme === null) theme = 'start';
if (fontsize === null) fontsize = 1;
if (settingsBitfield === null) settingsBitfield = 8192 + 16777216 + 33554432; // US Time, 12-Hour Format, Audio Ding


// Audio File (a hack I placed here just for fun)
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


  // Audio Volume
  if ($.cookie('webpro_audioVolume') !== null) snd.volume = ($.cookie('webpro_audioVolume') / 100);
  else snd.volume = .5;
}
else {
  var snd = {
    play : function() { return false; },
    volume : 0
  }
}


/* Get the absolute API path.
* TODO: Define this in a more "sophisticated manner". */

var directory = window.location.pathname.split('/').splice(0, window.location.pathname.split('/').length - 2).join('/') + '/', // splice returns the elements removed (and modifies the original array), in this case the first two; the rest should be self-explanatory
  currentLocation = window.location.origin + directory + 'webpro/';


/*********************************************************
************************* END ***************************
******************** Base Variables *********************
*********************************************************/








/*********************************************************
************************ START **************************
******************* Static Functions ********************
*********************************************************/

function fim_eURL(str) { // Escapes data for server storage.
  if ('encodeURIComponent' in window) {
    return window.encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+');
  }
  else if ('escape' in window) { // Escape is a bit overzealous, but it still works.
    return window.escape(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+');
  }
  else {
    window.location.href = 'browser.php';

    throw new Error('Your browser does not seem to support Base64 operations. The script has exited.');
  }
}

function fim_eXMLAttr(str) { // Escapes data that is stored via doublequote-encased attributes.
  return str.replace(/\"/g, '&quot;').replace(/\\/g, '\\\\');
}

function toBottom() { // Scrools the message list to the bottom.
  document.getElementById('messageList').scrollTop = document.getElementById('messageList').scrollHeight;
}

function faviconFlash() { // Changes the state of the favicon from opaque to transparent or similar.
  if ($('#favicon').attr('href') === 'images/favicon.ico') $('#favicon').attr('href', 'images/favicon2.ico');
  else $('#favicon').attr('href', 'images/favicon.ico');
}

function messageFormat(json, format) {
  var mjson = json.messageData,
    ujson = json.userData,
    data,
    text = mjson.messageText,
    messageTime = date(mjson.messageTime),
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
      case 'image':
      if (settings.disableImage) text = '<a href="' + fim_eXMLAttr(text) + '" class="imglink" target="_BLANK">[Image]</a>';
      else text = '<a href="' + text + '" target="_BLANK"><img src="' + fim_eXMLAttr(text) + '" style="max-width: 250px; max-height: 250px;" /></a>';
      break;

      case 'video':
      if (settings.disableVideo) text = '<a href="' + fim_eXMLAttr(text) + '" target="_BLANK">[Video]</a>';
      else text = '<video src="' + fim_eXMLAttr(text) + '" controls></video>';
      break;

      case 'audio':
      if (settings.disableVideo) text = '<a href="' + fim_eXMLAttr(text) + '" target="_BLANK">[Video]</a>';
      else text = '<audio src="' + fim_eXMLAttr(text) + '" controls></audio>';
      break;

      case 'email':
      text = '<a href="mailto: ' + fim_eXMLAttr(text) + '" target="_BLANK">' + text + '</a>';
      break;

      case 'url': case 'text': case 'html': case 'archive': case 'other':
      if (text.match(/^(http|https|ftp|data|gopher|sftp|ssh)/)) { // Certain protocols (e.g. "javascript:") could be malicious. Thus, we use a whitelist of trusted protocols instead.
        text = '<a href="' + text + '" target="_BLANK">' + text + '</a>';
      }
      else {
        text = '[Hidden Link]';
      }
      break;

      case '': console.log(text);
      text = text.replace(regexs.url, function($1) {
        if ($1.match(regexs.url2)) {
          var $2 = $1.replace(regexs.url2, "$2");
          $1 = $1.replace(regexs.url2, "$1"); // By doing this one second we don't have to worry about storing the variable first to get $2
        }
        else {
          var $2 = '';
        }

        if ($1.match(regexs.youtubeFull) || $1.match(regexs.youtubeShort)) {
          var code = false;

          if (text.match(regexs.youtubeFull) !== null) {
            code = text.replace(regexs.youtubeFull, "$8");
          }
          else if (text.match(regexs.youtubeShort) !== null) {
            code = text.replace(regexs.youtubeShort, "$5");
          }

          if (code) {
            if (settings.disableVideo) {
              return '<a href="https://www.youtu.be/' + code + '" target="_BLANK">[Youtube Video]</a>';
            }
            else {
              return '<iframe width="425" height="349" src="https://www.youtube.com/embed/' + code + '?rel=0&wmode=transparent" frameborder="0" allowfullscreen></iframe>';
            }
          }
          else {
            return '[Logic Error]';
          }
        }
        else if ($1.match(regexs.image)) {
          return '<a href="' + $1 + '" target="_BLANK" class="imglink">' + (settings.disableImage ? '[IMAGE]' : '<img src="' + $1 + '" style="max-width: 250px; max-height: 250px;" />') + '</a>' + $2;
        }
        else {
          return '<a href="' + $1 + '" target="_BLANK">' + $1 + '</a>' + $2;
        }
      });

      if (/^\/me/.test(text)) {
        text = text.replace(/^\/me/,'');

        if (settings.disableFormatting) {
          text = '<span style="padding: 10px;">* ' + userName + ' ' + text + '</span>';
        }
        else {
          text = '<span style="color: red; padding: 10px; font-weight: bold;">* ' + userName + ' ' + text + '</span>';
        }
      }
      else if (/^\/topic/.test(text)) {
        text = text.replace(/^\/topic/,'');

        $('#topic').html(text);

        if (settings.disableFormatting) {
          text = '<span style="padding: 10px;">* ' + userName + ' ' + text + '</span>';
        }
        else {
          text = '<span style="color: red; padding: 10px; font-weight: bold;">* ' + userName + ' changed the topic to "' + text + '".</span>';
        }
      }

      if (!settings.disableFormatting) {
        style = 'color: rgb(' + styleColor + '); background: rgb(' + styleHighlight + '); font-family: ' + fontIdRef[styleFontface] + ';';

        if (styleGeneral & 256) style += 'font-weight: bold;';
        if (styleGeneral & 512) style += 'font-style: oblique;';
        if (styleGeneral & 1024) style += 'text-decoration: underline;';
        if (styleGeneral & 2048) style += 'text-decoration: line-through;';
        if (styleGeneral & 4096) style += 'text-decoration: overline;';
      }
      break;
    }
  }

  switch (format) {
    case 'table':
    data = '<tr id="archiveMessage' + messageId + '"><td>' + groupFormatStart + '<span class="userName userNameTable" data-userId="' + userId + '">' + userName + '</span>' + groupFormatEnd + '</td><td>' + messageTime + '</td><td style="' + style + '" data-messageId="' + messageId + '" data-roomId="' + roomId + '">' + text + '</td><td><a href="javascript:void();" data-messageId="' + messageId + '"  data-roomId="' + roomId + '" class="updateArchiveHere">Show</a></td></tr>';
    break;

    case 'list':
    if (settings.showAvatars) {
      data = '<span id="message' + messageId + '" class="messageLine messageLineAvatar"><span class="userName userNameAvatar" data-userId="' + userId + '" tabindex="1000"><img alt="' + userName + '" src="' + avatar + '" /></span><span style="' + style + '" class="messageText" data-messageId="' + messageId + '" data-roomId="' + roomId + '" data-time="' + messageTime + '" tabindex="1000">' + text + '</span><br />';
    }
    else {
      data = '<span id="message' + messageId + '" class="messageLine"><span class="userName userNameTable" data-userId="' + userId + '" tabindex="1000">' + groupFormatStart + userName + groupFormatEnd + '</span> @ <em>' + messageTime + '</em>: <span style="' + style + '" class="messageText" data-messageid="' + messageId + '" data-roomId="' + roomId + '" tabindex="1000">' + text + '</span><br />';
    }
    break;
  }


  return data;
}

function fileFormat(container, file) {

}


/* ? */
function newMessage() {
  if (settings.reversePostOrder) {
    toBottom();
  }

  if (window.isBlurred) {
    if (settings.audioDing) {
      snd.play();
    }

    window.clearInterval(timers.t3);
    timers.t3 = window.setInterval(faviconFlash, 1000);

    if (typeof window.external === 'object') {
      if (typeof window.external.msIsSiteMode !== 'undefined' && typeof window.external.msSiteModeActivate !== 'undefined') {
        try {
          if (window.external.msIsSiteMode()) {
            window.external.msSiteModeActivate(); // Task Bar Flashes
          }
        }
        catch(ex) {
          // Ya know, its very weird IE insists on this when the "in" statement works just as well...
        }
      }
    }
  }

  contextMenuParseMessage();
  contextMenuParseUser('#messageList');

  $('.messageLine .messageText, .messageLine .userName, body').unbind('keydown');

  $('.messageLine .messageText').bind('keydown', function(e) {
    if (window.restrictFocus === 'contextMenu') return true;

    if (e.which === 38) { // Left
      $(this).parent().prev('.messageLine').children('.messageText').focus();
      return false;
    }
    else if (e.which === 37 || e.which === 39) { // Right+Left
      $(this).parent().children('.userName').focus();
      return false;
    }
    else if (e.which === 40) { // Down
      $(this).parent().next('.messageLine').children('.messageText').focus();
      return false;
    }
  });

  $('.messageLine .userName').bind('keydown', function(e) {
    if (window.restrictFocus === 'contextMenu') return true;

    if (e.which === 38) { // Up
      $(this).parent().prev('.messageLine').children('.userName').focus();
      return false;
    }
    else if (e.which === 39 || e.which === 37) { // Left+Right
      $(this).parent().children('.messageText').focus();
      return false;
    }
    else if (e.which === 40) { // Down
      $(this).parent() .next('.messageLine').children('.userName').focus();
      return false;
    }
  });

  $('body').bind('keydown', function(e) { console.log($('input:focus, textarea:focus, button:focus').length);
    if ($('input:focus, textarea:focus, button:focus').length === 0) { // Make sure a text-entry field does not have focus
      if (e.which === 192 || e.which === 49) { // "`", "1"
        if (e.ctrlKey || e.shiftKey || e.altKey || e.metaKey) {
          return true;
        }
        else {
          $('.messageLine .messageText').first().focus();
          return false;
        }
      }
      else if (e.which === 32) { // Space
        $('#messageInput').focus();
        return false;
      }
    }
  });
}


function messagePopup(data) {
  if (typeof notify != 'undefined' && typeof window.webkitNotifications === 'object') {
    notify.webkitNotify('images/favicon.ico', 'New Message', data);
  }
}

function date(timestamp, full) {
  // This pads zeros to the start of time values.
  _zeropad = function (number, newLength) {
    var numberString = number + '';

    for (var i = numberString.length; i < newLength; i++) {
      number = '0' + number;
    }

    return number;
  }


  // Create the date object; set it to the specified timestamp.
  var jsdate = new Date;
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


  if (!full) { // Short code
    var currentTime = new Date(), // Create a new date object so we can get the current time.
      currentTimeSeconds = Math.floor(currentTime.getTime() / 1000), // Get the unix timestamp from the current time, adjusted for the current timezone.
      lastMidnight = (currentTimeSeconds - (currentTimeSeconds % 86400)) + (currentTime.getTimezoneOffset() * 60); // Using some cool math (look it up if you're not familiar), we determine the distance from the last even day, then get the time of the last even day itself. This is the midnight referrence point. After all that, we add the timezone adjust in seconds; this is important because we're interested in the local midnight, not the UTC one.

    if (timestamp < lastMidnight) { // If the current time is before the last midnight...
      full = true;
    }
  }

  if (full) { // Long code
    var timestring = (settings.usTime ?
      (_timepart.months() + '-' + _timepart.days() + '-' + _timepart.years()) :
      (_timepart.days() + '-' + _timepart.months() + '-' + _timepart.years())) +
    ' ' + (settings.twelveHourTime ?
      _timepart.hours() :
      _timepart.hours24()) +
    ':' + _timepart.minutes() + ':' + _timepart.seconds();
  }
  else {
    var timestring = (settings.twelveHourTime ?
      _timepart.hours() :
      _timepart.hours24()) +
    ':' + _timepart.minutes() + ':' + _timepart.seconds();
  }

  return timestring;
}


/* URL-Defined Actions */

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
        case 'page':
        page = componentPieces[1];
        break;

        case 'room':
        roomIdLocal = componentPieces[1];
        break;

        case 'message':
        messageId = componentPieces[1];
        break;
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
    prepopup = function() {
      popup.userSettings();
    };
    break;
  }

  if (!roomIdLocal && options.defaultRoomId) {
    roomIdLocal = options.defaultRoomId;
  }

  if (roomId !== roomIdLocal) {
    standard.changeRoom(roomIdLocal); // If the room is different than current, change it.
  }
}

if (typeof console !== 'object' || typeof console.log !== 'function') {
  var console = {
    log : function() {
      return false;
    }
  };
}

var alert = function(text) {
  dia.info(text,"Alert");
};

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

    if (typeof window.EventSource == 'undefined') {
      requestSettings.serverSentEvents = false;
    }
    else {
      requestSettings.serverSentEvents = json.getServerStatus.serverStatus.requestMethods.serverSentEvents;
    }

    if (json.getServerStatus.serverStatus.installUrl != window.location.origin + directory) {
      dia.error('<strong>WARNING</strong>: Your copy of FreezeMessenger has been incorrectly installed. Errors may occur if this is not fixed. <a href="http://code.google.com/p/freeze-messenger/wiki/ChangingDomains">Please see the online documentation for more information.</a>');
    }

    return false;
  },
  error: function() {
    requestSettings.longPolling = false;
    requestSettings.serverSentEvents = false;

    return false;
  }
});

$.ajax({
  url: directory + 'api/getFileTypes.php?fim3_format=json',
  type: 'GET',
  timeout: 1000,
  dataType: 'json',
  success: function(json) {
    console.log('Upload file types obtained.');

    active = json.getFileTypes.fileTypes;

    for (i in active) {
      uploadFileTypes[active[i].extension] = {
        extension : active[i].extension,
        maxSize : active[i].maxSize,
        mime : active[i].mime,
        container : active[i].container,
      }
    }
  },
  error: function() {
    dia.error('Upload file types not retrieved.');
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
  disableFormatting : (settingsBitfield & 16 ? true : false),
  disableImage : (settingsBitfield & 32 ? true : false),
  disableVideos : (settingsBitfield & 64 ? true : false),

  // Fun Stuff
  reversePostOrder : (settingsBitfield & 1024 ? true : false), // Show posts in reverse?
  showAvatars : (settingsBitfield & 2048 ? true : false), // Use the complex document style?
  audioDing : (settingsBitfield & 8192 ? true : false), // Fire an HTML5 audio ding during each unread message?

  // Accessibility
  disableFx : (settingsBitfield & 262144 ? true : false), // Disable jQuery Effects?
  disableRightClick : (settingsBitfield & 1048576 ? true : false),

  // Localisation
  usTime : (settingsBitfield & 16777216 ? true : false),
  twelveHourTime : (settingsBitfield & 33554432 ? true : false),

  // Experimental Features
  webkitNotifications : (settingsBitfield & 536870912 ? true : false)
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
    "(w|v)=([a-zA-Z0-9]+)" + // The video ID
  ")$", "i"),

  youtubeShort : new RegExp("^(" +
    "(http|https)" + // List of acceptable protocols. (so far: "http")
    ":" + // Colon! (so far: "http:")
    "(//|)" + // "//" is optional; this allows for it or nothing. (so far: "http://")
    "(www\\.|)" + // "www." optional (so far: "http://www")
    "youtu\\.be/" + // domain after "www." (so far: "http://www.youtu.be/")
    "([a-zA-Z0-9]+)" + // THe video ID
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
        userSelectHtml = '';
        active = json.getUsers.users;

        console.log('Users obtained.');
        for (i in active) {
          var userName = active[i].userName,
            userId = active[i].userId;

          userRef[userName] = userId;
          userIdRef[userId] = userName;
          userList.push(userName);
        }

        return false;
      },
      error: function() {
        alert('Users Not Obtained - Problems May Occur');

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
        roomSelectHtml = '';
        roomUlHtml = '';
        roomUlPrivHtml = '';
        roomUlMyHtml = '';
        roomUlFavHtml = '';

        active = json.getRooms.rooms;

        for (i in active) {
          var roomName = active[i].roomName,
            roomId = active[i].roomId,
            roomTopic = active[i].roomTopic,
            isFav = active[i].favorite,
            isPriv = active[i].optionDefinitions.privateIm,
            isAdmin = active[i].permissions.canAdmin,
            isModerator = active[i].permissions.canModerate,
            messageCount = active[i].messageCount,
            isOwner = (active[i].owner === userId ? true : false),
            ulText = '<li><a href="#room=' + roomId + '" class="room" data-roomId="' + roomId + '">' + roomName + '</a></li>';

          if (isFav) { roomUlFavHtml += ulText; }
          else if (isOwner && !isPriv) { roomUlMyHtml += ulText; }
          else if (isPriv) { roomUlPrivHtml += ulText; }
          else { roomUlHtml += ulText; }

          roomTableHtml += '<tr id="room' + roomId + '"><td><a href="#room=' + roomId + '">' + roomName + '</a></td><td>' + roomTopic + '</td><td>' + (isAdmin ? '<button data-roomId="' + roomId + '" class="editRoomMulti standard"></button><button data-roomId="' + roomId + '" class="deleteRoomMulti standard"></button>' : '') + '<button data-roomId="' + roomId + '" class="archiveMulti standard"></button><input type="checkbox" ' + (isFav ? 'checked="checked" ' : '') + ' data-roomId="' + roomId + '" class="favRoomMulti" id="favRoom' + roomId + '" /><label for="favRoom' + roomId + '" class="standard"></label></td></tr>';

          roomRef[roomName] = roomId;
          roomIdRef[roomId] = {
            'roomName' : roomName,
            'messageCount' : messageCount
          }
          roomList.push(roomName);

          if (isAdmin) { modRooms[roomId] = 2; }
          else if (isModerator) { modRooms[roomId] = 1; }
          else { modRooms[roomId] = 0; }
        }

        $('#roomListLong > li > ul').html('<li>Favourites<ul>' + roomUlFavHtml + '</ul></li><li>My Rooms<ul>' + roomUlMyHtml + '</ul></li><li>General<ul>' + roomUlHtml + '</ul></li><li>Private<ul>' + roomUlPrivHtml + '</ul></li>');
        $('#roomListShort > ul').html('<li>Favourites<ul>' + roomUlFavHtml + '</ul></li>');

        console.log('Rooms obtained.');

        return false;
      },
      error: function() {
        alert('Rooms Not Obtained - Problems May Occur');

        return false;
      }
    }),


    $.ajax({
      url: directory + 'api/getGroups.php?fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(json) {
        console.log('Groups obtained.');

        active = json.getGroups.groups;
        for (i in active) {
          var groupName = active[i].groupName,
            groupId = active[i].groupId;

          groupRef[groupName] = groupId;
          groupIdRef[groupId] = groupName;
          groupList.push(groupName);
        }

        return false;
      },
      error: function() {
        alert('Groups Not Obtained - Problems May Occur');

        return false;
      }
    }),

    $.ajax({
      url: directory + 'api/getFonts.php?fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(json) {
        active = json.getFonts.fonts;

        for (i in active) {
          var fontName = active[i].fontName,
            fontId = active[i].fontId,
            fontGroup = active[i].fontGroup,
            fontData = active[i].fontData;

          fontSelectHtml += '<option value="' + fontId + '" style="' + fontData + '" data-font="' + fontData + '">' + fontName + '</option>';
          fontIdRef[fontId] = fontName;
        }

        return false;
      },
      error: function() {
        dia.error('The list of fonts could not be obtained from the server.');

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

function youtubeSend(id) { // TODO
  standard.sendMessage('http://www.youtube.com/watch?v=' + id, 0);

  $('#textentryBoxYoutube').dialog('close');
}


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
        case 'moderators': case 'allowedUsers': case 'ignoreList': val = userIdRef[id]; type2 = 'User'; break;
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


var standard = {
  archive : function (options) {
    var encrypt = 'base64',
      lastMessage = 0,
      firstMessage = 0,
      data = '',
      where = '';

    if (options.idMax) { where = 'messageIdEnd=' + options.idMax; }
    else if (options.idMin) { where = 'messageIdStart=' + options.idMin; }
    else { where = 'messageIdStart=1'; }

    $('#searchText, #resultLimit, #searchUser').unbind('change');
    $('#searchText, #resultLimit, #searchUser').bind('change', function() {
      standard.archive({
        idMax : options.idMax,
        idMin : options.idMin,
        roomId : options.roomId,
        userId : userRef[$('#searchUser').val()],
        search : $('#searchText').val(),
        maxResults : $('#resultLimit').val(),
      });
    });

    $.when( $.ajax({
      url: directory + 'api/getMessages.php?roomId=' + options.roomId + '&' + (options.userId ? '&users=' + options.userId : '') + '&archive=1&messageLimit=10000&messageHardLimit=' + (options.maxResults ? options.maxResults : 50) + '&' + where + (options.search ? '&search=' + fim_eURL(options.search) : '') + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
      type: 'GET',
      timeout: 5000,
      contentType: "text/json; charset=utf-8",
      dataType: "json",
      cache: false,
      success: function (json) {
        active = json.getMessages.messages;

        for (i in active) {
          var messageId = active[i].messageData.messageId;

          data += messageFormat(active[i], 'table');

          if (messageId > lastMessage) { lastMessage = messageId; }
          if (messageId < firstMessage || !firstMessage) { firstMessage = messageId; }
        }

        return true;
      }
    })).always(function() {
      $('#archiveMessageList').html(data);

      $('#archiveNext').unbind('click');
      $('#archivePrev').unbind('click');
      $('#export').unbind('click');
      $('.updateArchiveHere').unbind('click');

      $('#archiveNext').bind('click', function() {
        standard.archive({
          idMin : lastMessage,
          roomId: options.roomId,
          userId : userRef[$('#searchUser').val()],
          search : $('#searchText').val(),
          maxResults : $('#resultLimit').val()
        })
      });
      $('#archivePrev').bind('click', function() {
        standard.archive({
          idMax : firstMessage,
          roomId: options.roomId,
          userId : userRef[$('#searchUser').val()],
          search : $('#searchText').val(),
          maxResults : $('#resultLimit').val()
        })
      });
      $('.updateArchiveHere').bind('click', function() {
        $('#searchUser').val('');
        $('#searchText').val('');

        standard.archive({
          idMin : $(this).attr('data-messageId'),
          roomId: options.roomId,
          maxResults : $('#resultLimit').val()
        })
      });
      $('#export').bind('click', function() {
        dia.full({
          id : 'exportDia',
          content : '<form method="post" action="#" onsubmit="return false;" id="exportDiaForm">How would you like to export the data?<br /><br /><table align="center"><tr><td>Format</td><td><select id="exportFormat"><option value="bbcodetable">BBCode Table</option><option value="csv">CSV List (Excel, etc.)</option></select></td></tr><tr><td colspan="2" align="center"><button type="submit">Export</button></td></tr></table></form>',
          width: 600,
        });

        $('#exportDiaForm').submit(function() {
          switch ($('#exportFormat option:selected').val()) {
            case 'bbcodetable':
            var exportData = '';

            $('#archiveMessageList').find('tr').each(function() {
              var exportUser = $(this).find('td:nth-child(1) .userNameTable').text(),
                exportTime = $(this).find('td:nth-child(2)').text(),
                exportMessage = $(this).find('td:nth-child(3)').text();

              for (i in [1,3]) {
                switch (i) {
                  case 1:
                  var exportItem = exportUser;
                  break;

                  case 3:
                  var exportItem = exportMessage;
                  break;
                }

                var el = $(this).find('td:nth-child(' + i + ') > span'),
                  colour = el.css('color'),
                  highlight = el.css('backgroundColor'),
                  font = el.css('fontFamily'),
                  bold = (el.css('fontWeight') == 'bold' ? true : false),
                  underline = (el.css('textDecoration') == 'underline' ? true : false),
                  strikethrough = (el.css('textDecoration') == 'line-through' ? true : false);

                if (colour || highlight || font) {
                  exportUser = '[span="' + (colour ? 'color: ' + colour + ';' : '') + (highlight ? 'background-color: ' + highlight + ';' : '') + (font ? 'font: ' + font + ';' : '') + '"]' + exportUser + '[/span]';
                }
                if (bold) { exportUser = '[b]' + exportUser + '[/b]'; }
                if (underline) { exportUser = '[u]' + exportUser + '[/u]'; }
                if (strikethrough) { exportUser = '[s]' + exportUser + '[/s]'; }
              }

              switch (i) {
                case 1: exportUser = exportItem; break;
                case 3: exportMessage = exportItem; break;
              }

              exportData += exportUser + "|" + exportTime + "|" + exportMessage + "\n";
            });

            exportData = "<textarea style=\"width: 100%; height: 1000px;\">[table=head]User|Time|Message\n" + exportData + "[/table]</textarea>";
            break;

            case 'csv':
            var exportData = '';

            $('#archiveMessageList').find('tr').each(function() {
              var exportUser = $(this).find('td:nth-child(1) .userNameTable').text(),
                exportTime = $(this).find('td:nth-child(2)').text(),
                exportMessage = $(this).find('td:nth-child(3)').text();

              exportData += "'" + exportUser + "', '" + exportTime + "', '" + exportMessage + "'\n";
            });

            exportData = "<textarea style=\"width: 100%; height: 600px;\">" + exportData + "</textarea>";
            break;
          }

          dia.full({
            id : 'exportTable',
            content : exportData,
            width : '1000',
          });

          return false;
        });
      });

      if (options.callback) {
        options.callback(data);
      }

      return true;
    });
  },



  login : function(options) {
    console.log('Login Initiated');
    var data = '',
      passwordEncrypt = '';


    console.log('Encrypted Password: ' + options.password);


    if (options.start) {
      options.start();
    }


    if (options.userName && options.password) {
      console.log('Login Triggered; Using a Password of "' + options.password + '" and a Username of "' + options.userName + '"');

      passwordEncrypt = 'plaintext';
      // TODO: Enable for vBulletin
      // var password = md5(password);
      // var passwordEncrypt = 'md5';

      data = 'userName=' + fim_eURL(options.userName) + '&password=' + fim_eURL(options.password) + '&passwordEncrypt=' + passwordEncrypt;
    }
    else if (options.userId && options.password) {
      console.log('Login Triggered; Using a Password of "' + options.password + '" and a UserID of "' + options.userId + '"');

      passwordEncrypt = 'plaintext';
      // TODO: Enable for vBulletin
      // var password = md5(password);
      // var passwordEncrypt = 'md5';

      data = 'userId=' + fim_eURL(options.userId) + '&password=' + fim_eURL(options.password) + '&passwordEncrypt=' + passwordEncrypt;
    }
    else {
      data = 'apiLogin=1';
    }


    $.ajax({
      url: directory + 'validate.php',
      type: 'POST',
      data: data + '&apiVersion=3&fim3_format=json',
      cache: false,
      timeout: 2500,
      success: function(json) {
        activeLogin = json.login;

        userId = activeLogin.userData.userId;
        anonId = activeLogin.anonId;
        sessionHash = activeLogin.sessionHash;



        $.cookie('webpro_userId', userId, { expires : 14 });
        $.cookie('webpro_password', options.password, { expires : 14 }); // We will encrypt this in B3 or later -- it isn't a priority for now. (TODO)



        /* Update Permissions */

        userPermissions = {
          createRoom : activeLogin.userPermissions.createRooms, privateRoom : activeLogin.userPermissions.privateRooms,
          general : activeLogin.userPermissions.allowed
        }

        adminPermissions = {
          modPrivs : activeLogin.adminPermissions.modPrivs, modCore : activeLogin.adminPermissions.modCore,
          modUsers : activeLogin.adminPermissions.modUsers, modTemplates : activeLogin.adminPermissions.modTemplates,
          modImages : activeLogin.adminPermissions.modImages, modCensor : activeLogin.adminPermissions.modCensor,
          modHooks : activeLogin.adminPermissions.modHooks
        }


        if (activeLogin.banned) { // The user has been banned, so pretty much nothing will work. In some respects, this really only exists for IP bans, but meh.
          dia.error('You have been banned. You will not be able to do anything.');

          userPermissions = {
            createRoom : false, privateRoom : false, general : false
          }

          adminPermissions = {
            modPrivs : false, modCore : false, modUsers : false,
            modTemplates : false, modImages : false, modCensor : false,
            modHooks : false
          }
        }
        else if (activeLogin.valid === true) {
          if (options.showMessage) {
            // Display Dialog to Notify User of Being Logged In
            if (!userPermissions.general) {
              dia.info('You are now logged in as ' + activeLogin.userData.userName + '. However, you are not allowed to post and have been banned by an administrator.', 'Logged In');
            }
            else {
              dia.info('You are now logged in as ' + activeLogin.userData.userName + '.', 'Logged In');
            }
          }

          $('#loginDialogue').dialog('close'); // Close any open login forms.

          console.log('Login valid. Session hash: ' + sessionHash + '; User ID: ' + userId);
        }
        else {
          switch (activeLogin.loginFlag) {
            case 'PASSWORD_ENCRYPT': dia.error("The form encryption used was not accepted by the server."); break;
            case 'BAD_USERNAME': dia.error("A valid user was not provided."); break;
            case 'BAD_PASSWORD': dia.error("The password was incorrect."); break;
            case 'API_VERSION_STRING': dia.error("The server was unable to process the API version string specified."); break;
            case 'DEPRECATED_VERSION': dia.error("The server will not accept this client because it is of a newer version."); break;
            case 'INVALID_SESSION': sessionHash = ''; break;
            default: break;
          }

          console.log('Login Invalid');
        }


        if (!anonId && !userId) {
          $('#messageInput').attr("disabled","disabled"); // The user is not able to post.
        }

        if (options.finish) {
          options.finish();
        }

        populate({
          callback : function() {
            contextMenuParseRoom();
            windowDynaLinks();

            /* Select Room */
            if (!roomId) {
              hashParse({defaultRoomId : activeLogin.defaultRoomId}); // When a user logs in, the hash data (such as room and archive) is processed, and subsequently executed.

              /*** A Hack of Sorts to Open Dialogs onLoad ***/
              if (typeof prepopup === "function") {
                prepopup();

                prepopup = false;
              }
            }

            return false;
          }
        });

        console.log('Login Finished');


        return false;
      },
      error: function(err,err2,err3) {
        dia.error("The login request could not be sent. Please try again.<br /><br />" + err3 + "<br /><br />" + directory + "validate.php<br /><br />" + data + '&apiVersion=3');

        return false;
      }
    });
  },

  logout : function() {
    $.cookie('webpro_userId', null);
    $.cookie('webpro_password', null);

    standard.login({});
  },


  getMessages : function() {
    clearInterval(timers.t1);

    if (roomId) {

      var encrypt = 'base64',
        lastMessageId;

      if (requestSettings.firstRequest) {
        $.ajax({
          url: directory + 'api/getRooms.php?rooms=' + roomId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
          type: 'GET',
          timeout: 2400,
          cache: false,
          async : false, // We need to complete this request before the next
          success: function(json) {
            active = json.getRooms.rooms;

            for (i in active) {
              lastMessageId = active[i].lastMessageId;

              break;
            }

            return false;
          },
          error: function() {
            dia.error('Failed to obtain current room settings from server.');

            return false;
          }
        });
      }

      if (requestSettings.serverSentEvents) {
        var source = new EventSource(directory + 'eventStream.php?roomId=' + roomId + '&lastEvent=' + requestSettings.lastEvent + '&lastMessage=' + requestSettings.lastMessage + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId);

        source.addEventListener('message', function(e) {
          active = JSON.parse(e.data);

          var messageId = Number(active.messageData.messageId);

          console.log('Event (New Message): ' + messageId);

          data = messageFormat(active, 'list');

          messagePopup(data)


          if (messageIndex[messageId]) {
            // Double post hack
          }
          else {
            if (settings.reversePostOrder) { $('#messageList').append(data); }
            else { $('#messageList').prepend(data); }

            if (messageId > requestSettings.lastMessage) {
              requestSettings.lastMessage = messageId;
            }

            messageIndex.push(requestSettings.lastMessage);

            if (messageIndex.length === 100) {
              var messageOut = messageIndex[0];
              $('#message' + messageOut).remove();
              messageIndex = messageIndex.slice(1,99);
            }
          }

          newMessage();

          return false;
        }, false);

        source.addEventListener('topicChange', function(e) {
          var active = JSON.parse(e.data);

          $('#topic').html(active.param1);
          console.log('Event (Topic Change): ' + active.param1);

          requestSettings.lastEvent = active.eventId;

          return false;
        }, false);

        source.addEventListener('missedMessage', function(e) {
          var active = JSON.parse(e.data);

          requestSettings.lastEvent = active.eventId;
          $.jGrowl('Missed Message', 'New messages have been made in:<br /><br /><a href="#room=' + active.roomId + '">' + active.roomName + '</a>');
          console.log('Event (Missed Message): ' + active.messageId);

          return false;
        }, false);

        source.addEventListener('deletedMessage', function(e) {
          var active = JSON.parse(e.data);

          $('#topic').html(active.param1);
          console.log('Event (Topic Change): ' + active.param1);

          requestSettings.lastEvent = active.eventId;

          return false;
        }, false);

        source.addEventListener('open', function(e) {
          // Connection was opened.
        }, false);

        source.addEventListener('error', function(e) {
          if (e.eventPhase == EventSource.CLOSED) {
            // Connection was closed.
          }
        }, false);

      }
      else {
        $.ajax({
          url: directory + 'api/getMessages.php?roomId=' + roomId + '&messageLimit=100&watchRooms=1&activeUsers=1' + (requestSettings.firstRequest ? '&archive=1&messageIdEnd=' + lastMessageId : '&messageIdStart=' + (requestSettings.lastMessage + 1)) + (requestSettings.longPolling ? '&longPolling=true' : '') + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
          type: 'GET',
          timeout: requestSettings.timeout,
          contentType: "text/json; charset=utf-8",
          dataType: "json",
          cache: false,
          success: function(json) {
            var errStr = json.getMessages.errStr,
              errDesc = json.getMessages.errDesc,
              sentUserId = 0,
              messageCount = 0;

            if (errStr) {
              sentUserId = json.getMessages.activeUser.userId;

              if (errStr === 'noperm') {
                roomId = false;

                if (sentUserId) {
                  popup.selectRoom();

                  dia.error('You have been restricted access from this room. Please select a new room.');
                }
                else {
                  popup.login();

                  dia.error('You are no longer logged in. Please log-in.');
                }
              }
              else {
                roomId = false;
                dia.error(errDesc);
              }
            }
            else {
              requestSettings.totalFails = 0;
              var notifyData = '',
                activeUserHtml = [];




              $('#activeUsers').html('');

              active = json.getMessages.activeUsers;

              for (i in active) {
                var userName = active[i].userName,
                  userId = active[i].userId,
                  userGroup = active[i].userGroup,
                  startTag = active[i].startTag,
                  endTag = active[i].endTag;

                activeUserHtml.push('<span class="userName" data-userId="' + userId + '">' + startTag + '<span class="username">' + userName + '</span>' + endTag + '</span>');
              }

              $('#activeUsers').html(activeUserHtml.join(', '));
              contextMenuParseUser('#activeUsers');



              active = json.getMessages.messages;

              for (i in active) {
                var messageId = Number(active[i].messageData.messageId);
                data = messageFormat(active[i], 'list');

                if (messageIndex[messageId]) {
                  // Double post hack
                }
                else {
                  if (settings.reversePostOrder) {
                    $('#messageList').append(data);
                  }
                  else {
                    $('#messageList').prepend(data);
                  }

                  if (messageId > requestSettings.lastMessage) {
                    requestSettings.lastMessage = messageId;
                  }

                  messageIndex.push(requestSettings.lastMessage);

                  if (messageIndex.length === 100) {
                    var messageOut = messageIndex[0];
                    $('#message' + messageOut).remove();
                    messageIndex = messageIndex.slice(1,99);
                  }
                }

                messageCount++;
              }


              if (messageCount > 0) { newMessage(); }

              if (requestSettings.longPolling) {
                timers.t1 = setTimeout(standard.getMessages, 50);
              }
              else {
                requestSettings.timeout = 2400;
                timers.t1 = setTimeout(standard.getMessages, 2500);
              }
            }

            requestSettings.firstRequest = false;

            return false;
          },
          error: function(err) {
            console.log('Requesting messages for ' + roomId + '; failed: ' + err + '.');
            var wait;

            if (requestSettings.longPolling) {
              timers.t1 = setTimeout(standard.getMessages, 50);
            }
            else {
              requestSettings.totalFails += 1;

              if (!requestSettings.longPolling) {
                if (requestSettings.totalFails > 10) {
                  wait = 30000;
                  requestSettings.timeout = 29900;

                  // TODO: Add indicator.
                }
                else if (requestSettings.totalFails > 5) {
                  wait = 10000;
                  requestSettings.timeout = 9900;

                  // TODO: Add indicator.
                }
                else {
                  wait = 5000;
                  requestSettings.timeout = 4900;
                }
              }

              timers.t1 = setTimeout(standard.getMessages,wait);
            }

            return false;
          }
        });
      }
    }
    else {
      console.log('Not requesting messages; room undefined.');
    }

    return false;
  },


  sendMessage : function(message, confirmed, flag) {
    if (!roomId) {
      popup.selectRoom();
    }
    else {
      confirmed = (confirmed === 1 ? 1 : '');

      $.ajax({
        url: directory + 'api/sendMessage.php?fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
        type: 'POST',
        data: 'roomId=' + roomId + '&confirmed=' + confirmed + '&message=' + fim_eURL(message) + '&flag=' + (flag ? flag : ''),
        cache: false,
        timeout: 5000,
        success: function(json) {
          console.log('Message sent.');

          var errStr = json.sendMessage.errStr,
            errDesc = json.sendMessage.errDesc;

          switch (errStr) {
            case '': break;
            case 'badRoom': dia.error("A valid room was not provided."); break;
            case 'badMessage': dia.error("A valid message was not provided."); break;
            case 'spaceMessage': dia.error("Too... many... spaces!"); break;
            case 'noPerm': dia.error("You do not have permission to post in this room."); break;
            case 'blockCensor': dia.error(errDesc); break;
            case 'confirmCensor':
            dia.error(errDesc + '<br /><br /><button type="button" onclick="$(this).parent().dialog(&apos;close&apos;);">No</button><button type="button" onclick="standard.sendMessage(&apos;' + escape(message) + '&apos;,1' + (flag ? ', ' + flag : '') + '); $(this).parent().dialog(&apos;close&apos;);">Yes</button>');
            break;
          }

          return false;
        },
        error: function() {
          console.log('Message error.');

          if (settings.reversePostOrder) { $('#messageList').append('Your message, "' + message + '", could not be sent and will be retried.'); }
          else { $('#messageList').prepend('Your message, "' + message + '", could not be sent and will be retried.'); }

          window.setTimeout(function() { standard.sendMessage(message) }, 5000);

          return false;
        }
      });
    }

    return false;
  },


  changeRoom : function(roomIdLocal) {
    if (!roomIdLocal) {
      return false;
    }

    if (roomIdLocal.toString().substr(0,1) === 'p') {
      $.ajax({
        url: directory + 'api/getPrivateRoom.php?users=' + roomIdLocal.toString().substr(1) + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
        timeout: 5000,
        type: 'GET',
        cache: false,
        success: function(json) {
          active = json.getPrivateRoom.room;
          var users = active.roomUsers,
            roomUsers = [];

          for (i in users) {
            userName = users[i].userName,
            userFormatStart = users[i].userFormatStart,
            userFormatEnd = users[i].userFormatEnd,

            roomUsers.push(userName);
          }

          var roomName = 'Conversation Between: ' + roomUsers.join(', ');
          roomId = 'p' + active.roomUsersList;

          $('#roomName').html(roomName);
          $('#messageList').html('');

          $('#messageInput').removeAttr('disabled');
          $('#icon_url').button({ disabled : false });
          $('#icon_submit').button({ disabled : false });
          $('#icon_reset').button({ disabled : false });

          /*** Get Messages ***/
          $(document).ready(function() {
            requestSettings.firstRequest = true;
            requestSettings.lastMessage = 0;
            messageIndex = [];

            standard.getMessages();

            windowDraw();
            windowDynaLinks();
          });
        },
        error: function() {
          alert('Could not fetch room data.');

          return false;
        }
      });
    }
    else {
      $.ajax({
        url: directory + 'api/getRooms.php?rooms=' + roomIdLocal + '&permLevel=view&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
        timeout: 5000,
        type: 'GET',
        cache: false,
        success: function(json) {
          active = json.getRooms.rooms;

          for (i in active) {
            var roomName = active[i].roomName,
              roomId2 = active[i].roomId,
              roomTopic = active[i].roomTopic,
              permissions = active[i].permissions;

            if (!permissions.canView) {
              roomId = false;

              popup.selectRoom();

              dia.error('You have been restricted access from this room. Please select a new room.');
            }
            else if (!permissions.canPost) {
              alert('You are not allowed to post in this room. You will be able to view it, though.');

              $('#messageInput').attr('disabled','disabled');
              $('#icon_url').button({ disabled : true });
              $('#icon_submit').button({ disabled : true });
              $('#icon_reset').button({ disabled : true });
            }
            else {
              $('#messageInput').removeAttr('disabled');
              $('#icon_url').button({ disabled : false });
              $('#icon_submit').button({ disabled : false });
              $('#icon_reset').button({ disabled : false });
            }

            if (permissions.canView) {
              roomId = roomId2;

              $('#roomName').html(roomName);
              $('#topic').html(roomTopic);
              $('#messageList').html('');


              /*** Get Messages ***/
              $(document).ready(function() {
                requestSettings.firstRequest = true;
                requestSettings.lastMessage = 0;
                messageIndex = [];

                standard.getMessages();

                windowDraw();
                windowDynaLinks();
              });
            }

            break;
          }
        },
        error: function() {
          alert('Could not fetch room data.');

          return false;
        }
      });
    }
  },


  deleteRoom : function(roomIdLocal) {
    $.post(directory + 'api/editRoom.php', 'action=delete&messageId=' + messageId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json', function(json) {
      var errStr = json.editRoom.errStr,
        errDesc = json.editRoom.errDesc;

      switch (errStr) {
        case '': console.log('Message ' + messageId + ' deleted.'); break;
        case 'nopermission': dia.error('You do not have permision to administer this room.'); break;
        case 'badroom': dia.error('The specified room does not exist.'); break;
      }

      return false;
    }); // Send the form data via AJAX.
  },

  favRoom : function(roomIdLocal) {
    $.post(directory + 'api/moderate.php', 'action=favRoom&roomId=' + roomIdLocal + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json', function(json) {
      return false;
    });

    return false;
  },

  unfavRoom : function(roomIdLocal) {
    $.post(directory + 'api/moderate.php', 'action=unfavRoom&roomId=' + roomIdLocal + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json', function(json) {
      return false;
    });

    return false;
  },

  privateRoom : function(userLocalId) {
    userLocalId = Number(userLocalId);

    if (userLocalId === userId) { dia.error('You can\'t talk to yourself...'); }
    else if (!userLocalId) { dia.error('You have not specified a user.'); }
    else if (!userPermissions.privateRoom) { dia.error('You do not have permission to talk to users privately.'); }
    else {
      $.post(directory + 'api/editRoom.php', 'action=private&userId=' + userLocalId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json', function(json) {
        var privateRoomId = json.editRoom.response.insertId,
          errStr = json.editRoom.errStr,
          errDesc = json.editRoom.errDesc;

        if (errStr) {
          switch (errStr) {
            case 'baduser':
            dia.error('The user specified does not exist.');
            break;
          }
        }
        else {
          dia.full({
            content : 'You may talk to this person privately at the following link: <form method="post" onsubmit="return false;"><input type="text" style="width: 300px;" value="' + currentLocation + '#room=' + privateRoomId + '" name="url" /></form>',
            id : 'privateRoomSucessDialogue',
            buttons : {
              Open : function() { standard.changeRoom(privateRoomId); },
              Okay : function() { $('#privateRoomSucessDialogue').dialog('close'); }
            },
            width: 600
          });
        }

        return false;
      });
    }

    return false;
  },


  kick : function(userId, roomId, length) {
    $.post(directory + 'api/moderate.php', 'action=kickUser&userId=' + userId + '&roomId=' + roomId + '&length=' + length + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json', function(json) {
      var errStr = json.moderaate.errStr,
        errDesc = json.moderaate.errDesc;

      switch (errStr) {
        case '': dia.info('The user has been kicked.', 'Success'); $("#kickUserDialogue").dialog('close'); break;
        case 'nopermission': dia.error('You do not have permision to moderate this room.'); break;
        case 'nokickuser': dia.error('That user may not be kicked!'); break;
        case 'baduser': dia.error('The user specified does not exist.'); break;
        case 'badroom': dia.error('The room specified does not exist.'); break;
      }

      return false;
    }); // Send the form data via AJAX.

    return false;
  },

  unkick : function(userId, roomId) {
    $.post(directory + 'api/moderate.php', 'action=unkickUser&userId=' + userId + '&roomId=' + roomId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId, function(json) {
      var errStr = json.moderaate.errStr,
        errDesc = json.moderaate.errDesc;

      switch (errStr) {
        case '': dia.info('The user has been unkicked.', 'Success'); $("#kickUserDialogue").dialog('close'); break;
        case 'nopermission': dia.error('You do not have permision to moderate this room.'); break;
        case 'baduser': case 'badroom': dia.error('Odd error: the user or room sent do not seem to exist.'); break;
      }

      return false;
    }); // Send the form data via AJAX.

    return false;
  },


  deleteMessage : function(messageId) {
    $.post(directory + 'api/editMessage.php', 'action=delete&messageId=' + messageId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + 'fim3_format=json', function(json) { // Send the form data via AJAX.
      var errStr = json.moderaate.errStr,
        errDesc = json.moderaate.errDesc;

      switch (errStr) {
        case '': console.log('Message ' + messageId + ' deleted.'); break;
        case 'nopermission': dia.error('You do not have permision to moderate this room.'); break;
        case 'badmessage': dia.error('The message does not exist.'); break;
      }

      return false;
    });

    return false;
  }


};

/*********************************************************
************************* END ***************************
******************* Content Functions *******************
*********************************************************/







/*********************************************************
************************ START **************************
************** Repeat-Action Popup Methods **************
*********************************************************/

popup = {
  /*** START Login ***/

  login : function() {
    dia.full({
      content : window.templates.login,
      title : 'Login',
      id : 'loginDialogue',
      width : 600,
      oF : function() {
        // The following is a rather complicated hack that fixes a huge issue with how the login box first displays. It's stupid, but... yeah.
        manualHeight = ($(window).innerHeight() - 600) / 2;
        if (manualHeight < 0) manualHeight = 0;

        manualWidth = ($(window).innerWidth() - 600) / 2;
        if (manualWidth < 0) manualWidth = 0;

        $('#loginDialogue').parent().css('top', manualHeight);
        $('#loginDialogue').parent().css('left', manualWidth);
        $('#loginDialogue').parent().css('position', 'absolute');
        $('body').scrollTop();


        // Login Form Processing
        $("#loginForm").submit(function() {
          var userName = $('#loginForm > #userName').val(),
            password = $('#loginForm > #password').val(),
            rememberMe = $('#loginForm > #rememberme').is('checked');

          standard.login({
            userName : userName, password : password,
            showMessage : true, rememberMe : rememberMe
          });

          return false; // Don't submit the form.
        });
      },
      cF : function() {
        if (!userId) {
          standard.login({
            start : function() {
              $('<div class="ui-widget-overlay" id="loginWaitOverlay"></div>').appendTo('body').width($(document).width()).height($(document).height());
              $('<img src="images/ajax-loader.gif" id="loginWaitThrobber" />').appendTo('body').css('position', 'absolute').offset({ left : (($(window).width() - 220) / 2), top : (($(window).height() - 19) / 2)});
            },
            finish : function() {
              $('#loginWaitOverlay, #loginWaitThrobber').empty().remove();
            }
          });
        }

        return false;
      }
    });

    return false;
  },

  /*** END Login ***/




  /*** START Room Select ***/

  selectRoom : function() {
    dia.full({
      content : '<table class="center"><thead><tr><th style="width: 20%;">Name</th><th style="width: 60%;">Topic</th><th style="width: 20%;">Actions</th></tr></thead><tbody>' + roomTableHtml + '</tbody></table>',
      title : 'Room List',
      id : 'roomListDialogue',
      width: 1000,
      oF : function() {
        $('button.editRoomMulti, input[type=checkbox].favRoomMulti, button.archiveMulti, button.deleteRoomMulti').unbind('click'); // Prevent the below from being binded multiple times.


        $('button.editRoomMulti').button({icons : {primary : 'ui-icon-gear'}}).bind('click', function() {
          popup.editRoom($(this).attr('data-roomId'));
        });

        $('input[type=checkbox].favRoomMulti').button({icons : {primary : 'ui-icon-star'}, text : false}).bind('change', function() {
          if ($(this).is(':checked')) { standard.favRoom($(this).attr('data-roomId')); }
          else { standard.unfavRoom($(this).attr('data-roomId')); }
        });

        $('button.archiveMulti').button({icons : {primary : 'ui-icon-note'}}).bind('click', function() {
          popup.archive({roomId : $(this).attr('data-roomId')});
        });

        $('button.deleteRoomMulti').button({icons : {primary : 'ui-icon-trash'}}).bind('click', function() {
          standard.deleteRoom($(this).attr('data-roomId'));
        });
      }
    });

    return false;
  },

  /*** END Room List ***/




  /*** START Insert Docs ***/

  insertDoc : function(preselect) {
    var fileContent,
      selectTab;

    switch (preselect) {
      case 'video': selectTab = 2; break;
      case 'image': selectTab = 1; break;
      case 'link': default: selectTab = 0; break;
    }

    dia.full({
      content : window.templates.insertDoc,
      id : 'insertDoc',
      width: 600,
      tabs : true,
      oF : function() {
        var fileName,
          fileSize;

        $('#imageUploadSubmitButton').attr('disabled', 'disabled').button({ disabled: true });


        if (typeof FileReader !== 'function') {
          $('#uploadFileForm').html('Your device does not support file uploads.<br /><br />');
        }
        else {
          $('#fileUpload, #urlUpload').unbind('change'); // Prevent duplicate binds.
          $('#uploadFileForm, #uploadUrlForm, #linkForm. #uploadYoutubeForm').unbind('submit');


          $('#fileUpload').bind('change', function() {
            console.log('FileReader triggered.');

            var reader = new FileReader(),
              reader2 = new FileReader();

            files = this.files;

            if (files.length === 0) {
              dia.error('No files selected!');
            }
            else if (files.length > 1) {
              dia.error('Too many files selected!');
            }
            else {
              console.log('FileReader started.');

              fileName = files[0].name,
                fileSize = files[0].size;

              var fileParts = fileName.split('.');
              var filePartsLast = fileParts[fileParts.length - 1];

              if (!filePartsLast in uploadFileTypes) {
                $('#preview').html('The specified file type can not be uploaded.');
              }
              else if (fileSize > uploadFileTypes[filePartsLast].maxSize) {
                $('#preview').html('The specified file type must not be larger than ' + uploadFileTypes[filePartsLast].maxSize + ' bytes');
              }
              else {
                reader.readAsBinaryString(files[0]);
                reader.onloadend = function() {
                  fileContent = window.btoa(reader.result);
                };

                reader2.readAsDataURL(files[0]);
                reader2.onloadend = function() {
                  switch (uploadFileTypes[filePartsLast].container) {
                    case 'image': $('#uploadFileFormPreview').html('<img src="' + reader2.result + '" style="max-height: 200px; max-width: 200px;" />'); break;
                    case 'video': $('#uploadFileFormPreview').html('No Preview Available'); break;
                    case 'audio': $('#uploadFileFormPreview').html('No Preview Available'); break;
                    case 'text': $('#uploadFileFormPreview').html('No Preview Available'); break;
                    case 'html': $('#uploadFileFormPreview').html('No Preview Available'); break;
                    case 'archive': $('#uploadFileFormPreview').html('No Preview Available'); break;
                    case 'other': $('#uploadFileFormPreview').html('No Preview Available'); break;
                  }
                };
              }

              $('#imageUploadSubmitButton').removeAttr('disabled').button({ disabled: false });
            }
          });

          $('#urlUpload').bind('change', function() {
            fileContent = $('#urlUpload').val();
            if (fileContent && fileContent !== 'http://') {
              fileContainer = '<img src="' + fileContent + '" alt="" style="max-width: 200px; max-height: 250px; height: auto;" />';

              $('#uploadUrlFormPreview').html(fileContainer);
            }
          });


          $('#uploadFileForm').bind('submit', function() {
            $.ajax({
              url : directory + 'api/editFile.php',
              type : 'POST',
              data : 'action=create&dataEncode=base64&uploadMethod=raw&autoInsert=true&roomId=' + roomId + '&fileName=' + fileName + '&fileData=' + fim_eURL(fileContent) + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
              cache : false,
              success : function(json) {
                var errStr = json.editFile.errStr,
                  errDesc = json.editFile.errDesc;

                if (errStr) {
                  dia.error(errDesc);
                }
              }
            });

            $('#insertDoc').dialog('close');

            return false;
          });
        }


        $('#uploadUrlForm').bind('submit', function() {
          var linkImage = $('#urlUpload').val();

          if (linkImage) { standard.sendMessage(linkImage, 0, 'image'); }

          $('#insertDoc').dialog('close');

          return false;
        });


        $('#linkForm').bind('submit', function() {
          var linkUrl = $('#linkUrl').val(),
            linkMail = $('#linkEmail').val();

          if (linkUrl.length === 0 && linkMail.length === 0) { dia.error('No Link Was Specified'); } // No value for either.
          else if (linkUrl.length > 0) { standard.sendMessage(linkUrl, 0, 'url'); } // Link specified for URL.
          else if (linkMail.length > 0) { standard.sendMessage(linkMail, 0, 'email'); } // Link specified for mail, not URL.
          else { dia.error('Logic Error'); } // Eh, why not?

          $('#insertDoc').dialog('close');

          return false;
        });

        $('#uploadYoutubeForm').bind('submit', function() {
          linkVideo = $('#youtubeUpload');

          if (linkVideo.search(/^http\:\/\/(www\.|)youtube\.com\/(.*?)?v=(.+?)(&|)(.*?)$/) === 0) { dia.error('No Video Specified'); } // Bad format
          else { standard.sendMessage(linkVideo, 0, 'video'); }

          $('#insertDoc').dialog('close');

          return false;
        });

        return false;
      },
      selectTab : selectTab
    });

    return false;
  },

  /*** END Insert Docs ***/




  /*** START Stats ***/

  viewStats : function() {
    var statsHtml = {}, // Object
      statsHtml2 = '',
      roomHtml = '',
      number = 10,
      i = 1;

    for (i = 1; i <= number; i += 1) {
      statsHtml[i] = '';
    }

    $.ajax({
      url: directory + 'api/getStats.php?rooms=' + roomId + '&number=' + number + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(json) {
        for (i in json.getStats.roomStats) {
          var roomName = json.getStats.roomStats[i].roomData.roomName,
            roomId = json.getStats.roomStats[i].roomData.roomId;

          for (j in json.getStats.roomStats[i].users) {
            var userName = json.getStats.roomStats[i].users[j].userData.userName,
              userId = json.getStats.roomStats[i].users[j].userData.userId,
              startTag = json.getStats.roomStats[i].users[j].userData.startTag,
              endTag = json.getStats.roomStats[i].users[j].userData.endTag,
              position = json.getStats.roomStats[i].users[j].position,
              messageCount = json.getStats.roomStats[i].users[j].messageCount;

            statsHtml[position] += '<td><span class="userName userNameTable" data-userId="' + userId + '">' + startTag + userName + endTag + '</span> (' + messageCount + ')</td>';
          };


          roomHtml += '<th>' + roomName + '</th>';

        }

        for (i = 1; i <= number; i += 1) {
          statsHtml2 += '<tr><th>' + i + '</th>' + statsHtml[i] + '</tr>';
        }

        dia.full({
          content : '<table class="center"><thead><tr><th>Position</th>' + roomHtml + '</tr></thead><tbody>' + statsHtml2 + '</tbody></table>',
          title : 'Room Stats',
          id : 'roomStatsDialogue',
          width : 600
        });

        return false;
      },
      error: function() {
        dia.error('Failed to obtain stats.');

        return false;
      }
    });

    return false;
  },

  /*** END Stats ***/




  /*** START User Settings ***/

  userSettings : function() {
    dia.full({
      content : window.templates.userSettingsForm,
      id : 'changeSettingsDialogue',
      tabs : true,
      width : 1000,
      cF : function() {
        $('.colorpicker').empty().remove();

        return false;
      },
      oF : function() {
        var defaultColour = false,
          defaultHighlight = false,
          defaultFontface = false,
          idMap = {
            disableFormatting : 16, disableImage : 32, disableVideos : 64, reversePostOrder : 1024,
            showAvatars : 2048, audioDing : 8192, disableFx : 262144, disableRightClick : 1048576,
            usTime : 16777216, twelveHourTime : 33554432, webkitNotifications : 536870912
          };


        // Check boxes that should be.
        if (settings.reversePostOrder) $('#reversePostOrder').attr('checked', 'checked');
        if (settings.showAvatars) $('#showAvatars').attr('checked', 'checked');
        if (settings.audioDing) $('#audioDing').attr('checked', 'checked');
        if (settings.disableFx) $('#disableFx').attr('checked', 'checked');
        if (settings.disableFormatting) $('#disableFormatting').attr('checked', 'checked');
        if (settings.disableVideo) $('#disableVideo').attr('checked', 'checked');
        if (settings.disableImage) $('#disableImage').attr('checked', 'checked');
        if (settings.disableRightClick) $('#disableRightClick').attr('checked', 'checked');
        if (settings.webkitNotifications) $('#webkitNotifications').attr('checked', 'checked');
        if (settings.twelveHourTime) $('#twelveHourFormat').attr('checked', 'checked');
        if (settings.usTime) $('#usTime').attr('checked', 'checked');

        // Update volume to current.
        if (snd.volume) $('#audioVolume').attr('value', snd.volume * 100);

        // And the same with a few select boxes.
        if (theme) $('#theme > option[value="' + theme + '"]').attr('selected', 'selected');
        if (fontsize) $('#fontsize > option[value="' + fontsize + '"]').attr('selected', 'selected');


        $.get(directory + 'api/getUsers.php?users=' + userId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json', function(json) {
          active = json.getUsers.users;

          for (i in active) {
            defaultColour = active[i].defaultFormatting.color;
            defaultHighlight = active[i].defaultFormatting.highlight;
            defaultFontface = active[i].defaultFormatting.fontface;

            var defaultGeneral = active[i].defaultFormatting.general,
              ignoreList = active[i].ignoreList,
              watchRooms = active[i].watchRooms,
              options = active[i].options,
              defaultRoom = active[i].defaultRoom,
              defaultHighlightHashPre = [],
              defaultHighlightHash = {r:0, g:0, b:0},
              defaultColourHashPre = [],
              defaultColourHash = {r:0, g:0, b:0};

            if (defaultGeneral & 256) {
              $('#fontPreview').css('font-weight', 'bold');
              $('#defaultBold').attr('checked', 'checked');
            }
            $('#defaultBold').change(function() {
              if ($('#defaultBold').is(':checked')) $('#fontPreview').css('font-weight', 'bold');
              else $('#fontPreview').css('font-weight', 'normal');
            });

            if (defaultGeneral & 512) {
              $('#fontPreview').css('font-style', 'italic');
              $('#defaultItalics').attr('checked', 'checked');
            }
            $('#defaultItalics').change(function() {
              if ($('#defaultBold').is(':checked')) $('#fontPreview').css('font-style', 'italic');
              else $('#fontPreview').css('font-style', 'normal');
            });

            if (defaultColour) {
              $('#fontPreview').css('color', 'rgb(' + defaultColour + ')');
              $('#defaultColour').css('background-color', 'rgb(' + defaultColour + ')');

              defaultColourHashPre = defaultColour.split(',');
              defaultColourHash = {r : defaultColourHashPre[0], g : defaultColourHashPre[1], b : defaultColourHashPre[2] }
            }

            if (defaultHighlight) {
              $('#fontPreview').css('background-color', 'rgb(' + defaultHighlight + ')');
              $('#defaultHighlight').css('background-color', 'rgb(' + defaultHighlight + ')');

              defaultHighlightHashPre = defaultHighlight.split(',');
              defaultHighlightHash = {r : defaultHighlightHashPre[0], g : defaultHighlightHashPre[1], b : defaultHighlightHashPre[2] }
            }

            if (defaultFontface) {
              $('#defaultFace > option[value="' + defaultFontface + '"]').attr('selected', 'selected');
            }
            $('#defaultFace').change(function() {
              $('#fontPreview').css('fontFamily', $('#defaultFace > option:selected').attr('data-font'));
            });

            $('#defaultHighlight').ColorPicker({
              color: defaultHighlightHash,
              onShow: function (colpkr) { $(colpkr).fadeIn(500); }, // Fadein
              onHide: function (colpkr) { $(colpkr).fadeOut(500); }, // Fadeout
              onChange: function(hsb, hex, rgb) {
                defaultHighlight = rgb['r'] + ',' + rgb['g'] + ',' + rgb['b'];

                $('#defaultHighlight').css('background-color', 'rgb(' + defaultHighlight + ')');
                $('#fontPreview').css('background-color', 'rgb(' + defaultHighlight + ')');
              }
            });

            $('#defaultColour').ColorPicker({
              color: defaultColourHash,
              onShow: function (colpkr) { $(colpkr).fadeIn(500); }, // Fadein
              onHide: function (colpkr) { $(colpkr).fadeOut(500); }, // Fadeout
              onChange: function(hsb, hex, rgb) {
                defaultColour = rgb['r'] + ',' + rgb['g'] + ',' + rgb['b'];

                $('#defaultColour').css('background-color', 'rgb(' + defaultColour + ')');
                $('#fontPreview').css('color', 'rgb(' + defaultColour + ')');
              }
            });

            $('#defaultRoom').val(roomIdRef[defaultRoom].roomName);


            autoEntry.showEntries('ignoreList', ignoreList);
            autoEntry.showEntries('watchRooms', watchRooms);

            return false;
          }
        });


        // Autocomplete Rooms and Users
        $("#defaultRoom").autocomplete({ source: roomList });
        $("#watchRoomsBridge").autocomplete({ source: roomList });
        $("#ignoreListBridge").autocomplete({ source: userList });


        $('#defaultFace').html(fontSelectHtml);


        $('#theme').change(function() {
          $('#stylesjQ').attr('href', 'client/css/' + this.value + '/jquery-ui-1.8.16.custom.css');
          $('#stylesFIM').attr('href', 'client/css/' + this.value + '/fim.css');

          $.cookie('webpro_theme', this.value, { expires : 14 });
          theme = this.value;

          return false;
        });


        $('#fontsize').change(function() {
          $('body').css('font-size',this.value + 'em');

          $.cookie('webpro_fontsize', this.value, { expires : 14 });
          fontsize = this.value;

          return false;
        });

        $('#audioVolume').change(function() {
          $.cookie('webpro_audioVolume', this.value, { expires : 14 });
          snd.volume = this.value / 100;

          return false;
        });


        $('#showAvatars, #reversePostOrder, #disableFormatting, #disableVideo, #disableImage').change(function() {
          var localId = $(this).attr('id');

          if ($(this).is(':checked') && !settings[localId]) {
            settings[localId] = true;
            $('#messageList').html('');
            $.cookie('webpro_settings', Number($.cookie('webpro_settings')) + idMap[localId], { expires : 14 });
          }
          else if (!$(this).is(':checked') && settings[localId]) {
            settings[localId] = false;
            $('#messageList').html('');
            $.cookie('webpro_settings', Number($.cookie('webpro_settings')) - idMap[localId], { expires : 14 });
          }

          requestSettings.firstRequest = true;
          requestSettings.lastMessage = 0;
          messageIndex = [];
        });

        $('#audioDing, #disableFx, #webkitNotifications, #disableRightClick').change(function() {
          var localId = $(this).attr('id');

          if ($(this).is(':checked') && !settings[localId]) {
            settings[localId] = true;
            $.cookie('webpro_settings', Number($.cookie('webpro_settings')) + idMap[localId], { expires : 14 });

            if (localId === 'disableFx') { jQuery.fx.off = true; } // Disable jQuery Effects
            if (localId === 'webkitNotifications' && 'webkitNotifications' in window) { window.webkitNotifications.requestPermission(); } // Ask client permission for webkit notifications
          }
          else if (!$(this).is(':checked') && settings[localId]) {
            settings[localId] = false;
            $.cookie('webpro_settings', Number($.cookie('webpro_settings')) - idMap[localId], { expires : 14 });

            if (localId === 'disableFx') { jQuery.fx.off = false; } // Reenable jQuery Effects
          }
        });

        $("#changeSettingsForm").submit(function() {
          var watchRooms = $('#watchRooms').val(),
            defaultRoom = $('#defaultRoom').val(),
            ignoreList = $('#ignoreList').val(),
            defaultRoomId = (defaultRoom ? roomRef[defaultRoom] : 0),
            fontId = $('#defaultFace option:selected').val(),
            defaultFormatting = ($('#defaultBold').is(':checked') ? 256 : 0) + ($('#defaultItalics').is(':checked') ? 512 : 0);

          $.post(directory + 'api/editUserOptions.php', 'defaultFormatting=' + defaultFormatting + '&defaultColor=' + defaultColour + '&defaultHighlight=' + defaultHighlight + '&defaultRoomId=' + defaultRoomId + '&watchRooms=' + watchRooms + '&ignoreList=' + ignoreList + '&defaultFontface=' + fontId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json', function(json) {
            dia.info('Your settings may or may not have been updated.');
          }); // Send the form data via AJAX.

          $("#changeSettingsDialogue").empty().remove(); // Housecleaning, needed if we want the colorpicker to work in another changesettings dialogue.
          $(".colorpicker").empty().remove(); // Housecleaning, needed if we want the colorpicker to work in another changesettings dialogue.

          return false; // Don't submit the form.
        });

        return false;
      }
    });

    return false;
  },

  /*** END User Settings ***/






  /*** START View My Uploads ***/

  viewUploads : function() {
    dia.full({
      content : '<table align="center"><thead><tr><td>Preview</td><td>File Name</td></tr></thead><tbody id="viewUploadsBody"></tbody></table>',
      width : 1000,
      title : 'View My Uploads',
      position : 'top',
      oF : function() {
        $.ajax({
          url: directory + 'api/getFiles.php?users=' + userId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
          type: 'GET',
          timeout: 2400,
          cache: false,
          success: function(json) {
            var data = '';
            active = json.getFiles.files;

            for (i in active) {
              var fileName = active[i].fileName,
                md5hash = active[i].md5hash,
                sha256hash = active[i].sha256hash,
                fileSize = active[i].fileSize;

              data += '<tr><td><img src="' + directory + 'file.php?sha256hash=' + sha256hash + '" style="max-width: 200px; max-height: 200px;" /></td><td>' + fileName + '</td></tr>';
            }

            $('#viewUploadsBody').html(data);

            return false;
          },
          error: function() {
            dia.error('Could not obtain uploads.');
          }
        });

        return false;
      }
    });
  },

  /*** END View My Uploads ***/






  /*** START Create Room ***/

  editRoom : function(roomIdLocal) {
    if (roomIdLocal) {
      var action = 'edit';
    }
    else {
      var action = 'create';
    }

    dia.full({
      content : window.templates.editRoomForm,
      id : 'editRoomDialogue',
      width : 1000,
      tabs : true,
      oF : function() {
        if (roomIdLocal) {
          $.ajax({
            url: directory + 'api/getRooms.php?rooms=' + roomIdLocal + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
            type: 'GET',
            timeout: 2400,
            cache: false,
            success: function(json) {
              for (var i in json.getRooms.rooms) {
                var data = '',
                  roomName = json.getRooms.rooms[i].roomName,
                  roomId = json.getRooms.rooms[i].roomId,
                  allowedUsers = json.getRooms.rooms[i].allowedUsers,
                  allowedGroups = json.getRooms.rooms[i].allowedGroups,
                  defaultPermissions = json.getRooms.rooms[i].defaultPermissions,
                  allowedUsersArray = [],
                  moderatorsArray = [],
                  allowedGroupsArray = [];

                  for (var j in allowedUsers) {
                    if (allowedUsers[j] & 15 === 15) { // Are all bits up to 8 present?
                      moderatorsArray.push(j);
                    }
                    if (allowedUsers[j] & 7 === 7) { // Are the 1, 2, and 4 bits all present?
                      allowedUsersArray.push(j);
                    }
                  }

                break;
              }

              $('#name').val(roomName); // Current Room Name

              // Prepopulate
              if (allowedUsersArray.length > 0) autoEntry.showEntries('allowedUsers', allowedUsersArray);
              if (moderatorsArray.length > 0) autoEntry.showEntries('moderators', moderatorsArray);
              if (allowedGroupsArray.length > 0) autoEntry.showEntries('allowedGroups', allowedGroupsArray);

              if (defaultPermissions == 7) { // Are All Users Allowed Presently?
                $('#allowAllUsers').attr('checked', true);
                $('#allowedUsersBridge').attr('disabled', 'disabled');
                $('#allowedGroupsBridge').attr('disabled', 'disabled');
                $('#allowedUsersBridge').next().attr('disabled', 'disabled');
                $('#allowedGroupsBridge').next().attr('disabled', 'disabled');
              }

  //            if (mature) {
  //              $('#mature').attr('checked', 'checked');
  //            }

              return false;
            },
            error: function() {
              dia.error('Failed to obtain current room settings from server.');

              return false;
            }
          });
        }

        $.ajax({
          url: directory + 'api/getCensorLists.php?rooms=' + roomIdLocal + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
          type: 'GET',
          timeout: 2400,
          cache: false,
          success: function(json) {
            var data = '';

            for (var i in json.getCensorLists.lists) {
              var listId = json.getCensorLists.lists[i].listId,
                listName = json.getCensorLists.lists[i].listName,
                listType = json.getCensorLists.lists[i].listType,
                listOptions = json.getCensorLists.lists[i].listOptions;

              for (j in json.getCensorLists.lists[i].active) {
                var listStatus = json.getCensorLists.lists[i].active[j].status;
              }

              data += '<label><input type="checkbox" name="list' + listId + '" data-listId="' + listId + '" data-checkType="list" value="true" ' + (listOptions & 2 ? '' : ' disabled="disabled"') + (listStatus === 'block' ? ' checked="checked"' : '') + ' />' + listName + '</label>';
            }

            $('#censorLists').append(data);

            return false;
          },
          error: function() {
            dia.error('Failed to obtain current censor list settings from server.');

            return false;
          }
        });

        // Autocomplete Users and Groups
        $("#moderatorsBridge").autocomplete({ source: userList });
        $("#allowedUsersBridge").autocomplete({ source: userList });
        $("#allowedGroupsBridge").autocomplete({ source: groupList });

        $('#allowAllUsers').change(function() {
          if ($(this).is(':checked')) {
            $('#allowedUsersBridge').attr('disabled', 'disabled');
            $('#allowedGroupsBridge').attr('disabled', 'disabled');
            $('#allowedUsersBridge').next().attr('disabled', 'disabled');
            $('#allowedGroupsBridge').next().attr('disabled', 'disabled');
          }
          else {
            $('#allowedUsersBridge').removeAttr('disabled');
            $('#allowedGroupsBridge').removeAttr('disabled');
            $('#allowedUsersBridge').next().removeAttr('disabled');
            $('#allowedGroupsBridge').next().removeAttr('disabled');
          }
        });

        $("#editRoomForm").submit(function() {
          var name = $('#name').val(),
//            mature = ($('#mature').is(':checked') ? true : false),
            allowedUsers = $('#allowedUsers').val(),
            allowedGroups = $('#allowedGroups').val(),
            moderators = $('#moderators').val(),
            censor = [];

          $('input[data-checkType="list"]').each(function() {
            censor.push($(this).attr('data-listId') + '=' + ($(this).is(':checked') ? 1 : 0));
          });

          censor = censor.join(',');console.log(censor); // TODO

          if (name.length > 20) {
            dia.error('The roomname is too long.');
          }
          else {
            $.post(directory + 'api/editRoom.php', 'action=' + action + '&roomId=' +  roomIdLocal + '&roomName=' + fim_eURL(name) + '&defaultPermissions=' + ($('#allowAllUsers').is(':checked') ? '7' : '0' + '&allowedUsers=' + allowedUsers + '&allowedGroups=' + allowedGroups) + '&moderators=' + moderators + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId, function(json) {
              var errStr = json.editRoom.errStr,
                errDesc = json.editRoom.errDesc,
                createRoomId = json.editRoom.response.insertId;

              if (errStr) {
                dia.error('An error has occured: ' + errDesc);
              }
              else {
                dia.full({
                  content : 'The room has been created at the following URL:<br /><br /><form action="' + currentLocation + '#room=' + createRoomId + '" method="post"><input type="text" style="width: 300px;" value="' + currentLocation + '#room=' + createRoomId + '" name="url" /></form>',
                  title : 'Room Created!',
                  id : 'editRoomResultsDialogue',
                  width : 600,
                  buttons : {
                    Open : function() {
                      $('#editRoomResultsDialogue').dialog('close');
                      standard.changeRoom(createRoomId);

                      return false;
                    },
                    Okay : function() {
                      $('#editRoomResultsDialogue').dialog('close');

                      return false;
                    }
                  }
                });

                $("#editRoomDialogue").dialog('close');
              }
            }); // Send the form data via AJAX.
          }

          return false; // Don't submit the form.
        });

        return false;
      }
    });

    return false;
  },

  /*** END Create Room ***/




  /*** START Private Rooms ***/

  privateRoom : function() {
    dia.full({
      content : '<form action="index.php?action=privateRoom&phase=2" method="post" id="privateRoomForm"><label for="userName">Username</label>: <input type="text" name="userName" id="userName" /><br /><small><span style="margin-left: 10px;">The other user you would like to talk to.</span></small><br /><br />  <button type="submit">Go</button></form>',
      title : 'Enter Private Room',
      id : 'privateRoomDialogue',
      width : 1000,
      oF : function() {
        $('#userName').autocomplete({
          source: userList
        });

        $("#privateRoomForm").submit(function() {
          privateUserName = $("#privateRoomForm > #userName").val(); // Serialize the form data for AJAX.
          privateUserId = userRef[privateUserName];

          standard.privateRoom(privateUserId);

          return false; // Don't submit the form.
        });

        return false;
      }
    });

    return false;
  },

  /*** END Private Rooms ***/




  /*** START Online ***/

  online : function() {
    dia.full({
      content : '<table class="center"><thead><tr class="hrow"><th>User</th><th>Rooms</th></tr></thead><tbody id="onlineUsers"><tr><td colspan="2">Loading...</td></tr></tbody></table>',
      title : 'Active Users',
      id : 'onlineDialogue',
      position : 'top',
      width : 600,
      cF : function() {
        clearInterval(timers.t2);
      }
    });

    function updateOnline() {
      $.ajax({
        url: directory + 'api/getAllActiveUsers.php?fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
        type: 'GET',
        timeout: 2400,
        cache: false,
        success: function(json) {
          var data = '';

          active = json.getAllActiveUsers.users;

          for (i in active) {
            var userName = active[i].userData.userName,
              userId = active[i].userData.userId,
              startTag = active[i].userData.startTag,
              endTag = active[i].userData.endTag,
              roomData = [];

            for (j in active[i].rooms) {
              var roomId = active[i].rooms[j].roomId,
                roomName = active[i].rooms[j].roomName;
              roomData.push('<a href="#room=' + roomId + '">' + roomName + '</a>');
            }
            roomData = roomData.join(', ');

            data += '<tr><td>' + startTag + '<span class="userName" data-userId="' + userId + '">' + userName + '</span>' + endTag + '</td><td>' + roomData + '</td></tr>';
          }

          $('#onlineUsers').html(data);
          contextMenuParseUser('#onlineUsers');

          return false;
        },
        error: function() {
          $('#onlineUsers').html('Refresh Failed');
        }
      });

      return false;
    }

    timers.t2 = setInterval(updateOnline, 2500);

    return false;
  },

  /*** END Online ***/




  /*** START Kick Manager ***/

  manageKicks : function() {
    var kickHtml = '';

    $.ajax({
      url: directory + 'api/getKicks.php?rooms=' + roomId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(json) {
        active = json.getKicks.kicks;

        for (i in active) {
          var kickerId = active[i].kickerData.userId,
            kickerName = active[i].kickerData.userName,
            kickerFormatStart = active[i].kickerData.userFormatStart,
            kickerFormatEnd = active[i].kickerData.userFormatEnd,
            userId = active[i].userData.userId,
            userName = active[i].userData.userName,
            userFormatStart = active[i].userData.userFormatStart,
            userFormatEnd = active[i].userData.userFormatEnd,
            length = active[i].length,
            set = date(active[i].set, true),
            expires = date(active[i].expires, true);

          kickHtml += '<tr><td>' + userFormatStart + '<span class="userName userNameTable" data-userId="' + userId + '">' + userName + '</span>' + userFormatEnd + '</td><td>' + kickerFormatStart + '<span class="userName userNameTable" data-userId="' + kickerId + '">' + kickerName + '</span>' + kickerFormatEnd + '</td><td>' + set + '</td><td>' + expires + '</td><td><button onclick="standard.unkick(' + userId + ', ' + roomId + ')">Unkick</button></td></tr>';
        }

        dia.full({
          content : '<table class="center"><thead><tr class="hrow"><th>User</th><th>Kicked By</th><th>Kicked On</th><th>Expires On</th><th>Actions</th></tr>  </thead><tbody id="kickedUsers">' + kickHtml + '</tbody></table>',
          title : 'Manage Kicked Users in This Room',
          width : 1000
        });

        return false;
      },
      error: function() {
        dia.error('The list of currently kicked users could not be obtained from the server.');

        return false;
      }
    });

    return false;
  },

  /*** END Kick Manager ***/




  /*** START My Kicks ***/

  myKicks : function() {
    var kickHtml = '';

    $.ajax({
      url: directory + 'api/getKicks.php?users=' + userId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(json) {
        active = json.getKicks.kicks;

        for (i in active) {
          var kickerId = active[i].kickerData.userId,
            kickerName = active[i].kickerData.userName,
            kickerFormatStart = active[i].kickerData.userFormatStart,
            kickerFormatEnd = active[i].kickerData.userFormatEnd,
            userId = active[i].userData.userId,
            userName = active[i].userData.userName,
            userFormatStart = active[i].userData.userFormatStart,
            userFormatEnd = active[i].userData.userFormatEnd,
            length = active[i].length,
            set = date(active[i].set, true),
            expires = date(active[i].expires, true);

          kickHtml += '<tr><td>' + userFormatStart + '<span class="userName userNameTable" data-userId="' + userId + '">' + userName + '</span>' + userFormatEnd + '</td><td>' + kickerFormatStart + '<span class="userName userNameTable" data-userId="' + kickerId + '">' + kickerName + '</span>' + kickerFormatEnd + '</td><td>' + set + '</td><td>' + expires + '</td></tr>';
        }

        dia.full({
          content : '<table class="center"><thead><tr class="hrow"><th>User</th><th>Kicked By</th><th>Kicked On</th><th>Expires On</th></tr>  </thead><tbody id="kickedUsers">' + kickHtml + '</tbody></table>',
          title : 'You Have Been Kicked From The Following Rooms',
          width : 1000
        });

        return false;
      },
      error: function() {
        dia.error('The list of currently kicked users could not be obtained from the server.');

        return false;
      }
    });

    return false;
  },

  /*** END Kick Manager ***/




  /*** START Kick ***/

  kick : function() {
    dia.full({
      content : '<form action="#" id="kickUserForm" method="post">  <label for="userName">User</label>: <input type="text" name="userName" id="userName" /><br />  <label for="roomNameKick">Room</label>: <input type="text" id="roomNameKick" name="roomNameKick" /> <br />  <label for="time">Time</label>: <input type="text" name="time" id="time" style="width: 50px;" />  <select name="interval" id="interval">    <option value="1">Seconds</option>    <option value="60">Minutes</option>    <option value="3600">Hours</option>    <option value="86400">Days</option>    <option value="604800">Weeks</option>  </select><br /><br />  <button type="submit">Kick User</button><button type="reset">Reset</button></form>',
      title : 'Kick User',
      id : 'kickUserDialogue',
      width : 1000,
      oF : function() {
        var roomModList = [],
          i = 0;

        for (i = 0; i < roomList.length; i += 1) {
          if (modRooms[roomRef[roomList[i]]] > 0) {
            roomModList.push(roomIdRef[roomRef[roomList[i]]].roomName);
          }
        }

        $('#userName').autocomplete({ source: userList });
        $('#roomNameKick').autocomplete({ source: roomModList });

        $("#kickUserForm").submit(function() {
          var roomNameKick = $('#roomNameKick').val(),
            roomId = roomRef[roomNameKick],
            userName = $('#userName').val(),
            userId = userRef[userName],
            length = Math.floor(Number($('#time').val() * Number($('#interval > option:selected').attr('value'))));

          standard.kick(userId,roomId,length);

          return false; // Don't submit the form.
        });

        return false;
      }
    });

    return false;
  },

  /*** END Kick ***/




  /*** START Help ***/

  help : function() {
    dia.full({
      content : window.templates.help,
      title : 'helpDialogue',
      width : 1000,
      position : 'top',
      tabs : true
    });

    return false;
  },

  /*** END Help ***/




  /*** START Archive ***/

  archive : function(options) {
    dia.full({
      content : '<form id="archiveSearch" action="#" method="get" style="text-align: center;"><table style="text-align: center; margin-left: auto; margin-right: auto;"><thead><tr><th align="center"><small>Search Text:</small></th><th><small>Filter by User:</small></th><th><small>Results per Page:</small></th></tr></thead><tbody><tr><td><input type="text" id="searchText" name="searchText" style="margin-left: auto; margin-right: auto; text-align: left;" /></td><td><input type="text" id="searchUser" name="searchUser" style="margin-left: auto; margin-right: auto; text-align: left;" /></td><td><select id="resultLimit" name="resultLimit" style="margin-left: auto; margin-right: auto; text-align: left;"><option value="10">10</option><option value="25" selected="selected">25</option><option value="50">50</option><option value="100">100</option><option value="500">500</option></select></td></tr></tbody></table></form><br /><br /><table class="center"><thead><tr><th style="width: 20%;">User</th><th style="width: 20%;">Time</th><th style="width: 60%;">Message</th><th>-</th></tr></thead><tbody id="archiveMessageList"></tbody></table><br /><br /><div align="center"><button id="archivePrev"><< Prev</button><button id="export">Export</button><button id="archiveNext">Next >></button></div>',
      title : 'Archive',
      id : 'archiveDialogue',
      position : 'top',
      width : 1000,
      autoOpen : false
    });

    standard.archive({
      roomId: options.roomId,
      idMin: options.idMin,
      callback: function(data) {
        $('#archiveDialogue').dialog('open');
        $("#searchUser").autocomplete({
          source: userList,
          change : function() {
            standard.archive({
              idMax : options.idMax,
              idMin : options.idMin,
              roomId : options.roomId,
              userId : userRef[$('#searchUser').val()],
              search : $('#searchText').val(),
              maxResults : $('#resultLimit').val(),
            });
          }
        });

        return false;
      }
    });

    return false;
  },

  /*** END Archive ***/




  /*** START Copyright ***/

  copyright : function() {
    dia.full({
      content : window.templates.copyright,
      title : 'copyrightDialogue',
      width : 600,
      tabs : true
    });

    return false;
  }

  /*** END Copyright ***/
};

/*********************************************************
************************* END ***************************
************** Repeat-Action Popup Methods **************
*********************************************************/








/*********************************************************
************************ START **************************
********* DOM Event Handling & Window Painting **********
*********************************************************/

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
  if (roomId && (userId | anonId)) { $('#messageInput').removeAttr("disabled"); } // The user is able to post.
  else { $('#messageInput').attr("disabled","disabled"); } // The user is _not_ able to post.
console.log(roomId);


  /*** Call Resize ***/
  windowResize();



  /*** Return ***/
  return false;
}



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
  if (modRooms[roomId] < 1) { $('li > #kick').parent().hide(); $('li > #manageKick').parent().hide(); $('#userMenu a[data-action="kick"]').parent().hide(); $('ul#messageMenu > li > a[data-action="delete"], ul#messageMenuImage > li > a[data-action="delete"], ul#messageMenuLink > li > a[data-action="delete"], ul#messageMenuVideo > li > a[data-action="delete"]').hide(); noModCounter += 2; }
  if (modRooms[roomId] < 2) { $('li > #editRoom').parent().hide(); noModCounter += 1; }

  // Remove Link Categories If They Are to Appear Empty (the counter is incremented in the above code block)
  if (noAdminCounter === 8) { $('li > #modGeneral').parent().hide(); }
  if (noModCounter === 3 && noAdminCounter === 8) { $('#moderateCat').hide(); }

  // Show Login or Logout Only
  if (userId && !anonId) { $('li > #login').parent().hide(); }
  else { $('li > #logout').parent().hide(); }
}



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

    $.ajax({
      url: directory + 'api/getUsers.php?users=' + userId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,
      type: 'GET',
      timeout: 2400,
      success: function(json) {
        active = json.getUsers.users;

        for (i in active) {
          userName = active[i].userName;
          avatarUrl = active[i].avatar;
          profileUrl = active[i].profile;
        }

        switch(action) {
          case 'profile':
          dia.full({
            title : 'User Profile',
            id : 'messageLink',
            content : '<iframe src="' + profileUrl + '" style="width: 100%; height: 80%;" />',
            width: $(window).width() * .8,
            height: $(window).height() * .8,
          });
          break;

          case 'private_im': standard.privateRoom(userId); break;
          case 'kick': popup.kick(userId, roomId); break;
          case 'ban': standard.banUser(userId); break; // TODO
          case 'ignore': standard.ignoreUser(userId); break; // TODO
        }

        return false;
      },
      error: function() {
        dia.error('The information of this user could not be retrieved.');

        return false;
      }
    });

    return false;
  });
}

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

$(document).ready(function() {
    $('head').append('<link rel="stylesheet" id="stylesjQ" type="text/css" href="client/css/' + theme + '/jquery-ui-1.8.16.custom.css" /><link rel="stylesheet" id="stylesFIM" type="text/css" href="client/css/' + theme + '/fim.css" /><link rel="stylesheet" type="text/css" href="client/css/stylesv2.css" />');


    if (fontsize) { $('body').css('font-size', fontsize + 'em'); }


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
        var thisid = $(el).attr('data-userId');

        if (thisid != $('#tooltext').attr('data-lastuserId')) {
          $('#tooltext').attr('data-lastuserId', thisid);
          $.get(directory + 'api/getUsers.php', 'users=' + thisid + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json', function(json) {
            active = json.getUsers.users;

            for (i in active) {
              var userName = active[i].userName,
                userId = active[i].userId,
                startTag = active[i].startTag,
                endTag = active[i].endTag,
                userTitle = active[i].userTitle,
                posts = active[i].postCount,
                joinDate = date(active[i].joinDate, true),
                avatar = active[i].avatar;
            }

            content.html('<div style="width: 400px;">' + (avatar.length > 0 ? '<img alt="" src="' + avatar + '" style="float: left;" />' : '') + '<span class="userName" data-userId="' + userId + '">' + startTag + userName + endTag + '</span>' + (userTitle.length > 0 ? '<br />' + userTitle : '') + '<br /><em>Posts</em>: ' + posts + '<br /><em>Member Since</em>: ' + joinDate + '</div>');

            return false;
          });
        }

        return false;
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
    $('#roomList').bind('click', function() { popup.selectRoom(); }); // Room List
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
  $('#messageList').css('max-width', ((windowWidth - 10) * .75)); // Prevent box-stretching. This is common on... many chats.


  /* Body Padding: 10px
    * Right Area Width: 75%
    * "Enter Message" Table Padding: 10px
    *** TD Padding: 2px (on Standard Styling)
    * Message Input Text Area Padding: 3px */
  $('#messageInput').css('width', (((windowWidth - 10) * .75) - 20 - 2)); // Set the messageInput box to fill width.


  $('body').css('min-height', windowHeight); // Set the body height to equal that of the window; this fixes many gradient issues in theming.
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
