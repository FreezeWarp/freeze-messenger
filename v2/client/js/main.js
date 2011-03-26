/* Variable Setting */
var blur = false;
var totalFails = 0;
var timeout = 2400;
var totalFails2 = 0;
var timeout2 = 4900;
var timer3;
var lastMessage;
var messages;
var activeUsers;
var ding = $('data[name=ding]').attr('value');
var reverse = $('data[name=reverse]').attr('value');
var roomid = $('data[name=roomid]').attr('value');
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

  $.ajax({
    url: 'ajax/getMessages.php?room=' + roomid + '&lastMessage=' + lastMessage + '&reverse=' + reverse + '&encrypt=base64',
    type: 'GET',
    timeout: timeout,
    cache: false,
    success: function(html) {
      totalFails = 0;
      eval(html);

      $('#refreshStatus').html('<img src="/images/dialog-ok.png" alt="Apply" class="standard" />');

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
    },
    error: function(html) {
      totalFails += 1;
      $('#refreshStatus').html('<img src="/images/dialog-error.png" alt="Apply" class="standard" />');
    }
  });

  if (totalFails > 10) {
    timer1 = window.setInterval(updatePosts,30000);
    timeout = 29000;
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


function sendMessage(message) {
  $.ajax({
    url: 'ajax/sendMessage.php',
    type: 'POST',
    cache: false,
    timeout: 5000,
    data: 'room=' + roomid + '&message=' + str_replace('+','%2b',str_replace('&','%26',str_replace('%','%25',message))),
    success: function(html) {
      if (html == 'success') {
        updatePosts();
      }
      else {
        alert(html);
      }
    },
    error: function() {
      if (reverse) $('#messageList').append('Your message, "' + message + '", could not be sent and will be retried.');
      else $('#messageList').prepend('Your message, "' + message + '", could not be sent and will be retried.');

      sleep(2);
      sendMessage(message);
    }
  });
}

function stopUpload(success,message) {
  if (success == 1) {
    sendMessage(message);
  }
  return true;
}

/* Youtube */
function youtubeSend(id) {
  $.ajax({url: 'ajax/sendMessage.php', type: 'POST', contentType: 'application/x-www-form-urlencoded;charset=UTF-8', cache: false, data: 'room=' + roomid + '&message=' + escape('[youtube]' + id + '[/youtube]'), success: function(html) { /*updatePosts();*/ } });
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
  $("#icon_help").button( "option", "icons", { primary: 'ui-icon-help' } );
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

  resize();
});

function resize () {
  $('#messageList').css('height',(window.innerHeight - 230));
  $('#messageInput').css('width',(window.innerWidth - (window.innerWidth * .3)));
}

$(window).resize(resize);