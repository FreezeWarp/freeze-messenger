/* START WebPro
 * Note that: WebPro is not optimised for large sets of rooms. It can handle around 1,000 "normal" rooms. */

//$q($l('errorQuitMessage', 'errorGenericQuit'));
function $q(message, error) {
  $('body').replaceWith(message);
  throw new Error(error || message);
}

/** Returns a localised string.
 * Note that this currently is using "window.phrase", as that is how I did things prior to creating this function, but I will likely change this later.
 * (Also, this framework is decidedly original and custom-made. That said, if you like it, you are free to take it, assuming you follow WebPro's GPL licensing guidelines.)
 * 
 * @param stringName - The name of the string we will return.
 * @param substitutions - Strings can contain simple substitutions of their own. Strange though this is, we feel it is better than using a template when no HTML is involved.
 * @param extra - Additional replacements values, in addition to those stored in window.phrases.
 * 
 * @todo No optimisation has yet been made. We will need to do at least some profiling later on.
 * 
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2014
 */
function $l(stringName, substitutions, extra) {
  var phrase = false,
    stringParsed = '',
    eachBreak = false;
  
  // We start be breaking up the stringName (which needs to be seperated with periods), to use the [] format. This is mainly neccissary because of integer indexes (JS does not support "a.b.1"), but these indexes are better anyway for arrays.
  stringNameParts = stringName.split('.');
  
  $.each(stringNameParts, function(index, value) {
    stringParsed += ('[\'' + value + '\']');
    
    if (undefined === eval("window.phrases" + stringParsed + " || extra" + stringParsed)) {
      eachBreak = true;
      return false;
    }
  });

  if ((eachBreak === false) && (phrase = eval("window.phrases" + stringParsed + " || extra" + stringParsed))) {
    if (substitutions) {
      $.each(substitutions, function(index, value) {
        phrase = phrase.replace('{{{{' + index + '}}}}', value);
      });
    }
    
    return phrase;
  }
  else {
    console.log('Missing phrase "' + stringName + '"');
    return '~~' + stringName;
  }
}

/** Returns a formatted template.
 * 
 * @param stringName - The name of the template we will return.
 * @param substitutions - A list of "additional" template substitutions. Those included in the language.json files are automatically included.
 * 
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2014
 */
function $t(templateName, substitutions) {
  if (!(templateName in window.templates)) {
    $q('Template Error: ' + templateName + ' not found.');
    
    return false;
  }
  else {
    templateData = window.templates[templateName];
    
    return templateData = templateData.replace(/\{\{\{\{([a-zA-Z0-9\.]+)\}\}\}\}/g, function($1, $2) {
      return $l($2, false, substitutions);
    });
  }
}


/* Requirements
 * All of these are pretty universal, but I want to be explicit with them. */
if (typeof Date === 'undefined') { window.location.href = 'browser.php'; }
else if (typeof Math === 'undefined') { window.location.href = 'browser.php'; }
else if (false === ('encodeURIComponent' in window || 'escape' in window)) { window.location.href = 'browser.php'; }



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
if (!window.btoa) window.btoa = $.base64.encode;
if (!window.atob) window.atob = $.base64.decode;


// console.log
if (typeof console !== 'object' || typeof console.log !== 'function') {
  var console = {
    log : function() { return false; }
  };
}


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
  timers = {t1 : false}, // Object
  messageSource,
  eventSource;



/* Objects for Cleanness, Caching. */

var modRooms = {}, // Just a whole bunch of objects.
  userData = {},
  roomLists = {},

  roomList = [], userList = [], groupList = [], // Arrays that serve different purposes, notably looking up IDs from names.
  messageIndex = [],

  roomUlFavHtml = '', roomUlMyHtml = '', // A bunch of strings displayed at different points.
  roomUlHtml = '', ulText = '', roomTableHtml = '',

  active = {}; // This is used as a placeholder for JSON objects where code cleanity is nice.



