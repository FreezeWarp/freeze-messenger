/*********************************************************
 ************************ START **************************
 ************** Repeat-Action Popup Methods **************
 *********************************************************/

popup = {
    /*** START Login ***/

    login : function() {
        function login_success(data) {
            $('#loginDialogue').dialog('close'); // Close any open login forms.

            if (!permissions.view) dia.info('You are now logged in as ' + activeLogin.userData.userName + '. However, you are not allowed to view and have been banned by an administrator.', 'Logged In'); // dia.error(window.phrases.errorBanned);
            else if (!permissions.post) dia.info('You are now logged in as ' + activeLogin.userData.userName + '. However, you are not allowed to post and have been silenced by an administrator. You may still view rooms which allow you access.', 'Logged In'); // dia.error(window.phrases.errorBanned);)
            else dia.info('You are now logged in as ' + activeLogin.userData.userName + '.', 'Logged In');
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
                    standard.login({
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
                    standard.login({
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
                        $('#roomTableHtml').append('<tr id="room' + roomData.id + '"><td><a href="#room=' + roomData.id + '">' + roomData.name + '</a></td><td>' + roomData.topic + '</td><td>' + (roomData.permissions.properties ? '<button data-roomId="' + roomData.id + '" class="editRoomMulti standard"></button><button data-roomId="' + roomData.id + '" class="deleteRoomMulti standard"></button>' : '') + '<button data-roomId="' + roomData.id + '" class="archiveMulti standard"></button><input type="checkbox" data-roomId="' + roomData.id + '" class="favRoomMulti" id="favRoom' + roomData.id + '" /><label for="favRoom' + roomData.id + '" class="standard"></label></td></tr>');
                    },
                    'end' : function() {
                        $('button.editRoomMulti, input[type=checkbox].favRoomMulti, button.archiveMulti, button.deleteRoomMulti').unbind('click'); // Prevent the below from being binded multiple times.

                        /* Favorites */
                        if ('favRooms' in roomLists) {
                            $('input[type=checkbox].favRoomMulti').each(function() {
                                if (roomLists.favRooms.indexOf($(this).attr('data-roomid')) !== -1) $(this).attr('checked', 'checked');

                                $(this).button({icons : {primary : 'ui-icon-star'}, text : false}).bind('change', function() {
                                    if ($(this).is(':checked')) { standard.favRoom($(this).attr('data-roomId')); }
                                    else { standard.unfavRoom($(this).attr('data-roomId')); }
                                });
                            });
                        }
                        else {
                            $('input[type=checkbox].favRoomMulti').remove();
                        }

                        $('button.editRoomMulti').button({icons : {primary : 'ui-icon-gear'}}).bind('click', function() {
                            popup.editRoom($(this).attr('data-roomId'));
                        });

                        $('button.archiveMulti').button({icons : {primary : 'ui-icon-note'}}).bind('click', function() {
                            popup.archive({roomId : $(this).attr('data-roomId')});
                        });

                        $('button.deleteRoomMulti').button({icons : {primary : 'ui-icon-trash'}}).bind('click', function() {
                            standard.deleteRoom($(this).attr('data-roomId'));
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

                    for (i in serverSettings.fileUploads.extensionChanges) {
                        var extension = serverSettings.fileUploads.extensionChanges[i];

                        if (!(extension in serverSettings.fileUploads.extensionChangesReverse))
                            serverSettings.fileUploads.extensionChangesReverse[extension] = [extension];

                        serverSettings.fileUploads.extensionChangesReverse[extension].push(i);
                    }

                    for (i in serverSettings.fileUploads.allowedExtensions) {
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
                            for (i in serverSettings.parentalControls.parentalAges) {
                                $('#parentalAge').append('<option value="' + serverSettings.parentalControls.parentalAges[i] + '">' + $l('parentalAges.' + serverSettings.parentalControls.parentalAges[i]) + '</option>');
                            }

                            for (i in serverSettings.parentalControls.parentalFlags) {
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


                        /* Submit Upload */
/*                        $('#uploadFileForm').bind('submit', function() {
                            parentalAge = $('#parentalAge option:selected').val(),
                                parentalFlags = [];

                            $('input[data-cat=parentalFlag]:checked').each(function(a, b) {
                                parentalFlags.push($(b).attr('data-name'));
                            });

                            fim_showLoader();

                            fimApi.editFile({
                                'action' : 'create',
                                'dataEncode' : 'base64',
                                'uploadMethod' : 'raw',
                                'autoInsert' : true,
                                'roomId' : window.roomId,
                                'fileName' : fileName,
                                'fileData' : fileContent,
                                'parentalAge' : parentalAge,
                                'parentalFlags' : parentalFlags,
                                'md5hash' : md5hash
                            }, {
                                'end' : function(json) {
                                    fim_hideLoader();
                                    $('#insertDoc').dialog('close');
                                },
                                'error' : function() {
                                    fim_hideLoader();
                                    dia.error($l('uploadErrors.other')); // TODO: error string
                                }
                            });

                            return false;
                        });*/
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
                    'roomIds' : [window.roomId] // TODO
                }, {
                    'each' : function(room) {
                        $('table#viewStats > thead > tr').append('<th>' + room.roomData.roomName + '</th>');

                        var i = 0;

                        for (var j in room.users) { console.log(room.users[j]);
                            $('table#viewStats > tbody > tr').eq(i).append('<td><span class="userName userNameTable" data-userId="' + room.users[j].userData.userId + '" style="' + room.users[j].userData.userNameFormat + '">' + room.users[j].userData.userName + '</span> (' + room.users[j].messageCount + ')</td>');

                            i++;
                        }
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
                var idMap = {
                    disableFormatting : 16, disableImage : 32, disableVideos : 64, reversePostOrder : 1024,
                    showAvatars : 2048, audioDing : 8192, disableFx : 262144, disableRightClick : 1048576,
                    usTime : 16777216, twelveHourTime : 33554432, webkitNotifications : 536870912
                };

                fimApi.getUsers({
                    'info' : ['self', 'profile'],
                    'userIds' : [userId]
                }, {'each' : function(active) { console.log(active);
                    var options = active.options,
                        defaultHighlightHashPre = [],
                        defaultHighlightHash = {r:0, g:0, b:0},
                        defaultColourHashPre = [],
                        defaultColourHash = {r:0, g:0, b:0};

                    var ignoreList = new autoEntry($("#ignoreListContainer"), {
                        'name' : 'ignoreList',
                        'default' : active.ignoreList,
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
                    if ('font-weight' in defaultFormattingObj && defaultFormattingObj['font-style'] == 'italic') {
                        $('#fontPreview').css('font-style', 'italic');
                        $('#defaultItalics').attr('checked', 'checked');
                    }
                    $('#defaultItalics').change(function() {
                        if ($('#defaultItalics').is(':checked')) $('#fontPreview').css('font-style', 'italic');
                        else $('#fontPreview').css('font-style', 'normal');
                    });


                    // Default Formatting -- Font Colour
                    if ('color' in defaultFormattingObj) {
                        $('#fontPreview').css('color', defaultFormattingObj['color']);
                        $('#defaultColour').css('background-color', defaultFormattingObj['color']);

                        defaultColourHashPre = defaultFormattingObj['color'].slice(4, -1).split(',');
                        defaultColourHash = {r : defaultColourHashPre[0], g : defaultColourHashPre[1], b : defaultColourHashPre[2] }
                    }

                    // Default Formatting -- Highlight Colour
                    if ('background-color' in defaultFormattingObj) {
                        $('#fontPreview').css('background-color', defaultFormattingObj['background-color']);
                        $('#defaultHighlight').css('background-color', defaultFormattingObj['background-color']);

                        defaultHighlightHashPre = defaultFormattingObj['background-color'].slice(4, -1).split(',');
                        defaultHighlightHash = {r : defaultHighlightHashPre[0], g : defaultHighlightHashPre[1], b : defaultHighlightHashPre[2] }
                    }

                    // Default Formatting -- Fontface
                    if ('font-family' in defaultFormattingObj) {
                        $('#defaultFace > option').filter(function () { return $(this).attr('data-font') == defaultFormattingObj['font-family']; }).attr('selected', 'selected');
                    }
                    $('#defaultFace').change(function() {
                        $('#fontPreview').css('fontFamily', $('#defaultFace > option:selected').attr('data-font'));
                    });

                    // Colour Chooser -- Colour
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

                    // Colour Chooser -- Highlight
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

                    // Default Room Value
                    fimApi.getRooms({'roomIds' : [active.defaultRoomId]}, {'each' : function(roomData) { $('#defaultRoom').val(roomData.roomName).attr('data-id', roomData.roomId); }});

                    // Parental Control Flags
                    for (i in active.parentalFlags) {
                        $('input[data-cat=parentalFlag][data-name=' + active.parentalFlags[i] + ']').attr('checked', true);
                    }
                    $('select#parentalAge option[value=' + active.parentalAge + ']').attr('selected', 'selected');
                }});


                /* Update Default Form Values to Client Settings */
                // Boolean Checkboxes
                if (settings.reversePostOrder) $('#reversePostOrder').attr('checked', 'checked');
                if (settings.showAvatars) $('#showAvatars').attr('checked', 'checked');
                if (settings.audioDing) $('#audioDing').attr('checked', 'checked');
                if (settings.disableFx) $('#disableFx').attr('checked', 'checked');
                if (settings.disableFormatting) $('#disableFormatting').attr('checked', 'checked');
                if (settings.disableVideo) $('#disableVideo').attr('checked', 'checked');
                if (settings.disableImage) $('#disableImage').attr('checked', 'checked');
                if (settings.disableRightClick) $('#disableRightClick').attr('checked', 'checked');
                if (settings.webkitNotifications) $('#webkitNotifications').attr('checked', 'checked');
                if (settings.twelveHourTime) $('#twelveHourFormat').attr('checked', 'checked');
                if (settings.usTime) $('#usTime').attr('checked', 'checked');

                // Volume
                if (snd.volume) $('#audioVolume').attr('value', snd.volume * 100);

                // Select Boxes
                if (window.webproDisplay.theme) $('#theme > option[value="' + window.webproDisplay.theme + '"]').attr('selected', 'selected');
                if (window.webproDisplay.fontSize) $('#fontsize > option[value="' + window.webproDisplay.fontSize + '"]').attr('selected', 'selected');

                // Only Show the Profile Setting if Using Vanilla Logins
                if (window.serverSettings.branding.forumType !== 'vanilla') $('#settings5profile').hide(0);

                // Autocomplete Rooms and Users
                $("#defaultRoom").autocompleteHelper('rooms');

                // Populate Fontface Checkbox
                for (i in window.serverSettings.formatting.fonts) {
                    $('#defaultFace').append('<option value="' + i + '" style="' + window.serverSettings.formatting.fonts[i] + '" data-font="' + window.serverSettings.formatting.fonts[i] + '">' + i + '</option>')
                }

                // Parental Controls
                if (!window.serverSettings.parentalControls.parentalEnabled) { // Hide if Subsystem is Disabled
                    $('a[href="#settings5"]').parent().remove();
                }
                else {
                    for (i in window.serverSettings.parentalControls.parentalAges) {
                        $('#parentalAge').append('<option value="' + window.serverSettings.parentalControls.parentalAges[i] + '">' + $l('parentalAges.' + window.serverSettings.parentalControls.parentalAges[i]) + '</option>');
                    }
                    for (i in window.serverSettings.parentalControls.parentalFlags) {
                        $('#parentalFlagsList').append('<br /><label><input type="checkbox" value="true" name="flag' + window.serverSettings.parentalControls.parentalFlags[i] + '" data-cat="parentalFlag" data-name="' + window.serverSettings.parentalControls.parentalFlags[i] + '" />' +  $l('parentalFlags.' + window.serverSettings.parentalControls.parentalFlags[i]) + '</label>');
                    }
                }


                /* Actions onChange */
                // Theme -- Update onChange
                $('#theme').change(function() {
                    $('#stylesjQ').attr('href', 'client/css/' + this.value + '/jquery-ui-1.8.16.custom.css');
                    $('#stylesVIM').attr('href', 'client/css/' + this.value + '/fim.css');

                    $.cookie('webpro_theme', this.value, { expires : 14 });
                    window.webproDisplay.theme = this.value;

                    return false;
                });

                // Theme Fontsize -- Update onChange
                $('#fontsize').change(function() {
                    $('body').css('font-size',this.value + 'em');

                    $.cookie('webpro_fontsize', this.value, { expires : 14 });
                    window.webproDisplay.fontSize = this.value;

                    windowResize();

                    return false;
                });

                // Volume -- Update onChange
                $('#audioVolume').change(function() {
                    $.cookie('webpro_audioVolume', this.value, { expires : 14 });
                    snd.volume = this.value / 100;

                    return false;
                });

                // Various Settings -- Update onChange, Refresh Posts
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

                    requestSettings.firstRequest = true;
                    requestSettings.lastMessage = 0;
                    messageIndex = [];
                });

                // Various Settings -- Update onChange
                $('#audioDing, #disableFx, #webkitNotifications, #disableRightClick').change(function() {
                    var localId = $(this).attr('id');

                    if ($(this).is(':checked') && !settings[localId]) {
                        settings[localId] = true;
                        $.cookie('webpro_settings', Number($.cookie('webpro_settings')) + idMap[localId], { expires : 14 });

                        if (localId === 'disableFx') { jQuery.fx.off = true; } // Disable jQuery Effects
                        if (localId === 'webkitNotifications' && 'webkitNotifications' in window) { window.webkitNotifications.requestPermission(); } // Ask client permission for webkit notifications
                    }
                    else if (!$(this).is(':checked') && settings[localId]) {
                        settings[localId] = false;
                        $.cookie('webpro_settings', Number($.cookie('webpro_settings')) - idMap[localId], { expires : 14 });

                        if (localId === 'disableFx') { jQuery.fSystemx.off = false; } // Reenable jQuery Effects
                    }
                });


                /* Submit Processer */
                $("#changeSettingsForm").submit(function() {
                    var defaultFormatting = [],
                        parentalFlags = [];

                    if ($('#defaultBold').is(':checked')) defaultFormatting.push("bold");
                    if ($('#defaultItalics').is(':checked')) defaultFormatting.push("italic");

                    $('input[data-cat=parentalFlag]:checked').each(function(a, b) {
                        parentalFlags.push($(b).attr('data-name'));
                    });

                    fimApi.editUserOptions({
                        "defaultFontface" : $('#defaultFace option:selected').val(),
                        "defaultFormatting" : defaultFormatting,
                        "defaultHighlight" : ($('#fontPreview').css('background-color') === 'rgba(0, 0, 0, 0)' ? null : $('#fontPreview').css('background-color').slice(4,-1)),
                        "defaultColor" : $('#fontPreview').css('color').slice(4,-1),
                        "defaultRoomId" : $('#defaultRoom').attr('data-id'),
                        "watchRooms" : $('#watchRooms').val().split(','),
                        "ignoreList" : $('#ignoreList').val().split(','),
                        "profile" : $('#profile').val(),
                        "parentalAge" : $('#parentalAge option:selected').val(),
                        "parentalFlags" : parentalFlags
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

                            for (i in errors.responseJSON.editUserOptions) {
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

                        for (i in active.parentalFlags) {
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
                                        fimApi.editUserOptions({
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
                    'end' : windowDraw
                });
            }
        });
    },

    /*** END View My Uploads ***/






    /*** START Create Room ***/

    editRoom : function(roomIdLocal) {
        if (roomIdLocal) var action = 'edit';
        else var action = 'create';

        dia.full({
            content : $t('editRoomForm'),
            id : 'editRoomDialogue',
            width : 1000,
            tabs : true,
            oF : function() {
                /* Autocomplete Users and Groups */
                moderatorsList = new autoEntry($("#moderatorsContainer"), {
                    'name' : 'moderator',
                    'list' : 'users',
                    'onAdd' : function(id) {
                        if (action === 'edit') fimApi.editRoomPermissionUser(roomId, id, ["post", "moderate"])
                    },
                    'onRemove' : function(id) {
                        if (action === 'edit') fimApi.editRoomPermissionUser(roomId, id, ["post"])
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

                $('#allowPosting').change(function() {
                    if ($(this).is(':checked')) {
                        $('#allowedUsersBridge').attr('disabled', 'disabled');
                        $('#allowedGroupsBridge').attr('disabled', 'disabled');
                        $('#allowedUsersBridge').next().attr('disabled', 'disabled');
                        $('#allowedGroupsBridge').next().attr('disabled', 'disabled');
                    }
                    else {
                        $('#allowedUsersBridge').removeAttr('disabled');
                        $('#allowedGroupsBridge').removeAttr('disabled');
                        $('#allowedUsersBridge').next().removeAttr('disabled');
                        $('#allowedGroupsBridge').next().removeAttr('disabled');
                    }
                });


                /* Parental Controls */
                if (!serverSettings.parentalControls.parentalEnabled) { // Hide if Subsystem is Disabled
                    $('#editRoom1ParentalAge, #editRoom1ParentalFlags').remove();
                }
                else {
                    for (i in serverSettings.parentalControls.parentalAges) {
                        $('#parentalAge').append('<option value="' + serverSettings.parentalControls.parentalAges[i] + '">' + $l('parentalAges.' + serverSettings.parentalControls.parentalAges[i]) + '</option>');
                    }
                    for (i in serverSettings.parentalControls.parentalFlags) {
                        $('#parentalFlagsList').append('<label><input type="checkbox" value="true" name="flag' + serverSettings.parentalControls.parentalFlags[i] + '" data-cat="parentalFlag" data-name="' + serverSettings.parentalControls.parentalFlags[i] + '" />' +  $l('parentalFlags.' + serverSettings.parentalControls.parentalFlags[i]) + '</label><br />');
                    }
                }


                /* Censor Lists */
                fimApi.getCensorLists({
                    'roomId' : roomIdLocal ? roomIdLocal : 0,
                    'includeWords' : 0,
                }, {
                    'each' : function(listData) {
                        var listStatus;

                        if (listData.status) listStatus = listData.status;
                        else if (listData.listType === 'white') listStatus = 'block';
                        else if (listData.listType === 'black') listStatus = 'unblock';
                        else throw 'Bad logic.';

                        $('#censorLists').append('<label><input type="checkbox" name="list' + listData.listId + '" data-listId="' + listData.listId + '" data-checkType="list" value="true" ' + (listData.listOptions & 2 ? '' : ' disabled="disabled"') + (listStatus === 'block' ? ' checked="checked"' : '') + ' />' + listData.listName + '</label><br />');
                    }
                });


                /* Prepopulate Data if Editing a Room */
                if (roomIdLocal) {
                    fimApi.getRooms({
                        'roomIds' : [roomIdLocal]
                    }, {'each' : function(roomData) {
                        var allowedUsersArray = [],
                            moderatorsArray = [],
                            allowedGroupsArray = [];

                        for (var j in roomData.allowedUsers) { /* TODO? */
                            if (roomData.allowedUsers[j] & 15 === 15) { moderatorsArray.push(j); } // Are all bits up to 8 present?
                            if (roomData.allowedUsers[j] & 7 === 7) { allowedUsersArray.push(j); } // Are the 1, 2, and 4 bits all present?
                        }

                        // Name
                        $('#name').val(roomData.roomName);

                        // Options
                        $('#allowOfficial').attr('checked', roomData.official);
                        $('#allowHidden').attr('checked', roomData.hidden);

                        // Parental Data
                        console.log(roomData.parentalFlags);
                        for (i in roomData.parentalFlags) {
                            $('input[data-cat=parentalFlag][data-name=' + roomData.parentalFlags[i] + ']').attr('checked', true);
                        }
                        $('select#parentalAge option[value=' + roomData.parentalAge + ']').attr('selected', 'selected');

                        // Permissions
                        allowedUsersList.displayEntries(allowedUsersArray);
                        moderatorsList.displayEntries(moderatorsArray);
                        allowedGroupsList.displayEntries(allowedGroupsArray);

                        // Default Permissions
                        if (roomData.defaultPermissions == 7) $('#allowAllUsers').attr('checked', true); // If all users are currently allowed, check the box (which triggers other stuff above).
                    }});
                }


                /* Submit */
                $("#editRoomForm").submit(function() {console.log(allowedUsersList);
                    var name = $('#name').val(),
                        censor = {},
                        parentalAge = $('#parentalAge option:selected').val(),
                        parentalFlags = [],
                        combinedUserPermissions = {},
                        combinedGroupPermissions = {};

                    if (action === 'create') { console.log(allowedUsersList.getList());
                        allowedUsersList.getList().forEach(function(user) {
                            combinedUserPermissions["+" + user] = ['post'];
                        });
                        moderatorsList.getList().forEach(function(user) {
                            combinedUserPermissions["+" + user] = ['post', 'moderate'];
                        });
                        allowedGroupsList.getList().forEach(function(group) {
                            combinedGroupPermissions["+" + group] = ['post'];
                        });
                    }

                    $('input[data-checkType="list"]').each(function() {
                        censor[$(this).attr('data-listId')] = ($(this).is(':checked') ? 1 : 0);
                    });

                    $('input[data-cat=parentalFlag]:checked').each(function(a, b) {
                        parentalFlags.push($(b).attr('data-name'));
                    });

                    defaultPermissions = [];
                    if ($('#allowViewing').is(':checked')) defaultPermissions.push("view");
                    if ($('#allowPosting').is(':checked')) defaultPermissions.push("post");

                    fimApi.editRoom(roomIdLocal, {
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
                        'action' : action,
                        'begin' : function(json) {
                            dia.full({
                                content : 'The room has been created at the following URL: <br /><br /><form action="' + currentLocation + '#room=' + json.insertId + '" method="post"><input type="text" style="width: 300px;" value="' + currentLocation + '#room=' + json.insertId + '" name="url" /></form>',
                                title : 'Room Created!',
                                id : 'editRoomResultsDialogue',

                                width : 600,
                                buttons : {
                                    Open : function() {
                                        $('#editRoomResultsDialogue').dialog('close');
                                        standard.changeRoom(json.insertId);

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
            width : 1000,
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
                    for (room in user.rooms) roomData.push('<a href="#room=' + user.rooms[room].id + '">' + user.rooms[room].name + '</a>');

                    $('#onlineUsers').append('<tr><td><span class="userName" data-userId="' + user.userData.id + '" style=""' + user.userData.nameFormat + '"">' + user.userData.name + '</span></td><td>' + roomData.join(', ') + '</td></tr>');
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
                fimApi.getKicks({
                    'roomIds': ('roomId' in params ? [params.roomId] : null),
                    'userIds': ('userId' in params ? [params.userId] : null)
                }, {
                    'each' : function(kick) { console.log(kick);
                        $('#kickedUsers').append(
                            $('<tr>').append(
                                $('<td>').append(
                                    $('<span class="userName userNameTable">').attr({'data-userId' : kick.userData.userId, 'style' : kick.userData.userNameFormat}).text(kick.userData.userName)
                                )
                            ).append(
                                $('<td>').append(
                                    $('<span class="userName userNameTable">').attr({'data-userId' : kick.kickerData.userId, 'style' : kick.kickerData.userNameFormat}).text(kick.kickerData.userName)
                                )
                            ).append(
                                $('<td>').text(fim_dateFormat(kick.set, dateOptions))
                            ).append(
                                $('<td>').text(fim_dateFormat(kick.expires, dateOptions))
                            ).append(
                                $('<td>').append(
                                    $('<button>').click(function() {
                                        standard.unkick(kick.userData.userId, kick.roomData.roomId)
                                    }).text('Unkick')
                                )
                            )
                        );
                    }
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
            width : 1000,
            oF : function() {
                $('#userName').autocompleteHelper('users');
                $('#roomNameKick').autocompleteHelper('rooms');

                $("#kickUserForm").submit(function() {
                    var userName = $('#userName').val();
                    var roomName = $('#roomNameKick').val();
                    var length = Math.floor(Number($('#time').val() * Number($('#interval > option:selected').attr('value'))));

                    $.when(
                        Resolver.resolveUsersFromNames([userName]),
                        Resolver.resolveRoomsFromNames([roomName])
                    ).then(function(userPairs, roomPairs) { console.log(["pairs", userPairs, roomPairs]);
                        standard.kick(userPairs[userName].userId, roomPairs[roomName].roomId, length);
                    });

                    return false; // Don't submit the form.
                });

                return false;
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
                        var exportUser = $(this).find('td:nth-child(1) .userNameTable').text(),
                            exportTime = $(this).find('td:nth-child(2)').text(),
                            exportMessage = $(this).find('td:nth-child(3)').text();

                        for (i in [1,3]) {
                            switch (i) {
                                case 1: var exportItem = exportUser; break;
                                case 3: var exportItem = exportMessage; break;
                            }

                            var el = $(this).find('td:nth-child(' + i + ') > span'),
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