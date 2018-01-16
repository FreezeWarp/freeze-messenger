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
/*********************************************************
 ************************* END ***************************
 ************** Repeat-Action Popup Methods **************
 *********************************************************/