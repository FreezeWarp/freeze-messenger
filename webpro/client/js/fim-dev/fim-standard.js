var standard = {
  // This would be great as a class. In order to avoid Javascript's mess of inheritence, I've just written this to only support one instance. Regrettable, but there you go.
  archive : {
    options : {
      encrypt : 'base64',
      searchText : '',
      resultLimit : 40,
      searchUser : 0,
      lastMessage : 0,
      firstMessage : 0,
      roomId : 0
    },

    messageData : {},

    init : function(options) {
      for (i in options) standard.archive.options[i] = options[i];

      $('#searchText, #resultLimit, #searchUser, #archiveNext, #archivePrev, #export, .updateArchiveHere').unbind('change');

      $('#searchText, #resultLimit, #searchUser').bind('change', function() {
        standard.archive.update($(this).attr('id'), $(this).val());
        standard.archive.retrieve();
      });

      $('#archiveNext').bind('click', function() {
        standard.archive.nextPage();
        standard.archive.retrieve();
      });

      $('#archivePrev').bind('click', function() {
        standard.archive.prevPage();
        standard.archive.retrieve();
      });

      $('#archiveDialogue > table').on('click', '.updateArchiveHere', function() {
//        $('#searchUser').val(''); // Triggers change event.
//        $('#searchText').val(''); // "

        standard.archive.update('firstMessage', $(this).attr('data-messageId'));
        standard.archive.update('lastMessage', 0);

        standard.archive.retrieve();
      });

      $('#export').bind('click', function() {
        popup.exportArchive();
      });
    },

    retrieve : function() { // TODO: callback?
      $('#archiveMessageList').html('');
      standard.archive.messageData = {};

      fimApi.getMessages({
        'roomId' : standard.archive.options.roomId,
        'userIds' : [standard.archive.options.searchUser],
        'search' : standard.archive.options.searchText,
        'messageIdEnd' : standard.archive.options.lastMessage,
        'messageIdStart' : standard.archive.options.firstMessage,
        'archive' : 1,
        'sortOrder' : 'desc'
      }, {'each' : function(messageData) {
        $('#archiveMessageList').append(fim_messageFormat(messageData, 'table'));
        standard.archive.messageData[messageData.messageId] = messageData;
      }});
    },

    update : function (option, value) {
      standard.archive. options[option] = value;
    }
  },

  changeAvatar : function(sha256hash) {
    $.post(directory + 'api/editUserOptions.php', 'avatar=' + fim_eURL(window.location.protocol + '//' + window.location.host + '/' + directory + '/file.php?sha256hash=' + sha256hash) + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json', function(json) {
      active = json.editUserOptions;

      if (json.editUserOptions.response.avatar.errStr) {
        dia.info(json.editUserOptions.response.avatar.errDesc);
      }
      else {
        dia.info('Your avatar has been updated. It will not appear in your old messages.');
      }
    }); // Send the form data via AJAX.
  },

  login : function(options) {
    if (options.start) options.start();

    fimApi.login({
      'userId' : options.userId,
      'userName' : options.userName,
      'password' : options.password
    }, {
      callback : function(activeLogin) {
        window.activeLogin = activeLogin;
        window.userId = activeLogin.userData.userId;
        window.anonId = activeLogin.anonId;
        window.sessionHash = activeLogin.sessionHash;
        window.userPermissions = activeLogin.userPermissions;
        window.adminPermissions = activeLogin.adminPermissions;

        if (!anonId) {
          $.cookie('webpro_userId', userId, { expires : 14 });
          $.cookie('webpro_password', options.password, { expires : 14 }); // We will encrypt this in B3 or later -- it isn't a priority for now. (TODO)
        }


        if (options.showMessage) {
          // Display Dialog to Notify User of Being Logged In
          if (!userPermissions.view) dia.info('You are now logged in as ' + activeLogin.userData.userName + '. However, you are not allowed to view and have been banned by an administrator.', 'Logged In'); // dia.error(window.phrases.errorBanned);
          else if (!userPermissions.post) dia.info('You are now logged in as ' + activeLogin.userData.userName + '. However, you are not allowed to post and have been silenced by an administrator. You may still view rooms which allow you access.', 'Logged In'); // dia.error(window.phrases.errorBanned);)
          else dia.info('You are now logged in as ' + activeLogin.userData.userName + '.', 'Logged In');
        }

        $('#loginDialogue').dialog('close'); // Close any open login forms.

        if (options.finish) options.finish();


        if (!roomId) {
          fim_hashParse({defaultRoomId : activeLogin.defaultRoomId}); // When a user logs in, the hash data (such as room and archive) is processed, and subsequently executed.

          /*** A Hack of Sorts to Open Dialogs onLoad ***/
          if (typeof prepopup === 'function') { prepopup(); prepopup = false; }
        }

        return false;
      },
      error: function(data) { console.log(data);
         switch (data) {
         case 'PASSWORD_ENCRYPT': dia.error("The form encryption used was not accepted by the server."); break;
         case 'BAD_USERNAME': dia.error("A valid user was not provided."); break;
         case 'BAD_PASSWORD': dia.error("The password was incorrect."); break;
         case 'API_VERSION_STRING': dia.error("The server was unable to process the API version string specified."); break;
         case 'DEPRECATED_VERSION': dia.error("The server will not accept this client because it is of a newer version."); break;
         case 'INVALID_SESSION': sessionHash = ''; break;
         default: break;
         }

         console.log('Login Invalid');

        return false;
      }
    });
  },


  logout : function() {
    $.cookie('webpro_userId', null);
    $.cookie('webpro_password', null);

    standard.login({});
  },


  getMessages : function() {
    clearInterval(timers.t1);

    if (window.roomId) {
      var encrypt = 'base64';

      if (requestSettings.serverSentEvents) { // Note that the event subsystem __requires__ serverSentEvents for various reasons. If you use polling, these events will no longer be fully compatible.
        messageSource = new EventSource(directory + 'apiStream/messageStream.php?roomId=' + roomId + '&lastMessage=' + requestSettings.lastMessage + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId);
        eventSource = new EventSource(directory + 'apiStream/eventStream.php?roomId=' + roomId + '&lastEvent=' + requestSettings.lastEvent + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId);
        console.log('Starting EventSource; roomId: ' + roomId + '; lastEvent: ' + requestSettings.lastEvent + '; lastMessage: ' + requestSettings.lastMessage)

        messageSource.addEventListener('time', function(e) {
          console.log('The current time is: ' + e.data);
           return false;
        }, false);

        messageSource.addEventListener('message', function(e) {

          active = JSON.parse(e.data);

          var messageId = Number(active.messageData.messageId);

          console.log('Event (New Message): ' + messageId);

          data = fim_messageFormat(active, 'list');

          if ($.inArray(messageId, messageIndex) > -1) { } // Double post hack
          else { fim_newMessage(data, messageId); }

          return false;
        }, false);

        eventSource.addEventListener('topicChange', function(e) {
          var active = JSON.parse(e.data);

          $('#topic').html(active.param1);
          console.log('Event (Topic Change): ' + active.param1);

          requestSettings.lastEvent = active.eventId;

          return false;
        }, false);

        eventSource.addEventListener('missedMessage', function(e) {
          var active = JSON.parse(e.data);

          requestSettings.lastEvent = active.eventId;
          $.jGrowl('Missed Message', 'New messages have been made in:<br /><br /><a href="#room=' + active.roomId + '">' + active.roomName + '</a>');
          console.log('Event (Missed Message): ' + active.messageId);

          return false;
        }, false);

        eventSource.addEventListener('deletedMessage', function(e) {
          var active = JSON.parse(e.data);

          $('#topic').html(active.param1);
          console.log('Event (Topic Change): ' + active.param1);

          requestSettings.lastEvent = active.eventId;

          return false;
        }, false);
      }
      else {
        fimApi.getMessages({
          'roomId' : roomId,
          'archive' : (requestSettings.firstRequest ? 1 : 0),
          'messageIdStart' : requestSettings.lastMessage + 1
        }, {
          'each' : function(messageData) {
            var messageId = Number(messageData.messageId),
              data = fim_messageFormat(messageData, 'list');

            if ($.inArray(messageId, messageIndex)) { } // Double post hack
            else { fim_newMessage(data, messageId); }

            window.messageCount++;
          },
          'refresh' : 5000 // Todo: implement progressive refresh
        });
      }
    }
    else {
      console.log('Not requesting messages; room undefined.');
    }

    return false;
  },


  sendMessage : function(message, confirmed, flag) {
    if (!roomId) {
      popup.selectRoom();
    }
    else {
      confirmed = (confirmed === 1 ? 1 : '');

      $.ajax({
        url: directory + 'api/sendMessage.php?fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
        type: 'POST',
        data: 'roomId=' + roomId + '&confirmed=' + confirmed + '&message=' + fim_eURL(message) + '&flag=' + (flag ? flag : ''),
        cache: false,
        timeout: 5000,
        success: function(json) {
          var errStr = json.sendMessage.errStr,
            errDesc = json.sendMessage.errDesc;

          switch (errStr) {
            case '': break;
            case 'badRoom': dia.error("A valid room was not provided."); break;
            case 'badMessage': dia.error("A valid message was not provided."); break;
            case 'spaceMessage': dia.error("Too... many... spaces!"); break;
            case 'noPerm': dia.error("You do not have permission to post in this room."); break;
            case 'blockCensor': dia.error(errDesc); break;
            case 'confirmCensor':
            dia.error(errDesc + '<br /><br /><button type="button" onclick="$(this).parent().dialog(&apos;close&apos;);">No</button><button type="button" onclick="standard.sendMessage(&apos;' + escape(message) + '&apos;,1' + (flag ? ', ' + flag : '') + '); $(this).parent().dialog(&apos;close&apos;);">Yes</button>');
            break;
          }

          if (json.sendMessage.censor.severity === 'warn') {
            dia.info('Please use the word "' + json.sendMessage.censor.word + '" with care: ' + json.sendMessage.censor.reason);
          }

          return false;
        },
        error: function() {
          if (settings.reversePostOrder) { $('#messageList').append('Your message, "' + message + '", could not be sent and will be retried.'); }
          else { $('#messageList').prepend('Your message, "' + message + '", could not be sent and will be retried.'); }

          window.setTimeout(function() { standard.sendMessage(message) }, 5000);

          return false;
        }
      });
    }
  },


  changeRoom : function(roomIdLocal) {
    if (!roomIdLocal) {
      return false;
    }

    isPrivateRoom = false;
    if (isPrivateRoom) {
      // TODO
    }
    else { // Normal procedure otherwise.
      fimApi.getRooms({
        'roomIds' : [roomIdLocal],
        'permLevel' : 'view'
      }, {'each' : function(roomData) {
        if (!roomData.permissions.canView) { // If we can not view the room
          window.roomId = false; // Set the internal roomId false.
          popup.selectRoom(); // Prompt the user to select a new room.
          dia.error('You have been restricted access from this room. Please select a new room.');
        }
        else if (!roomData.permissions.canPost) { // If we can view, but not post
          dia.error('You are not allowed to post in this room. You will be able to view it, though.');
          disableSender();
        }
        else { // If we can both view and post.
          enableSender();
        }


        if (roomData.permissions.canView) { // If we can view the room...
          roomId = roomData.roomId;

          $('#roomName').html(roomData.roomName); // Update the room name.
          $('#topic').html(roomData.roomTopic); // Update the room topic.
          $('#messageList').html(''); // Clear the message list.


          /*** Get Messages (TODO: Streamline) ***/
          $(document).ready(function() {
            requestSettings.firstRequest = true;
            requestSettings.lastMessage = 0;
            messageIndex = [];

            standard.getMessages();

            windowDraw();
            windowDynaLinks();
          });
        }
      }});
    }
  },


  deleteRoom : function(roomIdLocal) {
    $.post(directory + 'api/editRoom.php', 'action=delete&messageId=' + messageId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json', function(json) {
      var errStr = json.editRoom.errStr,
        errDesc = json.editRoom.errDesc;

      switch (errStr) {
        case '': console.log('Message ' + messageId + ' deleted.'); break;
        case 'nopermission': dia.error('You do not have permision to administer this room.'); break;
        case 'badroom': dia.error('The specified room does not exist.'); break;
      }

      return false;
    }); // Send the form data via AJAX.
  },

  favRoom : function(roomIdLocal) {
    $.post(directory + 'api/editRoomLists.php', 'action=add&roomListName=favRooms&roomIds=' + roomIdLocal + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json', function(json) {
      return false;
    });
  },

  unfavRoom : function(roomIdLocal) {
    $.post(directory + 'api/editRoomLists.php', 'action=remove&roomListName=favRooms&roomIds=' + roomIdLocal + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json', function(json) {
      return false;
    });
  },

  /* TODO */
  privateRoom : function(params) {
  },


  kick : function(userLocalId, roomId, length) {
    $.post(directory + 'api/moderate.php', 'action=kickUser&userId=' + userLocalId + '&roomId=' + roomId + '&length=' + length + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json', function(json) {
      var errStr = json.moderaate.errStr,
        errDesc = json.moderaate.errDesc;

      switch (errStr) {
        case '': dia.info('The user has been kicked.', 'Success'); $("#kickUserDialogue").dialog('close'); break;
        case 'nopermission': dia.error('You do not have permision to moderate this room.'); break;
        case 'nokickuser': dia.error('That user may not be kicked!'); break;
        case 'baduser': dia.error('The user specified does not exist.'); break;
        case 'badroom': dia.error('The room specified does not exist.'); break;
      }

      return false;
    }); // Send the form data via AJAX.

    return false;
  },

  unkick : function(userId, roomId) {
    $.post(directory + 'api/moderate.php', 'action=unkickUser&userId=' + userId + '&roomId=' + roomId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId, function(json) {
      var errStr = json.moderaate.errStr,
        errDesc = json.moderaate.errDesc;

      switch (errStr) {
        case '': dia.info('The user has been unkicked.', 'Success'); $("#kickUserDialogue").dialog('close'); break;
        case 'nopermission': dia.error('You do not have permision to moderate this room.'); break;
        case 'baduser': case 'badroom': dia.error('Odd error: the user or room sent do not seem to exist.'); break;
      }

      return false;
    }); // Send the form data via AJAX.

    return false;
  },


  deleteMessage : function(messageId) {
    $.post(directory + 'api/editMessage.php', 'action=delete&messageId=' + messageId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + 'fim3_format=json', function(json) { // Send the form data via AJAX.
      var errStr = json.moderaate.errStr,
        errDesc = json.moderaate.errDesc;

      switch (errStr) {
        case '': console.log('Message ' + messageId + ' deleted.'); break;
        case 'nopermission': dia.error('You do not have permision to moderate this room.'); break;
        case 'badmessage': dia.error('The message does not exist.'); break;
      }

      return false;
    });

    return false;
  }
};