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
var lastMessage;
var messages;
var activeUsers;
var ding = ($('body').attr('data-ding') == '1' ? true : false);
var reverse = ($('body').attr('data-reverse') == '1' ? true : false);
var soundOn = (ding ? true : false);

/* Bing! Function */
window.onblur = function() {
  blur = true;
}
window.onfocus = function() {
  blur = false;
  window.clearInterval(timer3);
  $('#favicon').attr('href','/images/favicon.gif');
}


/* Refresh */
var timer1 = window.setInterval(updatePosts,2500);


function faviconFlash() {
  if (navigator.appName == 'Microsoft Internet Explorer') { }
  else {
    if ($('#favicon').attr('href') == '/images/favicon.gif') {
      $('#favicon').attr('href','/images/favicon2.gif');
    }
    else {
      $('#favicon').attr('href','/images/favicon.gif');
    }
  }
}

/* AJAX functions */
function updatePosts() {
  window.clearInterval(timer1);
//alert('ajax/fim-main.php?room=' + roomid + '&lastMessage=' + lastMessage + '&reverse=' + (reverse ? 1 : 0) + '&encrypt=base64');
  $.ajax({
    url: 'ajax/fim-main.php?room=' + roomid + '&lastMessage=' + lastMessage + '&reverse=' + (reverse ? 1 : 0) + '&encrypt=base64',
    type: 'GET',
    timeout: timeout,
    cache: false,
    success: function(html) {
      totalFails = 0;
      eval(html);

      $('#refreshStatus').html('<img src="/images/dialog-ok.png" alt="Apply" class="standard" />');

      if (topic) {
        $('#title' + roomid).html(base64_decode(topic));
      }

      if (activeUsers) {
        $('#activeUsers').html(base64_decode(activeUsers));
      }

      if (messages) {
        if (blur && soundOn) {
          window.beep();
          window.clearInterval(timer3);
          timer3 = window.setInterval(faviconFlash,1000);
        }
        if (blur) {
          try {
            if (window.external.msIsSiteMode()) {
              window.external.msSiteModeActivate();
            }
          }
          catch(ex) {
          }
        }

        if (reverse) $('#messageList').append(base64_decode(messages));
        else $('#messageList').prepend(base64_decode(messages));

        if (reverse) toBottom();
      }

      contextMenuParse();

      messages = '';
      topic = '';
      activeUsers = '';
    },
    error: function(html) {
      totalFails += 1;
      $('#refreshStatus').html('<img src="/images/dialog-error.png" alt="Apply" class="standard" />');
    }
  });

  if (totalFails > 10) {
    timer1 = window.setInterval(updatePosts,30000);
    timeout = 29900;
  }
  else if (totalFails > 5) {
    timer1 = window.setInterval(updatePosts,10000);
    timeout = 9900;
  }
  else if (totalFails > 0) {
    timer1 = window.setInterval(updatePosts,5000);
    timeout = 4900;
  }
  else {
    timer1 = window.setInterval(updatePosts,2500);
    timeout = 2400;
  }
}


function sendMessage(message,confirmed) {
  confirmed = (confirmed == 1 ? 1 : '');
  
  $.ajax({
    url: 'ajax/fim-sendMessage.php',
    type: 'POST',
    cache: false,
    timeout: 5000,
    data: 'room=' + roomid + '&confirmed=' + confirmed + '&message=' + str_replace('+','%2b',str_replace('&','%26',str_replace('%','%25',message))),
    success: function(html) {
      if (html == 'success') {
        updatePosts();
      }
      else {
        $('<div style="display: none;">' + html + '</div>').dialog({ title : 'Error'});
      }
    },
    error: function() {
      if (reverse) $('#messageList').append('Your message, "' + message + '", could not be sent and will be retried.');
      else $('#messageList').prepend('Your message, "' + message + '", could not be sent and will be retried.');

      sleep(2000);
      sendMessage(message);
    }
  });
}

/* Youtube */
function youtubeSend(id) {
  $.ajax({url: 'uploadFile.php', type: 'POST', contentType: 'application/x-www-form-urlencoded;charset=UTF-8', cache: false, data: 'method=youtube&room=' + roomid + '&youtubeUpload=' + escape('http://www.youtube.com/?v=' + id), success: function(html) { /*updatePosts();*/ } });
  $('#textentryBoxYoutube').dialog('close');
}

