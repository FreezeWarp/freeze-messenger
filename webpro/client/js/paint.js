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

Handlebars.registerHelper("contains", function( value, array, options ){
    // fallback...
    array = ( array instanceof Array ) ? array : [array];
    return (array.indexOf(value) > -1) ? options.fn( this ) : "";
});

function fim_renderHandlebarsInPlace(tag) {
    var id       = tag.attr('id');
    var source   = tag.html();
    var template = Handlebars.compile(source);

    $('#active-' + id).remove();

    $('<div id="active-' + id + '">' + template(fim_getHandlebarsPhrases()) + '</div>').insertAfter(tag);
}

function fim_renderHandlebars(tag, target) {
    var id       = tag.attr('id');
    var source   = tag.html();
    var template = Handlebars.compile(source);

    $('#active-' + id).remove();

    $(target).html($('<div id="active-' + id + '">' + template(fim_getHandlebarsPhrases()) + '</div>'));
}

function fim_getHandlebarsPhrases(extra) {
    if (!extra) extra = {};

    return Object.assign({}, window.phrases, {serverSettings : window.serverSettings, activeLogin : window.activeLogin}, extra);
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

        if (typeof popup[viewName].retrieve != "undefined")
            popup[viewName].retrieve();
    }

    else {
        tag = $('#view-' + viewName);

        if (tag.length > 0) {
            fim_closeView();
            fim_renderHandlebars(tag, $('#content'));

            $('#active-view-' + viewName).addClass('fim-activeView');

            if (typeof popup[viewName] != "undefined") {
                popup[viewName].init(options); // transitional; TODO: remove

                jQuery.each(options, function(name, value) {
                    var setterName = "set" + name.charAt(0).toUpperCase() + name.slice(1);

                    if (typeof popup[viewName] != "undefined"
                        && typeof popup[viewName][setterName] != "undefined") {
                        popup[viewName][setterName](value);
                    }
                });

                if (typeof popup[viewName].retrieve != "undefined")
                    popup[viewName].retrieve();
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

    // Build the map of properties with corresponding values.
    for (var i = 0; i < urlHashComponents.length; i++) {
        var componentPieces = urlHashComponents[i].split('=');

        if (componentPieces.length == 2)
            urlHashComponentsMap[componentPieces[0]] = componentPieces[1];
    }

    // Set the roomId property automatically to the global window roomId
    if (!('room' in urlHashComponentsMap)) {
        urlHashComponentsMap['room'] = window.roomId;
    }
    urlHashComponentsMap['roomId'] = urlHashComponentsMap['room'];

    // If no first hash component, open the default (room) view.
    if (!urlHashComponents[1])
        fim_openView('room', urlHashComponentsMap);

    // If we have view data for the hash component, open it.
    else if ($('#view-' + urlHashComponents[1].split('=')[0]).length > 0)
        fim_openView(urlHashComponents[1].split('=')[0], urlHashComponentsMap);

    // Otherwise, fallback to the default (room) view
    else {
        console.log("no action", urlHashComponentsMap);
        fim_openView('room', urlHashComponentsMap);
    }
}

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



/* Prototyping */

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

// Array remove
Array.prototype.remove = function(item) {
    return this.splice(this.indexOf(item), 1);
};

// console.log
if (typeof console !== 'object' || typeof console.log !== 'function') {
    var console = {
        log : function() { return false; },
        assert : function() { return false; }
    };
}



/* Define Global Variables */

var userId, // The user ID who is logged in.
    roomId, // The ID of the room we are in.
    sessionHash, // The session hash of the active user.
    anonId, // ID used to represent anonymous posters.
    prepopup,
    serverSettings;



/* Function-Specific Variables */

window.isBlurred = false; // By default, we assume the window is active and not blurred.
var favicon = $('#favicon').attr('href'),
    requestSettings = {
        serverSentEvents : false, // We may set this to true if the server supports it.
        //timeout : 2400, // We may increase this dramatically if the server supports longPolling.
        //firstRequest : true,
        //totalFails : 0,
        //lastMessage : 0,
        //lastEvent : 0
    },
    timers = {t1 : false},
    messageIndex = {};



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
    $.ajax({ // TODO?
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
        url: 'client/js/fim-all.js',
        dataType: 'script'
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


        /*** Context Menus ***/
        var contextAction_msgLink = function(roomId, messageId) {
            dia.full({
                title : 'Link to this Message',
                content : $('<span>').text('This message can be bookmarked using the following archive link:').append(
                    $('<br>'), $('<br>'), $('<input>').attr({
                        type : "text",
                        value : currentLocation + '#page=archive#room=' + roomId + '#message=' + messageId,
                        autofocus : true,
                        id : 'messageLink-' + roomId + '-' + messageId,
                        style : "width: 100%;"
                    })).prop('outerHTML'),
                oF : function() {
                    $('#' + 'messageLink-' + roomId + '-' + messageId).focus().select();
                }
            });
        };

        var contextAction_msgDelete = function(roomId, messageId) {
            dia.confirm({
                text : 'Are you sure you want to delete this message?',
                'true' : function() {
                    standard.deleteMessage(roomId, messageId);
                }
            });
        };

        var contextAction_msgEdit = function(messageId) {
            $('#message' + messageId + ' .messageText').dblclick();
        };


        var classNames = {
            //hover:            'bg-primary',          // Item hover
            disabled:         'bg-inverse',       // Item disabled
            visible:          'bg-primary',        // Item visible
            notSelectable:    'not-selectable', // Item not selectable
        }


        $.contextMenu({
            classNames : classNames,
            selector : '.messageText',
            items : {
                delete : {
                    name : 'Delete',
                    callback: function() {
                        contextAction_msgDelete($(this).attr('data-roomid'), $(this).attr('data-messageid'))
                    }
                },

                link : {
                    name : 'Link',
                    callback: function() {
                        contextAction_msgLink($(this).attr('data-roomid'), $(this).attr('data-messageid'))
                    }
                },

                edit : {
                    name : 'Edit',
                    callback : function() {
                        contextAction_msgEdit($(this).attr('data-messageid'))
                    },
                    visible : function() {
                        return $(this).parent('.messageLine').find('.userName').attr('data-userid') == window.userId && window.activeLogin.userData.permissions.editOwnPosts;
                    }
                }
            }
        });

        $.contextMenu({
            classNames : classNames,
            selector : '.messageText img', // Todo: exclude emoticons
            items : {
                delete : {
                    name : 'Delete',
                    callback: function() {
                        contextAction_msgDelete($(this).closest('.messageText').attr('data-roomid'), $(this).closest('.messageText').attr('data-messageid'))
                    }
                },

                link : {
                    name : 'Link',
                    callback: function() {
                        contextAction_msgLink($(this).closest('.messageText').attr('data-roomid'), $(this).closest('.messageText').attr('data-messageid'))
                    }
                },

                edit : {
                    name : 'Edit',
                    callback : function() {
                        contextAction_msgEdit($(this).closest('.messageText').attr('data-messageid'))
                    },
                    visible : function() {
                        return $(this).closest('.messageLine').find('.userName').attr('data-userid') == window.userId && window.activeLogin.userData.permissions.editOwnPosts;
                    }
                },

                click : {
                    name : 'URL',
                    callback : function() {
                        var url = $(this).attr('src').replace(/&thumbnailWidth=[^\&]*/, '')
                                                     .replace(/&thumbnailHeight=[^\&]*/, '');
                        dia.full({
                            title : 'Copy Image URL',
                            content : $('<div>').append(
                                $('<img>').attr({
                                    src : url,
                                    style : 'width: 100%;'
                                }), $('<br>'), $('<br>'), $('<input>').attr({
                                    type : 'text',
                                    name : 'url',
                                    value : url.replace(/&parentalAge=[^\&]*/, '').replace(/&parentalFlag[]=[^\&]*/, ''),
                                    style : 'width: 100%'
                                })).prop('outerHTML'),
                            width : 800,
                            position : 'top',
                            oF : function() {
                                $('input[name=url]', this).first().focus();
                            }
                        });
                    },
                }
            }
        });

        $.contextMenu({
            classNames : classNames,
            selector : '.messageText a', // Todo: exclude emoticons
            items : {
                delete : {
                    name : 'Delete',
                    callback: function() {
                        contextAction_msgDelete($(this).closest('.messageText').attr('data-roomid'), $(this).closest('.messageText').attr('data-messageid'))
                    }
                },

                link : {
                    name : 'Link',
                    callback: function() {
                        console.log($(this), $(this).closest('.messageText'), $(this).closest('.messageText').attr('data-roomid'))
                        contextAction_msgLink($(this).closest('.messageText').attr('data-roomid'), $(this).closest('.messageText').attr('data-messageid'))
                    }
                },

                edit : {
                    name : 'Edit',
                    callback : function() {
                        contextAction_msgEdit($(this).closest('.messageText').attr('data-messageid'))
                    },
                    visible : function() {
                        return $(this).closest('.messageLine').find('.userName').attr('data-userid') == window.userId && window.activeLogin.userData.permissions.editOwnPosts;
                    }
                },

                click : {
                    name : 'URL',
                    callback : function() {
                        dia.full({
                            title : 'Copy URL',
                            position : 'top',
                            content : $('<input>').attr({
                                type : 'text',
                                name : 'url',
                                value : $(this).attr('href'),
                                style : 'width: 100%;'
                            }).prop('outerHTML'),
                            width : 800,
                            oF : function() {
                                $('input[name=url]', this).first().focus();
                            }
                        });
                    },
                }
            }
        });

        $.contextMenu({
            classNames : classNames,
            selector : '.userName', // Todo: exclude emoticons
            items : {
                profile : {
                    name : 'Profile',
                    callback : function() {
                        var resolver = $.when(Resolver.resolveUsersFromIds([userId])).then(function(userData) {
                            dia.full({
                                title : 'User Profile',
                                id : 'messageLink',
                                content : (userData[userId].profile ? '<iframe src="' + userData[userId].profile + '" style="width: 100%; height: 90%;" /><br /><a href="' + userData[userId].profile + '" target="_BLANK">Visit The Page Directly</a>' : 'The user has not yet registered a profile.'),
                                width: $(window).width() * .8,
                                height: $(window).height() * .9
                            });
                        });
                    }
                },

                privateIm : {
                    name : 'Private IM',
                    callback : function() {
                        window.location.hash = '#room=p' + [window.userId, $(this).attr('data-userid')].join(',');
                    },
                    visible : function() {
                        return $(this).attr('data-userid') != window.userId;
                    }
                },

                kick : {
                    name : 'Kick',
                    callback : function() {
                        popup.kick($('data-userid'), window.roomId)
                    },
                    visible : function() {
                        return false; // TODO!
                    }
                },

                ban : {
                    name : 'Ban',
                    callback : function() {
                        standard.banUser($('data-userid'))
                    },
                    visible : function() {
                        return false; // TODO!
                    }
                },

                ignore : {
                    name : 'Ignore',
                    callback : function() {
                        dia.alert('This functionality is not yet implemented.');
                    }
                },
            }
        });
        /**
         * (Re-)Parse the "room" context menus.
         * TODO
         *
         * @author Jospeph T. Parsons <josephtparsons@gmail.com>
         * @copyright Joseph T. Parsons 2017
         */
        /*function contextMenuParseRoom() {
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
                        case 'archive': popup.archive.init({roomId : roomId}); break;
                        case 'enter': standard.changeRoom(roomId); break;
                    }

                    return false;
                });

            return false;
        }*/


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