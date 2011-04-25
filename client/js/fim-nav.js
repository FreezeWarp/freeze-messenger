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

var roomid;

function ajaxDialogue(uri,title,id,width,cF) {
  var dialog = $('<div style="display: none;" id="' + id +  '"></div>').appendTo('body');
  dialog.load(
    uri,
    {},
    function (responseText, textStatus, XMLHttpRequest) {
      $('button').button();

      if (light) {
        var windowWidth = document.documentElement.clientWidth;
        if (width > windowWidth || !width) {
          width = windowWidth;
        }
      }

      dialog.dialog({
        width: (width ? width : 600),
        title: title,
        hide: "puff",
        close: function() {
          $('#' + id).empty().remove(); // Housecleaning, needed if we want the next dialouge to work properly.
          if (cF) {
            cF();
          }
        }
      });
    }
  );

  return false;
}

function quickDialogue(content,title,id,width) {
  var dialog = $('<div style="display: none;" id="' + id +  '">' + content + '</div>').appendTo('body');
  dialog.dialog({
    width: (width ? width: 600),
    title: title,
    hide: "puff",
    close: function() {
      $('#' + id).empty().remove(); // Housecleaning, needed if we want the next dialouge to work properly.
    }
  });

  return false;
}

function quickConfirm(text) {
  $('<div id="dialog-confirm"><span class="ui-icon ui-icon-alert" style="float: left; margin: 0px 7px 20px 0px;"></span>' + text + '</div>').dialog({
    resizable: false,
    height: 240,
    modal: true,
    hide: "puff",
    buttons: {
      Confirm: function() {
        $(this).dialog("close");
        return true;
      },
      Cancel: function() {
        $(this).dialog("close");
        return false;
      }
    }
  });
}

function showAllRooms() {
  $.ajax({
    url: '/ajax/fim-roomList.php?rooms=*',
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

$(document).ready(function(){
  roomid = $('body').attr('data-roomid');

  $('#menu').accordion({
    autoHeight: false,
    navigation: true,
    clearStyle: true
  });

  $('table > thead > tr:first-child > td:first-child, table > tr:first-child > td:first-child').addClass('ui-corner-tl');
  $('table > thead > tr:first-child > td:last-child, table > tr:first-child > td:last-child').addClass('ui-corner-tr');
  $('table > tbody > tr:last-child > td:first-child, table > tr:last-child > td:first-child').addClass('ui-corner-bl');
  $('table > tbody > tr:last-child > td:last-child, table > tr:last-child > td:last-child').addClass('ui-corner-br');

  $('button').button();

  $('a#kick').click(function() {
    ajaxDialogue('/content/kick.php','Kick User','kickUserDialogue',1000);
  });

  $('a#privateRoom').click(function() {
    ajaxDialogue('/content/privateRoom.php','Enter Private Room','privateRoomDialogue',1000);
  });

  $('a#manageKick').click(function() {
    ajaxDialogue('/content/manageKick.php?roomid=' + roomid,'Manage Kicked Users in This Room','manageKickDialogue',600);
  });

  $('a#online').click(function() {
    ajaxDialogue('/content/online.php','View Active Users','onlineDialogue',600);
  });

  $('a#createRoom').click(function() {
    ajaxDialogue('/content/createRoom.php','Create a New Room','createRoomDialogue',1000);
  });

  $('a#editRoom').click(function() {
    ajaxDialogue('/content/editRoom.php?roomid=' + roomid,'Edit Room','editRoomDialogue',1000);
  });

  $('a.editRoomMulti').click(function() {
    ajaxDialogue('/content/editRoom.php?roomid=' + $(this).attr('data-roomid'),'Edit Room','editRoomDialogue',1000);
  });

  $('a#changeSettings,a.changeSettingsMulti').click(function() {
    ajaxDialogue('/content/options.php','Change My Settings','changeSettingsDialogue',1000,
      function() {
        $('.colorpicker').empty().remove();
      }
    );
  });

  $('#icon_help').click(function() {
    ajaxDialogue('/content/help.php','Help','helpDialogue',1000);
  });

  $('#copyrightLink').click(function() {
    quickDialogue('<div style="text-align: center;">FIM, including (but not limited to) FIM\'s private web API, FIM\'s public XML API, FIM\'s legacy public CSV API, and all sourcecode created for use originally with FIM &copy; 2010-2011 Joseph T. Parsons.<br /><br />jQuery, jQueryUI, and all jQueryUI Themeroller Themes &copy; The jQuery Project.<br /><br />jGrowl &copy; 2009 Stan Lemon.<br />jQuery Cookie Plugin &copy; 2006 Klaus Hartl<br />EZPZ Tooltip &copy; 2009 Mike Enriquez<br />Beeper &copy; 2009 Patrick Mueller<br />Error Logger Utility &copy; Ben Alman<br />Context Menu &copy; 2008 Cory S.N. LaViska<br />jQTubeUtil &copy; 2010 Nirvana Tikku</div>','FIM Copyrights','copyrightDialogue');
  });

  $(document).ready(function(){
    $("#kickForm").submit(function(){
      data = $("#kickForm").serialize(); // Serialize the form data for AJAX.

        $.post("content/kick.php?phase=2",data); // Send the form data via AJAX.

        $("#kick").dialog('close');

        return false; // Don\'t submit the form.
    });
  });
});