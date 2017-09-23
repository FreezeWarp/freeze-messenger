var standard = function() {

    return;
}

standard.prototype.archive = {
    options : {
        encrypt : 'base64',
        searchText : '',
        resultLimit : 40,
        searchUser : 0,
        firstMessage : 0,
        page : 0,
        roomId : 0
    },

    messageData : {},

    init : function(options) {
        var _this = this;
        for (i in options) standard.archive.options[i] = options[i];

        $('#searchText, #resultLimit, #searchUser, #archiveNext, #archivePrev, #export, .updateArchiveHere').unbind('change');

        $('#searchText, #resultLimit').bind('change', function() {
            standard.archive.update($(this).attr('id'), $(this).val());
            standard.archive.retrieve();
        });

        $('#searchUser').bind('change', function() {
            standard.archive.update($(this).attr('id'), $(this).attr('data-id'));
            standard.archive.retrieve();
        })

        $('#archiveNext').bind('click', function() {
            standard.archive.nextPage();
        });

        $('#archivePrev').bind('click', function() {
            standard.archive.prevPage();
        });

        $('#archiveDialogue > table').on('click', '.updateArchiveHere', function() {
            standard.archive.options.firstMessage = $(this).attr('data-messageId');
            window.location.hash = '#room=' + _this.options.roomId + '#message=' + $(this).attr('data-messageId');

            standard.archive.retrieve();
        });

        $('#export').bind('click', function() {
            popup.exportArchive();
        });
    },

    retrieve : function() { // TODO: callback?
        $('#archiveMessageList').html('');
        standard.archive.messageData = {};

        fimApi.getMessages({
            'roomId' : standard.archive.options.roomId,
            'userIds' : [standard.archive.options.searchUser],
            'messageTextSearch' : standard.archive.options.searchText,
            'messageIdStart' : standard.archive.options.firstMessage,
            'archive' : 1,
            'page' : standard.archive.options.page
        }, {
            'reverseEach' : false,
            'each' : function(messageData) {
                $.when(fim_messageFormat(messageData, 'table')).then(function(messageText) {
                    $('#archiveMessageList').append(messageText);
                    standard.archive.messageData[messageData.id] = messageData;
                    windowDraw();
                });
            },
            'end' : function(messages) {
                if (!Object.keys(messages).length) {
                    $('#archiveNext').button({ disabled : true });
                }
                else {
                    $('#archiveNext').button({ disabled : false });
                }
            }
        });
    },

    nextPage : function () {
        $('#archivePrev').button({ disabled : false });

        if (this.options.firstMessage) {
            this.options.firstMessage -= this.options.searchLimit;
        }
        else {
            this.options.page++;
        }

        this.retrieve();
    },

    prevPage : function () {
        if (this.options.firstMessage) {
            this.options.firstMessage += this.options.searchLimit;
        }
        else if (this.options.page !== 0) this.options.page--;

        if (this.options.page <= 0) {
            $('#archivePrev').button({ disabled : true });
        }

        this.retrieve();
    },

    update : function (option, value) {
        standard.archive.options[option] = value;
    }
};


standard.prototype.initialLogin = function(options) {
    options.finish = function() {
        if (!window.anonId) {
            $.cookie('webpro_username', options.username, { expires : 14 });
            $.cookie('webpro_password', options.password, { expires : 14 });

            // As soon as we have a valid login, we start polling getUnreadMessages.
            fimApi.getUnreadMessages(null, {'each' : function(message) {
                fim_showMissedMessage(message);
            }});
        }

        fim_hashParse({defaultRoomId : window.activeLogin.userData.defaultRoomId}); // When a user logs in, the hash data (such as room and archive) is processed, and subsequently executed.
    };

    standard.login(options);
}


