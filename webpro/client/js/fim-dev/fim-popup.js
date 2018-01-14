/*********************************************************
 ************************ START **************************
 ************** Repeat-Action Popup Methods **************
 *********************************************************/

var popup = function() {
    return;
}
/*** START Login ***/

popup.prototype.login = function() {
    function login_success(activeLogin) {
        $('#modal-login').modal('hide');

        if (!activeLogin.userData.permissions.view)
            dia.info('You are now logged in as ' + activeLogin.userData.name + '. However, you are not allowed to view and have been banned by an administrator.', 'danger');
        else if (!activeLogin.userData.permissions.post)
            dia.info('You are now logged in as ' + activeLogin.userData.name + '. However, you are not allowed to post and have been silenced by an administrator. You may still view rooms which allow you access.', 'warning');
        else
            dia.info('You are now logged in as ' + activeLogin.userData.name + '.', 'success');
    }

    function login_fail(data) {
        switch (data.string) {
            case 'sessionMismatchUserId':
            case 'sessionMismatchBrowser':
            case 'sessionMismatchIp':
            case 'invalidSession': dia.error('The server rejected the stored session. Please login.'); break;
            case 'loginRequired': dia.error("A valid login must be provided. Please login."); break;
            case 'invalid_grant': dia.error("The login provided is not valid. You most likely entered an incorrect password."); break;
            default: dia.error('Unknown error logging in: ' + data.string); break;
        }
    }

    fim_renderHandlebarsInPlace($('#modals-login'));
    $('#modal-login').modal();

    // Submission
    $("#loginForm").submit(function() {
        var loginForm = $('#loginForm');
        standard.initialLogin({
            username : $('input[name=userName]', loginForm).val(),
            password : $('input[name=password]', loginForm).val(),
            finish : login_success,
            error : login_fail
        });

        return false;
    });

    // Close
    $('#modal-login').on('hide.bs.modal', function () {
        if (!window.userId) {
            standard.initialLogin({
                grantType : 'anonymous',
                rememberMe : false,
                finish : login_success,
                error : login_fail
            });
        }
    });
};

/*** END Login ***/




/*** START Stats ***/

popup.prototype.viewStats = function() {
    var number = 10;

    $('#modal-stats').modal();

    $('table#viewStats > tbody').html('');
    for (i = 1; i <= number; i += 1) {
        $('table#viewStats > tbody').append('<tr><th>' + i + '</th></tr>');
    }

    fimApi.getStats({
        'roomId' : window.roomId
    }, {
        each : function(room) {
            $('table#viewStats > thead > tr').append(
                $('<th>').append(
                    $('<span>').attr({
                        'class' : 'roomName',
                        'data-roomId' : room.id
                    }).text(room.name)
                )
            );

            var i = 0;
            jQuery.each(room.users, function(userId, user) {
                $('table#viewStats > tbody > tr').eq(i).append(
                    $('<td>').append(
                        fim_buildUsernameTag($('<span>'), user.id),
                        $('<span>').text('(' + user.messageCount + ')')
                    )
                );

                i++;
            });
        },
    });
};

/*** END Stats ***/





/*** START Online ***/

popup.prototype.online = function() {
    $('#modal-online').modal();

    fimApi.getActiveUsers({}, {
        'refresh' : 60 * 1000,
        'begin' : function() {
            $('#onlineUsers').html('');
        },
        'each' : function(user) {
            var roomData = [];
            jQuery.each(user.rooms, function(roomId, room) {
                if (roomData.length) roomData.push($('<span>').text(', '));

                roomData.push(fim_buildRoomNameTag($('<span>'), room.id));
            });

            $('#onlineUsers').append($('<tr>').append(
                $('<td>').append(
                    fim_buildUsernameTag($('<span>'), user.id)
                )
            ).append($('<td>').append(roomData)));
        }
    });

    $('#modal-online').on('hidden.bs.modal', function () {
        fimApi.getActiveUsers({}, {
            'close' : true
        });
    });
};

/*** END Online ***/




/*** START Kick ***/

