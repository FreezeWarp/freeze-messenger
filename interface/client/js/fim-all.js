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
******************* Static Functions ********************
*********************************************************/

function unxml(data) {
  return data.replace(/\&lt\;/g,'<',data).replace(/\&gt\;/g,'>',data).replace(/\&apos\;/g,"'",data).replace(/\&quot\;/g,'"',data);
}

function urlEncode(data) {
  return data.replace(/\+/g,'%2b').replace(/\&/g,'%26').replace(/\%/g,'%25').replace(/\n/g,'%20');
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

if (typeof console != 'object' || typeof console.log != 'function') {
  console = {
    log : function() {
      return false;
    }
  };
}


dia = {
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
          if ('true' in options) {
            options['true']();
          }

          $(this).dialog("close");
          return true;
        },
        Cancel: function() {
          if ('false' in options) {
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
    var ajax;

    if (options.uri) {
      options.content = '<img src="images/ajax-loader.gif" align="center" />';

      ajax = true;
    }
    else if (options.content) {
    }
    else {
      console.log('No content found for dialog; exiting.');

      return false;
    }

    if (typeof options.autoOpen != 'undefined' && options.autoOpen == false) {
      var autoOpen = false;
    }
    else {
      var autoOpen = true;
    }

    var windowWidth = document.documentElement.clientWidth;
    if (options.width > windowWidth) {
      options.width = windowWidth;
    }
    else if (!options.width) {
      options.widthwidth = 600;
    }

    var dialogOptions = {
      width: options.width,
      title: options.title,
      hide: "puff",
      modal: true,
      buttons : options.buttons,
      autoOpen: autoOpen,
      open: function() {
        if ('oF' in options) {
          options['oF']();
        }

        return false
      },
      close: function() {
        $('#' + options.id).empty().remove(); // Housecleaning, needed if we want the next dialouge to work properly.
        if ('cF' in options) {
          options['cF']();
        }

        return false
      }
    };

    var tabsOptions = {
      selected : options.selectTab
    };


    var dialog = $('<div style="display: none;" id="' + options.id +  '">' + options.content + '</div>').appendTo('body');



    if (ajax) {
      var overlay = $('<div class="ui-widget-overlay"></div>').appendTo('body').width($(document).width()).height($(document).height());
      var throbber = $('<img src="images/ajax-loader.gif" />').appendTo('body').css('position','absolute').offset({ left : (($(window).width() - 220) / 2), top : (($(window).height() - 19) / 2)});

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

/*********************************************************
************************* END ***************************
******************* Static Functions ********************
*********************************************************/








/*********************************************************
************************ START **************************
******************* Variable Setting ********************
*********************************************************/

/* Common Variables */

var userId; // The user ID who is logged in.
var roomId; // The ID of the room we are in.
var sessionHash; // The session hash of the active user.
var anonId; // ID used to represent anonymous posters.
var prepopup;


/* Function-Specific Variables */

window.isBlurred = false; // By default, we assume the window is active and not blurred.
var topic;
var notify = true;
var favicon = $('#favicon').attr('href');

var uploadSettings = new Object;
var requestSettings = {
  longPolling : false, // We may set this to true if the server supports it.
  timeout : 2400, // We may increase this dramatically if the server supports longPolling.
  firstRequest : true,
  totalFails : 0,
  lastMessage : 0
};
var timers = new Object;



/* Get Cookies */

var layout = $.cookie('fim3_layout'); // TODO
var settingsBitfield = parseInt($.cookie('fim3_settings'));
var themeId = parseInt($.cookie('fim3_themeId'));

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



/* Get Server-Specific Variables
* We Should Not Call This Again */
$.ajax({
  url: directory + 'api/getServerStatus.php',
  type: 'GET',
  timeout: 5000,
  cache: false,
  success: function(xml) {
    requestSettings.longPolling = ($('serverStatus > requestMethods > longPoll').text().trim() == 'true' ? true : false);

    return false;
  },
  error: function() {
    requestSettings.longPolling = false;

    return false;
  }
});



/* URL-Defined Actions
* TODO */
function hashParse() {
  var urlHash = window.location.hash;
  var urlHashComponents = urlHash.split('#');
  var page = '';

  for (var i = 0; i < urlHashComponents.length; i++) {
    if (!urlHashComponents[i]) {
      continue;
    }

    var componentPieces = urlHashComponents[i].split('=');
    switch (componentPieces[0]) {
      case 'page':
      page = componentPieces[1]
      break;

      case 'room':
      roomId = componentPieces[1];
      break;

      case 'message':
      messageId = componentPieces[1];
      break;
    }
  }

  switch (page) {
    case 'archive':
    prepopup = function() {
      popup.archive({
        'roomId' : roomId,
        'idMin' : messageId - 1,
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
  showAvatars : (settingsBitfield & 2048 ? true : false), // Use the complex document style?
  reversePostOrder : (settingsBitfield & 1024 ? true : false), // Show posts in reverse?
  audioDing : (settingsBitfield & 8192 ? true : false), // Fire an HTML5 audio ding during each unread message?
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



/* Objects for Cleanness, Caching. */

var roomRef = new Object;
var roomIdRef = new Object;
var roomList = new Array;
var modRooms = new Object; // Rooms which the user has special permissions in.

var userRef = new Object;
var userIdRef = new Object;
var userList = new Array;

var groupRef = new Object;
var groupList = new Array;
var groupIdRef = new Object;

var messageIndex = new Array;


var roomUlFavHtml = '';
var roomUlMyHtml = '';
var roomUlPrivHtml = '';
var roomUlHtml = '';
var ulText = '';
var roomTableHtml = '';
var roomSelectHtml = '';

var userSelectHtml = '';


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
        userList = new Array; // Clear so we don't get repeat values on regeneration.
        userRef = new Object;
        userSelectHtml = '';

        console.log('Users obtained.');

        $(xml).find('user').each(function() {
          var userName = unxml($(this).find('userName').text().trim());
          var userId = parseInt($(this).find('userId').text().trim());

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
        roomList = new Array; // Clear so we don't get repeat values on regeneration.
        roomIdRef = new Object;
        roomRef = new Object;
        roomTableHtml = '';
        roomSelectHtml = '';
        roomUlHtml = '';
        roomUlPrivHtml = '';
        roomUlMyHtml = '';
        roomUlFavHtml = '';

        $(xml).find('room').each(function() {
          var roomName = unxml($(this).find('roomName').text().trim());
          var roomId = parseInt($(this).find('roomId').text().trim());
          var roomTopic = unxml($(this).find('roomTopic').text().trim());
          var isFav = ($(this).find('favorite').text().trim() == 'true' ? true : false);
          var isPriv = ($(this).find('optionDefinitions > privateIm').text().trim() == 'true' ? true : false);
          var isAdmin = ($(this).find('canAdmin').text().trim() === 'true' ? true : false);
          var isModerator = ($(this).find('canModerate').text().trim() === 'true' ? true : false);
          var isOwner = (parseInt($(this).find('owner').text().trim()) == userId ? true : false);

          var ulText = '<li><a href="#room=' + roomId + '">' + roomName + '</a></li>';

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
          var groupName = unxml($(this).find('groupName').text().trim());
          var groupId = parseInt($(this).find('groupId').text().trim());

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
  $.ajax({
    url: 'uploadFile.php',
    type: 'POST',
    contentType: 'application/x-www-form-urlencoded;charset=UTF-8',
    cache: false,
    data: 'method=youtube&room=' + roomId + '&youtubeUpload=' + escape('http://www.youtube.com/?v=' + id),
    success: function(html) { /*standard.getMessages();*/ }
  });

  $('#textentryBoxYoutube').dialog('close');
}


function updateVids(searchPhrase) {
  jQTubeUtil.search(searchPhrase, function(response) {
    var html = "";
    var num = 0;

    for (vid in response.videos) {
      var video = response.videos[vid];
      num ++;

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
  addEntry : function(type,source,id) {
    if (!id) {
      var val = $("#" + type + "Bridge").val();
      switch(type) {
        case 'watchRooms':
        var id = roomRef[val];
        var type2 = 'Room';
        break;

        case 'moderators':
        case 'allowedUsers':
        var id = userRef[val];
        var type2 = 'User';
        break;

        case 'allowedGroups':
        var id = groupRef[val];
        var type2 = 'Group';
        break;
      }
    }
    else {
      switch(type) {
        case 'watchRooms':
        var val = roomIdRef[id];
        var type2 = 'Room';
        break;

        case 'moderators':
        case 'allowedUsers':
        var val = userIdRef[id];
        var type2 = 'User';
        break;

        case 'allowedGroups':
        var val = groupIdRef[id];
        var type2 = 'Group';
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

  removeEntry : function(type,id) {
    $("#" + type + "SubList" + id).fadeOut(500, function() {
      $(this).remove();
    });

    var currentRooms = $("#" + type).val().split(",");

    for (var i = 0; i < currentRooms.length; i++) {
      if(currentRooms[i] == id) {
        currentRooms.splice(i, 1);
        break;
      }
    }

    $("#" + type).val(currentRooms.toString(","));

    return false;
  },

  showEntries : function(type,string) {
    entryList = string.split(',');


    switch(type) {
      case 'watchRooms':
      var source = roomRef;
      break;

      case 'moderators':
      case 'allowedUsers':
      var source = userRef;
      break;

      case 'allowedGroups':
      var source = groupRef;
      break;
    }


    for (var i = 0; i < entryList.length; i++) {
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
    var encrypt = 'base64';
    var lastMessage = 0;
    var firstMessage = 0;
    var data = '';

    if (options.idMax) {
      var where = 'messageIdEnd=' + options.idMax;
    }
    else if (options.idMin) {
      var where = 'messageIdStart=' + options.idMin;
    }
    else {
      var where = 'messageIdStart=0';
    }

    $.when( $.ajax({
      url: directory + 'api/getMessages.php?rooms=' + options.roomId + '&archive=1&messageLimit=20&' + where + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,
      type: 'GET',
      timeout: 1000,
      data: '',
      contentType: "text/xml; charset=utf-8",
      dataType: "xml",
      cache: false,
      success: function (xml) {

        if ($(xml).find('messages > message').length > 0) {
          $(xml).find('messages > message').each(function() {
            var text = unxml($(this).find('htmlText').text().trim());
            var messageTime = $(this).find('messageTimeFormatted').text().trim();
            var messageId = parseInt($(this).find('messageId').text().trim());

            var userName = $(this).find('userData > userName').text().trim();
            var userId = parseInt($(this).find('userData > userId').text().trim());
            var groupFormatStart = unxml($(this).find('userData > startTag').text().trim());
            var groupFormatEnd = unxml($(this).find('userData > endTag').text().trim());

            var styleColor = $(this).find('defaultFormatting > color').text().trim();
            var styleHighlight = $(this).find('defaultFormatting > highlight').text().trim();
            var styleFontface = $(this).find('defaultFormatting > fontface').text().trim();
            var styleGeneral = parseInt($(this).find('defaultFormatting > general').text().trim());

            var style = 'color: rgb(' + styleColor + '); background: rgb(' + styleHighlight + '); font-family: ' + styleFontface + ';';

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

          if (typeof contextMenuParse === 'function') { // TODO
            contextMenuParse();
          }
        }

        return false;
      },
      error: function() {
        dia.error('Archive failed to obtain results from server.');

        return false;
      }
    })).done(function() {
      $('#archiveMessageList').html(data);
      $('#archiveNext').attr('onclick','standard.archive({idMin : ' + lastMessage + ', roomId: ' + options.roomId + '});');
      $('#archivePrev').attr('onclick','standard.archive({idMax : ' + firstMessage + ', roomId: ' + options.roomId + '});');

      if (options.callback) {
        options.callback(data);
      }

      return false;
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
      //  var password = md5(password);
      //  var passwordEncrypt = 'md5';

      data = 'userName=' + options.userName + '&password=' + options.password + '&passwordEncrypt=' + passwordEncrypt;
    }
    else if (options.sessionHash) {
      data = 'sessionHash=' + options.sessionHash + '&apiLogin=1';
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
          var loginFlag = unxml($(xml).find('loginFlag').text().trim());
          var loginText = unxml($(xml).find('loginText').text().trim());
          var valid = unxml($(xml).find('valid').text().trim());
          var userName = unxml($(xml).find('userData > userName').text().trim());
          var defaultRoomId = parseInt($(xml).find('defaultRoomId').text().trim());

          userId = parseInt($(xml).find('userData > userId').text().trim());
          anonId = parseInt($(xml).find('anonId').text().trim());
          sessionHash = unxml($(xml).find('sessionHash').text().trim());

          $.cookie('fim3_sessionHash',sessionHash); // Set cookies.
          $.cookie('fim3_userId',userId);



          /* Update Permissions */

          userPermissions.createRoom = (parseInt($(xml).find('userPermissions > createRooms').text().trim()) > 0 ? true : false);
          userPermissions.privateRoom = (parseInt($(xml).find('userPermissions > privateRooms').text().trim()) > 0 ? true : false);
          userPermissions.general = (parseInt($(xml).find('userPermissions > allowed').text().trim()) > 0 ? true : false);


          adminPermissions.modPrivs = (parseInt($(xml).find('adminPermissions > modPrivs').text().trim()) > 0 ? true : false);
          adminPermissions.modCore = (parseInt($(xml).find('adminPermissions > modCore').text().trim()) > 0 ? true : false);
          adminPermissions.modUsers = (parseInt($(xml).find('adminPermissions > modUsers').text().trim()) > 0 ? true : false);
          adminPermissions.modTemplates = (parseInt($(xml).find('adminPermissions > modTemplates').text().trim()) > 0 ? true : false);
          adminPermissions.modImages = (parseInt($(xml).find('adminPermissions > modImages').text().trim()) > 0 ? true : false);
          adminPermissions.modCensor = (parseInt($(xml).find('adminPermissions > modCensor').text().trim()) > 0 ? true : false);
          adminPermissions.modHooks = (parseInt($(xml).find('adminPermissions > modHooks').text().trim()) > 0 ? true : false);



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
          var errStr = $(xml).find('errStr').text().trim();
          var errDesc = $(xml).find('errDesc').text().trim();

          if (errStr) {
            var sentUserId = $(xml).find('activeUser > userId');

            if (errStr == 'noperm') {
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
            var activeUserHtml = new Array;


            $(xml).find('activeUsers > user').each(function() {
              var userName = $(this).find('userName').text().trim();
              var userId = $(this).find('userId').text().trim();
              var userGroup = $(this).find('userGroup').text().trim();
              var startTag = unxml($(this).find('startTag').text().trim());
              var endTag = unxml($(this).find('endTag').text().trim());

              activeUserHtml.push('<span class="userName" data-userId="' + userId + '">' + startTag + '<span class="username">' + userName + '</span>' + endTag + '</span>');
            });

            $('#activeUsers').html(activeUserHtml.join(', '));


            if ($(xml).find('messages > message').length > 0) {
              $(xml).find('messages > message').each(function() {

                var text = unxml($(this).find('htmlText').text().trim());
                var messageTime = unxml($(this).find('messageTimeFormatted').text().trim());

                var messageId = parseInt($(this).find('messageId').text().trim());

                var userName = unxml($(this).find('userData > userName').text().trim());
                var userId = parseInt($(this).find('userData > userId').text().trim());
                var groupFormatStart = unxml($(this).find('userData > startTag').text().trim());
                var groupFormatEnd = unxml($(this).find('userData > endTag').text().trim());
                var avatar = unxml($(this).find('userData > avatar').text().trim());

                var styleColor = unxml($(this).find('defaultFormatting > color').text().trim());
                var styleHighlight = unxml($(this).find('defaultFormatting > highlight').text().trim());
                var styleFontface = unxml($(this).find('defaultFormatting > fontface').text().trim());
                var styleGeneral = parseInt($(this).find('defaultFormatting > general').text().trim());

                var style = 'color: rgb(' + styleColor + '); background: rgb(' + styleHighlight + '); font-family: ' + styleFontface + ';';

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

                if (messageIndex.length == 100) {
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
                    notify.webkitNotifyRequest('images/favicon.gif', 'New Message', notifyData);
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
            }

            if (typeof contextMenuParse === 'function') {
              contextMenuParse();
            }

            if (requestSettings.longPolling) {
              setTimeout(standard.getMessages,50);
            }
            else {
              requestSettings.timeout = 2400;
              setTimeout(standard.getMessages,500);
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
                var wait = 30000;
                requestSettings.timeout = 29900;

                // TODO: Add indicator.
              }
              else if (requestSettings.totalFails > 5) {
                var wait = 10000;
                requestSettings.timeout = 9900;

                // TODO: Add indicator.
              }
              else {
                var wait = 5000;
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


  sendMessage: function(message,confirmed) {
    if (!roomId) {
      popup.selectRoom();
    }
    else {
      confirmed = (confirmed === 1 ? 1 : '');

      $.ajax({
        url: directory + 'api/sendMessage.php?fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,
        type: 'POST',
        data: 'roomId=' + roomId + '&confirmed=' + confirmed + '&message=' + urlEncode(message),
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
            dia.error(errDesc + '<br /><br /><button type="button" onclick="$(this).parent().dialog(&apos;close&apos;);">No</button><button type="button" onclick="standard.standard.sendMessage(&apos;' + escape(message) + '&apos;,1); $(this).parent().dialog(&apos;close&apos;);">Yes</button>');
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
      var errStr = $(xml).find('errStr').text().trim();
      var errDesc = $(xml).find('errDesc').text().trim();

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
    if (userLocalId == userId) {
      dia.error('You can\'t talk to yourself...');
    }
    else {
      $.post(directory + 'api/moderate.php','action=privateRoom&userId=' + userLocalId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,function(xml) {
        var privateRoomId = parseInt($(xml).find('insertId').text().trim());
        var errStr = unxml($(xml).find('errStr').text().trim());
        var errDesc = unxml($(xml).find('errStr').text().trim());

        if (errStr) {
          switch (errStr) {
            case 'baduser':
            dia.error('The user specified does not exist.');
            break;
          }
        }
        else {
          dia.full({
            content : 'You may talk to this person privately at this link: <form method="post" onsubmit="return false;"><input type="text" style="width: 300px;" value="' + currentLocation + '#room=' + privateRoomId + '" name="url" /></form>',
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
      var errStr = $(xml).find('errStr').text().trim();
      var errDesc = $(xml).find('errDesc').text().trim();

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
      var errStr = $(xml).find('errStr').text().trim();
      var errDesc = $(xml).find('errDesc').text().trim();

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
            var userName = $('#loginForm > #userName').val();
            var password = $('#loginForm > #password').val();

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
        $('button.editRoomMulti').button({icons : {primary : 'ui-icon-gear'}}).click(function() {
          popup.editRoom($(this).attr('data-roomId'));

          return false;
        });

        $('input[type=checkbox].favRoomMulti').button({icons : {primary : 'ui-icon-star'}}).change(function() {
          if ($(this).is(':checked')) {
            standard.favRoom($(this).attr('data-roomId'));
          }
          else {
            standard.unfavRoom($(this).attr('data-roomId'));
          }

          return false;
        });

        $('button.archiveMulti').button({icons : {primary : 'ui-icon-note'}}).click(function() {
          popup.archive($(this).attr('data-roomId'));

          return false;
        });

        $('button.deleteRoomMulti').button({icons : {primary : 'ui-icon-trash'}}).click(function() {
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
    var fileContent;

    switch(preselect) {
      case 'video':
      var selectTab = 2;
      break;

      case 'image':
      var selectTab = 1;
      break;

      case 'link':
      default:
      var selectTab = 0;
      break;
    }

    dia.full({
      uri : 'template.php?template=insertDoc',
      id : 'insertDoc',
      width: 600,
      tabs : true,
      oF : function() {

        $('#fileUpload').attr('disabled','disabled').button({disabled: true});

        if (typeof FileReader == 'undefined') {
          dia.error('Your browser does not support file uploads.');
        }
        else {
          $('#fileUpload').change(function() {
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

              reader.readAsBinaryString(file);
              reader.onloadend = function() {
                fileContent = window.btoa(reader.result);

                console.log(fileContent);
              };

              var fileName = file.name;
              var fileSize = file.size;

              if (!fileName.match(/\.(jpg|jpeg|gif|png|svg)$/i)) {
                $('#preview').html('Wrong file type.');
              }
              else if (fileSize > 4 * 1000 * 1000) {
                $('#preview').html('File too large.');
              }
              else {
                var reader = new FileReader();
                reader.readAsDataURL(file);

                reader.onloadend = function () {
                  var fileContent = reader.result;
                  fileContainer = '<img src="' + fileContent + '" alt="" style="max-width: 200px; max-height: 250px; height: auto;" />';
                  $('#preview').html(fileContainer);

                  return false;
                };
              }

              fileContent = $('#urlUpload').val();
              if (fileContent && fileContent != 'http://') {
                fileContainer = '<img src="' + fileContent + '" alt="" style="max-width: 200px; max-height: 250px; height: auto;" />';

                $('#preview').html(fileContainer);
              }

              $('#imageUploadSubmitButton').removeAttr('disabled').button({disabled: false});
            }
          });


          $('#uploadFileForm').submit(function() {
            $.ajax({
              url: directory + 'api/uploadFile.php',
              type: 'POST',
              data : 'dataEncode=base64&uploadMethod=raw&autoInsert=true&roomId=' + roomId + '&file_data=' + urlEncode(fileContent) + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,
              cache : false
            });

            return false;
          });
        }

        return false;
      },
      selectTab : selectTab
    });

    return false;
  },

  /*** END Insert Docs ***/




  /*** START Stats ***/

  viewStats : function() {
    var statsHtml = new Object;
    var statsHtml2 = '';
    var roomHtml = '';
    var number = 10;

    for (var i = 1; i <= number; i++) {
      statsHtml[i] = '';
    }

    $.ajax({
      url: directory + 'api/getStats.php?rooms=' + roomId + '&maxResults=' + number + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(xml) {
        $(xml).find('room').each(function() {
          var roomName = unxml($(this).find('roomName').text().trim());
          var roomId = parseInt($(this).find('roomId').text().trim());

          $(this).find('user').each(function() {
            var userName = unxml($(this).find('userData > userName').text().trim());
            var userId = parseInt($(this).find('userData > userId').text().trim());
            var startTag = unxml($(this).find('userData > startTag').text().trim());
            var endTag = unxml($(this).find('userData > endTag').text().trim());
            var position = parseInt($(this).find('position').text().trim());
            var messageCount = parseInt($(this).find('messageCount').text().trim());

            statsHtml[position] += '<td>' + startTag + userName + endTag + ' (' + messageCount + ')</td>';
          });


          roomHtml += '<th>' + roomName + '</th>';

        });

        for (var i = 1; i <= number; i++) {
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
      title : 'changeSettingsDialogue',
      tabs : true,
      width : 1000,
      cF : function() {
        $('.colorpicker').empty().remove();

        return false;
      },
      oF : function() {
        $('#settingsOfficialAjax_theme').change(function() {
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


        $('#showAvatars').change(function() {
          if ($(this).val() == 'true' && !settings.showAvatars) {
            settings.showAvatars = true;
            $('#messageList').html('');
            $.cookie('fim3_settings',$.cookie('fim3_settings') + 2048);

            requestSettings.firstRequest= true;
          }
          else if ($(this).val() != 'true' && settings.showAvatars) {
            settings.showAvatars = false;
            $('#messageList').html('');
            $.cookie('fim3_settings',$.cookie('fim3_settings') - 2048);

            requestSettings.firstRequest= true;
          }

          return false;
        });

        $('#reversePostOrder').change(function() {
          if ($(this).val() == 'true' && !settings.reversePostOrder) {
            settings.reversePostOrder = true;
            $('#messageList').html('');
            $.cookie('fim3_settings',$.cookie('fim3_settings') + 1024);

            requestSettings.firstRequest= true;
          }
          else if ($(this).val() != 'true' && settings.reversePostOrder) {
            settings.reversePostOrder = false;
            $('#messageList').html('');
            $.cookie('fim3_settings',$.cookie('fim3_settings') - 1024);

            requestSettings.firstRequest= true;
          }

          return false;
        });

        $('#audioDing').change(function() {
          if ($(this).val() == 'true' && !settings.audioDing) {
            settings.audioDing = true;
            $('#messageList').html('');
            $.cookie('fim3_settings',$.cookie('fim3_settings') + 8192);

            requestSettings.firstRequest= true;
          }
          else if ($(this).val() != 'true' && settings.audioDing) {
            settings.audioDing = false;
            $('#messageList').html('');
            $.cookie('fim3_settings',$.cookie('fim3_settings') - 8192);

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
              var fontName = unxml($(this).find('fontName').text().trim());
              var fontId = parseInt($(this).find('fontId').text().trim());
              var fontGroup = unxml($(this).find('fontGroup').text().trim());
              var fontData = unxml($(this).find('fontData').text().trim());

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
            $('#defaultHighlight').css('background-color','#' + hex);
            $('#defaultHighlight').val(hex);
            $('#fontPreview').css('background-color','#' + hex);
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
            $('#defaultColour').css('background-color','#' + hex);
            $('#defaultColour').val(hex);
            $('#fontPreview').css('color','#' + hex);
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

        $("#changeSettingsForm").submit(function() { // TODO
          data = $("#changeSettingsForm").serialize(); // Serialize the form data for AJAX.
          $.post(directory + 'api/moderate.php?action=userOptions&userId=' + userId + data + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,function(xml) {
            // TODO
          }); // Send the form data via AJAX.

          $("#changeSettingsDialogue").empty().remove(); // Housecleaning, needed if we want the colorpicker to work in another changesettings dialogue.
          $(".colorpicker").empty().remove(); // Housecleaning, needed if we want the colorpicker to work in another changesettings dialogue.

          window.reload();

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
            var data = '';

            var roomName = unxml($(xml).find('roomName').text().trim());
            var roomId = parseInt($(xml).find('roomId').text().trim());
            var allowedUsers = $(xml).find('allowedUsers').text().trim();
            var allowedGroups = $(xml).find('allowedGroups').text().trim();
            var moderators = $(xml).find('moderators').text().trim();
            var mature = ($(xml).find('optionDefinitions > mature').text().trim() === 'true' ? true : false);

            $('#name').val(roomName);

            if (allowedUsers != '*' && allowedUsers != '') {
              autoEntry.showEntries('allowedUsers',allowedUsers);
            }


            if (moderators != '*' && moderators != '') {
              autoEntry.showEntries('moderators',moderators);
            }


            if (allowedGroups != '*' && allowedGroups != '') {
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
          var bbcode = parseInt($('#bbcode > option:selected').val());
          var name = $('#name').val();
          var mature = ($('#mature').is(':checked') ? true : false);
          var allowedUsers = $('#allowedUsers').val();
          var allowedGroups = $('#allowedGroups').val();
          var moderators = $('#moderators').val()

          if (name.length > 20) {
            dia.error('The roomname is too long.');
          }
          else {
            $.post(directory + 'api/moderate.php','action=editRoom&roomId=' + roomIdLocal + '&name=' + urlEncode(name) + '&bbcode=' + bbcode + '&mature=' + mature + '&allowedUsers=' + allowedUsers + '&allowedGroups=' + allowedGroups + '&moderators=' + moderators + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,function(xml) {
              var errStr = unxml($(xml).find('errStr').text().trim());
              var errDesc = unxml($(xml).find('errDesc').text().trim());

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
          var bbcode = parseInt($('#bbcode').val());
          var name = $('#name').val();
          var mature = ($('#mature').is(':checked') ? true : false);
          var allowedUsers = $('#allowedUsersBridge').val();
          var allowedGroups = $('#allowedGroupsBridge').val();
          var moderators = $('#moderatorsBridge').val()

          if (name.length > 20) {
            dia.error('The roomname is too long.');
          }
          else {
            $.post(directory + 'api/moderate.php','action=createRoom&name=' + urlEncode(name) + '&bbcode=' + bbcode + '&mature=' + mature + '&allowedUsers=' + allowedUsers + '&allowedGroups=' + allowedGroups + '&moderators=' + moderators + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId,function(xml) {
              var errStr = unxml($(xml).find('errStr').text().trim());
              var errDesc = unxml($(xml).find('errDesc').text().trim());
              var createRoomId = parseInt($(xml).find('insertId').text().trim());

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
            var userId = parseInt($(this).find('userId').text().trim());
            var startTag = unxml($(this).find('startTag').text().trim());
            var endTag = unxml($(this).find('endTag').text().trim());
            var roomData = new Array();

            $(this).find('room').each(function() {
              var roomId = parseInt($(this).find('roomId').text().trim());
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
          var kickerId = parseInt($(this).find('kickerData > userId').text().trim());
          var kickerName = $(this).find('kickerData > userName').text().trim();
          var kickerFormatStart = $(this).find('kickerData > userFormatStart').text().trim();
          var kickerFormatEnd = $(this).find('kickerData > userFormatEnd').text().trim();
          var userId = parseInt($(this).find('userData > userId').text().trim());
          var userName = $(this).find('userData > userName').text().trim();
          var userFormatStart = $(this).find('userData > userFormatStart').text().trim();
          var userFormatEnd = $(this).find('userData > userFormatEnd').text().trim();
          var length = parseInt($(this).find('length').text().trim());
          var set = unxml($(this).find('setFormatted').text().trim());
          var expires = unxml($(this).find('expiresFormatted').text().trim());

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
  //      $.post("content/unkick.php?phase=2",data,function(html) {
  //        quickDialogue(html,'','unkickDialogue');
  //      }); // Send the form data via AJAX.

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
        roomModList = new Array();

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

          var length = Math.floor(parseInt($('#time').val() * parseInt($('#interval > option:selected').attr('value'))));

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
      content : '<form id="archiveSearch" action="#" method="get"><input type="text" name="searchText" /></form> <table class="center"><thead><tr><th style="width: 20%;">User</th><th style="width: 20%;">Time</th><th style="width: 60%;">Message</th></tr></thead><tbody id="archiveMessageList"></tbody></table><br /><br /><button id="archivePrev"><< Prev</button><button id="archiveNext">Next >></button>',
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
          var userName = unxml($(xml).find('user > userName').text().trim());
          var userId = parseInt($(xml).find('user > userId').text().trim());
          var startTag = unxml($(xml).find('user > startTag').text().trim());
          var endTag = unxml($(xml).find('user > endTag').text().trim());
          var userTitle = unxml($(xml).find('user > userTitle').text().trim());
          var posts = parseInt($(xml).find('user > postCount').text().trim());
          var joinDate = unxml($(xml).find('user > joinDateFormatted').text().trim());
          var avatar = unxml($(xml).find('user > avatar').text().trim());

          content.html('<div style="width: 400px;">' + (avatar.length > 0 ? '<img alt="" src="' + avatar + '" style="float: left;" />' : '') + '<span class="userName" data-userId="' + userId + '">' + startTag + userName + endTag + '</span>' + (userTitle.length > 0 ? '<br />' + userTitle : '') + '<br /><em>Posts</em>: ' + posts + '<br /><em>Member Since</em>: ' + joinDate + '</div>');

          return false;
        });
      }

      return false;
    }
  });


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




  /*** Archive ***/

  $('#icon_note, #messageArchive').click(function() {
    popup.archive(roomId);

    return false;
  });



  /*** Edit Room ***/

  $('a#editRoom').click(function() {
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


  /* Remove Links if Not Available */

  if (!userPermissions.createRoom) {
    $('li > #createRoom').parent().hide();
  }
  if (!userPermissions.privateRoom) {
    $('li > #privateRoom').parent().hide();
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
/*  $('.userName').contextMenu({
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
        userName = unxml($(this).find('userName').text().trim());
        avatarUrl = parseInt($(this).find('avatar').text().trim());
        profileUrl = parseInt($(this).find('profile').text().trim());

        return false;
      },
      error: function() {
        dia.error('The information of this user could not be retrieved.');

        return false;
      }
    });

    switch(action) {
      case 'private_im':
      standard.privateRoom(userId);
      break;

      case 'profile':
      window.open(profileUrl + 'member.php?u=' + userId,'profile' + userId);
      break;

      case 'kick':
      popup.kick(userId, roomId);
      break;

      case 'ban': // TODO?
      window.open('moderate.php?do=banuser2&userId=' + userId,'banuser' + userId);
      break;
    }

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
  });*/

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
  $('#login').click(function() {
    popup.login();

    return false;
  });



  /*** Trigger Logout */
  $('#logout').click(function() {
    popup.login();

    return false;
  });




  /*** WIP ***/

  $('#showMoreRooms').click(function() {
    $('#roomListShort').slideUp();
    $('#roomListLong').slideDown();

    return false;
  });

  $('#showFewerRooms').click(function() {
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



  /*** ??? ***/

  $('#icon_url').click(function() {
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

  $('a#kick').click(function() {
    popup.kick();

    return false;
  });



  /*** Private Room ***/

  $('a#privateRoom').click(function() {
    popup.privateRoom();

    return false;
  });



  /*** Manage Kick ***/

  $('a#manageKick').click(function() {
    popup.manageKicks();

    return false;
  });



  $('#sendForm').submit(function() {
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

  $('a#online').click(function() {
    popup.online();

    return false;
  });



  /* Create Room */

  $('a#createRoom').click(function() {
    popup.createRoom();

    return false;
  });



  /*** Edit Room ***/

  $('a.editRoomMulti').click(function() {
    popup.editRoom($(this).attr('data-roomId'));

    return false;
  });



  /*** Help ***/

  $('#icon_help').click(function() {
    popup.help();

    return false;
  });



  /*** Room List ***/

  $('#roomList').click(function() {
    popup.selectRoom();

    return false;
  });



  /*** Stats ***/

  $('#viewStats').click(function() {
    popup.viewStats();

    return false;
  });



  /*** Copyright & Credits ***/

  $('#copyrightLink').click(function() {
    popup.copyright();

    return false;
  });



  /*** User Settings ***/

  $('#icon_settings, #changeSettings, a.changeSettingsMulti').click(function() {
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