/* Trigger a login using provided data. This will open a login form if necessary. */
standard.prototype.login = function(options) {
    if (options.start) options.start();

    fimApi.login({
        'grant_type' : options.grantType,
        'username' : options.username,
        'password' : options.password,
        'access_token' : options.sessionHash,
        'refresh_token' : options.refreshToken,
        'client_id' : 'WebPro'
    }, {
        end : function(activeLogin) {
            if ('userData' in activeLogin) { // A new set of user information is available.
                window.activeLogin = activeLogin;
                window.userId = activeLogin.userData.id;
                window.anonId = activeLogin.userData.anonId;
                window.permissions = activeLogin.userData.permissions;
            }
            else if (!window.activeLogin) { // If we already have an activeLogin, we can continue to use it. Otherwise, we must error.
                dia.error("The login did not return proper information. The page will reload in 3 seconds...");

                setTimeout(function() {
                    location.reload();
                }, 3000);
            }

            window.sessionHash = activeLogin.access_token;

            if (activeLogin.expires && activeLogin.refresh_token) {
                $.cookie('webpro_refreshToken', activeLogin.refresh_token);

                setTimeout(function() {
                    standard.login({
                        grantType : 'refresh_token',
                        refreshToken : activeLogin.refresh_token
                    });
                }, activeLogin.expires * 1000 / 2)
            }


            // TODO: only once
            fimApi.getRooms({
                roomIds : window.activeLogin.userData.favRooms
            }, {
                begin : function() {
                    $('#favRoomsList').html('');
                },
                each : function(roomData) {
                    console.log(roomData);

                    var html = $('<li>').append(
                        $('<a>').attr('href', '#room=' + roomData.id).text(roomData.name)
                    );

                    //TODO
                    //if (roomData.ownerId = window.activeLogin.userId)
                    //    $('#ownedRoomsList').append(html);

                    if (window.activeLogin.userData.favRooms.indexOf(roomData.id) != -1)
                        $('#favRoomsList').append(html);

                    if (window.activeLogin.userData.watchRooms.indexOf(roomData.id) != -1)
                        $('#watchRoomsList').append(html);

                    //TODO
                    //if (roomData.official)
                    //    $('#officialRoomsList').append(html);
                }
            });


            if (options.finish) options.finish();

            /*** A Hack of Sorts to Open Dialogs onLoad ***/
            if (typeof prepopup === 'function') { prepopup(); prepopup = false; }

            return false;
        },
        error: function(data) {
            if (options.error) options.error(data);

            return false;
        }
    });
};


standard.prototype.logout = function() {
    // TODO: clear refresh token on server?

    $.cookie('webpro_username', null);
    $.cookie('webpro_password', null);
    $.cookie('webpro_refreshToken', null);

    fimApi.getMessages(null, {close : true});
    fimApi.getActiveUsers(null, {close : true});
    fimApi.getUnreadMessages(null, {close : true});
    // TODO: others?

    popup.login();
};


standard.prototype.roomEventListener = function(roomId) {
    var roomSource = new EventSource(directory + 'stream.php?queryId=' + roomId + '&streamType=room&lastEvent=' + window.requestSettings.lastEvent + '&lastMessage=' + window.requestSettings.lastMessage + '&access_token=' + window.sessionHash);
    var eventHandler = function(callback) {
        return function(event) {
            if (event.id > requestSettings.lastEvent) {
                window.requestSettings.lastEvent = event.id;
            }

            callback(JSON.parse(event.data));
        }
    };

    roomSource.addEventListener('newMessage', eventHandler(function(active) {
        requestSettings.lastMessage = Math.max(requestSettings.lastMessage, active.id);

        $.when(fim_messageFormat(active, 'list')).then(function(messageText) {
            fim_newMessage(messageText, Number(active.id));
        });
    }), false);

    roomSource.addEventListener('topicChange', eventHandler(function(active) {
        $('#topic').html(active.param1);
        console.log('Event (Topic Change): ' + active.param1);
    }), false);

    // TODO
    roomSource.addEventListener('deletedMessage', function(e) {
    });
};


standard.prototype.getMessages = function() {
    console.log("Getting messages from roomId: " + window.roomId);

    if (window.roomId) {
        fimApi.getMessages({
            'roomId': roomId,
        }, {
            autoId : true,
            refresh : (window.requestSettings.serverSentEvents ? 3000 : 3000),
            each: function (messageData) {
                $.when(fim_messageFormat(messageData, 'list')).then(function(messageText) {
                    fim_newMessage(messageText, Number(messageData.id));
                });
            },
            end: function () {
                if (window.requestSettings.serverSentEvents) {
                    fimApi.getMessages(null, {'close' : true});

                    standard.roomEventListener(window.roomId);
                }
            }
        });
    }
    else {
        console.log('Not requesting messages; room undefined.');
    }

    return false;
};


standard.prototype.populateMessages = function(roomId) {
    $(document).ready(function() {
        // Clear the message list.
        $('#messageList').html('');
        requestSettings.lastMessage = null;
        requestSettings.firstRequest = true;
        messageIndex = [];

        // Get New Messages
        standard.getMessages();

        // TODO
        if (typeof intervalPing !== "undefined")
            clearInterval(intervalPing);

        fimApi.ping(roomId);
        intervalPing = window.setInterval(function() {
            fimApi.ping(roomId)
        }, 5 * 60 * 1000);

        windowDraw();
        windowDynaLinks();
    });
};


