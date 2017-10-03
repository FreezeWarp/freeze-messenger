/*********************************************************
 ************************ START **************************
 ************** Repeat-Action Popup Methods **************
 *********************************************************/

popup = {
    /*** START Login ***/

    login : function() {
        function login_success(activeLogin) {
            $('#loginDialogue').dialog('close'); // Close any open login forms.

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

        dia.full({
            content : $t('login'),
            title : 'Login',
            id : 'loginDialogue',
            width : 600,
            oF : function() {
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
            },
            cF : function() {
                if (!window.userId) {
                    standard.initialLogin({
                        username : '',
                        password : '',
                        rememberMe : false,
                        finish : login_success,
                        error : login_fail
                    });
                }
            }
        });
    },

    /*** END Login ***/




    /*** START Room Select ***/

    selectRoom : function() {
        dia.full({
            content : $t('selectRoom'),
            title : 'Room List',
            id : 'roomListDialogue',
            width: 1000,
            oF : function() {
                fimApi.getRooms({}, {
                    'each' : function(roomData) {
                        $('#roomTableHtml').append(
                            $('<tr>').attr('id', 'room' + roomData.id).append(
                                $('<td>').append(
                                    $('<a>').attr('href','#room=' + roomData.id).text(roomData.name)
                                ),
                                $('<td>').text(roomData.topic),
                                $('<td>').append(
                                    (roomData.permissions.properties
                                        ? $('<button>').attr('data-roomId', roomData.id).attr('class', 'editRoomMulti standard')
                                        : ''
                                    ), (roomData.permissions.properties
                                        ? $('<button>').attr('data-roomId', roomData.id).attr('class', 'deleteRoomMulti standard')
                                        : ''
                                    ), $('<button>').attr('data-roomId', roomData.id).attr('class', 'archiveMulti standard')
                                    , $('<button>').attr('data-roomId', roomData.id).attr('class', 'favRoomMulti standard')
                                    , $('<button>').attr('data-roomId', roomData.id).attr('class', 'watchRoomMulti standard')
                                )
                            )
                        );
                    },
                    'end' : function() {
                        $('button.editRoomMulti, button.favRoomMulti, button.archiveMulti, button.deleteRoomMulti').unbind('click'); // Prevent the below from being binded multiple times.

                        /* Favorites */
                        var roomLists = {
                            'favRoom' : window.activeLogin.userData.favRooms,
                            'watchRoom' : window.activeLogin.userData.watchRooms
                        };

                        for (i in roomLists) {
                            $('#roomTableHtml button.' + i + 'Multi').each(function() {
                                var roomId = $(this).attr('data-roomId');

                                $(this).button({
                                    icons : {primary : (i == 'favRoom' ? 'ui-icon-star' : 'ui-icon-search')},
                                }).bind('click', function() {
                                    if (roomLists[i].indexOf(roomId) === -1) {
                                        dia.info("You will now be notified of new messages made in this room.");
                                        roomLists[i].push(roomId);
                                        fimApi[i](roomId);
                                        $(this).addClass("ui-state-highlight");
                                    }

                                    else {
                                        roomLists[i].remove(roomId);
                                        fimApi["un" + i](roomId);
                                        $(this).removeClass("ui-state-highlight");
                                    }
                                });

                                if (roomLists[i].indexOf(roomId) !== -1) {
                                    $(this).addClass("ui-state-highlight");
                                }
                            });
                        }

                        $('button.editRoomMulti').button({icons : {primary : 'ui-icon-gear'}}).bind('click', function() {
                            popup.editRoom($(this).attr('data-roomId'));
                        });

                        $('button.archiveMulti').button({icons : {primary : 'ui-icon-note'}}).bind('click', function() {
                            popup.archive({roomId : $(this).attr('data-roomId')});
                        });

                        $('button.deleteRoomMulti').button({icons : {primary : 'ui-icon-trash'}}).bind('click', function() {
                            if (dia.confirm("Are you sure you want to delete this room?")) {
                                standard.deleteRoom($(this).attr('data-roomId'));
                            }
                        });

                        windowDraw();
                    }
                });
            }
        });
    },

    /*** END Room List ***/




    /*** START Insert Docs ***/
    /* Note: This dialogue will calculate only "expected" errors before submit. The rest of the errors, though we could calculate, will be left up to the server to tell us. */

    insertDoc : function(preSelect) {
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
                    serverSettings.fileUploads.extensionChangesReverse = new Object();

                    for (var i = 0; i < serverSettings.fileUploads.extensionChanges.length; i++) {
                        var extension = serverSettings.fileUploads.extensionChanges[i];

                        if (!(extension in serverSettings.fileUploads.extensionChangesReverse))
                            serverSettings.fileUploads.extensionChangesReverse[extension] = [extension];

                        serverSettings.fileUploads.extensionChangesReverse[extension].push(i);
                    }

                    for (var i = 0; i < serverSettings.fileUploads.allowedExtensions.length; i++) {
                        var maxFileSize = serverSettings.fileUploads.sizeLimits[serverSettings.fileUploads.allowedExtensions[i]],
                            fileContainer = serverSettings.fileUploads.fileContainers[serverSettings.fileUploads.allowedExtensions[i]],
                            fileExtensions = serverSettings.fileUploads.extensionChangesReverse[serverSettings.fileUploads.allowedExtensions[i]];

                        $('table#fileUploadInfo tbody').append('<tr><td>' + (fileExtensions ? fileExtensions.join(', ') : serverSettings.fileUploads.allowedExtensions[i]) + '</td><td>' + $l('fileContainers.' + fileContainer) + '</td><td>' + $.formatFileSize(maxFileSize, $l('byteUnits')) + '</td></tr>');
                    }


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
    },

    /*** END Insert Docs ***/




    /*** START Stats ***/

    viewStats : function() {
        var number = 10;

        dia.full({
            content : $t('viewStats'),
            title : 'Room Stats',
            id : 'roomStatsDialogue',
            width : 600,
            oF : function() {
                for (i = 1; i <= number; i += 1) {
                    $('table#viewStats > tbody').append('<tr><th>' + i + '</th></tr>');
                }

                fimApi.getStats({
                    'roomIds' : [window.roomId]
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
                                    $('<span>').attr({
                                        'class' : 'userName',
                                        'data-userId' : user.id,
                                        'style' : user.nameFormat
                                    }).text(user.name)
                                    .append($('<span>').text(' ('))
                                    .append($('<span>').text(user.messageCount))
                                    .append($('<span>').text(')'))
                                )
                            );

                            i++;
                        });
                    },
                    'end' : function() {
                        windowDraw();
                    }
                });
            }
        });
    },

    /*** END Stats ***/




    /*** START User Settings ***/

    userSettings : function() { /* TODO: Handle reset properly, and refresh the entire application when settings are changed. It used to make some sense not to, but not any more. */
        dia.full({
            content : $t('userSettingsForm'),
            id : 'changeSettingsDialogue',
            tabs : true,
            width : 1000,
            cF : function() {
                $('.colorpicker').empty().remove();

                return false;
            },
            oF : function() {
                // TODO: move
                var idMap = {
                    disableFormatting : 16, disableImage : 32, disableVideos : 64, reversePostOrder : 1024,
                    showAvatars : 2048, audioDing : 8192, disableFx : 262144, disableRightClick : 1048576,
                    usTime : 16777216, twelveHourTime : 33554432, webkitNotifications : 536870912
                };

                fimApi.getUsers({
                    'info' : ['self', 'profile'],
                    'userIds' : [userId]
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
                            $('#parentalAge').append(
                                $('<option>').attr('value', age).text($l('parentalAges.' + age))
                            );
                        });

                        // Parental Age Default
                        $('select#parentalAge option[value=' + active.parentalAge + ']').attr('selected', 'selected');

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
                        "parentalAge" : $('#parentalAge option:selected').val(),
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

                    return false; // Don't submit the form.
                });

                return false;
            }
        });

        return false;
    },

    /*** END User Settings ***/






    /*** START View My Uploads ***/

    viewUploads : function() {
        dia.full({
            content : $t('viewUploads'),
            width : 1200,
            title : 'View My Uploads',
            position : 'top',
            oF : function() {
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
        });
    },

    /*** END View My Uploads ***/






    /*** START Create Room ***/

    editRoom : function(roomId) {
        if (roomId)
            var action = 'edit';
        else
            var action = 'create';

        dia.full({
            content : $t('editRoomForm'),
            id : 'editRoomDialogue',
            width : 1000,
            tabs : true,
            oF : function() {
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
                    jQuery.each(serverSettings.parentalControls.parentalAges, function(i, age) {
                        $('#parentalAge').append('<option value="' + age + '">' + $l('parentalAges.' + age) + '</option>');
                    });

                    jQuery.each(serverSettings.parentalControls.parentalFlags, function(i, flag) {
                        $('#parentalFlagsList').append('<label><input type="checkbox" value="true" name="flag' + flag + '" data-cat="parentalFlag" data-name="' + flag + '" />' +  $l('parentalFlags.' + flag) + '</label><br />');
                    });
                }


                /* Censor Lists */
                fimApi.getCensorLists({
                    'roomId' : roomId ? roomId : 0,
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
                                'value' : 'true',

                            })
                            .attr(listData.listOptions & 2 ? { 'disabled' : 'disabled' } : {})
                            .attr(listStatus == 'block' ? { 'checked' : 'checked' } : {})
                        ), $('<label>').text(listData.listName), $('<br>'));
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
        });

        return false;
    },

    /*** END Create Room ***/




    /*** START Private Rooms ***/

    privateRoom : function() {
        dia.full({
            content : $t('privateRoom'),
            title : 'Enter Private Room',
            id : 'privateRoomDialogue',
            width : 500,
            oF : function() {
                $('#userName').autocompleteHelper('users');

                $("#privateRoomForm").submit(function() {
                    var userName = $("#privateRoomForm > #userName").val();
                    var userId = $("#privateRoomForm > #userName").attr('data-id');

                    whenUserIdAvailable = function(userId) {
                        standard.changeRoom("p" + [window.userId, userId].join(','), true);
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

                return false;
            }
        });

        return false;
    },

    /*** END Private Rooms ***/




    /*** START Online ***/

    online : function() {
        dia.full({
            content : $t('online'),
            title : 'Active Users',
            id : 'onlineDialogue',
            position : 'top',
            width : 600,
            oF : fimApi.getActiveUsers({}, {
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
                            $('<span>').attr('class', 'userName').attr('data-userId', user.userData.id).attr('style', user.userData.nameFormat).text(user.userData.name)
                        )
                    ).append($('<td>').append(roomData)));
                },
                'end' : function() {
                    contextMenuParseUser('#onlineUsers');
                    windowDraw();
                }
            }),
            cF : function() {
                fimApi.getActiveUsers({}, {
                    'close' : true
                })
            }
        });
    },

    /*** END Online ***/




    /*** START Kick Manager ***/

    manageKicks : function(params) {
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
    },

    /*** END Kick Manager ***/




    /*** START Kick ***/

    kick : function() {
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
    },

    /*** END Kick ***/




    /*** START Help ***/

    help : function() {
        dia.full({
            content : $t('help'),
            title : 'helpDialogue',
            width : 1000,
            position : 'top',
            tabs : true
        });

        return false;
    },

    /*** END Help ***/




    /*** START Archive ***/

    archive : function(options) {
        dia.full({
            content : $t('archive'),
            title : 'Archive',
            id : 'archiveDialogue',
            position : 'top',
            width : 1000,
            oF : function() {
                $('#searchUser').autocompleteHelper('users')
            }
        });

        standard.archive.init({
            roomId: options.roomId,
            firstMessage: options.firstMessage
        });

        standard.archive.retrieve();
    },

    /*** END Archive ***/



    /* TODO: Create a seperate call? */
    exportArchive : function() {
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
    },




    /*** START Copyright ***/

    copyright : function() {
        dia.full({
            content : $t('copyright'),
            title : 'copyrightDialogue',
            width : 800,
            tabs : true
        });

        return false;
    },

    /*** END Copyright ***/



    /*** START Editbox ***/
    editBox : function(name, list, allowDefaults, removeCallback, addCallback) { // TODO: Move into plugins?
        allowDefaults = allowDefaults || false;
        removeCallback = removeCallback || function(value) { return false };
        addCallback = addCallback || function(value) { return false };

        dia.full({
            content : $t('editBox'),
            title : 'Edit ' + $l('editBoxNames.' + name),
            width : 400,
            oF : function() {
                // General
                function remove(element) {
                    removeCallback($(element).text());

                    $(element).remove();
                }

                function drawButtons() {
                    $("#editBoxList button").button({
                        icons: {primary : 'ui-icon-closethick'}
                    }).bind('click', function() {
                        remove($(this).parent());
                    });
                }


                // Populate
                $(list).each(function(key, value) {
                    $('#editBoxList').append($t('editBoxItem', {"value" : value}));
                    drawButtons();
                });


                // Search
                $('#editBoxSearchValue').focus().keyup(function(e) {
                    var val = $('#editBoxSearchValue').val();

                    $('#editBoxList li').show();

                    if (val !== '') {
                        $("#editBoxList li").not(":contains('" + val + "')").hide();
                    }
                });

                $('#editBoxSearch').submit(function() { return false; });


                // Add
                $('#editBoxAdd').submit(function() {
                    value = $('#editBoxAddValue').val();
                    $('#editBoxAddValue').val('');

                    $('#editBoxList').append($t('editBoxItem', {"value" : value}));
                    drawButtons();

                    addCallback(value);

                    return false;
                });
            }
        });
    }

    /*** End Editbox ***/
};

/*********************************************************
 ************************* END ***************************
 ************** Repeat-Action Popup Methods **************
 *********************************************************/