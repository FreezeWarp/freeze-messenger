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
            'search' : standard.archive.options.searchText,
            'messageIdStart' : standard.archive.options.firstMessage,
            'archive' : 1,
            'page' : standard.archive.options.page
        }, {
            'reverseEach' : false,
            'each' : function(messageData) {
                $.when(fim_messageFormat(messageData, 'table')).then(function(messageText) {
                    $('#archiveMessageList').append(messageText);
                    standard.archive.messageData[messageData.id] = messageData;
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


/* Trigger a login using provided data. This will open a login form if neccessary. */
standard.prototype.login = function(options) {
    if (options.start) options.start();

    fimApi.login({
        'username' : options.username,
        'password' : options.password,
        'client_id' : 'WebPro'
    }, {
        end : function(activeLogin) {
            window.activeLogin = activeLogin;
            window.userId = activeLogin.userData.userId;
            window.anonId = activeLogin.anonId;
            window.sessionHash = activeLogin.access_token;
            window.permissions = activeLogin.permissions;

            if (!anonId) {
                $.cookie('webpro_username', options.username, { expires : 14 });
                $.cookie('webpro_password', options.password, { expires : 14 });

                // As soon as we have a valid login, we start polling getUnreadMessages.
                fimApi.getUnreadMessages(null, {'each' : function(message) {
                    fim_showMissedMessage(message);
                }});
            }


            // As soon as we have a token, populate active users.
            fimApi.getActiveUsers({
                'roomIds' : [1]
            }, {
                'refresh' : 15000,
                'exception' : function(exception) { console.log(exception);
                    // noLogin exceptions will commonly happen if the user is switching logins. We could either surpress the errors, as we do here, or disable the refresh until the user relogins in, which is more trouble than it's really worth.
                    if (exception.string !== 'noLogin') {
                        fimApi.getDefaultExceptionHandler()(exception);
                    }
                },
                'begin' : function() {
                    $('#activeUsers').html('<ul></ul>');
                },
                'each' : function(user) {
                    $('#activeUsers > ul').append('<li><span class="userName" data-userId="' + user.userData.userId + '" style=""' + user.userData.userNameFormat + '"">' + user.userData.userName + '</span></li>');
                },
                'end' : function() {
                    contextMenuParseUser('#activeUsers');
                }
            });


            if (options.finish) options.finish();


            if (!roomId) {
                fim_hashParse({defaultRoomId : activeLogin.defaultRoomId}); // When a user logs in, the hash data (such as room and archive) is processed, and subsequently executed.

                /*** A Hack of Sorts to Open Dialogs onLoad ***/
                if (typeof prepopup === 'function') { prepopup(); prepopup = false; }
            }
            return false;
        },
        error: function(data) {
            if (options.error) options.error(data);

            return false;
        }
    });
};


standard.prototype.logout = function() {
    $.cookie('webpro_username', null);
    $.cookie('webpro_password', null);

    fimApi.getMessages(null, {close : true}); // TODO: others
    fimApi.getUnreadMessages(null, {close : true});

    popup.login();
};


standard.prototype.getMessages = function() {
    console.log("Getting messages from roomId: " + window.roomId);

    if (window.roomId) {
        var encrypt = 'base64';

        if (requestSettings.serverSentEvents && !requestSettings.firstRequest) { // Note that the event subsystem __requires__ serverSentEvents for various reasons. If you use polling, these events will no longer be fully compatible.
            messageSource = new EventSource(directory + 'stream.php?queryId=' + roomId + '&streamType=messages&lastEvent=' + requestSettings.lastMessage + '&access_token=' + sessionHash);
            roomSource = new EventSource(directory + 'stream.php?queryId=' + roomId + '&streamType=room&lastEvent=' + requestSettings.lastEvent + '&access_token=' + sessionHash);
            console.log('Starting EventSource; roomId: ' + roomId + '; lastEvent: ' + requestSettings.lastEvent + '; lastMessage: ' + requestSettings.lastMessage)

            messageSource.addEventListener('time', function(e) {
                console.log('The current time is: ' + e.data);
                return false;
            }, false);

            messageSource.addEventListener('message', function(e) {
                var active = JSON.parse(e.data);

                console.log('Event (New Message): ' + Number(active.id));

                $.when(fim_messageFormat(JSON.parse(e.data), 'list')).then(function(messageText) {
                    fim_newMessage(messageText, Number(active.id));
                });

                return false;
            }, false);

            roomSource.addEventListener('topicChange', function(e) {
                var active = JSON.parse(e.data);

                $('#topic').html(active.param1);
                console.log('Event (Topic Change): ' + active.param1);

                requestSettings.lastEvent = active.eventId;

                return false;
            }, false);

            /*        roomSource.addEventListener('deletedMessage', function(e)     {
             var active = JSON.parse(e.data)    ;

             $('#topic').html(active.param1    );
             console.log('Event (Topic Change): ' + active.param1)    ;

             requestSettings.lastEvent = active.eventId    ;

             return fals    e;
             }, false);    */

            /*        eventSource.addEventListener('missedMessage', function(e)     {
             var active = JSON.parse(e.data)    ;

             requestSettings.lastEvent = active.eventId;
             $.jGrowl('Missed Message', 'New messages have been made in:<br /><br /><a href="#room=' + active.roomId + '">' + active.roomName + '</a>'    );
             console.log('Event (Missed Message): ' + active.messageId)    ;

             return false;
             }, false);*/
        }
        else {
            var timeout = 5000;

            function getMessages_query() {
                fimApi.getMessages({
                    'roomId': roomId,
                    'initialRequest': (requestSettings.firstRequest ? 1 : 0),
                    'messageIdStart': requestSettings.lastMessage + 1
                }, {
                    'reverseEach' : true,
                    'each': function (messageData) {
                        $.when(fim_messageFormat(messageData, 'list')).then(function(messageText) {
                            fim_newMessage(messageText, Number(messageData.id));
                        });
                    },
                    'end': function () {
                        if (requestSettings.firstRequest) requestSettings.firstRequest = false;
                        timeout = 5000;

                        window.setTimeout(standard.getMessages, timeout);
                    },
                    'error': function () {
                        if (timeout < 60000) timeout += 5000;

                        window.setTimeout(standard.getMessages, timeout);
                    }
                });
            }

            getMessages_query();
        }
    }
    else {
        console.log('Not requesting messages; room undefined.');
    }

    return false;
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
    if (!roomId) {
        console.log("Failed changeRoom.");
        return false;
    }
    var intervalPing, intervalWhosOnline;

    if (roomId[0] === 'p' || roomId[0] === 'o') {
        fimApi.getPrivateRoom({
            'roomId' : roomId,
        }, {
            'begin' : function(roomData) { console.log(roomData);
                enableSender();
                window.roomId = roomData.roomId;

                $('#roomName').html(roomData.roomName); // Update the room name.

                standard.populateMessages(roomData.roomId);
            }
        });
    }
    else { // Normal procedure otherwise.
        fimApi.getRooms({
            'roomIds' : [roomId],
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
                window.roomId = roomData.roomId;

                $('#roomName').html(roomData.roomName); // Update the room name.
                $('#topic').html(roomData.roomTopic); // Update the room topic.

               /*** Get Messages (TODO: Streamline) ***/
                standard.populateMessages(roomData.roomId);
            }
        }});
    }
};


standard.prototype.populateMessages = function(roomId) {
    $(document).ready(function() {
        $('#messageList').html(''); // Clear the message list.

        requestSettings.firstRequest = true;
        requestSettings.lastMessage = 0;
        messageIndex = [];

        standard.getMessages();

        if (typeof intervalPing !== "undefined")
            clearInterval(intervalPing);

        fimApi.ping(roomId);
        intervalPing = window.setInterval(function() {
            fimApi.ping(roomId)
        }, 5 * 60 * 1000);

        windowDraw();
        windowDynaLinks();
    });
}


standard.prototype.deleteRoom = function(roomIdLocal) {
    $.post(directory + 'api/editRoom.php', 'action=delete&messageId=' + messageId + '&access_token=' + sessionHash + '&fim3_format=json', function(json) {
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

standard.prototype.favRoom = function(roomIdLocal) {
    $.post(directory + 'api/editRoomLists.php', 'action=add&roomListName=favRooms&roomIds=' + roomIdLocal + '&access_token=' + sessionHash + '&fim3_format=json', function(json) {
        return false;
    });
};

standard.prototype.unfavRoom = function(roomIdLocal) {
    $.post(directory + 'api/editRoomLists.php', 'action=remove&roomListName=favRooms&roomIds=' + roomIdLocal + '&access_token=' + sessionHash + '&fim3_format=json', function(json) {
        return false;
    });
};

standard.prototype.kick = function(userId, roomId, length) {
    fimApi.kickUser(userId, roomId, length, {
        'exception' : function(exception) {
            switch (exception.string) {
                case 'nopermission': dia.error('You do not have permision to moderate this room.'); break;
                case 'nokickuser': dia.error('That user may not be kicked!'); break;
                case 'baduser': dia.error('The user specified does not exist.'); break;
                case 'badroom': dia.error('The room specified does not exist.'); break;
                default:
                    fimApi.getDefaultExceptionHandler()(exception);
                break;
            }
        },
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


standard.prototype.deleteMessage = function(messageId) {
    fimApi.deleteMessage({
        'messageId' : messageId,
        'action' : 'delete'
    }, {
        'end' : function() { dia.info("The message was deleted.") }
    });

    return false;
};