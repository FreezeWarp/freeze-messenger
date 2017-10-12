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

        fim_hashParse({room : activeLogin.userData.defaultRoomId}); // When a user logs in, the hash data (such as room and archive) is processed, and subsequently executed.
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