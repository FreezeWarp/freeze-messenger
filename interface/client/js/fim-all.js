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
  data = data.replace(/\&lt\;/g,'<',data).replace(/\&gt\;/g,'>',data).replace(/\&apos\;/g,"'",data).replace(/\&quot\;/g,'"',data);

  return data;
}

function urlEncode(data) {
  return data.replace(/\+/g,'%2b').replace(/\&/g,'%26').replace(/\%/g,'%25').replace(/\n/g,'%20');
}

function toBottom() {
  document.getElementById('messageList').scrollTop = document.getElementById('messageList').scrollHeight;
}

function faviconFlash() {
  if ($('#favicon').attr('href') === favicon) {
    $('#favicon').attr('href','images/favicon2.gif');
  }
  else {
    $('#favicon').attr('href',favicon);
  }
}

if (typeof console != 'object' || typeof console.log != 'function') {
  console = {
    log : function() {
      // Do nothing?
    },
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
        }
      }
    });
  },

  info : function(message, title) {
    $('<div style="display: none;">' + message + '</div>').dialog({
      title : title,
      modal : true,
      buttons: {
        Okay: function() {
          $( this ).dialog( "close" );
        }
      }
    });
  },

  confirm : function(text) {
    $('<div id="dialog-confirm"><span class="ui-icon ui-icon-alert" style="float: left; margin: 0px 7px 20px 0px;"></span>' + text + '</div>').dialog({
      resizable: false,
      height: 240,
      modal: true,
      hide: "puff",
      buttons: {
        Confirm: function() {
          $(this).dialog("close");
          return true;
        },
        Cancel: function() {
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
      autoOpen: autoOpen,
      open: function() {
        if (options.oF) {
          options.oF();
        }
      },
      close: function() {
        $('#' + options.id).empty().remove(); // Housecleaning, needed if we want the next dialouge to work properly.
        if (options.cF) {
          options.cF();
        }
      }
    };

    var tabsOptions = {
      selected : options.selectTab,
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
        },
        error : function() {
          overlay.empty().remove();
          throbber.empty().remove();

          dialog.dialog('close');

          dia.error('Could not request dialog URI.');
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
  },
};

/*********************************************************
 ************************* END ***************************
 ******************* Static Functions ********************
 *********************************************************/








/*********************************************************
 ************************ START **************************
 ******************* Variable Setting ********************
 *********************************************************/


/* Define Variables as Glboal */

var userId; // The user ID who is logged in.
var roomId; // The ID of the room we are in.
var sessionHash; // The session hash of the active user.
var anonId; // ID used to represent anonymous posters.


if ($.cookie('fim3_sessionHash')) {
  sessionHash = $.cookie('fim3_sessionHash');
  userId = $.cookie('fim3_userId');
}

if ($.cookie('fim3_defaultRoomId')) {
  roomId = $.cookie('fim3_defaultRoomId');

  standard.changeRoom(roomId);
}


var blur = false; // By default, we assume the window is active and not blurred.
var totalFails = 0;
var timer3;
var topic;
var lastMessage = 0;
var messages;
var activeUsers;
var notify = true;
var first = true;
var favicon = $('#favicon').attr('href');
var longPolling = true; // Use experimental longPolling technology?
var timeout = (longPolling ? 1000000 : 2400);
var layout = $.cookie('fim3_layout'); // TODO
var settingsBitfield = parseInt($.cookie('fim3_settings'));
var themeId = parseInt($.cookie('fim3_themeId'));


var userPermissions = {
  createRoom : false,
  privateRoom : false,
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
  modTranslations : false,
};

var settings = {
  showAvatars : (settingsBitfield & 2048 ? true : false), // Use the complex document style?
  reversePostOrder : (settingsBitfield & 1024 ? true : false), // Show posts in reverse?
  audioDing : (settingsBitfield & 8192 ? true : false), // Fire an HTML5 audio ding during each unread message?
  disableImages : (settingsBitfield & 32 ? true : false),
  disableVideos : (settingsBitfield & 64 ? true : false),
  disableFormatting : (settingsBitfield & 16 ? true : false),
};


var themes = {
  1 : 'ui-darkness',
  2 : 'ui-lightness',
  3 : 'redmond',
  4 : 'cupertino',
  5 : 'dark-hive',
  6 : 'start',
  7 : 'vader',
  8 : 'trontastic',
  9 : 'humanity',
};

var themeName = (themeId ? themes[themeId] : 'cupertino');

$('head').append('<link rel="stylesheet" type="text/css" id="stylesjQ" href="client/css/' + themeName + '/jquery-ui-1.8.13.custom.css" media="screen" />');
$('head').append('<link rel="stylesheet" type="text/css" id="stylesFIM" href="client/css/' + themeName + '/fim.css" media="screen" />');
$('head').append('<link rel="stylesheet" type="text/css" href="client/css/stylesv2.css" media="screen" />');



/* Objects for cleanness. */

var roomRef = new Object;
var roomIdRef = new Object;
var roomList = new Array;
var userRef = new Object;
var userList = new Array;
var groupRef = new Object;
var groupList = new Array;
var messageIndex = new Array;


var roomUlFavHtml = '';
var roomUlMyHtml = '';
var roomUlPrivHtml = '';
var roomUlHtml = '';
var ulText = '';
var roomTableHtml = '';
var roomSelectHtml = '';

var userSelectHtml = '';



/* Get the absolute API path.
 * TODO: Define this is more "sophisticated manner". */

var apiPathPre = window.location.pathname;
apiPathPre = apiPathPre.split('/');
apiPathPre.pop();
apiPathPre.pop();
apiPathPre = apiPathPre.join('/');

var apiPath = apiPathPre + '/';

/*********************************************************
 ************************* END ***************************
 ******************* Variable Setting ********************
 *********************************************************/








/*********************************************************
 ************************ START **************************
 ******************** Data Population ********************
 *********************************************************/

function populate() {
  $.ajax({
    url: apiPath + 'api/getUsers.php?sessionHash=' + sessionHash,
    type: 'GET',
    timeout: 5000,
    cache: false,
    success: function(xml) {
      console.log('Users obtained.');

      $(xml).find('user').each(function() {
        var userName = unxml($(this).find('userName').text().trim());
        var userId = parseInt($(this).find('userId').text().trim());

        userSelectHtml += '<option value="' + userId + '">' + userName + '</option>';

        userRef[userName] = userId;
        userList.push(userName);
      });
    },
    error: function() {
      console.log('Users Not Obtained - Problems May Occur');
    },
  });


  $.ajax({
    url: apiPath + 'api/getRooms.php?permLevel=view&sessionHash=' + sessionHash,
    timeout: 5000,
    type: 'GET',
    async: true,
    cache: false,
    success: function(xml) {
      roomList = new Array; // Clear so we don't get repeat values on regeneration.
      roomIdRef = new Object;
      roomRef = new Object;
      roomTableHtml = '';
      roomSelectHtml = '';

      $(xml).find('room').each(function() {
        var roomName = unxml($(this).find('roomName').text().trim());
        var roomId = parseInt($(this).find('roomId').text().trim());
        var roomTopic = unxml($(this).find('roomTopic').text().trim());
        var isFav = ($(this).find('favorite').text() == 'true' ? true : false);
        var isPriv = ($(this).find('optionDefinitions > privateIm').text() == 'true' ? true : false);
        var isAdmin = ($(this).find('canAdmin').text().trim() === 'true' ? true : false);
        var isModerator = ($(this).find('canModerate').text().trim() === 'true' ? true : false);
        var isOwner = (parseInt($(this).find('owner').text()) == userId ? true : false);

        var ulText = '<li><a href="index.php?room=' + roomId + '">' + roomName + '</a></li>';

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

        roomTableHtml += '<tr id="room' + roomId + '"><td><a href="#" onclick="standard.changeRoom(' + roomId + ');">' + roomName + '</a></td><td>' + roomTopic + '</td><td>' + (isAdmin ? '<button onclick="popup.editRoom(' + roomId + ');"></button>' : '') + '</td></tr>';

        roomSelectHtml += '<option value="' + roomId + '">' + roomName + '</option>';

        roomRef[roomName] = roomId;
        roomIdRef[roomId] = roomName;
        roomList.push(roomName);
      });

      console.log('Rooms obtained.');
    },
    error: function() {
      console.log('Rooms Not Obtained - Problems May Occur');
    }
  });


  $.ajax({
    url: apiPath + 'api/getGroups.php?sessionHash=' + sessionHash,
    timeout: 5000,
    type: 'GET',
    async: true,
    cache: false,
    success: function(xml) {
      console.log('Groups obtained.');

      $(xml).find('group').each(function() {
        var groupName = unxml($(this).find('groupName').text());
        var groupId = parseInt($(this).find('groupId').text());

        groupRef[groupName] = groupId;
        groupList.push(groupName);
      });
    },
    error: function() {
      console.log('Groups Not Obtained - Problems May Occur');
    }
  });
}

populate();

/*********************************************************
 ************************* END ***************************
 ******************** Data Population ********************
 *********************************************************/








/*********************************************************
 ************************ START **************************
 ******************* Content Functions *******************
 *********************************************************/

function youtubeSend(id) {
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
      html += '<td><img src="http://i2.ytimg.com/vi/' + video.videoId + '/default.jpg" style="width: 80px; height: 60px;" /><br /><small><a href="javascript: void(0);" onclick="youtubeSend(&apos;' + video.videoId + '&apos;)">' + video.title + '</a></small></td>';

      if (num % 3 === 0) {
        html += '</tr>';
      }
    }

    if (num % 3 !== 0) {
      html += '</tr>';
    }

    $('#youtubeResults').html(html);
  });
}


