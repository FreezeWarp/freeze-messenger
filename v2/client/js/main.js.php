<?php
header('Content-type: text/javascript');
error_reporting(E_ERROR);

$slowConnection = $_GET['sc'];
$roomid = $_GET['room'];
$reverse = $_GET['r'];
$mode = $_GET['m'];
$ding = $_GET['d'];
?>
/* Variable Setting */
var blur = false;
var soundOn = <?php echo ($mode == 'normal' && $ding ? 'true' : 'false'); ?>;
var totalFails = 0;
var timeout = 2400;
var totalFails2 = 0;
var timeout2 = 4900;
var timer3;
var lastMessage;
var messages;
var activeUsers;

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
    url: 'ajax/getMessages.php?room=<?php echo $roomid; ?>&lastMessage=' + lastMessage + '&reverse=<?php echo ($reverse ? 1 : 0); ?>&encrypt=base64',
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

        $('#messageList').<?php echo ($reverse ? 'append' : 'prepend'); ?>(base64_decode(messages));

        <?php if ($reverse)  echo 'toBottom();
'; ?>
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
    data: 'room=<?php echo $roomid; ?>&message=' + str_replace('+','%2b',str_replace('&','%26',str_replace('%','%25',message))),
    success: function(html) {
      if (html == 'success') {
        updatePosts();
      }
      else {
        alert(html);
      }
    },
    error: function() {
      $('#messageList').<?php echo ($reverse ? 'append' : 'prepend'); ?>('Your message, "' + message + '", could not be sent and will be retried.');
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
  $.ajax({url: 'ajax/sendMessage.php', type: 'POST', contentType: 'application/x-www-form-urlencoded;charset=UTF-8', cache: false, data: 'room=<?php echo $roomid; ?>&message=' + escape('[youtube]' + id + '[/youtube]'), success: function(html) { /*updatePosts();*/ } });
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