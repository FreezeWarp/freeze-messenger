/* START WebPro
 * Note that: WebPro is not optimised for large sets of rooms. It can handle around 1,000 "normal" rooms. */

//$q($l('errorQuitMessage', 'errorGenericQuit'));
function $q(message, error) {
    $('body').replaceWith(message);
    throw new Error(error || message);
}

/**
 * TODO: DEPRECATED
 * Returns a localised string.
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
 * @copyright Joseph T. Parsons 2017
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

function fim_renderHandlebarsInPlace(tag, name) {
    console.log(tag);

    var id       = tag.attr('id');
    var source   = tag.html();
    var template = Handlebars.compile(source);

    $('<div id="active-' + id + '">' + template(window.phrases) + '</div>').insertAfter(tag);
}

function fim_openView(viewName, options) {
    if ($('.fim-activeView').attr('id') == 'active-view-' + viewName) {
        jQuery.each(options, function(name, value) {
            var setterName = "set" + name.charAt(0).toUpperCase() + name.slice(1);

            if (typeof popup[viewName] != "undefined"
                && typeof popup[viewName][setterName] != "undefined") {
                popup[viewName][setterName](value);
            }
        });
    }

    else {
        tag = $('#view-' + viewName);

        if (tag.length > 0) {
            fim_closeView();
            fim_renderHandlebarsInPlace(tag);

            $('#active-view-' + viewName).addClass('fim-activeView');

            if (typeof popup[viewName] != "undefined") {
                popup[viewName].init(options);
            }
        }
        else {
            throw "Unknown view.";
        }
    }
}

function fim_closeView() {
    $('.fim-activeView').each(function() {
        var viewName = $(this).attr('id').slice(12);

        if (typeof popup[viewName] != "undefined"
            && typeof popup[viewName].close != "undefined") {
            popup[viewName].close(options);
        }

        $(this).remove();
    });
}

/**
 * Hash Parse for URL-Defined Actions.
 *
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 */
function fim_hashParse(options) {
    var urlHashComponents = window.location.hash.split('#'),
        urlHashComponentsMap = Object.assign({}, options);

    for (var i = 0; i < urlHashComponents.length; i++) {
        var componentPieces = urlHashComponents[i].split('=');

        if (componentPieces.length == 2)
            urlHashComponentsMap[componentPieces[0]] = componentPieces[1];
    }

    if (!('room' in urlHashComponentsMap)) {
        urlHashComponentsMap['room'] = window.roomId;
    }
    urlHashComponentsMap['roomId'] = urlHashComponentsMap['room'];

    if (!urlHashComponents[1])
        fim_openView('room', urlHashComponentsMap);

    else if ($('#view-' + urlHashComponents[1].split('=')[0]).length > 0)
        fim_openView(urlHashComponents[1].split('=')[0], urlHashComponentsMap);

    else {
        console.log("no action", urlHashComponentsMap);
        fim_openView('room', urlHashComponentsMap);
    }
}


// 1. default view is implied -- pulls in room=
// 2. get next hash as viewname
// 3. switch on viewname
// 4. pull all valid hash parameters (e.g. editRoom pulls in room=, archive pulls in room=, message=, page=, and so-on)
// 5. goto 2

function fim_getHashRegex(name) {
    return new RegExp('#' + name + '(=([^#]+))?(#|$)');
}

function fim_setHashParameter(name, value) {
    if (window.location.hash.match(fim_getHashRegex(name))) {
        window.location.hash = window.location.hash.replace(fim_getHashRegex(name), '#' + name + '=' + value + '$3');
    }
    else {
        window.location.hash += '#' + name + '=' + value;
    }
}

function fim_removeHashParameter(name) {
    window.location.hash = window.location.hash.replace(fim_getHashRegex(name), '');
}

function fim_atomicRemoveHashParameterSetHashParameter(removeName, setName, setValue) {
    var hash = window.location.hash;

    hash = hash.replace(fim_getHashRegex(removeName), '');

    if (hash.match(fim_getHashRegex(setName))) {
        hash = window.location.hash.replace(fim_getHashRegex(setName), '#' + setName + '=' + setValue + '$3');
    }
    else {
        hash += ('#' + setName + '=' + setValue);
    }

    window.location.hash = hash;
}


/* Requirements
 * All of these are pretty universal, but I want to be explicit with them. */
if (typeof Date === 'undefined') { window.location.href = 'browser.html'; }
else if (typeof Math === 'undefined') { window.location.href = 'browser.html'; }
else if (false === ('encodeURIComponent' in window || 'escape' in window)) { window.location.href = 'browser.html'; }



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

Array.prototype.remove = function(item) {
    return this.splice(this.indexOf(item), 1);
};



// Base64 encode/decode
if (!window.btoa) window.btoa = $.base64.encode;
if (!window.atob) window.atob = $.base64.decode;