var standard = {
  archive : function (options,searchText) {
    var encrypt = 'base64';
    var lastMessage = 0;
    var firstMessage = 0;
    var data = '';

    if (options.idMax) {
      var where = 'messageIdStart=' + options.idMax;
    }
    else if (options.idMin) {
      var where = 'messageIdEnd=' + options.idMin;
    }
    else {
      var where = 'messageIdStart=0';
    }

    $.when( $.ajax({
      url: apiPath + 'api/getMessages.php?rooms=' + options.roomId + '&archive=1&messageLimit=20&' + where + '&sessionHash=' + sessionHash,
      type: 'GET',
      timeout: 1000,
      async: true,
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

          if (typeof contextMenuParse === 'function') {
            contextMenuParse();
          }
        }
      },
      error: function() {
        dia.error('Archive failed to obtain results from server.');
      },
    })).then(function() {
      options.callback(data);
    });
  },


  // TODO
  autoEntry : {
    addEntry : function(type,source) {
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

      if (!id) {
        dia.error(type2 + ' does not exist.');
      }
      else {
        var currentRooms = $("#" + type).val().split(",");
        currentRooms.push(id);

        $("#" + type + "List").append("<span id=\"" + type + "SubList" + id + "\">" + val + " (<a href=\"javascript:void(0);\" onclick=\"removeEntry('" + type + "'," + id + ");\">×</a>), </span>");
        $("#" + type).val(currentRooms.toString(","));
      }
    },

    removeEntry : function(type,id) {
      $("#" + type + "SubList" + id).fadeOut(500, function() {
        $(this).remove();
      });

      var currentRooms = $("#" + type).val().split(",");s

      for (var i = 0; i < currentRooms.length; i++) {
        if(currentRooms[i] == id) {
          currentRooms.splice(i, 1);
          break;
        }
      }

      $("#" + type).val(currentRooms.toString(","));
    }
  },



  login : function(options) {
    console.log('Login Initiated');
    var data = '';
    sessionHash = '';
    $.cookie('fim3_sessionHash','');

    console.log('Encrypted Password: ' + options.password);

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

    $.ajax({
      url: apiPath + 'validate.php',
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

        if (valid === 'true') {
          populate(); // Update users/groups/rooms/etc. based on new credentials.

          userId = parseInt($(xml).find('userData > userId').text().trim());
          anonId = parseInt($(xml).find('anonId').text().trim());
          sessionHash = unxml($(xml).find('sessionHash').text().trim());


          $('#loginDialogue').dialog('close'); // Close any open login forms.


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


          if (!options.silent) {
            /* Display Dialog to Notify User of Being Logged In */
            if (!userPermissions.general) {
              dia.info('You are now logged in as ' + userName + '. However, you are not allowed to post and have been banned by an administrator.','Logged In');
            }
            else {
              dia.info('You are now logged in as ' + userName + '.','Logged In');
            }
          }

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
            popup.selectRoom();
          }
          else {
            standard.changeRoom(defaultRoomId);
          }
        }

        windowDynaLinks();
      },
      error: function() {
        dia.error("The login request could not be sent. Please try again.");
      }
    });

    console.log('Login Finished');
  },


  logout : function() {
    $.cookie('fim3_sessionHash','');

    userId = 0;
    sessionHash = '';

    windowDynaLinks();
  },


  getMessages : function() {
    if (roomId) {
      if (!longPolling) {
        window.clearInterval(window.timer1);
      }

      var encrypt = 'base64';

      $.ajax({
        url: apiPath + 'api/getMessages.php?rooms=' + roomId + '&messageLimit=100&watchRooms=1&activeUsers=1' + (first ? '&archive=1&messageDateMin=' + (Math.round((new Date()).getTime() / 1000) - 1200) : '&messageIdMin=' + (lastMessage)) + '&longPolling=' + (longPolling ? 'true' : 'false') + '&sessionHash=' + sessionHash,
        type: 'GET',
        timeout: timeout,
        async: true,
        data: '',
        contentType: "text/xml; charset=utf-8",
        dataType: "xml",
        cache: false,
        success: function(xml) {
          if (xml) {
            totalFails = 0;
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
          }

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

              if (messageId > lastMessage) {
                lastMessage = messageId;
              }

              messageIndex.push(lastMessage);

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

            if (blur) {
              if (settings.audioDing) {
                riffwave.play();

                if (navigator.appName === 'Microsoft Internet Explorer') {
                  timer3 = window.setInterval(faviconFlash,1000);

                  window.clearInterval(timer3);
                }
              }

              if (notify) {
                if (window.webkitNotifications) {
                  webkitNotify('images/favicon.gif', 'New Message', notifyData);
                }
              }

              if (navigator.appName === 'Microsoft Internet Explorer') {
                try {
                  if (window.external.msIsSiteMode()) {
                    window.external.msSiteModeActivate();
                  }
                }
                catch(ex) {
                  // Supress Error
                }
              }
            }
          }

          if (typeof contextMenuParse === 'function') {
            contextMenuParse();
          }

          if (longPolling) {
            setTimeout(standard.getMessages,50);
          }

          first = false;
        },
        error: function(err) {
          console.log('Requesting messages for ' + roomId + '; failed: ' + err + '.');

          if (longPolling) {
            setTimeout(standard.getMessages,50);
          }
          else {
            totalFails += 1;
            $('#roomName').append('<span class="ui-icon ui-icon-info"></span>');
          }
        },
      });

      if (!longPolling) {
        if (totalFails > 10) {
          window.timer1 = window.setInterval(window.standard.getMessages,30000);
          timeout = 29900;
        }
        else if (totalFails > 5) {
          window.timer1 = window.setInterval(window.standard.getMessages,10000);
          timeout = 9900;
        }
        else if (totalFails > 0) {
          window.timer1 = window.setInterval(window.standard.getMessages,5000);
          timeout = 4900;
        }
        else {
          window.timer1 = window.setInterval(window.standard.getMessages,2500);
          timeout = 2400;
        }
      }
    }
    else {
      console.log('Not requesting messages; room undefined.');
    }
  },

  sendMessage: function(message,confirmed) {
    if (!roomId) {
      popup.selectRoom();
    }
    else {
      confirmed = (confirmed === 1 ? 1 : '');

      $.ajax({
        url: apiPath + 'api/sendMessage.php?sessionHash=' + sessionHash,
        type: 'POST',
        data: 'roomId=' + roomId + '&confirmed=' + confirmed + '&message=' + urlEncode(message),
        cache: false,
        timeout: 2500,
        success: function(xml) {
          console.log('Message sent.');

          var status = $(xml).find('errorcode').text().trim();
          var emessage = $(xml).find('errortext').text().trim();
          switch (status) {
            case '':
            break;

            case 'badroom':
            dia.error("A valid room was not provided.");
            break;

            case 'badmessage':
            dia.error("A valid message was not provided.");
            break;

            case 'spacemessage':
            dia.error("Too... many... spaces!");
            break;

            case 'noperm':
            dia.error("You do not have permission to post in this room.");
            break;

            case 'blockcensor':
            dia.error(emessage);
            break;

            case 'confirmcensor':
            dia.error(emessage + '<br /><br /><button type="button" onclick="$(this).parent().dialog(&apos;close&apos;);">No</button><button type="button" onclick="standard.standard.sendMessage(&apos;' + escape(message) + '&apos;,1); $(this).parent().dialog(&apos;close&apos;);">Yes</button>');
            break;
          }
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
        }
      });
    }
  },

  changeRoom : function(roomLocalId) {
    console.log('Changing Room: ' + roomLocalId + '; Detected Name: ' + roomIdRef[roomLocalId]);

    roomId = roomLocalId;
    $('#roomName').html(roomIdRef[roomId]);
    $('#messageList').html('');

    windowDraw();

    /*** Get Messages ***/

    $(document).ready(function() {
      if (longPolling) {
        $(document).ready(function() {
          standard.getMessages();
        });
      }
      else {
        window.timer1 = window.setInterval(standard.getMessages,2500);
      }
    });
  },
};