callbackFunction = function(response) {
  var html = "";
  var num = 0;
  for (vid in response.videos) {
    var video = response.videos[vid];
    num ++;

    if (num % 3 == 1) html += '<tr>';

    html += '<td><img src="http://i2.ytimg.com/vi/' + video.videoId + '/default.jpg" style="width: 80px; height: 60px;" /><br /><small><a href="javascript: void(0);" onclick="youtubeSend(\'' + video.videoId + '\')">' + video.title + '</a></small></td>';

    if (num % 3 == 0) html += '</tr>';
  }

  if (num % 3 != 0) html + '</tr>';
  $('#youtubeResults').html(html);
}

function updateVids(searchPhrase) {
  jQTubeUtil.search(searchPhrase, callbackFunction);
}


$(document).ready(function() {
  $("#icon_reversePostOrder").button("option", "icons", { primary: 'ui-icon-circle-triangle-' + (reverse ? 'n' : 's') } );
  $("#icon_help").button({ icons: {primary:'ui-icon-help'} }).css({height: '32px', width: '32px'});
  $("#icon_muteSound").button( "option", "icons", { primary: 'ui-icon-volume-on' } );
  $("#icon_url").button( "option", "icons", { primary: 'ui-icon-link' } );
  $("#icon_upload").button( "option", "icons", { primary: 'ui-icon-image' } );
  $("#icon_video").button( "option", "icons", { primary: 'ui-icon-video' } );
  $("#icon_submit").button( "option", "icons", { primary: 'ui-icon-circle-check' } );
  $("#icon_reset").button( "option", "icons", { primary: 'ui-icon-circle-close' } );
  $("#imageUploadSubmitButton").button( "option", "disabled", true);

  $("#icon_reversePostOrder").hover(
    function() {
      $("#icon_reversePostOrder").button("option", "icons", { primary: 'ui-icon-circle-triangle-' + (reverse ? 's' : 'n') } );
    },
    function () {
      $("#icon_reversePostOrder").button("option", "icons", { primary: 'ui-icon-circle-triangle-' + (reverse ? 'n' : 's') } );
    }
  );

  $("#icon_muteSound").hover(
    function() {
      if (soundOn) $("#icon_muteSound").button("option", "icons", { primary: 'ui-icon-volume-off' } );
      else $("#icon_muteSound").button("option", "icons", { primary: 'ui-icon-volume-on' } );
    },
    function () {
      if (soundOn) $("#icon_muteSound").button("option", "icons", { primary: 'ui-icon-volume-on' } );
      else $("#icon_muteSound").button("option", "icons", { primary: 'ui-icon-volume-off' } );
    }
  );

  $("#icon_reversePostOrder").click(function() {
    var value = (reverse ? 'false' : 'true');
    $.cookie('vrim10-reverseOrder', value, {expires: 7 * 24 * 3600});
    location.reload(true);
  });

  resize();
});

function showAllRooms() {
  $.ajax({
    url: 'ajax/fim-roomList.php?rooms=*',
    timeout: 5000,
    type: 'GET',
    cache: false,
    success: function(html) {
      $('#rooms').html(html);
    },
    error: function() {
      alert('Failed to show all rooms');
    }
  });
}

function toBottom() {
  document.getElementById('messageList').scrollTop=document.getElementById('messageList').scrollHeight;
}

function resize () {
  var windowWidth = document.documentElement.clientWidth;
  var windowHeight = document.documentElement.clientHeight;

  $('#messageList').css('height',(windowHeight - 230));
  /* Body Padding: 10px
   * Right Area Width: 75%
   * "Enter Message" Table Padding: 10px
   *** TD Padding: 2px (on Standard Styling)
   * Message Input Container Padding : 3px (all padding-left)
   * Left Button Width: 36px
   * Message Input Text Area Padding: 6px */
  $('#messageInput').css('width',(((windowWidth - 10) * .75) - 10 - 2 - 3 - 36 - 6));
}

$(window).resize(resize);

jQTubeUtil.init({
  key: "AI39si5_Dbv6rqUPbSe8e4RZyXkDM3X0MAAtOgCuqxg_dvGTWCPzrtN_JLh9HlTaoC01hCLZCxeEDOaxsjhnH5p7HhZVnah2iQ",
  orderby: "relevance",  // *optional -- "viewCount" is set by default
  time: "this_month",   // *optional -- "this_month" is set by default
  maxResults: 20   // *optional -- defined as 10 results by default
});