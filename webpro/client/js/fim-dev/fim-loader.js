/*********************************************************
 ************************ START **************************
 ******************** Base Variables *********************
 *********************************************************/




/*********************************************************
 ************************ START **************************
 ******************* Static Functions ********************
 *********************************************************/



/**
 * Escapes Data for Server Storage
 * Internally, it will use either encodeURIComponent or escape, with custom replacements.
 *
 * @param str - The string to encode.
 *
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
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
 * @copyright Joseph T. Parsons 2017
 */
function fim_eXMLAttr(str) { // Escapes data that is stored via doublequote-encased attributes.
    return str.replace(/\"/g, '&quot;').replace(/\\/g, '\\\\');
}



/**
 * Scrolls the message list to the bottom.
 *
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 */
function fim_toBottom() { // Scrolls the message list to the bottom.
    document.getElementById('messageListContainer').scrollTop = document.getElementById('messageListContainer').scrollHeight;
}



/**
 * Attempts to "flash" the favicon once called, or stop flashing if already flashing.
 * This has been tested to work in Google Chrome.
 *
 * @param str - The string to encode.
 *
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
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
 * @copyright Joseph T. Parsons 2017
 */
function fim_messagePopup(data) {
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
        var code = false;

        if ($1.match(regexs.youtubeFull) !== null) { code = $1.replace(regexs.youtubeFull, "$8"); }
        else if ($1.match(regexs.youtubeShort) !== null) { code = $1.replace(regexs.youtubeShort, "$5"); }

        if (settings.disableVideo) { return '<a href="https://www.youtu.be/' + code + '" target="_BLANK">[Youtube Video]</a>'; }
        else { return '<iframe width="425" height="349" src="https://www.youtube.com/embed/' + code + '?rel=0&wmode=transparent" frameborder="0" allowfullscreen></iframe>'; }
    }

    else {
        return false;
    }
}



