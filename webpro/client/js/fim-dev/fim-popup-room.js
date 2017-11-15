;
popup.prototype.room = function () {
    this.options = {
        roomId: 0,
        lastEvent: 0,
        lastMessage: 0
    };
    this.faviconFlashTimer = false;
    this.windowBlurred = false;
    this.messageIndex = [];
    this.isTyping = false;
    this.roomSource = false;
    this.eventTimeout = false;
    this.pingInterval = false;
    return;
};
popup.prototype.room.prototype.insertDoc = function () {
    $('#modal-insertDoc').modal();
    var fileName = '', fileSize = 0, fileContent = '', fileParts = [], filePartsLast = '', md5hash = '';
    /* File Upload Info */
    if (!('fileUploads' in window.serverSettings)) {
        $('#insertDocUpload').html('Disabled.');
    }
    else {
        window.serverSettings.fileUploads.extensionChangesReverse = {};
        jQuery.each(window.serverSettings.fileUploads.extensionChanges, function (index, extension) {
            if (!(extension in window.serverSettings.fileUploads.extensionChangesReverse))
                window.serverSettings.fileUploads.extensionChangesReverse[extension] = [extension];
            window.serverSettings.fileUploads.extensionChangesReverse[extension].push(extension);
        });
        $('table#fileUploadInfo tbody').html('');
        jQuery.each(window.serverSettings.fileUploads.allowedExtensions, function (index, extension) {
            var maxFileSize = window.serverSettings.fileUploads.sizeLimits[extension], fileContainer = window.serverSettings.fileUploads.fileContainers[extension], fileExtensions = window.serverSettings.fileUploads.extensionChangesReverse[extension];
            $('table#fileUploadInfo tbody').append('<tr><td>' + (fileExtensions ? fileExtensions.join(', ') : extension) + '</td><td>' + $l('fileContainers.' + fileContainer) + '</td><td>' + $.formatFileSize(maxFileSize, $l('byteUnits')) + '</td></tr>');
        });
        /* File Upload Form */
        if (typeof FileReader !== 'function') {
            $('#uploadFileForm').html($l('uploadErrors.notSupported'));
        }
        else {
            $('#uploadFileForm').unbind('submit').bind('submit', function () {
                var filesList = $('input#fileUpload[type="file"]').prop('files');
                if (filesList.length == 0) {
                    dia.error('Please select a file to upload.');
                }
                else {
                    $('#chatContainer').fileupload('add', {
                        files: filesList,
                        formData: {
                            fileName: filesList[0].name
                        }
                    });
                    $('#modal-insertDoc').modal('hide');
                }
                return false;
            });
            /* Previewer for Files */
            $('#fileUpload').unbind('change').bind('change', function () {
                var reader = new FileReader();
                $('#imageUploadSubmitButton').attr('disabled', 'disabled').button({ disabled: true }); // Redisable the submit button if it has been enabled prior.
                if (this.files.length === 0)
                    dia.error('No files selected!');
                else if (this.files.length > 1)
                    dia.error('Too many files selected!');
                else {
                    console.log('FileReader started.');
                    // File Information
                    var fileName = this.files[0].name, fileSize = this.files[0].size, fileContent = '', fileParts = fileName.split('.'), filePartsLast = fileParts[fileParts.length - 1].toLowerCase();
                    // If there are two identical file extensions (e.g. jpg and jpeg), we only process the primary one. This converts a secondary extension to a primary.
                    if (filePartsLast in window.serverSettings.fileUploads.extensionChanges) {
                        filePartsLast = window.serverSettings.fileUploads.extensionChanges[filePartsLast];
                    }
                    if ($.inArray(filePartsLast, $.toArray(window.serverSettings.fileUploads.allowedExtensions)) === -1) {
                        $('#uploadFileFormPreview').html($l('uploadErrors.badExtPersonal'));
                    }
                    else if ((fileSize) > window.serverSettings.fileUploads.sizeLimits[filePartsLast]) {
                        $('#uploadFileFormPreview').html($l('uploadErrors.tooLargePersonal', {
                            'fileSize': window.serverSettings.fileUploads.sizeLimits[filePartsLast]
                        }));
                    }
                    else {
                        $('#uploadFileFormPreview').html('Loading Preview...');
                        reader.readAsDataURL(this.files[0]);
                        reader.onloadend = function () {
                            $('#uploadFileFormPreview').html(fim_messagePreview(window.serverSettings.fileUploads.fileContainers[filePartsLast], this.result));
                        };
                        $('#imageUploadSubmitButton').removeAttr('disabled').button({ disabled: false });
                    }
                }
            });
        }
    }
    return false;
};
popup.prototype.room.prototype.newMessage = function (roomId, messageId, messageText) {
    console.log("new message", roomId, messageId, messageText);
    if ($('#message' + messageId).length > 0) {
        console.log("existing");
        $('#message' + messageId).replaceWith(messageText);
    }
    else {
        var foundMatch_1 = false;
        $('#messageList .messageLine').each(function () {
            if (window.settings.reversePostOrder) {
                if ($('.messageText', this).attr('data-messageId') < messageId) {
                    $(messageText).insertBefore(this);
                    foundMatch_1 = true;
                    return false; // break each
                }
            }
            else {
                if ($('.messageText', this).attr('data-messageId') > messageId) {
                    $(messageText).insertBefore(this);
                    foundMatch_1 = true;
                    return false;
                }
            }
        });
        if (!foundMatch_1) {
            if (window.settings.reversePostOrder) {
                $('#messageList').prepend(messageText);
            }
            else {
                $('#messageList').append(messageText);
            }
        }
        // Only list 100 messages in the table at any given time. This prevents memory excess (this usually isn't a problem until around 1,000, but 100 is usually all a user is going to need).
        this.messageIndex.push(messageId); // Update the internal messageIndex array.
        if (this.messageIndex.length >= 100) {
            $('#message' + this.messageIndex[0]).remove();
            this.messageIndex = this.messageIndex.slice(1, 99);
        }
    }
    // Scroll Down
    if (!window.settings.reversePostOrder)
        this.toBottom();
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
                    if (window.external.msIsSiteMode()) {
                        window.external.msSiteModeActivate();
                    } // Task Bar Flashes
                }
                catch (ex) { } // Ya know, its very weird IE insists on this when the "in" statement works just as well...
            }
        }
        // HTML5 Notification
        if (window.notify.webkitNotifySupported() && window.settings.webkitNotifications) {
            window.notify.webkitNotify("images/favicon.ico", "New Message", $(messageText).text());
        }
    }
    /* Allow Keyboard Scrolling through Messages
     * (kinda broken atm) */
    $('.messageLine .messageText, .messageLine .userName, body').unbind('keydown');
    $('.messageLine .messageText').bind('keydown', function (e) {
        if (window.restrictFocus === 'contextMenu')
            return true;
        if (e.which === 38) {
            $(this).parent().prev('.messageLine').children('.messageText').focus();
            return false;
        } // Left
        else if (e.which === 37 || e.which === 39) {
            $(this).parent().children('.userName').focus();
            return false;
        } // Right+Left
        else if (e.which === 40) {
            $(this).parent().next('.messageLine').children('.messageText').focus();
            return false;
        } // Down
    });
    $('.messageLine .userName').bind('keydown', function (e) {
        if (window.restrictFocus === 'contextMenu')
            return true;
        if (e.which === 38) {
            $(this).parent().prev('.messageLine').children('.userName').focus();
            return false;
        } // Up
        else if (e.which === 39 || e.which === 37) {
            $(this).parent().children('.messageText').focus();
            return false;
        } // Left+Right
        else if (e.which === 40) {
            $(this).parent().next('.messageLine').children('.userName').focus();
            return false;
        } // Down
    });
};
popup.prototype.room.prototype.toBottom = function () {
    $('#messageListContainer').scrollTop($('#messageListContainer')[0].scrollHeight);
};
popup.prototype.room.prototype.faviconFlashStart = function () {
    this.faviconFlashTimer = window.setInterval(this.faviconFlashOnce, 1000);
};
popup.prototype.room.prototype.faviconFlashStop = function () {
    if (this.faviconFlashTimer) {
        window.clearInterval(this.faviconFlashTimer);
        this.faviconFlashTimer = false;
    }
};
popup.prototype.room.prototype.faviconFlashOnce = function () {
    if ($('#favicon').attr('href') === 'images/favicon.ico')
        $('#favicon').attr('href', 'images/favicon2.ico');
    else
        $('#favicon').attr('href', 'images/favicon.ico');
};
popup.prototype.room.prototype.onWindowResize = function () {
    var windowWidth = $(window).width(), // Get the browser window "viewport" width, excluding scrollbars.
    windowHeight = $(window).height(); // Get the browser window "viewport" height, excluding scrollbars.
    // Set the message list height to fill as much of the screen that remains after the textarea is placed.
    $('#messageListContainer').css('height', Math.floor(windowHeight
        - ($('#messageListCardHeader').height())
        - $('#textentryBoxMessage').height()
        - $('#navbar').height()
        - 65));
    $('#activeUsers').css('height', Math.floor(windowHeight
        - $('#activeUsersCardHeader').height()
        - $('#navbar').height()
        - 65));
};
popup.prototype.room.prototype.init = function (options) {
    var _this = this;
    for (var i in options)
        this.options[i] = options[i];
    var pingInterval;
    /* Setup */
    // Monitor the window visibility for running favicon flash and notifications.
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState == 'hidden') {
            _this.windowBlurred = true;
        }
        else {
            _this.windowBlurred = false;
            _this.faviconFlashStop();
        }
    });
    $(window).on('resize', null, this.onWindowResize);
    // Set up file upload handler, used for drag/drop, pasting, and insertDoc method.
    $('#chatContainer').fileupload({
        dropZone: $('body'),
        pasteZone: $('textarea#messageInput'),
        url: window.serverSettings.installUrl + 'api/editFile.php?' + $.param({
            "_action": "create",
            "access_token": window.sessionHash
        }),
        paramName: 'file',
        submit: function (e, data) {
            console.log("send", data);
            data.formData.roomId = _this.options.roomId;
            if (!("fileName" in data.formData)) {
                data.formData.fileName = "pasteupload.png";
            }
            return true;
        }
    });
    // Prevent default drag/drop handler, in conjunction with above.
    $(document).bind('drop dragover', function (e) {
        e.preventDefault();
    });
    // Send messages on form submit.
    $('#sendForm').on('submit', (function () {
        var message = $('textarea#messageInput').val();
        if (message.length === 0) {
            dia.error('Please enter your message.');
        }
        else {
            _this.sendMessage(message); // Send the messaage
            $('textarea#messageInput').val(''); // Clear the textbox
        }
        return false;
    }));
    // Send user status pings
    fimApi.ping(this.options.roomId);
    this.pingInterval = window.setInterval((function () {
        fimApi.editUserStatus(_this.options.roomId, {
            status: "",
            typing: _this.typing
        });
    }), 5 * 60 * 1000);
    // Detect form typing
    $('textarea#messageInput').on('keyup', fim_debounce(function () {
        fimApi.stoppedTyping(_this.options.roomId);
        _this.isTyping = false;
    }, 2000)).on('keydown', function (e) {
        if (e.keyCode == 13 && !e.shiftKey) {
            $('#sendForm').submit();
            e.preventDefault();
        }
        else if (!_this.isTyping) {
            _this.isTyping = true;
            fimApi.startedTyping(_this.options.roomId);
        }
    });
    // Send logoff event on window close.
    $(window).on('beforeunload', null, function () {
        _this.beforeUnload(_this.options.roomId);
    });
    // Try to allow resizes of the messageInput. (Currently kinda broken.)
    //$('textarea#messageInput').mouseup(this.onWindowResize);
    // Close any open notifications for this room
    if (window.standard.notifications["room" + this.options.roomId])
        window.standard.notifications["room" + this.options.roomId].close();
    /* Get our current room info and messages. */
    fimApi.getRooms({
        'id': this.options.roomId
    }, {
        each: (function (roomData) {
            if (!roomData.permissions.view) {
                window.roomId = false; // Set the global roomId false.
                window.location.hash = "#rooms";
                dia.error('You have been restricted access from this room. Please select a new room.');
            }
            else if (!roomData.permissions.post) {
                dia.error('You are not allowed to post in this room. You will be able to view it, though.');
                _this.disableSender();
            }
            else {
                _this.enableSender();
            }
            if (roomData.permissions.view) {
                window.roomId = roomData.id;
                $('#roomName').html(roomData.name); // Update the room name.
                $('#topic').html(roomData.topic); // Update the room topic.
                // Clear the message list.
                $('#messageList').html('');
                window.requestSettings[_this.options.roomId] = {
                    lastMessage: null,
                    firstRequest: true
                };
                // Get New Messages
                fimApi.getMessages({
                    'roomId': _this.options.roomId
                }, {
                    each: (function (messageData) {
                        _this.newMessage(Number(_this.options.roomId), Number(messageData.id), fim_messageFormat(messageData, 'list'));
                    }),
                    end: (function () {
                        if (window.requestSettings.serverSentEvents) {
                            _this.eventListener();
                        }
                        else {
                            _this.getMessagesFromFallback();
                        }
                    })
                });
            }
            if (!(roomData.permissions.properties || roomData.permissions.grant)) {
                $('#active-view-room #chatContainer button[name=editRoom]').hide();
            }
            _this.onWindowResize();
        }),
        exception: (function (exception) {
            if (exception.string === 'idNoExist') {
                window.roomId = false; // Set the global roomId false.
                window.location.hash = "#rooms";
                dia.error('That room doesn\'t exist. Please select a room.');
            }
            else {
                fimApi.getDefaultExceptionHandler()(exception);
            }
        })
    });
    /* Populate Active Users for the Room */
    fimApi.getActiveUsers({
        'roomIds': [this.options.roomId]
    }, {
        'refresh': 15000,
        'timerId': 1,
        'begin': (function () {
            $('ul#activeUsers').html('');
        }),
        'each': function (user) {
            jQuery.each(user.rooms, function (index, status) {
                _this.userStatusChangeHandler({
                    status: status.status,
                    typing: status.typing,
                    userId: user.id
                });
            });
        }
    });
};
popup.prototype.room.prototype.close = function () {
    console.log("close started");
    fimApi.getActiveUsers({}, {
        timerId: 1,
        close: true
    });
    if (this.roomSource) {
        this.roomSource.close();
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
popup.prototype.room.prototype.setRoom = function (roomId) {
    if (this.options.roomId != roomId) {
        this.close();
        this.options.roomId = roomId;
        this.init();
    }
};
popup.prototype.room.prototype.beforeUnload = function (roomId) {
    fimApi.exitRoom(roomId);
};
popup.prototype.room.prototype.eventListener = function () {
    var _this = this;
    this.roomSource = new EventSource(directory + 'stream.php?queryId=' + this.options.roomId + '&streamType=room&lastEvent=' + this.options.lastEvent + '&lastMessage=' + this.options.lastMessage + '&access_token=' + window.sessionHash);
    this.roomSource.onerror = (function (e) {
        console.log("event source error", e);
        if (_this.roomSource) {
            _this.roomSource.close();
            _this.roomSource = false;
        }
        _this.getMessagesFromFallback();
    });
    var eventHandler = (function (callback) {
        return (function (event) {
            _this.options.lastEvent = Math.max(Number(_this.options.lastEvent), Number(event.lastEventId));
            callback.call(_this, JSON.parse(event.data));
        });
    });
    this.roomSource.addEventListener('userStatusChange', eventHandler(this.userStatusChangeHandler), false);
    this.roomSource.addEventListener('newMessage', eventHandler(this.newMessageHandler), false);
    this.roomSource.addEventListener('topicChange', eventHandler(this.topicChangeHandler), false);
    this.roomSource.addEventListener('deletedMessage', eventHandler(this.deletedMessageHandler), false);
    this.roomSource.addEventListener('editedMessage', eventHandler(this.editedMesageHandler), false);
};
popup.prototype.room.prototype.newMessageHandler = function (active) {
    this.options.lastMessage = Math.max(this.options.lastMessage, active.id);
    this.newMessage(this.options.roomId, Number(active.id), fim_messageFormat(active, 'list'));
};
popup.prototype.room.prototype.deletedMessageHandler = function (active) {
    $('#message' + active.id).fadeOut();
};
popup.prototype.room.prototype.topicChangeHandler = function (active) {
    $('#topic').html(active.param1);
    console.log('Event (Topic Change): ' + active.param1);
};
popup.prototype.room.prototype.editedMessageHandler = function (active) {
    if ($('#message' + active.id).length > 0) {
        active.userId = $('#message' + active.id + ' .userName').attr('data-userid');
        active.time = $('#message' + active.id + ' .messageText').attr('data-time');
        this.newMessage(this.options.roomId, Number(active.id), fim_messageFormat(active, 'list'));
    }
};
popup.prototype.room.prototype.userStatusChangeHandler = function (active) {
    var existingRow = $('ul#activeUsers > li[data-userId=' + active.userId + ']');
    var newRow = $('<li>').attr('class', 'list-group-item').attr('data-userId', active.userId)
        .append(fim_buildUsernameTag($('<span>'), active.userId, fim_getUsernameDeferred(active.userId)))
        .append(active.typing ? $('<i class="fa fa-keyboard-o" style="vertical-align: middle; margin-left: 10px;"></i>') : $(''));
    if (existingRow.length > 0) {
        if (active.status !== "offline")
            existingRow.replaceWith(newRow);
        else
            existingRow.remove();
    }
    else {
        if (active.status !== "offline")
            $('ul#activeUsers').append(newRow);
    }
};
popup.prototype.room.prototype.getMessagesFromFallback = function () {
    var _this = this;
    if (this.options.roomId) {
        fimApi.getEventsFallback({
            'streamType': 'room',
            'queryId': this.options.roomId,
            'lastEvent': this.options.lastEvent
        }, {
            each: (function (event) {
                _this.options.lastEvent = Math.max(Number(_this.options.lastEvent), Number(event.id));
                if (event.eventName == "newMessage") {
                    _this.newMessageHandler(event.data);
                }
            }),
            end: (function () {
                if (window.requestSettings.serverSentEvents) {
                    _this.eventListener();
                }
                else {
                    _this.eventTimeout = window.setTimeout((function () {
                        _this.getMessagesFromFallback();
                    }), 3000);
                }
            })
        });
    }
    else {
        console.log('Not requesting messages; room undefined.');
    }
    return false;
};
popup.prototype.room.prototype.sendMessage = function (message, ignoreBlock, flag) {
    if (!this.options.roomId) {
        window.location.hash = '#rooms';
    }
    else {
        ignoreBlock = (ignoreBlock === 1 ? 1 : '');
        fimApi.sendMessage(this.options.roomId, {
            'ignoreBlock': ignoreBlock,
            'message': message,
            'flag': (flag ? flag : '')
        }, {
            end: (function (message) {
                if ("censor" in message && Object.keys(message.censor).length > 0) {
                    dia.info(Object.values(message.censor).join('<br /><br />'), "Censor warning: " + Object.keys(message.censor).join(', '));
                }
            }),
            exception: (function (exception) {
                if (exception.string === 'confirmCensor')
                    dia.confirm({
                        'text': exception.details,
                        'true': function () {
                            this.sendMessage(message, 1, flag);
                        }
                    }, "Censor Warning");
                else if (exception.string === 'spaceMessage') {
                    dia.error("Too... many... spaces!");
                }
                else {
                    fimApi.getDefaultExceptionHandler()(exception);
                }
            }),
            error: (function (request) {
                if (window.settings.reversePostOrder)
                    $('#messageList').append('Your message, "' + message + '", could not be sent and will be retried.');
                else
                    $('#messageList').prepend('Your message, "' + message + '", could not be sent and will be retried.');
                window.setTimeout(function () { this.sendMessage(message); }, 5000);
                return false;
            })
        });
    }
};
popup.prototype.room.prototype.disableSender = function () {
    $('#messageInput').attr('disabled', 'disabled'); // Disable input boxes.
    $('#icon_url').button({ disabled: true }); // "
    $('#icon_submit').button({ disabled: true }); // "
    $('#icon_reset').button({ disabled: true }); // "
};
popup.prototype.room.prototype.enableSender = function () {
    $('#messageInput').removeAttr('disabled'); // Make sure the input is not disabled.
    $('#icon_url').button({ disabled: false }); // "
    $('#icon_submit').button({ disabled: false }); // "
    $('#icon_reset').button({ disabled: false }); // "
};
//# sourceMappingURL=fim-popup-room.js.map