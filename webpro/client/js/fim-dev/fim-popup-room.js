;
popup.prototype.room = {
    options: {
        roomId: 0,
        intervalPing: false,
        lastEvent: 0,
        lastMessage: 0
    },
    faviconFlashTimer: false,
    windowBlurred: false,
    messageIndex: [],
    isTyping: false,
    insertDoc: function () {
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
            jQuery.each(window.serverSettings.fileUploads.allowedExtensions, function (index, extension) {
                var maxFileSize = window.serverSettings.fileUploads.sizeLimits[extension], fileContainer = window.serverSettings.fileUploads.fileContainers[extension], fileExtensions = window.serverSettings.fileUploads.extensionChangesReverse[extension];
                $('table#fileUploadInfo tbody').append('<tr><td>' + (fileExtensions ? fileExtensions.join(', ') : extension) + '</td><td>' + $l('fileContainers.' + fileContainer) + '</td><td>' + $.formatFileSize(maxFileSize, $l('byteUnits')) + '</td></tr>');
            });
            /* File Upload Form */
            if (typeof FileReader !== 'function') {
                $('#uploadFileForm').html($l('uploadErrors.notSupported'));
            }
            else {
                $('#uploadFileForm').submit(function () {
                    var filesList = $('input#fileUpload[type="file"]').prop('files');
                    if (filesList.length == 0) {
                        dia.error('Please select a file to upload.');
                        return false;
                    }
                    else {
                        $('#chatContainer').fileupload('add', {
                            files: filesList
                        });
                        $('#modal-insertDoc').modal('hide');
                        return false;
                    }
                });
                /* Previewer for Files */
                $('#fileUpload').bind('change', function () {
                    var reader = new FileReader(), reader2 = new FileReader();
                    console.log('FileReader triggered.');
                    $('#imageUploadSubmitButton').attr('disabled', 'disabled').button({ disabled: true }); // Redisable the submit button if it has been enabled prior.
                    if (this.files.length === 0)
                        dia.error('No files selected!');
                    else if (this.files.length > 1)
                        dia.error('Too many files selected!');
                    else {
                        console.log('FileReader started.');
                        // File Information
                        fileName = this.files[0].name,
                            fileSize = this.files[0].size,
                            fileContent = '',
                            fileParts = fileName.split('.'),
                            filePartsLast = fileParts[fileParts.length - 1];
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
                            reader.readAsBinaryString(this.files[0]);
                            reader.onloadend = function () {
                                fileContent = window.btoa(reader.result);
                            };
                            reader2.readAsDataURL(this.files[0]);
                            reader2.onloadend = function () {
                                $('#uploadFileFormPreview').html(fim_messagePreview(window.serverSettings.fileUploads.fileContainers[filePartsLast], this.result));
                            };
                            $('#imageUploadSubmitButton').removeAttr('disabled').button({ disabled: false });
                        }
                    }
                });
            }
        }
        return false;
    },
    newMessage: function (roomId, messageId, messageText) {
        if ($.inArray(messageId, this.messageIndex) > -1) {
            return;
        } // Double post hack
        if ($('#message' + messageId).length > 0) {
            $('#message' + messageId).replaceWith(messageText);
        }
        else {
            var foundMatch = false;
            $('#messageList .messageLine').each(function () {
                if (window.settings.reversePostOrder) {
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
                if (window.settings.reversePostOrder) {
                    $('#messageList').prepend(messageText);
                }
                else {
                    $('#messageList').append(messageText);
                }
            }
        }
        // Only list 100 messages in the table at any given time. This prevents memory excess (this usually isn't a problem until around 1,000, but 100 is usually all a user is going to need).
        this.messageIndex.push(messageId); // Update the internal messageIndex array.
        if (this.messageIndex.length >= 100) {
            $('#message' + this.messageIndex[0]).remove();
            this.messageIndex = this.messageIndex.slice(1, 99);
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
    },
    toBottom: function () {
        $('#messageListContainer').scrollTop($('#messageListContainer')[0].scrollHeight);
    },
    faviconFlashStart: function () {
        this.faviconFlashTimer = window.setInterval(fim_faviconFlash, 1000);
    },
    faviconFlashStop: function () {
        if (this.faviconFlashTimer) {
            window.clearInterval(this.faviconFlashTimer);
            this.faviconFlashTimer = false;
        }
    },
    faviconFlashOnce: function () {
        if ($('#favicon').attr('href') === 'images/favicon.ico')
            $('#favicon').attr('href', 'images/favicon2.ico');
        else
            $('#favicon').attr('href', 'images/favicon.ico');
    },
    onWindowResize: function () {
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
    },
    init: function (options) {
        var _this = this;
        for (var i in options)
            this.options[i] = options[i];
        var intervalPing;
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
        $(window).bind('resize', this.onWindowResize);
        // Set up file upload handler, used for drag/drop, pasting, and insertDoc method.
        $('#chatContainer').fileupload({
            dropZone: $('body'),
            pasteZone: $('textarea#messageInput'),
            url: window.serverSettings.installUrl + 'api/editFile.php?' + $.param({
                "_action": "create",
                "uploadMethod": "put",
                "dataEncode": "binary",
                "fileName": "pasteupload.png",
                "access_token": window.sessionHash
            }),
            type: 'PUT',
            multipart: false,
            send: function (e, data) {
                data.url += ('&roomId=' + _this.options.roomId);
                return true;
            }
        });
        // Prevent default drag/drop handler, in conjunction with above.
        $(document).bind('drop dragover', function (e) {
            e.preventDefault();
        });
        // Send messages on form submit.
        $('#sendForm').bind('submit', (function () {
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
        // Submit form on enter press.
        $('textarea#messageInput').onEnter(function () {
            $('#sendForm').submit();
            return false;
        });
        // Detect form typing
        $('textarea#messageInput').on('keyup', fim_debounce(function () {
            fimApi.stoppedTyping(_this.options.roomId);
            _this.isTyping = false;
        }, 2000)).on('keydown', function () {
            if (!_this.isTyping) {
                _this.isTyping = true;
                fimApi.startedTyping(_this.options.roomId);
            }
        });
        // Try to allow resizes of the messageInput. (Currently kinda broken.)
        $('textarea#messageInput').mouseup(this.onWindowResize);
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
                    /*** Get Messages ***/
                    _this.populateMessages();
                }
                if (!(roomData.permissions.properties || roomData.permissions.grant)) {
                    $('#active-view-room #chatContainer button[name=editRoom]').hide();
                }
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
            'begin': function () {
                $('ul#activeUsers').html('');
            },
            'each': function (user) {
                jQuery.each(user.rooms, function (index, status) {
                    $('ul#activeUsers')
                        .append($('<li>').attr('class', 'list-group-item')
                        .append(fim_buildUsernameTag($('<span>'), user.id, fim_getUsernameDeferred(user.id), true))
                        .append(status.typing ? $('<i class="fa fa-keyboard-o"></i>') : $('')));
                });
            }
        });
    },
    setRoom: function (roomId) {
        if (this.options.roomId != roomId) {
            this.init({
                'roomId': roomId
            });
        }
    },
    eventListener: function () {
        var _this = this;
        var roomSource = new EventSource(directory + 'stream.php?queryId=' + this.options.roomId + '&streamType=room&lastEvent=' + this.options.lastEvent + '&lastMessage=' + this.options.lastMessage + '&access_token=' + window.sessionHash);
        var eventHandler = (function (callback) {
            return (function (event) {
                _this.options.lastEvent = Math.max(_this.options.lastEvent, event.id);
                callback.call(_this, JSON.parse(event.data));
            });
        });
        roomSource.addEventListener('newMessage', eventHandler(this.newMessageHandler), false);
        roomSource.addEventListener('topicChange', eventHandler(this.topicChangeHandler), false);
        roomSource.addEventListener('deletedMessage', eventHandler(this.deletedMessageHandler), false);
        roomSource.addEventListener('editedMessage', eventHandler(this.editedMesageHandler), false);
    },
    newMessageHandler: function (active) {
        this.options.lastMessage = Math.max(this.options.lastMessage, active.id);
        this.newMessage(this.options.roomId, Number(active.id), fim_messageFormat(active, 'list'));
    },
    deletedMessageHandler: function (active) {
        $('#message' + active.id).fadeOut();
    },
    topicChangeHandler: function (active) {
        $('#topic').html(active.param1);
        console.log('Event (Topic Change): ' + active.param1);
    },
    editedMessageHandler: function (active) {
        if ($('#message' + active.id).length > 0) {
            active.userId = $('#message' + active.id + ' .userName').attr('data-userid');
            active.time = $('#message' + active.id + ' .messageText').attr('data-time');
            this.newMessage(this.options.roomId, Number(active.id), fim_messageFormat(active, 'list'));
        }
    },
    getMessagesFromFallback: function () {
        var _this = this;
        if (this.options.roomId) {
            fimApi.getEventsFallback({
                'streamType': 'room',
                'queryId': this.options.roomId,
                'lastEvent': this.options.lastEvent
            }, {
                each: (function (event) {
                    if (Number(event.id) > Number(_this.options.lastEvent)) {
                        _this.options.lastEvent = event.id;
                    }
                    if (event.eventName == "newMessage") {
                        _this.newMessageHandler(event.data);
                    }
                }),
                end: (function () {
                    if (window.requestSettings.serverSentEvents) {
                        _this.eventListener();
                    }
                    else {
                        window.setTimeout((function () {
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
    },
    populateMessages: function () {
        var _this = this;
        $(document).ready((function () {
            // Clear the message list.
            $('#messageList').html('');
            window.requestSettings[_this.options.roomId] = {
                lastMessage: null,
                firstRequest: true
            };
            _this.messageIndex[_this.options.roomId] = [];
            // Get New Messages
            if (_this.options.roomId) {
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
            else {
                console.log('Not requesting messages; room undefined.');
            }
            if (_this.options.intervalPing)
                clearInterval(_this.options.intervalPing);
            fimApi.ping(_this.options.roomId);
            _this.options.intervalPing = window.setInterval((function () {
                fimApi.ping(_this.options.roomId);
            }), 5 * 60 * 1000);
            _this.onWindowResize();
        }));
    },
    sendMessage: function (message, ignoreBlock, flag) {
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
    },
    disableSender: function () {
        $('#messageInput').attr('disabled', 'disabled'); // Disable input boxes.
        $('#icon_url').button({ disabled: true }); // "
        $('#icon_submit').button({ disabled: true }); // "
        $('#icon_reset').button({ disabled: true }); // "
    },
    enableSender: function () {
        $('#messageInput').removeAttr('disabled'); // Make sure the input is not disabled.
        $('#icon_url').button({ disabled: false }); // "
        $('#icon_submit').button({ disabled: false }); // "
        $('#icon_reset').button({ disabled: false }); // "
    }
};
//# sourceMappingURL=fim-popup-room.js.map