function fim_formatAsImage(imageUrl) {
    return $('<a target="_BLANK" class="imglink">').attr('href', imageUrl).append(
        settings.disableImage ? $('<span>').text('[IMAGE]')
            : $('<img style="max-width: 250px; max-height: 250px;" />').attr('src', imageUrl + "&" + $.param({
                    'thumbnailWidth' : 250,
                    'thumbnailHeight' : 250,
                })) // todo: only for files on installI
    ).prop('outerHTML');
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

function fim_showMissedMessage(message) {
    if (message.roomId == window.roomId) {
        // we'll eventually probably want to do something fancy here, like offering to scroll to the last-viewed message.
    }
    else if ($("#missedMessage" + message.roomId + "_" + message.messageId).length) { // We already have a box for this shown
        // Do nothing
    }
    else {
        $('.missedMessage').find('[data-roomId=' + message.roomId + ']').find('.jGrowl-close').click(); // Close missed messages that are from the same room.

        $.jGrowl($('<span>').attr({
            'class': 'missedMessage',
            'id': "missedMessage" + message.roomId + "_" + message.messageId,
            'data-roomId': message.roomId
        }).text('New message from ')
            .append(
                $('<strong>').attr('style', message.senderNameFormat).text(message.senderName)
            )
            .append(' has been made in ')
            .append(
                $('<a style="font-weight: bold">').attr('href', '#room=' + message.roomId).text(message.roomName).click(function () {
                    // admittedly, I really should change plugins, but it's worked for me so far, and this _does_ work
                    $(this).parent().parent().parent().parent().find('.jGrowl-close').click()
                })
            )
            .append((message.missedMessages ? $('<span>').text('(Total unread messages: ' + message.missedMessages + ')') : '')),
        {
            sticky : true,
            close : function() { fimApi.markMessageRead(message.roomId) }
        });
    }
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
        messageTime = fim_dateFormat(json.time),
        messageId = json.id,
        roomId = json.roomId,
        flag = json.flag,
        userId = Number(json.userId),
        userNameDeferred = fim_getUsernameDeferred(userId);


    text = text.replace(/\</g, '&lt;').replace(/\>/g, '&gt;').replace(/\n/g, '<br />');
    text = text.replace(/(file\.php\?sha256hash\=[a-f0-9]{64})/, function ($1) {
        // The usage of mergeDefaults here is a somewhat lazy way of unsetting the attributes if they are null (thus, not sending them at all, as opposed to sending them empty).
        return ($1 + "&" + $.param(fimApi.mergeDefaults({},
            {
                'parentalAge' : window.activeLogin.userData.parentalAge ? window.activeLogin.userData.parentalAge : null,
                'parentalFlags' : window.activeLogin.userData.parentalFlags ? window.activeLogin.userData.parentalFlags : null,
            }
        )));
    });

    if (text.length > 1000) { /* TODO */
        text = '[Message Too Long]';
    }
    else {
        switch (flag) {
            case 'source': text = text.replace(regexs.url, fim_youtubeParse) || '[Unrecognised Source]'; break; // Youtube, etc.
            case 'image': text = fim_formatAsImage(text); break; // // Image; We append the parentalAge flags regardless of an images source. It will potentially allow for other sites to use the same format (as far as I know, I am the first to implement the technology, and there are no related standards.)
            case 'video': text = fim_formatAsVideo(text) ; break; // Video
            case 'audio': text = fim_formatAsAudio(text); break; // Audio
            case 'email': text = fim_formatAsEmail(text); break; // Email Link

            // Various Files and URLs
            case 'url': case 'text': case 'html': case 'archive': case 'other':

            break;

            // Unspecified
            default:
                // URL Autoparse (will also detect youtube & image)
                text = text.replace(regexs.url, function($1) {
                    if ($1.match(regexs.url2)) {
                        var $2 = $1.replace(regexs.url2, "$2");
                        $1 = $1.replace(regexs.url2, "$1"); // By doing this one second we don't have to worry about storing the variable first to get $2
                    }
                    else {
                        var $2 = '';
                    }

                    if (youtubeCode = fim_youtubeParse($1)) return youtubeCode; // Youtube Autoparse

                    // Image Autoparse
                    else if ($1.match(regexs.image)) {
                        return fim_formatAsImage($1) + $2
                    }

                    // Normal URL
                    else {
                        return $('<a target="_BLANK">').attr('href', $1).text($1).prop('outerHTML') + $2;
                    }
                });

                // "/me" parse
                if (/^\/me/.test(text)) {
                    text = text.replace(/^\/me/,'');

                    $.when(userNameDeferred).then(function(pairs) {
                        text = $('<span style="color: red; padding: 10px; font-weight: bold;">').text('* ' + pairs[userId].name + ' ' + text).prop('outerHTML');
                    });
                }

                // "/topic" parse
                else if (/^\/topic/.test(text)) {
                    text = text.replace(/^\/topic/,'');

                    $('#topic').html(text);

                    $.when(userNameDeferred).then(function(pairs) {
                       text = $('<span style="color: red; padding: 10px; font-weight: bold;">').text('* ' + pairs[userId].name + ' changed the topic to "' + text + '".').prop('outerHTML');
                    });
                }
                break;
        }
    }


    function buildEditableSpan(text, messageId, userId, roomId, messageTime, userNameDeferred) {
        var tag = $('<span>').attr({
            'class': 'messageText' + (window.userId == userId && window.permissions.editOwnPosts ? ' editable' : ''),
            'data-messageId': messageId,
            'data-roomId': roomId,
            'data-time': messageTime,
            'tabindex': 1000
        }).html(text).on('dblclick', function() {
            var textarea = $('<textarea>').text($(this).text()).onEnter(function() {
                fimApi.editMessage(roomId, messageId, {
                    'message' : textarea.val()
                });

                $(this).replaceWith(buildEditableSpan(textarea.val(), messageId, userId, roomId, messageTime, style));
            });

            $.each(this.attributes, function() {
                textarea.attr(this.name, this.value);
            });

            $(this).replaceWith(textarea);
        });

        $.when(userNameDeferred).then(function(pairs) {
            tag.attr("style", pairs[userId].messageFormatting);
        });

        return tag;
    }


    switch (format) {
        case 'table':
            data = $('<tr style="word-wrap: break-word;">').attr({
                'id': "archiveMessage' + messageId + '"
            }).append(
                $('<td>').append(
                    fim_buildUsernameTag($('<span class="userName userNameTable">'), userId, userNameDeferred, true)
                )
            ).append(
                $('<td>').text(messageTime)
            ).append(
                $('<td>').append(
                    buildEditableSpan(text, messageId, userId, roomId, messageTime, userNameDeferred).html(text)
                )
            ).append(
                $('<td>').append(
                    $('<a href="javascript:void(0);" class="updateArchiveHere">').attr({'data-messageId': messageId}).text('Show')
                )
            );
            break;

        case 'list':
            data = $('<span>').attr({
                'id': 'message' + messageId,
                'class': 'messageLine' + (settings.showAvatars ? ' messageLineAvatar' : '')
            }).append(
                $('<span class="usernameDate">').append(
                    fim_buildUsernameTag($('<span>'), userId, userNameDeferred)
                ).append(
                    !settings.showAvatars ?
                        $('<span class="date">').css({'padding-right':'10px','letter-spacing':'-1px'}).text('@ ').append($('<em>').text(messageTime))
                        : ''
                )
            ).append(
                buildEditableSpan(text, messageId, userId, roomId, messageTime, userNameDeferred)
            );
            break;
    }


    /* Format for Table/List Display */
    return data;
}

function fim_buildUsernameTag(tag, userId, deferred, bothNameAvatar) {
    $.when(deferred).then(function(pairs) {
        var userName = pairs[userId].name,
            userNameFormat = pairs[userId].nameFormat,
            avatar = pairs[userId].avatar,
            style = settings.disableFormatting ? '' : pairs[userId].messageFormatting;

        tag.attr({
            'class': 'userName' + (settings.showAvatars || bothNameAvatar ? ' userNameAvatar' : ''),
            'style': (!settings.showAvatars ? userNameFormat : ''),
            'data-userId': userId,
            'data-userName': userName,
            'data-avatar': avatar,
            'tabindex': 1000
        }).append(
            settings.showAvatars || bothNameAvatar ?
                $('<img>').attr({
                    'alt': userName,
                    'src': avatar ? avatar : 'client/images/blankperson.png'
                }) : ''
        ).append(
            !settings.showAvatars || bothNameAvatar ?
                $('<span>').text(userName) : ''
        );
    });

    return tag;
}

function fim_getUsernameDeferred(userId) {
    return $.when(Resolver.resolveUsersFromIds([userId]));
}


/**
 *
 */

function fim_messagePreview(container, content) {
    switch (container) {
        case 'image': return '<img src="' + content + '" style="max-height: 200px; max-width: 200px;" />'; break;
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
 * Registers a new message in the caches and triggers alerts to users.
 * @param string messageText
 * @param int messageId
 *
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 */
function fim_newMessage(roomId, messageId, messageText) {
    if ($.inArray(messageId, messageIndex) > -1) { return; } // Double post hack

    var foundMatch = false;
    $('#messageList .messageLine').each(function() {
        if (settings.reversePostOrder) {
            if ($('.messageText', this).attr('data-messageId') < messageId) {
                $(messageText).insertBefore(this);
                foundMatch = true;
                return false; // break each
            }
        }
        else {
            if ($('.messageText', this).attr('data-messageId') > messageId) {
                $(messageText).insertBefore(this);
                foundMatch = true;
                return false;
            }
        }
    });

    if (!foundMatch) {
        if (settings.reversePostOrder) {
            $('#messageList').prepend(messageText);
        }
        else {
            $('#messageList').append(messageText);
        }
    }


    // Only list 100 messages in the table at any given time. This prevents memory excess (this usually isn't a problem until around 1,000, but 100 is usually all a user is going to need).
    messageIndex[roomId].push(messageId); // Update the internal messageIndex array.
    if (messageIndex[roomId].length >= 100) {
        var messageOut = messageIndex[roomId][0];
        $('#message' + messageOut).remove();
        messageIndex[roomId] = messageIndex[roomId].slice(1,99);
    }

    if (!settings.reversePostOrder) fim_toBottom();

    if (window.isBlurred) {
        // Play Sound
        if (settings.audioDing) snd.play();

        // Flash Favicon
        window.clearInterval(timers.t3);
        timers.t3 = window.setInterval(fim_faviconFlash, 1000);

        // Windows Flash Icon
        if (typeof window.external === 'object') {
            if (typeof window.external.msIsSiteMode !== 'undefined' && typeof window.external.msSiteModeActivate !== 'undefined') {
                try {
                    if (window.external.msIsSiteMode()) { window.external.msSiteModeActivate(); } // Task Bar Flashes
                }
                catch(ex) { } // Ya know, its very weird IE insists on this when the "in" statement works just as well...
            }
        }

        // HTML5 Notification
        if (notify.webkitNotifySupported() && settings.webkitNotifications) {
            notify.webkitNotify("images/favicon.ico", "New Message", $(messageText).text());
        }
    }

    contextMenuParseMessage();
    contextMenuParseUser('#messageList');

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
            var userId = $(el).attr('data-userId'),
                userName = $(el).attr('data-userName'),
                avatar = $(el).attr('data-avatar');

            if (userId != $('#tooltext').attr('data-lastuserId')) {
                $('#tooltext').attr('data-lastuserId', userId);

                content.html("");
                content.append(
                    $('<div style="width: 400px;">').append(
                        (typeof avatar !== "undefined" && avatar.length > 0) ? $('<img style="float: left; max-height: 200px; max-width: 200px;">').attr('src', avatar) : ''
                    ).append(
                        $('<span class="userName">').attr({'data-userId' : userId, 'style' : ''}).text(userName)
                    )
                );

                fimApi.getUsers({
                    'userIds' : [userId]
                }, {'each' : function(userData) {
                    if (userData.userTitle)
                        content.append($('<br>').append($('<span>').text(userData.userTitle)))

                    if (userData.posts)
                        content.append($('<span><br><em>Posts</em>: </span>').append($('<span>').text(userData.posts)));

                    if (userData.profile)
                        content.append($('<span><br><em>Profile</em>: </span>').append($('<a>').attr('href', userData.profile).text(userData.profile)));

                    if (userData.joinDate)
                        content.append($('<span><br><em>Member Since</em>: </span>').append($('<span>').text(fim_dateFormat(userData.joinDate, {year : "numeric", month : "numeric", day : "numeric"})))); // TODO:just date
                }});
            }
        }
    });

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
}



