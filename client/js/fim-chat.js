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

$(document).ready(function() {
  $("#icon_reversePostOrder").button("option", "icons", { primary: 'ui-icon-circle-triangle-' + (reverse ? 'n' : 's') } );
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
  
  if (!light) {
    jQTubeUtil.init({
      key: "AI39si5_Dbv6rqUPbSe8e4RZyXkDM3X0MAAtOgCuqxg_dvGTWCPzrtN_JLh9HlTaoC01hCLZCxeEDOaxsjhnH5p7HhZVnah2iQ",
      orderby: "relevance",  // *optional -- "viewCount" is set by default
      time: "this_month",   // *optional -- "this_month" is set by default
      maxResults: 20   // *optional -- defined as 10 results by default
    });
  }

  resize();
});

/***** Youtube *****/
function youtubeSend(id) {
  $.ajax({
    url: '/uploadFile.php',
    type: 'POST',
    contentType: 'application/x-www-form-urlencoded;charset=UTF-8',
    cache: false,
    data: 'method=youtube&room=' + roomid + '&youtubeUpload=' + escape('http://www.youtube.com/?v=' + id),
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

function resize () {
  var windowWidth = (window.innerWidth ? window.innerWidth : document.documentElement.clientWidth);
  var windowHeight = (window.innerHeight ? window.innerHeight : document.documentElement.clientHeight);

  if (layout == 'alt') {
    
  }
  else if (light) {
  /* Body Padding: 10px
   * "Enter Message" Table Padding: 10px
   *** TD Padding: 2px (on Standard Styling)
   * Message Input Container Padding : 3px (all padding-left)
   * Message Input Text Area Padding: 6px */
    $('#messageInput').css('width',(windowWidth - 10 - 10 - 2 - 3 - 6));
  }
  else {
    $('#messageList').css('height',(windowHeight - 230));
    $('#messageList').css('max-width',((windowWidth - 10) * .75));
  /* Body Padding: 10px
   * Right Area Width: 75%
   * "Enter Message" Table Padding: 10px
   *** TD Padding: 2px (on Standard Styling)
   * Message Input Container Padding : 3px (all padding-left)
   * Left Button Width: 36px
   * Message Input Text Area Padding: 6px */
    $('#messageInput').css('width',(((windowWidth - 10) * .75) - 10 - 2 - 3 - 36 - 6 - 20));
  }
}

$(window).resize(resize);