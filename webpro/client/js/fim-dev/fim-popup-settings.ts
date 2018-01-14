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

popup.prototype.settings = {
    init : function() { /* TODO: Handle reset properly, and refresh the entire application when settings are changed. It used to make some sense not to, but not any more. */
        // TODO: move
        let idMap = {
            disableFormatting : 16, disableImage : 32, disableVideos : 64, reversePostOrder : 1024,
            showAvatars : 2048, audioDing : 8192, disableFx : 262144, disableRightClick : 1048576,
            usTime : 16777216, twelveHourTime : 33554432, webkitNotifications : 536870912
        };

        /**************************
         ***** Server Settings ****
         **************************/

        let defaultHighlightHashPre = [],
            defaultHighlightHash = {r:0, g:0, b:0},
            defaultColourHashPre = [],
            defaultColourHash = {r:0, g:0, b:0};

        let ignoreList = new autoEntry($("#changeSettingsForm [name=ignoreListContainer]"), {
            'name' : 'ignoreList',
            'default' : window.activeLogin.userData.ignoredUsers,
            'list' : 'users',
            'resolveFromIds' : Resolver.resolveUsersFromIds,
            'resolveFromNames' : Resolver.resolveUsersFromNames
        });

        let friendsList = new autoEntry($("#changeSettingsForm [name=friendsListContainer]"), {
            'name' : 'friendsList',
            'default' : window.activeLogin.userData.ignoredUsers,
            'list' : 'users',
            'resolveFromIds' : Resolver.resolveUsersFromIds,
            'resolveFromNames' : Resolver.resolveUsersFromNames
        });

        let watchRooms = new autoEntry($("#changeSettingsForm [name=watchRoomsContainer]"), {
            'name' : 'watchRooms',
            'default' : window.activeLogin.userData.watchRooms,
            'list' : 'rooms',
            'resolveFromIds' : Resolver.resolveRoomsFromIds,
            'resolveFromNames' : Resolver.resolveRoomsFromNames
        });

        $("#changeSettingsForm input[name=defaultRoom]").autocompleteHelper('rooms', window.activeLogin.userData.defaultRoomId);


        /* Message Formatting */
        let defaultColourHashPre = [0,0,0];
        if ('color' in window.activeLogin.userData.messageFormattingObj) {
            $('#defaultColour').css('background-color', window.activeLogin.userData.messageFormattingObj['color']);
            defaultColourHashPre = window.activeLogin.userData.messageFormattingObj['color'].slice(4, -1).split(',');
        }

        $('#defaultColour').ColorPicker({
            color: {r : defaultColourHashPre[0], g : defaultColourHashPre[1], b : defaultColourHashPre[2] },
            onChange: function(hsb, hex, rgb) {
                $('#defaultColour').css('background-color', 'rgb(' + rgb['r'] + ',' + rgb['g'] + ',' + rgb['b'] + ')');
                $('#fontPreview').css('color', 'rgb(' + rgb['r'] + ',' + rgb['g'] + ',' + rgb['b'] + ')');
            }
        });



        let defaultHighlightHashPre = [255,255,255];
        if ('background-color' in window.activeLogin.userData.messageFormattingObj) {
            $('#defaultHighlight').css('background-color', window.activeLogin.userData.messageFormattingObj['background-color']);
            defaultHighlightHashPre = window.activeLogin.userData.messageFormattingObj['background-color'].slice(4, -1).split(',');
        }

        $('#defaultHighlight').ColorPicker({
            color: defaultHighlightHashPre ? {r : defaultHighlightHashPre[0], g : defaultHighlightHashPre[1], b : defaultHighlightHashPre[2] } : false,
            onChange: function(hsb, hex, rgb) {
                $('#defaultHighlight, #fontPreview').css('background-color', 'rgb(' + rgb['r'] + ',' + rgb['g'] + ',' + rgb['b'] + ')');
            }
        });


        // Default Privacy Level
        $('input[name=privacyLevel][value="' + window.activeLogin.userData.privacyLevel + '"]').prop('checked', true);




        /**************************
         * WebPro-Specific Values *
         **************************/

        /* Volume */
        if (window.snd.volume)
            $('#audioVolume').attr('value', snd.volume * 100);


        /* Various Settings onChange */
        $('input[name=showAvatars], input[name=reversePostOrder], input[name=disableFormatting], input[name=disableVideo], input[name=disableImage]').change(function() {
            var localId = $(this).attr('name');

            if ($(this).is(':checked') && !window.settings[localId]) {
                window.settings[localId] = true;
                $.cookie('webpro_settings', $.cookie('webpro_settings').toNumber() | idMap[localId], { expires : 14 });
            }
            else if (!$(this).is(':checked') && window.settings[localId]) {
                window.settings[localId] = false;
                $.cookie('webpro_settings', $.cookie('webpro_settings').toNumber() & ~idMap[localId], { expires : 14 });
            }
        });


        $('input[name=audioDing], input[name=webkitNotifications]').change(function() {
            var localId = $(this).attr('name');

            if ($(this).is(':checked') && !settings[localId]) {
                settings[localId] = true;
                $.cookie('webpro_settings', $.cookie('webpro_settings').toNumber() | idMap[localId], { expires : 14 });

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
                $.cookie('webpro_settings', Number($.cookie('webpro_settings')) & ~idMap[localId], { expires : 14 });
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
                "defaultRoomId" : $('#changeSettingsForm input[name=defaultRoom]').attr('data-id'),
                "watchRooms" : $('#changeSettingsForm input[name=watchRooms]').val().split(','),
                "ignoreList" : $('#changeSettingsForm input[name=ignoreList]').val().split(','),
                "friendsList" : $('#changeSettingsForm input[name=friendsList]').val().split(','),
                "profile" : $('#changeSettingsForm input[name=profile]').val(),
                "parentalAge" : $('form#changeSettingsForm select[name=parentalAge] option:selected').val(),
                "parentalFlags" : $('form#changeSettingsForm input[name=parentalFlags]:checked').map(function(){
                    return $(this).attr('value');
                }).get(),
                "privacyLevel" : $('input[name=privacyLevel]:radio:checked').val()
            }, {
                'each' : function(value) {
                    console.log(value);
                },
                'end' : function(data) {
                    let errorsList = [];

                    jQuery.each(data, function(param, error) {
                        errorsList.push("<li>" + param + ": " + error.details + "</li>");
                    });

                    if (errorsList.length) {
                        dia.error('Some of your settings have been updated. However, the following values were unable to be processed:<br /><ul>' + errorsList.join('') + '</ul>')
                    }
                    else {
                        dia.info('Your settings have been updated successfully.');

                        $("#changeSettingsDialogue").empty().remove(); // Housecleaning, needed if we want the colorpicker to work in another changesettings dialogue.
                        $(".colorpicker").empty().remove(); // Housecleaning, needed if we want the colorpicker to work in another changesettings dialogue.

                        window.location.hash = '#';
                    }
                },
            });

            return false; // Don't submit the form.
        });

        return false;
    }
};