/**
 * Hash Parse for URL-Defined Actions.
 *
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 */
function fim_hashParse(options) {
    var urlHashComponents = window.location.hash.split('#'),
        page, messageId, roomId;


    for (var i = 0; i < urlHashComponents.length; i++) {
        var componentPieces = urlHashComponents[i].split('=');

        switch (componentPieces[0]) {
            case 'page': page = componentPieces[1]; break;
            case 'room': roomId = componentPieces[1]; break;
            case 'message': messageId = componentPieces[1]; break;
        }
    }


    if (roomId && messageId)
        page = 'archive';

    switch (page) {
        case 'archive':
            prepopup = function() {
                popup.archive({
                    'roomId' : roomId,
                    'firstMessage' : messageId - 1
                });
            };
            break;

        case 'settings':
            prepopup = function() { popup.userSettings(); };
            break;
    }

    if (!window.roomId && options.defaultRoomId)
        roomId = options.defaultRoomId;

    if (roomId && roomId !== window.roomId)
        standard.changeRoom(roomId); // If the room is different than current, change it.
}

/*********************************************************
 ************************* END ***************************
 ******************* Static Functions ********************
 *********************************************************/






/*********************************************************
 ************************ START **************************
 ******************* Variable Setting ********************
 *********************************************************/



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

