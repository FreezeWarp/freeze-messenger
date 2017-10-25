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
            dia.info('You are now logged in as ' + activeLogin.userData.name + '. However, you are not allowed to view and have been banned by an administrator.', 'Logged In'); // dia.error(window.phrases.errorBanned);
        else if (!activeLogin.userData.permissions.post)
            dia.info('You are now logged in as ' + activeLogin.userData.name + '. However, you are not allowed to post and have been silenced by an administrator. You may still view rooms which allow you access.', 'Logged In');
        else
            dia.info('You are now logged in as ' + activeLogin.userData.name + '.', 'Logged In');
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





/*** START Insert Docs ***/
/* Note: This dialogue will calculate only "expected" errors before submit. The rest of the errors, though we could calculate, will be left up to the server to tell us. */

popup.prototype.insertDoc = function() {
    $('#modal-insertDoc').modal();


    var fileName = '',
        fileSize = 0,
        fileContent = '',
        fileParts = [],
        filePartsLast = '',
        md5hash = '';


    /* File Upload Info */
    if (!('fileUploads' in serverSettings)) {
        $('#insertDocUpload').html('Disabled.');
    }
    else {
        serverSettings.fileUploads.extensionChangesReverse = {};

        jQuery.each(serverSettings.fileUploads.extensionChanges, function(index, extension) {
            if (!(extension in serverSettings.fileUploads.extensionChangesReverse))
                serverSettings.fileUploads.extensionChangesReverse[extension] = [extension];

            serverSettings.fileUploads.extensionChangesReverse[extension].push(extension);
        });

        jQuery.each(serverSettings.fileUploads.allowedExtensions, function(index, extension) {
            var maxFileSize = serverSettings.fileUploads.sizeLimits[extension],
                fileContainer = serverSettings.fileUploads.fileContainers[extension],
                fileExtensions = serverSettings.fileUploads.extensionChangesReverse[extension];

            $('table#fileUploadInfo tbody').append('<tr><td>' + (fileExtensions ? fileExtensions.join(', ') : extension) + '</td><td>' + $l('fileContainers.' + fileContainer) + '</td><td>' + $.formatFileSize(maxFileSize, $l('byteUnits')) + '</td></tr>');
        });


        /* File Upload Form */
        if (typeof FileReader !== 'function') {
            $('#uploadFileForm').html($l('uploadErrors.notSupported'));
        }
        else {
            $('#uploadFileForm').submit(function () {
                filesList = $('input#fileUpload[type="file"]').prop('files');

                if (filesList.length == 0) {
                    dia.error('Please select a file to upload.');
                    return false;
                }
                else {
                    $('#fileUpload').fileupload();
                    $('#fileUpload').fileupload('add', {
                        files: filesList,
                        url: directory + 'api/editFile.php?' + $.param({
                            "_action" : "create",
                            "uploadMethod" : "put",
                            "dataEncode" : "binary",
                            "roomId" : window.roomId,
                            "fileName" : filesList.item(0).name,
                            "access_token" : window.sessionHash,
                            "parentalAge" : $('form#uploadFileForm select[name=parentalAge] option:selected').val(),
                            "parentalFlags" : $('form#uploadFileForm input[name=parentalFlags]:checked').map(function(){
                                return $(this).attr('value');
                            }).get(),
                        }),
                        type: 'PUT',
                        multipart: false,
                    });
                    $('#fileUpload').fileupload('destroy');

                    $('#modal-insertDoc').modal('hide');

                    return false;
                }
            });

            /* Previewer for Files */
            $('#fileUpload').bind('change', function() {
                var reader = new FileReader(),
                    reader2 = new FileReader();

                console.log('FileReader triggered.');
                $('#imageUploadSubmitButton').attr('disabled', 'disabled').button({ disabled: true }); // Redisable the submit button if it has been enabled prior.

                if (this.files.length === 0) dia.error('No files selected!');
                else if (this.files.length > 1) dia.error('Too many files selected!');
                else {
                    console.log('FileReader started.');

                    // File Information
                    fileName = this.files[0].name,
                        fileSize = this.files[0].size,
                        fileContent = '',
                        fileParts = fileName.split('.'),
                        filePartsLast = fileParts[fileParts.length - 1];

                    // If there are two identical file extensions (e.g. jpg and jpeg), we only process the primary one. This converts a secondary extension to a primary.
                    if (filePartsLast in serverSettings.fileUploads.extensionChanges) {
                        filePartsLast = serverSettings.fileUploads.extensionChanges[filePartsLast];
                    }

                    if ($.inArray(filePartsLast, $.toArray(serverSettings.fileUploads.allowedExtensions)) === -1) {
                        $('#uploadFileFormPreview').html($l('uploadErrors.badExtPersonal'));
                    }
                    else if ((fileSize) > serverSettings.fileUploads.sizeLimits[filePartsLast]) {
                        $('#uploadFileFormPreview').html($l('uploadErrors.tooLargePersonal', {
                            'fileSize' : serverSettings.fileUploads.sizeLimits[filePartsLast]
                        }));
                    }
                    else {
                        $('#uploadFileFormPreview').html('Loading Preview...');

                        reader.readAsBinaryString(this.files[0]);
                        reader.onloadend = function() {
                            fileContent = window.btoa(reader.result);
                        };

                        reader2.readAsDataURL(this.files[0]);
                        reader2.onloadend = function() {
                            $('#uploadFileFormPreview').html(fim_messagePreview(serverSettings.fileUploads.fileContainers[filePartsLast], this.result));
                        };

                        $('#imageUploadSubmitButton').removeAttr('disabled').button({ disabled: false });
                    }
                }
            });
        }
    }

    return false;
};