/* Get Cookies */
window.webproDisplay = {
  'theme' : $.getCookie('webpro_theme', 'absolution'), // Theme (goes into effect in document.ready)
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




var directory = window.location.pathname.split('/').splice(0, window.location.pathname.split('/').length - 2).join('/') + '/', // splice returns the elements removed (and modifies the original array), in this case the first two; the rest should be self-explanatory
  currentLocation = window.location.protocol + '//' + window.location.host + directory + 'webpro/';





$.when(
  $.ajax({
    url: 'client/data/config.json',
    dataType: 'json',
    success: function(data) { window.fim_config = data; }
  }),
  $.ajax({
    url: 'client/data/language_enGB.json',
    dataType: 'json',
    success: function(data) { window.phrases = data; }
  }),
  $.ajax({
    url: 'client/data/templates.json',
    dataType: 'json',
    success: function(data) { window.templates = data; }
  }),
  $.ajax({
    url: 'client/js/fim-dev/fim-api.js',
    dataType: 'script'
  }),
  $.ajax({
    url: 'client/js/fim-dev/fim-standard.js',
    dataType: 'script'
  }),
  $.ajax({
    url: 'client/js/fim-dev/fim-popup.js',
    dataType: 'script'
  }),
  $.ajax({
    url: 'client/js/fim-dev/fim-loader.js',
    dataType:'script'
  }),
  $.get('client/css/' + window.webproDisplay.theme + '/jquery-ui-1.10.4.min.css', function(response) {
    $('#stylesjQ').text(response);
  }),
  $.get('client/css/' + window.webproDisplay.theme + '/fim.css', function(response) {
    $('#stylesVIM').text(response);
  }),
  $.get("client/css/stylesv2.css", function(response) {
    $('#stylesv2').text(response);
  }),
  $.ajax({
    url: window.directory + 'api/getServerStatus.php?fim3_format=json',
    dataType: 'json',
    success: function(json) {
      window.serverSettings = json.getServerStatus.serverStatus;
    }
  })
 ).then(function() {
  if (typeof window.EventSource == 'undefined') requestSettings.serverSentEvents = false;
  else requestSettings.serverSentEvents = window.serverSettings.requestMethods.serverSentEvents;

  if (window.serverSettings.installUrl != (window.location.protocol + '//' + window.location.host + window.directory)) dia.error(window.phrases.errorBadInstall);



  /**
   * Blast-off.
   * There's a million things that happen here
   *
   * @author Jospeph T. Parsons <josephtparsons@gmail.com>
   * @copyright Joseph T. Parsons 2014
   */
  $(document).ready(function() {
    $('body').append($t('main'));
    $('body').append($t('contextMenu'));


    if (window.webproDisplay.fontSize) $('body').css('font-size', window.webproDisplay.fontSize + 'em');
    if (settings.disableFx) jQuery.fx.off = true;


    /*** Create the Accordion Menu ***/
    $('#menu').accordion({
      heightStyle : 'fill',
      navigation : true,
      collapsible: true,
      active : Number($.cookie('webpro_menustate')) - 1,
      activate: function(event, ui) {
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
    $('#logout').bind('click', function() { standard.logout(); }); // Logout
    $('a#kick').bind('click', function() { popup.kick(); }); // Kick
    $('a#privateRoom').bind('click', function() { popup.privateRoom(); }); // Private Room
    $('a#manageKick').bind('click', function() { popup.manageKicks({'roomIds' : [window.roomId]}); }); // Manage Kicks
    $('a#myKicks').bind('click', function() { popup.manageKicks({'userIds' : [window.userId]}); }); // Manage Kicks
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
      maxResults: 40   // *optional -- defined as 10 results by default
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
    $(window).bind('hashchange', fim_hashParse);


    /*** Initial Login ***/
    if ($.cookie('webpro_username')) {
      standard.login({
        username : $.cookie('webpro_username'),
        password : $.cookie('webpro_password'),
        error : function() {
          if (!window.userId) popup.login(); // The user is not actively logged in.
        }
      });
    }
    else {
      popup.login();
    }


    return false;
  });
}, function() {
  $q('Loading failed. Please refresh.');
});