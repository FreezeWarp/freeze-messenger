<?php header('Content-type: text/javascript'); ?>

/* Slow Connection Mode */

var slowConnection = <?php echo ($slowConnection ? 'true' : 'false'); ?>;

function slowConnectionMode() {
  if (!slowConnection) {
    slowConnection = true;
    clearTimeout(timer1);

    timer2 = setInterval(updatePosts,3000); 
    $('#roomtitle').append('<span id="slowConnectionNotice"> (Slow Connection Mode)</span>');
    $('#slowConnectionButton').html('Fast Connection');
    createCookie('slowConnection',true,7 * 24 * 3600);
  }
  else {
    slowConnection = false;
    clearTimeout(timer2);

    timer1 = setInterval(updatePosts,500);
    $('#slowConnectionNotice').remove();
    $('#slowConnectionButton').html('Slow Connection');
    createCookie('slowConnection',false,7 * 24 * 3600);
  }

}

window.onload = function() { timer1 = setInterval(updatePosts,500); }

window.onblur = function() { blur = true; }
window.onfocus = function() { blur = false; }

function updatePosts() {
  if (!slowConnection) {
    $.ajax({
      url: '/ajax/getMessages.php?room=<?php echo $room['id']; ?>',
      type: 'GET',
      timeout: 350,
      cache: false,
      success: function(html) {
        $('#refreshStatus').html('Refresh Successful');
        totalFails = 0;
        if (html) {
          if (blur && soundOn) { window.beep(); }
          $('#messagelist').<?php echo ($reverse ? 'append' : 'prepend') ?>(html);
          <?php if ($reverse)  echo 'toBottom();
'; ?>
        }
      },
      error: function(html) {
        $('#refreshStatus').html('Refresh Failed');
      }
    });
  }

  else {
    $.ajax({
      url: '/ajax/getMessages.php?room=<?php echo $room['id']; ?>',
      type: 'GET',
      timeout: 2500,
      cache: false,
      success: function(html) {
        totalFails = 0;
        if (html) {
          if (blur && soundOn) { window.beep(); }
          $('#messagelist').<?php echo ($reverse ? 'append' : 'prepend') ?>(html);
          <?php if ($reverse) echo 'toBottom();
'; ?>
        }
      }
    });
  }
}

function stopUpload(success,message) {
  if (success == 1) {
    $.ajax({url: '/ajax/sendMessage.php', type: 'POST', contentType: 'application/x-www-form-urlencoded;charset=UTF-8', cache: false, data: 'room=<?php echo $room['id']; ?>&message=' + escape(message), success: function(html) { /*updatePosts();*/ } });
  }
  $('#textentryBoxMessage').slideDown();
  $('#textentryBoxUpload').slideUp();
  return true;
}

/* Youtube */
function youtubeSend(id) {
  $.ajax({url: '/ajax/sendMessage.php', type: 'POST', contentType: 'application/x-www-form-urlencoded;charset=UTF-8', cache: false, data: 'room=<?php echo $room['id']; ?>&message=' + escape('[youtube]' + id + '[/youtube]'), success: function(html) { /*updatePosts();*/ } });
  $('#textentryBoxMessage').slideDown();
  $('#textentryBoxYoutube').slideUp();
}

callbackFunction = function(response) {
  var html = "";
  for (vid in response.videos) {
    var video = response.videos[vid];
    html += '<tr><td><img src="http://i2.ytimg.com/vi/' + video.videoId + '/default.jpg" /></td><td><a href="javascript: void(0);" onclick="youtubeSend(\'' + video.videoId + '\')">' + video.title + '</a></td></tr>';
  }
  $('#youtubeResults').html(html);
}

function updateVids(searchPhrase) {
  jQTubeUtil.search(searchPhrase, callbackFunction);  // basic: just specify the callback function }
}