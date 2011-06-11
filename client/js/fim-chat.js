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

/***** Variable Setting *****/
var blur = false;
var totalFails = 0;
var totalFails2 = 0;
var timer3;
var topic;
var lastMessage = 0;
var messages;
var activeUsers;
var notify = true;
var timeout = (window.longPolling ? 1000000 : 2400);
var first = true;
var favicon = $('#favicon').attr('href');
var messageIndex = new Array;


/***** Misc Functions *****/
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




/***** Context Menu *****/

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
      url: 'api/getUserInfo.php?userId' = userId,
      type: 'GET',
      timeout: 2400,
      cache: false,
      success: function(xml) {
        userName = unxml($(this).find('userName').text().trim());
        avatarUrl = parseInt($(this).find('userId').text().trim());
        profileUrl = parseInt($(this).find('userId').text().trim());
      },
      error: function() {
        alert('User Not Obtained - Problems May Occur');
      },
    });

    switch(action) {
      case 'private_im':
      ajaxDialogue('content/privateRoom.php?phase=2&userId=' + userId,'Private IM','privateRoomDialogue',1000);
      break;
      case 'profile':
      window.open(profileUrl + 'member.php?u=' + userId,'profile' + userId);
      break;
      case 'kick':
      ajaxDialogue('content/kick.php?userId=' + userId + '&roomId=' + $('body').attr('data-roomId'),'Kick User','kickUserDialogue',1000);
      break;
      case 'ban':
      window.open('moderate.php&do=banuser2&userId=' + userId,'banuser' + userId);
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
            quickDialogue('The message could not be deleted.','Error','message');
          }
        });
      }
      break;

      case 'link':
      quickDialogue('This message can be bookmarked using the following archive link:<br /><br /><input type="text" value="' + window.location.hostname + '/archive.php?roomId=' + $('body').attr('data-roomId') + '&message=' + postid + '" />','Link to This Message','linkMessage');
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

      quickDialogue('<img src="' + src + '" style="max-width: 550px; max-height: 550px;" /><br /><br /><input type="text" value="' + src +  '" style="width: 550px;" />','Copy Image URL','getUrl');
      break;
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
            quickDialogue('The message could not be deleted.','Error','message');
          }
        });
      }
      break;

      case 'link':
      quickDialogue('This message can be bookmarked using the following archive link:<br /><br /><input type="text" value="http://' + window.location.hostname + '/archive.php?roomId=' + $('body').attr('data-roomId') + '&message=' + postid + '" />','Link to This Message','linkMessage');
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
            quickDialogue('The room could not be deleted.','Error','message');
          }
        });
      }
      break;
      case 'edit':
      ajaxDialogue('content/editRoom.php?roomId=' + $(el).attr('data-roomId'),'Edit Room','editRoomDialogue',1000);
      break;
    }
  });

  $('.userName').ezpz_tooltip({
    contentId: 'tooltext',
    beforeShow: function(content,el) {
      var thisid = $(el).attr('data-userId');

      if (thisid != $('#tooltext').attr('data-lastuserId')) {
        $('#tooltext').attr('data-lastuserId',thisid);
        $.get("api/getUserInfo.php?userId=" + thisid, function(xml) {
          var userName = unxml($(xml).find('userData > userName').text().trim());
          var userId = parseInt($(xml).find('userData > userId').text().trim());
          var startTag = unxml($(xml).find('userData > startTag').text().trim());
          var endTag = unxml($(xml).find('userData > endTag').text().trim());
          var userTitle = unxml($(xml).find('userData > userTitle').text().trim());
          var posts = parseInt($(xml).find('userData > postCount').text().trim());
          var joinDate = unxml($(xml).find('userData > joinDateFormatted').text().trim());
          var avatar = unxml($(xml).find('userData > avatar').text().trim());

          content.html('<div style="width: 400px;">' + (avatar.length > 0 ? '<img alt="" src="' + avatar + '" style="float: left;" />' : '') + '<span class="userName" data-userId="' + userId + '">' + startTag + userName + endTag + '</span>' + (userTitle.length > 0 ? '<br />' + userTitle : '') + '<br /><em>Posts</em>: ' + posts + '<br /><em>Member Since</em>: ' + joinDate + '</div>');
        });
      }
    }
  });

  if (complex) {
    $('.messageText').tipTip({
      attribute: 'data-time'
    });
  }
}






/***** AJAX Functions *****/

