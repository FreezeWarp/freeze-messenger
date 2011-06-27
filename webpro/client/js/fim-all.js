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
  * Consistency in use of templates+raw HTML. */






/*********************************************************
************************ START **************************
******************** Base Variables *********************
*********************************************************/


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
    lastMessage : 0
  },
  timers = {}; // Object



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

  messageIndex = []; // Array


var roomUlFavHtml = '',
  roomUlMyHtml = '',
  roomUlPrivHtml = '',
  roomUlHtml = '',
  ulText = '',
  roomTableHtml = '',
  roomSelectHtml = '',

  userSelectHtml = '';



/* Get Cookies */

var layout = $.cookie('fim3_layout'), // TODO
  themeId = Number($.cookie('fim3_themeId'));


if (Number($.cookie('fim3_settings'))) {
  var settingsBitfield = Number($.cookie('fim3_settings'));
}
else {
  var settingsBitfield = 0;
  $.cookie('fim3_settings',0);
}

if ($.cookie('fim3_sessionHash')) {
  sessionHash = $.cookie('fim3_sessionHash');
  userId = $.cookie('fim3_userId');
}



/* Get the absolute API path.
* TODO: Define this in a more "sophisticated manner". */

var directoryPre = window.location.pathname;
directoryPre = directoryPre.split('/');
directoryPre.pop();
directoryPre.pop();
directoryPre = directoryPre.join('/');

var directory = directoryPre + '/';
var currentLocation = window.location.origin + directory + 'interface/';


/*********************************************************
************************* END ***************************
******************** Base Variables *********************
*********************************************************/








/*********************************************************
************************ START **************************
******************* Static Functions ********************
*********************************************************/

function unxml(data) {
  return data.replace(/\&lt\;/g, '<', data).replace(/\&gt\;/g, '>', data).replace(/\&apos\;/g, "'", data).replace(/\&quot\;/g, '"', data);
}

function urlEncode(data) {
  return data.replace(/\+/g, '%2b').replace(/\&/g, '%26').replace(/\%/g, '%25').replace(/\n/g, '%20');
}

function toBottom() {
  document.getElementById('messageList').scrollTop = document.getElementById('messageList').scrollHeight;

  return false;
}

