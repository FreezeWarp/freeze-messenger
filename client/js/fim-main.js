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

/* Variable Setting */
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


function toBottom() {
  document.getElementById('messageList').scrollTop = document.getElementById('messageList').scrollHeight;
}


function faviconFlash() {
    if ($('#favicon').attr('href') === 'images/favicon.gif') {
      $('#favicon').attr('href','images/favicon2.gif');
    }
    else {
      $('#favicon').attr('href','images/favicon.gif');
    }
  
}


/* AJAX functions */
function updatePosts() {
  if (!window.longPolling) {
    window.clearInterval(window.timer1);
  }

  if (light) {
    var encrypt = 'plaintext';
  }
  else {
    var encrypt = 'base64';
  }

  $.ajax({
    url: 'api/getMessages.php?rooms=' + roomid + '&messageIdMin=' + (lastMessage) + '&messageLimit=100&watchRooms=1&activeUsers=1&archive=' + (first ? '1&messageDateMin=' + (Math.round((new Date()).getTime() / 1000) - 600) : '0'),
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

    var newTopic = $(xml).find('topic').html();
    if (newTopic) {
      $('#topic' + roomid).html(newTopic);
    }


    $('#activeUsers').html('');
    var activeUserHtml = new Array;

    $(xml).find('activeUsers > user').each(function() {
     var username = $(this).find('username').text();
     var userid = $(this).find('userid').text();
     var displaygroupid = $(this).find('displaygroupid').text();
     var start_tag = unxml($(this).find('startTag').text());
     var end_tag = unxml($(this).find('endTag').text());

     activeUserHtml.push('<span class="username" data-userid="' + userid + '">' + start_tag + username + end_tag + '</span>');
    });

    $('#activeUsers').html(activeUserHtml.join(', '));
  }

  if ($(xml).find('messages > message').length > 0) {
    $(xml).find('messages > message').each(function() {
      var text = $(this).find('htmltext').text();
      var messageTime = $(this).find('messagetimeformatted').text();

      var messageId = $(this).find('messageid').text();

      var username = $(this).find('userdata > username').text();
      var userid = $(this).find('userdata > userid').text();
      var groupFormatStart = unxml($(this).find('userdata > startTag').text());
      var groupFormatEnd = unxml($(this).find('userdata > endTag').text());

      var styleColor = $(this).find('defaultFormatting > color').text();
      var styleHighlight = $(this).find('defaultFormatting > highlight').text();
      var styleFontface = $(this).find('defaultFormatting > fontface').text();
      var styleGeneral = parseInt($(this).find('defaultFormatting > general').text());

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
        var data = '<span id="message' + messageId + '" class="messageLine" style="padding-bottom: 3px; padding-top: 3px; vertical-align: middle;"><img alt="' + username + '" src="' + forumUrl + 'image.php?u=' + userid + '" style="max-width: 24px; max-height: 24px; padding-right: 3px;" class="username usernameTable" data-userid="' + userid + '" /><span style="padding: 2px; ' + style + '" class="messageText" data-messageid="' + messageId + '"  data-time="' + messageTime + '">' + text + '</span><br />';
      }
      else {
        var data = '<span id="message' + messageId + '" class="messageLine">' + groupFormatStart + '<span class="username usernameTable" data-userid="' + userid + '">' + username + '</span>' + groupFormatEnd + ' @ <em>' + messageTime + '</em>: <span style="padding: 2px; ' + style + '" class="messageText" data-messageid="' + messageId + '">' + text + '</span><br />';
      }
      
      notifyData += username + ': ' + text + "\n";
      
      if (reverse) {
        $('#messageList').append(data);
      }
      else {
        $('#messageList').prepend(data);
      }
          
      if (messageId > lastMessage) {
        lastMessage = messageId;
      }
    });
          
          

    if (reverse) {
      toBottom();
    }
  }
        
        
  if (!light) {
    if (blur && soundOn) {
      window.beep();

      if (navigator.appName === 'Microsoft Internet Explorer') {
        timer3 = window.setInterval(faviconFlash,1000);

        window.clearInterval(timer3);
      }
    }

    if (blur && notify) {
      if (window.webkitNotifications) {
        webkitNotify('images/favicon.gif', 'New Message', notifyData);
      }
    }

    if (blur) {
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
    url: 'api/sendMessage.php?roomid=' + roomid + '&confirmed=' + confirmed + '&message=' + str_replace('+','%2b',str_replace('&','%26',str_replace('%','%25',message))),
    type: 'GET',
    cache: false,
    timeout: 2500,
    success: function(xml) {
      var status = $(xml).find('errorcode').text();
      var emessage = $(xml).find('errormessage').text();
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
      if (reverse) {
        $('#messageList').append('Your message, "' + message + '", could not be sent and will be retried.');
      }
      else {
        $('#messageList').prepend('Your message, "' + message + '", could not be sent and will be retried.');
      }

      sendMessage(message);
    }
  });
}


/* Bing! Function */
window.onblur = function() {
  blur = true;
};

window.onfocus = function() {
  blur = false;
  window.clearInterval(timer3);
  $('#favicon').attr('href','images/favicon.gif');
};


/* Refresh */
if (window.longPolling) {
  $(document).ready(function() {
    updatePosts();
  });
}
else {
  window.timer1 = window.setInterval(updatePosts,2500);
}