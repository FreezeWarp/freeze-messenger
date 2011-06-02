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

function contextMenuParse() {
  $('.username').contextMenu({
    menu: 'userMenu'
  },
  function(action, el) {
    var userid = $(el).attr('data-userid');

    switch(action) {
      case 'private_im':
      ajaxDialogue('content/privateRoom.php?phase=2&userid=' + userid,'Private IM','privateRoomDialogue',1000);
      break;
      case 'profile':
      window.open(window.forumUrl + 'member.php?u=' + userid,'profile' + userid);
      break;
      case 'kick':
      ajaxDialogue('/content/kick.php?userid=' + userid + '&roomid=' + $('body').attr('data-roomid'),'Kick User','kickUserDialogue',1000);
      break;
      case 'ban':
      window.open('/moderate.php&do=banuser2&userid=' + userid,'banuser' + userid);
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
      quickDialogue('This message can be bookmarked using the following archive link:<br /><br /><input type="text" value="http://2.vrim.victoryroad.net/archive.php?roomid=' + $('body').attr('data-roomid') + '&message=' + postid + '" />','Link to This Message','linkMessage');
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
      quickDialogue('This message can be bookmarked using the following archive link:<br /><br /><input type="text" value="http://2.vrim.victoryroad.net/archive.php?roomid=' + $('body').attr('data-roomid') + '&message=' + postid + '" />','Link to This Message','linkMessage');
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
          url: 'ajax/fim-modAction.php?action=deleteroom&roomid=' + postid,
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
      ajaxDialogue('/content/editRoom.php?roomid=' + $(el).attr('data-roomid'),'Edit Room','editRoomDialogue',1000);
      break;
    }
  });

  $('.username').ezpz_tooltip({
    contentId: 'tooltext',
    beforeShow: function(content,el) {
      var thisid = $(el).attr('data-userid');

      if (thisid != $('#tooltext').attr('data-lastuserid')) {
        $('#tooltext').attr('data-lastuserid',thisid);
        $.get("api/getUserInfo.php?userid=" + thisid, function(xml) {
          var username = $(xml).find('userData > username').text();
          var userid = $(xml).find('userData > userid').text();
          var start_tag = unxml($(xml).find('userData > startTag').text());
          var end_tag = unxml($(xml).find('userData > endTag').text());
          var usertitle = $(xml).find('userData > usertitle').text();
          var posts = $(xml).find('userData > postcount').text();
          var joindate = $(xml).find('userData > joindateformatted').text();
          
          content.html('<div style="width: 400px;"><img alt="" src="' + forumUrl + 'image.php?u=' + userid + '" style="float: left;" /><span class="username" data-userid="' + userid + '">' + start_tag + username + end_tag + '</span><br />' + usertitle + '<br /><em>Posts</em>: ' + posts + '<br /><em>Member Since</em>: ' + joindate + '</div>');
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