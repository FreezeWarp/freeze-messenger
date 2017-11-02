;
popup.prototype.room = {
    options: {
        roomId: 0,
        intervalPing: false,
        lastEvent: 0,
        lastMessage: 0
    },
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
    init: function (options) {
        var _this = this;
        for (var i in options)
            this.options[i] = options[i];
        var intervalPing;
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
        $(document).bind('drop dragover', function (e) {
            e.preventDefault();
        });
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
        $('#messageInput').onEnter(function () {
            $('#sendForm').submit();
            return false;
        });
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
                $('#activeUsers').html('<ul></ul>');
            },
            'each': function (user) {
                $('#activeUsers > ul').append($('<li>').append(fim_buildUsernameTag($('<span>'), user.id, fim_getUsernameDeferred(user.id), true)));
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
        fim_newMessage(this.options.roomId, Number(active.id), fim_messageFormat(active, 'list'));
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
            fim_newMessage(this.options.roomId, Number(active.id), fim_messageFormat(active, 'list'));
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
            messageIndex[_this.options.roomId] = [];
            // Get New Messages
            if (_this.options.roomId) {
                fimApi.getMessages({
                    'roomId': _this.options.roomId
                }, {
                    each: (function (messageData) {
                        fim_newMessage(Number(_this.options.roomId), Number(messageData.id), fim_messageFormat(messageData, 'list'));
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
            windowDraw();
            //windowDynaLinks();
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