standard.prototype.sendMessage = function(message, ignoreBlock, flag) {
    if (!window.roomId) {
        popup.selectRoom();
    }
    else {
        ignoreBlock = (ignoreBlock === 1 ? 1 : '');

        fimApi.sendMessage(window.roomId, {
            'ignoreBlock' : ignoreBlock,
            'message' : message,
            'flag' : (flag ? flag : '')
        }, {
            'end' : function (message) {
                if ("censor" in message && message.censor.size) {
                    console.log("censor match.");
                    dia.info(Object.values(message.censor).join('<br /><br />'), "Censor warning: " + Object.keys(message.censor).join(', '));
                }
            },
            'exception' : function(exception) {
                if (exception.string === 'confirmCensor')
                    dia.confirm({
                        'text' : exception.details,
                        'true' : function() {
                            standard.sendMessage(message, 1, flag);
                        }
                    }, "Censor Warning");
                else if (exception.string === 'spaceMessage') {
                    dia.error("Too... many... spaces!")
                }
                else { fimApi.getDefaultExceptionHandler()(exception); }
            },
            'error' : function(request) {
                if (settings.reversePostOrder)
                    $('#messageList').append('Your message, "' + message + '", could not be sent and will be retried.');
                else
                    $('#messageList').prepend('Your message, "' + message + '", could not be sent and will be retried.');

                window.setTimeout(function() { standard.sendMessage(message) }, 5000);

                return false;
            }
        });
    }
};


standard.prototype.changeRoom = function(roomId) {
    var intervalPing;


    fimApi.getRooms({
        'id' : roomId,
        'permLevel' : 'view'
    }, {'each' : function(roomData) {
        if (!roomData.permissions.view) { // If we can not view the room
            window.roomId = false; // Set the global roomId false.
            popup.selectRoom(); // Prompt the user to select a new room.
            dia.error('You have been restricted access from this room. Please select a new room.');
        }
        else if (!roomData.permissions.post) { // If we can view, but not post
            dia.error('You are not allowed to post in this room. You will be able to view it, though.');
            disableSender();
        }
        else { // If we can both view and post.
            enableSender();
        }


        if (roomData.permissions.view) { // If we can view the room...
            window.roomId = roomData.id;

            $('#roomName').html(roomData.name); // Update the room name.
            $('#topic').html(roomData.topic); // Update the room topic.

           /*** Get Messages (TODO: Streamline) ***/
            standard.populateMessages(roomData.id);
        }
    }});


    /* Populate Active Users for the Room */
    fimApi.getActiveUsers({
        'roomIds' : [roomId]
    }, {
        'refresh' : 15000,
        'timerId' : 1,
        'begin' : function() {
            $('#activeUsers').html('<ul></ul>');
        },
        'each' : function(user) {
            $('#activeUsers > ul').append('<li><span class="userName" data-userId="' + user.userData.id + '" style=""' + user.userData.nameFormat + '"">' + user.userData.name + '</span></li>');
        },
        'end' : function() {
            contextMenuParseUser('#activeUsers');
        }
    });
};


standard.prototype.deleteRoom = function(roomIdLocal) {
    $.post(directory + 'api/editRoom.php', 'action=delete&messageId=' + messageId + '&access_token=' + sessionHash, function(json) {
        var errStr = json.editRoom.errStr,
            errDesc = json.editRoom.errDesc;

        switch (errStr) {
            case '': console.log('Message ' + messageId + ' deleted.'); break;
            case 'nopermission': dia.error('You do not have permission to administer this room.'); break;
            case 'badroom': dia.error('The specified room does not exist.'); break;
        }

        return false;
    }); // Send the form data via AJAX.
};

standard.prototype.kick = function(userId, roomId, length) {
    fimApi.kickUser(userId, roomId, length, {
        'end' : function() {
            dia.info('The user has been kicked.', 'Success');
            $("#kickUserDialogue").dialog('close');
        }
    });

    return false;
};

standard.prototype.unkick = function(userId, roomId) {
    fimApi.unkickUser(userId, roomId, {
        'exception' : function(exception) {
            switch (exception.string) {
                case 'nopermission': dia.error('You do not have permision to moderate this room.'); break;
                case 'baduser': case 'badroom': dia.error('Odd error: the user or room sent do not seem to exist.'); break;
                default:
                    fimApi.getDefaultExceptionHandler()(exception);
                    break;
            }
        },
        'end' : function() {
            dia.info('The user has been unkicked.', 'Success');
            $("#kickUserDialogue").dialog('close');
        }
    });

    return false;
};


standard.prototype.deleteMessage = function(roomId, messageId) {
    fimApi.deleteMessage(roomId, messageId, {
        'end' : function() { dia.info("The message was deleted.") }
    });

    return false;
};