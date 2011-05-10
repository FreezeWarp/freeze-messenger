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
var timeout = 2400;
var totalFails2 = 0;
var timeout2 = 4900;
var timer3;
var topic;
var lastMessage = 0;
var messages;
var activeUsers;
var soundOn = ($('body').attr('data-ding') === '1' ? true : false);
var reverse = ($('body').attr('data-reverse') === '1' ? 1 : 0);
var light = ($('body').attr('data-mode') === 'light' ? 1 : 0);
var complex = ($('body').attr('data-complex') === '1' ? 1 : 0);
var forumUrl = 'http://www.victoryroad.net/';



function toBottom() {
  document.getElementById('messageList').scrollTop=document.getElementById('messageList').scrollHeight;
}


function faviconFlash() {
  if (navigator.appName === 'Microsoft Internet Explorer') {
    // Do nothing
  }

  else {
    if ($('#favicon').attr('href') === '/images/favicon.gif') {
      $('#favicon').attr('href','/images/favicon2.gif');
    }
    else {
      $('#favicon').attr('href','/images/favicon.gif');
    }
  }
}


/* AJAX functions */
function updatePosts() {
  window.clearInterval(window.timer1);

  if (light) {
    var encrypt = 'plaintext';
  }
  else {
    var encrypt = 'base64';
  }

  $.ajax({
//    url: '/ajax/fim-main.php?room=' + roomid + '&lastMessage=' + lastMessage + '&reverse=' + (reverse ? 1 : 0) + '&encrypt=' + encrypt,
    url: '/api/getMessages.php?rooms=' + roomid + '&messageIdMin=' + (lastMessage) + '&messageLimit=40&watchRooms=1&activeUsers=1&order=' + (reverse ? 'reverse' : 'normal'),
    type: 'GET',
    timeout: timeout,
    cache: false,
    success: function(xml) {
      if (xml) {
        totalFails = 0;

        $('#refreshStatus').html('<img src="/images/dialog-ok.png" alt="Apply" class="standard" />');

        var newTopic = $(xml).find('topic').html();
        if (newTopic) {
          $('#topic' + roomid).html(newTopic);
        }


        $('#activeUsers').html('');
        $(xml).find('activeUsers > user').each(function() {
          var username = $(this).find('username').text();
          var userid = $(this).find('userid').text();
          var displaygroupid = $(this).find('displaygroupid').text();

          $('#activeUsers').append('<span class="username" data-userid="' + userid + '">' + username + '</span>');
        });
      }

      if ($(xml).find('messages > message').length > 0) {
        if (!light) {
          if (blur && soundOn) {
            window.beep();
            window.clearInterval(timer3);
            timer3 = window.setInterval(faviconFlash,1000);
          }
          if (blur && !light) {
            try {
              if (window.external.msIsSiteMode()) {
                window.external.msSiteModeActivate();
              }
            }
            catch(ex) {
              // Supress Error
            }
          }
          
          
          $(xml).find('messages > message').each(function() {
            var text = $(this).find('htmltext').text();
            var messageTime = $(this).find('messagetimeformatted').text();
          
            var messageId = $(this).find('messageid').text();

            var username = $(this).find('userdata > username').text();
            var userid = $(this).find('userdata > userid').text();

            if (complex) {
              $('#messageList').append('<span id="message' + messageId + '" class="messageLine" style="padding-bottom: 3px; padding-top: 3px; vertical-align: middle;"><img alt="" src="' + forumUrl + 'image.php?u=' + userid + '" style="max-width: 32px; max-height: 32px; padding-right: 3px;" class="username usernameTable" data-userid="' + userid + '" time="' + messageTime + '" /><span style="padding: 2px;" class="messageText" data-messageid="' + messageId + '">' + text + '</span><br />');
            }
            else {
              $('#messageList').append('<span id="message' + messageId + '" class="messageLine"><span class="username usernameTable" data-userid="' + userid + '">' + username + '</span> @ <em>' + messageTime + '</em>: <span style="padding: 2px;" class="messageText" data-messageid="' + messageId + '">' + text + '</span><br />');
            }
          
            if (messageId > lastMessage) {
              lastMessage = messageId;
            }
          });
        }

        if (reverse) {
          toBottom();
        }

        if (typeof contextMenuParse === 'function') {
          contextMenuParse();
        }
      }
    },
    error: function(html) {
      totalFails += 1;
      $('#refreshStatus').html('<img src="/images/dialog-error.png" alt="Apply" class="standard" />');
    }
  });

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


function sendMessage(message,confirmed) {
  confirmed = (confirmed === 1 ? 1 : '');

  $.ajax({
    url: '/api/sendMessage.php?roomid=' + roomid + '&confirmed=' + confirmed + '&message=' + str_replace('+','%2b',str_replace('&','%26',str_replace('%','%25',message))),
    type: 'GET',
    cache: false,
    timeout: 2500,
    success: function(xml) {
      var status = $(xml).find('errorcode').text();
      var emessage = $(xml).find('errormessage').text();
      switch (status) {
        case '':
        updatePosts();
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
      if (html === 'success') {
      }
      else {
      }
    },
    error: function() {
      if (reverse) {
        $('#messageList').append('Your message, "' + message + '", could not be sent and will be retried.');
      }
      else {
        $('#messageList').prepend('Your message, "' + message + '", could not be sent and will be retried.');
      }

      sleep(2000);
      sendMessage(message);
    }
  });
}



/***** Youtube *****/
function youtubeSend(id) {
  $.ajax({url: '/uploadFile.php', type: 'POST', contentType: 'application/x-www-form-urlencoded;charset=UTF-8', cache: false, data: 'method=youtube&room=' + roomid + '&youtubeUpload=' + escape('http://www.youtube.com/?v=' + id), success: function(html) { /*updatePosts();*/ } });
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


/***** Other Scripts ******/
if (!light) {
  jQTubeUtil.init({
    key: "AI39si5_Dbv6rqUPbSe8e4RZyXkDM3X0MAAtOgCuqxg_dvGTWCPzrtN_JLh9HlTaoC01hCLZCxeEDOaxsjhnH5p7HhZVnah2iQ",
    orderby: "relevance",  // *optional -- "viewCount" is set by default
    time: "this_month",   // *optional -- "this_month" is set by default
    maxResults: 20   // *optional -- defined as 10 results by default
  });
}

function notify(text,header,id,id2) {
  if ($('#' + id + ' > #' + id + id2).html()) { }
  else {
  if ($('#' + id).html()) {
    $('#' + id).append('<br />' + text);
  }
  else {
    $.jGrowl('<div id="' + id + '"><span id="' + id + id2 + '">' + text + '</span></div>', {
      sticky: true,
      glue: true,
      header: header
    }); 
  }
  }
}




/* Bing! Function */
window.onblur = function() {
  blur = true;
};

window.onfocus = function() {
  blur = false;
  window.clearInterval(timer3);
  $('#favicon').attr('href','/images/favicon.gif');
};


/* Refresh */
window.timer1 = window.setInterval(updatePosts,2500);