var userNameToId = {};
var userIdToData = {};
/*********************************************************
 ************************* END ***************************
 ******************* Variable Setting ********************
 *********************************************************/








/*********************************************************
 ************************ START **************************
 ***** Window Manipulation and Multi-Window Handling *****
 *********************************************************/



/**
 * Redraws part of the window when it is resized.
 *
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 */
function windowResize() {
    var windowWidth = $(window).width(); // Get the browser window "viewport" width, excluding scrollbars.
    var windowHeight = $(window).height(); // Get the browser window "viewport" height, excluding scrollbars.

    $('#messageListContainer').css('height', Math.floor(windowHeight -
        $('#textentryBoxMessage').height() -
        $('#messageList').parents().eq(4).children('thead').height() -
        50)); // Set the message list height to fill as much of the screen that remains after the textarea is placed.
    $('#menuParent').css('height', windowHeight - 30); // Set the message list height to fill as much of the screen that remains after the textarea is placed.

    // should be fixed, need browser test
    //$('#messageList').css('max-width', ((windowWidth - 40) * (windowWidth < 600 ? 1 : .75))); // Prevent box-stretching. This is common on... many chats.

    if ($("#menu").hasClass("ui-accordion")) $("#menu").accordion("refresh");

    $('body').css('min-height', windowHeight - 1); // Set the body height to equal that of the window; this fixes many gradient issues in theming.

    $('.ui-widget-overlay').each(function() {
        $(this).height(windowHeight);
        $(this).width(windowWidth);
    });

    $('.ui-dialog-content').each(function() {
        if ($(this).dialog("option", "width") > windowWidth)
            $(this).dialog("option", "width", windowWidth);

        if ($(this).dialog("option", "height") > windowHeight || $(this).height() > windowHeight)
            $(this).dialog("option", "height", windowHeight);

        $(this).dialog("option", "position", { my: "center", at: "center", of: window });
    });
}