// console.log
if (typeof console !== 'object' || typeof console.log !== 'function') {
    var console = {
        log : function() { return false; },
        assert : function() { return false; }
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
    requestSettings = {
        serverSentEvents : false, // We may set this to true if the server supports it.
        //timeout : 2400, // We may increase this dramatically if the server supports longPolling.
        //firstRequest : true,
        //totalFails : 0,
        //lastMessage : 0,
        //lastEvent : 0
    },
    timers = {t1 : false}, // Object
    messageSource,
    eventSource;



/* Objects for Cleanness, Caching. */

var modRooms = {}, // Just a whole bunch of objects.
    userData = {},
    roomLists = {},

    roomList = [], userList = [], groupList = [], // Arrays that serve different purposes, notably looking up IDs from names.
    messageIndex = {},

    roomUlFavHtml = '', roomUlMyHtml = '', // A bunch of strings displayed at different points.
    roomUlHtml = '', ulText = '', roomTableHtml = '',

    active = {}; // This is used as a placeholder for JSON objects where code cleanity is nice.



/* Get Cookies */
window.webproDisplay = {
    'theme' : $.getCookie('webpro_theme', 'absolution'), // Theme (goes into effect in document.ready)
    'fontSize' : $.getCookie('webpro_fontSize', 1), // Font Size (goes into effect in document.ready)
    'settingsBitfield' : $.getCookie('webpro_settings', 2048 + 8192 + 16777216 + 33554432), // Settings Bitfield (goes into effect all over the place); defaults with show avatars, US Time, 12-Hour Format,    Audio Ding
    'audioVolume' : $.getCookie('webpro_audioVolume', .5)
}



/* Sanity Checks */
if (window.webproDisplay.audioVolume > 1 || window.webproDisplay.audioVolume < 0) {
    console.error("audioVolume was " + window.webproDisplay.audioVolume + "; set to .5");

    window.webproDisplay.audioVolume = .5;
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
        url: 'client/js/fim-dev/fim-apiHelper.js',
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
    $.ajax({
        url: window.directory + 'api/serverStatus.php',
        dataType: 'json',
        success: function(json) {
            window.serverSettings = json.serverStatus;
        }
    })
).then(function() {
    /* Our handful of global objects */
    window.fimApi = new fimApi();
    fimApi.registerDefaultExceptionHandler(function(exception) {
        dia.exception(exception);
    });


    window.standard = new standard();
    window.popup = new popup();



    /* Do some final compat testing */
    if (typeof window.EventSource == 'undefined') requestSettings.serverSentEvents = false;
    else requestSettings.serverSentEvents = window.serverSettings.requestMethods.serverSentEvents;

    if (window.serverSettings.installUrl != (window.location.protocol + '//' + window.location.host + window.directory)) dia.error(window.phrases.errorBadInstall);



    /**
     * Blast-off.
     * There's a million things that happen here
     *
     * @author Jospeph T. Parsons <josephtparsons@gmail.com>
     * @copyright Joseph T. Parsons 2017
     */
    $(document).ready(function() {
        /* Draw Template */
        fim_renderHandlebarsInPlace($("#entry-template"));



        /* Private Room Form */
        $('#privateRoomForm input[name=userName]').autocompleteHelper('users');

        $("#privateRoomForm").submit(function() {
            console.log("form submitted");
            var userName = $("#privateRoomForm input[name=userName]").val();
            var userId = $("#privateRoomForm input[name=userName]").attr('data-id');

            whenUserIdAvailable = function(userId) {
                window.location.hash = "#room=p" + [window.userId, userId].join(',');
            };

            if (!userId && userName) {
                whenUserIdAvailable(userId);
            }
            else if (!userName) {
                dia.error('Please enter a username.');
            }
            else {
                var userIdDeferred = $.when(Resolver.resolveUsersFromNames([userName]).then(function(pairs) {
                    whenUserIdAvailable(pairs[userName].id);
                }));
            }

            return false; // Don't submit the form.
        });



        if (window.webproDisplay.fontSize) $('body').css('font-size', window.webproDisplay.fontSize + 'em');
        if (settings.disableFx) jQuery.fx.off = true;


        /*** Window Manipulation (see below) ***/
        $(window).bind('resize', windowResize);
        $(window).bind('hashchange', fim_hashParse);
        document.addEventListener('visibilitychange', function(){
            if(document.visibilityState == 'hidden') {
                windowBlur();
            }
            else {
                windowFocus();
            }
        });


        /*** Image Buttons! ***/
        // todo: move to upload popup
        //$("#imageUploadSubmitButton").button("option", "disabled", true);

        // Room Shower Thing
        $('#showMoreRooms').bind('click', function() { $('#roomListShort').slideUp(); $('#roomListLong').slideDown(); });
        $('#showFewerRooms').bind('click', function() { $('#roomListLong').slideUp(); $('#roomListShort').slideDown(); });


        showLogin = function() {
            /*** Initial Login ***/
            if (window.location.hash.match(/\#sessionHash=/)) {
                standard.initialLogin({
                    grantType : 'access_token',
                    sessionHash : window.location.hash.match(/\#sessionHash=([^\#]+)/)[1],
                    error : function() {
                        if (!window.userId) popup.login(); // The user is not actively logged in.
                    }
                });
            }
            else if ($.cookie('webpro_refreshToken')) {
                standard.initialLogin({
                    grantType : 'refresh_token',
                    refreshToken : $.cookie('webpro_refreshToken'),
                    error : function() {
                        if (!window.userId) popup.login(); // The user is not actively logged in.
                    }
                });
            }
            else if ($.cookie('webpro_username')) {
                standard.initialLogin({
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
        }
        showLogin();

        return false;
    });
}, function() {
    $q('Loading failed. Please refresh.');
});