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





/*** START Create Room ***/

popup.prototype.editRoom = {
    options : {
        roomId : null
    },

    setRoomId : function(roomId) {
        this.options.roomId = roomId ? roomId : null;
    },

    init : function() {

    },

    retrieve : function() {
        var _this = this;

        if (this.options.roomId != null)
            var action = 'edit';
        else
            var action = 'create';


        /**
         * Form Change Events
         */
        $('#editRoomForm input[name=allowPosting]').change(function() {
            if ($(this).is(':checked')) {
                $(this).closest('fieldset').attr('disabled', 'disabled');
            }
            else {
                $(this).closest('fieldset').removeAttr('disabled', 'disabled');
            }
        });


        /* Autocomplete Users and Groups */
        moderatorsList = new autoEntry($("#moderatorsContainer"), {
            'name' : 'moderators',
            'list' : 'users',
            'onAdd' : function(id) {
                if (action === 'edit') fimApi.createRoomPermissionUser(_this.options.roomId, id, ["view", "post", "moderate", "properties", "grant"])
            },
            'onRemove' : function(id) {
                if (action === 'edit') fimApi.deleteRoomPermissionUser(_this.options.roomId, id, ["moderate", "properties", "grant"])
            },
            'resolveFromIds' : Resolver.resolveUsersFromIds,
            'resolveFromNames' : Resolver.resolveUsersFromNames
        });

        allowedUsersList = new autoEntry($("#allowedUsersContainer"), {
            'name' : 'allowedUsers',
            'list' : 'users',
            'onAdd' : function(id) {
                if (action === 'edit') fimApi.createRoomPermissionUser(_this.options.roomId, id, ["view", "post"])
            },
            'onRemove' : function(id) {
                if (action === 'edit') fimApi.deleteRoomPermissionUser(_this.options.roomId, id, ["view", "post", "changeTopic", "moderate", "properties", "grant"]) // In effect, reset the user's permissions to the default.
            },
            'resolveFromIds' : Resolver.resolveUsersFromIds,
            'resolveFromNames' : Resolver.resolveUsersFromNames
        });

        allowedGroupsList = new autoEntry($("#allowedGroupsContainer"), {
            'name' : 'allowedGroups',
            'list' : 'groups',
            'onAdd' : function(id) {
                if (action === 'edit') fimApi.createRoomPermissionGroup(_this.options.roomId, id, ["view", "post"])
            },
            'onRemove' : function(id) {
                if (action === 'edit') fimApi.deleteRoomPermissionGroup(_this.options.roomId, id, ["view", "post", "changeTopic", "moderate", "properties", "grant"]) // In effect, reset the user's permissions to the default.
            },
            'resolveFromIds' : Resolver.resolveGroupsFromIds,
            'resolveFromNames' : Resolver.resolveGroupsFromNames
        });


        /* Censor Lists */
        fimApi.getCensorLists({
            'roomId' : this.options.roomId,
            'includeWords' : 0,
        }, {
            'each' : function(listData) {
                var listStatus;

                if (listData.status) listStatus = listData.status;
                else if (listData.listType === 'white') listStatus = 'block';
                else if (listData.listType === 'black') listStatus = 'unblock';
                else throw 'Bad logic.';

                $('#editRoomForm [name=censorLists]').append(
                    $('<label>').attr('class', 'btn btn-secondary m-1').text(listData.listName).prepend(
                        $('<input>').attr({
                            'type' : 'checkbox',
                            'name' : 'censorLists',
                            'value' : listData.listId
                        }).attr(listData.listOptions & 2 ? { 'disabled' : 'disabled' } : {})
                            .attr(listStatus == 'block' ? { 'checked' : 'checked' } : {})
                    )
                );
            }
        });


        /*
         * Prepopulate Data if Editing a Room
         */
        if (this.options.roomId != null) {
            fimApi.getRooms({
                'id' : this.options.roomId
            }, {'each' : function(roomData) {
                // User Permissions
                var allowedUsersArray = [], moderatorsArray = [];

                jQuery.each(roomData.userPermissions, function(userId, privs) {
                    if (privs.moderate && privs.properties && privs.grant)
                        moderatorsArray.push(userId);

                    else if (privs.post)
                        allowedUsersArray.push(userId);
                });

                allowedUsersList.displayEntries(allowedUsersArray);
                moderatorsList.displayEntries(moderatorsArray);

                // Group Permissions
                var allowedGroupsArray = [];
                jQuery.each(roomData.groupPermissions, function(userId, privs) {
                    if (privs.post) // Are the 1, 2, and 4 bits all present?
                        allowedGroupsArray.push(userId);
                });
                allowedGroupsList.displayEntries(allowedGroupsArray);

                // Default Permissions
                if ('view' in roomData.defaultPermissions) // If all users are currently allowed, check the box (which triggers other stuff above).
                    $('#editRoomForm input[name=allowViewing]').prop('checked', true);
                if ('post' in roomData.defaultPermissions) // If all users are currently allowed, check the box (which triggers other stuff above).
                    $('#editRoomForm input[name=allowPosting]').prop('checked', true);

                // Name
                $('#editRoomForm input[name=name]').val(roomData.name);

                // Options
                $('#editRoomForm input[name=official]').prop('checked', roomData.official);
                $('#editRoomForm input[name=hidden]').prop('checked', roomData.hidden);

                // Parental Data
                jQuery.each(roomData.parentalFlags, function(index, flag) {
                    $('#editRoomForm input[name=parentalFlags][value=' + flag + ']').prop('checked', true);
                });
                $('#editRoomForm select[name=parentalAge] option[value=' + roomData.parentalAge + ']').attr('selected', 'selected');
            }});
        }


        /* Submit */
        $("#editRoomForm").submit(function() {
            // Parse Default Permissions
            defaultPermissions = [];
            if ($('#editRoomForm input[name=allowViewing]').is(':checked'))
                defaultPermissions.push("view");
            if ($('#editRoomForm input[name=allowPosting]').is(':checked'))
                defaultPermissions.push("post");

            var censorLists = {};
            jQuery.each($('#editRoomForm input[name=censorLists]:checked').map(function(){
                return $(this).attr('value');
            }).get(), function(index, value) {
                censorLists[value] = 1;
            });

            jQuery.each($('#editRoomForm input[name=censorLists]:not(:checked)').map(function(){
                return $(this).attr('value');
            }).get(), function(index, value) {
                censorLists[value] = 0;
            });

            // Do Edit
            fimApi.editRoom(_this.options.roomId, action, {
                "name" : $('#editRoomForm input[name=name]').val(),
                "defaultPermissions" : defaultPermissions,
                "parentalAge" : $('#editRoomForm select[name=parentalAge] option:selected').val(),
                "parentalFlags" : $('#editRoomForm input[name=parentalFlags]:checked').map(function(){
                    return $(this).attr('value');
                }).get(),
                "censorLists" : censorLists,
                "official" : $("#editRoomForm input[name=official]").is(":checked"),
                "hidden" : $("#editRoomForm input[name=hidden]").is(":checked")
            }, {
                end : function(room) {
                    // Parse Allowed Users
                    if (action === 'create') {
                        allowedUsersList.getList().forEach(function(user) {
                            fimApi.createRoomPermissionUser(_this.options.roomId, user, ["view", "post"]);
                        });
                        moderatorsList.getList().forEach(function(user) {
                            fimApi.createRoomPermissionUser(_this.options.roomId, id, ["view", "post", "changeTopic", "moderate", "properties", "grant"]);
                        });
                        allowedGroupsList.getList().forEach(function(group) {
                            fimApi.createRoomPermissionGroup(_this.options.roomId, group, ["view", "post"]);
                        });
                    }

                    window.location.hash = '#';

                    dia.full({
                        content : $l('editRoom.finish.' + action + "Title") + '<br /><br /><form action="#room=' + room.id + '"><div class="input-group"><input autofocus type="text" value="' + currentLocation + '#room=' + room.id + '" name="url" class="form-control"  /><span class="input-group-btn"><button class="btn btn-primary">Go!</button></span></div></form>',
                        title : $l('editRoom.finish.' + action + "Message"),
                        buttons : {
                            Open : function() {
                                window.location.hash = '#room=' + room.id;
                            },
                            Okay : function() {}
                        }
                    });
                }
            });

            return false; // Don't submit the form.
        });

        return false;
    }
};


/*** END Create Room ***/





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