/**
 * Define the window as blurred (used for new message notifications).
 *
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 */
function windowBlur() {
    window.isBlurred = true;
}



/**
 * Define the window as active (used for new message notifications), and clear the Favicon Flash timer.
 *
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
 */
function windowFocus() {
    window.isBlurred = false;
    window.clearInterval(timers.t3);

    $('#favicon').attr('href', favicon);
}

/*********************************************************
 ************************* END ***************************
 ***** Window Manipulation and Multi-Window Handling *****
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
 * @copyright Joseph T. Parsons 2017
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
 * @copyright Joseph T. Parsons 2017
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
            html += '<td><img src="http://i2.ytimg.com/vi/' + video.videoId + '/default.jpg" style="width: 120px; height: 90px;" /><br /><small><a href="javascript: void(0);" onclick="youtubeSend(&apos;' + video.videoId + '&apos;)">' + video.title + '</a></small></td>';
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
 * @copyright Joseph T. Parsons 2017
 */
var autoEntry = function(target, options) {
    this.options = options;
    var _this = this;

    target.append($('<input type="text" name="' + this.options.name + 'Bridge" id="' + this.options.name + 'Bridge" class="ui-autocomplete-input" autocomplete="off" />').autocompleteHelper(this.options.list))
        .append($('<input type="button" value="Add">').click(function() {
            _this.addEntry($("#" + _this.options.name + "Bridge").attr('data-id'), $("#" + _this.options.name + "Bridge").val());
        }))
        .append('<input type="hidden" name="' + this.options.name + '" id="' + this.options.name + '">');

    if ('default' in options) {
        this.displayEntries(options.default);
    }

    return this;
}

