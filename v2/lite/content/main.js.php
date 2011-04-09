<?php
header('Content-type: text/javascript');
error_reporting(E_ERROR);

$slowConnection = $_GET['sc'];
$roomid = $_GET['room'];
$reverse = $_GET['r'];
$mode = $_GET['m'];
$ding = $_GET['d'];
?>
/* So... yeah... this is about the messiest file physically possible right now. Once we hit Beta, it should be more managable. */


/* Variable Setting */
var blur = false;
var soundOn = <?php echo ($mode == 'normal' && $ding ? 'true' : 'false'); ?>;
var totalFails = 0;
var timeout = 2400;
var totalFails2 = 0;
var timeout2 = 4900;
var timer3;
var lastMessage;

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
var timer2 = window.setInterval(updateActive,5000);


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
    url: '/ajax/getMessages.php?room=<?php echo $roomid; ?>&lastMessage=' + lastMessage + '&reverse=<?php echo ($reverse ? 1 : 0); ?>&encrypt=base64',
    type: 'GET',
    timeout: timeout,
    cache: false,
    success: function(html) {
      totalFails = 0;
      $('#refreshStatus').html('<img src="/images/dialog-ok.png" alt="Apply" class="standard" />');
      if (html) {
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
          catch(ex) {    }
        }

        $('#messageList').<?php echo ($reverse ? 'append' : 'prepend'); ?>(base64_decode(html));
        <?php if ($reverse)  echo 'toBottom();
'; ?>
        $('.username').contextMenu({
          menu: 'userMenu'
        },
        function(action, el) {
          switch(action) {
            case 'private_im':
            window.open('/index.php?action=privateRoom&phase=2&userid=' + $(el).attr('data-userid'),'privateim' + attr('data-userid')); 
            break;
            case 'profile':
            window.open('http://victoryroad.net/member.php?u=' + $(el).attr('data-userid'),'profile' + attr('data-userid')); 
            break;
            case 'kick':
            var time = prompt('How many minutes should the user be kicked for? (Hint: You can use decimals.');
            if (!time) {
              //window.open('/index.php?action=kick&userid=' + $(el).attr('data-userid') + '&roomid=<?php echo $roomid; ?>','kickuser' + attr('data-userid'));
              alert('No Time Entered');
            }
            else {
              $.ajax({
                url: '/ajax/modAction.php?action=kickuser&userid=' + $(el).attr('data-userid') + '&roomid=<?php echo $roomid; ?>&time=' + time,
                type: 'POST',
                contentType: 'application/x-www-form-urlencoded;charset=UTF-8',
                cache: false,
                timeout: 10000,
                success: function(html) {
                  if (html) { alert(html); }
                },
                error: function() { alert('AJAX Timeout.'); }
              });
            }
            break;
            case 'ban':
            window.open('/index.php?action=moderate&do=banuser2&userid=' + $(el).attr('data-userid'),'banuser' + attr('data-userid'));
            break;
          }
        });
      }
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


function updateActive() {
  window.clearInterval(timer2);

  $.ajax({
    url: '/ajax/ping.php?room=<?php echo $roomid; ?>',
    type: 'GET',
    timeout: timeout2,
    cache: false,
    success: function(html) {
      if (html) $('#activeUsers').html(html);
      totalFails2 = 0;
    },
    error: function() {
      $('#activeUsers').html('Refresh Failed');
      totalFails2++;
    }
  });

  if (totalFails2 > 5) {
    timer2 = window.setInterval(updateActive,30000);
    timeout2 = 29900;
  }
  else if (totalFails2 > 0) {
    timer2 = window.setInterval(updateActive,10000);
    timeout2 = 9900;
  }
  else {
    timer2 = window.setInterval(updateActive,5000);
    timeout2 = 4900;
  }
}


function sendMessage(message) {
  $.ajax({
    url: '/ajax/sendMessage.php',
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

function showAllRooms() {
  $.ajax({
    url: '/ajax/roomList.php?rooms=*',
    timeout: 5000,
    type: 'GET',
    cache: false,
    success: function(html) {
      $('#roomListContainer').html(html);
    },
    error: function() {
      alert('Failed to show all rooms');
    }
  });
}


<?php if ($mode == 'normal') { ?>
function stopUpload(success,message) {
  if (success == 1) {
    sendMessage(message);
  }
  $('#textentryBoxMessage, #roomListTable, #activeUsersContainer').slideDown();
  $('#textentryBoxUpload, #textentryBoxYoutube, #textentryBoxUrl').slideUp();
  return true;
}

/* Youtube */
function youtubeSend(id) {
  $.ajax({url: '/ajax/sendMessage.php', type: 'POST', contentType: 'application/x-www-form-urlencoded;charset=UTF-8', cache: false, data: 'room=<?php echo $roomid; ?>&message=' + escape('[youtube]' + id + '[/youtube]'), success: function(html) { /*updatePosts();*/ } });
  $('#textentryBoxMessage, #roomListTable, #activeUsersContainer').slideDown();
  $('#textentryBoxYoutube').slideUp();
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

<?php } ?>


function toBottom() {
  document.getElementById('messageList').scrollTop=document.getElementById('messageList').scrollHeight;
}


<?php if ($mode == 'normal') { ?>
/* Text Entry */

<?php } ?>