function faviconFlash() {
  if ($('#favicon').attr('href') === favicon) {
    $('#favicon').attr('href','images/favicon2.gif');
  }
  else {
    $('#favicon').attr('href',favicon);
  }

  return false;
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

  for (i; i < urlHashComponents.length; i += 1) {
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

  switch (page) {
    case 'archive':
    prepopup = function() {
      popup.archive({
        'roomId' : roomId,
        'idMin' : messageId - 1
      });
    };
    break;
    default:
    if (roomId) {
      standard.changeRoom(roomId);
    }
    break;
  }
}



/* Dia Object for jQueryUI */

var dia = {
  error : function(message) {
    $('<div style="display: none;">' + message + '</div>').dialog({
      title : 'Error',
      modal : true,
      buttons: {
        Close: function() {
          $( this ).dialog( "close" );

          return false;
        }
      }
    });
  },

  info : function(message, title) {
    $('<div style="display: none;">' + message + '</div>').dialog({
      title : title,
      modal : true,
      buttons: {
        Okay : function() {
          $(this).dialog( "close" );

          return false;
        }
      }
    });
  },

  confirm : function(options) {
    $('<div id="dialog-confirm"><span class="ui-icon ui-icon-alert" style="float: left; margin: 0px 7px 20px 0px;"></span>' + options.text + '</div>').dialog({
      resizable: false,
      height: 240,
      modal: true,
      hide: "puff",
      buttons: {
        Confirm: function() {
          if (typeof options['true'] !== 'undefined') {
            options['true']();
          }

          $(this).dialog("close");
          return true;
        },
        Cancel: function() {
          if (typeof options['false'] !== 'undefined') {
            options['false']();
          }

          $(this).dialog("close");
          return false;
        }
      }
    });
  },

  // Supported options: autoShow (true), id, content, width (600), oF, cF
  full : function(options) {
    var ajax,
      autoOpen,
      windowWidth = document.documentElement.clientWidth,
      dialog,
      dialogOptions,
      tabsOptions,
      overlay,
      throbber;

    if (options.uri) {
      options.content = '<img src="images/ajax-loader.gif" align="center" />';

      ajax = true;
    }
    else if (!options.content) {
      console.log('No content found for dialog; exiting.');

      return false;
    }

    if (typeof options.autoOpen !== 'undefined' && options.autoOpen === false) {
      autoOpen = false;
    }
    else {
      autoOpen = true;
    }

    if (options.width > windowWidth) {
      options.width = windowWidth;
    }
    else if (!options.width) {
      options.widthwidth = 600;
    }

    dialogOptions = {
      width: options.width,
      title: options.title,
      hide: "puff",
      modal: true,
      buttons : options.buttons,
      autoOpen: autoOpen,
      open: function() {
        if (typeof options.oF !== 'undefined') {
          options.oF();
        }

        return false;
      },
      close: function() {
        $('#' + options.id).empty().remove(); // Housecleaning, needed if we want the next dialouge to work properly.
        if (typeof options.cF !== 'undefined') {
          options.cF();
        }

        return false;
      }
    };

    tabsOptions = {
      selected : options.selectTab
    };


    dialog = $('<div style="display: none;" id="' + options.id +  '">' + options.content + '</div>').appendTo('body');



    if (ajax) {
      overlay = $('<div class="ui-widget-overlay"></div>').appendTo('body').width($(document).width()).height($(document).height());
      throbber = $('<img src="images/ajax-loader.gif" />').appendTo('body').css('position','absolute').offset({ left : (($(window).width() - 220) / 2), top : (($(window).height() - 19) / 2)});

      $.ajax({
        url : options.uri,
        type : "GET",
        timeout : 5000,
        cache : true,
        success : function(content) {
          overlay.empty().remove();
          throbber.empty().remove();

          dialog.html(content);

          if (options.tabs) {
            dialog.tabbedDialog(dialogOptions,tabsOptions);
          }
          else {
            dialog.dialog(dialogOptions);
          }

          windowDraw();

          return false;
        },
        error : function() {
          overlay.empty().remove();
          throbber.empty().remove();

          dialog.dialog('close');

          dia.error('Could not request dialog URI.');

          return false;
        }
      });
    }
    else {
      if (options.tabs) {
        dialog.tabbedDialog(dialogOptions,tabsOptions);
      }
      else {
        dialog.dialog(dialogOptions);
      }

      windowDraw();
    }
  }
};

if (typeof console !== 'object' || typeof console.log !== 'function') {
  var console = {
    log : function() {
      return false;
    }
  };
}

var alert = function(text) {
  dia.info(text,"Alert");
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
  url: directory + 'api/getServerStatus.php',
  type: 'GET',
  timeout: 5000,
  cache: false,
  success: function(xml) {
    requestSettings.longPolling = ($(xml).find('serverStatus > requestMethods > longPoll').text().trim() === 'true' ? true : false);

    return false;
  },
  error: function() {
    requestSettings.longPolling = false;

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
  disableFx : (settingsBitfield & 16384 ? true : false), // Disable jQuery Effects?
  audioDing : (settingsBitfield & 8192 ? true : false), // Fire an HTML5 audio ding during each unread message?
  showAvatars : (settingsBitfield & 2048 ? true : false), // Use the complex document style?
  reversePostOrder : (settingsBitfield & 1024 ? true : false), // Show posts in reverse?
  disableImages : (settingsBitfield & 32 ? true : false),
  disableVideos : (settingsBitfield & 64 ? true : false),
  disableFormatting : (settingsBitfield & 16 ? true : false)
};



/* Apply CSS Styling Dynamically
* Added Bonus: It's Hard Not To Know When the Script is Broken */

var themes = {
  1 : 'ui-darkness',
  2 : 'ui-lightness',
  3 : 'redmond',
  4 : 'cupertino',
  5 : 'dark-hive',
  6 : 'start',
  7 : 'vader',
  8 : 'trontastic',
  9 : 'humanity'
};

var themeName = (themeId ? themes[themeId] : 'cupertino');

$('head').append('<link rel="stylesheet" type="text/css" id="stylesjQ" href="client/css/' + themeName + '/jquery-ui-1.8.13.custom.css" media="screen" />');
$('head').append('<link rel="stylesheet" type="text/css" id="stylesFIM" href="client/css/' + themeName + '/fim.css" media="screen" />');
$('head').append('<link rel="stylesheet" type="text/css" href="client/css/stylesv2.css" media="screen" />');


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
      url: directory + 'api/getUsers.php?fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,
      type: 'GET',
      timeout: 5000,
      cache: false,
      success: function(xml) {
        userList = []; // Array // Clear so we don't get repeat values on regeneration.
        userRef = {}; // Object
        userSelectHtml = '';

        console.log('Users obtained.');

        $(xml).find('user').each(function() {
          var userName = unxml($(this).find('userName').text().trim()),
            userId = Number($(this).find('userId').text().trim());

          userRef[userName] = userId;
          userIdRef[userId] = userName;
          userList.push(userName);
        });

        return false;
      },
      error: function() {
        console.log('Users Not Obtained - Problems May Occur');

        return false;
      }
    }),


    $.ajax({
      url: directory + 'api/getRooms.php?permLevel=view&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(xml) {
        roomList = []; // Array // Clear so we don't get repeat values on regeneration.
        roomIdRef = {}; // Object
        roomRef = {}; // Object
        roomTableHtml = '';
        roomSelectHtml = '';
        roomUlHtml = '';
        roomUlPrivHtml = '';
        roomUlMyHtml = '';
        roomUlFavHtml = '';

        $(xml).find('room').each(function() {
          var roomName = unxml($(this).find('roomName').text().trim()),
            roomId = Number($(this).find('roomId').text().trim()),
            roomTopic = unxml($(this).find('roomTopic').text().trim()),
            isFav = ($(this).find('favorite').text().trim() === 'true' ? true : false),
            isPriv = ($(this).find('optionDefinitions > privateIm').text().trim() === 'true' ? true : false),
            isAdmin = ($(this).find('canAdmin').text().trim() === 'true' ? true : false),
            isModerator = ($(this).find('canModerate').text().trim() === 'true' ? true : false),
            isOwner = (Number($(this).find('owner').text().trim()) === userId ? true : false),
            ulText = '<li><a href="#room=' + roomId + '">' + roomName + '</a></li>';

          if (isFav) {
            roomUlFavHtml += ulText;
          }
          if (isOwner && !isPriv) {
            roomUlMyHtml += ulText;
          }
          if (isPriv) {
            roomUlPrivHtml += ulText;
          }
          if (!isFav && !isOwner && !isPriv) {
            roomUlHtml += ulText;
          }

          roomTableHtml += '<tr id="room' + roomId + '"><td><a href="#room=' + roomId + '">' + roomName + '</a></td><td>' + roomTopic + '</td><td>' + (isAdmin ? '<button data-roomId="' + roomId + '" class="editRoomMulti standard"></button><button data-roomId="' + roomId + '" class="deleteRoomMulti standard"></button>' : '') + '<button data-roomId="' + roomId + '" class="archiveMulti standard"></button><input type="checkbox" ' + (isFav ? 'checked="checked" ' : '') + ' data-roomId="' + roomId + '" class="favRoomMulti standard" /></td></tr>';

          roomRef[roomName] = roomId;
          roomIdRef[roomId] = roomName;
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
        });

        $('#roomListLong > li > ul').html('<li>Favourites<ul>' + roomUlFavHtml + '</ul></li><li>My Rooms<ul>' + roomUlMyHtml + '</ul></li><li>General<ul>' + roomUlHtml + '</ul></li><li>Private<ul>' + roomUlPrivHtml + '</ul></li>');

        $('#roomListShort > ul').html('<li>Favourites<ul>' + roomUlFavHtml + '</ul></li>');

        $('#roomName').html(roomIdRef[roomId]);

        console.log('Rooms obtained.');

        return false;
      },
      error: function() {
        console.log('Rooms Not Obtained - Problems May Occur');

        return false;
      }
    }),


    $.ajax({
      url: directory + 'api/getGroups.php?fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(xml) {
        console.log('Groups obtained.');

        $(xml).find('group').each(function() {
          var groupName = unxml($(this).find('groupName').text().trim()),
            groupId = Number($(this).find('groupId').text().trim());

          groupRef[groupName] = groupId;
          groupIdRef[groupId] = groupName;
          groupList.push(groupName);
        });

        return false;
      },
      error: function() {
        console.log('Groups Not Obtained - Problems May Occur');

        return false;
      }
    })
  ).done(function() {
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

function youtubeSend(id) { // TODO
  standard.sendMessage('http://www.youtube.com/watch?v=' + id,0,'video');

  $('#textentryBoxYoutube').dialog('close');
}


function updateVids(searchPhrase) {
  jQTubeUtil.search(searchPhrase, function(response) {
    var html = "",
      num = 0,
      video;

    for (vid in response.videos) {
      video = response.videos[vid];
      num += 1;

      if (num % 3 === 1) {
        html += '<tr>';
      }
      html += '<td><img src="http://i2.ytimg.com/vi/' + video.videoId + '/default.jpg" style="width: 80px; height: 60px;" /><br /><small><a href="javascript: false(0);" onclick="youtubeSend(&apos;' + video.videoId + '&apos;)">' + video.title + '</a></small></td>';

      if (num % 3 === 0) {
        html += '</tr>';
      }
    }

    if (num % 3 !== 0) {
      html += '</tr>';
    }

    $('#youtubeResults').html(html);

    return false;
  });

  return false;
}



autoEntry = {
  addEntry : function(type, source, id) {
    var val,
      id,
      type2;

    if (!id) {
      val = $("#" + type + "Bridge").val();
      switch(type) {
        case 'watchRooms':
        id = roomRef[val];
        type2 = 'Room';
        break;

        case 'moderators':
        case 'allowedUsers':
        id = userRef[val];
        type2 = 'User';
        break;

        case 'allowedGroups':
        id = groupRef[val];
        type2 = 'Group';
        break;
      }
    }
    else {
      switch(type) {
        case 'watchRooms':
        val = roomIdRef[id];
        type2 = 'Room';
        break;

        case 'moderators':
        case 'allowedUsers':
        val = userIdRef[id];
        type2 = 'User';
        break;

        case 'allowedGroups':
        val = groupIdRef[id];
        type2 = 'Group';
        break;
      }
    }

    if (!id) {
      dia.error(type2 + ' does not exist.');
    }
    else {
      var currentRooms = $("#" + type).val().split(",");
      currentRooms.push(id);

      $("#" + type + "List").append("<span id=\"" + type + "SubList" + id + "\">" + val + " (<a href=\"javascript:false(0);\" onclick=\"autoEntry.removeEntry('" + type + "'," + id + ");\">×</a>), </span>");
      $("#" + type).val(currentRooms.toString(","));
    }

    return false;
  },

  removeEntry : function(type, id) {
    var currentRooms = $("#" + type).val().split(","),
      i = 0;

    for (i; i < currentRooms.length; i += 1) {
      if(currentRooms[i] == id) {
        currentRooms.splice(i, 1);
        break;
      }
    }

    $("#" + type).val(currentRooms.toString(","));

    $("#" + type + "SubList" + id).fadeOut(500, function() {
      $(this).remove();
    });

    return false;
  },

  showEntries : function(type, string) {
    var source,
      i = 0;

    entryList = string.split(',');


    switch(type) {
      case 'watchRooms':
      source = roomRef;
      break;

      case 'moderators':
      case 'allowedUsers':
      source = userRef;
      break;

      case 'allowedGroups':
      source = groupRef;
      break;
    }


    for (i = 0; i < entryList.length; i += 1) {
      if (entryList[i] == '') {
        continue;
      }

      autoEntry.addEntry(type,source,entryList[i]);
    }

    return false;
  }
};


var standard = {
  archive : function (options) {
    var encrypt = 'base64',
      lastMessage = 0,
      firstMessage = 0,
      data = '',
      where;

    if (options.idMax) {
      where = 'messageIdEnd=' + options.idMax;
    }
    else if (options.idMin) {
      where = 'messageIdStart=' + options.idMin;
    }
    else {
      where = 'messageIdStart=0';
    }


    $('#searchText').change(function() {
      standard.archive({
        idMax : options.idMax,
        idMin : options.idMin,
        roomId : options.roomId,
        search : $(this).val()
      });
    });

    $('#archiveSearch').submit(function() {
      standard.archive({
        idMax : options.idMax,
        idMin : options.idMin,
        roomId : options.roomId,
        search : $(this).val()
      });

      return false;
    });

    $.when( $.ajax({
      url: directory + 'api/getMessages.php?rooms=' + options.roomId + '&archive=1&messageLimit=20&' + where + (options.search ? '&search=' + urlEncode(options.search) : '') + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,
      type: 'GET',
      timeout: 5000,
      data: '',
      contentType: "text/xml; charset=utf-8",
      dataType: "xml",
      cache: false,
      success: function (xml) {

        if ($(xml).find('messages > message').length > 0) {
          $(xml).find('messages > message').each(function() {
            var text = unxml($(this).find('htmlText').text().trim()),
              messageTime = $(this).find('messageTimeFormatted').text().trim(),
              messageId = Number($(this).find('messageId').text().trim()),

              userName = $(this).find('userData > userName').text().trim(),
              userId = Number($(this).find('userData > userId').text().trim()),
              groupFormatStart = unxml($(this).find('userData > startTag').text().trim()),
              groupFormatEnd = unxml($(this).find('userData > endTag').text().trim()),

              styleColor = $(this).find('defaultFormatting > color').text().trim(),
              styleHighlight = $(this).find('defaultFormatting > highlight').text().trim(),
              styleFontface = $(this).find('defaultFormatting > fontface').text().trim(),
              styleGeneral = Number($(this).find('defaultFormatting > general').text().trim()),

              style = 'color: rgb(' + styleColor + '); background: rgb(' + styleHighlight + '); font-family: ' + styleFontface + ';';

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

            data += '<tr id="archiveMessage' + messageId + '"><td>' + groupFormatStart + '<span class="userName userNameTable" data-userId="' + userId + '">' + userName + '</span>' + groupFormatEnd + '</td><td>' + messageTime + '</td><td style="' + style + '" data-messageid="' + messageId + '">' + text + '</td></tr>';

            if (messageId > lastMessage) {
              lastMessage = messageId;
            }
            if (messageId < firstMessage || !firstMessage) {
              firstMessage = messageId;
            }
          });
        }

        return true;
      }
    })).done(function() {
      $('#archiveMessageList').html(data);
      $('#archiveNext').attr('onclick','standard.archive({idMin : ' + lastMessage + ', roomId: ' + options.roomId + '});');
      $('#archivePrev').attr('onclick','standard.archive({idMax : ' + firstMessage + ', roomId: ' + options.roomId + '});');

      if (options.callback) {
        options.callback(data);
      }

      return true;
    });
  },



  login : function(options) {
    console.log('Login Initiated');
    var data = '';
    sessionHash = '';
    $.cookie('fim3_sessionHash','');

    console.log('Encrypted Password: ' + options.password);


    if (options.start) {
      options.start();
    }


    if (options.userName && options.password) {
      var passwordEncrypt = 'plaintext';
      // TODO: Enable for vBulletin
      // var password = md5(password);
      // var passwordEncrypt = 'md5';

      data = 'userName=' + options.userName + '&password=' + options.password + '&passwordEncrypt=' + passwordEncrypt;
    }
    else if (options.sessionHash) {
      data = 'fim3_sessionHash=' + options.sessionHash + '&apiLogin=1&fim3_userId=' + $.cookie('fim3_userId');
    }
    else {
      data = 'apiLogin=1';
    }


    $.when(
      $.ajax({
        url: directory + 'validate.php',
        type: 'POST',
        data: data + '&apiVersion=3',
        cache: false,
        timeout: 2500,
        success: function(xml) {
          var loginFlag = unxml($(xml).find('loginFlag').text().trim()),
            loginText = unxml($(xml).find('loginText').text().trim()),
            valid = unxml($(xml).find('valid').text().trim()),
            userName = unxml($(xml).find('userData > userName').text().trim()),
            defaultRoomId = Number($(xml).find('defaultRoomId').text().trim());

          userId = Number($(xml).find('userData > userId').text().trim());
          anonId = Number($(xml).find('anonId').text().trim());
          sessionHash = unxml($(xml).find('sessionHash').text().trim());

          $.cookie('fim3_sessionHash',sessionHash); // Set cookies.
          $.cookie('fim3_userId',userId);



          /* Update Permissions */

          userPermissions.createRoom = (Number($(xml).find('userPermissions > createRooms').text().trim()) > 0 ? true : false);
          userPermissions.privateRoom = (Number($(xml).find('userPermissions > privateRooms').text().trim()) > 0 ? true : false);
          userPermissions.general = (Number($(xml).find('userPermissions > allowed').text().trim()) > 0 ? true : false);


          adminPermissions.modPrivs = (Number($(xml).find('adminPermissions > modPrivs').text().trim()) > 0 ? true : false);
          adminPermissions.modCore = (Number($(xml).find('adminPermissions > modCore').text().trim()) > 0 ? true : false);
          adminPermissions.modUsers = (Number($(xml).find('adminPermissions > modUsers').text().trim()) > 0 ? true : false);
          adminPermissions.modTemplates = (Number($(xml).find('adminPermissions > modTemplates').text().trim()) > 0 ? true : false);
          adminPermissions.modImages = (Number($(xml).find('adminPermissions > modImages').text().trim()) > 0 ? true : false);
          adminPermissions.modCensor = (Number($(xml).find('adminPermissions > modCensor').text().trim()) > 0 ? true : false);
          adminPermissions.modHooks = (Number($(xml).find('adminPermissions > modHooks').text().trim()) > 0 ? true : false);



          if (valid === 'true') {

            if (options.showMessage) {
              /* Display Dialog to Notify User of Being Logged In */
              if (!userPermissions.general) {
                dia.info('You are now logged in as ' + userName + '. However, you are not allowed to post and have been banned by an administrator.','Logged In');
              }
              else {
                dia.info('You are now logged in as ' + userName + '.','Logged In');
              }
            }



            $('#loginDialogue').dialog('close'); // Close any open login forms.

            console.log('Login valid. Session hash: ' + sessionHash + '; User ID: ' + userId);
          }

          else {

            switch (loginFlag) {
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
              $.cookie('fim3_sessionHash','');

              dia.error("You have been logged out. Please log-in.");
              break;

              default:
              break;
            }

            console.log('Login Invalid');
          }


          if (!anonId && !userId) {
            $('#messageInput').attr("disabled","disabled"); // The user is not able to post.
          }



          /* Select Room */

          if (!roomId) {
            if (!defaultRoomId) {
              standard.changeRoom(1);
            }
            else {
              standard.changeRoom(defaultRoomId);
            }
          }

          return false;
        },
        error: function() {
          dia.error("The login request could not be sent. Please try again.");

          return false;
        }
      })
    ).done(function() {
      if (options.finish) {
        options.finish();
      }

      populate({
        callback : function() {
          windowDraw();
          windowDynaLinks();

          return false;
        }
      });

      console.log('Login Finished');

      return false;
    });

    return false;
  },


  logout : function() {
    $.cookie('fim3_sessionHash','');

    standard.login({});
  },


  getMessages : function() {
    if (roomId) {

      var encrypt = 'base64';

      $.ajax({
        url: directory + 'api/getMessages.php?rooms=' + roomId + '&messageLimit=100&watchRooms=1&activeUsers=1' + (requestSettings.firstRequest? '&archive=1&messageDateMin=' + (Math.round((new Date()).getTime() / 1000) - 1200) : '&messageIdMin=' + (requestSettings.lastMessage)) + (requestSettings.longPolling ? '&longPolling=true' : '') + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,
        type: 'GET',
        timeout: requestSettings.timeout,
        data: '',
        contentType: "text/xml; charset=utf-8",
        dataType: "xml",
        cache: false,
        success: function(xml) {
          var errStr = $(xml).find('errStr').text().trim(),
            errDesc = $(xml).find('errDesc').text().trim();

          if (errStr) {
            var sentUserId = $(xml).find('activeUser > userId');

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
            var notifyData = '';


            $('#activeUsers').html('');
            var activeUserHtml = []; // Array


            $(xml).find('activeUsers > user').each(function() {
              var userName = $(this).find('userName').text().trim(),
                userId = $(this).find('userId').text().trim(),
                userGroup = $(this).find('userGroup').text().trim(),
                startTag = unxml($(this).find('startTag').text().trim()),
                endTag = unxml($(this).find('endTag').text().trim());

              activeUserHtml.push('<span class="userName" data-userId="' + userId + '">' + startTag + '<span class="username">' + userName + '</span>' + endTag + '</span>');
            });

            $('#activeUsers').html(activeUserHtml.join(', '));


            if ($(xml).find('messages > message').length > 0) {
              $(xml).find('messages > message').each(function() {

                var text = unxml($(this).find('htmlText').text().trim()),
                  messageTime = unxml($(this).find('messageTimeFormatted').text().trim()),

                  messageId = Number($(this).find('messageId').text().trim()),

                  userName = unxml($(this).find('userData > userName').text().trim()),
                  userId = Number($(this).find('userData > userId').text().trim()),
                  groupFormatStart = unxml($(this).find('userData > startTag').text().trim()),
                  groupFormatEnd = unxml($(this).find('userData > endTag').text().trim()),
                  avatar = unxml($(this).find('userData > avatar').text().trim()),

                  styleColor = unxml($(this).find('defaultFormatting > color').text().trim()),
                  styleHighlight = unxml($(this).find('defaultFormatting > highlight').text().trim()),
                  styleFontface = unxml($(this).find('defaultFormatting > fontface').text().trim()),
                  styleGeneral = Number($(this).find('defaultFormatting > general').text().trim()),

                  style = 'color: rgb(' + styleColor + '), background: rgb(' + styleHighlight + '), font-family: ' + styleFontface + ',';

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


                if (settings.showAvatars) {
                  var data = '<span id="message' + messageId + '" class="messageLine" style="padding-bottom: 3px; padding-top: 3px; vertical-align: middle;"><img alt="' + userName + '" src="' + avatar + '" style="max-width: 24px; max-height: 24px; padding-right: 3px;" class="userName userNameTable" data-userId="' + userId + '" /><span style="padding: 2px; ' + style + '" class="messageText" data-messageid="' + messageId + '"  data-time="' + messageTime + '">' + text + '</span><br />';
                }
                else {
                  var data = '<span id="message' + messageId + '" class="messageLine">' + groupFormatStart + '<span class="userName userNameTable" data-userId="' + userId + '">' + userName + '</span>' + groupFormatEnd + ' @ <em>' + messageTime + '</em>: <span style="padding: 2px; ' + style + '" class="messageText" data-messageid="' + messageId + '">' + text + '</span><br />';
                }

                notifyData += userName + ': ' + text + "\n";

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

                var roomTopic = $(this).find('roomData > roomTopic').text().trim();
                if (roomTopic) {
                  $('#topic').html(roomTopic);
                }
              });



              if (settings.reversePostOrder) {
                toBottom();
              }



              if (window.isBlurred) {
                if (settings.audioDing) {
                  riffwave.play();

                  if (navigator.appName === 'Microsoft Internet Explorer') {
                    timers.t3 = window.setInterval(faviconFlash,1000);

                    window.clearInterval(timers.t3);
                  }
                }

                if (notify) {
                  if ('webkitNotifications' in window) {
                    notify.webkitNotify('images/favicon.gif', 'New Message', notifyData);
                  }
                }

                if ('external' in window) {
                  if ('msIsSiteMode' in window.external && 'msSiteModeActivate' in window.external) {
                    try {
                      if (window.external.msIsSiteMode()) {
                        window.external.msSiteModeActivate();
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

            if (requestSettings.longPolling) {
              setTimeout(standard.getMessages,50);
            }
            else {
              requestSettings.timeout = 2400;
              setTimeout(standard.getMessages,2500);
            }
          }

          requestSettings.firstRequest = false;

          return false;
        },
        error: function(err) {
          console.log('Requesting messages for ' + roomId + '; failed: ' + err + '.');

          if (requestSettings.longPolling) {
            setTimeout(standard.getMessages,50);
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

            setTimeout(standard.getMessages,wait);
          }

          return false;
        }
      });
    }
    else {
      console.log('Not requesting messages; room undefined.');
    }

    return false;
  },


  sendMessage : function(message,confirmed,flag) {
    if (!roomId) {
      popup.selectRoom();
    }
    else {
      confirmed = (confirmed === 1 ? 1 : '');

      $.ajax({
        url: directory + 'api/sendMessage.php?fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,
        type: 'POST',
        data: 'roomId=' + roomId + '&confirmed=' + confirmed + '&message=' + urlEncode(message) + '&flag=' + flag,
        cache: false,
        timeout: 2500,
        success: function(xml) {
          console.log('Message sent.');

          var errStr = $(xml).find('errStr').text().trim();
          var errDesc = $(xml).find('errDesc').text().trim();
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
            dia.error(errDesc + '<br /><br /><button type="button" onclick="$(this).parent().dialog(&apos;close&apos;);">No</button><button type="button" onclick="standard.standard.sendMessage(&apos;' + escape(message) + '&apos;,1' + (flag ? ',' + flag : '') + '); $(this).parent().dialog(&apos;close&apos;);">Yes</button>');
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

          standard.standard.sendMessage(message);

          return false;
        }
      });
    }

    return false;
  },


  changeRoom : function(roomLocalId) {
    console.log('Changing Room: ' + roomLocalId + '; Detected Name: ' + roomIdRef[roomLocalId]);

    roomId = roomLocalId;
    $('#roomName').html(roomIdRef[roomId]);
    $('#messageList').html('');

    windowDraw();
    windowDynaLinks();


    /*** Get Messages ***/

    $(document).ready(function() {
      $(document).ready(function() {
        standard.getMessages();
      });

      return false;
    });

    return false;
  },


  deleteRoom : function(roomLocalId) {
    $.post(directory + 'api/moderate.php','action=deleteRoom&messageId=' + messageId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,function(xml) {
      var errStr = $(xml).find('errStr').text().trim(),
        errDesc = $(xml).find('errDesc').text().trim();

      switch (errStr) {
        case '':
        console.log('Message ' + messageId + ' deleted.');
        break;

        case 'nopermission':
        dia.error('You do not have permision to administer this room.');
        break;

        case 'badroom':
        dia.error('The specified room does not exist.');
        break;
      }

      return false;
    }); // Send the form data via AJAX.
  },


  privateRoom : function(userLocalId) {
    userLocalId = Number(userLocalId);

    if (userLocalId === userId) {
      dia.error('You can\'t talk to yourself...');
    }
    else if (!userLocalId) {
      dia.error('You have not specified a user.');
    }
    else if (!userPermissions.privateRoom) {
      dia.error('You do not have permission to talk to users privately.');
    }
    else {
      $.post(directory + 'api/moderate.php','action=privateRoom&userId=' + userLocalId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,function(xml) {
        var privateRoomId = Number($(xml).find('insertId').text().trim()),
          errStr = unxml($(xml).find('errStr').text().trim()),
          errDesc = unxml($(xml).find('errStr').text().trim());

        if (errStr) {
          switch (errStr) {
            case 'baduser':
            dia.error('The user specified does not exist.');
            break;
          }
        }
        else {
          dia.full({
            content : 'You may talk to this person privately at the following link: <form method="post" onsubmit="return false;"><input type="text" style="width: 300px;" value="' + currentLocation + '#room=' + privateRoomId + '" name="url" /></form>',
            id : 'privateRoomSucessDialogue',
            buttons : {
              Open : function() {
                standard.selectRoom(privateRoomId);
              },
              Okay : function() {
                $('#privateRoomSucessDialogue').dialog('close');
              }
            },
            width: 600
          });
        }

        return false;
      }); // Send the form data via AJAX.
    }

    return false;
  },


  kick : function(userId, roomId, length) {
    $.post(directory + 'api/moderate.php','action=kickUser&userId=' + userId + '&roomId=' + roomId + '&length=' + length + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,function(xml) {
      var errStr = $(xml).find('errStr').text().trim(),
        errDesc = $(xml).find('errDesc').text().trim();

      switch (errStr) {
        case '':
        dia.info('The user has been kicked.','Success');

        $("#kickUserDialogue").dialog('close');
        break;

        case 'nopermission':
        dia.error('You do not have permision to moderate this room.');
        break;

        case 'nokickuser':
        dia.error('That user may not be kicked!');
        break;

        case 'baduser':
        dia.error('The user specified does not exist.');
        break;

        case 'badroom':
        dia.error('The room specified does not exist.');
        break;
      }

      return false;
    }); // Send the form data via AJAX.

    return false;
  },


  deleteMessage : function(messageId) {
    $.post(directory + 'api/moderate.php','action=deleteMessage&messageId=' + messageId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,function(xml) {
      var errStr = $(xml).find('errStr').text().trim(),
        errDesc = $(xml).find('errDesc').text().trim();

      switch (errStr) {
        case '':
        console.log('Message ' + messageId + ' deleted.');
        break;

        case 'nopermission':
        dia.error('You do not have permision to moderate this room.');
        break;

        case 'badmessage':
        dia.error('The message does not exist.');
        break;
      }

      return false;
    }); // Send the form data via AJAX.

    return false;
  }


};

/*********************************************************
************************* END ***************************
******************* Content Functions *******************
*********************************************************/







/*********************************************************
************************ START **************************
********** Silent Init Uses of Standard Methods *********
*********************************************************/

if ("onhashchange" in window) {
  window.onhashchange = hashParse;
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
    $.get('template.php','template=login',function(data) {
      dia.full({
        content : data,
        title : 'Login',
        id : 'loginDialogue',
        width : 600,
        oF : function() {
          $("#loginForm").submit(function() {
            var userName = $('#loginForm > #userName').val(),
              password = $('#loginForm > #password').val();

            standard.login({
              userName : userName,
              password : password,
              showMessage : true
            });

            return false; // Don't submit the form.
          });
        },
        cF : function() {
          if (!userId) {
            standard.login({
              start : function() {
                $('<div class="ui-widget-overlay" id="loginWaitOverlay"></div>').appendTo('body').width($(document).width()).height($(document).height());
                $('<img src="images/ajax-loader.gif" id="loginWaitThrobber" />').appendTo('body').css('position','absolute').offset({ left : (($(window).width() - 220) / 2), top : (($(window).height() - 19) / 2)});
              },
              finish : function() {
                $('#loginWaitOverlay, #loginWaitThrobber').empty().remove();
              }
            });
          }

          return false;
        }
      });

      console.log('Popup for un-loggedin user triggered.');

      return false;
    });
  },

  /*** END Login ***/




  /*** START Room Select ***/

  selectRoom : function() {
    dia.full({
      content : '<table class="center"><thead><tr><th style="width: 20%;">Name</th><th style="width: 60%;">Topic</th><th style="width: 20%;">Actions</th></tr></thead><tbody>' + roomTableHtml + '</tbody></table>',
      title : 'Room List',
      id : 'roomListDialogue',
      width: 1000,
      oF : function() {
        $('button.editRoomMulti, input[type=checkbox].favRoomMulti, button.archiveMulti, button.deleteRoomMulti').unbind('click'); // Prevent the below from being binded multiple times.


        $('button.editRoomMulti').button({icons : {primary : 'ui-icon-gear'}}).bind('click',function() {
          popup.editRoom($(this).attr('data-roomId'));

          return false;
        });

        $('input[type=checkbox].favRoomMulti').button({icons : {primary : 'ui-icon-star'}}).bind('click',function() {
          if ($(this).is(':checked')) {
            standard.favRoom($(this).attr('data-roomId'));
          }
          else {
            standard.unfavRoom($(this).attr('data-roomId'));
          }

          return false;
        });

        $('button.archiveMulti').button({icons : {primary : 'ui-icon-note'}}).bind('click',function() {
          popup.archive({roomId : $(this).attr('data-roomId')});

          return false;
        });

        $('button.deleteRoomMulti').button({icons : {primary : 'ui-icon-trash'}}).bind('click',function() {
          standard.deleteRoom($(this).attr('data-roomId'));

          return false;
        });
      }
    });

    return false;
  },

  /*** END Room List ***/




  /*** START Insert Docs ***/

  insertDoc : function(preselect) {
    var fileContent,
      selectTab;

    switch(preselect) {
      case 'video':
      selectTab = 2;
      break;

      case 'image':
      selectTab = 1;
      break;

      case 'link':
      default:
      selectTab = 0;
      break;
    }

    dia.full({
      uri : 'template.php?template=insertDoc',
      id : 'insertDoc',
      width: 600,
      tabs : true,
      oF : function() {

        $('#fileUpload').attr('disabled','disabled').button({
          disabled: true
        });


        if (typeof FileReader !== 'function') {
          $('#uploadFileForm').html('Your device does not support file uploads.<br /><br />');
        }
        else {
          $('#fileUpload, #urlUpload').unbind('change'); // Prevent duplicate binds.
          $('#uploadFileForm, #uploadUrlForm, #linkForm. #uploadYoutubeForm').unbind('submit');


          $('#fileUpload').bind('change',function() {
            console.log('FileReader triggered.');

            var reader = new FileReader();
            files = this.files;

            if (files.length == 0) {
              dia.error('No files selected!');
            }
            else if (files.length > 1) {
              dia.error('Too many files selected!');
            }
            else {
              console.log('FileReader started.');

              var file = files[0];

              var fileName = file.name,
                fileSize = file.size;

              if (!fileName.match(/\.(jpg|jpeg|gif|png|svg)$/i)) { // TODO
                $('#preview').html('Wrong file type.');
              }
              else if (fileSize > 4 * 1000 * 1000) { // TODO
                $('#preview').html('File too large.');
              }
              else {
                reader.readAsBinaryString(file);
                reader.onloadend = function() {
                  fileContent = window.btoa(reader.result);
                };

                reader2.readAsDataUrl(file);
                reader.onloadend = function() {
                  $('#uploadUrlFormPreview').html('<img src="' + reader2.result + '" style="max-height: 200px; max-width: 200px;" />');
                };

                var reader = new FileReader();
                reader.readAsDataURL(file);
              }

              $('#imageUploadSubmitButton').removeAttr('disabled').button({disabled: false});
            }
          });

          $('#urlUpload').bind('change',function() {
            fileContent = $('#urlUpload').val();
            if (fileContent && fileContent !== 'http://') {
              fileContainer = '<img src="' + fileContent + '" alt="" style="max-width: 200px; max-height: 250px; height: auto;" />';

              $('#uploadUrlFormPreview').html(fileContainer);
            }
          });


          $('#uploadFileForm').bind('submit',function() {
            $.ajax({
              url: directory + 'api/uploadFile.php',
              type: 'POST',
              data : 'dataEncode=base64&uploadMethod=raw&autoInsert=true&roomId=' + roomId + '&file_data=' + urlEncode(fileContent) + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,
              cache : false
            });

            return false;
          });
        }


        $('#uploadUrlForm').bind('submit',function() {
          var linkImage = $('#urlUpload').val();

          if (linkImage) {
            standard.sendMessage(linkImage,0,'image');
          }

          return false;
        });


        $('#linkForm').bind('submit',function() {
          var linkUrl = $('#linkUrl').val(),
            linkMail = $('#linkEmail').val();

          if (!linkUrl && !linkMail) {
            dia.error('No Link Was Specified');
          }
          else if (linkUrl) {
            standard.sendMessage(linkUrl,0,'url');
          }
          else if (linkMail) {
            standard.sendMessage(linkMail,0,'email');
          }
          else {
            dia.error('Logic Error');
          }

          return false;
        });

        $('#uploadYoutubeForm').bind('submit',function() {
          linkVideo = $('#youtubeUpload');

          if (linkVideo.search(/^http\:\/\/(www\.|)youtube\.com\/(.*?)?v=(.+?)(&|)(.*?)$/) === 0) {
            dia.error('No Video Specified');
          }
          else {
            standard.sendMessage(linkVideo,0,'video');
          }

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
    var statsHtml = {}, // Object
      statsHtml2 = '',
      roomHtml = '',
      number = 10,
      i = 1;

    for (i = 1; i <= number; i += 1) {
      statsHtml[i] = '';
    }

    $.ajax({
      url: directory + 'api/getStats.php?rooms=' + roomId + '&maxResults=' + number + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(xml) {
        $(xml).find('room').each(function() {
          var roomName = unxml($(this).find('roomName').text().trim()),
            roomId = Number($(this).find('roomId').text().trim());

          $(this).find('user').each(function() {
            var userName = unxml($(this).find('userData > userName').text().trim()),
              userId = Number($(this).find('userData > userId').text().trim()),
              startTag = unxml($(this).find('userData > startTag').text().trim()),
              endTag = unxml($(this).find('userData > endTag').text().trim()),
              position = Number($(this).find('position').text().trim()),
              messageCount = Number($(this).find('messageCount').text().trim());

            statsHtml[position] += '<td>' + startTag + userName + endTag + ' (' + messageCount + ')</td>';
          });


          roomHtml += '<th>' + roomName + '</th>';

        });

        for (i = 1; i <= number; i += 1) {
          statsHtml2 += '<tr><th>' + i + '</th>' + statsHtml[i] + '</tr>';
        }

        dia.full({
          content : '<table class="center"><thead><tr><th>Position</th>' + roomHtml + '</tr></thead><tbody>' + statsHtml2 + '</tbody></table>',
          title : 'Room Stats',
          id : 'roomStatsDialogue',
          width : 600
        });

        return false;
      },
      error: function() {
        dia.error('Failed to show all rooms');

        return false;
      }
    });

    return false;
  },

  /*** END Stats ***/




  /*** START User Settings ***/

  userSettings : function() {
    dia.full({
      uri : 'template.php?template=userSettingsForm',
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
          defaultFontface = false;

        $('#theme').change(function() {
          $('#stylesjQ').attr('href','client/css/' + themes[this.value] + '/jquery-ui-1.8.13.custom.css');
          $('#stylesFIM').attr('href','client/css/' + themes[this.value] + '/fim.css');

          $.cookie('fim3_themeId',this.value);

          return false;
        });


        if (settings.reversePostOrder) {
          $('#reversePostOrder').attr('checked','checked');
        }
        if (settings.showAvatars) {
          $('#showAvatars').attr('checked','checked');
        }
        if (settings.audioDing) {
          $('#audioDing').attr('checked','checked');
        }
        if (settings.disableFx) {
          $('#disableFx').attr('checked','checked');
        }


        $('#showAvatars').change(function() {
          if ($(this).val() === 'true' && !settings.showAvatars) {
            settings.showAvatars = true;
            $('#messageList').html('');
            $.cookie('fim3_settings',Number($.cookie('fim3_settings')) + 2048);

            requestSettings.firstRequest= true;
          }
          else if ($(this).val() !== 'true' && settings.showAvatars) {
            settings.showAvatars = false;
            $('#messageList').html('');
            $.cookie('fim3_settings',Number($.cookie('fim3_settings')) - 2048);

            requestSettings.firstRequest= true;
          }

          return false;
        });

        $('#reversePostOrder').change(function() {
          if ($(this).val() === 'true' && !settings.reversePostOrder) {
            settings.reversePostOrder = true;
            $('#messageList').html('');
            $.cookie('fim3_settings',Number($.cookie('fim3_settings')) + 1024);

            requestSettings.firstRequest= true;
          }
          else if ($(this).val() !== 'true' && settings.reversePostOrder) {
            settings.reversePostOrder = false;
            $('#messageList').html('');
            $.cookie('fim3_settings',Number($.cookie('fim3_settings')) - 1024);

            requestSettings.firstRequest= true;
          }

          return false;
        });

        $('#audioDing').change(function() {
          if ($(this).val() === 'true' && !settings.audioDing) {
            settings.audioDing = true;
            $('#messageList').html('');
            $.cookie('fim3_settings',Number($.cookie('fim3_settings')) + 8192);

            requestSettings.firstRequest= true;
          }
          else if ($(this).val() !== 'true' && settings.audioDing) {
            settings.audioDing = false;
            $('#messageList').html('');
            $.cookie('fim3_settings',Number($.cookie('fim3_settings')) - 8192);

            requestSettings.firstRequest= true;
          }

          return false;
        });

        $('#disableFx').change(function() {
          if ($(this).val() === 'true' && !settings.disableFx) {
            settings.disableFx = true;
            $('#disableFx').html('');
            $.cookie('fim3_settings',Number($.cookie('fim3_settings')) + 16384);

            requestSettings.firstRequest= true;
          }
          else if ($(this).val() !== 'true' && settings.disableFx) {
            settings.disableFx = false;
            $('#disableFx').html('');
            $.cookie('fim3_settings',Number($.cookie('fim3_settings')) - 16384);

            requestSettings.firstRequest= true;
          }

          return false;
        });

        $("#defaultRoom").autocomplete({
          source: roomList
        });
        $("#watchRoomsBridge").autocomplete({
          source: roomList
        });

        $.ajax({
          url: directory + 'api/getFonts.php?fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,
          timeout: 5000,
          type: 'GET',
          cache: false,
          success: function(xml) {
            $(xml).find('font').each(function() {
              var fontName = unxml($(this).find('fontName').text().trim()),
                fontId = Number($(this).find('fontId').text().trim()),
                fontGroup = unxml($(this).find('fontGroup').text().trim()),
                fontData = unxml($(this).find('fontData').text().trim());

              $('#defaultFace').append('<option value="' + fontId + '" style="' + fontData + '" data-font="' + fontData + '">' + fontName + '</option>');
            });

            return false;
          },
          error: function() {
            dia.error('The list of fonts could not be obtained from the server.');

            return false;
          }
        });

        $('#defaultHighlight').ColorPicker({
          color: '',
          onShow: function (colpkr) {
            $(colpkr).fadeIn(500);

            return false;
          },
          onHide: function (colpkr) {
            $(colpkr).fadeOut(500);

            return false;
          },
          onChange: function(hsb, hex, rgb) {
            defaultHighlight = rgb['r'] + ',' + rgb['g'] + ',' + rgb['b'];

            $('#defaultHighlight').css('background-color','rgb(' + defaultHighlight + ')');
            $('#fontPreview').css('background-color','rgb(' + defaultHighlight + ')');
          }
        });

        $('#defaultColour').ColorPicker({
          color: '',
          onShow: function (colpkr) {
            $(colpkr).fadeIn(500);

            return false;
          },
          onHide: function (colpkr) {
            $(colpkr).fadeOut(500);

            return false;
          },
          onChange: function(hsb, hex, rgb) {
            defaultColour = rgb['r'] + ',' + rgb['g'] + ',' + rgb['b'];

            $('#defaultColour').css('background-color','rgb(' + defaultColour + ')');
            $('#fontPreview').css('color','rgb(' + defaultColour + ')');
          }
        });

        $('#fontPreview').css('color','');
        $('#defaultColour').css('background-color','');
        $('#fontPreview').css('background-color','');
        $('#defaultHighlight').css('background-color','');

        if ($('#defaultItalics').is(':checked')) {
          $('#fontPreview').css('font-style','italic');
        }
        else {
          $('#fontPreview').css('messageIdfont-style','normal');
        }

        if ($('#defaultBold').is(':checked')) {
          $('#fontPreview').css('font-weight','bold');
        }
        else {
          $('#fontPreview').css('font-style','normal');
        }

        $("#changeSettingsForm").submit(function() {
          var watchRooms = $('#watchRooms').val(),
            defaultRoom = $('#defaultRoom').val(),
            defaultRoomId = (defaultRoom ? roomRef[defaultRoom] : 0),
            fontId = $('#defaultFace option:selected').val();

          $.post(directory + 'api/moderate.php','action=userOptions&userId=' + userId + (defaultColour ? '&defaultColor=' + defaultColour : '') + (defaultHighlight ? '&defaultHighlight=' + defaultHighlight : '') + (defaultRoomId ? '&defaultRoomId=' + defaultRoomId : '') + (watchRooms ? '&watchRooms=' + watchRooms : '') + (fontId ? '&defaultFontface=' + fontId : '') + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,function(xml) {
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




  /*** START Edit Room ***/

  editRoom : function(roomIdLocal) {
    dia.full({
      uri : 'template.php?template=editRoomForm',
      tabs : true,
      id : 'editRoomDialogue',
      width : 1000,
      oF: function() {
        $("#moderatorsBridge").autocomplete({
          source: userList
        });
        $("#allowedUsersBridge").autocomplete({
          source: userList
        });
        $("#allowedGroupsBridge").autocomplete({
          source: groupList
        });

        $.ajax({
          url: directory + 'api/getRooms.php?rooms=' + roomIdLocal + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,
          type: 'GET',
          timeout: 2400,
          cache: false,
          success: function(xml) {
            var data = '',
              roomName = unxml($(xml).find('roomName').text().trim()),
              roomId = Number($(xml).find('roomId').text().trim()),
              allowedUsers = $(xml).find('allowedUsers').text().trim(),
              allowedGroups = $(xml).find('allowedGroups').text().trim(),
              moderators = $(xml).find('moderators').text().trim(),
              mature = ($(xml).find('optionDefinitions > mature').text().trim() === 'true' ? true : false);

            $('#name').val(roomName);

            if (allowedUsers !== '*' && allowedUsers !== '') {
              autoEntry.showEntries('allowedUsers',allowedUsers);
            }


            if (moderators !== '*' && moderators !== '') {
              autoEntry.showEntries('moderators',moderators);
            }


            if (allowedGroups !== '*' && allowedGroups !== '') {
              autoEntry.showEntries('allowedGroups',allowedGroups);
            }

            if (mature) {
              $('#mature').attr('checked','checked');
            }

            return false;
          },
          error: function() {
            dia.error('Failed to obtain current room settings from server.');

            return false;
          }
        });

        $("#editRoomForm").submit(function() {
          var bbcode = Number($('#bbcode > option:selected').val()),
            name = $('#name').val(),
            mature = ($('#mature').is(':checked') ? true : false),
            allowedUsers = $('#allowedUsers').val(),
            allowedGroups = $('#allowedGroups').val(),
            moderators = $('#moderators').val();

          if (name.length > 20) {
            dia.error('The roomname is too long.');
          }
          else {
            $.post(directory + 'api/moderate.php','action=editRoom&roomId=' + roomIdLocal + '&name=' + urlEncode(name) + '&bbcode=' + bbcode + '&mature=' + mature + '&allowedUsers=' + allowedUsers + '&allowedGroups=' + allowedGroups + '&moderators=' + moderators + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,function(xml) {
              var errStr = unxml($(xml).find('errStr').text().trim()),
                errDesc = unxml($(xml).find('errDesc').text().trim());

              if (errStr) {
                dia.error('An error has occured: ' + errDesc);
              }
              else {
                dia.full({
                  content : 'The room has been edited.',
                  title : 'Room Edited!',
                  id : 'editRoomResultsDialogue',
                  width : 600,
                  buttons : {
                    Open : function() {
                      standard.selectRoom(roomIdLocal);

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

              return false;
            }); // Send the form data via AJAX.
          }
          return false; // Don't submit the form.
        });
      }
    });
  },

  /*** END Edit Room ***/




  /*** START Create Room ***/

  createRoom : function() {
    dia.full({
      uri : 'template.php?template=editRoomForm&action=create',
      id : 'createRoomDialogue',
      width : 1000,
      tabs : true,
      oF : function() {
        $("#moderatorsBridge").autocomplete({
          source: userList
        });
        $("#allowedUsersBridge").autocomplete({
          source: userList
        });
        $("#allowedGroupsBridge").autocomplete({
          source: groupList
        });

        $("#editRoomForm").submit(function() {
          var bbcode = Number($('#bbcode').val()),
            name = $('#name').val(),
            mature = ($('#mature').is(':checked') ? true : false),
            allowedUsers = $('#allowedUsersBridge').val(),
            allowedGroups = $('#allowedGroupsBridge').val(),
            moderators = $('#moderatorsBridge').val();

          if (name.length > 20) {
            dia.error('The roomname is too long.');
          }
          else {
            $.post(directory + 'api/moderate.php','action=createRoom&name=' + urlEncode(name) + '&bbcode=' + bbcode + '&mature=' + mature + '&allowedUsers=' + allowedUsers + '&allowedGroups=' + allowedGroups + '&moderators=' + moderators + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,function(xml) {
              var errStr = unxml($(xml).find('errStr').text().trim()),
                errDesc = unxml($(xml).find('errDesc').text().trim()),
                createRoomId = Number($(xml).find('insertId').text().trim());

              if (errStr) {
                dia.error('An error has occured: ' + errDesc);
              }
              else {
                dia.full({
                  content : 'The room has been created at the following URL:<br /><br /><form action="' + currentLocation + '#room=' + createRoomId + '" method="post"><input type="text" style="width: 300px;" value="' + currentLocation + '#room=' + createRoomId + '" name="url" /></form>',
                  title : 'Room Created!',
                  id : 'createRoomResultDialogue',
                  width : 600,
                  buttons : {
                    Open : function() {
                      standard.changeRoom(createRoomId);

                      return false;
                    },
                    Okay : function() {
                      $('#createRoomResultsDialogue').dialog('close');

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
      content : '<form action="index.php?action=privateRoom&phase=2" method="post" id="privateRoomForm"><label for="userName">Username</label>: <input type="text" name="userName" id="userName" /><br /><small><span style="margin-left: 10px;">The other user you would like to talk to.</span></small><br /><br />  <button type="submit">Go</button></form>',
      title : 'Enter Private Room',
      id : 'privateRoomDialogue',
      width : 1000,
      oF : function() {
        $('#userName').autocomplete({
          source: userList
        });

        $("#privateRoomForm").submit(function() {
          privateUserName = $("#privateRoomForm > #userName").val(); // Serialize the form data for AJAX.
          privateUserId = userRef[privateUserName];

          standard.privateRoom(privateUserId);

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
      content : '<table class="center"><thead><tr class="hrow"><th>User</th><th>Rooms</th></tr></thead><tbody id="onlineUsers"><tr><td colspan="2">Loading...</td></tr></tbody></table>',
      title : 'Active Users',
      id : 'onlineDialogue',
      width : 600,
      cF : function() {
        clearInterval(timers.t2);
      }
    });

    function updateOnline() {
      $.ajax({
        url: directory + 'api/getAllActiveUsers.php?fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,
        type: 'GET',
        timeout: 2400,
        cache: false,
        success: function(xml) {
          var data = '';

          $(xml).find('user').each(function() {
            var userName = unxml($(this).find('userName').text().trim());
            var userId = Number($(this).find('userId').text().trim());
            var startTag = unxml($(this).find('startTag').text().trim());
            var endTag = unxml($(this).find('endTag').text().trim());
            var roomData = []();

            $(this).find('room').each(function() {
              var roomId = Number($(this).find('roomId').text().trim());
              var roomName = unxml($(this).find('roomName').text().trim());
              roomData.push('<a href="#room=' + roomId + '">' + roomName + '</a>');
            });
            roomData = roomData.join(', ');

            data += '<tr><td>' + startTag + '<span class="userName">' + userName + '</span>' + endTag + '</td><td>' + roomData + '</td></tr>';
          });

          $('#onlineUsers').html(data);

          return false;
        },
        error: function() {
          $('#onlineUsers').html('Refresh Failed');
        }
      });

      return false;
    }

    timers.t2 = setInterval(updateOnline,2500);

    return false;
  },

  /*** END Online ***/




  /*** START Kick Manager ***/

  manageKicks : function() {
    var kickHtml = '';

    $.ajax({
      url: directory + 'api/getKicks.php?rooms=' + roomId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(xml) {
        $(xml).find('kick').each(function() {
          var kickerId = Number($(this).find('kickerData > userId').text().trim()),
            kickerName = $(this).find('kickerData > userName').text().trim(),
            kickerFormatStart = $(this).find('kickerData > userFormatStart').text().trim(),
            kickerFormatEnd = $(this).find('kickerData > userFormatEnd').text().trim(),
            userId = Number($(this).find('userData > userId').text().trim()),
            userName = $(this).find('userData > userName').text().trim(),
            userFormatStart = $(this).find('userData > userFormatStart').text().trim(),
            userFormatEnd = $(this).find('userData > userFormatEnd').text().trim(),
            length = Number($(this).find('length').text().trim()),
            set = unxml($(this).find('setFormatted').text().trim()),
            expires = unxml($(this).find('expiresFormatted').text().trim());

          kickHtml += '<tr><td>' + userFormatStart + userName + userFormatEnd + '</td><td>' + kickerFormatStart + kickerName + kickerFormatEnd + '</td><td>' + set + '</td><td>' + expires + '</td><td><button onclick="standard.unkick(' + userId + ',' + roomId + ')">Unkick</button></td></tr>';
        });

        dia.full({
          content : '<table class="center"><thead><tr class="hrow"><th>User</th><th>Kicked By</th><th>Kicked On</th><th>Expires On</th><th>Actions</th></tr>  </thead><tbody id="kickedUsers">' + kickHtml + '</tbody></table>',
          title : 'Manage Kicked Users in This Room',
          width : 1000
        });

        return false;
      },
      error: function() {
        dia.error('The list of currently kicked users could not be obtained from the server.');

        return false;
      }
    });

    $("form[data-formid=unkick]").submit(function() {
      data = $(this).serialize(); // Serialize the form data for AJAX.
      // TODO
  // $.post("content/unkick.php?phase=2",data,function(html) {
  // quickDialogue(html,'','unkickDialogue');
  // }); // Send the form data via AJAX.

      $("#manageKickDialogue").dialog('destroy');
      return false; // Don\\''t submit the form.
    });

    return false;
  },

  /*** END Kick Manager ***/




  /*** START Kick ***/

  kick : function() {
    dia.full({
      content : '<form action="#" id="kickUserForm" method="post">  <label for="userName">User</label>: <input type="text" name="userName" id="userName" /><br />  <label for="roomNameKick">Room</label>: <input type="text" id="roomNameKick" name="roomNameKick" /> <br />  <label for="time">Time</label>: <input type="text" name="time" id="time" style="width: 50px;" />  <select name="interval" id="interval">    <option value="1">Seconds</option>    <option value="60">Minutes</option>    <option value="3600">Hours</option>    <option value="86400">Days</option>    <option value="604800">Weeks</option>  </select><br /><br />  <button type="submit">Kick User</button><button type="reset">Reset</button></form>',
      title : 'Kick User',
      id : 'kickUserDialogue',
      width : 1000,
      oF : function() {
        roomModList = []();

        for (var i = 0; i < roomList.length; i++) {
          if (modRooms[roomRef[roomList[i]]] > 0) {
            roomModList.push(roomIdRef[roomRef[roomList[i]]]);
          }
        }

        $('#userName').autocomplete({
          source: userList
        });
        $('#roomNameKick').autocomplete({
          source: roomModList
        });

        $("#kickUserForm").submit(function() {
          var roomNameKick = $('#roomNameKick').val();
          var roomId = roomRef[roomNameKick];

          var userName = $('#userName').val();
          var userId = userRef[userName];

          var length = Math.floor(Number($('#time').val() * Number($('#interval > option:selected').attr('value'))));

          standard.kick(userId,roomId,length);

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
      uri : 'template.php?template=help',
      title : 'helpDialogue',
      width : 1000,
      tabs : true
    });

    return false;
  },

  /*** END Help ***/




  /*** START Archive ***/

  archive : function(options) {
    dia.full({
      content : '<form id="archiveSearch" action="#" method="get" style="text-align: center;"><input type="text" id="searchText" name="searchText" style="margin-left: auto; margin-right: auto; text-align: left;" /><button type="submit">Search</button></form><br /><br /><table class="center"><thead><tr><th style="width: 20%;">User</th><th style="width: 20%;">Time</th><th style="width: 60%;">Message</th></tr></thead><tbody id="archiveMessageList"></tbody></table><br /><br /><button id="archivePrev"><< Prev</button><button id="archiveNext">Next >></button>',
      title : 'Archive',
      id : 'archiveDialogue',
      width : 1000,
      autoOpen : false
    });

    standard.archive({
      roomId: options.roomId,
      idMin: options.idMin,
      callback: function(data) {
        $('#archiveDialogue').dialog('open');

        return false;
      }
    });

    return false;
  },

  /*** END Archive ***/




  /*** START Copyright ***/

  copyright : function() {
    dia.full({
      uri : 'template.php?template=copyright',
      title : 'copyrightDialogue',
      width : 600,
      tabs : true
    });

    return false;
  }

  /*** END Copyright ***/
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
        $.get(directory + 'api/getUsers.php?users=' + thisid + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId, function(xml) {
          var userName = unxml($(xml).find('user > userName').text().trim()),
            userId = Number($(xml).find('user > userId').text().trim()),
            startTag = unxml($(xml).find('user > startTag').text().trim()),
            endTag = unxml($(xml).find('user > endTag').text().trim()),
            userTitle = unxml($(xml).find('user > userTitle').text().trim()),
            posts = Number($(xml).find('user > postCount').text().trim()),
            joinDate = unxml($(xml).find('user > joinDateFormatted').text().trim()),
            avatar = unxml($(xml).find('user > avatar').text().trim());

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




  /*** General Generic Styling ***/

  $('table > thead > tr:first-child > td:first-child, table > tr:first-child > td:first-child').addClass('ui-corner-tl');
  $('table > thead > tr:first-child > td:last-child, table > tr:first-child > td:last-child').addClass('ui-corner-tr');
  $('table > tbody > tr:last-child > td:first-child, table > tr:last-child > td:first-child').addClass('ui-corner-bl');
  $('table > tbody > tr:last-child > td:last-child, table > tr:last-child > td:last-child').addClass('ui-corner-br');

  $('button').button();
  $('legend').addClass('ui-widget-header').addClass('ui-corner-all'); // Can these combine?
  $('fieldset').addClass('ui-widget ui-widget-content');

  $('thead').addClass('ui-widget-header');
  $('tbody').addClass('widget ui-widget-content');





  /*** Draw the chatbox. ***/
  if (roomId && (userId | anonId)) {
    $('#messageInput').removeAttr("disabled"); // The user is able to post.
  }
  else {
    $('#messageInput').attr("disabled","disabled"); // The user is able to post.
  }

  $("#icon_settings.reversePostOrder").button("option", "icons", { primary: 'ui-icon-circle-triangle-' + (settings.reversePostOrder ? 'n' : 's') } );
  $("#icon_help").button({ icons: {primary:'ui-icon-help'} });
  $("#icon_note").button({ icons: {primary:'ui-icon-note'} });
  $("#icon_settings").button({ icons: {primary:'ui-icon-wrench'} });
  $("#icon_muteSound").button( "option", "icons", { primary: 'ui-icon-volume-on' } );
  $("#icon_url").button( "option", "icons", { primary: 'ui-icon-link' } );
  $("#icon_image").button( "option", "icons", { primary: 'ui-icon-image' } );
  $("#icon_video").button( "option", "icons", { primary: 'ui-icon-video' } );
  $("#icon_submit").button( "option", "icons", { primary: 'ui-icon-circle-check' } );
  $("#icon_reset").button( "option", "icons", { primary: 'ui-icon-circle-close' } );

  $("#imageUploadSubmitButton").button( "option", "disabled", true);

  $("#icon_settings.reversePostOrder").hover(
    function() {
      $("#icon_settings.reversePostOrder").button("option", "icons", { primary: 'ui-icon-circle-triangle-' + (settings.reversePostOrder ? 's' : 'n') } );

      return false;
    },
    function () {
      $("#icon_settings.reversePostOrder").button("option", "icons", { primary: 'ui-icon-circle-triangle-' + (settings.reversePostOrder ? 'n' : 's') } );

      return false;
    }
  );

  $("#icon_muteSound").hover(
    function() {
      if (settings.audioDing) {
        $("#icon_muteSound").button("option", "icons", { primary: 'ui-icon-volume-off' } );
      }
      else {
        $("#icon_muteSound").button("option", "icons", { primary: 'ui-icon-volume-on' } );
      }

      return false;
    },
    function () {
      if (settings.audioDing) {
        $("#icon_muteSound").button("option", "icons", { primary: 'ui-icon-volume-on' } );
      }
      else {
        $("#icon_muteSound").button("option", "icons", { primary: 'ui-icon-volume-off' } );
      }

      return false;
    }
  );



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

function windowDynaLinks() {
  var noAdminCounter = 0; // This is probably a bad way of doing what we'll do, but meh.
  var noModCounter = 0; // Same as above...


  /* Show All Links */
  $('#moderateCat').show();
  $('#moderateCat').next().children().children().show(); // LIs
  $('#quickCat').next().children().children().show(); // LIs
  $('#moderateCat').next().children().children().children().show(); // Admin LIs
  $('#userMenu li').show(); // Context LIs

  /* Remove Links if Not Available */

  if (!userPermissions.createRoom) {
    $('li > #createRoom').parent().hide();
  }
  if (!userPermissions.privateRoom) {
    $('li > #privateRoom').parent().hide();
    $('#userMenu a[data-action="private_im"]').parent().hide();
  }


  if (!adminPermissions.modUsers) {
    $('li > #modUsers').parent().hide();

    noAdminCounter += 1;
  }
  if (!adminPermissions.modImages) {
    $('li > #modImages').parent().hide();

    noAdminCounter += 1;
  }
  if (!adminPermissions.modCensor) {
    $('li > #modCensor').parent().hide();

    noAdminCounter += 1;
  }
  if (!adminPermissions.modTemplates) {
    $('li > #modPhrases').parent().hide();

    noAdminCounter += 1;
  }
  if (!adminPermissions.modTemplates) {
    $('li > #modTemplates').parent().hide();

    noAdminCounter += 1;
  }
  if (!adminPermissions.modPrivs) {
    $('li > #modPrivs').parent().hide();

    noAdminCounter += 1;
  }
  if (!adminPermissions.modHooks) {
    $('li > #modHooks').parent().hide();

    noAdminCounter += 1;
  }
  if (!adminPermissions.modCore) {
    $('li > #modCore').parent().hide();

    noAdminCounter += 1;
  }

  if (modRooms[roomId] < 1) {
    $('li > #kick').parent().hide();
    $('li > #manageKick').parent().hide();
    $('#userMenu a[data-action="kick"]').parent().hide();

    noModCounter += 2;
  }
  if (modRooms[roomId] < 2) {
    $('li > #editRoom').parent().hide();

    noModCounter += 1;
  }



  /* Remove Link Categories */

  if (noAdminCounter === 8) {
      $('li > #modGeneral').parent().hide();
  }

  if (noModCounter === 3 && noAdminCounter === 8) {
    $('#moderateCat').hide();
  }



  /* Show Login or Logout Only */
  if (userId && !anonId) {
    $('li > #login').parent().hide();
  }
  else {
    $('li > #logout').parent().hide();
  }
}


function contextMenuParse() {
  $('.userName').contextMenu({
    menu: 'userMenu'
  },
  function(action, el) {
    var userId = $(el).attr('data-userId');
    var userName = '';
    var avatarUrl = '';
    var profileUrl = '';

    $.ajax({
      url: directory + 'api/getUsers.php?users=' + userId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,
      type: 'GET',
      timeout: 2400,
      cache: false,
      success: function(xml) {
        userName = unxml($(xml).find('userName').text().trim());
        avatarUrl = unxml($(xml).find('avatar').text().trim());
        profileUrl = unxml($(xml).find('profile').text().trim());

        switch(action) {
          case 'private_im':
          standard.privateRoom(userId);
          break;

          case 'profile':
          window.open(profileUrl);
          break;

          case 'kick':
          popup.kick(userId, roomId);
          break;

          case 'ban': // TODO?
          window.open('moderate.php?do=banuser2&userId=' + userId,'banuser' + userId);
          break;
        }

        return false;
      },
      error: function() {
        dia.error('The information of this user could not be retrieved.');

        return false;
      }
    });

    return false;
  });

  $('.messageLine .messageText').contextMenu({
    menu: 'messageMenu'
  },
  function(action, el) {
    postid = $(el).attr('data-messageid');

    switch(action) {
      case 'delete':
      dia.confirm({
        text : 'Are you sure you want to delete this message?',
        'true' : function() {
          standard.deleteMessage(postid);

          $(el).parent().fadeOut();

          return false;
        }
      });
      break;

      case 'link':
        // TODO
        dia.info('This message can be bookmarked using the following archive link:<br /><br /><input type="text" value="' + currentLocation + '/#page=archive#room=' + $('body').attr('data-roomId') + '#message=' + postid + '" />','Link to This Message');
      break;
    }

    return false;
  });

  $('.messageLine .messageText img').contextMenu({
    menu: 'messageMenuImage'
  },
  function(action, el) {
    postid = $(el).attr('data-messageid');

    switch(action) {
      case 'url':
      var src= $(el).attr('src');

      dia.info('<img src="' + src + '" style="max-width: 550px; max-height: 550px;" /><br /><br /><input type="text" value="' + src +  '" style="width: 550px;" />','Copy Image URL');
      break;

      case 'delete':
      dia.confirm({
        text : 'Are you sure you want to delete this message?',
        'true' : function() {
          standard.deleteMessage(postid);

          $(el).parent().fadeOut();
        }
      });
      break;

      case 'link': // TODO
      dia.info('This message can be bookmarked using the following archive link:<br /><br /><input type="text" value="' + currentLocation + '/#page=archive#room=' + $('body').attr('data-roomId') + '#message=' + postid + '" />','Link to This Message');
      break;
    }

    return false;
  });

  $('.room').contextMenu({
    menu: 'roomMenu'
  },
  function(action, el) {
    switch(action) {
      case 'delete':
      dia.confirm({
        text : 'Are you sure you want to delete this room?',
        'true' : function() {
          standard.deleteRoom($(el).attr('data-roomId'));

          $(el).parent().fadeOut();

          return false;
        }
      });
      break;

      case 'edit':
      popup.editRoom($(el).attr('data-roomId'));
      break;
    }

    return false;
  });

  return false;
}



$(document).ready(function() {
  standard.login({
    sessionHash: sessionHash
  });



  /*** Trigger Login ***/

  if (!userId) { // The user is not actively logged in.
    popup.login();
  }
  $('#login').bind('click',function() {
    popup.login();

    return false;
  });



  /*** Trigger Logout */

  $('#logout').bind('click',function() {
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




  /*** Context Menus ***/

  $.get('template.php','template=contextMenu',function(data) {
    $('body').append(data);

    console.log('Appended Context Menus to DOM');

    return false;
  });



  /*** Upload ***/

  $('#icon_url').bind('click',function() {
    popup.insertDoc('url');

    return false;
  });


  jQTubeUtil.init({
    key: 'AI39si5_Dbv6rqUPbSe8e4RZyXkDM3X0MAAtOgCuqxg_dvGTWCPzrtN_JLh9HlTaoC01hCLZCxeEDOaxsjhnH5p7HhZVnah2iQ',
    orderby: 'relevance',  // *optional -- 'viewCount' is set by default
    time: 'this_month',   // *optional -- 'this_month' is set by default
    maxResults: 20   // *optional -- defined as 10 results by default
  });



  /*** Kick ***/

  $('a#kick').bind('click',function() {
    popup.kick();

    return false;
  });



  /*** Private Room ***/

  $('a#privateRoom').bind('click',function() {
    popup.privateRoom();

    return false;
  });



  /*** Manage Kick ***/

  $('a#manageKick').bind('click',function() {
    popup.manageKicks();

    return false;
  });



  $('#sendForm').bind('submit',function() {
    var message = $('textarea#messageInput').val();

    if (message.length == 0) {
      dia.error('Please enter your message.');
    }
    else {
      standard.sendMessage(message);
      $('textarea#messageInput').val('');
    }

    return false;
  });



  /*** Online ***/

  $('a#online').bind('click',function() {
    popup.online();

    return false;
  });



  /* Create Room */

  $('a#createRoom').bind('click',function() {
    popup.createRoom();

    return false;
  });



  /*** Edit Room ***/

  $('a.editRoomMulti').bind('click',function() {
    popup.editRoom($(this).attr('data-roomId'));

    return false;
  });



  /*** Help ***/

  $('#icon_help').bind('click',function() {
    popup.help();

    return false;
  });



  /*** Room List ***/

  $('#roomList').bind('click',function() {
    popup.selectRoom();

    return false;
  });



  /*** Stats ***/

  $('#viewStats').bind('click',function() {
    popup.viewStats();

    return false;
  });



  /*** Copyright & Credits ***/

  $('#copyrightLink').bind('click',function() {
    popup.copyright();

    return false;
  });



  /*** User Settings ***/

  $('#icon_settings, #changeSettings, a.changeSettingsMulti').bind('click',function() {
    popup.userSettings();

    return false;
  });

  if (typeof prepopup == "function") {
    prepopup();
  }

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

function windowResize () {
  var windowWidth = document.documentElement.clientWidth; // Get the browser window "viewport" width, excluding scrollbars.
  var windowHeight = document.documentElement.clientHeight; // Get the browser window "viewport" height, excluding scrollbars.


  switch (window.layout) { // Determine which layout we are using.
    default: // The main layout.
    $('#messageList').css('height',(windowHeight - 240)); // Set the message list height to fill as much of the screen that remains after the textarea is placed.
    $('#messageList').css('max-width',((windowWidth - 10) * .75)); // Prevent box-stretching. This is common on... many chats.


    /* Body Padding: 10px
      * Right Area Width: 75%
      * "Enter Message" Table Padding: 10px
      *** TD Padding: 2px (on Standard Styling)
      * Message Input Text Area Padding: 3px */
      $('#messageInput').css('width',(((windowWidth - 10) * .75) - 10 - 2)); // Set the messageInput box to fill width.


    $('body').css('height',window.innerHeight); // Set the body height to equal that of the window; this fixes many gradient issues in theming.
    break;

    // TODO
  }

  return false;
}

function windowBlur () {
  window.isBlurred = true;

  return false;
}

function windowFocus() {
  window.isBlurred = false;
  window.clearInterval(timers.t3);
  $('#favicon').attr('href',favicon);

  return false;
}


window.onresize = windowResize;
window.onblur = windowBlur;
window.onfocus = windowFocus;

/*********************************************************
************************* END ***************************
***** Window Manipulation and Multi-Window Handling *****
*********************************************************/