popup.prototype.kick = function(userId, roomId) {
    // Render Modal
    $('#modal-kick').modal();


    // Create Autocompletes
    $('#kickUserForm input[name=userName]').autocompleteHelper('users', userId);
    $('#kickUserForm input[name=roomName]').autocompleteHelper('rooms', roomId);


    // Process Submit
    $("#kickUserForm").off('submit').on('submit', function() {
        var userId = $("#kickUserForm input[name=userName]").attr('data-id'),
            roomId = $("#kickUserForm input[name=roomName]").attr('data-id'),
            length = Math.floor(
                Number($('#kickUserForm input[name=time]').val()
                    *
                    Number($('#kickUserForm select[name=interval] > option:selected').attr('value')))
            );


        if (!roomId) {
            dia.error("An invalid room was provided.");
        }
        else if (!userId) {
            dia.error("An invalid user was provided.");
        }
        else {
            fimApi.kickUser(userId, roomId, length, {
                'end' : function() {
                    dia.info('The user has been kicked.', 'success');
                    $('#modal-kick').modal('hide');
                }
            });
        }


        return false;
    });


    return false;
};

/*** END Kick ***/



/*** START Archive ***/

popup.prototype.archive = {
    options : {
        searchText : '',
        resultLimit : 40,
        searchUser : 0,
        firstMessage : null,
        page : 0,
        roomId : 0
    },

    messageData : {},

    init : function(options) {
        var _this = this;

        for (i in options)
            this.options[i] = options[i];

        if (!this.options.roomId)
            this.options.roomId = window.roomId;


        $('#active-view-archive form#archiveSearch input[name=searchText]').unbind('change').bind('change', function() {
            _this.update('searchText', $(this).val());
            _this.retrieve();
        });

        $('#active-view-archive form#archiveSearch input[name=searchUser]').unbind('change').bind('change', function() {
            _this.update('searchUser', $(this).attr('data-id'));
            _this.retrieve();
        }).autocompleteHelper('users');


        $('#active-view-archive button[name=archiveNext]').unbind('click').bind('click', function() {
            _this.nextPage();
        });
        $('#active-view-archive button[name=archivePrev]').unbind('click').bind('click', function() {
            _this.prevPage();
        });

        $('#active-view-archive button[name=export]').unbind('click').bind('click', function() {
            popup.exportArchive();
        });


        _this.retrieve();
    },

    setRoom : function(roomId) {
        if (this.options.roomId != roomId) {
            this.options.roomId = roomId;
            this.retrieve();
        }
    },

    setFirstMessage : function(firstMessage) {
        this.options.firstMessage = firstMessage;
        this.options.lastMessage = null;
        this.retrieve();
    },

    setLastMessage : function(lastMessage) {
        this.options.lastMessage = lastMessage;
        this.options.firstMessage = null;
        this.retrieve();
    },

    nextPage : function () {
        fim_atomicRemoveHashParameterSetHashParameter('firstMessage', 'lastMessage', $('#active-view-archive table.messageTable tr:last-child > td > span.messageText').attr('data-messageid'));
    },

    prevPage : function () {
        fim_atomicRemoveHashParameterSetHashParameter('lastMessage', 'firstMessage', $('#active-view-archive table.messageTable tr:first-child > td > span.messageText').attr('data-messageid'));
    },

    retrieve : function() {
        var _this = this;

        fimApi.getMessages({
            'roomId' : _this.options.roomId,
            'userIds' : [_this.options.searchUser],
            'messageTextSearch' : _this.options.searchText,
            'messageIdStart' : _this.options.firstMessage,
            'messageIdEnd' : _this.options.lastMessage,
            'page' : _this.options.page
        }, {
            'reverseEach' : (_this.options.firstMessage ? true : false),
            'end' : function(messages) {
                $('#active-view-archive table.messageTable > tbody').html('');
                $('#active-view-archive button[name=archivePrev]').prop('disabled', false);

                this.messageData = {};

                jQuery.each(messages, function(index, messageData) {
                    $('#active-view-archive table.messageTable > tbody').append(fim_messageFormat(messageData, 'table').append(
                        $('<td class="d-none d-md-table-cell">').append(
                            $('<a href="#archive#room=' + _this.options.roomId + '#lastMessage=' + messageData.id + '">Show</a>')
                        )
                    ));

                    _this.messageData[messageData.id] = messageData;
                });

                if (messages.length < 2) {
                    if (_this.options.firstMessage)
                        $('#active-view-archive button[name=archivePrev]').prop('disabled', true);

                    if (_this.options.lastMessage)
                        $('#active-view-archive button[name=archiveNext]').prop('disabled', true);
                }
                else {
                    if (_this.options.firstMessage)
                        $('#active-view-archive button[name=archiveNext]').prop('disabled', false);

                    if (_this.options.lastMessage)
                        $('#active-view-archive button[name=archivePage]').prop('disabled', false);
                }
            },
        });
    },

    update : function (option, value) {
        this.options[option] = value;
    }
};

/*** END Archive ***/
/*********************************************************
 ************************* END ***************************
 ************** Repeat-Action Popup Methods **************
 *********************************************************/