/*** END Insert Docs ***/




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
                        fim_buildUsernameTag($('<span>'), user.id, fim_getUsernameDeferred(user.id), true),
                        $('<span>').text('(' + user.messageCount + ')')
                    )
                );

                i++;
            });
        },
        end : function() {
            windowDraw();
        }
    });
};

/*** END Stats ***/




/*** START User Settings ***/

popup.prototype.settings = {
    init : function() { /* TODO: Handle reset properly, and refresh the entire application when settings are changed. It used to make some sense not to, but not any more. */
        // TODO: move
        var idMap = {
            disableFormatting : 16, disableImage : 32, disableVideos : 64, reversePostOrder : 1024,
            showAvatars : 2048, audioDing : 8192, disableFx : 262144, disableRightClick : 1048576,
            usTime : 16777216, twelveHourTime : 33554432, webkitNotifications : 536870912
        };

        fimApi.getUsers({
            'info' : ['self', 'profile'],
            'id' : userId
        }, {'each' : function(active) { console.log(active);

            /**************************
             ***** Server Settings ****
             **************************/

            var options = active.options,
                defaultHighlightHashPre = [],
                defaultHighlightHash = {r:0, g:0, b:0},
                defaultColourHashPre = [],
                defaultColourHash = {r:0, g:0, b:0};

            var ignoreList = new autoEntry($("#changeSettingsForm [name=ignoreListContainer]"), {
                'name' : 'ignoreList',
                'default' : active.ignoredUsers,
                'list' : 'users',
                'resolveFromIds' : Resolver.resolveUsersFromIds,
                'resolveFromNames' : Resolver.resolveUsersFromNames
            });

            var friendsList = new autoEntry($("#changeSettingsForm [name=friendsListContainer]"), {
                'name' : 'friendsList',
                'default' : active.friendedUsers,
                'list' : 'users',
                'resolveFromIds' : Resolver.resolveUsersFromIds,
                'resolveFromNames' : Resolver.resolveUsersFromNames
            });

            var watchRooms = new autoEntry($("#changeSettingsForm [name=watchRoomsContainer]"), {
                'name' : 'watchRooms',
                'default' : active.watchRooms,
                'list' : 'rooms',
                'resolveFromIds' : Resolver.resolveRoomsFromIds,
                'resolveFromNames' : Resolver.resolveRoomsFromNames
            });


            $('#fontPreview').attr('style', active.messageFormatting);

            defaultFormatting = active.messageFormatting.split(';');
            defaultFormattingObj = {};
            jQuery.each(defaultFormatting, function(index, value) {
                pair = value.split(':');
                defaultFormattingObj[pair[0]] = pair[1];
            });


            /* Update Default Forum Values Based on Server Settings */
            // User Profile
            if (active.profile) $('#profile').val(active.profile);


            // Default Formatting -- Bold
            if ('font-weight' in defaultFormattingObj && defaultFormattingObj['font-weight'] == 'bold') {
                $('#fontPreview').css('font-weight', 'bold');
                $('#defaultBold').attr('checked', 'checked');
            }
            $('#defaultBold').change(function() {
                if ($('#defaultBold').is(':checked')) $('#fontPreview').css('font-weight', 'bold');
                else $('#fontPreview').css('font-weight', 'normal');
            });


            // Default Formatting -- Italics
            if ('font-style' in defaultFormattingObj && defaultFormattingObj['font-style'] == 'italic') {
                $('#defaultItalics').attr('checked', 'checked');
            }
            $('#defaultItalics').change(function() {
                if ($('#defaultItalics').is(':checked')) $('#fontPreview').css('font-style', 'italic');
                else $('#fontPreview').css('font-style', 'normal');
            });


            // Default Formatting -- Fontface
            if (window.serverSettings.formatting.fonts) {
                // Populate Box
                jQuery.each(window.serverSettings.formatting.fonts, function(font, fontFamily) {
                    $('#defaultFace').append($('<option>').attr({
                        value: font,
                        style: "font-family: " + fontFamily,
                    }).attr((defaultFormattingObj['font-family'] == fontFamily) ? {
                        selected : 'selected'
                    } : {}).text(font));
                });

                // onChange
                $('#defaultFace').change(function() {
                    $('#fontPreview').css('fontFamily', $('#defaultFace > option:selected').attr('data-font'));
                });
            }
            else {
                $('#defaultFace').hide();
            }


            // Colour Chooser -- Colour
            if (window.serverSettings.formatting.color) {
                if ('color' in defaultFormattingObj) {
                    $('#defaultColour').css('background-color', defaultFormattingObj['color']);

                    defaultColourHashPre = defaultFormattingObj['color'].slice(4, -1).split(',');
                    defaultColourHash = {r : defaultColourHashPre[0], g : defaultColourHashPre[1], b : defaultColourHashPre[2] }
                }

                $('#defaultColour').ColorPicker({
                    color: defaultColourHash,
                    onShow: function (colpkr) { $(colpkr).fadeIn(500); }, // Fadein
                    onHide: function (colpkr) { $(colpkr).fadeOut(500); }, // Fadeout
                    onChange: function(hsb, hex, rgb) {
                        defaultColour = rgb['r'] + ',' + rgb['g'] + ',' + rgb['b'];

                        $('#defaultColour').css('background-color', 'rgb(' + defaultColour + ')');
                        $('#fontPreview').css('color', 'rgb(' + defaultColour + ')');
                    }
                });
            }
            else {
                $('#defaultColour').hide();
            }


            // Colour Chooser -- Highlight
            if (window.serverSettings.formatting.highlight) {
                if ('background-color' in defaultFormattingObj) {
                    $('#defaultHighlight').css('background-color', defaultFormattingObj['background-color']);

                    defaultHighlightHashPre = defaultFormattingObj['background-color'].slice(4, -1).split(',');
                    defaultHighlightHash = {r : defaultHighlightHashPre[0], g : defaultHighlightHashPre[1], b : defaultHighlightHashPre[2] }
                }

                $('#defaultHighlight').ColorPicker({
                    color: defaultHighlightHash,
                    onShow: function (colpkr) { $(colpkr).fadeIn(500); }, // Fadein
                    onHide: function (colpkr) { $(colpkr).fadeOut(500); }, // Fadeout
                    onChange: function(hsb, hex, rgb) {
                        defaultHighlight = rgb['r'] + ',' + rgb['g'] + ',' + rgb['b'];

                        $('#defaultHighlight').css('background-color', 'rgb(' + defaultHighlight + ')');
                        $('#fontPreview').css('background-color', 'rgb(' + defaultHighlight + ')');
                    }
                });
            }
            else {
                $('#defaultHighlight').hide();
            }


            // Default Room Value
            fimApi.getRooms({'roomIds' : [active.defaultRoomId]}, {'each' : function(roomData) { $('#defaultRoom').val(roomData.name).attr('data-id', roomData.id); }});


            // Parental Ages/Flags
            jQuery.each(active.parentalFlags, function(key, flag) {
                $('input[name=parentalFlags][value=' + flag + ']').attr('checked', true);
            });


            // Default Privacy Level
            $('input[name=privacyLevel][value="' + active.privacyLevel + '"]').prop('checked', true);
        }});




        /**************************
         * WebPro-Specific Values *
         **************************/

        // Only Show the Profile Setting if Using Vanilla Logins
        if (window.serverSettings.branding.forumType !== 'vanilla') $('#settings5profile').hide(0);

        // Autocomplete Rooms and Users
        $("#defaultRoom").autocompleteHelper('rooms');


        /* Theme */
        // Default
        if (window.webproDisplay.theme) $('#theme > option[value="' + window.webproDisplay.theme + '"]').attr('selected', 'selected');

        // onChange
        $('#theme').change(function() {
            $('#stylesjQ').attr('href', 'client/css/' + this.value + '/jquery-ui-1.8.16.custom.css');
            $('#stylesVIM').attr('href', 'client/css/' + this.value + '/fim.css');

            $.cookie('webpro_theme', this.value, { expires : 14 });
            window.webproDisplay.theme = this.value;

            return false;
        });


        /* Theme Fontsize */
        // Default
        if (window.webproDisplay.fontSize)
            $('#fontsize > option[value="' + window.webproDisplay.fontSize + '"]').attr('selected', 'selected');

        // onChange
        $('#fontsize').change(function() {
            $('body').css('font-size',this.value + 'em');

            $.cookie('webpro_fontsize', this.value, { expires : 14 });
            window.webproDisplay.fontSize = this.value;

            windowResize();

            return false;
        });


        /* Volume */
        // Default
        if (snd.volume) $('#audioVolume').attr('value', snd.volume * 100);

        // onChange
        $('#audioVolume').change(function() {
            $.cookie('webpro_audioVolume', this.value, { expires : 14 });
            snd.volume = this.value / 100;

            return false;
        });


        /* Various Settings -- Update onChange, Refresh Posts */
        // Defaults
        if (settings.showAvatars) $('#showAvatars').attr('checked', 'checked');
        if (settings.reversePostOrder) $('#reversePostOrder').attr('checked', 'checked');
        if (settings.disableFormatting) $('#disableFormatting').attr('checked', 'checked');
        if (settings.disableVideo) $('#disableVideo').attr('checked', 'checked');
        if (settings.disableImage) $('#disableImage').attr('checked', 'checked');

        // onChange -- refresh messages when needed
        $('#showAvatars, #reversePostOrder, #disableFormatting, #disableVideo, #disableImage').change(function() {
            var localId = $(this).attr('id');

            if ($(this).is(':checked') && !settings[localId]) {
                settings[localId] = true;
                $('#messageList').html('');
                $.cookie('webpro_settings', Number($.cookie('webpro_settings')) + idMap[localId], { expires : 14 });
            }
            else if (!$(this).is(':checked') && settings[localId]) {
                settings[localId] = false;
                $('#messageList').html('');
                $.cookie('webpro_settings', Number($.cookie('webpro_settings')) - idMap[localId], { expires : 14 });
            }

            // TODO: test
            standard.changeRoom(window.roomId);
        });


        /* Various Settings */
        // Defaults
        if (settings.audioDing) $('#audioDing').attr('checked', 'checked');
        if (settings.disableFx) $('#disableFx').attr('checked', 'checked');
        if (settings.disableRightClick) $('#disableRightClick').attr('checked', 'checked');
        if (settings.webkitNotifications) $('#webkitNotifications').attr('checked', 'checked');

        // onChange
        $('#audioDing, #disableFx, #webkitNotifications, #disableRightClick').change(function() {
            var localId = $(this).attr('id');

            if ($(this).is(':checked') && !settings[localId]) {
                settings[localId] = true;
                $.cookie('webpro_settings', Number($.cookie('webpro_settings')) + idMap[localId], { expires : 14 });

                // Disable jQuery Effects
                if (localId === 'disableFx') {
                    jQuery.fx.off = true;
                }

                // Notifications
                if (localId === 'webkitNotifications') {
                    if (notify.webkitNotifySupported()) {
                        notify.webkitNotifyRequest();  // Ask client permission for webkit notifications
                    }
                    else {
                        dia.error("Notifications are not supported on your browser.");
                    }
                }
            }

            else if (!$(this).is(':checked') && settings[localId]) {
                settings[localId] = false;
                $.cookie('webpro_settings', Number($.cookie('webpro_settings')) - idMap[localId], { expires : 14 });

                // Reenable jQuery Effects
                if (localId === 'disableFx') {
                    jQuery.fx.off = false;
                }
            }
        });



        /**************************
         ******* Submit Form ******
         **************************/

        $("#changeSettingsForm").submit(function() {
            var defaultFormatting = [],
                parentalFlags = [];

            if ($('#defaultBold').is(':checked')) defaultFormatting.push("bold");
            if ($('#defaultItalics').is(':checked')) defaultFormatting.push("italic");

            fimApi.editUserOptions('edit', {
                "defaultFontface" : $('#defaultFace option:selected').val(),
                "defaultFormatting" : defaultFormatting,
                "defaultHighlight" : ($('#fontPreview').css('background-color') === 'rgba(0, 0, 0, 0)' ? null : $('#fontPreview').css('background-color').slice(4,-1)),
                "defaultColor" : $('#fontPreview').css('color').slice(4,-1),
                "defaultRoomId" : $('#defaultRoom').attr('data-id'),
                "watchRooms" : $('#watchRooms').val().split(','),
                "ignoreList" : $('#ignoreList').val().split(','),
                "friendsList" : $('#friendsList').val().split(','),
                "profile" : $('#profile').val(),
                "parentalAge" : $('form#changeSettingsForm select[name=parentalAge] option:selected').val(),
                "parentalFlags" : $('form#changeSettingsForm input[name=parentalFlags]:checked').map(function(){
                    return $(this).attr('value');
                }).get(),
                "privacyLevel" : $('input[name=privacyLevel]:radio:checked').val()
            }, {
                'each' : function(value) {
                    console.log(value);
                },
                'end' : function() {
                    dia.info('Your settings have been updated successfully.');

                    $("#changeSettingsDialogue").empty().remove(); // Housecleaning, needed if we want the colorpicker to work in another changesettings dialogue.
                    $(".colorpicker").empty().remove(); // Housecleaning, needed if we want the colorpicker to work in another changesettings dialogue.
                },
                'error' : function(errors) {
                    errorsList = [];

                    for (var i = 0; i < errors.responseJSON.editUserOptions.length; i++) {
                        errorsList.push("<li>" + i + ": " + errors.responseJSON.editUserOptions[i].exception.details + "</li>")
                    }
                    dia.error('Some of your settings have been updated. However, the following values were unable to be processed:<ul>' + errorsList.join() + '</ul>')
                }
            });

            window.history.back();

            return false; // Don't submit the form.
        });

        return false;
    }
};

