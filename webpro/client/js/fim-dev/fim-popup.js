/*********************************************************
************************ START **************************
************** Repeat-Action Popup Methods **************
*********************************************************/

popup = {
  /*** START Login ***/

  login : function() {
    dia.full({
      content : $t('login'),
      title : 'Login',
      id : 'loginDialogue',
      width : 600,
      oF : function() {
        // The following is a rather complicated hack that fixes a huge issue with how the login box first displays. It's stupid, but... yeah.
        manualHeight = ($(window).innerHeight() - 600) / 2;
        if (manualHeight < 0) manualHeight = 0;

        manualWidth = ($(window).innerWidth() - 600) / 2;
        if (manualWidth < 0) manualWidth = 0;

        $('#loginDialogue').parent().css('top', manualHeight);
        $('#loginDialogue').parent().css('left', manualWidth);
        $('#loginDialogue').parent().css('position', 'absolute');
        $('body').scrollTop();


        // Login Form Processing
        $("#loginForm").submit(function() {
          var userName = $('#loginForm > #userName').val(),
            password = $('#loginForm > #password').val(),
            rememberMe = $('#loginForm > #rememberme').is('checked');

          standard.login({
            userName : userName, password : password,
            showMessage : true, rememberMe : rememberMe
          });

          return false; // Don't submit the form.
        });
      },
      cF : function() {
        if (!userId) {
          standard.login({
            start : function() {
              $('<div class="ui-widget-overlay" id="loginWaitOverlay"></div>').appendTo('body').width($(document).width()).height($(document).height());
              $('<img src="images/ajax-loader.gif" id="loginWaitThrobber" />').appendTo('body').css('position', 'absolute').offset({ left : (($(window).width() - 220) / 2), top : (($(window).height() - 19) / 2)});
            },
            finish : function() {
              $('#loginWaitOverlay, #loginWaitThrobber').empty().remove();
            }
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
        fimApi.getRooms({
        }, function(roomData) {
          $('#roomTableHtml').append('<tr id="room' + roomData.roomId + '"><td><a href="#room=' + roomData.roomId + '">' + roomData.roomName + '</a></td><td>' + roomData.roomTopic + '</td><td>' + (roomData.isAdmin ? '<button data-roomId="' + roomData.roomId + '" class="editRoomMulti standard"></button><button data-roomId="' + roomData.roomId + '" class="deleteRoomMulti standard"></button>' : '') + '<button data-roomId="' + roomData.roomId + '" class="archiveMulti standard"></button><input type="checkbox" data-roomId="' + roomData.roomId + '" class="favRoomMulti" id="favRoom' + roomData.roomId + '" /><label for="favRoom' + roomData.roomId + '" class="standard"></label></td></tr>');
        }, function() {
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
            $('#uploadFileForm').bind('submit', function() {
              parentalAge = $('#parentalAge option:selected').val(),
              parentalFlags = [];

              $('input[data-cat=parentalFlag]:checked').each(function(a, b) {
                parentalFlags.push($(b).attr('data-name'));
              });

              fim_showLoader();

              $.ajax({
                url : directory + 'api/editFile.php',
                type : 'POST',
                data : 'action=create&dataEncode=base64&uploadMethod=raw&autoInsert=true&roomId=' + roomId + '&fileName=' + fileName + '&parentalAge=' + parentalAge + '&parentalFlags=' + parentalFlags.join(',') + '&fileData=' + fim_eURL(fileContent) + '&md5hash=' + md5hash + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
                cache : false,
                success : function(json) {
                  if (json.editFile.errStr) $l('uploadErrors.' + errStr);
                  else $('#insertDoc').dialog('close');
                },
                error : function() {
                  dia.error($l('uploadErrors.other'));
                },
                finish : fim_hideLoader(),
              });

              return false;
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
          'roomIds' : [window.roomId] // TODO
        }, function(active) {
          var roomName = active.roomData.roomName,
            roomId = active.roomData.roomId;

          $('table#viewStats > thead > tr').append('<th>' + roomName + '</th>');

          i = 1;

          for (j in active.users) {
            var userName = active.users[j].userData.userName,
              userId = active.users[j].userData.userId,
              startTag = active.users[j].userData.startTag,
              endTag = active.users[j].userData.endTag,
              position = active.users[j].position,
              messageCount = active.users[j].messageCount;

            $('table#viewStats > tbody > tr').eq(i - 1).append('<td><span class="userName userNameTable" data-userId="' + userId + '">' + startTag + userName + endTag + '</span> (' + messageCount + ')</td>');

            i++;
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
        var defaultColour = false,
          defaultHighlight = false,
          defaultFontface = false,
          defaultGeneral, ignoreList, watchRooms, options, defaultRoom,
          defaultHighlightHashPre, defaultHighlightHash, defaultColourHashPre, defaultColourHash,
          parentalAge, parentalFlags,
          idMap = {
            disableFormatting : 16, disableImage : 32, disableVideos : 64, reversePostOrder : 1024,
            showAvatars : 2048, audioDing : 8192, disableFx : 262144, disableRightClick : 1048576,
            usTime : 16777216, twelveHourTime : 33554432, webkitNotifications : 536870912
          };

        fimApi.getUsers({
          'userIds' : [userId]
        }, function(active) { console.log(active);
          defaultColour = active.defaultFormatting.color;
          defaultHighlight = active.defaultFormatting.highlight;
          defaultFontface = active.defaultFormatting.fontface;
          defaultGeneral = active.defaultFormatting.general;
          ignoreList = active.ignoreList;
          watchRooms = active.watchRooms;
          options = active.options;
          defaultRoom = active.defaultRoom;
          defaultHighlightHashPre = [];
          defaultHighlightHash = {r:0, g:0, b:0};
          defaultColourHashPre = [];
          defaultColourHash = {r:0, g:0, b:0};
          parentalAge = active.parentalAge;
          parentalFlags = active.parentalFlags;

          /* Update Default Forum Values Based on Server Settings */
          // User Profile
          if (active.profile) $('#profile').val(active.profile);

          // Default Formatting -- Bold
          if (defaultGeneral & 256) {
            $('#fontPreview').css('font-weight', 'bold');
            $('#defaultBold').attr('checked', 'checked');
          }
          $('#defaultBold').change(function() {
            if ($('#defaultBold').is(':checked')) $('#fontPreview').css('font-weight', 'bold');
            else $('#fontPreview').css('font-weight', 'normal');
          });

          // Default Formatting -- Italics
          if (defaultGeneral & 512) {
            $('#fontPreview').css('font-style', 'italic');
            $('#defaultItalics').attr('checked', 'checked');
          }
          $('#defaultItalics').change(function() {
            if ($('#defaultItalics').is(':checked')) $('#fontPreview').css('font-style', 'italic');
            else $('#fontPreview').css('font-style', 'normal');
          });

          // Default Formatting -- Font Colour
          if (defaultColour) {
            $('#fontPreview').css('color', 'rgb(' + defaultColour + ')');
            $('#defaultColour').css('background-color', 'rgb(' + defaultColour + ')');

            defaultColourHashPre = defaultColour.split(',');
            defaultColourHash = {r : defaultColourHashPre[0], g : defaultColourHashPre[1], b : defaultColourHashPre[2] }
          }

          // Default Formatting -- Highlight Colour
          if (defaultHighlight) {
            $('#fontPreview').css('background-color', 'rgb(' + defaultHighlight + ')');
            $('#defaultHighlight').css('background-color', 'rgb(' + defaultHighlight + ')');

            defaultHighlightHashPre = defaultHighlight.split(',');
            defaultHighlightHash = {r : defaultHighlightHashPre[0], g : defaultHighlightHashPre[1], b : defaultHighlightHashPre[2] }
          }

          // Default Formatting -- Fontface
          if (defaultFontface) {
            $('#defaultFace > option[value="' + defaultFontface + '"]').attr('selected', 'selected');
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
          fimApi.getRooms({'roomIds' : [defaultRoom]}, function(roomData) { $('#defaultRoom').val(roomData.roomName); })

          // Populate Existing Entries for Lists
          autoEntry.showEntries('ignoreList', ignoreList);
          autoEntry.showEntries('watchRooms', watchRooms);

          // Parental Control Flags
          for (i in parentalFlags) {
            $('input[data-cat=parentalFlag][data-name=' + parentalFlags[i] + ']').attr('checked', true);
          }
          $('select#parentalAge option[value=' + parentalAge + ']').attr('selected', 'selected');

          return false;
        });


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
        $("#defaultRoom").autocomplete({ source: roomList });
        $("#watchRoomsBridge").autocomplete({ source: roomList });
        $("#ignoreListBridge").autocomplete({ source: userList });

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
          $('#stylesFIM').attr('href', 'client/css/' + this.value + '/fim.css');

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
          var watchRooms = $('#watchRooms').val(),
            defaultRoom = $('#defaultRoom').val(),
            ignoreList = $('#ignoreList').val(),
            profile = $('#profile').val(),
            defaultRoomId = (defaultRoom ? roomRef[defaultRoom] : 0),
            fontId = $('#defaultFace option:selected').val(),
            defaultFormatting = ($('#defaultBold').is(':checked') ? 256 : 0) + ($('#defaultItalics').is(':checked') ? 512 : 0),
            parentalAge = $('#parentalAge option:selected').val(),
            parentalFlags = [];

          $('input[data-cat=parentalFlag]:checked').each(function(a, b) {
            parentalFlags.push($(b).attr('data-name'));
          });

          $.post(directory + 'api/editUserOptions.php', 'defaultFormatting=' + defaultFormatting + '&defaultColor=' + defaultColour + '&defaultHighlight=' + defaultHighlight + '&defaultRoomId=' + defaultRoomId + '&watchRooms=' + watchRooms + '&ignoreList=' + ignoreList + '&profile=' + profile + '&defaultFontface=' + fontId + '&parentalAge=' + parentalAge + '&parentalFlags=' + parentalFlags.join(',') + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json', function(json) {
            
            dia.info('Your settings may or may not have been updated.');
          }); // Send the form data via AJAX.

          $("#changeSettingsDialogue").empty().remove(); // Housecleaning, needed if we want the colorpicker to work in another changesettings dialogue.
          $(".colorpicker").empty().remove(); // Housecleaning, needed if we want the colorpicker to work in another changesettings dialogue.

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
        }, function(active) {
          var fileName = active.fileName,
            md5hash = active.md5hash,
            sha256hash = active.sha256hash,
            fileSizeFormatted = active.fileSizeFormatted,
            parentalAge = active.parentalAge,
            parentalFlags = active.parentalFlags,
            parentalFlagsFormatted = [];

          for (i in parentalFlags) {
            if (parentalFlags[i]) parentalFlagsFormatted.push($l('parentalFlags.' + parentalFlags[i])); // Yes, this is a very weird line.
          }

          $('#viewUploadsBody').append('<tr><td align="center"><img src="' + directory + 'file.php?sha256hash=' + sha256hash + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json" style="max-width: 200px; max-height: 200px;" /><br />' + fileName + '</td><td align="center">' + fileSizeFormatted + '</td><td align="center">' + $l('parentalAges.' + parentalAge) + '<br />' + parentalFlagsFormatted.join(', ') + '</td><td align="center"><button onclick="standard.changeAvatar(\'' + sha256hash + '\')">Set to Avatar</button></td></tr>');
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
        $("#moderatorsBridge").autocomplete({ source: userList });
        $("#allowedUsersBridge").autocomplete({ source: userList });
        $("#allowedGroupsBridge").autocomplete({ source: groupList });

        $('#allowAllUsers').change(function() {
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
          'roomIds' : roomIdLocal ? [roomIdLocal] : [0]
        }, {
          'each' : function(listData) {
            var listStatus;

            if (('roomStatus ' + roomIdLocal.listId) in listData.roomStatuses) listStatus = listData.roomStatuses['roomStatus ' + roomIdLocal.listId].status;
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
          }, function(roomData) {
            var data = '',
              roomName = roomData.roomName,
              roomId = roomData.roomId,
              allowedUsers = roomData.allowedUsers,
              allowedGroups = roomData.allowedGroups,
              defaultPermissions = roomData.defaultPermissions,
              parentalAge = roomData.parentalAge,
              parentalFlags = roomData.parentalFlags,
              allowedUsersArray = [],
              moderatorsArray = [],
              allowedGroupsArray = [];

            for (var j in allowedUsers) { /* TODO? */
              if (allowedUsers[j] & 15 === 15) { moderatorsArray.push(j); } // Are all bits up to 8 present?
              if (allowedUsers[j] & 7 === 7) { allowedUsersArray.push(j); } // Are the 1, 2, and 4 bits all present?
            }

            console.log(parentalFlags);
            for (i in parentalFlags) {
              $('input[data-cat=parentalFlag][data-name=' + parentalFlags[i] + ']').attr('checked', true);
            }
            $('select#parentalAge option[value=' + parentalAge + ']').attr('selected', 'selected');
            $('#name').val(roomName); // Current Room Name

            /* Prepopulate
            * TODO: Replace w/ AJAX. */
            // User Autocomplete
            if (allowedUsersArray.length > 0) autoEntry.showEntries('allowedUsers', allowedUsersArray);
            if (moderatorsArray.length > 0) autoEntry.showEntries('moderators', moderatorsArray);
            if (allowedGroupsArray.length > 0) autoEntry.showEntries('allowedGroups', allowedGroupsArray);

            if (defaultPermissions == 7) $('#allowAllUsers').attr('checked', true); // If all users are currently allowed, check the box (which triggers other stuff above).
          });
        }


        /* Submit */
        $("#editRoomForm").submit(function() {
          var name = $('#name').val(),
            allowedUsers = $('#allowedUsers').val(),
            allowedGroups = $('#allowedGroups').val(),
            moderators = $('#moderators').val(),
            censor = [],
            parentalAge = $('#parentalAge option:selected').val(),
            parentalFlags = [];

          $('input[data-checkType="list"]').each(function() {
            censor.push($(this).attr('data-listId') + '=' + ($(this).is(':checked') ? 1 : 0));
          });

          $('input[data-cat=parentalFlag]:checked').each(function(a, b) {
            parentalFlags.push($(b).attr('data-name'));
          });

          console.log(directory + 'api/editRoom.php', 'action=' + action + '&roomId=' +  roomIdLocal + '&roomName=' + fim_eURL(name) + '&defaultPermissions=' + ($('#allowAllUsers').is(':checked') ? '7' : '0' + '&allowedUsers=' + allowedUsers + '&allowedGroups=' + allowedGroups) + '&moderators=' + moderators + '&parentalAge=' + parentalAge + '&parentalFlags=' + parentalFlags + '&censor=' + censor.join(',') + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId);

          if (name.length > window.serverSettings.rooms.roomLengthMaximum) dia.error('The roomname is too long.');
          else if (name.length < window.serverSettings.rooms.roomLengthMinimum) dia.error('The roomname is too short.');
          else {
            $.post(directory + 'api/editRoom.php', 'action=' + action + '&roomId=' +  roomIdLocal + '&roomName=' + fim_eURL(name) + '&defaultPermissions=' + ($('#allowAllUsers').is(':checked') ? '7' : '0' + '&allowedUsers=' + allowedUsers + '&allowedGroups=' + allowedGroups) + '&moderators=' + moderators + '&parentalAge=' + parentalAge + '&parentalFlags=' + parentalFlags + '&censor=' + fim_eURL(censor.join(',')) + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId, function(json) {
              var errStr = json.editRoom.errStr,
                errDesc = json.editRoom.errDesc,
                createRoomId = json.editRoom.response.insertId;

              if (errStr) {
                dia.error('An error has occured: ' + errDesc);
              }
              else {
                dia.full({
                  content : 'The room has been created at the following URL:<br /><br /><form action="' + currentLocation + '#room=' + createRoomId + '" method="post"><input type="text" style="width: 300px;" value="' + currentLocation + '#room=' + createRoomId + '" name="url" /></form>',
                  title : 'Room Created!',
                  id : 'editRoomResultsDialogue',

                  width : 600,
                  buttons : {
                    Open : function() {
                      $('#editRoomResultsDialogue').dialog('close');
                      standard.changeRoom(createRoomId);

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
            }); // Send the form data via AJAX.
          }

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
        $('#userName').autocomplete({
          source: userList
        });

        $("#privateRoomForm").submit(function() {
          standard.privateRoom({
            'userName' : $("#privateRoomForm > #userName").val()
          });

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
        'refresh' : 1000,
        'begin' : function() {
          $('#onlineUsers').html('');
        },
        'each' : function(activeUser) {
          var roomData = [];
          for (j in activeUser.rooms) roomData.push('<a href="#room=' + activeUser.rooms[j].roomId + '">' + activeUser.rooms[j].roomName + '</a>');

          $('#onlineUsers').append('<tr><td>' + activeUser.userData.startTag + '<span class="userName" data-userId="' + activeUser.userData.userId + '">' + activeUser.userData.userName + '</span>' + activeUser.userData.endTag + '</td><td>' + roomData.join(', ') + '</td></tr>');
        },
        'end' : function() {
          //contextMenuParseUser('#onlineUsers');
        }
      }),
      cF : fimApi.getActiveUsers({}, {
        'close' : true
      })
    });
  },

  /*** END Online ***/




  /*** START Kick Manager ***/

  manageKicks : function(params) {
    dia.full({
      content : $t('manageKicks'),
      title : 'Manage/View Kicked Users',
      width : 1000,
      oF : function() {
        getKicks({
          'roomIds': ('roomId' in params ? params.roomId : [0]),
          'userIds': ('userId' in params ? params.userId : [0])
        }, function(kick) {
          $('#kickedUsers').append('<tr><td>' + kick.userData.userFormatStart + '<span class="userName userNameTable" data-userId="' + kick.userData.userId + '">' + kick.userData.userName + '</span>' + kick.userData.userFormatEnd + '</td><td>' + kick.kickerData.userFormatStart + '<span class="userName userNameTable" data-userId="' + kick.kickerData.userId + '">' + kick.kickerData.userName + '</span>' + kick.kickerData.userFormatEnd + '</td><td>' + fim_dateFormat(kick.set, true) + '</td><td>' + fim_dateFormat(kick.expires, true) + '</td><td><button onclick="standard.unkick(' + userId + ', ' + roomId + ')">Unkick</button></td></tr>');
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
        $("#kickUserForm").submit(function() {
          var roomNameKick = $('#roomNameKick').val(),
            roomId = roomRef[roomNameKick],
            userName = $('#userName').val(),
            length = Math.floor(Number($('#time').val() * Number($('#interval > option:selected').attr('value'))));

          fimApi.getUsers({
            'userNames' : [userName]
          }, function(userData) {
            standard.kick(userData.userId, roomId, length);
          })

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

  archive : function(options) { console.log(options);
    dia.full({
      content : $t('archive'),
      title : 'Archive',
      id : 'archiveDialogue',
      position : 'top',
      width : 1000
    });

    standard.archive.init({
      roomId: options.roomId,
      firstMessage: options.idMin
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