function updatePosts() {
  if (!window.longPolling) {
    window.clearInterval(window.timer1);
  }

  var encrypt = 'base64';

  $.ajax({
    url: 'api/getMessages.php?rooms=' + roomId + '&messageLimit=100&watchRooms=1&activeUsers=1' + (first ? '&archive=1&messageDateMin=' + (Math.round((new Date()).getTime() / 1000) - 1200) : '&messageIdMin=' + (lastMessage)),
    type: 'GET',
    timeout: timeout,
    async: true,
    data: '',
    contentType: "text/xml; charset=utf-8",
    dataType: "xml",
    cache: false,
    success: getSuccess,
    error: getError,
  });

  if (!window.longPolling) {
    if (totalFails > 10) {
      window.timer1 = window.setInterval(window.updatePosts,30000);
      timeout = 29900;
    }
    else if (totalFails > 5) {
      window.timer1 = window.setInterval(window.updatePosts,10000);
      timeout = 9900;
    }
    else if (totalFails > 0) {
      window.timer1 = window.setInterval(window.updatePosts,5000);
      timeout = 4900;
    }
    else {
      window.timer1 = window.setInterval(window.updatePosts,2500);
      timeout = 2400;
    }
  }
}

function getError(xml) {
  if (window.longPolling) {
    setTimeout(updatePosts,50);
  }
  else {
    totalFails += 1;
    $('#refreshStatus').html('<img src="images/dialog-error.png" alt="Apply" class="standard" />');
  }
}