/*** END User Settings ***/






/*** START View My Uploads ***/

popup.prototype.uploads = {
    init : function() {
        fimApi.getFiles({
            'userIds' : [window.userId]
        }, {
            'each': function(active) {
                var parentalFlagsFormatted = [];

                for (var i = 0; i < active.parentalFlags.length; i++) {
                    if (active.parentalFlags[i]) parentalFlagsFormatted.push($l('parentalFlags.' + active.parentalFlags[i])); // Yes, this is a very weird line.
                }

                $('#viewUploadsBody').append(
                    $('<tr>').append(
                        $('<td align="center">').append(
                            $('<img style="max-width: 200px; max-height: 200px;" />').attr('src', directory + 'file.php?' + $.param({
                                'sha256hash': active.sha256hash,
                                'thumbnailWidth': 200,
                                'thumbnailHeight': 200
                            }))
                        ).append('<br />').append($('<span>').text(active.fileName))
                    ).append(
                        $('<td align="center">').text(active.fileSizeFormatted)
                    ).append(
                        $('<td align="center">').text($l('parentalAges.' + active.parentalAge))
                            .append('<br />')
                            .append(parentalFlagsFormatted.join(', '))
                    ).append(
                        $('<td align="center">').append(
                            $('<button>').attr('class', 'btn btn-primary').click(function() {
                                fimApi.editUserOptions('edit', {
                                    'avatar': serverSettings.installUrl + "file.php?sha256hash=" + active.sha256hash + '&thumbnailWidth=200&thumbnailHeight=200',
                                }, {
                                    'end' : function(response) {
                                        if ("avatar" in response) {
                                            dia.error(response.avatar.string);
                                        }
                                        else {
                                            dia.info('Your avatar has been updated. It will not appear in your old messages.');
                                        }
                                    }
                                });
                            }).text($l("uploads.setToAvatar")).prepend($('<i class="fa fa-picture-o" aria-hidden="true"></i> '))
                        )
                    )
                );
            },
            'end' : function() {
                $("#viewUploadsBody img").load(windowDraw);
            }
        });
    }
}

