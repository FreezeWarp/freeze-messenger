declare var fimApi: any;
declare var $: any;
declare var $l: any;
declare var jQuery: any;
declare var fim_messagePreview: any;
declare var directory: any;
declare var dia: any;
declare var fim_buildUsernameTag : any;
declare var fim_getUsernameDeferred : any;
declare var fim_messageFormat : any;
declare var fim_renderHandlebarsInPlace : any;
declare var fim_buildMessageLine : any;
declare var fim_dateFormat : any;
declare var Debounce: any;
declare var EventSource : any;

interface popup {
    room : Object
}

popup.prototype.room = function() {
    this.options = {
        roomId : 0,
        lastEvent : 0,
        lastMessage : 0
    };

    this.roomData = false;

    this.faviconFlashTimer = false;
    this.windowBlurred = false;
    this.messageIndex = [];
    this.isTyping = false;
    this.roomSource = false;
    this.eventTimeout = false;
    this.pingInterval = false;

    this.focusListener = false;
    this.blurListener = false;
    this.visibilitychangeListener = false;

    this.debounce = new Debounce();

    return;
};


popup.prototype.room.prototype.insertDoc = function() {
    // Show Modal
    $('#modal-insertDoc').modal();


    // Disable Submit Button Initially
    $('#uploadFileForm [type=submit]').attr('disabled', 'disabled');


    /* File Upload Info */
    if (!('fileUploads' in window.serverSettings)) {
        $('#insertDocUpload').html('Disabled.');
    }
    else {
        /* File Upload Form */
        if (typeof FileReader !== 'function') {
            $('#uploadFileForm').html($l('uploadErrors.notSupported'));
        }
        else {
            $('#uploadFileForm').unbind('submit').bind('submit', () => {
                let filesList = $('input#fileUpload[type="file"]').prop('files');

                if (filesList.length == 0) {
                    dia.error('Please select a file to upload.');
                }
                else {
                    $('#chatContainer').fileupload('add', {
                        files: filesList,
                        formData : {
                            fileName : filesList[0].name,
                        }
                    });

                    $('#modal-insertDoc').modal('hide');
                }

                return false;
            });

            /* Previewer for Files */
            $('#fileUpload').unbind('change').bind('change', function() {
                let reader = new FileReader();

                $('#imageUploadSubmitButton').attr('disabled', 'disabled').button({ disabled: true }); // Redisable the submit button if it has been enabled prior.

                if (this.files.length === 0) dia.error('No files selected!');
                else if (this.files.length > 1) dia.error('Too many files selected!');
                else {
                    console.log('FileReader started.');

                    // File Information
                    var fileName = this.files[0].name,
                        fileSize = this.files[0].size,
                        fileParts = fileName.split('.'),
                        filePartsLast = fileParts[fileParts.length - 1].toLowerCase();

                    // If there are two identical file extensions (e.g. jpg and jpeg), we only process the primary one. This converts a secondary extension to a primary.
                    if (filePartsLast in window.serverSettings.fileUploads.extensionChanges) {
                        filePartsLast = window.serverSettings.fileUploads.extensionChanges[filePartsLast];
                    }

                    if ($.inArray(filePartsLast, $.toArray(window.serverSettings.fileUploads.allowedExtensions)) === -1) {
                        $('#uploadFileFormPreview').html($l('uploadErrors.badExtPersonal'));
                    }
                    else if (fileSize > window.serverSettings.fileUploads.sizeLimits[filePartsLast]) {
                        $('#uploadFileFormPreview').html($l('uploadErrors.tooLargePersonal', {
                            'fileSize' : window.serverSettings.fileUploads.sizeLimits[filePartsLast]
                        }));
                    }
                    else {
                        $('#uploadFileFormPreview').html('Loading Preview...');

                        reader.readAsDataURL(this.files[0]);
                        reader.onloadend = function() {
                            $('#uploadFileFormPreview').html(fim_messagePreview(window.serverSettings.fileUploads.fileContainers[filePartsLast], this.result));
                        };

                        console.log("file good");
                        $('#uploadFileForm [type=submit]').removeAttr('disabled');
                    }
                }
            });
        }
    }

    return false;
};