autoEntry.prototype = {
    setOnAdd : function(onAdd) {
        this.options.onAdd = onAdd;
    },

    addEntry : function(id, name, suppressEvents) {

        var _this = this;
        var id = id;
        var name = name;

        if (!id && name) {
            var resolver = $.when(this.options.resolveFromNames([name])).then(function(data) {
                id = data[name].id;
            });
        }

        else if (!name && id) {
            var resolver = $.when(this.options.resolveFromIds([id]).then(function(data) {
                name = data[id].name;
            }));
        }

        $.when(resolver).then(function() {
            if (!id) {
                dia.error("Invalid entry.");
            }
            else if ($("span #" + _this.options.name + "SubList" + id).length) {
                console.log("autoEntry: attempted to add duplicate");
            }
            else {
                var nameTag = $('<span>');

                // this whole thing is TODO; I just wanna get a proof-of-concept
                if (_this.options.name == "allowedUsers" || _this.options.name == "moderators") {
                    nameTag = fim_buildUsernameTag(nameTag, id, fim_getUsernameDeferred(id), true);
                }
                else {
                    nameTag.text(name);
                }

                $("#" + _this.options.name).val($("#" + _this.options.name).val() + "," + id);

                $("#" + _this.options.name + "List").append(
                    $("<span>").attr('id', _this.options.name + "SubList" + id).text(
                        ($("#" + _this.options.name + "List > span").length > 0 ? ', ' : '')
                    ).append(nameTag)
                    .append(
                        $('<span class="close">(<a href="javascript:void(0);">×</a>)</span>').click(function () {
                            _this.removeEntry(id)
                        })
                    )
                )

                $("#" + _this.options.name + "Bridge").val('');

                if (!suppressEvents && _this.options.onAdd) _this.options.onAdd(id);
            }
        });
    },

    removeEntry : function(id) {
        var options = this.options;

        $("#" + this.options.name).val($("#" + this.options.name).val().replace(new RegExp("(^|,)" + id + "(,|$)"), "$1$2").replace(/^,|(,),|,$/,'$1'));

        $("#" + this.options.name + "SubList" + id).fadeOut(500, function() {
            $(this).remove();

            if (typeof options.onRemove === 'function') {
                options.onRemove(id);
            }
        });
    },

    displayEntries : function(string) {
        var entryList;

        if (typeof string === 'object') { entryList = string; }
        else if (typeof string === 'string' && string.length > 0) { entryList = string.split(','); } // String is a string and not empty.
        else { entryList = []; }

        var _this = this;
        $.when(this.options.resolveFromIds(entryList)).then(function(entries) {
            for (var i = 0; i < entryList.length; i++) {
                _this.addEntry(entryList[i], entries[entryList[i]].name, true);
            }
        });
    },

    getList : function() {
        return $("#" + this.options.name).val().split(',').filter(Number);
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
 * @copyright Joseph T. Parsons 2017
 */
function windowDraw() {
    console.log('Redrawing window.');


    /*** Context Menus ***/
    contextMenuParseRoom();


    /*** Funky Little Dialog Thing ***/
    $('.ui-dialog-titlebar-tabbed').on('dblclick', function() {
        var newHeight = $(window).height();
        var newWidth = $(window).width();

        if (($(this).parent().css('width') == newWidth && $(this).parent().css('height') == newHeight) === false) { // Only maximize if not already maximized.
            $(this).parent().css({ width: newWidth, height: newHeight, left: 0, top : 0 });  // Set to the size of the window, realign to the upper-let corner.
            //$(this).removeClass('ui-dialog-draggable'); // Remove the drag indicator.
            //$(this).parent().draggable("destroy").resizable("destroy"); // Remove the ability to drag and resize.
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
 * @copyright Joseph T. Parsons 2017
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


    // Hide/show login/logout based on active login
    if (window.userId) {
        $('#logout').parent().first().show();
        $('#login').parent().first().hide();
    }
    else {
        $('#logout').parent().first().hide();
        $('#login').parent().first().show();
    }


    // Hide DOM Elements Based on User's Permissions
    if (!window.permissions.createRooms) { $('li > #createRoom').parent().hide(); }
    if (!window.permissions.privateRoomsFriends) { $('li > #privateRoom').parent().hide(); $('#userMenu a[data-action="private_im"]').parent().hide(); }
    if (!window.permissions.modUsers) { $('li > #modUsers').parent().hide(); $('ul#userMenu > li > a[data-action="ban"]').hide(); noAdminCounter += 1; }
    if (!window.permissions.modRooms) { $('ul#roomMenu > li > a[data-action="delete"]').hide(); noAdminCounter += 1; }
    if (!window.permissions.modFiles) { $('li > #modImages').parent().hide(); $('ul#messageMenu > li > a[data-action="deleteimage"]').hide(); noAdminCounter += 1; }
    if (!window.permissions.modCensor) { $('li > #modCensor').parent().hide(); noAdminCounter += 1; }
    if (!window.permissions.modTemplates) { $('li > #modPhrases, li > #modTemplates').parent().hide(); noAdminCounter += 1; }
    if (!window.permissions.modPrivs) { $('li > #modPrivs').parent().hide(); noAdminCounter += 1; }
    if (!window.permissions.modPlugins) { $('li > #modHooks').parent().hide(); noAdminCounter += 1; }
    if (!window.permissions.modPrivs) { $('li > #modCore').parent().hide(); noAdminCounter += 1; }

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
}


/*
 * 
 */

function fim_showLoader() {
    $('<div class="ui-widget-overlay" id="waitOverlay"></div>').appendTo('body').width($(document).width()).height($(document).height());
    $('<img src="images/ajax-loader.gif" id="waitThrobber" />').appendTo('body').css('position', 'absolute').offset({ left : (($(window).width() - 220) / 2), top : (($(window).height() - 19) / 2)});
}

function fim_hideLoader() {
    $('#waitOverlay, #waitThrobber').empty().remove();
}


/**
 * Disables the input boxes.
 *
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
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
 * @copyright Joseph T. Parsons 2017
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
 * @copyright Joseph T. Parsons 2017
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
                    var resolver = $.when(Resolver.resolveUsersFromIds([userId])).then(function(userData) {
                        dia.full({
                            title : 'User Profile',
                            id : 'messageLink',
                            content : (userData[userId].profile ? '<iframe src="' + userData[userId].profile + '" style="width: 100%; height: 90%;" /><br /><a href="' + userData[userId].profile + '" target="_BLANK">Visit The Page Directly</a>' : 'The user has not yet registered a profile.'),
                            width: $(window).width() * .8,
                            height: $(window).height() * .9
                        });
                    });

                    break;

                case 'private_im':
                    standard.changeRoom("p" + [window.userId, userId].join(','), true);
                    break;
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
 * @copyright Joseph T. Parsons 2017
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
                            standard.deleteMessage(roomId, messageId);

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
                        width: 600
                    });
                    break;

                case 'edit':
                    $('#message' + messageId + ' .messageText').dblclick();

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
                            standard.deleteMessage(roomId, messageId);

                            $(el).parent().fadeOut();
                        }
                    });
                    break;

                case 'link':
                    dia.full({
                        title : 'Link to this Message',
                        id : 'messageLink',
                        content : 'This message can be bookmarked using the following archive link:<br /><br /><input type="text" value="' + currentLocation + '/#page=archive#room=' + roomId + '#message=' + messageId + '" style="width: 100%;" />',
                        width: 600
                    });
                    break;

                case 'click':
                    $('<a id="contextMenuClickHelper" style="display: none;" />').attr('href', src).attr('target', '_blank').text('-').appendTo('body').get(0).click();
                    $('#contextMenuClickHelper').remove();
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
                            standard.deleteMessage(roomId, messageId);
                            $(el).parent().fadeOut();
                        }
                    });
                    break;

                case 'link':
                    dia.full({
                        title : 'Link to this Message',
                        id : 'messageLink',
                        content : 'This message can be bookmarked using the following archive link:<br /><br /><input type="text" value="' + currentLocation + '/#page=archive#room=' + roomId + '#message=' + messageId + '" style="width: 100%;" />',
                        width: 600
                    });
                    break;

                case 'click':
                    $('<a id="contextMenuClickHelper" style="display: none;" />').attr('href', src).attr('target', '_blank').text('-').appendTo('body').get(0).click();
                    $('#contextMenuClickHelper').remove();
                    break;
            }

            return false;
        });
}



/**
 * (Re-)Parse the "room" context menus.
 *
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2017
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

/*********************************************************
 ************************* END ***************************
 ********* DOM Event Handling & Window Painting **********
 *********************************************************/