/*********************************************************
 ************************* END ***************************
 ******************* Content Functions *******************
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
            });

            return false; // Don't submit the form.
          });
        }
      });

      console.log('Popup for un-loggedin user triggered.');
    });
  },

  /*** END Login ***/




  /*** START Help ***/

  selectRoom : function() {
    dia.full({
      content : '<table><thead><tr><th>Name</th><th>Topic</th><th>Actions</th></tr></thead><tbody>' + roomTableHtml + '</tbody></table>',
      title : 'Room List',
      id : 'roomListDialogue',
      width: 1000,
    });
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
        if (typeof FileReader == 'undefined') {
          dia.error('Your browser does not support file uploads.');
          $('#fileUpload').attr('disabled','disabled');
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

              $('#imageUploadSubmitButton').removeAttr('disabled');
            }
          });


          $('#uploadFileForm').submit(function() {
            $.ajax({
              url: apiPath + 'api/uploadFile.php',
              type: 'POST',
              data : 'dataEncode=base64&uploadMethod=raw&autoInsert=true&roomId=' + roomId + '&file_data=' + urlEncode(fileContent) + '&sessionHash=' + sessionHash,
              cache : false,
            });

            return false;
          });
        }
      },
      selectTab : selectTab,
    });
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
      url: apiPath + 'api/getStats.php?rooms=' + roomId + '&maxResults=' + number + '&sessionHash=' + sessionHash,
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
          content : '<table><thead><tr><th>Position</th>' + roomHtml + '</tr></thead><tbody>' + statsHtml2 + '</tbody></table>',
          title : 'Room Stats',
          id : 'roomStatsDialogue',
          width : 600,
        });
      },
      error: function() {
        dia.error('Failed to show all rooms');
      }
    });
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
      },
      oF : function() {
        $('#settingsOfficialAjax_theme').change(function() {
          $('#stylesjQ').attr('href','client/css/' + themes[this.value] + '/jquery-ui-1.8.13.custom.css');
          $('#stylesFIM').attr('href','client/css/' + themes[this.value] + '/fim.css');

          $.cookie('fim3_themeId',this.value);
        });

        $("#defaultRoom").autocomplete({
          source: roomList
        });
        $("#watchRoomsBridge").autocomplete({
          source: roomList
        });

        $.ajax({
          url: apiPath + 'api/getFonts.php?sessionHash=' + sessionHash,
          timeout: 5000,
          type: 'GET',
          async: true,
          cache: false,
          success: function(xml) {
            $(xml).find('font').each(function() {
              var fontName = unxml($(this).find('fontName').text().trim());
              var fontId = parseInt($(this).find('fontId').text().trim());
              var fontGroup = unxml($(this).find('fontGroup').text().trim());
              var fontData = unxml($(this).find('fontData').text().trim());

              $('#defaultFace').append('<option value="' + fontId + '" style="' + fontData + '" data-font="' + fontData + '">' + fontName + '</option>');
            });
          },
          error: function() {
            dia.error('The list of fonts could not be obtained from the server.');
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
          $.post(apiPath + 'api/moderate.php?action=userOptions&userId=' + userId + data + '&sessionHash=' + sessionHash,function(xml) {
            // TODO
          }); // Send the form data via AJAX.

          $("#changeSettingsDialogue").empty().remove(); // Housecleaning, needed if we want the colorpicker to work in another changesettings dialogue.
          $(".colorpicker").empty().remove(); // Housecleaning, needed if we want the colorpicker to work in another changesettings dialogue.

          window.reload();

          return false; // Don't submit the form.
        });
      },
    });
  },

  /*** END User Settings ***/




  /*** START Help ***/

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
          url: apiPath + 'api/getRooms.php?rooms=' + roomIdLocal + '&sessionHash=' + sessionHash,
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

            $('#name').val(roomName);
            $('#allowedUsers').val(allowedUsers);
            $('#allowedGroups').val(allowedGroups);
            $('#moderators').val(moderators);

          },
          error: function() {
            dia.error('Failed to obtain current room settings from server.');
          },
        });
      },
    });

    $("#editRoomForm").submit(function() {
      var data = $("#editRoomForm").serialize(); // Serialize the form data for AJAX.

      $.post(apiPath + 'api/moderate.php',data + '&action=editRoom&sessionHash=' + sessionHash,function(xml) {
        var errorCode = unxml($(xml).find('errorcode').text().trim());
        var errorMessage = unxml($(xml).find('errortext').text().trim());
        var newRoomId = parseInt($(xml).find('insertId').text().trim());

        if (errorCode) {
          dia.error('An error has occured: ' + errorMessage);
        }
        else { // TODO
          dia.full({
            uri : 'template.php?template=editRoomSuccess&insertId=' + newRoomId,
            title : 'Room Edited!',
            id : 'editRoomResultDialogue',
            width : 600,
          });
          $("#editRoomDialogue").dialog('close');
        }
      }); // Send the form data via AJAX.
      return false; // Don't submit the form.
    });
  },

  /*** END Edit Room ***/




  /*** START Help ***/

  help : function() {
    dia.full({
      uri : 'template.php?template=help',
      title : 'helpDialogue',
      width : 1000,
      tabs : true,
    });
  },

  /*** END Help ***/




  /*** START Archive ***/

  archive : function(roomLocalId) {
    dia.full({
      content : '<form id="archiveSearch" action="#" method="get"><input type="text" name="searchText" /></form> <table><thead><tr><th>User</th><th>Time</th><th>Message</th></tr></thead><tbody id="archiveMessageList"></tbody></table><br /><br /><button id="archivePrev"><< Prev</button><button id="archiveNext">Next >></button>',
      title : 'Archive',
      id : 'archiveDialogue',
      width : 1000,
      autoOpen : false,
    });

    var lastMessage = standard.archive({
      roomId: roomLocalId,
      callback: function(data) {
        $('#archiveMessageList').html(data);

        $('#archiveNext').click(function() {
          standard.archive(lastMessage,false);
        });

        $('#archivePrev').click(function() {
          standard.archive(false,firstMessage);
        });

        $('#archiveDialogue').dialog('open');
      }
    });
  },

  /*** END Archive ***/




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
          var data = $("#editRoomForm").serialize(); // Serialize the form data for AJAX.

          $.post(apiPath + 'api/moderate.php',data + '&action=createRoom&sessionHash=' + sessionHash,function(xml) {
            var errorCode = unxml($(xml).find('errorcode').text().trim());
            var errorMessage = unxml($(xml).find('errortext').text().trim());
            var newRoomId = parseInt($(xml).find('insertId').text().trim());

            if (errorCode) {
              dia.error('An error has occured: ' + errorMessage);
            }
            else {
              dia.full({
                uri : 'template.php?template=createRoomSuccess&insertId=' + newRoomId,
                title : 'Room Created!',
                id : 'createRoomResultDialogue',
                width : 600
              });
              $("#editRoomDialogue").dialog('close');
            }
          }); // Send the form data via AJAX.
          return false; // Don't submit the form.
        });
      },
    });
  },

  /*** END Create Room ***/




  /*** START Online ***/

  online : function() {
    dia.full({
      content : '<table class="page"><thead><tr class="hrow"><th>User</th><th>Rooms</th></tr></thead><tbody id="onlineUsers"><tr><td colspan="2">Loading...</td></tr></tbody></table>',
      title : 'Active Users',
      id : 'onlineDialogue',
      width : 600,
      cF : function() {
        clearInterval(timer2);
      },
    });

    function updateOnline() {
      $.ajax({
        url: apiPath + 'api/getAllActiveUsers.php?sessionHash=' + sessionHash,
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
              roomData.push('<a href="/index.php?room=' + roomId + '">' + roomName + '</a>');
            });
            roomData = roomData.join(', ');

            data += '<tr><td>' + startTag + '<span class="userName">' + userName + '</span>' + endTag + '</td><td>' + roomData + '</td></tr>';
          });

          $('#onlineUsers').html(data);
        },
        error: function() {
          $('#onlineUsers').html('Refresh Failed');
        },
      });
    }

    var timer2 = setInterval(updateOnline,2500);
  },

  /*** END Online ***/




  /*** START Kick Manager ***/

  manageKicks : function() {
    var kickHtml = '';

    $.ajax({
      url: apiPath + 'api/getKicks.php?rooms=' + roomId + '&sessionHash=' + sessionHash,
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(xml) {
        $(xml).find('kick').each(function() {
          var kickerId = parseInt($(this).find('kickerData > userId').text().trim());
          var kickerName = $(this).find('kickerData > userName').text().trim();
          var userId = parseInt($(this).find('userData > userId').text().trim());
          var userName = $(this).find('userData > userName').text().trim();
          var length = parseInt($(this).find('length').text().trim());
          var set = parseInt($(this).find('set').text().trim());
          var expires = parseInt($(this).find('expires').text().trim());

          kickHtml += '<tr><td>' + userName +  '</td><td>' + kickerName + '</td><td>' + set + '</td><td>' + expires + '</td><td><button onclick="unkick(' + userId + ',' + roomId + ')>Unkick</button></td></tr>';
        });

        dia.full({
          content : '<table class="page"><thead><tr class="hrow"><th>User</th><th>Kicked By</th><th>Kicked On</th><th>Expires On</th><th>Actions</th></tr>  </thead><tbody id="kickedUsers"></tbody></table>',
          title : 'Manage Kicked Users in This Room',
          width : 1000,
        });
      },
      error: function() {
        dia.error('The list of currently kicked users could not be obtained from the serveer.');
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
  },

  /*** END Kick Manager ***/




  /*** START Private Rooms ***/

  privateRoom : function() {
    dia.full({
      content : '<form action="index.php?action=privateRoom&phase=2" method="post" id="privateRoomForm"><label for="userName">Username</label>: <input type="text" name="userName" id="userName" /><br /><small><span style="margin-left: 10px;">The other user you would like to talk to.</span></small><br /><br />  <input type="submit" value="Go" /></form>',
      title : 'Enter Private Room',
      id : 'privateRoomDialogue',
      width : 1000,
      oF : function() {
        $('#userName').autocomplete({
          source: userList
        });

        $("#privateRoomForm").submit(function() {
          privateUserName = $("#privateRoomForm > #userName").val(); // Serialize the form data for AJAX.
          privateUserId = userRef[data];

//TODO
//          $.post(apiPath + 'api/createRoom.php?sessionHash=' + sessionHash,data,function(html) {
//            quickDialogue(html,'','privateRoomResultDialogue');
//          }); // Send the form data via AJAX.

          $("#privateRoomDialogue").dialog('close');

          return false; // Don't submit the form.
        });
      }
    });
  },

  kick : function() {
    dia.full({
      content : '<form action="#" id="kickUserForm" method="post">  <label for="userId">User</label>: <select name="userId">$userSelect</select><br />  <label for="roomId">Room</label>: <select name="roomId">$roomSelect</select><br />  <label for="time">Time</label>: <input type="text" name="time" id="time" style="width: 50px;" />  <select name="interval">    <option value="1">Seconds</option>    <option value="60">Minutes</option>    <option value="3600">Hours</option>    <option value="86400">Days</option>    <option value="604800">Weeks</option>  </select><br /><br />  <button type="submit">Kick User</button><button type="reset">Reset</button></form>',
      title : 'Kick User',
      id : 'kickUserDialogue',
      width : 1000,
    });

    $('select[name=roomId]').html(roomSelectHtml);

    $.ajax({
      url: apiPath + 'api/getUsers.php?sessionHash=' + sessionHash,
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(xml) {
        $('select[name=userId]').html(userSelectHtml);
      },
      error: function() {
        dia.error('The list of users could not be obtained from the server.');
      }
    });

    $("#kickUserForm").submit(function() {
      data = $("#kickUserForm").serialize(); // Serialize the form data for AJAX.
      $.post(apiPath + 'api/moderate.php',data + '&action=kickUser&sessionHash=' + sessionHash,function(xml) {
        var status = $(xml).find('errorcode').text().trim();
        var emessage = $(xml).find('errormessage').text().trim();

        switch (status) {
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
      }); // Send the form data via AJAX.

      return false; // Don't submit the form.
    });
  },

  /*** END Private Rooms ***/
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
    },
    function () {
      $("#icon_settings.reversePostOrder").button("option", "icons", { primary: 'ui-icon-circle-triangle-' + (settings.reversePostOrder ? 'n' : 's') } );
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
    },
    function () {
      if (settings.audioDing) {
        $("#icon_muteSound").button("option", "icons", { primary: 'ui-icon-volume-on' } );
      }
      else {
        $("#icon_muteSound").button("option", "icons", { primary: 'ui-icon-volume-off' } );
      }
    }
  );

  windowResize();
}

function windowDynaLinks() {
  /* Show and Hide Links Based on Permissions */
  if (!userPermissions.createRoom) {
    $('li > #createRoom').parent().hide();
  }
  if (!userPermissions.privateRoom) {
    $('li > #privateRoom').parent().hide();
  }

  if (!adminPermissions) {
    //
  }
  if (!adminPermissions.modUsers) {
    $('li > #modUsers').parent().hide();
  }
  if (!adminPermissions.modImages) {
    $('li > #modImages').parent().hide();
  }
  if (!adminPermissions.modCensor) {
    $('li > #modCensor').parent().hide();
  }
  if (!adminPermissions.modTemplates) {
    $('li > #modPhrases').parent().hide();
  }
  if (!adminPermissions.modTemplates) {
    $('li > #modTemplates').parent().hide();
  }
  if (!adminPermissions.modPrivs) {
    $('li > #modPrivs').parent().hide();
  }
  if (!adminPermissions.modHooks) {
    $('li > #modHooks').parent().hide();
  }
  if (!adminPermissions.modCore) {
    $('li > #modCore').parent().hide();
  }

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
      url: apiPath + 'api/getUsers.php?users=' + userId + '&sessionHash=' + sessionHash,
      type: 'GET',
      timeout: 2400,
      cache: false,
      success: function(xml) {
        userName = unxml($(this).find('userName').text().trim());
        avatarUrl = parseInt($(this).find('userId').text().trim());
        profileUrl = parseInt($(this).find('userId').text().trim());
      },
      error: function() {
        dia.error('The information of this user could not be retrieved.');
      },
    });

    switch(action) {
      case 'private_im':
      dia.full({
        uri : 'content/privateRoom.php?phase=2&userId=' + userId,
        title : 'Private IM',
        id : 'privateRoomDialogue',
        width : 1000,
      });
      break;

      case 'profile':
      window.open(profileUrl + 'member.php?u=' + userId,'profile' + userId);
      break;

      case 'kick':
      dia.full({
        uri : 'content/kick.php?userId=' + userId + '&roomId=' + $('body').attr('data-roomId'),
        title : 'Kick User',
        id : 'kickUserDialogue',
        width : 1000
      });
      break;

      case 'ban': // TODO?
      window.open('moderate.php?do=banuser2&userId=' + userId,'banuser' + userId);
      break;
    }
  });

  $('.messageLine .messageText').contextMenu({
    menu: 'messageMenu'
  },
  function(action, el) {
    postid = $(el).attr('data-messageid');

    switch(action) {
      case 'delete':
      if (confirm('Are you sure you want to delete this message?')) {
        $.ajax({
          url: 'ajax/fim-modAction.php?action=deletepost&postid=' + postid,
          type: 'GET',
          cache: false,
          success: function() {
            $(el).parent().fadeOut();
          },
          error: function() {
            dia.error('The message could not be deleted.');
          }
        });
      }
      break;

      case 'link':
        // TODO
      dia.info('This message can be bookmarked using the following archive link:<br /><br /><input type="text" value="' + window.location.hostname + '/archive.php?roomId=' + $('body').attr('data-roomId') + '&message=' + postid + '" />','Link to This Message');
      break;
    }
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
      if (dia.confirm('Are you sure you want to delete this message?')) {
        $.ajax({
          url: 'ajax/fim-modAction.php?action=deletepost&postid=' + postid,
          type: 'GET',
          cache: false,
          success: function() {
            $(el).parent().fadeOut();
          },
          error: function() {
            dia.error('The message could not be deleted.');
          }
        });
      }
      break;

      case 'link': // TODO
      dia.info('This message can be bookmarked using the following archive link:<br /><br /><input type="text" value="http://' + window.location.hostname + '/archive.php?roomId=' + $('body').attr('data-roomId') + '&message=' + postid + '" />','Link to This Message');
      break;
    }
  });

  $('.room').contextMenu({
    menu: 'roomMenu'
  },
  function(action, el) {
    switch(action) {
      case 'delete':
      if (confirm('Are you sure you want to delete this room?')) {
        $.ajax({
          url: 'ajax/fim-modAction.php?action=deleteroom&roomId=' + postid,
          type: 'GET',
          cache: false,
          success: function() {
            $(el).parent().fadeOut();
          },
          error: function() {
            dia.error('The room could not be deleted.');
          }
        });
      }
      break;
      case 'edit':
      dia.full({
        uri : 'content/editRoom.php?roomId=' + $(el).attr('data-roomId'),
        title : 'Edit Room',
        id : 'editRoomDialogue',
        width : 1000
      });
      break;
    }
  });

  $('.userName').ezpz_tooltip({
    contentId: 'tooltext',
    beforeShow: function(content,el) {
      var thisid = $(el).attr('data-userId');

      if (thisid != $('#tooltext').attr('data-lastuserId')) {
        $('#tooltext').attr('data-lastuserId',thisid);
        $.get(apiPath + 'api/getUsers.php?users=' + thisid + '&sessionHash=' + sessionHash, function(xml) {
          var userName = unxml($(xml).find('user > userName').text().trim());
          var userId = parseInt($(xml).find('user > userId').text().trim());
          var startTag = unxml($(xml).find('user > startTag').text().trim());
          var endTag = unxml($(xml).find('user > endTag').text().trim());
          var userTitle = unxml($(xml).find('user > userTitle').text().trim());
          var posts = parseInt($(xml).find('user > postCount').text().trim());
          var joinDate = unxml($(xml).find('user > joinDateFormatted').text().trim());
          var avatar = unxml($(xml).find('user > avatar').text().trim());

          content.html('<div style="width: 400px;">' + (avatar.length > 0 ? '<img alt="" src="' + avatar + '" style="float: left;" />' : '') + '<span class="userName" data-userId="' + userId + '">' + startTag + userName + endTag + '</span>' + (userTitle.length > 0 ? '<br />' + userTitle : '') + '<br /><em>Posts</em>: ' + posts + '<br /><em>Member Since</em>: ' + joinDate + '</div>');
        });
      }
    }
  });

  if (settings.showAvatars) {
    $('.messageText').tipTip({
      attribute: 'data-time'
    });
  }
}