popup.prototype.room.prototype.newMessage = function(messageData) {
    let usernameDeferred = fim_getUsernameDeferred(messageData.userId);
    let messageText = fim_buildMessageLine(messageData.text, messageData.flag, messageData.id, messageData.userId, messageData.roomId, messageData.time, usernameDeferred);

    if ($('iframe', messageText).length) {
        messageText.css('min-width', '400px');
    }

    if (window.settings.showAvatars) {
        messageText.popover({
            content : function() {
                return fim_dateFormat($(this).attr('data-time'))
            },
            html : false,
            trigger : 'hover',
            placement : 'bottom'
        });
    }

    let date = $('<span class="date">').text(
        (window.settings.showAvatars
            ? ''
            : ' @ ')
        + fim_dateFormat(messageData.time)
    );


    let avatar = $('<span class="usernameDate">').append(
        fim_buildUsernameTag($('<span>'), messageData.userId, usernameDeferred, messageData.anonId, window.settings.showAvatars, !window.settings.showAvatars)
    );

    let messageLine = $('<span>').attr({
        'id': 'message' + messageData.id,
        'class': 'messageLine'
    });


    if (!window.settings.showAvatars) {
        avatar.append(date);
    }
    else {
        messageText.append(date);
    }

    if (window.settings.showAvatars && messageData.userId == window.activeLogin.userData.id) {
        messageLine.addClass('messageLineReverse').append(messageText).append(avatar);
    }
    else {
        messageLine.append(avatar).append(messageText);
    }


    if ($('#message' + messageData.id).length > 0) {
        console.log("existing");
        $('#message' + messageData.id).replaceWith(messageLine);
    }
    else {
        let foundMatch = false;
        $('#messageList .messageLine').each(function() {
            if (window.settings.reversePostOrder) {
                if ($('.messageText', this).attr('data-messageId') < messageData.id) {
                    $(messageLine).insertBefore(this);
                    foundMatch = true;
                    return false; // break each
                }
            }
            else {
                if ($('.messageText', this).attr('data-messageId') > messageData.id) {
                    $(messageLine).insertBefore(this);
                    foundMatch = true;
                    return false;
                }
            }
        });

        if (!foundMatch) {
            if (window.settings.reversePostOrder) {
                $('#messageList').append(messageLine);
            }
            else {
                $('#messageList').append(messageLine);
            }
        }

        // Only list 100 messages in the table at any given time. This prevents memory excess (this usually isn't a problem until around 1,000, but 100 is usually all a user is going to need).
        this.messageIndex.push(messageData.id); // Update the internal messageIndex array.
        if (this.messageIndex.length >= 100) {
            $('#message' + this.messageIndex[0]).remove();
            this.messageIndex = this.messageIndex.slice(1,99);
        }
    }


    // Scroll Down
    $('#message' + messageData.id + ' img').on('load', (() => {
        this.scrollBack();
    }));

    this.scrollBack();


    // Blur Events
    if (this.windowBlurred) {
        // Play Sound
        if (window.settings.audioDing)
            window.snd.play();

        // Flash Favicon
        this.faviconFlashStart();

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
        if (window.notify.webkitNotifySupported() && window.settings.webkitNotifications) {
            $.when(fim_getUsernameDeferred(messageData.userId)).then(function(matches) {
                window.notify.webkitNotify("images/favicon.ico", $('#roomName').text() + "[" + matches[messageData.userId].name + "]", messageData.text);
            })
        }
    }
};

popup.prototype.room.prototype.scrollBack = function() { // Scrolls the message list to the bottom.
    if (window.settings.reversePostOrder)
        $('#messageListContainer').scrollTop(0);
    else
        $('#messageListContainer').scrollTop($('#messageListContainer')[0].scrollHeight);
};

popup.prototype.room.prototype.faviconFlashOnce = function() { // Changes the state of the favicon from opaque to transparent or similar.
    if ($('#favicon').attr('href') === 'images/favicon.ico') $('#favicon').attr('href', 'images/favicon2.ico');
    else $('#favicon').attr('href', 'images/favicon.ico');
};

