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

    $('#modal-login').modal();

    // Submission
    $("#loginForm").submit(function() {
        var loginForm = $('#loginForm');
        standard.initialLogin({
            username : $('#userName', loginForm).val(),
            password : $('#password', loginForm).val(),
            rememberMe : $('#rememberme', loginForm).is('checked'),
            finish : login_success,
            error : login_fail
        });

        return false;
    });

    // Close
    $('#modal-login').on('hidden.bs.modal', function () {
        if (!window.userId) {
            standard.initialLogin({
                username : '',
                password : '',
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

popup.prototype.insertDoc = function(preSelect) {
    var selectTab;

    switch (preSelect) {
        case 'video': selectTab = 2; break;
        case 'image': selectTab = 1; break;
        case 'link': default: selectTab = 0; break;
    }

    dia.full({
        content : $t('insertDoc'),
        id : 'insertDoc',
        width: 1000,
        position: 'top',
        tabs : true,
        oF : function() {
            /* Define Variables (these are updated onChange and used onSubmit) */
            var fileName = '',
                fileSize = 0,
                fileContent = '',
                fileParts = [],
                filePartsLast = '',
                md5hash = '';

            /* Form Stuff */
            $('#fileUpload, #urlUpload').unbind('change'); // Prevent duplicate binds.
            $('#uploadFileForm, #uploadUrlForm, #linkForm, #uploadYoutubeForm').unbind('submit'); // Disable default submit action.
            $('#imageUploadSubmitButton').attr('disabled', 'disabled').button({ disabled: true }); // Disable submit button until conditions are fulfilled.


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
                    /* Parental Controls */
                    if (!serverSettings.parentalControls.parentalEnabled) { // Hide if Subsystem is Disabled
                        $('#insertDocParentalAge, #insertDocParentalFlags').remove();
                    }
                    else {
                        for (var i = 0; i < serverSettings.parentalControls.parentalAges.length; i++) {
                            $('#parentalAge').append('<option value="' + serverSettings.parentalControls.parentalAges[i] + '">' + $l('parentalAges.' + serverSettings.parentalControls.parentalAges[i]) + '</option>');
                        }

                        for (var i = 0; i < serverSettings.parentalControls.parentalFlags.length; i++) {
                            $('#parentalFlagsList').append('<br /><label><input type="checkbox" value="true" name="flag' + serverSettings.parentalControls.parentalFlags[i] + '" data-cat="parentalFlag" data-name="' + serverSettings.parentalControls.parentalFlags[i] + '" />' + $l('parentalFlags.' + serverSettings.parentalControls.parentalFlags[i]) + '</label>');
                        }
                    }

                    $('#imageUploadSubmitButton').click(function () {
                        filesList = $('input#fileUpload[type="file"]').prop('files');
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
                                "parentalAge" : $('#parentalAge option:selected').val(),
                                "parentalFlags" : $('input[data-cat=parentalFlag]:checked').map(function(){
                                    return $(this).attr('data-name');
                                }).get(),
                            }),
                            type: 'PUT',
                            multipart: false,
                        });
//                                .success(function (result, textStatus, jqXHR) {})
//                                .error(function (jqXHR, textStatus, errorThrown) {})
//                                .complete(function (result, textStatus, jqXHR) {});
                        $('#fileUpload').fileupload('destroy');
                        $('#insertDoc').dialog('close');
                        return false;
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
                                    md5hash = md5.hex_md5(fileContent);
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


            /* Upload URL */
            $('#uploadUrlForm').bind('submit', function() {
                var linkName = $('#urlUpload').val();

                if (linkName.length > 0 && linkName !== 'http://') {
                    standard.sendMessage(linkName, 0, 'image');
                    $('#insertDoc').dialog('close');
                }
                else {
                    dia.error($l('uploadErrors.imageEmpty'));
                }

                return false;
            });


            /* Previewer for URLs */
            $('#urlUpload').bind('change', function() {
                var linkName = $('#urlUpload').val();

                if (linkName.length > 0 && linkName !== 'http://') {
                    $('#uploadUrlFormPreview').html('<img src="' + linkName + '" alt="" style="max-width: 200px; max-height: 250px; height: auto;" />');
                }
            });


            /* Upload Link */
            $('#linkForm').bind('submit', function() {
                var linkUrl = $('#linkUrl').val(),
                    linkMail = $('#linkEmail').val();

                if (linkUrl.length === 0 && linkMail.length === 0) { dia.error($l('uploadErrors.linkEmpty')); } // No value for either.
                else if (linkUrl.length > 0) { standard.sendMessage(linkUrl, 0, 'url'); } // Link specified for URL.
                else if (linkMail.length > 0) { standard.sendMessage(linkMail, 0, 'email'); } // Link specified for mail, not URL.
                else { dia.error('Logic Error'); } // Eh, why not?

                $('#insertDoc').dialog('close');

                return false;
            });


            /* Upload Youtube */
            $('#uploadYoutubeForm').bind('submit', function() {
                linkVideo = $('#youtubeUpload');

                if (linkVideo.search(/^http\:\/\/(www\.|)youtube\.com\/(.*?)?v=(.+?)(&|)(.*?)$/) === 0) { dia.error($l('uploadErrors.videoEmpty')); } // Bad format
                else { standard.sendMessage(linkVideo, 0, 'source'); }

                $('#insertDoc').dialog('close');

                return false;
            });

            return false;
        },
        selectTab : selectTab
    });

    return false;
};

/*** END Insert Docs ***/




/*** START Stats ***/

popup.prototype.viewStats = function() {
    var number = 10;

    $('#modal-stats').modal();

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

            var ignoreList = new autoEntry($("#ignoreListContainer"), {
                'name' : 'ignoreList',
                'default' : active.ignoredUsers,
                'list' : 'users',
                'resolveFromIds' : Resolver.resolveUsersFromIds,
                'resolveFromNames' : Resolver.resolveUsersFromNames
            });

            var friendsList = new autoEntry($("#friendsListContainer"), {
                'name' : 'friendsList',
                'default' : active.friendedUsers,
                'list' : 'users',
                'resolveFromIds' : Resolver.resolveUsersFromIds,
                'resolveFromNames' : Resolver.resolveUsersFromNames
            });

            var watchRooms = new autoEntry($("#watchRoomsContainer"), {
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
            if (window.serverSettings.parentalControls.parentalEnabled) {
                // Parental Age Values
                jQuery.each(window.serverSettings.parentalControls.parentalAges, function(key, age) {
                    $('#changeSettingsForm select[name=parentalAge]').append(
                        $('<option>').attr('value', age).text($l('parentalAges.' + age))
                    );
                });

                // Parental Age Default
                $('#changeSettingsForm select[name=parentalAge] option[value=' + active.parentalAge + ']').attr('selected', 'selected');

                // Parental Flags Values
                jQuery.each(window.serverSettings.parentalControls.parentalFlags, function(key, flag) {
                    $('#parentalFlagsList').append($('<br />'),
                        $('<label>').append(
                            $('<input>').attr({
                                type : "checkbox",
                                value : "true",
                                name : "flag" + flag,
                                'data-cat' : "parentalFlag",
                                'data-name' : flag
                            }),
                            $('<span>').text($l('parentalFlags.' + flag))
                        )
                    );
                });

                // Parental Flags Default
                jQuery.each(active.parentalFlags, function(key, flag) {
                    $('input[data-cat=parentalFlag][data-name=' + flag + ']').attr('checked', true);
                });
            }
            else {
                $('#settings5parentalAge, #settings5parentalFlags').hide();
            }


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

            $('input[data-cat=parentalFlag]:checked').each(function(a, b) {
                parentalFlags.push($(b).attr('data-name'));
            });

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
                "parentalAge" : $('#changeSettingsForm select[name=parentalAge] option:selected').val(),
                "parentalFlags" : parentalFlags,
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
                            $('<button>').click(function() {
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
                            }).text('Set to Avatar')
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

    init : function(options) {
        for (i in options)
            this.options[i] = options[i];

        if (this.options.roomId)
            var action = 'edit';
        else
            var action = 'create';

        /* Events */
        $('#allowPosting').change(function() {
            if ($(this).is(':checked')) {
                $('#allowedUsersBridge, #allowedGroupsBridge').attr('disabled', 'disabled');
                $('#allowedUsersBridge, #allowedGroupsBridge').next().attr('disabled', 'disabled');
            }
            else {
                $('#allowedUsersBridge, #allowedGroupsBridge').removeAttr('disabled');
                $('#allowedUsersBridge, #allowedGroupsBridge').next().removeAttr('disabled');
            }
        });


        /* Autocomplete Users and Groups */
        moderatorsList = new autoEntry($("#moderatorsContainer"), {
            'name' : 'moderators',
            'list' : 'users',
            'onAdd' : function(id) {
                if (action === 'edit') fimApi.editRoomPermissionUser(roomId, id, ["post", "moderate", "properties", "grant"])
            },
            'onRemove' : function(id) {
                if (action === 'edit') fimApi.editRoomPermissionUser(roomId, id, ["post"]) // todo: just remove moderate privs
            },
            'resolveFromIds' : Resolver.resolveUsersFromIds,
            'resolveFromNames' : Resolver.resolveUsersFromNames
        });

        allowedUsersList = new autoEntry($("#allowedUsersContainer"), {
            'name' : 'allowedUsers',
            'list' : 'users',
            'onAdd' : function(id) {
                if (action === 'edit') fimApi.editRoomPermissionUser(roomId, id, ["post"])
            },
            'onRemove' : function(id) {
                if (action === 'edit') fimApi.editRoomPermissionUser(roomId, id, [])
            },
            'resolveFromIds' : Resolver.resolveUsersFromIds,
            'resolveFromNames' : Resolver.resolveUsersFromNames
        });

        allowedGroupsList = new autoEntry($("#allowedGroupsContainer"), {
            'name' : 'allowedGroups',
            'list' : 'groups',
            'onAdd' : function(id) {
                if (action === 'edit') fimApi.editRoomPermissionGroup(roomId, id, ["post"])
            },
            'onRemove' : function(id) {
                if (action === 'edit') fimApi.editRoomPermissionGroup(roomId, id, [])
            },
            'resolveFromIds' : Resolver.resolveGroupsFromIds,
            'resolveFromNames' : Resolver.resolveGroupsFromNames
        });


        /* Parental Controls */
        if (!serverSettings.parentalControls.parentalEnabled) { // Hide if Subsystem is Disabled
            $('#editRoom1ParentalAge, #editRoom1ParentalFlags').remove();
        }
        else {
                jQuery.each(window.serverSettings.parentalControls.parentalAges, function(i, age) {
                    $('#editRoomForm select[name=parentalAge]').append($('<option>').attr('value', age).text($l('parentalAges.' + age)));
                });

                jQuery.each(window.serverSettings.parentalControls.parentalFlags, function(i, flag) {
                    $('#editRoomForm div[name=parentalFlagsList]').append('<label><input type="checkbox" value="true" name="flag' + flag + '" data-cat="parentalFlag" data-name="' + flag + '" />' +  $l('parentalFlags.' + flag) + '</label><br />');
                });
        }


        /* Censor Lists */
        fimApi.getCensorLists({
            'roomId' : roomId ? roomId : null,
            'includeWords' : 0,
        }, {
            'each' : function(listData) {
                var listStatus;

                if (listData.status) listStatus = listData.status;
                else if (listData.listType === 'white') listStatus = 'block';
                else if (listData.listType === 'black') listStatus = 'unblock';
                else throw 'Bad logic.';

                $('#censorLists').append($('<label>').append(
                    $('<input>').attr({
                        'type' : 'checkbox',
                        'name' : 'list' + listData.listId,
                        'data-listId' : listData.listId,
                        'data-checkType' : 'list',
                        'value' : 'true'

                    })
                        .attr(listData.listOptions & 2 ? { 'disabled' : 'disabled' } : {})
                        .attr(listStatus == 'block' ? { 'checked' : 'checked' } : {})
                , $('<span>').text(listData.listName), $('<br>')));
            }
        });


        /* Prepopulate Data if Editing a Room */
        if (roomId) {
            fimApi.getRooms({
                'id' : roomId
            }, {'each' : function(roomData) {
                // User Permissions
                var allowedUsersArray = [], moderatorsArray = [];
                jQuery.each(roomData.userPermissions, function(userId, privs) {
                    if (privs.moderate && privs.properties && privs.grant) // Are all bits up to 8 present?
                        moderatorsArray.push(userId);

                    if (privs.post) // Are the 1, 2, and 4 bits all present?
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
                    $('#allowViewing').prop('checked', true);
                if ('post' in roomData.defaultPermissions) // If all users are currently allowed, check the box (which triggers other stuff above).
                    $('#allowPosting').prop('checked', true);

                // Name
                $('#name').val(roomData.name);

                // Options
                $('#allowOfficial').prop('checked', roomData.official);
                $('#allowHidden').prop('checked', roomData.hidden);

                // Parental Data
                jQuery.each(roomData.parentalFlags, function(index, flag) {
                    $('input[data-cat=parentalFlag][data-name=' + flag + ']').prop('checked', true);
                });
                $('select#parentalAge option[value=' + roomData.parentalAge + ']').attr('selected', 'selected');
            }});
        }


        /* Submit */
        $("#editRoomForm").submit(function() {console.log("allowed users", allowedUsersList, allowedUsersList.getList());
            var name = $('#name').val(),
                censor = {},
                parentalAge = $('#parentalAge option:selected').val(),
                parentalFlags = [],
                combinedUserPermissions = {},
                combinedGroupPermissions = {};

            // Parse Alloewd Users
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

            // Parse Censor Lists
            $('input[data-checkType="list"]').each(function() {
                censor[$(this).attr('data-listId')] = ($(this).is(':checked') ? 1 : 0);
            });

            // Parse Parental Flags
            $('input[data-cat=parentalFlag]:checked').each(function(a, b) {
                parentalFlags.push($(b).attr('data-name'));
            });

            // Parse Default Permissions
            defaultPermissions = [];
            if ($('#allowViewing').is(':checked'))
                defaultPermissions.push("view");
            if ($('#allowPosting').is(':checked'))
                defaultPermissions.push("post");

            // Do Edit
            fimApi.editRoom(roomId, action, {
                "name" : name,
                "defaultPermissions" : defaultPermissions,
                "userPermissions" : combinedUserPermissions,
                "groupPermissions" : combinedGroupPermissions,
                "parentalAge" : parentalAge,
                "parentalFlags" : parentalFlags,
                "censorLists" : censor,
                "official" : $("#allowOfficial").is(":checked"),
                "hidden" : $("#allowHidden").is(":checked")
            }, {
                end : function(room) {
                    dia.full({
                        content : 'The room has been created at the following URL: <br /><br /><form action="' + currentLocation + '#room=' + room.id + '" method="post"><input type="text" style="width: 300px;" value="' + currentLocation + '#room=' + room.id + '" name="url" /></form>',
                        title : 'Room Created!',
                        id : 'editRoomResultsDialogue',

                        width : 600,
                        buttons : {
                            Open : function() {
                                $('#editRoomResultsDialogue').dialog('close');
                                standard.changeRoom(room.id);

                                return false;
                            },
                            Okay : function() {
                                $('#editRoomResultsDialogue').dialog('close');

                                return false;
                            }
                        }
                    });

                    $("#editRoomDialogue").dialog('close');
                }
            });

            return false; // Don't submit the form.
        });

        return false;
    }
};

/*** END Create Room ***/




/*** START Private Rooms ***/

popup.prototype.privateRoom = function() {
    $('#modal-privateRoom').modal();

    $('#privateRoomForm input[name=userName]').autocompleteHelper('users');

    $("#privateRoomForm").submit(function() {
        console.log("form submitted");
        var userName = $("#privateRoomForm input[name=userName]").val();
        var userId = $("#privateRoomForm input[name=userName]").attr('data-id');

        whenUserIdAvailable = function(userId) {
            window.location.hash = "#room=p" + [window.userId, userId].join(',');
        };

        if (!userId && userName) {
            whenUserIdAvailable(userId);
        }
        else if (!userName) {
            dia.error('Please enter a username.');
        }
        else {
            var userIdDeferred = $.when(Resolver.resolveUsersFromNames([userName]).then(function(pairs) {
                whenUserIdAvailable(pairs[userName].id);
            }));
        }

        return false; // Don't submit the form.
    });
};

/*** END Private Rooms ***/




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

                roomData.push($('<a>').attr('href', '"#room=' + room.id).text(room.name));
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

popup.prototype.manageKicks = function(params) {
    var dateOptions = {year : "numeric", month : "numeric", day : "numeric", hour: "numeric", minute: "numeric", second: "numeric"};

    dia.full({
        content : $t('manageKicks'),
        title : 'Manage/View Kicked Users',
        width : 1000,
        oF : function() {
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
                },
                'end' : windowDraw
            });
        }
    });
};

/*** END Kick Manager ***/




/*** START Kick ***/

popup.prototype.kick = function() {
    dia.full({
        content : $t('kick'),
        title : 'Kick User',
        id : 'kickUserDialogue',
        width : 500,
        oF : function() {
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
        }
    });

    return false;
};

/*** END Kick ***/



/*** START Archive ***/

popup.prototype.archive = {
    options : {
        encrypt : 'base64',
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

        $('#searchText, #searchUser, #archiveNext, #archivePrev, #export, .updateArchiveHere').unbind('change');


        $('#searchText').bind('change', function() {
            _this.update('searchText', $(this).val());
            _this.retrieve();
        });


        $('#searchUser').bind('change', function() {
            _this.update('searchUser', $(this).attr('data-id'));
            _this.retrieve();
        }).autocompleteHelper('users');


        $('#archiveNext').bind('click', function() {
            _this.nextPage();
        });
        $('#archivePrev').bind('click', function() {
            _this.prevPage();
        });


        $('#archiveDialogue > table').on('click', '.updateArchiveHere', function() {
            _this.update('firstMessage', $(this).attr('data-messageId'));
            window.location.hash = '#room=' + _this.options.roomId + '#message=' + $(this).attr('data-messageId');

            _this.retrieve();
        });


        $('#export').bind('click', function() {
            popup.exportArchive();
        });


        _this.retrieve();
    },

    retrieve : function() {
        var _this = this;

        $('#archiveMessageList').html('');
        this.messageData = {};
console.log(_this.options);
        fimApi.getMessages({
            'roomId' : _this.options.roomId,
            'userIds' : [_this.options.searchUser],
            'messageTextSearch' : _this.options.searchText,
            'messageIdStart' : _this.options.firstMessage,
            'archive' : 1,
            'page' : _this.options.page
        }, {
            'reverseEach' : false,
            'each' : function(messageData) {
                $('#archiveMessageList').append(fim_messageFormat(messageData, 'table'));
                _this.messageData[messageData.id] = messageData;
                windowDraw();
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


popup.prototype.room = {
    options : {
        roomId : 0,
        intervalPing : false,
        lastEvent : 0,
        lastMessage : 0
    },

    init : function(options) {
        var _this = this;

        for (i in options)
            _this.options[i] = options[i];

        var intervalPing;


        $('#sendForm').bind('submit', function() {
            var message = $('textarea#messageInput').val();

            if (message.length === 0) { dia.error('Please enter your message.'); }
            else {
                _this.sendMessage(message); // Send the messaage
                $('textarea#messageInput').val(''); // Clear the textbox
            }

            return false;
        });

        $('#messageInput').onEnter(function() {
            $('#sendForm').submit();

            return false;
        });


        fimApi.getRooms({
            'id' : _this.options.roomId,
            'permLevel' : 'view'
        }, {'each' : function(roomData) {
            if (!roomData.permissions.view) { // If we can not view the room
                window.roomId = false; // Set the global roomId false.
                window.location.hash = "#rooms";
                dia.error('You have been restricted access from this room. Please select a new room.');
            }

            else if (!roomData.permissions.post) { // If we can view, but not post
                dia.error('You are not allowed to post in this room. You will be able to view it, though.');
                _this.disableSender();
            }

            else { // If we can both view and post.
                _this.enableSender();
            }


            if (roomData.permissions.view) { // If we can view the room...
                window.roomId = roomData.id;

                $('#roomName').html(roomData.name); // Update the room name.
                $('#topic').html(roomData.topic); // Update the room topic.

                /*** Get Messages ***/
                _this.populateMessages();
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
    },

    eventListener : function() {
        var _this = this;

        var roomSource = new EventSource(directory + 'stream.php?queryId=' + _this.options.roomId + '&streamType=room&lastEvent=' + _this.options.lastEvent + '&lastMessage=' + _this.options.lastMessage + '&access_token=' + window.sessionHash);
        var eventHandler = function(callback) {
            return function(event) {
                if (event.id > _this.options.lastEvent) {
                    _this.options.lastEvent = event.id;
                }

                callback(JSON.parse(event.data));
            }
        };

        roomSource.addEventListener('newMessage', eventHandler(function(active) {
            _this.options.lastMessage = Math.max(_this.options.lastMessage, active.id);

            fim_newMessage(_this.options.roomId, Number(active.id), fim_messageFormat(active, 'list'));
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

                fim_newMessage(_this.options.roomId, Number(active.id), fim_messageFormat(active, 'list'));
            }
        }), false);
    },

    getMessages : function() {
        var _this = this;

        if (_this.options.roomId) {
            fimApi.getMessages({
                'roomId': _this.options.roomId,
            }, {
                autoId : true,
                refresh : (window.requestSettings.serverSentEvents ? 3000 : 3000),
                each: function (messageData) {
                    fim_newMessage(Number(_this.options.roomId), Number(messageData.id), fim_messageFormat(messageData, 'list'));
                },
                end: function () {
                    if (window.requestSettings.serverSentEvents) {
                        fimApi.getMessages(null, {'close' : true});

                        _this.eventListener();
                    }
                }
            });
        }
        else {
            console.log('Not requesting messages; room undefined.');
        }

        return false;
    },

    populateMessages : function() {
        var _this = this;

        $(document).ready(function() {
            // Clear the message list.
            $('#messageList').html('');

            window.requestSettings[_this.options.roomId] = {
                lastMessage : null,
                firstRequest : true
            };
            messageIndex[_this.options.roomId] = [];

            // Get New Messages
            _this.getMessages();

            if (_this.options.intervalPing)
                clearInterval(_this.options.intervalPing);

            fimApi.ping(_this.options.roomId);
            _this.options.intervalPing = window.setInterval(function() {
                fimApi.ping(_this.options.roomId)
            }, 5 * 60 * 1000);

            windowDraw();
            windowDynaLinks();
        });
    },

    sendMessage : function(message, ignoreBlock, flag) {
        var _this = this;

        if (!_this.options.roomId) {
            window.location.hash = '#rooms';
        }
        else {
            ignoreBlock = (ignoreBlock === 1 ? 1 : '');

            fimApi.sendMessage(_this.options.roomId, {
                'ignoreBlock' : ignoreBlock,
                'message' : message,
                'flag' : (flag ? flag : '')
            }, {
                end : function (message) {
                    if ("censor" in message && Object.keys(message.censor).length > 0) {
                        dia.info(Object.values(message.censor).join('<br /><br />'), "Censor warning: " + Object.keys(message.censor).join(', '));
                    }
                },

                exception : function(exception) {
                    if (exception.string === 'confirmCensor')
                        dia.confirm({
                            'text' : exception.details,
                            'true' : function() {
                                _this.sendMessage(message, 1, flag);
                            }
                        }, "Censor Warning");
                    else if (exception.string === 'spaceMessage') {
                        dia.error("Too... many... spaces!")
                    }
                    else { fimApi.getDefaultExceptionHandler()(exception); }
                },

                error : function(request) {
                    if (settings.reversePostOrder)
                        $('#messageList').append('Your message, "' + message + '", could not be sent and will be retried.');
                    else
                        $('#messageList').prepend('Your message, "' + message + '", could not be sent and will be retried.');

                    window.setTimeout(function() { _this.sendMessage(message) }, 5000);

                    return false;
                }
            });
        }
    },

    disableSender : function() {
        $('#messageInput').attr('disabled','disabled'); // Disable input boxes.
        $('#icon_url').button({ disabled : true }); // "
        $('#icon_submit').button({ disabled : true }); // "
        $('#icon_reset').button({ disabled : true }); // "
    },

    enableSender : function() {
        $('#messageInput').removeAttr('disabled'); // Make sure the input is not disabled.
        $('#icon_url').button({ disabled : false }); // "
        $('#icon_submit').button({ disabled : false }); // "
        $('#icon_reset').button({ disabled : false }); // "
    }
};


popup.prototype.rooms = {
    options : {
        searchText : '',
        page : 0
    },

    init : function(options) {
        var _this = this;

        for (i in options)
            _this.options[i] = options[i];

        // TODO: names, not IDs
        $('#permissionLevel, #roomNameSearchText, #roomListNext, #roomListPrev').unbind('change');

        $('#roomNameSearchText').bind('change', function() {
            _this.update('searchText', $(this).val());
            _this.retrieve();
        });

        $('#roomListNext').bind('click', function() {
            _this.nextPage();
        });

        $('#roomListPrev').bind('click', function() {
            _this.prevPage();
        });

        _this.retrieve();
    },

    retrieve : function() {
        var _this = this;

        $('#roomTableHtml').html('');

        fimApi.getRooms({
            page : _this.options.page
        }, {
            'each' : function(roomData) {
                $('#roomTableHtml').append(
                    $('<tr>').attr('id', 'room' + roomData.id).append(
                        $('<td>').append(
                            $('<a>').attr('href','#room=' + roomData.id).text(roomData.name)
                        ),
                        $('<td>').text(roomData.topic),
                        $('<td>').append(
                            $('<div class="btn-toolbar" role="toolbar">').append(
                                (roomData.permissions.properties
                                        ? $('<button>').attr({
                                            'class' : 'btn btn-secondary',
                                            'title' : 'Edit Room'
                                        }).append($('<i class="fa fa-sliders"></i>')).click(function() {
                                            window.location.hash = '#editRoom#room=' + roomData.id;
                                        })
                                        : ''
                                ),

                                // TODO: test
                                (roomData.permissions.properties
                                        ? $('<button>').attr({
                                            'class' : 'btn btn-danger',
                                            'title' : 'Delete Room'
                                        }).append($('<i class="fa fa-trash"></i>')).click(function() {
                                            if (dia.confirm("Are you sure you want to delete this room?")) {
                                                standard.deleteRoom(roomData.id);
                                            }
                                        })
                                        : ''
                                ),

                                $('<button>').attr({
                                    'class' : 'btn btn-secondary',
                                    'title' : 'View History'
                                }).append($('<i class="fa fa-history"></i>')).click(function() {
                                    window.location.hash = '#archive#room=' + roomData.id;
                                }),

                                $('<button>').attr({
                                    'class' : 'btn btn-secondary',
                                    'title' : 'Favourite Room'
                                }).append($('<i class="fa fa-star" style="color: yellow;"></i>')),

                                $('<button>').attr({
                                    'data-toggle' : 'button',
                                    'class' : 'btn btn-secondary' + (window.activeLogin.userData.watchRooms.indexOf(roomData.id) !== -1 ? ' active' : ''),
                                    'aria-pressed' : (window.activeLogin.userData.watchRooms.indexOf(roomData.id) !== -1 ? 'true' : 'false'),
                                    'title' : 'Get Notifications About New Messages in This Room'
                                }).append($('<i class="fa fa-eye"></i>')).click(function() {
                                    if (window.activeLogin.userData.watchRooms.indexOf(roomData.id) === -1) {
                                        dia.info("You will now be notified of new messages made in this room.");
                                        window.activeLogin.userData.watchRooms.push(roomData.id);
                                        fimApi.watchRoom(roomData.id);
                                        $(this).addClass("active");
                                    }

                                    else {
                                        window.activeLogin.userData.watchRooms.remove(roomData.id);
                                        fimApi.unwatchRoom(roomData.id);
                                        $(this).removeClass("active");
                                    }
                                })
                            )
                        )
                    )
                );
            }
        });
    },

    nextPage : function () {
        $('#archivePrev').button({ disabled : false });

        this.options.page++;

        this.retrieve();
    },

    prevPage : function () {
        if (this.options.page !== 0) this.options.page--;

        if (this.options.page <= 0) {
            $('#archivePrev').button({ disabled : true });
        }

        this.retrieve();
    },

    update : function (option, value) {
        this.options[option] = value;
    }
};

/*********************************************************
 ************************* END ***************************
 ************** Repeat-Action Popup Methods **************
 *********************************************************/