/*** END View My Uploads ***/






/*** START Create Room ***/

popup.prototype.editRoom = {
    options : {
        roomId : 0
    },

    setOptions : function(options) {
        for (i in options)
            this.options[i] = options[i];
    },

    init : function(options) {
        var _this = this;
        this.setOptions(options);

        if (this.options.roomId != 0)
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
                if (action === 'edit') fimApi.editRoomPermissionUser(_this.options.roomId, id, ["post", "moderate", "properties", "grant"])
            },
            'onRemove' : function(id) {
                if (action === 'edit') fimApi.editRoomPermissionUser(_this.options.roomId, id, ["post"]) // todo: just remove moderate privs
            },
            'resolveFromIds' : Resolver.resolveUsersFromIds,
            'resolveFromNames' : Resolver.resolveUsersFromNames
        });

        allowedUsersList = new autoEntry($("#allowedUsersContainer"), {
            'name' : 'allowedUsers',
            'list' : 'users',
            'onAdd' : function(id) {
                if (action === 'edit') fimApi.editRoomPermissionUser(_this.options.roomId, id, ["post"])
            },
            'onRemove' : function(id) {
                if (action === 'edit') fimApi.editRoomPermissionUser(_this.options.roomId, id, [""])
            },
            'resolveFromIds' : Resolver.resolveUsersFromIds,
            'resolveFromNames' : Resolver.resolveUsersFromNames
        });

        allowedGroupsList = new autoEntry($("#allowedGroupsContainer"), {
            'name' : 'allowedGroups',
            'list' : 'groups',
            'onAdd' : function(id) {
                if (action === 'edit') fimApi.editRoomPermissionGroup(_this.options.roomId, id, ["post"])
            },
            'onRemove' : function(id) {
                if (action === 'edit') fimApi.editRoomPermissionGroup(_this.options.roomId, id, [""])
            },
            'resolveFromIds' : Resolver.resolveGroupsFromIds,
            'resolveFromNames' : Resolver.resolveGroupsFromNames
        });


        /* Censor Lists */
        fimApi.getCensorLists({
            'roomId' : this.options.roomId != 0 ? this.options.roomId : null,
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
        if (this.options.roomId != 0) {
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
                var allowedGroupsArray = []
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
            //console.log("allowed users", allowedUsersList, allowedUsersList.getList());

            var combinedUserPermissions = {},
                combinedGroupPermissions = {};

            // Parse Allowed Users
            if (action === 'create') {
                allowedUsersList.getList().forEach(function(user) {
                    combinedUserPermissions["+" + user] = ['view', 'post'];
                });
                moderatorsList.getList().forEach(function(user) {
                    combinedUserPermissions["+" + user] = ['view', 'post', 'moderate'];
                });
                allowedGroupsList.getList().forEach(function(group) {
                    combinedGroupPermissions["+" + group] = ['post'];
                });
            }

            // Parse Default Permissions
            defaultPermissions = [];
            if ($('#editRoomForm input[name=allowViewing]').is(':checked'))
                defaultPermissions.push("view");
            if ($('#editRoomForm input[name=allowPosting]').is(':checked'))
                defaultPermissions.push("post");

            // Do Edit
            fimApi.editRoom(_this.options.roomId, action, {
                "name" : $('#editRoomForm input[name=name]').val(),
                "defaultPermissions" : defaultPermissions,
                "userPermissions" : combinedUserPermissions,
                "groupPermissions" : combinedGroupPermissions,
                "parentalAge" : $('#editRoomForm select[name=parentalAge] option:selected').val(),
                "parentalFlags" : $('#editRoomForm input[name=parentalFlags]:checked').map(function(){
                    return $(this).attr('value');
                }).get(),
                "censorLists" : $('#editRoomForm input[name=censorLists]:checked').map(function(){
                    return $(this).attr('value');
                }).get(),
                "official" : $("#editRoomForm input[name=official]").is(":checked"),
                "hidden" : $("#editRoomForm input[name=hidden]").is(":checked")
            }, {
                end : function(room) {
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

                roomData.push(fim_buildRoomNameTag($('<span>'), room.id, fim_getRoomNameDeferred(room.id)));
            });

            $('#onlineUsers').append($('<tr>').append(
                $('<td>').append(
                    fim_buildUsernameTag($('<span>'), user.id, fim_getUsernameDeferred(user.id), true)
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




/*** START Kick Manager ***/

popup.prototype.kicks = {
    dateOptions : {year : "numeric", month : "numeric", day : "numeric", hour: "numeric", minute: "numeric", second: "numeric"},

    init : function() {
        fimApi.getKicks(params, {
            'each' : function(kick) {
                jQuery.each(kick.kicks, function(kickId, kickData) {
                    console.log(kickData);
                    $('#kickedUsers').append(
                        $('<tr>').append(
                            $('<td>').append(
                                $('<span class="userName userNameTable">').attr({'data-userId' : kick.userId, 'style' : kick.userNameFormat}).text(kick.userName)
                            )
                        ).append(
                            $('<td>').append(
                                $('<span class="userName userNameTable">').attr({'data-userId' : kickData.kickerId, 'style' : kickData.kickerNameFormat}).text(kickData.kickerName)
                            )
                        ).append(
                            $('<td>').append(
                                $('<span class="roomName roomNameTable">').attr({'data-roomId' : kickData.roomId}).text(kickData.roomName)
                            )
                        ).append(
                            $('<td>').text(fim_dateFormat(kickData.set, dateOptions))
                        ).append(
                            $('<td>').text(fim_dateFormat(kickData.expires, dateOptions))
                        ).append(
                            $('<td>').append(
                                $('<button>').click(function() {
                                    standard.unkick(kick.userId, kickData.roomId)
                                }).text('Unkick')
                            )
                        )
                    );
                });
            }
        });
    }
};

/*** END Kick Manager ***/




/*** START Kick ***/

popup.prototype.kick = function() {
    $('#modal-kick').modal();

    $('#userName').autocompleteHelper('users');
    $('#roomNameKick').autocompleteHelper('rooms');

    $("#kickUserForm").submit(function() {
        var userName = $('#kickUserForm > #userName').val();
        var userId = $("#kickUserForm > #userName").attr('data-id');
        var roomName = $('#kickUserForm > #roomNameKick').val();
        var roomId = $("#kickUserForm > #roomNameKick").attr('data-id');
        var length = Math.floor(Number($('#kickUserForm > #time').val() * Number($('#kickUserForm > #interval > option:selected').attr('value'))));

        var userIdDeferred = true;
        var roomIdDeferred = true;

        if (roomName && !roomId) {
            userIdDeferred = $.when(Resolver.resolveUsersFromNames([userName]).then(function(pairs) {
                userId = pairs[userName].id;
            }));
        }

        if (roomName && !roomId) {
            roomIdDeferred = $.when(Resolver.resolveRoomsFromNames([roomName]).then(function(pairs) {
                roomId = pairs[roomName].id;
            }));
        }

        $.when(userIdDeferred, roomIdDeferred).then(function() {
            standard.kick(userId, roomId, length);
        });

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
            console.log('search user', $(this).attr('data-id'));
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
            'archive' : 1,
            'page' : _this.options.page
        }, {
            'reverseEach' : (_this.options.firstMessage ? true : false),
            'end' : function(messages) {
                $('#active-view-archive table.messageTable > tbody').html('');
                $('#active-view-archive button[name=archivePrev]').prop('disabled', false);

                this.messageData = {};

                jQuery.each(messages, function(index, messageData) {
                    $('#active-view-archive table.messageTable > tbody').append(fim_messageFormat(messageData, 'table').append(
                        $('<td>').append(
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



/* TODO: Create a seperate call? */
popup.prototype.exportArchive = function() {
    dia.full({
        id : 'exportDia',
        content : '<form method="post" action="#" onsubmit="return false;" id="exportDiaForm">How would you like to export the data?<br /><br /><table align="center"><tr><td>Format</td><td><select id="exportFormat"><option value="bbcodetable">BBCode Table</option><option value="csv">CSV List (Excel, etc.)</option></select></td></tr><tr><td colspan="2" align="center"><button type="submit">Export</button></td></tr></table></form>',
        width: 600
    });


    $('#exportDiaForm').submit(function() {
        switch ($('#exportFormat option:selected').val()) {
            case 'bbcodetable':
                var exportData = '';

                $('#archiveMessageList').find('tr').each(function() {
                    var exportUser = $(this).find('td:eq(0) .userNameTable').text(),
                        exportTime = $(this).find('td:eq(1)').text(),
                        exportMessage = $(this).find('td:eq(2)').text();

                    for (i in [0,2]) {
                        switch (i) {
                            case 0: var exportItem = exportUser; break;
                            case 2: var exportItem = exportMessage; break;
                        }

                        var el = $(this).find('td:eq(' + i + ') > span'),
                            colour = el.css('color'),
                            highlight = el.css('backgroundColor'),
                            font = el.css('fontFamily'),
                            bold = (el.css('fontWeight') == 'bold' ? true : false),
                            underline = (el.css('textDecoration') == 'underline' ? true : false),
                            strikethrough = (el.css('textDecoration') == 'line-through' ? true : false);

                        if (colour || highlight || font) exportUser = '[span="' + (colour ? 'color: ' + colour + ';' : '') + (highlight ? 'background-color: ' + highlight + ';' : '') + (font ? 'font: ' + font + ';' : '') + '"]' + exportUser + '[/span]';
                        if (bold) { exportUser = '[b]' + exportUser + '[/b]'; }
                        if (underline) { exportUser = '[u]' + exportUser + '[/u]'; }
                        if (strikethrough) { exportUser = '[s]' + exportUser + '[/s]'; }

                        switch (i) {
                            case 1: exportUser = exportItem; break;
                            case 3: exportMessage = exportItem; break;
                        }
                    }

                    exportData += exportUser + "|" + exportTime + "|" + exportMessage + "\n";
                });

                exportData = "<textarea style=\"width: 100%; height: 1000px;\">[table=head]User|Time|Message\n" + exportData + "[/table]</textarea>";
                break;

            case 'csv':
                var exportData = '';

                $('#archiveMessageList').find('tr').each(function() {
                    var exportUser = $(this).find('td:nth-child(1) .userNameTable').text(),
                        exportTime = $(this).find('td:nth-child(2)').text(),
                        exportMessage = $(this).find('td:nth-child(3)').text();

                    exportData += "'" + exportUser + "', '" + exportTime + "', '" + exportMessage + "'\n";
                });

                exportData = "<textarea style=\"width: 100%; height: 600px;\">" + exportData + "</textarea>";
                break;
        }

        dia.full({
            id : 'exportTable',
            content : exportData,
            width : '1000'
        });

        return false;
    });
};
/*********************************************************
 ************************* END ***************************
 ************** Repeat-Action Popup Methods **************
 *********************************************************/