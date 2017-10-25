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
            : $('<img style="max-width: 250px; max-height: 250px;" />').attr('src', imageUrl/* + "&" + $.param({
                    'thumbnailWidth' : 250,
                    'thumbnailHeight' : 250,
                })*/) // todo: only for files on installI
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

function fim_showMissedMessage(message) {
    // todo

    if (message.roomId == window.roomId) {
        // we'll eventually probably want to do something fancy here, like offering to scroll to the last-viewed message.
    }
    else if ($("#missedMessage" + message.roomId + "_" + message.messageId).length) { // We already have a box for this shown
        // Do nothing
    }
    else {
        $('.missedMessage').find('[data-roomId="' + message.roomId + '"]').find('.jGrowl-close').click(); // Close missed messages that are from the same room.

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
        messageTime = json.time,
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


    switch (flag) {
        case 'source': text = fim_youtubeParse(text); break; // Youtube, etc.
        case 'image': text = fim_formatAsImage(text); break; // // Image; We append the parentalAge flags regardless of an images source. It will potentially allow for other sites to use the same format (as far as I know, I am the first to implement the technology, and there are no related standards.)
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
                    text.html($('<span style="color: red; padding: 10px; font-weight: bold;">').text('* ' + pairs[userId].name + ' ' + text).prop('outerHTML'));
                });
            }

            // "/topic" parse
            else if (/^\/topic/.test(text.text())) {
                text.text(text.text().replace(/^\/topic/,''));

                $('#topic').text(text);

                $.when(userNameDeferred).then(function(pairs) {
                   text.html($('<span style="color: red; padding: 10px; font-weight: bold;">').text('* ' + pairs[userId].name + ' changed the topic to "' + text + '".').prop('outerHTML'));
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
                    fim_buildUsernameTag($('<span class="userName userNameTable">'), userId, userNameDeferred, true)
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
                    fim_buildUsernameTag($('<span>'), userId, userNameDeferred)
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

function fim_buildUsernameTag(tag, userId, deferred, bothNameAvatar) {
    $.when(deferred).then(function(pairs) {
        var userName = pairs[userId].name,
            userNameFormat = pairs[userId].nameFormat,
            avatar = pairs[userId].avatar ? pairs[userId].avatar : 'client/images/blankperson.png',
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
            trigger : 'hover'
        }).on("show.bs.popover", function(e){
            console.log($(this).data("bs.popover"), $(this).data("bs.popover").tip)
            $($(this).data("bs.popover").tip).css({"max-width": "600px"});
        });
    });

    return tag;
}

function fim_buildRoomNameTag(tag, roomId, deferred) {
    $.when(deferred).then(function(pairs) { console.log("pairs: ", roomId, pairs);
        var roomName = pairs[roomId].name;

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

    if (window.userId == userId && window.permissions.editOwnPosts) {
        tag.on('dblclick', function() {
            var textarea = $('<textarea>').text($(this).text()).onEnter(function() {
                fimApi.editMessage(roomId, messageId, {
                    'message' : textarea.val()
                });

                $(this).replaceWith(fim_buildMessageLine(textarea.val(), messageId, userId, roomId, messageTime, userNameDeferred));
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

    if ($('#message' + messageId).length > 0) {
        $('#message' + messageId).replaceWith(messageText);
    }
    else {
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


    /* ... todo whatever the fuck this is */
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

    // Set the message list height to fill as much of the screen that remains after the textarea is placed.
    $('#messageListContainer').css('height', Math.floor(
        windowHeight
        - ($('#messageListCard').height() - $('#messageListContainer').height())
        - $('#textentryBoxMessage').height()
        - $('#navbar').height()
        - 50));

    //$('#menuParent').css('height', windowHeight - 30); // Set the message list height to fill as much of the screen that remains after the textarea is placed.

    // should be fixed, need browser test
    //$('#messageList').css('max-width', ((windowWidth - 40) * (windowWidth < 600 ? 1 : .75))); // Prevent box-stretching. This is common on... many chats.

    if ($("#menu").hasClass("ui-accordion")) $("#menu").accordion("refresh");

    //$('body').css('min-height', windowHeight - 1); // Set the body height to equal that of the window; this fixes many gradient issues in theming.

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
    // Hide/show login/logout based on active login
    if (window.userId) {
        $('#navbar a[name=logout]').show();
        $('#navbar a[name=login]').hide();
    }
    else {
        $('#navbar a[name=logout]').hide();
        $('#navbar a[name=login]').show();
    }


/*    if (roomId) {
        if (roomId.toString().substr(0,1) === 'p' || modRooms[roomId] < 1) { $('li > #kick').parent().hide(); $('li > #manageKick').parent().hide(); $('#userMenu a[data-action="kick"]').parent().hide(); $('ul#messageMenu > li > a[data-action="delete"], ul#messageMenuImage > li > a[data-action="delete"], ul#messageMenuLink > li > a[data-action="delete"], ul#messageMenuVideo > li > a[data-action="delete"]').hide(); noModCounter += 2; }
        if (roomId.toString().substr(0,1) === 'p' || modRooms[roomId] < 2) { $('li > #editRoom').parent().hide(); noModCounter += 1; }
    }
    else {
        $('li > #editRoom').parent().hide(); noModCounter += 1; $('li > #kick').parent().hide(); $('li > #manageKick').parent().hide(); $('#userMenu a[data-action="kick"]').parent().hide();
    }*/
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



/*********************************************************
 ************************* END ***************************
 ********* DOM Event Handling & Window Painting **********
 *********************************************************/