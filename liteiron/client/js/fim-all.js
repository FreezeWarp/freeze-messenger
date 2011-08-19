/* FreezeMessenger Copyright © 2011 Joseph Todd Parsons

* This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

* This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

* You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>. */

/* Global Definitions
* These are used throughout all other Javascript files, so are defined before all other FIM-specific files.


* Needed Changes:
  * Consistency in use of templates+raw HTML.
  * Password Encryption */






/*********************************************************
************************ START **************************
******************** Base Variables *********************
*********************************************************/


/* Requirements */

if (false === ('JSON' in window)) {
  window.location.href = 'browser.php';

  throw new Error('Your browser does not seem to support JSON objects. The script has exited.');
}
else if (false === ('btoa' in window)) {
  window.location.href = 'browser.php';

  throw new Error('Your browser does not seem to support Base64 operations. The script has exited.');
}
else if (false === ('encodeURIComponent' in window)) {
  window.location.href = 'browser.php';

  throw new Error('Your browser does not seem to support encodeURI operations. The script has exited.');
}
else if (false === ('onhashchange' in window)) {
  window.location.href = 'browser.php';

  throw new Error('Your browser does not seem to support onhashchange operations. The script has exited.');
}



/* Common Variables */

var userId, // The user ID who is logged in.
  roomId, // The ID of the room we are in.
  sessionHash, // The session hash of the active user.
  anonId, // ID used to represent anonymous posters.
  prepopup;



/* Function-Specific Variables */

window.isBlurred = false; // By default, we assume the window is active and not blurred.
var topic,
  favicon = $('#favicon').attr('href'),
  uploadSettings = {}, // Object
  requestSettings = {
    longPolling : false, // We may set this to true if the server supports it.
    timeout : 2400, // We may increase this dramatically if the server supports longPolling.
    firstRequest : true,
    totalFails : 0,
    lastMessage : 0,
    lastEvent : 0
  },
  timers = {t1 : false}; // Object



/* Objects for Cleanness, Caching. */

var roomRef = {}, // Object
  roomIdRef = {}, // Object
  roomList = [], // Array
  modRooms = {}, // Object // Rooms which the user has special permissions in.

  userRef = {}, // Object
  userIdRef = {}, // Object
  userList = [], // Array

  groupRef = {}, // Object
  groupList = [], // Array
  groupIdRef = {}, // Object

  fontIdRef = {}, // Object

  messageIndex = [], // Array

  roomUlFavHtml = '',
  roomUlMyHtml = '',
  roomUlPrivHtml = '',
  roomUlHtml = '',
  ulText = '',
  roomTableHtml = '',
  roomSelectHtml = '',

  userSelectHtml = '',

  fontSelectHtml = '',

  active = {}, // Object which will be used to store various JSON results
  uploadFileTypes = {};



/* Get Cookies */

// Theme (goes into effect in document.ready)
var theme = $.cookie('fim3_theme');

if (!theme) {
  theme = 'cupertino';
}

// Font Size (goes into effect in document.ready)
var fontsize = $.cookie('fim3_fontsize');

// Settings Bitfield (goes into effect all over the place)
if ($.cookie('fim3_settings') === null) {
  var settingsBitfield = 8192;
}
else if (Number($.cookie('fim3_settings'))) {
  var settingsBitfield = Number($.cookie('fim3_settings'));
}
else {
  var settingsBitfield = 0;
  $.cookie('fim3_settings',0);
}

// Audio File (a hack I placed here just for fun)
if (typeof Audio !== 'undefined') {
  var snd = new Audio();

  if ($.cookie('fim3_audioFile') !== null) {
    audioFile = $.cookie('fim3_audioFile');
  }
  else {
    if (snd.canPlayType('audio/ogg; codecs=vorbis')) {
      audioFile = 'images/beep.ogg';
    }
    else if (snd.canPlayType('audio/mp3')) {
      audioFile = 'images/beep.mp3';
    }
    else if (snd.canPlayType('audio/wav')) {
      audioFile = 'images/beep.wav';
    }
    else {
      audioFile = '';

      console.log('Audio Disabled');
    }
  }

  snd.setAttribute('src', audioFile);

  // Audio Volume
  if ($.cookie('fim3_audioVolume') !== null) {
    snd.volume = $.cookie('fim3_audioVolume') / 100;
  }
  else {
    snd.volume = .5;
  }
}
else {
  snd = {
    play : function() { return false; },
    volume : 0
  }
}

/* Get the absolute API path.
* TODO: Define this in a more "sophisticated manner". */

var directoryPre = window.location.pathname;
directoryPre = directoryPre.split('/');
directoryPre.pop();
directoryPre.pop();
directoryPre = directoryPre.join('/');

var directory = directoryPre + '/';
var currentLocation = window.location.origin + directory + 'webpro/';


/*********************************************************
************************* END ***************************
******************** Base Variables *********************
*********************************************************/








/*********************************************************
************************ START **************************
******************* Static Functions ********************
*********************************************************/

function urlencode(str) {
  return encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+');
}

function toBottom() {
  document.getElementById('messageList').scrollTop = document.getElementById('messageList').scrollHeight;

  return false;
}

