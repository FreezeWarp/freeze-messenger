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
    switch(action) {
      case 'private_im':
      window.open('/index.php?action=privateRoom&phase=2&userid=' + $(el).attr('data-userid'),'privateim' + attr('data-userid')); 
      break;
      case 'profile':
      window.open('http://victoryroad.net/member.php?u=' + $(el).attr('data-userid'),'profile' + attr('data-userid')); 
      break;
      case 'kick':
      $('#kick').dialog();
      break;
      case 'ban':
      window.open('/index.php?action=moderate&do=banuser2&userid=' + $(el).attr('data-userid'),'banuser' + attr('data-userid'));
      break;
    }
  });

  $('.messageLine .messageText').contextMenu({
    menu: 'messageMenu'
  },
  function(action, el) {
    switch(action) {
      case 'delete':
      break;
    }
  });

  $('.room').contextMenu({
    menu: 'roomMenu'
  },
  function(action, el) {
    switch(action) {
      case 'delete':
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
        $('#tooltext').attr('data-lastuserid',thisid)
        $.get("ajax/fim-usernameHover.php?userid=" + thisid, function(html) {
           content.html(html);
        });
      }
    }
  });
}
