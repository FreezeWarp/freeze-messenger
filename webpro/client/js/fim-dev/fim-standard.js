var standard = function() {
    return;
};


standard.prototype.initialLogin = function(options) {
    oldFinish = options.finish;

    options.finish = function(activeLogin) {
        if (oldFinish) oldFinish(activeLogin);

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

            if (options.finish) options.finish(activeLogin);

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

    $('#logout').parent().show();
    $('#login').parent().hide();

    popup.login();
};


standard.prototype.roomEventListener = function(roomId) {
    var roomSource = new EventSource(directory + 'stream.php?queryId=' + roomId + '&streamType=room&lastEvent=' + window.requestSettings[roomId].lastEvent + '&lastMessage=' + window.requestSettings[roomId].lastMessage + '&access_token=' + window.sessionHash);
    var eventHandler = function(callback) {
        return function(event) {
            if (event.id > window.requestSettings[roomId].lastEvent) {
                window.requestSettings[roomId].lastEvent = event.id;
            }

            callback(JSON.parse(event.data));
        }
    };

    roomSource.addEventListener('newMessage', eventHandler(function(active) {
        window.requestSettings[roomId].lastMessage = Math.max(window.requestSettings[roomId].lastMessage, active.id);

        fim_newMessage(roomId, Number(active.id), fim_messageFormat(active, 'list'));
    }), false);

    roomSource.addEventListener('topicChange', eventHandler(function(active) {
        $('#topic').html(active.param1);
        console.log('Event (Topic Change): ' + active.param1);
    }), false);

    roomSource.addEventListener('deletedMessage', eventHandler(function(active) {
        $('#message' + active.id).fadeOut();
    }), false);

    roomSource.addEventListener('editedMessage', eventHandler(function(active) {
        if ($('#message' + active.id).length > 0) {
            active.userId = $('#message' + active.id + ' .userName').attr('data-userid');
            active.time = $('#message' + active.id + ' .messageText').attr('data-time');

            fim_newMessage(roomId, Number(active.id), fim_messageFormat(active, 'list'));
        }
    }), false);
};


standard.prototype.getMessages = function() {
    console.log("Getting messages from roomId: " + window.roomId);

    if (window.roomId) {
        fimApi.getMessages({
            'roomId': window.roomId,
        }, {
            autoId : true,
            refresh : (window.requestSettings.serverSentEvents ? 3000 : 3000),
            each: function (messageData) {
                fim_newMessage(Number(window.roomId), Number(messageData.id), fim_messageFormat(messageData, 'list'));
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

        window.requestSettings[roomId] = {
            lastMessage : null,
            firstRequest : true
        };
        messageIndex[roomId] = [];

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
        popup.selectRoom.init();
    }
    else {
        ignoreBlock = (ignoreBlock === 1 ? 1 : '');

        fimApi.sendMessage(window.roomId, {
            'ignoreBlock' : ignoreBlock,
            'message' : message,
            'flag' : (flag ? flag : '')
        }, {
            'end' : function (message) {
                if ("censor" in message && Object.keys(message.censor).length > 0) {
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

    // Put the room hash in the URL.
    history.replaceState(undefined, undefined, "#room=" + roomId);

    fimApi.getRooms({
        'id' : roomId,
        'permLevel' : 'view'
    }, {'each' : function(roomData) {
        if (!roomData.permissions.view) { // If we can not view the room
            window.roomId = false; // Set the global roomId false.
            popup.selectRoom.init(); // Prompt the user to select a new room.
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
            $('#activeUsers > ul').append($('<li>').append(fim_buildUsernameTag($('<span>'), user.id, fim_getUsernameDeferred(user.id), true)));
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