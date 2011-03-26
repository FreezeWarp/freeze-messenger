<?php
header('Content-type: text/javascript');
error_reporting(E_ERROR);

$slowConnection = $_GET['sc'];
$roomid = $_GET['room'];
$reverse = $_GET['r'];
$mode = $_GET['m'];
?>
/* So... yeah... this is about the messiest file physically possible right now. Once we hit Beta, it should be more managable. */

function str_replace(search,replace,subject){var result="";var oldi=0;for(i=subject.indexOf(search);i>-1;i=subject.indexOf(search,i)){result+=subject.substring(oldi,i);result+=replace;i+=search.length;oldi=i;}
return result+subject.substring(oldi,subject.length);}

/* Variable Setting */
var totalFails = 0;
var timeout = 2400;
var totalFails2 = 0;
var timeout2 = 4900;
var timer3;
var lastMessage;


/* Refresh */
var timer1 = window.setInterval(updatePosts,2500);
var timer2 = window.setInterval(updateActive,5000);


/* AJAX functions */
function updatePosts() {
  window.clearInterval(timer1);

  $.ajax({
    url: '/ajax/getMessages.php?room=<?php echo $roomid; ?>&lastMessage=' + lastMessage + '&light=1&reverse=<?php echo ($reverse ? 1 : 0); ?>&disableVideo=true',
    type: 'GET',
    timeout: timeout,
    cache: false,
    success: function(html) {
      totalFails = 0;
      $('#refreshStatus').html('<img src="/images/dialog-ok.png" alt="Apply" class="standard" />');
      if (html) {
        $('#messageList').<?php echo ($reverse ? 'append' : 'prepend'); ?>(html);
        <?php if ($reverse)  echo 'toBottom();
'; ?>
      }
    },
    error: function(html) {
      totalFails += 1;
      $('#refreshStatus').html('<img src="/images/dialog-error.png" alt="Apply" class="standard" />');
    }
  });

  if (totalFails > 10) {
    alert('The chat is currently having extensive refresh issues, possibly due to a slow connection. You may wish to refresh. Currently trying to refresh the message list every minute.');
    timer1 = window.setInterval(updatePosts,30000);
    timeout = 59000;
  }
  else if (totalFails > 5) {
    timer1 = window.setInterval(updatePosts,10000);
    timeout = 29000;
  }
  else if (totalFails > 0) {
    timer1 = window.setInterval(updatePosts,5000);
    timeout = 9900;
  }
  else {
    timer1 = window.setInterval(updatePosts,2500);
    timeout = 4900;
  }
}


function updateActive() {
  window.clearInterval(timer2);

  $.ajax({
    url: '/ajax/ping.php?room=<?php echo $roomid; ?>&light=true',
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
    },
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


function toBottom() {
  document.getElementById('messageListContainer').scrollTop=document.getElementById('messageListContainer').scrollHeight;
}