popup.prototype.room.prototype.faviconFlashStart = function() {
    if (!this.faviconFlashTimer) {
        this.faviconFlashTimer = window.setInterval(this.faviconFlashOnce, 1000);
    }
};

popup.prototype.room.prototype.faviconFlashStop = function() {
    if (this.faviconFlashTimer) {
        window.clearInterval(this.faviconFlashTimer);
        this.faviconFlashTimer = false;
    }

    $('#favicon').attr('href', 'images/favicon.ico');
};

popup.prototype.room.prototype.onBlur = function() {
    this.windowBlurred = true;

    if (this.isTyping) {
        fimApi.stoppedTyping(this.options.roomId);
        this.isTyping = false;
    }
};

popup.prototype.room.prototype.onFocus = function() {
    this.windowBlurred = false;
    this.faviconFlashStop();
};

popup.prototype.room.prototype.onVisibilityChange = function() {
    if (document.visibilityState == 'hidden')
        this.onBlur();
    else
        this.onFocus();
};

popup.prototype.room.prototype.onWindowResize = function() {
    let windowWidth = $(window).width(), // Get the browser window "viewport" width, excluding scrollbars.
        windowHeight = $(window).height(); // Get the browser window "viewport" height, excluding scrollbars.

    // Set the message list height to fill as much of the screen that remains after the textarea is placed.
    $('#messageListContainer').css('height', Math.floor(
        windowHeight
        - ($('#messageListCardHeader').height())
        - $('#textentryBoxMessage').height()
        - $('#navbar').height()
        - 65)
    );

    $('#activeUsers').css('height', Math.floor(
        windowHeight
        - $('#activeUsersCardHeader').height()
        - $('#navbar').height()
        - 65)
    );
};

popup.prototype.room.prototype.isNeutralKeyCode = function(keyCode) {
    return (keyCode == 9 // Tab
        || (keyCode >= 16 && keyCode <= 18) // Shift, Control, Alt
        || keyCode == 27 // Escape
        || (keyCode >= 33 && keyCode <= 40) // Arrow Keys, Home, End, Page Up/Down
        || keyCode == 44 // Print Screen
        || keyCode == 45 // Insert
        || (keyCode >= 91 && keyCode <= 93) // OS/Meta Keys
    );
};

