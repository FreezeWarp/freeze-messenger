declare var fim_buildUsernameTag : any;
declare var fim_getUsernameDeferred : any;
declare var fim_buildRoomNameTag : any;
declare var fim_getRoomNameDeferred : any;
declare var fim_renderHandlebarsInPlace : any;
declare var fim_hashParse : any;
declare var fim_removeHashParameter : any;
declare var Resolver: any;

let standard = function() {
    this.userId = 0;
    this.sessionHash = "";
    this.anonId = "";
    this.activeLogin = {};

    this.lastEvent = 0;

    this.notifications = {};

    return;
};

standard.prototype.initialLogin = function(options) {
    let oldFinish = options.finish;

    options.finish = (activeLogin) => {
        if (oldFinish) oldFinish(activeLogin);

        if (!this.anonId) {
            $.cookie('webpro_username', options.username, { expires : 14 });
            $.cookie('webpro_password', options.password, { expires : 14 });

            // As soon as we have a valid login, we start polling getUnreadMessages.
            fimApi.getUnreadMessages(null, {'each' : (message) => {
                this.missedMessageHandler(message);
            }});
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
        end : (activeLogin) => {
            if ('userData' in activeLogin) { // A new set of user information is available.
                this.activeLogin = window.activeLogin = activeLogin;
                this.userId = window.userId = activeLogin.userData.id;
                this.anonId = window.anonId = activeLogin.userData.anonId;
            }
            else if (!this.activeLogin) { // If we already have an activeLogin, we can continue to use it. Otherwise, we must error.
                dia.error("The login did not return proper information. The page will reload in 3 seconds...");

                setTimeout(function() {
                    location.reload();
                }, 3000);
            }

            this.sessionHash = window.sessionHash = activeLogin.access_token;
            fim_removeHashParameter("sessionHash");

            if (options.finish) options.finish(activeLogin);

            if (activeLogin.expires && activeLogin.refresh_token) {
                $.cookie('webpro_refreshToken', activeLogin.refresh_token);

                setTimeout(() => {
                    this.login({
                        grantType : 'refresh_token',
                        refreshToken : activeLogin.refresh_token
                    });
                }, activeLogin.expires * 1000 / 2);
            }

            // TODO: only once
            fimApi.getRooms({
                roomIds : window.serverSettings.officialRooms.concat(this.activeLogin.userData.favRooms).concat(this.activeLogin.userData.watchRooms)
            }, {
                begin : () => {
                    $('#navbar div[name=favRoomsList]').html('');
                    $('#navbar div[name=officialRoomsList]').html('');
                    $('#navbar div[name=watchRoomsList]').html('');
                },
                each : (roomData) => {
                    let html = $('<a>').attr({
                        'href' : '#room=' + roomData.id,
                        'class' : 'dropdown-item'
                    }).text(roomData.name);

                    if (this.activeLogin.userData.favRooms.indexOf(roomData.id) != -1)
                        $('#navbar div[name=favRoomsList]').append(html.clone());

                    if (this.activeLogin.userData.watchRooms.indexOf(roomData.id) != -1)
                        $('#navbar div[name=watchRoomsList]').append(html.clone());

                    if (roomData.official)
                        $('#navbar div[name=officialRoomsList]').append(html.clone());
                }
            });

            /* Private Room Form */
            $('#privateRoomForm input[name=userName]').autocompleteHelper('users');

            $("#privateRoomForm").submit(() => {
                let userName = $("#privateRoomForm input[name=userName]").val();
                let userId = $("#privateRoomForm input[name=userName]").attr('data-id');

                let whenUserIdAvailable = (userId) => {
                    window.location.hash = "#room=p" + [this.userId, userId].join(',');
                };

                if (!userId && userName) {
                    whenUserIdAvailable(userId);
                }
                else if (!userName) {
                    dia.error('Please enter a username.');
                }
                else {
                    $.when(Resolver.resolveUsersFromNames([userName]).then(function(pairs) {
                        whenUserIdAvailable(pairs[userName].id);
                    }));
                }

                return false; // Don't submit the form.

            });

            this.getEvents();

            return false;
        },
        error: (data) => {
            if (options.error) options.error(data);

            return false;
        }
    });
};


standard.prototype.getEventsFromFallback = function() {
    fimApi.getEventsFallback({
        'streamType': 'user',
        'lastEvent' : this.lastEvent
    }, {
        each: ((event) => {
            this.lastEvent = Math.max(Number(event.id), Number(this.lastEvent));

            if (event.eventName == "missedMessage") {
                this.missedMessageHandler(event.data);
            }
        }),
        end: (() => {
            if (window.requestSettings.serverSentEvents) {
                this.getEventsFromStream();
            }
            else {
                window.setTimeout((() => {
                    this.getEventsFromFallback()
                }), 3000);
            }
        })
    });
};

standard.prototype.getEventsFromStream = function() {
    let userSource = new EventSource(directory + 'stream.php?streamType=user&lastEvent=' + this.lastEvent + '&access_token=' + this.sessionHash);
    let eventHandler = ((callback) => {
        return ((event) => {
            this.lastEvent = Math.max(this.lastEvent, event.id);

            callback.call(this, JSON.parse(event.data));
        });
    });

    userSource.addEventListener('missedMessage', eventHandler(this.missedMessageHandler), false);
};

standard.prototype.getEvents = function() {
    if (window.requestSettings.serverSentEvents) {
        this.getEventsFromStream();
    }
    else {
        this.getEventsFromFallback();
    }
};

standard.prototype.missedMessageHandler = function(message) {
    if (message.roomId == window.roomId) {
        // we'll eventually probably want to do something fancy here, like offering to scroll to the last-viewed message.
    }
    else {
        console.log("missed message", message);
        if (!this.notifications["room" + message.roomId]) {
            this.notifications["room" + message.roomId] = $.notify({
                url : '#room=' + message.roomId,
                message : $('<span>').attr({
                    'class': 'missedMessage',
                    'id': "missedMessage" + message.roomId,
                    'data-roomId': message.roomId
                }).prop('outerHTML')
            }, {
                newest_on_top : true,
                type : "info",
                placement: {
                    from : 'top',
                    align : 'right',
                },
                delay : 0,
                animate : {
                    exit : ""
                },
                onClose : () => {
                    fimApi.markMessageRead(message.roomId);
                },
                url_target : "_self"
            });
        }

        $('#missedMessage' + message.roomId.toString().replace(',', '\\,')).replaceWith(
            $('<span>').text('New message from ')
                .append(fim_buildUsernameTag($('<strong>'), message.senderId, fim_getUsernameDeferred(message.senderId)))
                .append(' has been made in ')
                .append(fim_buildRoomNameTag($('<strong>'), message.roomId, fim_getRoomNameDeferred(message.roomId)))
                .append(message.missedMessages ? $('<span>').text('(Other messages: ' + message.otherMessages + ')') : '')
        );
    }
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