function getSuccess(xml) {
  if (xml) {
    totalFails = 0;
    var notifyData = '';

    $('#refreshStatus').html('<img src="images/dialog-ok.png" alt="Apply" class="standard" />');


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


      if (complex) {
        var data = '<span id="message' + messageId + '" class="messageLine" style="padding-bottom: 3px; padding-top: 3px; vertical-align: middle;"><img alt="' + userName + '" src="' + avatar + '" style="max-width: 24px; max-height: 24px; padding-right: 3px;" class="userName userNameTable" data-userId="' + userId + '" /><span style="padding: 2px; ' + style + '" class="messageText" data-messageid="' + messageId + '"  data-time="' + messageTime + '">' + text + '</span><br />';
      }
      else {
        var data = '<span id="message' + messageId + '" class="messageLine">' + groupFormatStart + '<span class="userName userNameTable" data-userId="' + userId + '">' + userName + '</span>' + groupFormatEnd + ' @ <em>' + messageTime + '</em>: <span style="padding: 2px; ' + style + '" class="messageText" data-messageid="' + messageId + '">' + text + '</span><br />';
      }

      notifyData += userName + ': ' + text + "\n";

      if (window.reverse) {
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
    });



    if (window.reverse) {
      toBottom();
    }

    if (blur) {
      if (window.soundOn) {
        window.beep();

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

  if (window.longPolling) {
    setTimeout(updatePosts,50);
  }

  first = false;
}


function sendMessage(message,confirmed) {
  confirmed = (confirmed === 1 ? 1 : '');

  $.ajax({
    url: 'api/sendMessage.php',
    type: 'POST',
    data: 'roomId=' + roomId + '&confirmed=' + confirmed + '&message=' + str_replace('+','%2b',str_replace('&','%26',str_replace('%','%25',message))),
    cache: false,
    timeout: 2500,
    success: function(xml) {
      var status = $(xml).find('errorcode').text().trim();
      var emessage = $(xml).find('errormessage').text().trim();
      switch (status) {
        case '':
        break;

        case 'badroom':
        $('<div style="display: none;">A valid room was not provided.</div>').dialog({ title : 'Error'});
        break;

        case 'badmessage':
        $('<div style="display: none;">A valid message was not provided.</div>').dialog({ title : 'Error'});
        break;

        case 'spacemessage':
        $('<div style="display: none;">Too... many... spaces!</div>').dialog({ title : 'Error'});
        break;

        case 'noperm':
        $('<div style="display: none;">You do not have permission to post in this room.</div>').dialog({ title : 'Error'});
        break;

        case 'blockcensor':
        $('<div style="display: none;">' + emessage + '</div>').dialog({ title : 'Error'});
        break;

        case 'confirmcensor':
        $('<div style="display: none;">' + emessage + '<br /><br /><button type="button" onclick="$(this).parent().dialog(&apos;close&apos;);">No</button><button type="button" onclick="sendMessage(&apos;' + escape(message) + '&apos;,1); $(this).parent().dialog(&apos;close&apos;);">Yes</button></div>').dialog({ title : 'Error'});
        break;
      }
    },
    error: function() {
      if (window.reverse) {
        $('#messageList').append('Your message, "' + message + '", could not be sent and will be retried.');
      }
      else {
        $('#messageList').prepend('Your message, "' + message + '", could not be sent and will be retried.');
      }

      sendMessage(message);
    }
  });
}




/***** Initiate getMessages *****/

if (window.longPolling) {
  $(document).ready(function() {
    updatePosts();
  });
}
else {
  window.timer1 = window.setInterval(updatePosts,2500);
}




/***** DOM Parsing *****/

$(document).ready(function() {
  $("#icon_reversePostOrder").button("option", "icons", { primary: 'ui-icon-circle-triangle-' + (window.reverse ? 'n' : 's') } );
  $("#icon_help").button({ icons: {primary:'ui-icon-help'} }).css({height: '32px', width: '32px'});
  $("#icon_note").button({ icons: {primary:'ui-icon-note'} }).css({height: '32px', width: '32px'});
  $("#icon_settings").button({ icons: {primary:'ui-icon-wrench'} }).css({height: '32px', width: '32px'});
  $("#icon_muteSound").button( "option", "icons", { primary: 'ui-icon-volume-on' } );
  $("#icon_url").button( "option", "icons", { primary: 'ui-icon-link' } );
  $("#icon_upload").button( "option", "icons", { primary: 'ui-icon-image' } );
  $("#icon_video").button( "option", "icons", { primary: 'ui-icon-video' } );
  $("#icon_submit").button( "option", "icons", { primary: 'ui-icon-circle-check' } );
  $("#icon_reset").button( "option", "icons", { primary: 'ui-icon-circle-close' } );
  $("#imageUploadSubmitButton").button( "option", "disabled", true);

  $("#icon_reversePostOrder").hover(
    function() {
      $("#icon_reversePostOrder").button("option", "icons", { primary: 'ui-icon-circle-triangle-' + (window.reverse ? 's' : 'n') } );
    },
    function () {
      $("#icon_reversePostOrder").button("option", "icons", { primary: 'ui-icon-circle-triangle-' + (window.reverse ? 'n' : 's') } );
    }
  );

  $("#icon_muteSound").hover(
    function() {
      if (window.soundOn) $("#icon_muteSound").button("option", "icons", { primary: 'ui-icon-volume-off' } );
      else $("#icon_muteSound").button("option", "icons", { primary: 'ui-icon-volume-on' } );
    },
    function () {
      if (window.soundOn) $("#icon_muteSound").button("option", "icons", { primary: 'ui-icon-volume-on' } );
      else $("#icon_muteSound").button("option", "icons", { primary: 'ui-icon-volume-off' } );
    }
  );

  $("#icon_reversePostOrder").click(function() {
    var value = (window.reverse ? 'false' : 'true');
    $.cookie('vrim10-reverseOrder', value, {expires: 7 * 24 * 3600});
    location.reload(true);
  });

  jQTubeUtil.init({
    key: "AI39si5_Dbv6rqUPbSe8e4RZyXkDM3X0MAAtOgCuqxg_dvGTWCPzrtN_JLh9HlTaoC01hCLZCxeEDOaxsjhnH5p7HhZVnah2iQ",
    orderby: "relevance",  // *optional -- "viewCount" is set by default
    time: "this_month",   // *optional -- "this_month" is set by default
    maxResults: 20   // *optional -- defined as 10 results by default
  });

});




/***** Youtube *****/
function youtubeSend(id) {
  $.ajax({
    url: 'uploadFile.php',
    type: 'POST',
    contentType: 'application/x-www-form-urlencoded;charset=UTF-8',
    cache: false,
    data: 'method=youtube&room=' + roomId + '&youtubeUpload=' + escape('http://www.youtube.com/?v=' + id),
    success: function(html) { /*updatePosts();*/ }
  });

  $('#textentryBoxYoutube').dialog('close');
}

callbackFunction = function(response) {
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
}

function updateVids(searchPhrase) {
  jQTubeUtil.search(searchPhrase, callbackFunction);
}




/***** Window Manipulation *****/

function windowBlur () {
  blur = true;
}

function windowFocus() {
  blur = false;
  window.clearInterval(timer3);
  $('#favicon').attr('href',favicon);
}

window.onblur = windowBlur;
window.onfocus = windowFocus;