popup.prototype.room.prototype.init = function(options) {
    for (var i in options)
        this.options[i] = options[i];


    /* Setup */

    // Monitor the window visibility for running favicon flash and notifications.
    document.addEventListener('visibilitychange', this.visibilitychangeListener = this.onVisibilityChange.bind(this));
    window.addEventListener('blur', this.blurListener = this.onBlur.bind(this));
    window.addEventListener('focus',  this.focusListener = this.onFocus.bind(this));
    this.onFocus();


    // Process Resizes
    $(window).on('resize', null, this.onWindowResize);
    $('#navbarSupportedContent').on('shown.bs.collapse', () => { this.onWindowResize() }).on('hidden.bs.collapse', () => { this.onWindowResize() });


    // Set up file upload handler, used for drag/drop, pasting, and insertDoc method.
    $('#chatContainer').fileupload({
        dropZone : $('body'),
        pasteZone : $('textarea#messageInput'),
        paramName : 'file',
        submit: (e, data) => {
            console.log("send", data);

            // Append the access token only on submit, since it may change.
            data.url = window.serverSettings.installUrl + 'api/file.php?' + $.param({
                "_action" : "create",
                "access_token" : window.sessionHash
            });

            // Make sure we have formData
            if (!("formData" in data)) {
                data.formData = {};
            }

            // Append roomId to formData
            data.formData.roomId = this.options.roomId;

            // Append fileName to formData from original file name
            if (!("fileName" in data.formData)) {
                data.formData.fileName = data.originalFiles[0].name;
            }

            return true;
        }
    });




    /* Allow Keyboard Scrolling through Messages
     * (kinda broken atm) */
    $('#messageList')
        .off('keydown', '.messageLine .userName, .messageLine .messageText')
        .on('keydown', '.messageLine .userName, .messageLine .messageText', {}, function(e) {
            if (window.restrictFocus === 'contextMenu') // TODO?
                return true;

            if (e.which === 38) { // Up
                $(this).closest('.messageLine').prev('.messageLine').find($(this).hasClass('userName') ? '.userName' : '.messageText').focus();
                return false;
            }
            else if (e.which === 37 || e.which === 39) { // Left+Right
                $(this).closest('.messageLine').find($(this).hasClass('userName') ? '.messageText' : '.userName').focus();
                return false;
            }
            else if (e.which === 40) { // Down
                console.log("down", $(this).closest('.messageLine'), $(this).closest('.messageLine').next('.messageLine'), $(this).hasClass('userName'));
                $(this).closest('.messageLine').next('.messageLine').find($(this).hasClass('userName') ? '.userName' : '.messageText').focus();
                return false;
            }
        });


    // Prevent default drag/drop handler, in conjunction with above.
    $(document).bind('drop dragover', function (e) {
        e.preventDefault();
    });


    // Send messages on form submit.
    $('#sendForm').on('submit', (() => {
        let message = $('textarea#messageInput').val();

        if (message.length === 0) {
            dia.error('Please enter your message.');
        }
        else {
            this.sendMessage(message); // Send the messaage
            $('textarea#messageInput').val(''); // Clear the textbox
        }

        return false;
    }));


    // Try to allow resizes of the messageInput. (Currently kinda broken.)
    //$('textarea#messageInput').mouseup(this.onWindowResize);


    // Close any open notifications for this room
    if (window.standard.notifications["room" + this.options.roomId])
        window.standard.notifications["room" + this.options.roomId].close();



    /* Get our current room info and messages. */
    fimApi.getRooms({
        'id' : this.options.roomId,
    }, {
        each : ((roomData) => {
            this.roomData = roomData;


            if (!roomData.permissions.view) { // If we can not view the room
                window.roomId = null; // Set the global roomId false.
                window.location.hash = "#rooms";
                dia.error('You have been restricted access from this room. Please select a new room.');
            }

            else if (!roomData.permissions.post) { // If we can view, but not post
                dia.info('You are not allowed to post in this room. You will be able to view it, though.', 'danger');
                this.disableSender();
            }

            else { // If we can both view and post.
                this.enableSender();
            }


            if (roomData.permissions.view) { // If we can view the room...
                window.roomId = roomData.id;


                // Populate Active Users for the Room
                fimApi.getActiveUsers({
                    'roomIds' : [this.options.roomId]
                }, {
                    'refresh' : 15000,
                    'timerId' : 1,
                    'begin' : (() => {
                        $('ul#activeUsers').html('');
                    }),
                    'each' : (user) => {
                        jQuery.each(user.rooms, (index, status) => {
                            this.userStatusChangeHandler({
                                status : status.status,
                                typing : status.typing,
                                userId : user.id
                            });
                        });
                    }
                });


                // Send user status pings
                fimApi.ping(this.options.roomId);
                this.pingInterval = window.setInterval((() => {
                    fimApi.editUserStatus(this.options.roomId, {
                        status : "",
                        typing : this.typing
                    });
                }), 60 * 1000);


                // Detect form typing
                $('textarea#messageInput').on('keyup', (e) => {
                    if (!this.isNeutralKeyCode(e.keyCode)) {
                        return this.debounce.invoke(() => {
                            if (this.isTyping) {
                                this.isTyping = false;
                                fimApi.stoppedTyping(this.options.roomId);
                            }
                        }, 2000);
                    }
                }).on('keydown', (e) => {
                    // Enter
                    if (e.keyCode == 13 && !e.shiftKey) {
                        $('#sendForm').submit();
                        e.preventDefault();

                        if (this.isTyping) {
                            this.isTyping = false;
                            fimApi.stoppedTyping(this.options.roomId);
                        }
                    }

                    // Backspace or Delete
                    else if (e.keyCode == 8 || e.keyCode == 46) {
                        if (this.isTyping) {
                            this.isTyping = false;
                            fimApi.stoppedTyping(this.options.roomId);
                        }
                    }

                    // Special Keys
                    else if (!this.isNeutralKeyCode(e.keyCode) && !this.isTyping) {
                        this.isTyping = true;
                        fimApi.startedTyping(this.options.roomId);
                    }
                });


                // Send logoff event on window close.
                $(window).on('beforeunload', null, () => {
                    this.beforeUnload(this.options.roomId);
                });


                // Render Header Template
                fim_renderHandlebarsInPlace($('#messageListCardHeaderTemplate'), {roomData : roomData});


                // Clear the message list.
                $('#messageList').html('');


                // Get New Messages
                window.requestSettings[this.options.roomId] = {
                    lastMessage : null,
                    firstRequest : true
                };

                fimApi.getMessages({
                    'roomId': this.options.roomId,
                }, {
                    each: ((messageData) => {
                        this.newMessage(messageData);
                    }),
                    end: (() => {
                        if (window.requestSettings.serverSentEvents) {
                            this.eventListener();
                        }
                        else {
                            this.getMessagesFromFallback();
                        }
                    })
                });
            }

            this.onWindowResize();
        }),

        exception : ((exception) => {
            if (exception.string === 'idNoExist' || exception.string === 'roomIdInvalid') {
                window.roomId = null; // Set the global roomId false.
                window.location.hash = "#rooms";
                dia.error('That room doesn\'t exist. Please select a room.');
            }
            else { fimApi.getDefaultExceptionHandler()(exception); }
        })
    });
};

