var standard = function () {
    this.userId = 0;
    this.sessionHash = "";
    this.anonId = "";
    this.activeLogin = {};
    this.lastEvent = 0;
    this.notifications = {};
    return;
};
standard.prototype.initialLogin = function (options) {
    var _this = this;
    var oldFinish = options.finish;
    options.finish = function (activeLogin) {
        if (oldFinish)
            oldFinish(activeLogin);
        if (!_this.anonId) {
            $.cookie('webpro_username', options.username, { expires: 14 });
            $.cookie('webpro_password', options.password, { expires: 14 });
            // As soon as we have a valid login, we start polling getUnreadMessages.
            fimApi.getUnreadMessages(null, { 'each': function (message) {
                    _this.missedMessageHandler(message);
                } });
        }
        if (!window.roomId) {
            window.roomId = activeLogin.userData.defaultRoomId ? activeLogin.userData.defaultRoomId : 1;
        }
        fim_renderHandlebarsInPlace($("#entry-template"));
        fim_hashParse(); // When a user logs in, the hash data (such as room and archive) is processed, and subsequently executed.
    };
    this.login(options);
};
/* Trigger a login using provided data. This will open a login form if necessary. */
standard.prototype.login = function (options) {
    var _this = this;
    if (options.start)
        options.start();
    fimApi.login({
        'grant_type': options.grantType,
        'username': options.username,
        'password': options.password,
        'access_token': options.sessionHash,
        'refresh_token': options.refreshToken,
        'client_id': 'WebPro'
    }, {
        end: function (activeLogin) {
            if ('userData' in activeLogin) {
                _this.activeLogin = window.activeLogin = activeLogin;
                _this.userId = window.userId = activeLogin.userData.id;
                _this.anonId = window.anonId = activeLogin.userData.anonId;
            }
            else if (!_this.activeLogin) {
                dia.error("The login did not return proper information. The page will reload in 3 seconds...");
                setTimeout(function () {
                    location.reload();
                }, 3000);
            }
            _this.sessionHash = window.sessionHash = activeLogin.access_token;
            fim_removeHashParameter("sessionHash");
            if (options.finish)
                options.finish(activeLogin);
            if (activeLogin.expires && activeLogin.refresh_token) {
                $.cookie('webpro_refreshToken', activeLogin.refresh_token);
                setTimeout(function () {
                    _this.login({
                        grantType: 'refresh_token',
                        refreshToken: activeLogin.refresh_token
                    });
                }, activeLogin.expires * 1000 / 2);
            }
            // TODO: only once
            fimApi.getRooms({
                roomIds: window.serverSettings.officialRooms.concat(_this.activeLogin.userData.favRooms).concat(_this.activeLogin.userData.watchRooms)
            }, {
                begin: function () {
                    $('#navbar div[name=favRoomsList]').html('');
                    $('#navbar div[name=officialRoomsList]').html('');
                    $('#navbar div[name=watchRoomsList]').html('');
                },
                each: function (roomData) {
                    var html = $('<a>').attr({
                        'href': '#room=' + roomData.id,
                        'class': 'dropdown-item'
                    }).text(roomData.name);
                    if (_this.activeLogin.userData.favRooms.indexOf(roomData.id) != -1)
                        $('#navbar div[name=favRoomsList]').append(html.clone());
                    if (_this.activeLogin.userData.watchRooms.indexOf(roomData.id) != -1)
                        $('#navbar div[name=watchRoomsList]').append(html.clone());
                    if (roomData.official)
                        $('#navbar div[name=officialRoomsList]').append(html.clone());
                }
            });
            /* Private Room Form */
            $('#privateRoomForm input[name=userName]').autocompleteHelper('users');
            $("#privateRoomForm").submit(function () {
                var userName = $("#privateRoomForm input[name=userName]").val();
                var userId = $("#privateRoomForm input[name=userName]").attr('data-id');
                var whenUserIdAvailable = function (userId) {
                    window.location.hash = "#room=p" + [_this.userId, userId].join(',');
                };
                if (!userId && userName) {
                    whenUserIdAvailable(userId);
                }
                else if (!userName) {
                    dia.error('Please enter a username.');
                }
                else {
                    $.when(Resolver.resolveUsersFromNames([userName]).then(function (pairs) {
                        whenUserIdAvailable(pairs[userName].id);
                    }));
                }
                return false; // Don't submit the form.
            });
            _this.getEvents();
            return false;
        },
        error: function (data) {
            if (options.error)
                options.error(data);
            return false;
        }
    });
};
standard.prototype.getEventsFromFallback = function () {
    var _this = this;
    fimApi.getEventsFallback({
        'streamType': 'user',
        'lastEvent': this.lastEvent
    }, {
        each: (function (event) {
            _this.lastEvent = Math.max(Number(event.id), Number(_this.lastEvent));
            if (event.eventName == "missedMessage") {
                _this.missedMessageHandler(event.data);
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
};
standard.prototype.getEventsFromStream = function () {
    var _this = this;
    var userSource = new EventSource(directory + 'stream.php?streamType=user&lastEvent=' + this.lastEvent + '&access_token=' + this.sessionHash);
    var eventHandler = (function (callback) {
        return (function (event) {
            _this.lastEvent = Math.max(_this.lastEvent, event.id);
            callback.call(_this, JSON.parse(event.data));
        });
    });
    userSource.addEventListener('missedMessage', eventHandler(this.missedMessageHandler), false);
};
standard.prototype.getEvents = function () {
    if (window.requestSettings.serverSentEvents) {
        this.getEventsFromStream();
    }
    else {
        this.getEventsFromFallback();
    }
};
standard.prototype.missedMessageHandler = function (message) {
    if (message.roomId == window.roomId) {
        // we'll eventually probably want to do something fancy here, like offering to scroll to the last-viewed message.
    }
    else {
        if (!this.notifications["room" + message.roomId]) {
            this.notifications["room" + message.roomId] = $.notify({
                url: '#room=' + message.roomId,
                message: $('<span>').attr({
                    'class': 'missedMessage',
                    'id': "missedMessage" + message.roomId,
                    'data-roomId': message.roomId
                }).prop('outerHTML')
            }, {
                newest_on_top: true,
                type: "info",
                placement: {
                    from: 'top',
                    align: 'right'
                },
                delay: 0,
                animate: {
                    exit: ""
                },
                onClose: function () {
                    fimApi.markMessageRead(message.roomId);
                },
                url_target: "_self"
            });
        }
        $('#missedMessage' + message.roomId.replace(',', '\\,')).replaceWith($('<span>').text('New message from ')
            .append(fim_buildUsernameTag($('<strong>'), message.senderId, fim_getUsernameDeferred(message.senderId)))
            .append(' has been made in ')
            .append(fim_buildRoomNameTag($('<strong>'), message.roomId, fim_getRoomNameDeferred(message.roomId)))
            .append(message.missedMessages ? $('<span>').text('(Total unread messages: ' + message.missedMessages + ')') : ''));
    }
};
standard.prototype.logout = function () {
    // TODO: clear refresh token on server?
    $.cookie('webpro_username', null);
    $.cookie('webpro_password', null);
    $.cookie('webpro_refreshToken', null);
    fimApi.getMessages(null, { close: true });
    fimApi.getActiveUsers(null, { close: true });
    fimApi.getUnreadMessages(null, { close: true });
    // TODO: others?
    $('#logout').parent().show();
    $('#login').parent().hide();
    window.popup.login();
};
/*standard.prototype.deleteRoom = function(roomIdLocal) {
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
};*/ 
//# sourceMappingURL=fim-standard.js.map