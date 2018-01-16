declare var fimApi: any;
declare var $: any;
declare var directory: any;
declare var dia: any;
declare var fim_buildUsernameTag : any;
declare var fim_getUsernameDeferred : any;
declare var fim_setHashParameter : any;
declare var fim_getHandlebarsPhrases : any;
declare var windowDraw : any;
declare var Handlebars : any;
declare var popup : any;

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
        $('#editRoomForm [name=censorLists]').html('');
        $('#moderatorsContainer').html('');
        $('#allowedUsersContainer').html('');
        $('#allowedGroupsContainer').html('');

        
        let action;
        if (this.options.roomId != null)
            action = 'edit';
        else
            action = 'create';


        /* Autocomplete Users and Groups */
        let moderatorsList = new autoEntry($("#moderatorsContainer"), {
            'name' : 'moderators',
            'list' : 'users',
            'onAdd' : (id) => {
                if (action === 'edit') fimApi.createRoomPermissionUser(this.options.roomId, id, ["view", "post", "moderate", "properties", "grant"])
            },
            'onRemove' : (id) => {
                if (action === 'edit') fimApi.deleteRoomPermissionUser(this.options.roomId, id, ["moderate", "properties", "grant"])
            },
            'resolveFromIds' : Resolver.resolveUsersFromIds,
            'resolveFromNames' : Resolver.resolveUsersFromNames
        });

        let allowedUsersList = new autoEntry($("#allowedUsersContainer"), {
            'name' : 'allowedUsers',
            'list' : 'users',
            'onAdd' : (id) => {
                if (action === 'edit') fimApi.createRoomPermissionUser(this.options.roomId, id, ["view", "post"])
            },
            'onRemove' : (id) => {
                if (action === 'edit') fimApi.deleteRoomPermissionUser(this.options.roomId, id, ["view", "post", "changeTopic", "moderate", "properties", "grant"]) // In effect, reset the user's permissions to the default.
            },
            'resolveFromIds' : Resolver.resolveUsersFromIds,
            'resolveFromNames' : Resolver.resolveUsersFromNames
        });

        let allowedGroupsList = new autoEntry($("#allowedGroupsContainer"), {
            'name' : 'allowedGroups',
            'list' : 'groups',
            'onAdd' : (id) => {
                if (action === 'edit') fimApi.createRoomPermissionGroup(this.options.roomId, id, ["view", "post"])
            },
            'onRemove' : (id) => {
                if (action === 'edit') fimApi.deleteRoomPermissionGroup(this.options.roomId, id, ["view", "post", "changeTopic", "moderate", "properties", "grant"]) // In effect, reset the user's permissions to the default.
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
                let listStatus;

                if (listData.status)
                    listStatus = listData.status;
                else if (listData.listType === 'white')
                    listStatus = 'block';
                else if (listData.listType === 'black')
                    listStatus = 'unblock';
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
                    let allowedUsersArray = [], moderatorsArray = [];

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
        $("#editRoomForm").submit(() => {
            // Parse Default Permissions
            let defaultPermissions = [];
            if ($('#editRoomForm input[name=allowViewing]').is(':checked'))
                defaultPermissions.push("view");
            if ($('#editRoomForm input[name=allowPosting]').is(':checked'))
                defaultPermissions.push("post");

            let censorLists = {};
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
            fimApi.editRoom(this.options.roomId, action, {
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
                        allowedUsersList.getList().forEach((user) => {
                            fimApi.createRoomPermissionUser(this.options.roomId, user, ["view", "post"]);
                        });
                        moderatorsList.getList().forEach((user) => {
                            fimApi.createRoomPermissionUser(this.options.roomId, user, ["view", "post", "changeTopic", "moderate", "properties", "grant"]);
                        });
                        allowedGroupsList.getList().forEach((group) => {
                            fimApi.createRoomPermissionGroup(this.options.roomId, group, ["view", "post"]);
                        });
                    }

                    window.location.hash = '#';

                    dia.full({
                        content : $l('editRoom.finish.' + action + "Title") + '<br /><br /><form action="#room=' + room.id + '"><div class="input-group"><input autofocus type="text" value="' + window.currentLocation + '#room=' + room.id + '" name="url" class="form-control"  /><span class="input-group-btn"><button class="btn btn-primary">Go!</button></span></div></form>',
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