popup.prototype.room.prototype.close = function() {
    fimApi.getActiveUsers({}, {
        timerId : 1,
        close : true
    });

    document.removeEventListener('visibilitychange', this.visibilitychangeListener);
    window.removeEventListener('blur', this.blurListener);
    window.removeEventListener('focus',  this.focusListener);

    if (this.roomSource) {
        this.roomSource.close();
        this.roomSource = false;
    }

    if (this.eventTimeout) {
        window.clearTimeout(this.eventTimeout);
    }

    if (this.pingInterval) {
        window.clearInterval(this.pingInterval);
    }

    $('#fileupload').fileupload('destroy');

    $('textarea#messageInput').off('keyup').off('keydown');
    $('#sendForm').off('submit');

    $(window).off('beforeunload');
    this.beforeUnload(this.options.roomId);

    $(window).off('resize', null, this.onWindowResize);
};

popup.prototype.room.prototype.setRoom = function(roomId) {
    if (this.options.roomId && this.options.roomId != roomId) {
        this.close();
        this.options.lastEvent = 0;
        this.options.lastMessage = 0;
        this.options.roomId = roomId;
        this.init();
    }
};

popup.prototype.room.prototype.beforeUnload = function(roomId) {
    fimApi.exitRoom(roomId);
};


popup.prototype.room.prototype.eventListener = function() {
    this.roomSource = new EventSource(directory + 'stream.php?queryId=' + this.options.roomId + '&streamType=room&lastEvent=' + this.options.lastEvent + '&lastMessage=' + this.options.lastMessage + '&access_token=' + window.sessionHash);

    // If we get an error that causes the browser to close the connection, open a fallback connection instead
    this.roomSource.onerror = ((e) => {
        if (this.roomSource.readyState === 2) {
            this.roomSource = false;
            this.getMessagesFromFallback();
        }
    });

    let eventHandler = ((callback) => {
        return ((event) => {
            this.options.lastEvent = Math.max(Number(this.options.lastEvent), Number(event.lastEventId));

            callback.call(this, JSON.parse(event.data));
        });
    });

    this.roomSource.addEventListener('userStatusChange', eventHandler(this.userStatusChangeHandler), false);
    this.roomSource.addEventListener('newMessage', eventHandler(this.newMessageHandler), false);
    this.roomSource.addEventListener('topicChange', eventHandler(this.topicChangeHandler), false);
    this.roomSource.addEventListener('deletedMessage', eventHandler(this.deletedMessageHandler), false);
    this.roomSource.addEventListener('editedMessage', eventHandler(this.editedMessageHandler), false);
};


popup.prototype.room.prototype.newMessageHandler = function(active) {
    this.options.lastMessage = Math.max(this.options.lastMessage, active.id);

    this.newMessage(active);
};

popup.prototype.room.prototype.deletedMessageHandler = function(active) {
    $('#message' + active.id).fadeOut();
};