$(document).ready(function() {
  console.log('Automatic Login Triggered');

  if (sessionHash) {
    standard.login({
      sessionHash : sessionHash, // Use existing sessionhash (we can track the user by this if they aren't logged in).
    });
  }
  else {
    standard.login({
      silent : true,
    }); // Get a sessionhash for guest navigation.
  }



  windowDraw();




  /*** WIP ***/

  $('#roomListLong > ul').append('<li>Favourites<ul>' + roomUlFavHtml + '</ul></li><li>My Rooms<ul>' + roomUlMyHtml + '</ul></li><li>General<ul>' + roomUlHtml + '</ul></li><li>Private<ul>' + roomUlPrivHtml + '</ul></li>');

  $('#roomListShort > ul').append('<li>Favourites<ul>' + roomUlFavHtml + '</ul></li>');

  $('#showMoreRooms').click(function() {
    $('#roomListShort').slideUp();
    $('#roomListLong').slideDown();
  });

  $('#showFewerRooms').click(function() {
    $('#roomListLong').slideUp();
    $('#roomListShort').slideDown();
  });




  /*** Context Menus ***/

  $.get('template.php','template=contextMenu',function(data) {
    $('body').append(data);

    console.log('Appended Context Menus to DOM');
  });



  /*** ??? ***/

  $('#icon_settings.reversePostOrder').click(function() { // TODO
  });


  $('#icon_url').click(function() {
    popup.insertDoc('url');
  });

  $('#icon_image').click(function() {
    popup.insertDoc('image');
  });

  $('#icon_video').click(function() {
    popup.insertDoc('video');
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
  });



  /*** Private Room ***/

  $('a#privateRoom').click(function() {
    popup.privateRoom();
  });



  /*** Manage Kick ***/

  $('a#manageKick').click(function() {
    popup.manageKicks();
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
  });



  /* Create Room */

  $('a#createRoom').click(function() {
    popup.createRoom();
  });



  /*** Edit Room ***/

  $('a#editRoom').click(function() {
    popup.editRoom(roomId);
  });

  $('a.editRoomMulti').click(function() {
    popup.editRoom($(this).attr('data-roomId'));
  });



  /*** Help ***/

  $('#icon_help').click(function() {
    popup.help();
  });



  /*** Archive ***/

  $('#icon_note, #messageArchive').click(function() {
    popup.archive(roomId);
  });



  /*** Room List ***/

  $('#roomList').click(function() {
    popup.selectRoom();
  });



  /*** Stats ***/

  $('#viewStats').click(function() {
    popup.viewStats();
  });



  /*** Copyright & Credits ***/

  $('#copyrightLink').click(function() {
    dia.full({
      uri : 'template.php?template=copyright',
      title : 'copyrightDialogue',
      width : 600,
      tabs : true,
    });
  });



  /*** User Settings ***/

  $('#icon_settings, #changeSettings, a.changeSettingsMulti').click(function() {
    popup.userSettings();
  });



  /*** Trigger Login ***/

  if (!userId) { // The user is not actively logged in.
    popup.login();
  }
  $('#login').click(function() {
    popup.login();
  });



  /*** Trigger Logout */
  $('#logout').click(function() {
    popup.login();
  });

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
  var windowWidth = (window.innerWidth ? window.innerWidth : document.documentElement.clientWidth); // Get the browser window "viewport" width.
  var windowHeight = (window.innerHeight ? window.innerHeight : document.documentElement.clientHeight); // Get the browser window "viewport" height.


  switch (window.layout) { // Determine which layout we are using.
    default: // The main layout.
    $('#messageList').css('height',(windowHeight - 240)); // Set the message list height to fill as much of the screen that remains after the textarea is placed.
    $('#messageList').css('max-width',((windowWidth - 10) * .75)); // Prevent box-stretching. This is common on... many chats.


    /* Body Padding: 10px
     * Right Area Width: 75%
     * "Enter Message" Table Padding: 10px
     *** TD Padding: 2px (on Standard Styling)
     * Message Input Container Padding : 3px (all padding-left)
     * Message Input Text Area Padding: 6px */
    $('#messageInput').css('width',(((windowWidth - 10) * .75) - 10 - 2 - 3 - 6)); // Set the messageInput box to fill width.


    $('body').css('height',window.innerHeight); // Set the body height to equal that of the window; this fixes many gradient issues in theming.
    break;

    // TODO
  }
}

function windowBlur () {
  blur = true;
}

function windowFocus() {
  blur = false;
  window.clearInterval(timer3);
  $('#favicon').attr('href',favicon);
}


window.onresize = windowResize;
window.onblur = windowBlur;
window.onfocus = windowFocus;

/*********************************************************
 ************************* END ***************************
 ***** Window Manipulation and Multi-Window Handling *****
 *********************************************************/