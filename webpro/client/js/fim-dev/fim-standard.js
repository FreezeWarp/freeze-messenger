var standard = {
  archive : function (options) {
    var encrypt = 'base64',
      lastMessage = 0,
      firstMessage = 0,
      data = '',
      where = '';

    if (options.idMax) where = 'messageIdEnd=' + options.idMax;
    else if (options.idMin) where = 'messageIdStart=' + options.idMin;
    else where = 'messageIdStart=1';

    $('#searchText, #resultLimit, #searchUser').unbind('change');
    $('#searchText, #resultLimit, #searchUser').bind('change', function() {
      standard.archive({
        idMax : options.idMax,
        idMin : options.idMin,
        roomId : options.roomId,
        userId : userRef[$('#searchUser').val()],
        search : $('#searchText').val(),
        maxResults : $('#resultLimit').val(),
      });
    });

    $.when( $.ajax({
      url: directory + 'api/getMessages.php?roomId=' + options.roomId + '&' + (options.userId ? '&users=' + options.userId : '') + '&archive=1&messageHardLimit=' + (options.maxResults ? options.maxResults : 50) + '&' + where + (options.search ? '&search=' + fim_eURL(options.search) : '') + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
      type: 'GET',
      timeout: 5000,
      contentType: "text/json; charset=utf-8",
      dataType: "json",
      cache: false,
      success: function (json) {
        active = json.getMessages.messages;

        for (i in active) {
          var messageId = active[i].messageData.messageId;

          data += messageFormat(active[i], 'table');

          if (messageId > lastMessage) { lastMessage = messageId; }
          if (messageId < firstMessage || !firstMessage) { firstMessage = messageId; }
        }

        return true;
      }
    })).always(function() {
      $('#archiveMessageList').html(data);

      $('#archiveNext').unbind('click');
      $('#archivePrev').unbind('click');
      $('#export').unbind('click');
      $('.updateArchiveHere').unbind('click');

      $('#archiveNext').bind('click', function() {
        standard.archive({
          idMin : lastMessage,
          roomId: options.roomId,
          userId : userRef[$('#searchUser').val()],
          search : $('#searchText').val(),
          maxResults : $('#resultLimit').val()
        })
      });
      $('#archivePrev').bind('click', function() {
        standard.archive({
          idMax : firstMessage,
          roomId: options.roomId,
          userId : userRef[$('#searchUser').val()],
          search : $('#searchText').val(),
          maxResults : $('#resultLimit').val()
        })
      });
      $('.updateArchiveHere').bind('click', function() {
        $('#searchUser').val('');
        $('#searchText').val('');

        standard.archive({
          idMin : $(this).attr('data-messageId'),
          roomId: options.roomId,
          maxResults : $('#resultLimit').val()
        })
      });
      $('#export').bind('click', function() {
        dia.full({
          id : 'exportDia',
          content : '<form method="post" action="#" onsubmit="return false;" id="exportDiaForm">How would you like to export the data?<br /><br /><table align="center"><tr><td>Format</td><td><select id="exportFormat"><option value="bbcodetable">BBCode Table</option><option value="csv">CSV List (Excel, etc.)</option></select></td></tr><tr><td colspan="2" align="center"><button type="submit">Export</button></td></tr></table></form>',
          width: 600,
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
                  case 1:
                  var exportItem = exportUser;
                  break;

                  case 3:
                  var exportItem = exportMessage;
                  break;
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
              }

              switch (i) {
                case 1: exportUser = exportItem; break;
                case 3: exportMessage = exportItem; break;
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
            width : '1000',
          });

          return false;
        });
      });

      if (options.callback) {
        options.callback(data);
      }

      return true;
    });
  },

  changeAvatar : function(sha256hash) {
    $.post(directory + 'api/editUserOptions.php', 'avatar=' + encodeURIComponent(window.location.protocol + '//' + window.location.host + '/' + directory + '/file.php?sha256hash=' + sha256hash) + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json', function(json) {
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
    var data = '',
      passwordEncrypt = '';

    console.log('Encrypted Password: ' + options.password);

    if (options.start) options.start();

    if (options.userName && options.password) {
      console.log('Login Triggered; Using a Password of "' + options.password + '" and a Username of "' + options.userName + '"');

      passwordEncrypt = 'plaintext';
      // TODO: Enable for vBulletin
      // var password = md5(password);
      // var passwordEncrypt = 'md5';

      data = 'userName=' + fim_eURL(options.userName) + '&password=' + fim_eURL(options.password) + '&passwordEncrypt=' + passwordEncrypt;
    }
    else if (options.userId && options.password) {
      console.log('Login Triggered; Using a Password of "' + options.password + '" and a UserID of "' + options.userId + '"');

      passwordEncrypt = 'plaintext';
      // TODO: Enable for vBulletin
      // var password = md5(password);
      // var passwordEncrypt = 'md5';

      data = 'userId=' + fim_eURL(options.userId) + '&password=' + fim_eURL(options.password) + '&passwordEncrypt=' + passwordEncrypt;
    }
    else {
      data = 'apiLogin=1';
    }


    $.ajax({
      url: directory + 'validate.php',
      type: 'POST',
      data: data + '&apiVersion=3&fim3_format=json',
      cache: false,
      timeout: 2500,
      success: function(json) {
        console.log('Login Started');

        activeLogin = json.login;

        userId = activeLogin.userData.userId;
        anonId = activeLogin.anonId;
        sessionHash = activeLogin.sessionHash;


        $.cookie('webpro_userId', userId, { expires : 14 });
        $.cookie('webpro_password', options.password, { expires : 14 }); // We will encrypt this in B3 or later -- it isn't a priority for now. (TODO)


        /* Update Permissions */

        userPermissions = {
          createRoom : activeLogin.userPermissions.createRooms, privateRoom : activeLogin.userPermissions.privateRooms,
          general : activeLogin.userPermissions.allowed
        }

        adminPermissions = {
          modPrivs : activeLogin.adminPermissions.modPrivs, modCore : activeLogin.adminPermissions.modCore,
          modUsers : activeLogin.adminPermissions.modUsers, modTemplates : activeLogin.adminPermissions.modTemplates,
          modImages : activeLogin.adminPermissions.modImages, modCensor : activeLogin.adminPermissions.modCensor,
          modHooks : activeLogin.adminPermissions.modHooks
        }


        if (activeLogin.banned) { // The user has been banned, so pretty much nothing will work. In some respects, this really only exists for IP bans, but meh.
          dia.error(window.phrases.errorBanned);

          userPermissions = {
            createRoom : false, privateRoom : false, general : false
          }

          adminPermissions = {
            modPrivs : false, modCore : false, modUsers : false,
            modTemplates : false, modImages : false, modCensor : false,
            modHooks : false
          }
        }
        else if (activeLogin.valid === true) {
          if (options.showMessage) {
            // Display Dialog to Notify User of Being Logged In
            if (!userPermissions.general) dia.info('You are now logged in as ' + activeLogin.userData.userName + '. However, you are not allowed to post and have been banned by an administrator.', 'Logged In');
            else dia.info('You are now logged in as ' + activeLogin.userData.userName + '.', 'Logged In');
          }

          $('#loginDialogue').dialog('close'); // Close any open login forms.

          console.log('Login valid. Session hash: ' + sessionHash + '; User ID: ' + userId);
        }
        else {
          switch (activeLogin.loginFlag) {
            case 'PASSWORD_ENCRYPT': dia.error("The form encryption used was not accepted by the server."); break;
            case 'BAD_USERNAME': dia.error("A valid user was not provided."); break;
            case 'BAD_PASSWORD': dia.error("The password was incorrect."); break;
            case 'API_VERSION_STRING': dia.error("The server was unable to process the API version string specified."); break;
            case 'DEPRECATED_VERSION': dia.error("The server will not accept this client because it is of a newer version."); break;
            case 'INVALID_SESSION': sessionHash = ''; break;
            default: break;
          }

          console.log('Login Invalid');
        }


        if (!anonId && !userId) disableSender(); // The user is not able to post.

        if (options.finish) options.finish();

        populate({
          callback : function() {
            contextMenuParseRoom();
            windowDynaLinks();

            /* Select Room */
            if (!roomId) {
              hashParse({defaultRoomId : activeLogin.defaultRoomId}); // When a user logs in, the hash data (such as room and archive) is processed, and subsequently executed.

              /*** A Hack of Sorts to Open Dialogs onLoad ***/
              if (typeof prepopup === 'function') { prepopup(); prepopup = false; }
            }

            return false;
          }
        });

        console.log('Login Finished');

        return false;
      },
      error: function(err,err2,err3) {
        dia.error('The login request could not be sent. Please try again.<br /><br />' + err3 + '<br /><br />' + directory + 'validate.php<br /><br />' + data + '&apiVersion=3');

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

    if (roomId) {
      var encrypt = 'base64',
        lastMessageId;

      if (requestSettings.firstRequest) {
        $.ajax({
          url: directory + 'api/getRooms.php?rooms=' + roomId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
          type: 'GET',
          timeout: 2400,
          cache: false,
          async : false, // We need to complete this request before the next
          success: function(json) {
            active = json.getRooms.rooms;

            for (i in active) {
              lastMessageId = active[i].lastMessageId;

              break;
            }

            return false;
          },
          error: function() {
            dia.error('Failed to obtain current room settings from the server.'); // TODO: Handle gracefully.

            return false;
          }
        });
      }

      if (requestSettings.serverSentEvents) { // Note that the event subsystem __requires__ serverSentEvents for various reasons. If you use polling, these events will no longer be fully compatible.
        var messageSource = new EventSource(directory + 'apiStream/messageStream.php?roomId=' + roomId + '&lastEvent=' + requestSettings.lastEvent + '&lastMessage=' + requestSettings.lastMessage + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId);

        var eventSource = new EventSource(directory + 'apiStream/eventStream.php?roomId=' + roomId + '&lastEvent=' + requestSettings.lastEvent + '&lastMessage=' + requestSettings.lastMessage + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId);

        console.log('Starting EventSource; roomId: ' + roomId + '; lastEvent: ' + requestSettings.lastEvent + '; lastMessage: ' + requestSettings.lastMessage);

        messageSource.addEventListener('time', function(e) {
          console.log('The current time is: ' + e.data);
          return false;
        }, false);

        messageSource.addEventListener('message', function(e) {
          active = JSON.parse(e.data);

          var messageId = Number(active.messageData.messageId);

          console.log('Event (New Message): ' + messageId);

          data = messageFormat(active, 'list');

          if ($.inArray(messageId, messageIndex) > -1) { } // Double post hack
          else { newMessage(data, messageId); }

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
        $.ajax({
          url: directory + 'api/getMessages.php?roomId=' + roomId + '&messageHardLimit=100&watchRooms=1&activeUsers=1' + (requestSettings.firstRequest ? '&archive=1&messageIdEnd=' + lastMessageId : '&messageIdStart=' + (requestSettings.lastMessage + 1)) + (requestSettings.longPolling ? '&longPolling=true' : '') + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
          type: 'GET',
          timeout: requestSettings.timeout,
          contentType: "text/json; charset=utf-8",
          dataType: "json",
          cache: false,
          success: function(json) {
            var errStr = json.getMessages.errStr,
              errDesc = json.getMessages.errDesc,
              sentUserId = 0,
              messageCount = 0;

            if (errStr) {
              sentUserId = json.getMessages.activeUser.userId;

              if (errStr === 'noperm') {
                roomId = false; // Clear the internal roomId.

                if (sentUserId) { popup.selectRoom(); dia.error('You have been restricted access from this room. Please select a new room.'); } // You are still login, but permission has been denied for whatever reason.
                else { popup.login(); dia.error('You are no longer logged in. Please log-in.'); } // If the API no longer recognises the login, prompt a relogin.
              }
              else {
                roomId = false;
                dia.error(errDesc);
              }
            }
            else {
              requestSettings.totalFails = 0;
              var notifyData = '',
                activeUserHtml = [];

              $('#activeUsers').html(''); // Clear the active users box.

              active = json.getMessages.activeUsers;

              for (i in active) { // Update active users box.
                var userName = active[i].userName,
                  userId = active[i].userId,
                  userGroup = active[i].userGroup,
                  startTag = active[i].startTag,
                  endTag = active[i].endTag;

                activeUserHtml.push('<span class="userName" data-userId="' + userId + '">' + startTag + '<span class="username">' + userName + '</span>' + endTag + '</span>');
              }

              $('#activeUsers').html(activeUserHtml.join(', '));
              contextMenuParseUser('#activeUsers');

              active = json.getMessages.messages;

              for (i in active) {
                var messageId = Number(active[i].messageData.messageId);
                data = messageFormat(active[i], 'list');

                if ($.inArray(messageId, messageIndex)) { } // Double post hack
                else { newMessage(data, messageId); }

                messageCount++;
              }

              if (requestSettings.longPolling) { requestSettings.timeout = 100000; timers.t1 = setTimeout(standard.getMessages, 50); } // TODO: If longPolling were to fail, we'd be screwed. Examine how to handle the possibility to longPolling erroring on the server side without reporting this.
              else {                             requestSettings.timeout = 2400;   timers.t1 = setTimeout(standard.getMessages, 2500); }
            }

            requestSettings.firstRequest = false;

            return false;
          },
          error: function(err) {
            console.log('Requesting messages for ' + roomId + '; failed: ' + err + '.');
            var wait;

            if (requestSettings.longPolling) { timers.t1 = setTimeout(standard.getMessages, 50); } // Begin again without delay.
            else {
              requestSettings.totalFails += 1; // Increase total fail count.

              // Delays progressively become greater.
              if (requestSettings.totalFails > 10) {     wait = 30000; requestSettings.timeout = 29900; } // TODO: Add indicator.
              else if (requestSettings.totalFails > 5) { wait = 10000; requestSettings.timeout = 9900; } // TODO: Add indicator.
              else {                                     wait = 5000;  requestSettings.timeout = 4900; }

              timers.t1 = setTimeout(standard.getMessages, wait);
            }

            return false;
          }
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

    return false;
  },


  changeRoom : function(roomIdLocal) {
    if (!roomIdLocal) {
      return false;
    }

    if (roomIdLocal.toString().substr(0,1) === 'p') { // If the roomId string corresponds to a private room, we must query getPrivateRoom, among other things. [[TODO: windowDynaLinks]]
      $.ajax({
        url: directory + 'api/getPrivateRoom.php?users=' + roomIdLocal.toString().substr(1) + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
        timeout: 5000,
        type: 'GET',
        cache: false,
        success: function(json) {
          active = json.getPrivateRoom.room;
          var users = active.roomUsers,
            roomUsers = [];


          for (i in users) { // Run through the user list return by getPrivateRooms
            var userName = users[i].userName,
              userFormatStart = users[i].userFormatStart,
              userFormatEnd = users[i].userFormatEnd;

            roomUsers.push(userName);
          }

          if (roomUsers) {
            var roomName = 'Conversation Between: ' + roomUsers.join(', '); // Set the room name to list the users conversing.
            roomId = 'p' + active.uniqueId; // Set the internal roomId to the uniqueId required by all API calls outside of getPrivateRoom.

            $('#roomName').html(roomName);
            $('#topic').html(''); // Clear the topic.
            $('#messageList').html(''); // Clear the messsage list.

            enableSender();

            /*** Get Messages ***/
            $(document).ready(function() {
              requestSettings.firstRequest = true;
              requestSettings.lastMessage = 0;
              messageIndex = [];

              standard.getMessages();

              windowDraw();
              windowDynaLinks();
            });
          }
          else {
            dia.error('You are not allowed to talk to that user.');
          }
        },
        error: function() {
          dia.error('Could not fetch room data. Action cancelled.'); // TODO: Handle Gracefully

          return false;
        }
      });
    }
    else { // Normal procedure otherwise.
      $.ajax({
        url: directory + 'api/getRooms.php?rooms=' + roomIdLocal + '&permLevel=view&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
        timeout: 5000,
        type: 'GET',
        cache: false,
        success: function(json) {
          active = json.getRooms.rooms;

          for (i in active) {
            var roomName = active[i].roomName,
              roomId2 = active[i].roomId,
              roomTopic = active[i].roomTopic,
              permissions = active[i].permissions;

            if (!permissions.canView) { // If we can not view the room
              roomId = false; // Set the internal roomId false.
              popup.selectRoom(); // Prompt the user to select a new room.
              dia.error('You have been restricted access from this room. Please select a new room.');
            }
            else if (!permissions.canPost) { // If we can view, but not post
              dia.error('You are not allowed to post in this room. You will be able to view it, though.');

              disableSender();
            }
            else { // If we can both view and post.
              enableSender();
            }

            if (permissions.canView) { // If we can view the room...
              roomId = roomId2;

              $('#roomName').html(roomName); // Update the room name.
              $('#topic').html(roomTopic); // Update the room topic.
              $('#messageList').html(''); // Clear the message list.


              /*** Get Messages ***/
              $(document).ready(function() {
                requestSettings.firstRequest = true;
                requestSettings.lastMessage = 0;
                messageIndex = [];

                standard.getMessages();

                windowDraw();
                windowDynaLinks();
              });
            }

            break;
          }
        },
        error: function() {
          dia.error('Could not fetch room data. Action cancelled.'); // TODO: Handle gracefully

          return false;
        }
      });
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

    return false;
  },

  unfavRoom : function(roomIdLocal) {
    $.post(directory + 'api/editRoomLists.php', 'action=remove&roomListName=favRooms&roomIds=' + roomIdLocal + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json', function(json) {
      return false;
    });

    return false;
  },

  privateRoom : function(userLocalId) {
    userLocalId = Number(userLocalId);

    if (userLocalId === userId) { dia.error('You can\'t talk to yourself...'); }
    else if (!userLocalId) { dia.error('You have not specified a user.'); }
    else if (!userPermissions.privateRoom) { dia.error('You do not have permission to talk to users privately.'); }
    else {
    }

    return false;
  },


  kick : function(userId, roomId, length) {
    $.post(directory + 'api/moderate.php', 'action=kickUser&userId=' + userId + '&roomId=' + roomId + '&length=' + length + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json', function(json) {
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