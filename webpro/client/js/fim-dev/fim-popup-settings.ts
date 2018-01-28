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
    render : function() {
        // Disable autorender
    },

    init : function(options, render) { /* TODO: Handle reset properly, and refresh the entire application when settings are changed. It used to make some sense not to, but not any more. */

        render({
            notifySupported : notify.webkitNotifySupported(),
            pushNotifySupported : notify.pushNotifySupported()
        });

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
            'default' : window.activeLogin.userData.friendedUsers,
            'list' : 'users',
            'resolveFromIds' : Resolver.resolveUsersFromIds,
            'resolveFromNames' : Resolver.resolveUsersFromNames
        });

        let favRooms = new autoEntry($("#changeSettingsForm [name=favRoomsContainer]"), {
            'name' : 'favRooms',
            'default' : window.activeLogin.userData.favRooms,
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
        $('input[name=reversePostOrder], input[name=disableFormatting], input[name=disableVideos], input[name=disableImages], input[name=disableSocial], input[name=audioDing], input[name=webkitNotifications], input[name=pushNotifications], input[name=hideTimes], input[name=alternateSelfPosts], input[name=bubbleFormatting]').change(function() {
            let localId = $(this).attr('name');

            if ($(this).is(':checked') && !window.settings[localId]) {
                window.settings[localId] = true;
                $.cookie('webpro_settings', $.cookie('webpro_settings').toNumber() | window.settingNames[localId], { expires : 14 });
            }
            else if (!$(this).is(':checked') && window.settings[localId]) {
                window.settings[localId] = false;
                $.cookie('webpro_settings', $.cookie('webpro_settings').toNumber() & ~window.settingNames[localId], { expires : 14 });
            }
        });

        $('input[name=displayMode]').change(function() {
            switch ($(this).val()) {
                case 'simple':
                    window.settings.showAvatars = false;
                    window.settings.groupMessages = false;
                    $.cookie('webpro_settings', $.cookie('webpro_settings').toNumber() & ~window.settingNames.groupMessages & ~window.settingNames.showAvatars, { expires : 14 });
                    break;

                case 'avatars':
                    window.settings.showAvatars = true;
                    window.settings.groupMessages = false;
                    $.cookie('webpro_settings', $.cookie('webpro_settings').toNumber() & ~window.settingNames.groupMessages | window.settingNames.showAvatars, { expires : 14 });
                    break;

                case 'grouped':
                    window.settings.showAvatars = false;
                    window.settings.groupMessages = true;
                    $.cookie('webpro_settings', $.cookie('webpro_settings').toNumber() & ~window.settingNames.showAvatars | window.settingNames.groupMessages, { expires : 14 });
                    break;
            }
        });



        /**************************
         ******* Submit Form ******
         **************************/

        $("#changeSettingsForm").submit(function() {
            let defaultFormatting = [];

            if ($('#defaultBold').is(':checked'))
                defaultFormatting.push("bold");
            if ($('#defaultItalics').is(':checked'))
                defaultFormatting.push("italic");

            fimApi.editUserOptions('edit', {
                "defaultFontface" : $('#defaultFace option:selected').val(),
                "defaultFormatting" : $('form#changeSettingsForm input[name="defaultFormatting[]"]:checked').map(function(){
                    return $(this).attr('value');
                }).get(),
                "defaultHighlight" : ($('#fontPreview').css('background-color') === 'rgba(0, 0, 0, 0)' ? null : $('#fontPreview').css('background-color').slice(4,-1)),
                "defaultColor" : $('#fontPreview').css('color').slice(4,-1),
                "defaultRoomId" : $('#changeSettingsForm input[name=defaultRoom]').attr('data-id'),
                "favRooms" : $('#changeSettingsForm input[name=favRooms]').val().split(','),
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

                        fimApi.getUsers({
                            'id' : window.activeLogin.userData.id,
                        }, {
                            'each' : function(userData) {
                                window.standard.setUserData(userData);
                            },
                            'end' : function () {
                                window.location.hash = '#';
                            }
                        })
                    }
                },
            });

            return false; // Don't submit the form.
        });
    },

    close : function() {
        // Clean up colour pickers
        $('.colorpicker').remove();
    }
};