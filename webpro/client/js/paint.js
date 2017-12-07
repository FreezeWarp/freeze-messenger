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

Handlebars.registerHelper("byte", function(fileSize) {
    var fileSize2 = fileSize;

    for (i in window.phrases.byteUnits) {
        if (fileSize > i)
            fileSize2 = (fileSize / i) + window.phrases.byteUnits[i];
    }

    return fileSize2;
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

    return Object.assign({}, window.phrases, {serverSettings : window.serverSettings, activeLogin : window.activeLogin, hash : fim_hashToMap()[1]}, extra);
}

var openObjectInstance;
function fim_openView(viewName, options) {
    if ($('.fim-activeView').attr('id') == 'active-view-' + viewName) {
        jQuery.each(options, function(name, value) {
            var setterName = "set" + name.charAt(0).toUpperCase() + name.slice(1);

            if (typeof openObjectInstance[setterName] != "undefined") {
                openObjectInstance[setterName](value);
            }
        });

        if (typeof openObjectInstance.retrieve != "undefined")
            openObjectInstance.retrieve();
    }

    else {
        // Close the existing object
        if (openObjectInstance && typeof openObjectInstance.close !== "undefined") {
            console.log("close view2");
            openObjectInstance.close();
        }

        $('.fim-activeView').each(function() {
            $(this).remove();
        });


        // Set the new object
        if (typeof popup[viewName] === "function") {
            openObjectInstance = new popup[viewName]();
        }
        else if (typeof popup[viewName] === "object") {
            openObjectInstance = popup[viewName];
        }
        else {
            throw "View is invalid type.";
        }


        // Find the view tag
        tag = $('#view-' + viewName);

        if (tag.length > 0) {
            // Render view
            fim_renderHandlebars(tag, $('#content'));
            $('#active-view-' + viewName).addClass('fim-activeView');

            // Run init
            openObjectInstance.init(options); // transitional; TODO: remove

            // Run setters
            jQuery.each(options, function(name, value) {
                var setterName = "set" + name.charAt(0).toUpperCase() + name.slice(1);

                if (typeof openObjectInstance[setterName] != "undefined") {
                    openObjectInstance[setterName](value);
                }
            });

            // Run retrieve method
            if (typeof openObjectInstance.retrieve != "undefined")
                openObjectInstance.retrieve();
        }
        else {
            throw "Unknown view.";
        }
    }
}

/**
 * Hash Parse for URL-Defined Actions.
 *
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 */
function fim_hashParse(options) {
    var hashToMap = fim_hashToMap(options),
        urlHashComponents = hashToMap[0],
        urlHashComponentsMap = hashToMap[1];

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

function fim_hashToMap(options) {
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

    return [urlHashComponents, urlHashComponentsMap];
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

function fim_debounce(func, wait, immediate) {
    var timeout;
    return function() {
        var context = this, args = arguments;
        var later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        var callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
}

function fim_showLoader() {
    $('<div class="ui-widget-overlay" id="waitOverlay"></div>').appendTo('body').width($(document).width()).height($(document).height());
    $('<img src="images/ajax-loader.gif" id="waitThrobber" />').appendTo('body').css('position', 'absolute').offset({ left : (($(window).width() - 220) / 2), top : (($(window).height() - 19) / 2)});
}

function fim_hideLoader() {
    $('#waitOverlay, #waitThrobber').empty().remove();
}

function fim_messagePreview(container, content) {
    switch (container) {
        case 'image': return '<img src="' + content + '" class="img-fluid"/>'; break;
        case 'video': return '<video src="' + content + '" style="max-height: 200px; max-width: 200px;">Video Preview Not Supported</video>'; break;
        case 'audio': return '<audio src="' + content + '" style="max-height: 200px; max-width: 200px;">Audio Preview Not Supported</video>'; break;
        case 'text': return 'No Preview Available'; break;
        case 'html': return 'No Preview Available'; break;
        case 'archive': return 'No Preview Available'; break;
        case 'other': return 'No Preview Available'; break;
        default: return 'No Preview Available'; break;
    }
}

/**
 * Formats a timestamp into a date string.
 *
 * @param int timestamp - The UNIX timestamp that will be formatted.
 * @param bool full - If true, will include
 *
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 */
function fim_dateFormat(timestamp, options) {
    // Create the date object; set it to the specified timestamp.
    var jsdate = new Date;
    jsdate.setTime(timestamp * 1000);

    if (typeof options === "undefined") {
        var options = {hour: "numeric", minute: "numeric", second: "numeric"};

        var today = new Date;
        var lastMidnight = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 0, 0, 0, 0).getTime() / 1000; // Finds the date of the last midnight as a timestamp.

        if (timestamp < lastMidnight) {
            options.day = "numeric";
            options.month = "numeric";
            options.year = "2-digit";
        }
    }

    return jsdate.toLocaleString(undefined, options);
}


function fim_youtubeParse($1) {
    if ($1.match(regexs.youtubeFull) || $1.match(regexs.youtubeShort)) {
        var code = '';

        // Parse Out the Code
        if ($1.match(regexs.youtubeFull) !== null)
            code = $1.replace(regexs.youtubeFull, "$8");
        else if ($1.match(regexs.youtubeShort) !== null)
            code = $1.replace(regexs.youtubeShort, "$5");


        // Embed the Video, or a Link to It
        if (settings.disableVideo) return $('<a>').attr({
            'href' : 'https://www.youtu.be/' + code,
            'target' : '_BLANK'
        }).text('[Youtube Video]');

        else return $('<iframe>').attr({
            'width' : 560,
            'height' : 315,
            'src' : 'https://www.youtube.com/embed/' + code + '?rel=0',
            'frameborder' : 0,
            'allowfullscreen' : true
        });
    }

    else {
        return fim_formatAsUrl($1);
    }
}



function fim_formatAsImage(imageUrl) {
    return $('<a target="_BLANK" class="imglink">').attr('href', imageUrl).append(
        settings.disableImage ? $('<span>').text('[IMAGE]')
            : $('<img class="inlineImage" />').attr('src', imageUrl + (imageUrl.slice(0, window.serverSettings.installUrl.length) === window.serverSettings.installUrl ? "&" + $.param({
                'thumbnailWidth' : 400,
                'thumbnailHeight' : 400,
            }) : '')) // todo: only for files on install
    );
}

function fim_formatAsVideo(videoUrl) {
    return settings.disableVideo ? $('<a target="_BLANK">[Video]</a>').attr('href', videoUrl)
        : $('<video controls>').attr('src', videoUrl);
}

function fim_formatAsAudio(audioUrl) {
    return settings.disableVideo ? $('<a target="_BLANK">[Audio]</a>').attr('href', audioUrl)
        : $('<audio controls>').attr('src', audioUrl);
}

function fim_formatAsEmail(email) {
    return $('<a target="_BLANK">').attr('href', 'mailto:' . email).text(email);
}

function fim_formatAsUrl(url) {
    if (url.match(/^(http|https|ftp|data|gopher|sftp|ssh)\:/)) // Certain protocols (e.g. "javascript:") could be malicious. Thus, we use a whitelist of trusted protocols instead.
        return $('<a target="_BLANK">').attr('href', url).text(url);
    else
        return $('<span>').text('[Broken Link: ' + url + ']');
}


/**
 * Formats received message data for display in either the message list or message table.
 *
 * @param object json - The data to format.
 * @param string format - The format to use.
 *
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 */
function fim_messageFormat(json, format) {


    var data,
        text = json.text,
        messageTime = json.time,
        messageId = json.id,
        roomId = json.roomId,
        flag = json.flag,
        userId = Number(json.userId),
        userNameDeferred = fim_getUsernameDeferred(userId);


    text = text.replace(/\</g, '&lt;').replace(/\>/g, '&gt;').replace(/\n/g, '<br />');


    switch (flag) {
        case 'source': text = fim_youtubeParse(text); break; // Youtube, etc.
        case 'image': text = fim_formatAsImage(text); break; // // Image
        case 'video': text = fim_formatAsVideo(text) ; break; // Video
        case 'audio': text = fim_formatAsAudio(text); break; // Audio
        case 'email': text = fim_formatAsEmail(text); break; // Email Link
        case 'url': case 'text': case 'html': case 'archive': case 'other': // Various Files and URLs
        text = fim_formatAsUrl(text);
        break;

        // Unspecified
        default:
            // URL Autoparse (will also detect youtube & image)
            text = $('<span>').text(text);

            text.html(text.text().replace(regexs.url, function($1) {
                if ($1.match(regexs.url2)) {
                    var $2 = $1.replace(regexs.url2, "$2");
                    $1 = $1.replace(regexs.url2, "$1"); // By doing this one second we don't have to worry about storing the variable first to get $2
                }
                else {
                    var $2 = '';
                }

                /* Youtube, Image, URL Parsing */
                if ($1.match(regexs.youtubeFull) || $1.match(regexs.youtubeShort)) // Youtube Autoparse
                    return fim_youtubeParse($1).prop('outerHTML') + $2;

                else if ($1.match(regexs.image)) // Image Autoparse
                    return fim_formatAsImage($1).prop('outerHTML') + $2;

                else // Normal URL
                    return fim_formatAsUrl($1).prop('outerHTML') + $2;
            }));

            // "/me" parse
            if (/^\/me/.test(text.text())) {
                text.text(text.text().replace(/^\/me/,''));

                $.when(userNameDeferred).then(function(pairs) {
                    text.html($('<span style="color: red; padding: 10px; font-weight: bold;">').text('* ' + pairs[userId].name + ' ' + text.text()).prop('outerHTML'));
                });
            }

            // "/topic" parse
            else if (/^\/topic/.test(text.text())) {
                text.text(text.text().replace(/^\/topic/,''));

                $.when(userNameDeferred).then(function(pairs) {
                    text.html($('<span style="color: red; padding: 10px; font-weight: bold;">').text('* ' + pairs[userId].name + ' changed the topic to "' + text.text().trim() + '".').prop('outerHTML'));
                });
            }

            jQuery.each(serverSettings.emoticons, function(index, emoticon) {
                text.contents()
                    .filter(function() {
                        return this.nodeType === 3; //Node.TEXT_NODE
                    }).each(function() {
                    $(this).replaceWith($(this).text().replace(new RegExp(emoticon.emoticonText.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&"), "gi"), function() {
                        return $('<img>').attr('src', emoticon.emoticonFile).prop('outerHTML')
                    }));
                });
            });
            break;
    }


    switch (format) {
        case 'table':
            data = $('<tr style="word-wrap: break-word;">').attr({
                'id': "archiveMessage' + messageId + '"
            }).append(
                $('<td>').append(
                    fim_buildUsernameTag($('<span class="userName userNameTable">'), userId, userNameDeferred)
                )
            ).append(
                $('<td>').text(fim_dateFormat(messageTime))
            ).append(
                $('<td>').append(
                    fim_buildMessageLine(text, messageId, userId, roomId, messageTime, userNameDeferred).append(text)
                )
            );
            break;

        case 'list':
            var messageLine = fim_buildMessageLine(text, messageId, userId, roomId, messageTime, userNameDeferred);

            if (settings.showAvatars) {
                messageLine.popover({
                    content : function() {
                        return fim_dateFormat($(this).attr('data-time'))
                    },
                    html : false,
                    trigger : 'hover',
                    placement : 'bottom'
                });
            }
            data = $('<span>').attr({
                'id': 'message' + messageId,
                'class': 'messageLine' + (settings.showAvatars ? ' messageLineAvatar' : '')
            }).append(
                $('<span class="usernameDate">').append(
                    fim_buildUsernameTag($('<span>'), userId, userNameDeferred, settings.showAvatars, !settings.showAvatars)
                ).append(
                    !settings.showAvatars ?
                        $('<span class="date">').css({'padding-right':'10px','letter-spacing':'-1px'}).text('@ ').append($('<em>').text(fim_dateFormat(messageTime)))
                        : ''
                )
            ).append(messageLine);
            break;
    }


    /* Format for Table/List Display */
    return data;
}

function fim_buildUsernameTag(tag, userId, deferred, includeAvatar, includeUsername) {
    if (includeAvatar == undefined)
        includeAvatar = true;
    if (includeUsername == undefined)
        includeUsername = true;


    $.when(deferred).then(function(pairs) {
        var userName = pairs[userId].name,
            userNameFormat = pairs[userId].nameFormat,
            avatar = pairs[userId].avatar ? pairs[userId].avatar : 'client/images/blankperson.png',
            style = settings.disableFormatting ? '' : pairs[userId].messageFormatting;

        tag.attr({
            'class': 'userName' + (includeAvatar ? ' userNameAvatar' : ''),
            'style': (includeUsername ? userNameFormat : ''),
            'data-userId': userId,
            'data-userName': userName,
            'data-avatar': avatar,
            'tabindex': 1000
        }).append(
            includeAvatar
                ? $('<img>').attr({
                    'alt': userName,
                    'src': avatar ? avatar : 'client/images/blankperson.png'
                })
                : ''
        ).append(
            includeUsername
                ? $('<span>').text(userName)
                : ''
        );

        tag.popover({
            content : function() {
                var el = $('<div class="row no-gutters">');
                var data = $('<div class="col">').append(
                    $('<div class="userName">').attr({
                        'data-userId' : userId,
                        'style' : userNameFormat
                    }).css('font-weight', 'bold').text(userName),
                    $('<hr>')
                );

                if (pairs[userId].bio)
                    data.append($('<div>').text(pairs[userId].bio));

                if (pairs[userId].profile)
                    data.append($('<div>').append($('<em><strong>Profile</strong></em>'), ': ', $('<a>').attr('href', pairs[userId].profile).text(pairs[userId].profile)));

                if (pairs[userId].joinDate)
                    data.append($('<div>').append($('<em><strong>Member Since</strong></em>'), ': ', $('<span>').text(fim_dateFormat(pairs[userId].joinDate, {year : "numeric", month : "numeric", day : "numeric"})))); // TODO:just date

                el.append(
                    $('<div class="col-sm-auto">').append(
                        $('<img style="max-height: 200px; max-width: 200px;" class="mr-2">').attr('src', avatar)
                    ),
                    data
                );

                return el.prop('outerHTML');
            },
            html : true,
            trigger : 'hover',
            placement : 'auto',
            container: tag
        }).on("show.bs.popover", function(e){
            console.log($(this).data("bs.popover"), $(this).data("bs.popover").tip)
            $($(this).data("bs.popover").tip).css({"max-width": "600px"});
        });
    });

    return tag;
}

function fim_buildRoomNameTag(tag, roomId, deferred) {
    $.when(deferred).then(function(pairs) {
        var roomName = pairs[String(roomId)].name;

        tag.attr({
            'class': 'roomName',
            'data-roomId': roomId,
            'data-roomName': roomName,
            'tabindex': 1000
        }).append(
            $('<a>').attr('href', '#room=' + roomId).text(roomName)
        );
    });

    return tag;
}

function fim_buildMessageLine(text, messageId, userId, roomId, messageTime, userNameDeferred) {
    var tag = $('<span>');

    tag.attr({
        'class': 'messageText',
        'data-messageId': messageId,
        'data-roomId': roomId,
        'data-time': messageTime,
        'tabindex': 1000
    }).append(text);

    if (window.userId == userId && window.activeLogin.userData.permissions.editOwnPosts) {
        tag.on('dblclick', function() {
            var textarea = $('<textarea>').text($(this).text()).on('keydown', function(e) {
                if (e.keyCode == 13 && !e.shiftKey) {
                    fimApi.editMessage(roomId, messageId, {
                        'message' : textarea.val()
                    });

                    $(this).replaceWith(fim_buildMessageLine(textarea.val(), messageId, userId, roomId, messageTime, userNameDeferred))
                    e.preventDefault();
                }
            });

            $.each(this.attributes, function() {
                textarea.attr(this.name, this.value);
            });

            $(this).replaceWith(textarea);
        });
    }

    $.when(userNameDeferred).then(function(pairs) {
        tag.attr("style", pairs[userId].messageFormatting);
    });

    return tag;
}

function fim_getUsernameDeferred(userId) {
    return $.when(Resolver.resolveUsersFromIds([userId]));
}

function fim_getRoomNameDeferred(roomId) {
    return $.when(Resolver.resolveRoomsFromIds([roomId]));
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

String.prototype.toNumber = function() {
    if (isNaN(Number(this)))
        return 0;
    else
        return Number(this);
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
    serverSettings;


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
        "(&t=([a-zA-Z0-9]+))*" + // Time code
        ")$", "i"),

    youtubeShort : new RegExp("^(" +
        "(http|https)" + // List of acceptable protocols. (so far: "http")
        ":" + // Colon! (so far: "http:")
        "(//|)" + // "//" is optional; this allows for it or nothing. (so far: "http://")
        "(www\\.|)" + // "www." optional (so far: "http://www")
        "youtu\\.be/" + // domain after "www." (so far: "http://www.youtu.be/")
        "([a-zA-Z0-9\-\_]+)" + // THe video ID
        ")$", "i")
};


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
};

var settings = {
    // Formatting
    disableFormatting : !!(window.webproDisplay.settingsBitfield & 16),
    disableImage : !!(window.webproDisplay.settingsBitfield & 32),
    disableVideos : !!(window.webproDisplay.settingsBitfield & 64),

    // Fun Stuff
    reversePostOrder : !!(window.webproDisplay.settingsBitfield & 1024), // Show posts in reverse?
    showAvatars : !!(window.webproDisplay.settingsBitfield & 2048), // Use the complex document style?
    audioDing : !!(window.webproDisplay.settingsBitfield & 8192), // Fire an HTML5 audio ding during each unread message?

    // Accessibility
    disableFx : !!(window.webproDisplay.settingsBitfield & 262144), // Disable jQuery Effects?
    disableRightClick : !!(window.webproDisplay.settingsBitfield & 1048576),

    // Localisation
    usTime : !!(window.webproDisplay.settingsBitfield & 16777216),
    twelveHourTime : !!(window.webproDisplay.settingsBitfield & 33554432),

    // Experimental Features
    webkitNotifications : !!(window.webproDisplay.settingsBitfield & 536870912)
};



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

            window.serverSettings.fileUploads.extensionChangesReverse = {};

            jQuery.each(window.serverSettings.fileUploads.extensionChanges, function(index, extension) {
                if (!(extension in window.serverSettings.fileUploads.extensionChangesReverse))
                    window.serverSettings.fileUploads.extensionChangesReverse[extension] = [];

                window.serverSettings.fileUploads.extensionChangesReverse[extension].push(index);
            });

        }
    })
).then(function() {
    /* Do some final compat testing */
    if (typeof window.EventSource === 'undefined')
        requestSettings.serverSentEvents = false;
    else
        requestSettings.serverSentEvents = window.serverSettings.requestMethods.serverSentEvents;

    if (window.serverSettings.installUrl !== (window.location.protocol + '//' + window.location.host + window.directory))
        dia.error(window.phrases.errorBadInstall);



    /* Our handful of global objects */
    window.fimApi = new fimApi(window.serverSettings.installUrl);
    fimApi.registerDefaultExceptionHandler(function(exception) {
        dia.exception(exception);
    });


    window.standard = new standard();
    window.popup = new popup();



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
                        value : currentLocation + '#archive#room=' + roomId + '#lastMessage=' + messageId,
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
                    fimApi.deleteMessage(roomId, messageId, {
                        'end' : function() {
                            $.notify({"message" : "The message was deleted."}, {
                                type: "success",
                                placement: {
                                    from: 'top',
                                    align: 'middle'
                                },
                            });
                        }
                    });
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
                        contextAction_msgDelete($(this).attr('data-roomId'), $(this).attr('data-messageid'))
                    }
                },

                link : {
                    name : 'Link',
                    callback: function() {
                        contextAction_msgLink($(this).attr('data-roomId'), $(this).attr('data-messageid'))
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
                        contextAction_msgDelete($(this).closest('.messageText').attr('data-roomId'), $(this).closest('.messageText').attr('data-messageid'))
                    }
                },

                link : {
                    name : 'Link',
                    callback: function() {
                        contextAction_msgLink($(this).closest('.messageText').attr('data-roomId'), $(this).closest('.messageText').attr('data-messageid'))
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
                                    value : url,
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
                        contextAction_msgDelete($(this).closest('.messageText').attr('data-roomId'), $(this).closest('.messageText').attr('data-messageid'))
                    }
                },

                link : {
                    name : 'Link',
                    callback: function() {
                        console.log($(this), $(this).closest('.messageText'), $(this).closest('.messageText').attr('data-roomId'))
                        contextAction_msgLink($(this).closest('.messageText').attr('data-roomId'), $(this).closest('.messageText').attr('data-messageid'))
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



        if (window.webproDisplay.fontSize) $('body').css('font-size', window.webproDisplay.fontSize + 'em');
        if (settings.disableFx) jQuery.fx.off = true;


        /*** Window Manipulation (see below) ***/
        $(window).bind('hashchange', fim_hashParse);


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
        };
        showLogin();

        return false;
    });
}, function() {
    $q('Loading failed. Please refresh.');
});