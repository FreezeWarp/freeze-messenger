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
    
    action : null,

    moderatorsList : null,
    allowedUsersList : null,
    allowedGroupsList : null,

    setRoomId : function(roomId) {
        this.options.roomId = roomId ? roomId : null;

        if (this.options.roomId != null)
            this.action = 'edit';
        else
            this.action = 'create';
    },

    init : function() {
    },

    render : function() {
    },

    afterRender : function() {
        /* Autocomplete Users and Groups */
        this.moderatorsList = new autoEntry($("#moderatorsContainer"), {
            'name': 'moderators',
            'list': 'users',
            'onAdd': (id) => {
                if (this.action === 'edit')
                    fimApi.createRoomPermissionUser(this.options.roomId, id, ["view", "post", "moderate", "properties", "grant"])
            },
            'onRemove': (id) => {
                if (this.action === 'edit')
                    fimApi.deleteRoomPermissionUser(this.options.roomId, id, ["moderate", "properties", "grant"])
            },
            'resolveFromIds': Resolver.resolveUsersFromIds,
            'resolveFromNames': Resolver.resolveUsersFromNames
        });//

        this.allowedUsersList = new autoEntry($("#allowedUsersContainer"), {
            'name': 'allowedUsers',
            'list': 'users',
            'onAdd': (id) => {
                if (this.action === 'edit')
                    fimApi.createRoomPermissionUser(this.options.roomId, id, ["view", "post"])
            },
            'onRemove': (id) => {
                if (this.action === 'edit')
                    fimApi.deleteRoomPermissionUser(this.options.roomId, id, ["view", "post", "changeTopic", "moderate", "properties", "grant"]) // In effect, reset the user's permissions to the default.
            },
            'resolveFromIds': Resolver.resolveUsersFromIds,
            'resolveFromNames': Resolver.resolveUsersFromNames
        });

        this.allowedGroupsList = new autoEntry($("#allowedGroupsContainer"), {
            'name': 'allowedGroups',
            'list': 'groups',
            'onAdd': (id) => {
                if (this.action === 'edit')
                    fimApi.createRoomPermissionGroup(this.options.roomId, id, ["view", "post"])
            },
            'onRemove': (id) => {
                if (this.action === 'edit')
                    fimApi.deleteRoomPermissionGroup(this.options.roomId, id, ["view", "post", "changeTopic", "moderate", "properties", "grant"]) // In effect, reset the user's permissions to the default.
            },
            'resolveFromIds': Resolver.resolveGroupsFromIds,
            'resolveFromNames': Resolver.resolveGroupsFromNames
        });


        /* Censor Lists */
        let censorListTemplate = Handlebars.compile($('#view-editRoom-censorList').html());
        fimApi.getCensorLists({
            'roomId': this.options.roomId,
            'includeWords': 0,
        }, {
            'each': (listData) => {
                listData.enabled = (listData.status === 'block' || listData.type === 'white');

                $('#editRoomForm [name=censorLists]').append(
                    censorListTemplate(fim_getHandlebarsPhrases({listData : listData}))
                );
            }
        });


        /* Submit */
        $("#editRoomForm").off('submit').on('submit', () => {
            fimApi.editRoom(this.options.roomId, this.action, $('#editRoomForm').serializeJSON(), {
                end : (room) => {
                    // Parse Allowed Users
                    if (this.action === 'create') {
                        this.allowedUsersList.getList().forEach((user) => {
                            fimApi.createRoomPermissionUser(room.id, user, ["view", "post"]);
                        });
                        this.moderatorsList.getList().forEach((user) => {
                            fimApi.createRoomPermissionUser(room.id, user, ["view", "post", "changeTopic", "moderate", "properties", "grant"]);
                        });
                        this.allowedGroupsList.getList().forEach((group) => {
                            fimApi.createRoomPermissionGroup(room.id, group, ["view", "post"]);
                        });
                    }

                    window.location.hash = '#';

                    dia.full({
                        content : window.phrases.editRoom.finish[this.action + "Title"] + '<br /><br /><form action="#room=' + room.id + '"><div class="input-group"><input autofocus type="text" value="' + window.currentLocation + '#room=' + room.id + '" name="url" class="form-control"  /><span class="input-group-btn"><button class="btn btn-primary">Go!</button></span></div></form>',
                        title : window.phrases.editRoom.finish[this.action + "Message"],
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
    },

    retrieve : function(render) {
        /*
         * Prepopulate Data if Editing a Room
         */
        if (this.options.roomId != null) {
            fimApi.getRooms({
                'id' : this.options.roomId
            }, {'end' : (data) => {
                let roomData = data['room ' + this.options.roomId];

                render({roomData : roomData});
                this.afterRender();


                // User Permissions
                let allowedUsersArray = [], moderatorsArray = [];

                jQuery.each(roomData.userPermissions, function(userId, privs) {
                    if (privs.moderate && privs.properties && privs.grant)
                        moderatorsArray.push(userId);

                    else if (privs.post)
                        allowedUsersArray.push(userId);
                });

                this.allowedUsersList.displayEntries(allowedUsersArray);
                this.moderatorsList.displayEntries(moderatorsArray);


                // Group Permissions
                let allowedGroupsArray = [];
                jQuery.each(roomData.groupPermissions, function(userId, privs) {
                    if (privs.post) // Are the 1, 2, and 4 bits all present?
                        allowedGroupsArray.push(userId);
                });
                this.allowedGroupsList.displayEntries(allowedGroupsArray);


                // Parental Data
                jQuery.each(roomData.parentalFlags, function(index, flag) {
                    $('#editRoomForm input[name=parentalFlags][value=' + flag + ']').prop('checked', true);
                });
                $('#editRoomForm select[name=parentalAge] option[value=' + roomData.parentalAge + ']').attr('selected', 'selected');
            }});
        }
        else {
            render();
            this.afterRender();
        }

        return false;
    }
};