popup.prototype.room.prototype.topicChangeHandler = function(active) {
    $('#topic').text(active.topic);
    console.log('Event (Topic Change): ' + active.topic);
};

popup.prototype.room.prototype.editedMessageHandler = function(active) {
    if ($('#message' + active.id).length > 0) {
        active.userId = active.senderId;
        active.time = $('#message' + active.id + ' .messageText').attr('data-time');

        this.newMessage(active);
    }
};

popup.prototype.room.prototype.userStatusChangeHandler = function(active) {
    let existingRow = $('ul#activeUsers > li[data-userId=' + active.userId + ']');
    let newRow = $('<li>').attr('class', 'list-group-item').attr('data-userId', active.userId)
        .append(fim_buildUsernameTag($('<span>'), active.userId))
        .append(active.typing ? $('<i class="fa fa-keyboard-o" style="vertical-align: middle; margin-left: 10px;"></i>') : $(''));

    if (existingRow.length > 0) {
        if (active.status !== "offline")
            existingRow.replaceWith(newRow)
        else
            existingRow.remove();
    }
    else {
        if (active.status !== "offline")
            $('ul#activeUsers').append(newRow);
    }
};


popup.prototype.room.prototype.getMessagesFromFallback = function() {
    if (this.options.roomId) {
        fimApi.getEventsFallback({
            'streamType': 'room',
            'queryId': this.options.roomId,
            'lastEvent' : this.options.lastEvent
        }, {
            each: ((event) => {
                this.options.lastEvent = Math.max(Number(this.options.lastEvent), Number(event.id));
                this[event.eventName + "Handler"](event.data);
            }),
            end: (() => {
                if (window.requestSettings.serverSentEvents) {
                    this.eventListener();
                }
                else {
                    this.eventTimeout = window.setTimeout((() => {
                        this.getMessagesFromFallback()
                    }), 2000);
                }
            })
        });
    }
    else {
        console.log('Not requesting messages; room undefined.');
    }

    return false;
};


popup.prototype.room.prototype.sendMessage = function(message, ignoreBlock, flag) {
    if (!this.options.roomId) {
        window.location.hash = '#rooms';
    }
    else {
        ignoreBlock = (ignoreBlock === 1 ? 1 : '');

        fimApi.sendMessage(this.options.roomId, {
            'ignoreBlock' : ignoreBlock,
            'message' : message,
            'flag' : (flag ? flag : '')
        }, {
            end : ((message) => {
                if ("censor" in message && Object.keys(message.censor).length > 0) {
                    dia.info(Object.values(message.censor).join('<br /><br />'), "Censor warning: " + Object.keys(message.censor).join(', '));
                }
            }),

            exception : ((exception) => {
                if (exception.string === 'confirmCensor')
                    dia.confirm({
                        'text' : exception.details,
                        'true' : function() {
                            this.sendMessage(message, 1, flag);
                        }
                    }, "Censor Warning");
                else if (exception.string === 'spaceMessage') {
                    dia.error("Too... many... spaces!")
                }
                else { fimApi.getDefaultExceptionHandler()(exception); }
            }),

            error : ((request) => {
                if (window.settings.reversePostOrder)
                    $('#messageList').append($('<div>').text('Your message, "' + message + '", could not be sent and will be retried.'));
                else
                    $('#messageList').prepend($('<div>').text('Your message, "' + message + '", could not be sent and will be retried.'));

                window.setTimeout(() => {
                    this.sendMessage(message)
                }, 5000);

                return false;
            })
        });
    }
};

popup.prototype.room.prototype.disableSender = function() {
    $('#messageInput').attr('disabled','disabled'); // Disable input boxes.
    $('#icon_url').button({ disabled : true }); // "
    $('#icon_submit').button({ disabled : true }); // "
    $('#icon_reset').button({ disabled : true }); // "
};

popup.prototype.room.prototype.enableSender = function() {
    $('#messageInput').removeAttr('disabled'); // Make sure the input is not disabled.
    $('#icon_url').button({ disabled : false }); // "
    $('#icon_submit').button({ disabled : false }); // "
    $('#icon_reset').button({ disabled : false }); // "
};