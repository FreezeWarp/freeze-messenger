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
      window.open('http://victoryroad.net/member.php?u=' + userid,'profile' + userid);
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
          url: '/ajax/fim-modAction.php?action=deletepost&postid=' + postid,
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
          url: '/ajax/fim-modAction.php?action=deleteroom&roomid=' + postid,
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

/*  $('.room').click(
    function() {
      var roomSwitchId = $(this).attr('data-roomid');

      if (!roomSwitchId) {
        quickDialogue('The roomid could not be obtained, possibly due to a scripting error.','Error','roomError');
        return false;
      }

      $.ajax({
        url: 'ajax/fim-roomDisplay.php',
        type: 'POST',
        cache: false,
        timeout: 5000,
        data: 'roomid=' + roomSwitchId,
        success: function(html) {
          if (html) {
            try {
              roomid = roomSwitchId;
              $('form[action^="/uploadFile"]').attr('action','/uploadFile.php?room=' + roomSwitchId);
              $('#roomTemplateContainer').html(html);
            }
            catch(err) {
              quickDialogue('An error caused the page to become unstable. <a href="/index.php?room=' + roomSwitchId + '">Click here to refresh.</a>','Error','roomError');
              debug.log(err);
            }
          }
          else {
            quickDialogue('The room data appears to be invalid. <a href="/index.php?room=' + roomSwitchId + '">Click here to attempt to go there with a page refresh.</a>','Error','roomError');
          }
        },
        error: function() {
          quickDialogue('The room could not be switched. <a href="/index.php?room=' + roomSwitchId + '">Click here to attempt to go there with a page refresh.</a>','Error','roomError');
        }
      });
      return false;
    }
  );*/

  $('.username').ezpz_tooltip({
    contentId: 'tooltext',
    beforeShow: function(content,el) {
      var thisid = $(el).attr('data-userid');

      if (thisid != $('#tooltext').attr('data-lastuserid')) {
        $('#tooltext').attr('data-lastuserid',thisid);
        $.get("ajax/fim-usernameHover.php?userid=" + thisid, function(html) {
           content.html(html);
        });
      }
    }
  });
}