function faviconFlash() {
  if ($('#favicon').attr('href') === 'images/favicon.ico') {
    $('#favicon').attr('href', 'images/favicon2.ico');
  }
  else {
    $('#favicon').attr('href', 'images/favicon.ico');
  }

  return false;
}

function messageFormat(json, format) {
  var mjson = json.messageData,
    ujson = json.userData,
    data,
    text = mjson.messageText.htmlText,
    messageTime = mjson.messageTimeFormatted,
    messageId = mjson.messageId,

    userName = ujson.userName,
    userId = ujson.userId,
    groupFormatStart = ujson.startTag,
    groupFormatEnd = ujson.endTag,
    avatar = ujson.avatar,

    styleColor = ujson.defaultFormatting.color,
    styleHighlight = ujson.defaultFormatting.highlight,
    styleFontface = ujson.defaultFormatting.fontface,
    styleGeneral = ujson.defaultFormatting.general,
    style = '',

    flag = mjson.flags;

  switch (flag) {
    case 'me':
    text = text.replace(/^\/me/,'');

    if (settings.disableFormatting) {
      text = '<span style="padding: 10px;">* ' + userName + ' ' + text + '</span>';
    }
    else {
      text = '<span style="color: red; padding: 10px; font-weight: bold;">* ' + userName + ' ' + text + '</span>';
    }
    break;

    case 'topic':
    text = text.replace(/^\/me/,'');

    $('#topic').html(text);

    if (settings.disableFormatting) {
      text = '<span style="padding: 10px;">* ' + userName + ' ' + text + '</span>';
    }
    else {
      text = '<span style="color: red; padding: 10px; font-weight: bold;">* ' + userName + ' ' + text + '</span>';
    }
    break;

    case 'image':
    if (settings.disableImage) {
      text = '<a href="' + text + '" target="_BLANK">[Image]</a>';
    }
    else {
      text = '<a href="' + text + '" target="_BLANK"><img src="' + text + '" style="max-width: 250px; max-height: 250px;" /></a>';
    }
    break;

    case 'video':
    if (settings.disableVideo) {
      text = '<a href="' + text + '" target="_BLANK">[Video]</a>';
    }
    else {
      text = '<video src="' + text + '" controls></video><br /><small><a href="'+ text + '">If you can not see the above link, click here.</a></small>';
    }
    break;

    case 'audio':
    if (settings.disableVideo) {
      text = '<a href="' + text + '" target="_BLANK">[Video]</a>';
    }
    else {
      text = '<audio src="' + text + '" controls></video><br /><small><a href="'+ text + '">If you can not see the above link, click here.</a></small>';
    }
    break;

    case 'youtube':
    if (text.match(/http\:\/\/(www\.|)youtube\.com\/(.*?)(\?|\&)w=([a-zA-Z0-9]+)/) !== null) {
      var code = text.replace(/http\:\/\/(www\.|)youtube\.com\/(.*?)(\?|\&)w=([a-zA-Z0-9]+)/i, "$4");
    }
    else if (text.match(/http\:\/\/(www\.|)youtu\.be\/([a-zA-Z0-9]+)/) !== null) {
      var code = text.replace(/http\:\/\/(www\.|)youtu\.be\/([a-zA-Z0-9]+)/i, "$2");
    }
    else {
      var code = false;
      text = '<span style="color: red; font-style: oblique;">[Invalid Youtube Video]</span>';
    }


    if (code) {
      if (settings.disableVideo) {
        text = '<a href="https://www.youtu.be/' + code + '" target="_BLANK">[Youtube Video]</a>';
      }
      else {
        text = '<iframe width="425" height="349" src="https://www.youtube.com/embed/' + code + '?rel=0&wmode=transparent" frameborder="0" allowfullscreen></iframe>';
      }
    }
    break;

    case 'email':
    text = '<a href="mailto: ' + text + '" target="_BLANK">' + text + '</a>';
    break;

    case 'url':
    text = '<a href="' + text + '" target="_BLANK">' + text + '</a>';
    break;

    case '':
    text = text.replace(/((http|https|ftp|data|gopher|sftp|ssh):(\/\/|)(.+?\.|)([a-zA-Z\-]+)\.(aero|asia|biz|cat|com|coop|edu|gov|info|int|jobs|mil|mobi|museum|name|net|org|pro|tel|travel|xxx)((\/)([^ \n\<\>\"]*)([^\?\.\! \n])|))(?!\")(?!\])/,'<a href="$1">$1</a>');

    if (!settings.disableFormatting) {
      style = 'color: rgb(' + styleColor + '); background: rgb(' + styleHighlight + '); font-family: ' + fontIdRef[styleFontface] + ';';

      if (styleGeneral & 256) {
        style += 'font-weight: bold;';
      }
      if (styleGeneral & 512) {
        style += 'font-style: oblique;';
      }
      if (styleGeneral & 1024) {
        style += 'text-decoration: underline;';
      }
      if (styleGeneral & 2048) {
        style += 'text-decoration: line-through;';
      }
    }
    break;
  }

  switch (format) {
    case 'list':
    if (settings.showAvatars) {
      data = '<span id="message' + messageId + '" class="messageLine messageLineAvatar"><span class="userName userNameTable userNameAvatar" data-userId="' + userId + '"><img alt="' + userName + '" src="' + avatar + '" /></span><span style="' + style + '" class="messageText" data-messageid="' + messageId + '"  data-time="' + messageTime + '">' + text + '</span><br />';
    }
    else {
      data = '<span id="message' + messageId + '" class="messageLine">' + groupFormatStart + '<span class="userName userNameTable" data-userId="' + userId + '">' + userName + '</span>' + groupFormatEnd + ' @ <em>' + messageTime + '</em>: <span style="' + style + '" class="messageText" data-messageid="' + messageId + '">' + text + '</span><br />';
    }
    break;
  }


  return data;
}

function fileFormat(container, file) {

}


/* ? */
function newMessage() {
  if (settings.reversePostOrder) {
    toBottom();
  }

  if (window.isBlurred) {
    if (settings.audioDing) {
      snd.play();
    }

    window.clearInterval(timers.t3);
    timers.t3 = window.setInterval(faviconFlash, 1000);

    if (typeof window.external === 'object') {
      if (typeof window.external.msIsSiteMode !== 'undefined' && typeof window.external.msSiteModeActivate !== 'undefined') {
        try {
          if (window.external.msIsSiteMode()) {
            window.external.msSiteModeActivate(); // Task Bar Flashes
          }
        }
        catch(ex) {
          // Ya know, its very weird IE insists on this when the "in" statement works just as well...
        }
      }
    }
  }

  contextMenuParse();
}


/* URL-Defined Actions
* TODO */

function hashParse() {
  var urlHash = window.location.hash,
    urlHashComponents = urlHash.split('#'),
    page = '', // String
    i = 0,
    componentPieces = [],
    messageId = 0;

  for (i = 0; i < urlHashComponents.length; i += 1) {
    if (urlHashComponents[i]) {
      componentPieces = urlHashComponents[i].split('=');
      switch (componentPieces[0]) {
        case 'page':
        page = componentPieces[1];
        break;

        case 'room':
        roomId = componentPieces[1];
        break;

        case 'message':
        messageId = componentPieces[1];
        break;
      }
    }
  }

  if (roomId) {
    if (!requestSettings.firstRequest) {
      standard.changeRoom(roomId); // We only need to call this if we have already obtained messages.
    }
  }
}

if (typeof console !== 'object' || typeof console.log !== 'function') {
  var console = {
    log : function() {
      return false;
    }
  };
}

/*********************************************************
************************* END ***************************
******************* Static Functions ********************
*********************************************************/








/*********************************************************
************************ START **************************
******************* Variable Setting ********************
*********************************************************/

/* Get Server-Specific Variables
* We Should Not Call This Again */

$.ajax({
  url: directory + 'api/getServerStatus.php?fim3_format=json',
  type: 'GET',
  timeout: 1000,
  dataType: 'json',
  success: function(json) {
    requestSettings.longPolling = json.getServerStatus.serverStatus.requestMethods.longPoll;

    if (typeof window.EventSource == 'undefined') {
      requestSettings.serverSentEvents = false;
    }
    else {
      requestSettings.serverSentEvents = json.getServerStatus.serverStatus.requestMethods.serverSentEvents;
    }

    return false;
  },
  error: function() {
    requestSettings.longPolling = false;
    requestSettings.serverSentEvents = false;

    return false;
  }
});



/* Permission Dead Defaults
* Specifically, These All Start False then Change on-Login */
var userPermissions = {
  createRoom : false,
  privateRoom : false
};

var adminPermissions = {
  modPrivs : false,
  modCore : false,
  modUsers : false,
  modImages : false,
  modCensor : false,
  modPlugins : false,
  modTemplates: false,
  modHooks : false,
  modTranslations : false
};



/* Settings
* These Are Set Based on Cookies */
var settings = {
  disableFormatting : (settingsBitfield & 16 ? true : false),
  disableImage : (settingsBitfield & 32 ? true : false),
  disableVideos : (settingsBitfield & 64 ? true : false),
  reversePostOrder : (settingsBitfield & 1024 ? true : false), // Show posts in reverse?
  showAvatars : (settingsBitfield & 2048 ? true : false), // Use the complex document style?
  audioDing : (settingsBitfield & 8192 ? true : false), // Fire an HTML5 audio ding during each unread message?
  disableFx : (settingsBitfield & 16384 ? true : false), // Disable jQuery Effects?
  webkitNotifications : (settingsBitfield & 32768 ? true : false),
  disableRightClick : (settingsBitfield & 65536 ? true : false)
};




/*********************************************************
************************* END ***************************
******************* Variable Setting ********************
*********************************************************/








/*********************************************************
************************ START **************************
******************** Data Population ********************
*********************************************************/

function populate(options) {
  $.when(
    $.ajax({
      url: directory + 'api/getUsers.php?fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
      type: 'GET',
      timeout: 5000,
      cache: false,
      success: function(json) {
        userList = []; // Array // Clear so we don't get repeat values on regeneration.
        userRef = {}; // Object
        userSelectHtml = '';
        active = json.getUsers.users;

        console.log('Users obtained.');
        for (i in active) {
          var userName = active[i].userName,
            userId = active[i].userId;

          userRef[userName] = userId;
          userIdRef[userId] = userName;
          userList.push(userName);
        }

        return false;
      },
      error: function() {
        alert('Users Not Obtained - Problems May Occur');

        return false;
      }
    }),


    $.ajax({
      url: directory + 'api/getRooms.php?permLevel=view&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(json) {
        roomList = []; // Array // Clear so we don't get repeat values on regeneration.
        roomIdRef = {}; // Object
        roomRef = {}; // Object
        roomTableHtml = '';
        roomSelectHtml = '';
        roomUlHtml = '';
        roomUlPrivHtml = '';
        roomUlMyHtml = '';
        roomUlFavHtml = '';

        active = json.getRooms.rooms;

        for (i in active) {
          var roomName = active[i].roomName,
            roomId = active[i].roomId,
            roomTopic = active[i].roomTopic,
            isFav = active[i].favorite,
            isPriv = active[i].optionDefinitions.privateIm,
            isAdmin = active[i].permissions.canAdmin,
            isModerator = active[i].permissions.canModerate,
            messageCount = active[i].messageCount,
            isOwner = (active[i].owner === userId ? true : false),
            ulText = '<li><a href="#room=' + roomId + '">' + roomName + '</a></li>';

          if (isFav) {
            roomUlFavHtml += ulText;
          }
          else if (isOwner && !isPriv) {
            roomUlMyHtml += ulText;
          }
          else if (isPriv) {
            roomUlPrivHtml += ulText;
          }
          else {
            roomUlHtml += ulText;
          }

          roomTableHtml += '<tr id="room' + roomId + '"><td><a href="#room=' + roomId + '">' + roomName + '</a></td><td>' + roomTopic + '</td><td>' + (isAdmin ? '<button data-roomId="' + roomId + '" class="editRoomMulti standard"></button><button data-roomId="' + roomId + '" class="deleteRoomMulti standard"></button>' : '') + '<button data-roomId="' + roomId + '" class="archiveMulti standard"></button><input type="checkbox" ' + (isFav ? 'checked="checked" ' : '') + ' data-roomId="' + roomId + '" class="favRoomMulti" id="favRoom' + roomId + '" /><label for="favRoom' + roomId + '" class="standard"></label></td></tr>';

          roomRef[roomName] = roomId;
          roomIdRef[roomId] = {
            'roomName' : roomName,
            'messageCount' : messageCount
          }
          roomList.push(roomName);

          if (isAdmin) {
            modRooms[roomId] = 2;
          }
          else if (isModerator) {
            modRooms[roomId] = 1;
          }
          else {
            modRooms[roomId] = 0;
          }
        }

        $('#roomListLong > li > ul').html('<li>Favourites<ul>' + roomUlFavHtml + '</ul></li><li>My Rooms<ul>' + roomUlMyHtml + '</ul></li><li>General<ul>' + roomUlHtml + '</ul></li><li>Private<ul>' + roomUlPrivHtml + '</ul></li>');

        $('#roomListShort > ul').html('<li>Favourites<ul>' + roomUlFavHtml + '</ul></li>');

        $('#roomName').html(roomIdRef[roomId].roomName);

        console.log('Rooms obtained.');

        return false;
      },
      error: function() {
        alert('Rooms Not Obtained - Problems May Occur');

        return false;
      }
    }),


    $.ajax({
      url: directory + 'api/getGroups.php?fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(json) {
        console.log('Groups obtained.');

        active = json.getGroups.groups;
        for (i in active) {
          var groupName = active[i].groupName,
            groupId = active[i].groupId;

          groupRef[groupName] = groupId;
          groupIdRef[groupId] = groupName;
          groupList.push(groupName);
        }

        return false;
      },
      error: function() {
        alert('Groups Not Obtained - Problems May Occur');

        return false;
      }
    }),

    $.ajax({
      url: directory + 'api/getFonts.php?fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(json) {
        active = json.getFonts.fonts;

        for (i in active) {
          var fontName = active[i].fontName,
            fontId = active[i].fontId,
            fontGroup = active[i].fontGroup,
            fontData = active[i].fontData;

          fontSelectHtml += '<option value="' + fontId + '" style="' + fontData + '" data-font="' + fontData + '">' + fontName + '</option>';
          fontIdRef[fontId] = fontName;
        }

        return false;
      },
      error: function() {
        dia.error('The list of fonts could not be obtained from the server.');

        return false;
      }
    })
  ).always(function() {
      if (typeof options.callback === 'function') {
        options.callback();
      }

      return true;
  });

  return false;
}

/*********************************************************
************************* END ***************************
******************** Data Population ********************
*********************************************************/








/*********************************************************
************************ START **************************
******************* Content Functions *******************
*********************************************************/

var standard = {
  login : function(options) {
    console.log('Login Initiated');
    var data = '',
      passwordEncrypt = '';


    console.log('Encrypted Password: ' + options.password);


    if (options.start) {
      options.start();
    }


    if (options.userName && options.password) {
      passwordEncrypt = 'plaintext';
      // TODO: Enable for vBulletin
      // var password = md5(password);
      // var passwordEncrypt = 'md5';

      data = 'userName=' + urlencode(options.userName) + '&password=' + urlencode(options.password) + '&passwordEncrypt=' + passwordEncrypt;
    }
    else if (options.userId && options.password) {
      passwordEncrypt = 'plaintext';
      // TODO: Enable for vBulletin
      // var password = md5(password);
      // var passwordEncrypt = 'md5';

      data = 'userId=' + urlencode(options.userId) + '&password=' + urlencode(options.password) + '&passwordEncrypt=' + passwordEncrypt;
    }
    else {
      data = 'apiLogin=1';
    }


    $.when(
      $.ajax({
        url: directory + 'validate.php',
        type: 'POST',
        data: data + '&apiVersion=3&fim3_format=json',
        cache: false,
        timeout: 2500,
        success: function(json) {
          active = json.login;

          var loginFlag = active.loginFlag,
            loginText = active.loginText,
            valid = active.valid,
            userName = active.userData.userName,
            defaultRoomId = active.defaultRoomId,
            banned = active.banned;

          userId = active.userData.userId;
          anonId = active.anonId;
          sessionHash = active.sessionHash;



          $.cookie('fim3_userId', userId, { expires : 14 });
          $.cookie('fim3_password', options.password, { expires : 14 }); // We will encrypt this in B3 or later -- it wasn't a priority for now.



          /* Update Permissions */

          userPermissions = {
            createRoom : active.userPermissions.createRooms,
            privateRoom : active.userPermissions.privateRooms,
            general : active.userPermissions.allowed
          }

          adminPermissions = {
            modPrivs : active.adminPermissions.modPrivs,
            modCore : active.adminPermissions.modCore,
            modUsers : active.adminPermissions.modUsers,
            modTemplates : active.adminPermissions.modTemplates,
            modImages : active.adminPermissions.modImages,
            modCensor : active.adminPermissions.modCensor,
            modHooks : active.adminPermissions.modHooks
          }


          if (banned) { // The user has been banned, so pretty much nothing will work. In some respects, this really only exists for IP bans, but meh.
            dia.error('You have been banned. You will not be able to do anything.');

            userPermissions = {
              createRoom : false,
              privateRoom : false,
              general : false
            }

            adminPermissions = {
              modPrivs : false,
              modCore : false,
              modUsers : false,
              modTemplates : false,
              modImages : false,
              modCensor : false,
              modHooks : false
            }
          }
          else if (valid === true) {
            if (options.showMessage) {
              // Display Dialog to Notify User of Being Logged In
              if (!userPermissions.general) {
                dia.info('You are now logged in as ' + userName + '. However, you are not allowed to post and have been banned by an administrator.', 'Logged In');
              }
              else {
                dia.info('You are now logged in as ' + userName + '.', 'Logged In');
              }
            }

            $('#loginDialogue').dialog('close'); // Close any open login forms.

            console.log('Login valid. Session hash: ' + sessionHash + '; User ID: ' + userId);
          }
          else {
            switch (loginFlag) {
              case 'INVALID_LOGIN':
              dia.error("The server did not accept the login, but did not specify why.")
              break;

              case 'PASSWORD_ENCRYPT':
              dia.error("The form encryption used was not accepted by the server.");
              break;

              case 'BAD_USERNAME':
              dia.error("A valid user was not provided.");
              break;

              case 'BAD_PASSWORD':
              dia.error("The password was incorrect.");
              break;

              case 'API_VERSION_STRING':
              dia.error("The server was unable to process the API version string specified.");
              break;

              case 'DEPRECATED_VERSION':
              dia.error("The server will not accept this client because it is of a newer version.");
              break;

              case 'INVALID_SESSION':
              sessionHash = '';
              break;

              default:
              break;
            }

            console.log('Login Invalid');
          }


          if (!roomId) {
            if (!defaultRoomId) {
              roomId = 1;
            }

            else {
              roomId = defaultRoomId;
            }
          }


          if (!anonId && !userId) {
            $('#messageInput').attr("disabled","disabled"); // The user is not able to post.
          }


          return false;
        },
        error: function(err,err2,err3) {
          dia.error("The login request could not be sent. Please try again.<br /><br />" + err3 + "<br /><br />" + directory + "validate.php<br /><br />" + data + '&apiVersion=3');

          return false;
        }
      })
    ).always(function() {
      if (options.finish) {
        options.finish();
      }

      populate({
        callback : function() {
          windowDraw();

          /* Select Room */
          standard.changeRoom(roomId);

          return false;
        }
      });

      console.log('Login Finished');

      return false;
    });

    return false;
  },


  logout : function() {
    $.cookie('fim3_userId', null);
    $.cookie('fim3_password', null);

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
            dia.error('Failed to obtain current room settings from server.');

            return false;
          }
        });
      }

      if (requestSettings.serverSentEvents) {console.log(requestSettings);
        var source = new EventSource(directory + 'eventStream.php?roomId=' + roomId + '&lastEvent=' + requestSettings.lastEvent + '&lastMessage=' + requestSettings.lastMessage + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId);

        source.addEventListener('message', function(e) {
          active = JSON.parse(e.data);

          var messageId = Number(active.messageData.messageId);

          console.log('Event (New Message): ' + messageId);

          data = messageFormat(active, 'list');

          messagePopup(data)


          if (messageIndex[messageId]) {
            // Double post hack
          }
          else {
            if (settings.reversePostOrder) {
              $('#messageList').append(data);
            }
            else {
              $('#messageList').prepend(data);
            }

            if (messageId > requestSettings.lastMessage) {
              requestSettings.lastMessage = messageId;
            }

            messageIndex.push(requestSettings.lastMessage);

            if (messageIndex.length === 100) {
              var messageOut = messageIndex[0];
              $('#message' + messageOut).remove();
              messageIndex = messageIndex.slice(1,99);
            }
          }

          newMessage();

          return false;
        }, false);

        source.addEventListener('topicChange', function(e) {
          var active = JSON.parse(e.data);

          $('#topic').html(active.param1);
          console.log('Event (Topic Change): ' + active.param1);

          requestSettings.lastEvent = active.eventId;

          return false;
        }, false);

        source.addEventListener('missedMessage', function(e) {
          var active = JSON.parse(e.data);

          requestSettings.lastEvent = active.eventId;
          $.jGrowl('Missed Message', 'New messages have been made in:<br /><br /><a href="#room=' + active.roomId + '">' + active.roomName + '</a>');
          console.log('Event (Missed Message): ' + active.messageId);

          return false;
        }, false);

        source.addEventListener('deletedMessage', function(e) {
          var active = JSON.parse(e.data);

          $('#topic').html(active.param1);
          console.log('Event (Topic Change): ' + active.param1);

          requestSettings.lastEvent = active.eventId;

          return false;
        }, false);

        source.addEventListener('open', function(e) {
          // Connection was opened.
        }, false);

        source.addEventListener('error', function(e) {
          if (e.eventPhase == EventSource.CLOSED) {
            // Connection was closed.
          }
        }, false);

      }
      else {
        $.ajax({
          url: directory + 'api/getMessages.php?rooms=' + roomId + '&messageLimit=100&watchRooms=1&activeUsers=1' + (requestSettings.firstRequest ? '&archive=1&messageIdEnd=' + lastMessageId : '&messageIdStart=' + (requestSettings.lastMessage + 1)) + (requestSettings.longPolling ? '&longPolling=true' : '') + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
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
                roomId = false;

                if (sentUserId) {
                  popup.selectRoom();

                  dia.error('You have been restricted access from this room. Please select a new room.');
                }
                else {
                  popup.login();

                  dia.error('You are no longer logged in. Please log-in.');
                }
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




              $('#activeUsers').html('');

              active = json.getMessages.activeUsers;

              for (i in active) {
                var userName = active[i].userName,
                  userId = active[i].userId,
                  userGroup = active[i].userGroup,
                  startTag = active[i].startTag,
                  endTag = active[i].endTag;

                activeUserHtml.push('<span class="userName" data-userId="' + userId + '">' + startTag + '<span class="username">' + userName + '</span>' + endTag + '</span>');
              }

              $('#activeUsers').html(activeUserHtml.join(', '));



              active = json.getMessages.messages;

              for (i in active) {
                var messageId = Number(active[i].messageData.messageId);
                data = messageFormat(active[i], 'list');

                if (messageIndex[messageId]) {
                  // Double post hack
                }
                else {
                  if (settings.reversePostOrder) {
                    $('#messageList').append(data);
                  }
                  else {
                    $('#messageList').prepend(data);
                  }

                  if (messageId > requestSettings.lastMessage) {
                    requestSettings.lastMessage = messageId;
                  }

                  messageIndex.push(requestSettings.lastMessage);

                  if (messageIndex.length === 100) {
                    var messageOut = messageIndex[0];
                    $('#message' + messageOut).remove();
                    messageIndex = messageIndex.slice(1,99);
                  }
                }

                messageCount++;
              }


              if (messageCount > 0) {
                newMessage();
              }

              if (requestSettings.longPolling) {
                timers.t1 = setTimeout(standard.getMessages,50);
              }
              else {
                requestSettings.timeout = 2400;
                timers.t1 = setTimeout(standard.getMessages,2500);
              }
            }

            requestSettings.firstRequest = false;

            return false;
          },
          error: function(err) {
            console.log('Requesting messages for ' + roomId + '; failed: ' + err + '.');
            var wait;

            if (requestSettings.longPolling) {
              timers.t1 = setTimeout(standard.getMessages,50);
            }
            else {
              requestSettings.totalFails += 1;

              if (!requestSettings.longPolling) {
                if (requestSettings.totalFails > 10) {
                  wait = 30000;
                  requestSettings.timeout = 29900;

                  // TODO: Add indicator.
                }
                else if (requestSettings.totalFails > 5) {
                  wait = 10000;
                  requestSettings.timeout = 9900;

                  // TODO: Add indicator.
                }
                else {
                  wait = 5000;
                  requestSettings.timeout = 4900;
                }
              }

              timers.t1 = setTimeout(standard.getMessages,wait);
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


  sendMessage : function(message,confirmed,flag) {
    if (!flag) {
      flag = '';

      if (message.match(/http\:\/\/(www\.|)youtu\.be\/(.*?)(\?|\&)w=([a-zA-Z0-9]+)/) !== null) {
        flag = 'youtube';
      }
      else if (message.match(/http\:\/\/(www\.|)youtu\.be\/([a-zA-Z0-9]+)/) !== null) {
        flag = 'youtube';
      }
    }

    if (!roomId) {
      popup.selectRoom();
    }
    else {
      confirmed = (confirmed === 1 ? 1 : '');

      $.ajax({
        url: directory + 'api/sendMessage.php?fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
        type: 'POST',
        data: 'roomId=' + roomId + '&confirmed=' + confirmed + '&message=' + urlencode(message) + '&flag=' + flag,
        cache: false,
        timeout: 5000,
        success: function(json) {
          console.log('Message sent.');

          var errStr = json.sendMessage.errStr,
            errDesc = json.sendMessage.errDesc;

          switch (errStr) {
            case '':
            break;

            case 'badroom':
            dia.error("A valid room was not provided.");
            break;

            case 'badmessage':
            dia.error("A valid message was not provided.");
            break;

            case 'spacerrDesc':
            dia.error("Too... many... spaces!");
            break;

            case 'noperm':
            dia.error("You do not have permission to post in this room.");
            break;

            case 'blockcensor':
            dia.error(errDesc);
            break;

            case 'confirmcensor':
            dia.error(errDesc + '<br /><br /><button type="button" onclick="$(this).parent().dialog(&apos;close&apos;);">No</button><button type="button" onclick="standard.sendMessage(&apos;' + escape(message) + '&apos;,1' + (flag ? ', ' + flag : '') + '); $(this).parent().dialog(&apos;close&apos;);">Yes</button>');
            break;
          }

          return false;
        },
        error: function() {
          console.log('Message error.');

          if (settings.reversePostOrder) {
            $('#messageList').append('Your message, "' + message + '", could not be sent and will be retried.');
          }
          else {
            $('#messageList').prepend('Your message, "' + message + '", could not be sent and will be retried.');
          }

          window.setTimeout(function() {
            standard.sendMessage(message)
          },5000);

          return false;
        }
      });
    }

    return false;
  },


  changeRoom : function(roomLocalId) {
    console.log('Changing Room: ' + roomLocalId + '; Detected Name: ' + roomIdRef[roomLocalId].roomName);

    roomId = roomLocalId;
    $('#roomName').html(roomIdRef[roomId].roomName);
    $('#messageList').html('');

    windowDraw();


    /*** Get Messages ***/

    $(document).ready(function() {
      // If getMessages is called before the document is loaded, and we are using server-sent events or longpolling, then WebKit browsers will go beserk. It's an annoying bug, and the setTimeout merely serves as an inconsistent hack, but meh.
      timers.t1 = setTimeout(standard.getMessages,500);

      return false;
    });

    return false;
  },


};

/*********************************************************
************************* END ***************************
******************* Content Functions *******************
*********************************************************/







/*********************************************************
************************ START **************************
********** Silent Init Uses of Standard Methods *********
*********************************************************/

if (typeof window.onhashchange !== 'undefined') {
  window.onhashchange = function() {
    hashParse();

    return true;
  }
}

hashParse();

/*********************************************************
************************* END ***************************
********** Silent Init Uses of Standard Methods *********
*********************************************************/







/*********************************************************
************************ START **************************
************** Repeat-Action Popup Methods **************
*********************************************************/

popup = {
  /*** START Login ***/

  login : function() {
    $.get('template.php', 'template=login',function(data) {
      dia.full({
        content : data,
        title : 'Login',
        id : 'loginDialogue',
        width : 600,
        oF : function() {
          $("#loginForm").submit(function() {
            var userName = $('#loginForm > #userName').val(),
              password = $('#loginForm > #password').val(),
              rememberMe = $('#loginForm > #rememberme').is('checked');

            standard.login({
              userName : userName,
              password : password,
              showMessage : true,
              rememberMe : rememberMe
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

          return false;
        }
      });

      return false;
    });
  },

  /*** END Login ***/






};

/*********************************************************
************************* END ***************************
************** Repeat-Action Popup Methods **************
*********************************************************/








/*********************************************************
************************ START **************************
********* DOM Event Handling & Window Painting **********
*********************************************************/

function windowDraw() {
  console.log('Redrawing window.');


  if (settings.disableFx) {
    jQuery.fx.off = true;
  }


  if (settings.showAvatars) {
    $('.messageText').tipTip({
      attribute: 'data-time'
    });
  }


  $('.userName').ezpz_tooltip({
    contentId: 'tooltext',
    beforeShow: function(content,el) {
      var thisid = $(el).attr('data-userId');

      if (thisid != $('#tooltext').attr('data-lastuserId')) {
        $('#tooltext').attr('data-lastuserId',thisid);
        $.get(directory + 'api/getUsers.php?users=' + thisid + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId, function(json) {
          active = json.getUsers.users;

          for (i in active) {
            var userName = active[i].userName,
              userId = active[i].userId,
              startTag = active[i].startTag,
              endTag = active[i].endTag,
              userTitle = active[i].userTitle,
              posts = active[i].postCount,
              joinDate = active[i].joinDateFormatted,
              avatar = active[i].avatar;
          }

          content.html('<div style="width: 400px;">' + (avatar.length > 0 ? '<img alt="" src="' + avatar + '" style="float: left;" />' : '') + '<span class="userName" data-userId="' + userId + '">' + startTag + userName + endTag + '</span>' + (userTitle.length > 0 ? '<br />' + userTitle : '') + '<br /><em>Posts</em>: ' + posts + '<br /><em>Member Since</em>: ' + joinDate + '</div>');

          return false;
        });
      }

      return false;
    }
  });


  contextMenuParse();


  /*** Create the Accordion Menu ***/

  $('#menu').accordion({
    autoHeight: false,
    navigation: true,
    clearStyle: true
  });



  /*** Draw the chatbox. ***/
  if (roomId && (userId | anonId)) {
    $('#messageInput').removeAttr("disabled"); // The user is able to post.
  }
  else {
    $('#messageInput').attr("disabled","disabled"); // The user is able to post.
  }

  $("#imageUploadSubmitButton").button("option", "disabled", true);



  $('#icon_note, #messageArchive, a#editRoom').unbind('click'); // Cleanup



  /*** Archive ***/

  $('#icon_note, #messageArchive').bind('click',function() {
    popup.archive({roomId : roomId});

    return false;
  });



  /*** Edit Room ***/

  $('a#editRoom').bind('click',function() {
    popup.editRoom(roomId);

    return false;
  });



  windowResize();


  return false;
}




$(document).ready(function() {
//  $('head').append('<link rel="stylesheet" id="stylesjQ" type="text/css" href="client/css/' + theme + '/jquery-ui-1.8.13.custom.css" /><link rel="stylesheet" id="stylesFIM" type="text/css" href="client/css/' + theme + '/fim.css" /><link rel="stylesheet" type="text/css" href="client/css/stylesv2.css" />');

  if (fontsize) {
    $('body').css('font-size',fontsize + 'em');
  }


  if ($.cookie('fim3_userId') > 0) {
    standard.login({
      userId : $.cookie('fim3_userId'),
      password : $.cookie('fim3_password'),
      finish : function() {
        if (!userId) { // The user is not actively logged in.
          popup.login();
        }
      }
    });
  }
  else {
    popup.login();
  }



  /*** Trigger Login ***/

  $('#login').bind('click',function() {
    popup.login();

    return false;
  });



  /*** Trigger Logout */

  $('#logout').bind('click',function() {
    standard.logout();
    popup.login();

    return false;
  });



  /*** WIP ***/

  $('#showMoreRooms').bind('click',function() {
    $('#roomListShort').slideUp();
    $('#roomListLong').slideDown();

    return false;
  });

  $('#showFewerRooms').bind('click',function() {
    $('#roomListLong').slideUp();
    $('#roomListShort').slideDown();

    return false;
  });



  $('#sendForm').bind('submit',function() {
    var message = $('textarea#messageInput').val();

    if (message.length === 0) {
      dia.error('Please enter your message.');
    }
    else {
      standard.sendMessage(message);
      $('textarea#messageInput').val('');
    }

    return false;
  });



  /* We haven't a clue what this does... */

  if (typeof prepopup === "function") {
    prepopup();
  }



  /* Window Manipulation (see below) */

  $(window).bind('resize', windowResize);
  window.onblur = function() { windowBlur(); }
  window.onfocus = function() { windowFocus(); }



  return false;
});

/*********************************************************
************************* END ***************************
********* DOM Event Handling & Window Painting **********
*********************************************************/








/*********************************************************
************************ START **************************
***** Window Manipulation and Multi-Window Handling *****
*********************************************************/

function windowResize() {
  var windowWidth = document.documentElement.clientWidth; // Get the browser window "viewport" width, excluding scrollbars.
  var windowHeight = document.documentElement.clientHeight; // Get the browser window "viewport" height, excluding scrollbars.


  $('#messageList').css('min-height',(windowHeight - 240)); // Set the message list height to fill as much of the screen that remains after the textarea is placed.
  $('#messageList').css('max-width',((windowWidth - 10) * .75)); // Prevent box-stretching. This is common on... many chats.


  /* Body Padding: 10px
    * Right Area Width: 75%
    * "Enter Message" Table Padding: 10px
    *** TD Padding: 2px (on Standard Styling)
    * Message Input Text Area Padding: 3px */
  $('#messageInput').css('width',(((windowWidth - 10) * .75) - 10 - 2)); // Set the messageInput box to fill width.


  $('body').css('height',windowHeight); // Set the body height to equal that of the window; this fixes many gradient issues in theming.


  return false;
}

function windowBlur() {
  window.isBlurred = true;

  return false;
}

function windowFocus() {
  window.isBlurred = false;
  window.clearInterval(timers.t3);
  $('#favicon').attr('href',favicon);

  return false;
}

/*********************************************************
************************* END ***************************
***** Window Manipulation and Multi-Window Handling *****
*********************************************************/